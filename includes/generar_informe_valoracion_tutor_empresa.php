<?php
declare(strict_types=1);

use Mpdf\Mpdf;
use Mpdf\Output\Destination;

ob_start();

function esc(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function value(array $row, string $key): string { return (string)($row[$key] ?? ''); }
function month_name_es(int $month): string { $m=[1=>'enero',2=>'febrero',3=>'marzo',4=>'abril',5=>'mayo',6=>'junio',7=>'julio',8=>'agosto',9=>'septiembre',10=>'octubre',11=>'noviembre',12=>'diciembre']; return $m[$month] ?? ''; }
function checkbox(bool $checked): string { return $checked ? '☒' : '☐'; }
function limpiarTextoPlano(mixed $valor, int $max = 0): string {
  $texto = trim((string)$valor);
  $texto = strip_tags($texto);
  $texto = preg_replace('/\s+/u', ' ', $texto) ?? $texto;
  if ($max > 0 && mb_strlen($texto, 'UTF-8') > $max) $texto = mb_substr($texto, 0, $max, 'UTF-8') . '…';
  return $texto;
}
function ensure_mpdf_temp_dir(): string {
  $tmpDir = sys_get_temp_dir() . '/mpdf_tmp';
  if (!is_dir($tmpDir) && !mkdir($tmpDir, 0755, true) && !is_dir($tmpDir)) throw new RuntimeException('No se pudo crear el directorio temporal de mPDF.');
  return $tmpDir;
}
function create_mpdf_like_project(string $docsPath): Mpdf {
  $mpdf = new Mpdf([
    'mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 10, 'margin_right' => 10,
    'margin_top' => 10, 'margin_bottom' => 10, 'default_font_size' => 12,
    'default_font' => 'arial', 'shrink_tables_to_fit' => 0,
    'tempDir' => ensure_mpdf_temp_dir(), 'keep_table_proportions' => true, 'use_kwt' => true,
  ]);
  $mpdf->setBasePath($docsPath . '/');
  $mpdf->showImageErrors = true;
  return $mpdf;
}

$fase = 'inicio';
$id_practica = null;

try {
  $rootDir = dirname(__DIR__);
  $scriptPath = __FILE__;
  $scriptRealPath = realpath(__FILE__) ?: __FILE__;
  $dbPath = $rootDir . '/db.php';
  $autoloadPath = $rootDir . '/vendor/autoload.php';
  $templateLegacyPath = $rootDir . '/docs/practicas_informe_valoracion_final_tutor_empresa.html';
  $templateMpdfPath = $rootDir . '/docs/practicas_informe_valoracion_final_tutor_empresa_mpdf.html';
  $templatePath = $templateMpdfPath;

  $isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1'], true) || in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
  $debugVersion = $isLocal && (($_GET['debug_version'] ?? '0') === '1');
  $debugHtml = $isLocal && (($_GET['debug_html'] ?? '0') === '1');
  $debugMinimal = $isLocal && (($_GET['debug_minimal'] ?? '0') === '1');
  $debugFakeData = $isLocal && (($_GET['debug_fake_data'] ?? '0') === '1');

  $fase = 'validando parámetro id_practica';
  $id_practica = filter_var($_GET['id_practica'] ?? ($_GET['id'] ?? null), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
  if ($id_practica === false || $id_practica === null) throw new RuntimeException('No se recibió un id_practica válido.');

  if ($debugVersion) {
    if (ob_get_length()) ob_end_clean();
    header('Content-Type: text/plain; charset=UTF-8');
    echo "Archivo ejecutado:
" . $scriptPath . "

";
    echo "__DIR__:
" . __DIR__ . "

";
    echo "realpath(__FILE__):
" . $scriptRealPath . "

";
    echo "Última modificación:
" . date('Y-m-d H:i:s', filemtime(__FILE__)) . "

";
    echo "Hash archivo (md5):
" . md5_file(__FILE__) . "

";
    echo "db.php:
" . $dbPath . ' — ' . (file_exists($dbPath) ? 'existe' : 'NO existe') . "

";
    echo "vendor/autoload.php:
" . $autoloadPath . ' — ' . (file_exists($autoloadPath) ? 'existe' : 'NO existe') . "

";
    echo "Plantilla usada:
" . $templatePath . "
";
    echo 'Plantilla usada existe: ' . (file_exists($templatePath) ? 'sí' : 'NO') . "
";
    echo 'Plantilla antigua existe: ' . (file_exists($templateLegacyPath) ? 'sí' : 'NO') . "
";
    echo 'Nombre exacto plantilla: ' . basename($templatePath) . "
";
    echo 'Usa plantilla _mpdf.html: ' . (str_ends_with($templatePath, '_mpdf.html') ? 'sí' : 'NO') . "
";
    exit;
  }

  require_once $dbPath;
  require_once $autoloadPath;
  if (!class_exists(Mpdf::class)) throw new RuntimeException('No se pudo cargar mPDF.');

  if ($debugMinimal) {
    $htmlMin = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body><h1>Prueba mínima informe tutor</h1><p>ID práctica: ' . (int)$id_practica . '</p></body></html>';
    $mpdf = create_mpdf_like_project($rootDir . '/docs');
    $mpdf->WriteHTML($htmlMin);
    if (ob_get_length()) ob_end_clean();
    $mpdf->Output('debug_minimal_' . (int)$id_practica . '.pdf', Destination::INLINE);
    exit;
  }

  $pdo = db();
  $fase = 'consulta principal';
  $stmt = $pdo->prepare('SELECT p.*, a.nombre AS alumno_nombre, a.apellido1 AS alumno_apellido1, a.apellido2 AS alumno_apellido2,
      e.nombre AS empresa_nombre, e.nombre_comercial AS empresa_nombre_comercial, e.convenio AS empresa_convenio,
      et.nombre AS tutor_nombre, et.apellido1 AS tutor_apellido1, et.apellido2 AS tutor_apellido2,
      ce.curso_escolar, ci.ciclo AS ciclo_nombre, ci.codigo AS ciclo_codigo, ac.id_curso_escolar AS ac_id_curso_escolar, ac.id_ciclo AS ac_id_ciclo, c.curso AS curso_ordinal,
      (SELECT direccion_correo FROM correos c1 WHERE c1.entidad_tipo = "alumno" AND c1.id_entidad = a.id_alumno ORDER BY c1.id_correo ASC LIMIT 1) AS alumno_email,
      (SELECT direccion_correo FROM correos c2 WHERE c2.entidad_tipo = "empresa_tutor" AND c2.id_entidad = et.id_empresas_tutor ORDER BY c2.id_correo ASC LIMIT 1) AS tutor_empresa_email
    FROM practicas p
    LEFT JOIN alumnos a ON a.id_alumno = p.id_alumno
    LEFT JOIN empresas e ON e.id_empresa = p.id_empresa
    LEFT JOIN empresas_tutores et ON et.id_empresas_tutor = p.id_empresa_tutor
    LEFT JOIN alumno_curso ac ON ac.id_alumno = p.id_alumno AND ac.id_curso_escolar = (SELECT MAX(ac2.id_curso_escolar) FROM alumno_curso ac2 WHERE ac2.id_alumno = p.id_alumno)
    LEFT JOIN cursos_escolares ce ON ce.id_curso_escolar = ac.id_curso_escolar
    LEFT JOIN grupos gr ON gr.id_grupo = ac.id_grupo
    LEFT JOIN ciclos ci ON ci.id_ciclo = gr.id_ciclo
    LEFT JOIN cursos c ON c.id_curso = ac.id_curso
    WHERE p.id_practica = :id_practica LIMIT 1');
  $stmt->execute(['id_practica' => $id_practica]);
  $practice = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$practice) throw new RuntimeException('No existe la práctica con id ' . (int)$id_practica . '.');

  $htmlTemplate = (string)file_get_contents($templatePath);
  if ($htmlTemplate === '') throw new RuntimeException('Plantilla vacía: ' . $templatePath);

  $repl = [];
  $setText = static function(array &$repl, string $k, mixed $v, int $max = 0): void { $repl[$k] = esc(limpiarTextoPlano((string)$v, $max)); };
  $setText($repl,'{{CURSO_ACADEMICO}}',value($practice,'curso_escolar'),120);
  $setText($repl,'{{NUM_CONVENIO}}',value($practice,'empresa_convenio'),120);
  $setText($repl,'{{NUM_ANEXO_RELACION}}',value($practice,'anexo'),120);
  $setText($repl,'{{ALUMNO_APELLIDOS}}',trim(value($practice,'alumno_apellido1').' '.value($practice,'alumno_apellido2')),180);
  $setText($repl,'{{ALUMNO_NOMBRE}}',value($practice,'alumno_nombre'),120);
  $setText($repl,'{{ALUMNO_EMAIL}}',value($practice,'alumno_email'),180);
  $setText($repl,'{{CENTRO_TRABAJO_DENOMINACION}}',value($practice,'empresa_nombre_comercial')!==''?value($practice,'empresa_nombre_comercial'):value($practice,'empresa_nombre'),250);
  $setText($repl,'{{TUTOR_EMPRESA_APELLIDOS}}',trim(value($practice,'tutor_apellido1').' '.value($practice,'tutor_apellido2')),180);
  $setText($repl,'{{TUTOR_EMPRESA_NOMBRE}}',value($practice,'tutor_nombre'),120);
  $setText($repl,'{{TUTOR_EMPRESA_EMAIL}}',value($practice,'tutor_empresa_email'),180);
  $setText($repl,'{{CICLO_DENOMINACION}}',value($practice,'ciclo_nombre'),250);
  $setText($repl,'{{GRADO}}',value($practice,'curso_ordinal'),120);
  $setText($repl,'{{CODIGO_CICLO}}',value($practice,'ciclo_codigo'),120);
  $setText($repl,'{{HORAS_REALIZADAS}}',value($practice,'horas'),80);
  $setText($repl,'{{AREAS_PUESTOS_VALORACION}}',value($practice,'areas_puestos_trabajo'),900);
  $setText($repl,'{{VALORACION_COMPETENCIAS_TRANSVERSALES}}',value($practice,'valoracion_competencias_transversales'),900);
  $setText($repl,'{{OBSERVACIONES_TUTOR_EMPRESA}}',value($practice,'observaciones_tutor_empresa'),900);
  $setText($repl,'{{MOTIVOS_NO_SUPERACION}}',value($practice,'motivos_no_superacion'),700);
  $setText($repl,'{{LOCALIDAD_FIRMA}}',value($practice,'localidad_firma'),150);
  $today = new DateTimeImmutable('today');
  $repl['{{DIA_FIRMA}}'] = esc($today->format('d')); $repl['{{MES_FIRMA}}'] = esc(month_name_es((int)$today->format('n'))); $repl['{{ANIO_FIRMA}}'] = esc($today->format('Y'));
  for ($i=1;$i<=3;$i++) $repl['{{PERIODO_'.$i.'}}']=checkbox(((int)($practice['periodo']??0))===$i);
  for ($i=1;$i<=6;$i++) { $repl['{{RA_'.$i.'_MODULO}}']=''; $repl['{{RA_'.$i.'_RESULTADO_APRENDIZAJE}}']=''; $repl['{{RA_'.$i.'_SUPERADO}}']=''; $repl['{{RA_'.$i.'_NO_SUPERADO}}']=''; }

  if ($debugFakeData) foreach ($repl as $k => $v) if (!str_contains($k, 'PERIODO_')) $repl[$k] = esc('Dato demo');

  $html = strtr($htmlTemplate, $repl);
  if ($debugHtml) { if (ob_get_length()) ob_end_clean(); header('Content-Type: text/html; charset=UTF-8'); echo $html; exit; }

  $mpdf = create_mpdf_like_project($rootDir . '/docs');
  $mpdf->WriteHTML($html);
  if ($mpdf->page > 100) throw new RuntimeException('El informe se ha generado con ' . $mpdf->page . ' páginas. Archivo: ' . $scriptRealPath . ' plantilla: ' . $templatePath);

  if (ob_get_length()) ob_end_clean();
  $mpdf->Output('informe_valoracion_tutor_empresa_' . (int)$id_practica . '.pdf', Destination::DOWNLOAD);
  exit;
} catch (Throwable $e) {
  error_log('[generar_informe_valoracion_tutor_empresa] id_practica=' . (string)($id_practica ?? 'null') . ' fase=' . $fase . ' message=' . $e->getMessage());
  if (ob_get_length()) ob_end_clean();
  $isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1'], true) || in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
  http_response_code(500);
  if ($isLocal) {
    echo '<h1>No se pudo generar el informe.</h1><p><strong>Fase:</strong> ' . esc($fase) . '</p><p><strong>ID práctica:</strong> ' . (int)($id_practica ?? 0) . '</p><p><strong>Error real:</strong> ' . esc($e->getMessage()) . '</p><p><strong>Archivo:</strong> ' . esc(__FILE__) . '</p><p><strong>Hash:</strong> ' . esc(md5_file(__FILE__)) . '</p><p><strong>Fecha mod:</strong> ' . esc(date('Y-m-d H:i:s', filemtime(__FILE__))) . '</p><p><strong>Plantilla:</strong> ' . esc((string)($templatePath ?? 'N/D')) . '</p>';
    exit;
  }
  exit('No se pudo generar el informe. Revisa el log de errores del servidor.');
}
