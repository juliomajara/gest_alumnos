<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/vendor/autoload.php';

use Mpdf\Mpdf;
use Mpdf\Output\Destination;

const DEFAULT_CENTRO_DOCENTE = 'IES Laguna de Joatzel';
const DEFAULT_CENTRO_EMAIL = 'ies.lagunadejoatzel.getafe@educa.madrid.org';
const DEFAULT_CENTRO_TELEFONO = '916837197';

function fail(string $message, int $status = 400): never {
  http_response_code($status);
  header('Content-Type: text/plain; charset=UTF-8');
  echo $message;
  exit;
}

function full_name(array $row, string $prefix): string {
  $parts = array_filter([
    trim((string) ($row[$prefix . '_apellido1'] ?? '')),
    trim((string) ($row[$prefix . '_apellido2'] ?? '')),
  ], static fn (string $value): bool => $value !== '');

  $name = trim((string) ($row[$prefix . '_nombre'] ?? ''));
  if ($parts === [] && $name === '') {
    return '';
  }

  return trim(implode(' ', $parts) . ', ' . $name, ' ,');
}

function format_date(?string $value): string {
  if ($value === null || trim($value) === '') {
    return '';
  }

  $dt = DateTime::createFromFormat('Y-m-d', $value);
  if ($dt instanceof DateTime) {
    return $dt->format('d/m/Y');
  }

  return $value;
}

function format_time(?string $value): string {
  if ($value === null || trim($value) === '') {
    return '';
  }

  $dt = DateTime::createFromFormat('H:i:s', $value) ?: DateTime::createFromFormat('H:i', $value);
  return $dt instanceof DateTime ? $dt->format('H:i') : $value;
}

function normalize_upper(string $value): string {
  return function_exists('mb_strtoupper') ? mb_strtoupper($value, 'UTF-8') : strtoupper($value);
}

function set_text_by_id(DOMXPath $xpath, string $id, string $value): void {
  $nodes = $xpath->query('//*[@id="' . $id . '"]');
  if (!($nodes instanceof DOMNodeList) || $nodes->length === 0) {
    return;
  }

  $node = $nodes->item(0);
  if ($node instanceof DOMElement) {
    $node->textContent = $value;
  }
}

function set_checkbox_group_option(DOMXPath $xpath, string $groupLabel, string $optionToMark): void {
  $groups = $xpath->query('//table[contains(concat(" ", normalize-space(@class), " "), " checkbox-group ")]');
  if (!($groups instanceof DOMNodeList)) {
    return;
  }

  foreach ($groups as $group) {
    if (!($group instanceof DOMElement)) {
      continue;
    }

    $groupText = trim((string) $group->textContent);
    if (!str_contains(normalize_upper($groupText), normalize_upper($groupLabel))) {
      continue;
    }

    $items = $xpath->query('.//td[contains(concat(" ", normalize-space(@class), " "), " checkbox-item ")]', $group);
    if (!($items instanceof DOMNodeList)) {
      continue;
    }

    foreach ($items as $item) {
      if (!($item instanceof DOMElement)) {
        continue;
      }

      $labelNode = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " checkbox-option-label ")]', $item)->item(0);
      $boxNode = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " checkbox-option-box ") or contains(concat(" ", normalize-space(@class), " "), " checkbox ")]', $item)->item(0);
      if (!($labelNode instanceof DOMElement) || !($boxNode instanceof DOMElement)) {
        continue;
      }

      $isChecked = normalize_upper(trim($labelNode->textContent)) === normalize_upper($optionToMark);
      $boxNode->textContent = $isChecked ? 'X' : '';
    }

    return;
  }
}

function build_schedule_summary(array $scheduleByDay): string {
  $labels = [1 => 'L', 2 => 'M', 3 => 'X', 4 => 'J', 5 => 'V', 6 => 'S', 7 => 'D'];
  $parts = [];

  foreach ($labels as $day => $label) {
    $ranges = [];
    foreach (($scheduleByDay[$day] ?? []) as $segment) {
      $start = format_time($segment['hora_entrada'] ?? null);
      $end = format_time($segment['hora_salida'] ?? null);
      if ($start !== '' && $end !== '') {
        $ranges[] = $start . '-' . $end;
      }
    }

    $parts[] = $ranges === [] ? $label . ': —' : $label . ': ' . implode(' / ', $ranges);
  }

  return implode(' / ', $parts);
}

function build_module_rows(PDO $pdo, int $courseId, int $cycleId): array {
  if ($courseId <= 0 || $cycleId <= 0) {
    return [];
  }

  $stmt = $pdo->prepare(
    'SELECT
      pr.id_practica_ra,
      m.codigo,
      m.materia_general,
      m.materia_propia,
      ra.numero AS ra_numero
     FROM practicas_ras pr
     LEFT JOIN resultados_aprendizaje ra ON ra.id_ra = pr.id_ra
     LEFT JOIN modulos m ON m.id_modulo = COALESCE(pr.id_modulo, ra.id_modulo)
     WHERE pr.id_curso_escolar = :id_curso_escolar
       AND pr.id_ciclo = :id_ciclo
     ORDER BY m.codigo ASC, CAST(ra.numero AS UNSIGNED) ASC, ra.numero ASC, pr.id_practica_ra ASC'
  );
  $stmt->execute([
    'id_curso_escolar' => $courseId,
    'id_ciclo' => $cycleId,
  ]);

  $rows = [];
  foreach ($stmt->fetchAll() as $row) {
    $module = trim((string) ($row['materia_general'] ?? ''));
    if ($module === '') {
      $module = trim((string) ($row['materia_propia'] ?? ''));
    }

    $ra = trim((string) ($row['ra_numero'] ?? ''));
    $rows[] = [
      'modulo' => $module,
      'codigo' => trim((string) ($row['codigo'] ?? '')),
      'resultado' => $ra !== '' ? 'RA ' . $ra : '',
    ];
  }

  return $rows;
}

$idRaw = $_GET['id_practica'] ?? null;
$idPractica = filter_var($idRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($idPractica === false || $idPractica === null) {
  fail('id_practica no válido.', 400);
}

$pdo = db();
$stmt = $pdo->prepare(
  'SELECT
    p.*, 
    a.id_alumno,
    a.nia AS alumno_nia,
    a.dni AS alumno_dni,
    a.nombre AS alumno_nombre,
    a.apellido1 AS alumno_apellido1,
    a.apellido2 AS alumno_apellido2,
    COALESCE((SELECT c1.direccion_correo FROM correos c1 WHERE c1.entidad_tipo = "alumno" AND c1.id_entidad = a.id_alumno ORDER BY c1.id_correo ASC LIMIT 1), "") AS alumno_email,
    COALESCE((SELECT t1.telefono FROM telefonos t1 WHERE t1.entidad_tipo = "alumno" AND t1.id_entidad = a.id_alumno ORDER BY t1.id_telefono ASC LIMIT 1), "") AS alumno_telefono,
    e.id_empresa,
    e.nombre AS empresa_nombre,
    e.cif AS empresa_cif,
    COALESCE((SELECT c2.direccion_correo FROM correos c2 WHERE c2.entidad_tipo = "empresa" AND c2.id_entidad = e.id_empresa ORDER BY c2.id_correo ASC LIMIT 1), "") AS empresa_email,
    COALESCE((SELECT t2.telefono FROM telefonos t2 WHERE t2.entidad_tipo = "empresa" AND t2.id_entidad = e.id_empresa ORDER BY t2.id_telefono ASC LIMIT 1), "") AS empresa_telefono,
    et.id_empresas_tutor,
    et.nombre AS tutor_empresa_nombre,
    et.apellido1 AS tutor_empresa_apellido1,
    et.apellido2 AS tutor_empresa_apellido2,
    COALESCE((SELECT c3.direccion_correo FROM correos c3 WHERE c3.entidad_tipo = "empresas_tutor" AND c3.id_entidad = et.id_empresas_tutor ORDER BY c3.id_correo ASC LIMIT 1), "") AS tutor_empresa_email,
    COALESCE((SELECT t3.telefono FROM telefonos t3 WHERE t3.entidad_tipo = "empresas_tutor" AND t3.id_entidad = et.id_empresas_tutor ORDER BY t3.id_telefono ASC LIMIT 1), "") AS tutor_empresa_telefono,
    ac.id_curso_escolar,
    ac.id_curso,
    ac.id_grupo,
    ce.curso_escolar,
    c.curso AS curso_ordinal,
    g.id_ciclo,
    ci.ciclo AS ciclo_nombre,
    ci.codigo AS codigo_ciclo,
    prf.id_profesor AS tutor_centro_id,
    prf.nombre AS tutor_centro_nombre,
    prf.apellido1 AS tutor_centro_apellido1,
    prf.apellido2 AS tutor_centro_apellido2,
    COALESCE((SELECT c4.direccion_correo FROM correos c4 WHERE c4.entidad_tipo = "profesor" AND c4.id_entidad = prf.id_profesor ORDER BY c4.id_correo ASC LIMIT 1), "") AS tutor_centro_email,
    COALESCE((SELECT t4.telefono FROM telefonos t4 WHERE t4.entidad_tipo = "profesor" AND t4.id_entidad = prf.id_profesor ORDER BY t4.id_telefono ASC LIMIT 1), "") AS tutor_centro_telefono
  FROM practicas p
  INNER JOIN alumnos a ON a.id_alumno = p.id_alumno
  INNER JOIN empresas e ON e.id_empresa = p.id_empresa
  LEFT JOIN empresas_tutores et ON et.id_empresas_tutor = p.id_empresa_tutor
  LEFT JOIN alumno_curso ac
    ON ac.id_alumno = p.id_alumno
   AND ac.id_curso_escolar = (
      SELECT MAX(ac2.id_curso_escolar)
      FROM alumno_curso ac2
      WHERE ac2.id_alumno = p.id_alumno
   )
  LEFT JOIN cursos_escolares ce ON ce.id_curso_escolar = ac.id_curso_escolar
  LEFT JOIN cursos c ON c.id_curso = ac.id_curso
  LEFT JOIN grupos g ON g.id_grupo = ac.id_grupo
  LEFT JOIN ciclos ci ON ci.id_ciclo = g.id_ciclo
  LEFT JOIN grupos_tutores gt ON gt.id_grupo = ac.id_grupo AND gt.id_curso_escolar = ac.id_curso_escolar
  LEFT JOIN profesores prf ON prf.id_profesor = gt.id_profesor
  WHERE p.id_practica = :id_practica
  LIMIT 1'
);
$stmt->execute(['id_practica' => $idPractica]);
$practice = $stmt->fetch();

if (!is_array($practice)) {
  fail('No existe la práctica indicada.', 404);
}

$scheduleStmt = $pdo->prepare(
  'SELECT dia_semana, hora_entrada, hora_salida
   FROM practicas_horario
   WHERE id_practica = :id_practica
   ORDER BY dia_semana ASC, hora_entrada ASC'
);
$scheduleStmt->execute(['id_practica' => $idPractica]);
$scheduleByDay = [];
foreach ($scheduleStmt->fetchAll() as $segment) {
  $day = (int) ($segment['dia_semana'] ?? 0);
  if ($day > 0) {
    $scheduleByDay[$day][] = $segment;
  }
}

$moduleRows = build_module_rows($pdo, (int) ($practice['id_curso_escolar'] ?? 0), (int) ($practice['id_ciclo'] ?? 0));

$templatePath = __DIR__ . '/docs/practicas_plan_formacion.html';
if (!is_file($templatePath)) {
  fail('No se encuentra la plantilla del Plan de formación.', 500);
}

$html = file_get_contents($templatePath);
if ($html === false) {
  fail('No se pudo leer la plantilla del Plan de formación.', 500);
}

$flagImagePath = realpath(__DIR__ . '/docs/bandera_CM.png');
if (is_string($flagImagePath) && $flagImagePath !== '') {
  $flagUri = 'file://' . str_replace(DIRECTORY_SEPARATOR, '/', $flagImagePath);
  if (DIRECTORY_SEPARATOR === '\\') {
    $flagUri = preg_replace('/^file:\/\//', 'file:///', $flagUri) ?? $flagUri;
  }
  $html = str_replace('src="bandera_CM.png"', 'src="' . htmlspecialchars($flagUri, ENT_QUOTES, 'UTF-8') . '"', $html);
}

$dom = new DOMDocument();
libxml_use_internal_errors(true);
$domHtml = function_exists('mb_convert_encoding') ? mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8') : $html;
$dom->loadHTML($domHtml);
libxml_clear_errors();
$xpath = new DOMXPath($dom);

$fechaFin = trim((string) ($practice['fecha_fin_real'] ?? ''));
if ($fechaFin === '') {
  $fechaFin = trim((string) ($practice['fecha_fin'] ?? ''));
}

$dataById = [
  'date' => date('d/m/Y'),
  'course' => trim((string) ($practice['curso_escolar'] ?? '')),
  'ciclo-formativo' => trim((string) ($practice['ciclo_nombre'] ?? '')),
  'codigo' => trim((string) ($practice['codigo_ciclo'] ?? '')),
  'curso' => trim((string) ($practice['curso_ordinal'] ?? '')),
  'alumno' => full_name($practice, 'alumno'),
  'correo_alumno' => trim((string) ($practice['alumno_email'] ?? '')),
  'telefono_alumno' => trim((string) ($practice['alumno_telefono'] ?? '')),
  'centro_docente' => DEFAULT_CENTRO_DOCENTE,
  'correo_centro' => DEFAULT_CENTRO_EMAIL,
  'telefono_centro' => DEFAULT_CENTRO_TELEFONO,
  'tutor_centro' => full_name($practice, 'tutor_centro'),
  'correo_tutor_centro' => trim((string) ($practice['tutor_centro_email'] ?? '')),
  'telefono_tutor_centro' => trim((string) ($practice['tutor_centro_telefono'] ?? '')),
  'empresa' => trim((string) ($practice['empresa_nombre'] ?? '')),
  'nif_empresa' => trim((string) ($practice['empresa_cif'] ?? '')),
  'correo_empresa' => trim((string) ($practice['empresa_email'] ?? '')),
  'telefono_empresa' => trim((string) ($practice['empresa_telefono'] ?? '')),
  'tutor_empresa' => full_name($practice, 'tutor_empresa'),
  'correo_tutor_empresa' => trim((string) ($practice['tutor_empresa_email'] ?? '')),
  'telefono_tutor_empresa' => trim((string) ($practice['tutor_empresa_telefono'] ?? '')),
  'periodo_1_numero' => '1',
  'periodo_1_calendario' => trim(implode(' - ', array_filter([format_date($practice['fecha_inicio'] ?? null), format_date($fechaFin)]))),
  'periodo_1_horario' => build_schedule_summary($scheduleByDay),
  'total_horas' => trim((string) ($practice['horas'] ?? '')),
  'observaciones' => trim((string) ($practice['observaciones'] ?? '')),
  'causas_autorizacion' => '',
  'medidas_adaptaciones' => '',
];

foreach ($dataById as $id => $value) {
  set_text_by_id($xpath, $id, $value);
}

set_checkbox_group_option($xpath, 'Requiere medidas/adaptaciones extraordinarias por discapacidad', 'NO');
set_checkbox_group_option($xpath, 'Requiere autorización extraordinaria', ((int) ($practice['circ_excep'] ?? 0) === 1) ? 'SÍ' : 'NO');
set_checkbox_group_option($xpath, 'Intervalo de formación', 'Diario');

$moduleBodyNodes = $xpath->query('//table[contains(concat(" ", normalize-space(@class), " "), " module-table ")]/tbody');
if ($moduleBodyNodes instanceof DOMNodeList && $moduleBodyNodes->length > 0) {
  $tbody = $moduleBodyNodes->item(0);
  if ($tbody instanceof DOMElement) {
    while ($tbody->firstChild !== null) {
      $tbody->removeChild($tbody->firstChild);
    }

    foreach ($moduleRows as $row) {
      $tr = $dom->createElement('tr');

      $moduleTd = $dom->createElement('td');
      $moduleTd->appendChild($dom->createTextNode((string) $row['modulo']));
      $tr->appendChild($moduleTd);

      $codeTd = $dom->createElement('td');
      $codeTd->setAttribute('style', 'text-align:center;');
      $codeTd->appendChild($dom->createTextNode((string) $row['codigo']));
      $tr->appendChild($codeTd);

      $raTd = $dom->createElement('td');
      $raTd->setAttribute('style', 'text-align:center;');
      $raTd->appendChild($dom->createTextNode((string) $row['resultado']));
      $tr->appendChild($raTd);

      $empTd = $dom->createElement('td');
      $empTd->setAttribute('style', 'text-align:center;');
      $empTd->appendChild($dom->createTextNode(''));
      $tr->appendChild($empTd);

      $compTd = $dom->createElement('td');
      $compTd->setAttribute('style', 'text-align:center;');
      $compTd->appendChild($dom->createTextNode('X'));
      $tr->appendChild($compTd);

      $tbody->appendChild($tr);
    }
  }
}

$styleHtml = '';
$styleNodes = $xpath->query('//head/style');
if ($styleNodes instanceof DOMNodeList) {
  foreach ($styleNodes as $styleNode) {
    $chunk = $dom->saveHTML($styleNode);
    if (is_string($chunk)) {
      $styleHtml .= $chunk;
    }
  }
}

$bodyHtml = '';
$bodyNodes = $xpath->query('//body');
if ($bodyNodes instanceof DOMNodeList && $bodyNodes->length > 0) {
  $body = $bodyNodes->item(0);
  if ($body instanceof DOMElement) {
    foreach ($body->childNodes as $child) {
      $chunk = $dom->saveHTML($child);
      if (is_string($chunk)) {
        $bodyHtml .= $chunk;
      }
    }
  }
}

$rendered = trim($styleHtml . $bodyHtml);
if ($rendered === '') {
  fail('No se pudo renderizar la plantilla del Plan de formación.', 500);
}

$tmpDir = __DIR__ . '/docs/.mpdf_tmp';
if (!is_dir($tmpDir) && !mkdir($tmpDir, 0755, true) && !is_dir($tmpDir)) {
  fail('No se pudo crear el directorio temporal de mPDF.', 500);
}

$mpdf = new Mpdf([
  'mode' => 'utf-8',
  'format' => 'A4',
  'margin_left' => 10,
  'margin_right' => 10,
  'margin_top' => 10,
  'margin_bottom' => 10,
  'shrink_tables_to_fit' => 0,
  'tempDir' => $tmpDir,
]);

$mpdf->setBasePath(__DIR__ . '/docs/');
$mpdf->showImageErrors = true;
$mpdf->WriteHTML('@page { size: A4; margin: 10mm; }', \Mpdf\HTMLParserMode::HEADER_CSS);
$mpdf->WriteHTML($rendered);

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="plan_formacion_' . (int) $idPractica . '.pdf"');
$mpdf->Output('plan_formacion_' . (int) $idPractica . '.pdf', Destination::INLINE);
exit;
