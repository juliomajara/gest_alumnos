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
function diagnosticarPatronesEnDatos(string $texto): array {
  $patterns = ['position:absolute','position: absolute','table-layout','word-wrap','overflow-wrap','page-break-inside','min-height','::after','<html','<body','<style'];
  $found = [];
  $lower = mb_strtolower($texto, 'UTF-8');
  foreach ($patterns as $p) if (str_contains($lower, mb_strtolower($p, 'UTF-8'))) $found[] = $p;
  return $found;
}
function newMpdfInstance(): Mpdf {
  $tmp = sys_get_temp_dir() . '/mpdf_tmp';
  if (!is_dir($tmp)) mkdir($tmp, 0755, true);
  $mpdf = new Mpdf(['tempDir' => $tmp, 'mode' => 'utf-8', 'format' => 'A4', 'orientation' => 'P', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 10, 'default_font' => 'dejavusans']);
  $mpdf->shrink_tables_to_fit = 1;
  $mpdf->keep_table_proportions = true;
  $mpdf->useSubstitutions = false;
  $mpdf->SetBasePath(__DIR__ . '/../docs/');
  return $mpdf;
}

$fase = 'inicio';
$id_practica = null;

try {
  $isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1'], true) || in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
  $debugHtml = $isLocal && (($_GET['debug_html'] ?? '0') === '1');
  $debugPdf = $isLocal && (($_GET['debug_pdf'] ?? '0') === '1');
  $debugMinimal = $isLocal && (($_GET['debug_minimal'] ?? '0') === '1');
  $debugBlocks = $isLocal && (($_GET['debug_blocks'] ?? '0') === '1');
  $debugFakeData = $isLocal && (($_GET['debug_fake_data'] ?? '0') === '1');

  $fase = 'validando parámetro id_practica';
  $id_practica = filter_var($_GET['id_practica'] ?? ($_GET['id'] ?? null), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
  if ($id_practica === false || $id_practica === null) throw new RuntimeException('No se recibió un id_practica válido.');

  require_once __DIR__ . '/../db.php';
  require_once __DIR__ . '/../vendor/autoload.php';
  if (!class_exists(Mpdf::class)) throw new RuntimeException('No se pudo cargar mPDF (clase Mpdf\\Mpdf no encontrada).');

  if ($debugMinimal) {
    $htmlMin = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>@page { size: A4 portrait; margin: 10mm; } body { font-family: dejavusans; font-size: 9pt; }</style></head><body><h1>Prueba mínima informe tutor</h1><p>ID práctica: ' . (int)$id_practica . '</p></body></html>';
    $mpdf = newMpdfInstance();
    $mpdf->WriteHTML($htmlMin);
    if ($mpdf->page > 3) throw new RuntimeException('Debug minimal: generado con ' . $mpdf->page . ' páginas (>3). El problema está en configuración mPDF/WriteHTML.');
    if (ob_get_length()) ob_end_clean();
    $mpdf->Output('debug_minimal_' . (int)$id_practica . '.pdf', Destination::INLINE);
    exit;
  }

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

  $templatePath = __DIR__ . '/../docs/practicas_informe_valoracion_final_tutor_empresa_mpdf.html';
  $htmlTemplate = (string)file_get_contents($templatePath);
  if ($htmlTemplate === '') throw new RuntimeException('La plantilla está vacía: ' . $templatePath);

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

  $dbMeta = []; $truncados = []; $peligrosos = [];
  $setText = static function(array &$repl, array &$dbMeta, array &$truncados, array &$peligrosos, string $k, string $dbField, mixed $v, int $max = 0): void {
    $raw = (string)$v;
    $plain = limpiarTextoPlano($raw, 0);
    $dbMeta[$k] = ['field' => $dbField, 'length' => mb_strlen($plain, 'UTF-8')];
    $pat = diagnosticarPatronesEnDatos($raw);
    if (!empty($pat)) $peligrosos[$k] = ['field' => $dbField, 'patterns' => $pat];
    if ($max > 0 && mb_strlen($plain, 'UTF-8') > $max) $truncados[$k] = mb_strlen($plain, 'UTF-8');
    $repl[$k] = esc(limpiarTextoPlano($raw, $max));
  };
  $repl = [];

  $setText($repl,$dbMeta,$truncados,$peligrosos,'{{CURSO_ACADEMICO}}','cursos_escolares.curso_escolar',value($practice,'curso_escolar'),120);
  $setText($repl,$dbMeta,$truncados,$peligrosos,'{{NUM_CONVENIO}}','empresas.convenio',value($practice,'empresa_convenio'),120);
  $setText($repl,$dbMeta,$truncados,$peligrosos,'{{NUM_ANEXO_RELACION}}','practicas.anexo',value($practice,'anexo'),120);
  $setText($repl,$dbMeta,$truncados,$peligrosos,'{{ALUMNO_APELLIDOS}}','alumnos.apellido1/apellido2',trim(value($practice,'alumno_apellido1').' '.value($practice,'alumno_apellido2')),180);
  $setText($repl,$dbMeta,$truncados,$peligrosos,'{{ALUMNO_NOMBRE}}','alumnos.nombre',value($practice,'alumno_nombre'),120);
  $setText($repl,$dbMeta,$truncados,$peligrosos,'{{ALUMNO_EMAIL}}','correos.direccion_correo',value($practice,'alumno_email'),180);
  $setText($repl,$dbMeta,$truncados,$peligrosos,'{{CENTRO_TRABAJO_DENOMINACION}}','empresas.nombre/nombre_comercial',value($practice,'empresa_nombre_comercial')!==''?value($practice,'empresa_nombre_comercial'):value($practice,'empresa_nombre'),250);
  $setText($repl,$dbMeta,$truncados,$peligrosos,'{{TUTOR_EMPRESA_APELLIDOS}}','empresas_tutores.apellido1/apellido2',trim(value($practice,'tutor_apellido1').' '.value($practice,'tutor_apellido2')),180);
  $setText($repl,$dbMeta,$truncados,$peligrosos,'{{TUTOR_EMPRESA_NOMBRE}}','empresas_tutores.nombre',value($practice,'tutor_nombre'),120);
  $setText($repl,$dbMeta,$truncados,$peligrosos,'{{TUTOR_EMPRESA_EMAIL}}','correos.direccion_correo',value($practice,'tutor_empresa_email'),180);
  $setText($repl,$dbMeta,$truncados,$peligrosos,'{{CICLO_DENOMINACION}}','ciclos.ciclo',value($practice,'ciclo_nombre'),250);
  $setText($repl,$dbMeta,$truncados,$peligrosos,'{{GRADO}}','cursos.curso',value($practice,'curso_ordinal'),120);
  $setText($repl,$dbMeta,$truncados,$peligrosos,'{{CODIGO_CICLO}}','ciclos.codigo',value($practice,'ciclo_codigo'),120);
  $setText($repl,$dbMeta,$truncados,$peligrosos,'{{HORAS_REALIZADAS}}','practicas.horas',value($practice,'horas'),80);
  $setText($repl,$dbMeta,$truncados,$peligrosos,'{{AREAS_PUESTOS_VALORACION}}','practicas.areas_puestos_trabajo',value($practice,'areas_puestos_trabajo'),900);
  $setText($repl,$dbMeta,$truncados,$peligrosos,'{{VALORACION_COMPETENCIAS_TRANSVERSALES}}','practicas.valoracion_competencias_transversales',value($practice,'valoracion_competencias_transversales'),900);
  $setText($repl,$dbMeta,$truncados,$peligrosos,'{{OBSERVACIONES_TUTOR_EMPRESA}}','practicas.observaciones_tutor_empresa',value($practice,'observaciones_tutor_empresa'),900);
  $setText($repl,$dbMeta,$truncados,$peligrosos,'{{MOTIVOS_NO_SUPERACION}}','practicas.motivos_no_superacion',value($practice,'motivos_no_superacion'),700);
  $setText($repl,$dbMeta,$truncados,$peligrosos,'{{LOCALIDAD_FIRMA}}','practicas.localidad_firma',value($practice,'localidad_firma'),150);

  $today = new DateTimeImmutable('today');
  $repl['{{DIA_FIRMA}}'] = esc($today->format('d'));
  $repl['{{MES_FIRMA}}'] = esc(month_name_es((int)$today->format('n')));
  $repl['{{ANIO_FIRMA}}'] = esc($today->format('Y'));
  for ($i=1;$i<=3;$i++) $repl['{{PERIODO_'.$i.'}}']=checkbox(((int)($practice['periodo']??0))===$i);
  for ($i=1;$i<=6;$i++) {
    $row = $raRows[$i-1] ?? [];
    $setText($repl,$dbMeta,$truncados,$peligrosos,'{{RA_'.$i.'_MODULO}}','modulos.materia_general/materia_propia',(($row['materia_general'] ?? '') !== '' ? ($row['materia_general'] ?? '') : ($row['materia_propia'] ?? '')),180);
    $setText($repl,$dbMeta,$truncados,$peligrosos,'{{RA_'.$i.'_RESULTADO_APRENDIZAJE}}','resultados_aprendizaje.numero',trim((string)($row['ra_numero'] ?? '')) !== '' ? ('RA ' . trim((string)$row['ra_numero'])) : '',300);
    $repl['{{RA_'.$i.'_SUPERADO}}']=''; $repl['{{RA_'.$i.'_NO_SUPERADO}}']='';
  }

  if ($debugFakeData) {
    foreach ($repl as $k => $v) if (!str_contains($k, 'PERIODO_')) $repl[$k] = esc('Dato demo');
  }

  $html = strtr($htmlTemplate, $repl);
  if (preg_match('/{{[^}]+}}/', $html, $m)) throw new RuntimeException('Quedan placeholders sin sustituir en la plantilla. Ejemplo: ' . $m[0]);

  $blocks = [
    'Cabecera mínima' => '<h1>Cabecera</h1>',
    'Datos del alumno' => '<table><tr><td>' . $repl['{{ALUMNO_APELLIDOS}}'] . '</td><td>' . $repl['{{ALUMNO_NOMBRE}}'] . '</td></tr></table>',
    'Datos empresa/tutor' => '<table><tr><td>' . $repl['{{CENTRO_TRABAJO_DENOMINACION}}'] . '</td></tr></table>',
    'Datos estudios' => '<table><tr><td>' . $repl['{{CICLO_DENOMINACION}}'] . '</td></tr></table>',
    'Valoración áreas' => '<table><tr><td>' . $repl['{{AREAS_PUESTOS_VALORACION}}'] . '</td></tr></table>',
    'Competencias transversales' => '<table><tr><td>' . $repl['{{VALORACION_COMPETENCIAS_TRANSVERSALES}}'] . '</td></tr></table>',
    'Observaciones del tutor' => '<table><tr><td>' . $repl['{{OBSERVACIONES_TUTOR_EMPRESA}}'] . '</td></tr></table>',
    'Tabla RAs' => '<table><tr><td>'.$repl['{{RA_1_MODULO}}'].'</td><td>'.$repl['{{RA_1_RESULTADO_APRENDIZAJE}}'].'</td></tr></table>',
    'Motivos no superación' => '<table><tr><td>' . $repl['{{MOTIVOS_NO_SUPERACION}}'] . '</td></tr></table>',
    'Firma/notas' => '<p>En ' . $repl['{{LOCALIDAD_FIRMA}}'] . '</p>',
    'Documento completo' => $html,
  ];

  $blockResults = []; $susBlock = 'N/D'; $susField = 'N/D';
  foreach ($blocks as $name => $blockHtml) {
    $mp = newMpdfInstance();
    $mp->WriteHTML('<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>' . $blockHtml . '</body></html>');
    $pages = $mp->page;
    $state = $pages > 3 ? 'ERROR' : 'OK';
    $blockResults[$name] = ['pages' => $pages, 'state' => $state];
    if ($susBlock === 'N/D' && $pages > 3) {
      $susBlock = $name;
      if ($name === 'Observaciones del tutor') $susField = 'practicas.observaciones_tutor_empresa';
    }
  }

  if ($debugBlocks) {
    if (ob_get_length()) ob_end_clean();
    header('Content-Type: text/html; charset=UTF-8');
    echo '<h1>Diagnóstico de bloques - Informe tutor empresa</h1><table border="1" cellpadding="4" cellspacing="0"><tr><th>Bloque</th><th>Páginas</th><th>Estado</th></tr>';
    foreach ($blockResults as $n => $r) echo '<tr><td>' . esc($n) . '</td><td class="center">' . (int)$r['pages'] . '</td><td>' . esc($r['state']) . '</td></tr>';
    echo '</table>';
    if (!empty($peligrosos)) echo '<h2>Patrones peligrosos detectados en datos dinámicos</h2><pre>' . esc((string)json_encode($peligrosos, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)) . '</pre>';
    exit;
  }

  if ($debugHtml) {
    if (ob_get_length()) ob_end_clean();
    header('Content-Type: text/html; charset=UTF-8');
    echo $html;
    exit;
  }

  $fase = 'escribiendo HTML en mPDF';
  $mpdf = newMpdfInstance();
  $mpdf->WriteHTML($html);
  if ($mpdf->page > 3) {
    $obsLen = $dbMeta['{{OBSERVACIONES_TUTOR_EMPRESA}}']['length'] ?? 0;
    throw new RuntimeException('El informe se ha generado con ' . $mpdf->page . ' páginas. Bloque sospechoso: ' . $susBlock . '. Modo recomendado: debug_blocks=1. Campo origen: ' . $susField . '. Longitud observaciones: ' . $obsLen . ' caracteres.');
  }

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
