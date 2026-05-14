<?php
declare(strict_types=1);

use Mpdf\Mpdf;
use Mpdf\Output\Destination;

ob_start();

function esc(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function value(array $row, string $key): string { return (string)($row[$key] ?? ''); }
function month_name_es(int $month): string { $m=[1=>'enero',2=>'febrero',3=>'marzo',4=>'abril',5=>'mayo',6=>'junio',7=>'julio',8=>'agosto',9=>'septiembre',10=>'octubre',11=>'noviembre',12=>'diciembre']; return $m[$month] ?? ''; }
function checkbox(bool $checked): string { return $checked ? '☒' : '☐'; }
function limpiarTextoPdf(mixed $valor, int $max = 0): string {
  $texto = trim((string)$valor);
  $texto = strip_tags($texto);
  $texto = preg_replace('/\s+/u', ' ', $texto) ?? $texto;
  if ($max > 0 && mb_strlen($texto, 'UTF-8') > $max) { $texto = mb_substr($texto, 0, $max, 'UTF-8') . '…'; }
  return esc($texto);
}

$fase = 'inicio';
$id_practica = null;

try {
  $isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1'], true) || in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
  $debugHtml = $isLocal && (($_GET['debug_html'] ?? '0') === '1');
  $debugPdf = $isLocal && (($_GET['debug_pdf'] ?? '0') === '1');

  $fase = 'validando parámetro id_practica';
  $id_practica = filter_var($_GET['id_practica'] ?? ($_GET['id'] ?? null), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
  if ($id_practica === false || $id_practica === null) throw new RuntimeException('No se recibió un id_practica válido.');

  $dbPath = __DIR__ . '/../db.php'; require_once $dbPath;
  $autoloadPath = __DIR__ . '/../vendor/autoload.php'; require_once $autoloadPath;
  if (!class_exists(Mpdf::class)) throw new RuntimeException('No se pudo cargar mPDF (clase Mpdf\\Mpdf no encontrada).');

  $pdo = db();
  $fase = 'ejecutando consulta principal de práctica';
  $stmt = $pdo->prepare('SELECT p.*, a.nombre AS alumno_nombre, a.apellido1 AS alumno_apellido1, a.apellido2 AS alumno_apellido2,
      e.nombre AS empresa_nombre, e.nombre_comercial AS empresa_nombre_comercial, e.convenio AS empresa_convenio,
      et.nombre AS tutor_nombre, et.apellido1 AS tutor_apellido1, et.apellido2 AS tutor_apellido2,
      ce.curso_escolar, ci.ciclo AS ciclo_nombre, ci.codigo AS ciclo_codigo, ci.id_ciclo,
      ac.id_curso_escolar AS ac_id_curso_escolar, ac.id_ciclo AS ac_id_ciclo, c.curso AS curso_ordinal,
      (SELECT direccion_correo FROM correos c1 WHERE c1.entidad_tipo = "alumno" AND c1.id_entidad = a.id_alumno ORDER BY c1.id_correo ASC LIMIT 1) AS alumno_email,
      (SELECT direccion_correo FROM correos c2 WHERE c2.entidad_tipo = "empresa_tutor" AND c2.id_entidad = et.id_empresas_tutor ORDER BY c2.id_correo ASC LIMIT 1) AS tutor_empresa_email
    FROM practicas p
    LEFT JOIN alumnos a ON a.id_alumno = p.id_alumno
    LEFT JOIN empresas e ON e.id_empresa = p.id_empresa
    LEFT JOIN empresas_tutores et ON et.id_empresas_tutor = p.id_empresa_tutor
    LEFT JOIN alumno_curso ac ON ac.id_alumno = p.id_alumno
      AND ac.id_curso_escolar = (SELECT MAX(ac2.id_curso_escolar) FROM alumno_curso ac2 WHERE ac2.id_alumno = p.id_alumno)
    LEFT JOIN cursos_escolares ce ON ce.id_curso_escolar = ac.id_curso_escolar
    LEFT JOIN grupos gr ON gr.id_grupo = ac.id_grupo
    LEFT JOIN ciclos ci ON ci.id_ciclo = gr.id_ciclo
    LEFT JOIN cursos c ON c.id_curso = ac.id_curso
    WHERE p.id_practica = :id_practica LIMIT 1');
  $stmt->execute(['id_practica' => $id_practica]);
  $practice = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$practice) throw new RuntimeException('No existe la práctica con id ' . (int)$id_practica . '.');

  $html = (string)file_get_contents(__DIR__ . '/../docs/practicas_informe_valoracion_final_tutor_empresa.html');
  $docsDir = __DIR__ . '/../docs';
  $html = preg_replace_callback('~(<img\b[^>]*\bsrc=["\'])([^"\']+)(["\'][^>]*>)~iu', static function (array $m) use ($docsDir): string {
    $path = $docsDir . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim($m[2])), DIRECTORY_SEPARATOR);
    if (!is_file($path)) return $m[0];
    $mime = function_exists('mime_content_type') ? (string)mime_content_type($path) : 'image/png';
    $data = file_get_contents($path);
    return $data === false ? $m[0] : ($m[1] . ('data:' . $mime . ';base64,' . base64_encode($data)) . $m[3]);
  }, $html) ?? $html;

  $raRows = [];
  $courseId = (int)($practice['ac_id_curso_escolar'] ?? 0);
  $cycleId = (int)($practice['ac_id_ciclo'] ?? 0);
  if ($courseId > 0 && $cycleId > 0) {
    $raStmt = $pdo->prepare('SELECT m.materia_general, m.materia_propia, ra.numero AS ra_numero
      FROM practicas_ras pr
      LEFT JOIN resultados_aprendizaje ra ON ra.id_ra = pr.id_ra
      LEFT JOIN modulos m ON m.id_modulo = COALESCE(pr.id_modulo, ra.id_modulo)
      WHERE pr.id_curso_escolar = :id_curso_escolar AND pr.id_ciclo = :id_ciclo
      ORDER BY m.codigo ASC, CAST(ra.numero AS UNSIGNED) ASC, ra.numero ASC, pr.id_practica_ra ASC');
    $raStmt->execute(['id_curso_escolar' => $courseId, 'id_ciclo' => $cycleId]);
    $raRows = $raStmt->fetchAll(PDO::FETCH_ASSOC);
  }

  $truncados = [];
  $setText = static function(array &$repl, array &$truncados, string $k, mixed $v, int $max = 0): void {
    $plain = preg_replace('/\s+/u', ' ', trim(strip_tags((string)$v))) ?? '';
    if ($max > 0 && mb_strlen($plain, 'UTF-8') > $max) $truncados[$k] = mb_strlen($plain, 'UTF-8');
    $repl[$k] = limpiarTextoPdf($v, $max);
  };
  $repl = [];
  $setText($repl, $truncados, '{{CURSO_ACADEMICO}}', value($practice, 'curso_escolar'), 120);
  $setText($repl, $truncados, '{{NUM_CONVENIO}}', value($practice, 'empresa_convenio'), 120);
  $setText($repl, $truncados, '{{NUM_ANEXO_RELACION}}', value($practice, 'anexo'), 120);
  $setText($repl, $truncados, '{{ALUMNO_APELLIDOS}}', trim(value($practice, 'alumno_apellido1') . ' ' . value($practice, 'alumno_apellido2')), 180);
  $setText($repl, $truncados, '{{ALUMNO_NOMBRE}}', value($practice, 'alumno_nombre'), 120);
  $setText($repl, $truncados, '{{ALUMNO_EMAIL}}', value($practice, 'alumno_email'), 180);
  $setText($repl, $truncados, '{{CENTRO_TRABAJO_DENOMINACION}}', value($practice, 'empresa_nombre_comercial') !== '' ? value($practice, 'empresa_nombre_comercial') : value($practice, 'empresa_nombre'), 250);
  $setText($repl, $truncados, '{{TUTOR_EMPRESA_APELLIDOS}}', trim(value($practice, 'tutor_apellido1') . ' ' . value($practice, 'tutor_apellido2')), 180);
  $setText($repl, $truncados, '{{TUTOR_EMPRESA_NOMBRE}}', value($practice, 'tutor_nombre'), 120);
  $setText($repl, $truncados, '{{TUTOR_EMPRESA_EMAIL}}', value($practice, 'tutor_empresa_email'), 180);
  $setText($repl, $truncados, '{{CICLO_DENOMINACION}}', value($practice, 'ciclo_nombre'), 250);
  $setText($repl, $truncados, '{{GRADO}}', value($practice, 'curso_ordinal'), 120);
  $setText($repl, $truncados, '{{CODIGO_CICLO}}', value($practice, 'ciclo_codigo'), 120);
  $setText($repl, $truncados, '{{HORAS_REALIZADAS}}', value($practice, 'horas'), 80);
  $setText($repl, $truncados, '{{AREAS_PUESTOS_VALORACION}}', value($practice, 'areas_puestos_trabajo'), 900);
  $setText($repl, $truncados, '{{VALORACION_COMPETENCIAS_TRANSVERSALES}}', value($practice, 'valoracion_competencias_transversales'), 900);
  $setText($repl, $truncados, '{{OBSERVACIONES_TUTOR_EMPRESA}}', value($practice, 'observaciones_tutor_empresa'), 900);
  $setText($repl, $truncados, '{{MOTIVOS_NO_SUPERACION}}', value($practice, 'motivos_no_superacion'), 700);
  $setText($repl, $truncados, '{{LOCALIDAD_FIRMA}}', value($practice, 'localidad_firma'), 150);

  $today = new DateTimeImmutable('today');
  $repl['{{DIA_FIRMA}}'] = esc($today->format('d')); $repl['{{MES_FIRMA}}'] = esc(month_name_es((int)$today->format('n'))); $repl['{{ANIO_FIRMA}}'] = esc($today->format('Y'));
  for ($i = 1; $i <= 3; $i++) $repl['{{PERIODO_' . $i . '}}'] = checkbox(((int)($practice['periodo'] ?? 0)) === $i);
  for ($i = 1; $i <= 6; $i++) {
    $row = $raRows[$i - 1] ?? [];
    $setText($repl, $truncados, '{{RA_' . $i . '_MODULO}}', (($row['materia_general'] ?? '') !== '' ? ($row['materia_general'] ?? '') : ($row['materia_propia'] ?? '')), 180);
    $setText($repl, $truncados, '{{RA_' . $i . '_RESULTADO_APRENDIZAJE}}', trim((string)($row['ra_numero'] ?? '')) !== '' ? ('RA ' . trim((string)$row['ra_numero'])) : '', 300);
    $repl['{{RA_' . $i . '_SUPERADO}}'] = '';
    $repl['{{RA_' . $i . '_NO_SUPERADO}}'] = '';
  }

  $html = strtr($html, $repl);
  if (preg_match('/{{[^}]+}}/', $html, $m)) throw new RuntimeException('Quedan placeholders sin sustituir en la plantilla. Ejemplo: ' . $m[0]);

  $diag = [];
  foreach (['position:absolute', 'table-layout: fixed', 'word-wrap: break-word', 'page-break-inside: avoid', '::after', 'transform'] as $frag) if (stripos($html, $frag) !== false) $diag[] = $frag;
  preg_match_all('/\b\S{81,}\b/u', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), $palabrasLargas);
  preg_match_all('/\s{5,}/u', $html, $espaciosLargos);

  $pdfTempDir = sys_get_temp_dir() . '/mpdf_tmp'; if (!is_dir($pdfTempDir)) mkdir($pdfTempDir, 0755, true);
  $mpdf = new Mpdf(['tempDir' => $pdfTempDir, 'mode' => 'utf-8', 'format' => 'A4', 'orientation' => 'P', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 10, 'default_font' => 'dejavusans']);
  $mpdf->shrink_tables_to_fit = 1; $mpdf->keep_table_proportions = true; $mpdf->useSubstitutions = false;

  if ($debugHtml) {
    if (ob_get_length()) ob_end_clean();
    header('Content-Type: text/html; charset=UTF-8');
    echo $html;
    echo '<hr><h2>Diagnóstico debug_html</h2><ul>';
    echo '<li>Longitud total HTML: ' . strlen($html) . '</li><li>RAs cargados: ' . count($raRows) . '</li><li>Campos truncados: ' . count($truncados) . '</li>';
    echo '<li>Palabras >80 caracteres: ' . count($palabrasLargas[0] ?? []) . '</li><li>Secuencias largas de espacios: ' . count($espaciosLargos[0] ?? []) . '</li>';
    echo '<li>Placeholders pendientes: 0</li></ul>';
    if (!empty($truncados)) echo '<pre>' . esc(json_encode($truncados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>';
    if (!empty($diag)) echo '<pre>Patrones sospechosos: ' . esc(implode(', ', $diag)) . '</pre>';
    exit;
  }

  $fase = 'escribiendo HTML en mPDF';
  $mpdf->SetBasePath(__DIR__ . '/../docs/');
  $mpdf->WriteHTML($html);
  if ($mpdf->page > 3) throw new RuntimeException('El informe se ha generado con ' . $mpdf->page . ' páginas. La maquetación HTML está rota. Revisa la plantilla del informe.');

  if (ob_get_length()) ob_end_clean();
  $mpdf->Output('informe_valoracion_tutor_empresa_' . (int)$id_practica . '.pdf', $debugPdf ? Destination::INLINE : Destination::DOWNLOAD);
  exit;
} catch (Throwable $e) {
  error_log('[generar_informe_valoracion_tutor_empresa] id_practica=' . (string)($id_practica ?? 'null') . ' fase=' . $fase . ' message=' . $e->getMessage());
  if (ob_get_length()) ob_end_clean();
  $isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1'], true) || in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
  http_response_code(500);
  if ($isLocal) {
    echo '<h1>No se pudo generar el informe.</h1><p><strong>Fase:</strong> ' . esc($fase) . '</p><p><strong>ID práctica:</strong> ' . (int)($id_practica ?? 0) . '</p><p><strong>Error real:</strong> ' . esc($e->getMessage()) . '</p><p><strong>Archivo:</strong> ' . esc($e->getFile()) . '</p><p><strong>Línea:</strong> ' . (int)$e->getLine() . '</p>';
    exit;
  }
  exit('No se pudo generar el informe. Revisa el log de errores del servidor.');
}
