<?php
declare(strict_types=1);

function format_value($value, string $fallback = 'No disponible'): string {
  if ($value === null || $value === '') {
    return $fallback;
  }

  return (string) $value;
}

function v($x): string {
  if ($x === null) {
    return '';
  }

  return trim((string) $x) === '' ? '' : (string) $x;
}

function format_date(?string $value, string $fallback = 'No disponible'): string {
  if ($value === null || $value === '') {
    return $fallback;
  }

  $date = DateTime::createFromFormat('Y-m-d', $value);
  if ($date && $date->format('Y-m-d') === $value) {
    return $date->format('d/m/Y');
  }

  return $value;
}

function format_time(?string $value, string $fallback = '—'): string {
  if ($value === null || $value === '') {
    return $fallback;
  }

  $time = DateTime::createFromFormat('H:i:s', $value);
  if ($time && $time->format('H:i:s') === $value) {
    return $time->format('H:i');
  }

  return $value;
}

function format_academic_year_from_date(?string $date): string {
  if ($date === null || $date === '') {
    return '';
  }

  $parsed = DateTime::createFromFormat('Y-m-d', $date);
  if (!$parsed || $parsed->format('Y-m-d') !== $date) {
    return '';
  }

  $year = (int) $parsed->format('Y');
  $month = (int) $parsed->format('n');
  $startYear = $month >= 9 ? $year : $year - 1;

  return $startYear . ' - ' . ($startYear + 1);
}

function get_previous_school_day(?string $startDate, PDO $pdo): ?string {
  if ($startDate === null || trim($startDate) === '') {
    return null;
  }

  $start = DateTime::createFromFormat('Y-m-d', trim($startDate));
  if (!$start || $start->format('Y-m-d') !== trim($startDate)) {
    return null;
  }

  $current = clone $start;
  $current->modify('-1 day');

  $festivosStmt = null;
  try {
    $festivosStmt = $pdo->prepare('SELECT 1 FROM festivos WHERE fecha = :fecha LIMIT 1');
  } catch (Throwable $e) {
    $festivosStmt = null;
  }

  for ($attempt = 0; $attempt < 370; $attempt++) {
    $weekday = (int) $current->format('N');
    if ($weekday >= 6) {
      $current->modify('-1 day');
      continue;
    }

    $isHoliday = false;
    if ($festivosStmt !== null) {
      try {
        $festivosStmt->execute(['fecha' => $current->format('Y-m-d')]);
        $isHoliday = (bool) $festivosStmt->fetchColumn();
      } catch (Throwable $e) {
        $isHoliday = false;
      }
    }

    if (!$isHoliday) {
      return $current->format('Y-m-d');
    }

    $current->modify('-1 day');
  }

  return null;
}

function format_bool_value($value, string $fallback = 'No disponible'): string {
  if ($value === null || $value === '') {
    return $fallback;
  }

  return (int) $value === 1 ? 'Sí' : 'No';
}

function full_name(array $row, string $prefix): string {
  $parts = array_filter([
    trim((string) ($row[$prefix . '_apellido1'] ?? '')),
    trim((string) ($row[$prefix . '_apellido2'] ?? '')),
  ], static fn (string $value): bool => $value !== '');

  $name = trim((string) ($row[$prefix . '_nombre'] ?? ''));
  if ($parts === [] && $name === '') {
    return 'No disponible';
  }

  return trim(implode(' ', $parts) . ', ' . $name, ' ,');
}

function build_address(array $practice): string {
  $parts = array_filter([
    trim((string) ($practice['direccion_via_tipo'] ?? '')),
    trim((string) ($practice['direccion_nombre_via'] ?? '')),
    trim((string) ($practice['direccion_numero'] ?? '')),
    ($practice['direccion_bloque'] ?? '') !== '' ? 'Bloque ' . $practice['direccion_bloque'] : '',
    ($practice['direccion_escalera'] ?? '') !== '' ? 'Esc. ' . $practice['direccion_escalera'] : '',
    ($practice['direccion_planta'] ?? '') !== '' ? 'Planta ' . $practice['direccion_planta'] : '',
    ($practice['direccion_puerta'] ?? '') !== '' ? 'Puerta ' . $practice['direccion_puerta'] : '',
    trim((string) ($practice['direccion_otros'] ?? '')),
  ], static fn (string $value): bool => $value !== '');

  $cpLocalidad = trim(implode(' ', array_filter([
    trim((string) ($practice['direccion_cp'] ?? '')),
    trim((string) ($practice['direccion_localidad'] ?? '')),
  ], static fn (string $value): bool => $value !== '')));

  if ($cpLocalidad !== '') {
    $parts[] = $cpLocalidad;
  }

  $provincia = trim((string) ($practice['direccion_provincia'] ?? ''));
  if ($provincia !== '') {
    $parts[] = $provincia;
  }

  $pais = trim((string) ($practice['direccion_pais'] ?? ''));
  if ($pais !== '') {
    $parts[] = $pais;
  }

  return $parts ? implode(', ', $parts) : 'No disponible';
}

function sanitize_filename_component(?string $value, int $maxLength = 60): string {
  $value = trim((string) $value);
  if ($value === '') {
    return 'NA';
  }

  $value = preg_replace('/[\\\\\/:*?"<>|]+/u', '', $value) ?? '';
  $value = preg_replace('/\s+/u', '_', $value) ?? '';
  $value = preg_replace('/_+/u', '_', $value) ?? '';
  $value = trim($value, '._-');

  if ($value === '') {
    return 'NA';
  }

  if (function_exists('mb_substr')) {
    return mb_substr($value, 0, $maxLength);
  }

  return substr($value, 0, $maxLength);
}

function build_calendar_pdf_filename(array $practice): string {
  $companyNameForFile = (string) ($practice['nombre_comercial']
    ?? $practice['empresa_nombre_comercial']
    ?? $practice['empresa_nombre']
    ?? '');

  $parts = [
    sanitize_filename_component((string) ($practice['empresa_convenio'] ?? ''), 20),
    sanitize_filename_component((string) ($practice['anexo'] ?? ''), 20),
    sanitize_filename_component((string) ($practice['alumno_nombre'] ?? ''), 40),
    sanitize_filename_component((string) ($practice['alumno_apellido1'] ?? ''), 40),
    sanitize_filename_component($companyNameForFile, 70),
  ];

  $baseName = implode('_', $parts);
  if (function_exists('mb_substr')) {
    $baseName = mb_substr($baseName, 0, 200);
  } else {
    $baseName = substr($baseName, 0, 200);
  }

  return $baseName . '.pdf';
}

function build_plan_pdf_filename(array $practice): string {
  $sanitize = static function (?string $value, int $maxLength = 80): string {
    $value = trim((string) $value);
    if ($value === '') {
      return 'NA';
    }

    if (function_exists('iconv')) {
      $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
      if (is_string($converted) && $converted !== '') {
        $value = $converted;
      }
    }

    $value = preg_replace('/[\/\\:*?"<>|]+/', '', $value) ?? '';
    $value = preg_replace('/[^A-Za-z0-9]+/', '_', $value) ?? '';
    $value = preg_replace('/_+/', '_', $value) ?? '';
    $value = trim($value, '._-');

    if ($value === '') {
      return 'NA';
    }

    return function_exists('mb_substr') ? mb_substr($value, 0, $maxLength) : substr($value, 0, $maxLength);
  };

  $student = trim(implode(' ', array_filter([
    trim((string) ($practice['alumno_apellido1'] ?? '')),
    trim((string) ($practice['alumno_apellido2'] ?? '')),
  ], static fn (string $value): bool => $value !== '')));
  $studentName = trim((string) ($practice['alumno_nombre'] ?? ''));
  $student = trim($student) . ($student !== '' && $studentName !== '' ? '_' : '') . $studentName;
  $company = (string) ($practice['empresa_nombre'] ?? '');
  $dateForFile = date('Ymd');

  return sprintf(
    'PlanFormación_%s_%s_%s.pdf',
    $sanitize($student, 80),
    $sanitize($company, 80),
    $sanitize($dateForFile, 20)
  );
}

function set_checkbox_state(DOMElement $checkbox, bool $checked): void {
  $classes = preg_split('/\s+/', trim($checkbox->getAttribute('class'))) ?: [];
  $classes = array_values(array_filter($classes, static fn (string $class): bool => $class !== ''));

  if (!in_array('checkbox', $classes, true)) {
    array_unshift($classes, 'checkbox');
  }

  $checkbox->setAttribute('class', implode(' ', array_unique($classes)));
  $checkbox->textContent = $checked ? 'X' : "\u{00A0}";
}

function xpath_literal(string $value): string {
  if (!str_contains($value, "'")) {
    return "'" . $value . "'";
  }

  if (!str_contains($value, '"')) {
    return '"' . $value . '"';
  }

  $parts = explode("'", $value);
  $result = [];
  foreach ($parts as $index => $part) {
    if ($part !== '') {
      $result[] = "'" . $part . "'";
    }
    if ($index < count($parts) - 1) {
      $result[] = '"\'"';
    }
  }

  return 'concat(' . implode(', ', $result) . ')';
}

function normalize_upper(string $value): string {
  return function_exists('mb_strtoupper') ? mb_strtoupper($value, 'UTF-8') : strtoupper($value);
}

function mark_checkbox_group_option(DOMXPath $xpath, string $groupLabel, string $optionToMark): void {
  $groups = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " checkbox-group ")][.//span[contains(normalize-space(.), ' . xpath_literal($groupLabel) . ')]]');
  if (!($groups instanceof DOMNodeList) || $groups->length === 0) {
    return;
  }

  $group = $groups->item(0);
  if (!($group instanceof DOMElement)) {
    return;
  }

  $items = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " checkbox-item ")]', $group);
  if (!($items instanceof DOMNodeList)) {
    return;
  }

  foreach ($items as $item) {
    if (!($item instanceof DOMElement)) {
      continue;
    }

    $labelNodes = $xpath->query('./span[1]', $item);
    $checkboxNodes = $xpath->query('./span[contains(concat(" ", normalize-space(@class), " "), " checkbox ")]', $item);
    if (!($labelNodes instanceof DOMNodeList) || $labelNodes->length === 0 || !($checkboxNodes instanceof DOMNodeList) || $checkboxNodes->length === 0) {
      continue;
    }

    $label = trim((string) $labelNodes->item(0)?->textContent);
    $checkbox = $checkboxNodes->item($checkboxNodes->length - 1);
    if ($checkbox instanceof DOMElement) {
      set_checkbox_state($checkbox, normalize_upper($label) === normalize_upper($optionToMark));
    }
  }
}

function set_multiline_content_by_id(DOMXPath $xpath, string $id, array $lines): void {
  $nodes = $xpath->query('//*[@id="' . $id . '"]');
  if (!($nodes instanceof DOMNodeList) || $nodes->length === 0) {
    return;
  }

  $node = $nodes->item(0);
  if (!($node instanceof DOMElement)) {
    return;
  }

  while ($node->firstChild !== null) {
    $node->removeChild($node->firstChild);
  }

  $owner = $node->ownerDocument;
  if (!($owner instanceof DOMDocument)) {
    return;
  }

  foreach (array_values($lines) as $index => $line) {
    if ($index > 0) {
      $node->appendChild($owner->createElement('br'));
    }
    $node->appendChild($owner->createTextNode($line));
  }
}

function set_span_by_text(DOMXPath $xpath, string $currentText, string $value): void {
  $nodes = $xpath->query('//span[normalize-space(text()) = ' . xpath_literal($currentText) . ']');
  if (!($nodes instanceof DOMNodeList) || $nodes->length === 0) {
    return;
  }

  $node = $nodes->item(0);
  if ($node instanceof DOMElement) {
    $node->textContent = $value;
  }
}

function set_span_after_strong_label(DOMXPath $xpath, string $labelText, string $value): void {
  $nodes = $xpath->query('//td[.//strong[contains(normalize-space(.), ' . xpath_literal($labelText) . ')]]//span[1]');
  if (!($nodes instanceof DOMNodeList) || $nodes->length === 0) {
    return;
  }

  $node = $nodes->item(0);
  if ($node instanceof DOMElement) {
    $node->textContent = $value;
  }
}

function remove_table_row_by_span_placeholder(DOMXPath $xpath, string $placeholder): void {
  $nodes = $xpath->query('//tr[.//span[normalize-space(text()) = ' . xpath_literal($placeholder) . ']]');
  if (!($nodes instanceof DOMNodeList) || $nodes->length === 0) {
    return;
  }

  $row = $nodes->item(0);
  if ($row === null || $row->parentNode === null) {
    return;
  }

  $row->parentNode->removeChild($row);
}

function set_observaciones_text(DOMXPath $xpath, string $value): void {
  $nodes = $xpath->query('//table[.//strong[contains(normalize-space(.), "Observaciones:")]]/tr[2]/td');
  if (!($nodes instanceof DOMNodeList) || $nodes->length === 0) {
    return;
  }

  $node = $nodes->item(0);
  if (!($node instanceof DOMElement)) {
    return;
  }

  while ($node->firstChild !== null) {
    $node->removeChild($node->firstChild);
  }

  $node->textContent = $value;
}


function center_plan_marks(DOMXPath $xpath, int $rows = 17): void {
  for ($index = 1; $index <= $rows; $index++) {
    foreach (['integro', 'compartido'] as $prefix) {
      $nodes = $xpath->query('//span[normalize-space(text()) = ' . xpath_literal($prefix . $index) . ']');
      if (!($nodes instanceof DOMNodeList) || $nodes->length === 0) {
        continue;
      }

      $span = $nodes->item(0);
      if (!($span instanceof DOMElement)) {
        continue;
      }

      $cell = $span->parentNode;
      if (!($cell instanceof DOMElement)) {
        continue;
      }

      $style = trim((string) $cell->getAttribute('style'));
      if ($style !== '' && !str_ends_with($style, ';')) {
        $style .= ';';
      }

      if (!str_contains($style, 'text-align: center')) {
        $style .= ' text-align: center;';
      }

      $cell->setAttribute('style', trim($style));
    }
  }
}
function set_checkbox_in_table_by_title(DOMXPath $xpath, string $title, int $index, bool $checked): void {
  $nodes = $xpath->query('//table[.//strong[contains(normalize-space(.), ' . xpath_literal($title) . ')]]//span[contains(concat(" ", normalize-space(@class), " "), " checkbox ")]');
  if (!($nodes instanceof DOMNodeList) || $nodes->length <= $index) {
    return;
  }

  $checkbox = $nodes->item($index);
  if (!($checkbox instanceof DOMElement)) {
    return;
  }

  set_checkbox_state($checkbox, $checked);

  $classes = preg_split('/\s+/', trim($checkbox->getAttribute('class'))) ?: [];
  $classes = array_values(array_filter($classes, static fn (string $class): bool => $class !== ''));
  $hasChecked = in_array('checked', $classes, true);

  if ($checked && !$hasChecked) {
    $classes[] = 'checked';
  }

  if (!$checked && $hasChecked) {
    $classes = array_values(array_filter($classes, static fn (string $class): bool => $class !== 'checked'));
  }

  $checkbox->setAttribute('class', implode(' ', array_unique($classes)));
}

function calculate_schedule_metrics(array $scheduleByDay): array {
  $hasWeekend = false;
  $maxDayHours = 0.0;
  $weeklyHours = 0.0;

  foreach ($scheduleByDay as $day => $segments) {
    $dayHours = 0.0;
    foreach ($segments as $segment) {
      $entrada = time_to_seconds((string) ($segment['hora_entrada'] ?? ''));
      $salida = time_to_seconds((string) ($segment['hora_salida'] ?? ''));
      if ($entrada !== null && $salida !== null && $salida > $entrada) {
        $dayHours += ($salida - $entrada) / 3600;
      }
    }

    if ((int) $day === 6 || (int) $day === 7 || (int) $day === 0) {
      if ($dayHours > 0) {
        $hasWeekend = true;
      }
    }

    $maxDayHours = max($maxDayHours, $dayHours);
    $weeklyHours += $dayHours;
  }

  return [
    'has_weekend' => $hasWeekend,
    'max_day_hours' => $maxDayHours,
    'weekly_hours' => $weeklyHours,
  ];
}

function build_extraordinary_authorization_causes(array $practice, array $scheduleByDay): array {
  $causes = [];
  $provinceId = isset($practice['direccion_id_provincia']) ? (int) $practice['direccion_id_provincia'] : 0;
  if ($provinceId > 0 && $provinceId !== 28) {
    $causes[] = 'El centro de trabajo está ubicado en otra comunidad autónoma.';
  }

  $metrics = calculate_schedule_metrics($scheduleByDay);
  if ($metrics['has_weekend']) {
    $causes[] = 'Parte de las prácticas se desarrollan durante días no lectivos.';
  }

  if ($metrics['max_day_hours'] > 8.0 || $metrics['weekly_hours'] > 40.0) {
    $causes[] = 'El horario es diferente de los definidos con carácter general.';
  }

  return $causes;
}

function redirect_to_practice(int $practiceId, ?string $documentStatus = null, ?string $documentError = null): void {
  $params = ['id_practica=' . $practiceId];
  if ($documentStatus !== null) {
    $params[] = 'doc_status=' . rawurlencode($documentStatus);
  }
  if ($documentError !== null) {
    $params[] = 'doc_error=' . rawurlencode($documentError);
  }

  header('Location: practica_detalle.php?' . implode('&', $params));
  exit;
}



function blank_if_unavailable(string $value): string {
  return $value === 'No disponible' ? '' : $value;
}

function build_plan_formacion_html(array $practice, array $scheduleByDay, array $planRows, PDO $pdo): string {
  $templatePath = __DIR__ . '/../docs/practicas_plan_formacion.html';
  if (!is_file($templatePath)) {
    $templatePath = __DIR__ . '/docs/practicas_plan_formacion.html';
  }
  if (!is_file($templatePath)) {
    throw new RuntimeException('No se encuentra la plantilla docs/practicas_plan_formacion.html.');
  }

  $html = file_get_contents($templatePath);
  if ($html === false) {
    throw new RuntimeException('No se pudo leer la plantilla del plan de formación.');
  }

  $dom = new DOMDocument();
  libxml_use_internal_errors(true);
  $htmlForDom = function_exists('mb_convert_encoding')
    ? mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8')
    : $html;
  $dom->loadHTML($htmlForDom);
  libxml_clear_errors();

  $xpath = new DOMXPath($dom);
  $studentName = blank_if_unavailable(full_name($practice, 'alumno'));
  $companyName = v($practice['empresa_nombre'] ?? null);
  $companyCif = v($practice['empresa_cif'] ?? null);
  $companyTutor = blank_if_unavailable(full_name($practice, 'tutor'));
  $courseName = format_academic_year_from_date(date('Y-m-d'));
  if ($courseName === '') {
    $courseName = v($practice['curso_escolar'] ?? null);
  }

  $previousSchoolDay = get_previous_school_day((string) ($practice['fecha_inicio'] ?? ''), $pdo);
  $creationDate = $previousSchoolDay !== null ? format_date($previousSchoolDay, '') : '';

  if ($creationDate === '') {
    $creationDate = date('d/m/Y');
  }
  $cycleName = v($practice['ciclo_nombre'] ?? null);

  $endDateRaw = (string) ($practice['fecha_fin_real'] ?? '');
  if ($endDateRaw === '') {
    $endDateRaw = (string) ($practice['fecha_fin'] ?? '');
  }
  $scheduleSummary = implode(' / ', build_schedule_summary($scheduleByDay));

  $hoursValue = v($practice['horas'] ?? null);
  $totalHours = $hoursValue === '' ? '' : $hoursValue . ' horas';

  $circExcep = (int) ($practice['circ_excep'] ?? 0);
  $causes = $circExcep === 1 ? build_extraordinary_authorization_causes($practice, $scheduleByDay) : [];

  $valuesByPlaceholder = [
    'fecha de creación' => $creationDate,
    '02/03/2026' => $creationDate,
    'curso escolar' => $courseName,
    '2025 - 2026' => $courseName,
    'Nombre del ciclo formativo' => $cycleName,
    'Técnico Superior en Desarrollo de Aplicaciones Multiplataforma' => $cycleName,
    'código del ciclo' => v($practice['ciclo_codigo'] ?? null),
    'IFCS03' => v($practice['ciclo_codigo'] ?? null),
    'curso del alumno' => v($practice['curso_ordinal'] ?? null),
    '2º' => v($practice['curso_ordinal'] ?? null),
    'Nombre del Alumno' => $studentName,
    'correo del alumno' => v($practice['alumno_email'] ?? null),
    'Teléfono del alumno' => v($practice['alumno_telefono'] ?? null),
    'Nombre del tutor' => v($practice['tutor_centro'] ?? null),
    'correo del tutor' => v($practice['tutor_centro_email'] ?? null),
    'teléfono del tutor' => v($practice['tutor_centro_telefono'] ?? null),
    'Nombre de la empresa' => $companyName,
    'NIF de la empresa' => $companyCif,
    'correo de la empresa' => v($practice['empresa_email'] ?? null),
    'Teléfono de la empresa' => v($practice['empresa_telefono'] ?? null),
    'Nombre del tutor de empresa' => $companyTutor,
    'correo del tutor de empresa' => v($practice['tutor_empresa_email'] ?? null),
    'teléfono del tutor de empresa' => v($practice['tutor_empresa_telefono'] ?? null),
    'periodo' => '1',
    'fecha incio - fecha fin' => trim(implode(' - ', array_values(array_filter([
      format_date((string) ($practice['fecha_inicio'] ?? ''), ''),
      format_date($endDateRaw, ''),
    ], static fn (string $item): bool => $item !== '')))),
    'horario' => $scheduleSummary,
    'horas' => $totalHours,
  ];

  foreach ($valuesByPlaceholder as $placeholder => $value) {
    set_span_by_text($xpath, $placeholder, $value);
  }

  set_span_after_strong_label($xpath, 'Ciclo Formativo/Curso de especialización/ Programa de especialización:', $cycleName);
  set_span_after_strong_label($xpath, 'Código:', v($practice['ciclo_codigo'] ?? null));
  set_span_after_strong_label($xpath, 'Curso:', v($practice['curso_ordinal'] ?? null));

  $causasText = implode(' ', $causes);
  if ($causasText !== '') {
    $causeNodes = $xpath->query('//table[.//strong[contains(normalize-space(.), "Requiere autorización extraordinaria:")]]/tr[3]/td');
    if ($causeNodes instanceof DOMNodeList && $causeNodes->length > 0) {
      $causeNode = $causeNodes->item(0);
      if ($causeNode instanceof DOMElement) {
        $causeNode->textContent = $causasText;
      }
    }
  }

  set_observaciones_text($xpath, v($practice['observaciones'] ?? null));

  set_checkbox_in_table_by_title($xpath, 'Requiere medidas/adaptaciones extraordinarias por discapacidad:', 0, false);
  set_checkbox_in_table_by_title($xpath, 'Requiere medidas/adaptaciones extraordinarias por discapacidad:', 1, true);
  set_checkbox_in_table_by_title($xpath, 'Requiere autorización extraordinaria:', 0, $circExcep === 1);
  set_checkbox_in_table_by_title($xpath, 'Requiere autorización extraordinaria:', 1, $circExcep !== 1);
  set_checkbox_in_table_by_title($xpath, 'Intervalo de formación:', 0, true);
  set_checkbox_in_table_by_title($xpath, 'Intervalo de formación:', 1, false);
  set_checkbox_in_table_by_title($xpath, 'Intervalo de formación:', 2, false);
  set_checkbox_in_table_by_title($xpath, 'Intervalo de formación:', 3, false);
  set_checkbox_in_table_by_title($xpath, 'Intervalo de formación:', 4, false);

  center_plan_marks($xpath);

  for ($index = 1; $index <= 17; $index++) {
    $row = $planRows[$index - 1] ?? [];
    $modulo = v($row['modulo'] ?? null);
    $codigo = v($row['codigo'] ?? null);
    $resultado = v($row['resultado'] ?? null);
    $integro = v($row['empresa_x'] ?? null);
    $compartido = v($row['compartida_x'] ?? null);

    if ($modulo === '' && $codigo === '' && $resultado === '' && $integro === '' && $compartido === '') {
      remove_table_row_by_span_placeholder($xpath, 'modulo' . $index);
      continue;
    }

    set_span_by_text($xpath, 'modulo' . $index, $modulo);
    set_span_by_text($xpath, 'codigo' . $index, $codigo);
    set_span_by_text($xpath, 'RA' . $index, $resultado);
    set_span_by_text($xpath, 'integro' . $index, $integro);
    set_span_by_text($xpath, 'compartido' . $index, $compartido);
  }

  $rendered = $dom->saveHTML();
  if (trim($rendered) === '') {
    throw new RuntimeException('El contenido renderizado del plan de formación está vacío.');
  }

  return $rendered;
}

function fetch_plan_formacion_rows(PDO $pdo, array $practice): array {
  $courseId = isset($practice['id_curso_escolar']) ? (int) $practice['id_curso_escolar'] : 0;
  $cycleId = isset($practice['id_ciclo']) ? (int) $practice['id_ciclo'] : 0;

  if ($courseId <= 0 || $cycleId <= 0) {
    return [];
  }

  $stmt = $pdo->prepare(
    'SELECT
      pr.id_practica_ra,
      pr.porcentaje,
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
    $moduleName = v($row['materia_general'] ?? null);
    if ($moduleName === '') {
      $moduleName = v($row['materia_propia'] ?? null);
    }

    $raNumero = v($row['ra_numero'] ?? null);
    $raLabel = $raNumero !== '' ? 'RA ' . $raNumero : '';

    $rows[] = [
      'modulo' => $moduleName,
      'codigo' => v($row['codigo'] ?? null),
      'resultado' => $raLabel,
      'empresa_x' => '',
      'compartida_x' => 'X',
    ];
  }

  return $rows;
}

function ensure_mpdf_temp_dir(): string {
  $tmpDir = sys_get_temp_dir() . '/mpdf_tmp';
  if (!is_dir($tmpDir) && !mkdir($tmpDir, 0755, true) && !is_dir($tmpDir)) {
    throw new RuntimeException('No se pudo crear el directorio temporal de mPDF.');
  }
  $testFile = $tmpDir . '/_write_test.tmp';
  if (@file_put_contents($testFile, '1') === false) {
    throw new RuntimeException('El directorio temporal para PDFs no tiene permisos de escritura: ' . $tmpDir);
  }
  @unlink($testFile);
  return $tmpDir;
}

function month_name_es(int $month): string {
  $names = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
    7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
  ];
  return $names[$month] ?? '';
}

function build_schedule_summary(array $scheduleByDay): array {
  $labels = [1 => 'L', 2 => 'M', 3 => 'X', 4 => 'J', 5 => 'V', 6 => 'S', 7 => 'D'];
  $summary = [];

  foreach ($labels as $dayNumber => $label) {
    $segments = $scheduleByDay[$dayNumber] ?? [];
    $ranges = [];

    foreach ($segments as $segment) {
      $entrada = format_time($segment['hora_entrada'] ?? null, '');
      $salida = format_time($segment['hora_salida'] ?? null, '');
      if ($entrada !== '' && $salida !== '') {
        $ranges[] = $entrada . '-' . $salida;
      }
    }

    $summary[$dayNumber] = $ranges === [] ? $label . ': —' : $label . ': ' . implode(' / ', $ranges);
  }

  return $summary;
}

function time_to_seconds(?string $value): ?int {
  if ($value === null || $value === '') {
    return null;
  }

  $parts = explode(':', $value);
  if (count($parts) < 2) {
    return null;
  }

  $hours = (int) $parts[0];
  $minutes = (int) $parts[1];
  $seconds = isset($parts[2]) ? (int) $parts[2] : 0;

  return ($hours * 3600) + ($minutes * 60) + $seconds;
}

function build_weekly_schedule_pdf_table(array $scheduleByDay, array $diasSemana): string {
  $html = '<table width="100%" border="1" cellpadding="4" cellspacing="0" style="border-collapse: collapse; font-size: 10pt; margin-bottom: 14px;">';
  $html .= '<thead>';
  $html .= '<tr>';
  $html .= '<th rowspan="2" style="background-color:#d9d9d9; text-align:left;">Día</th>';
  $html .= '<th colspan="2" style="background-color:#d9d9d9; text-align:center;">Horario Mañana</th>';
  $html .= '<th colspan="2" style="background-color:#d9d9d9; text-align:center;">Horario tarde</th>';
  $html .= '<th rowspan="2" style="background-color:#d9d9d9; text-align:center;">Total</th>';
  $html .= '</tr>';
  $html .= '<tr>';
  $html .= '<th style="background-color:#d9d9d9; text-align:center;">Inicio</th>';
  $html .= '<th style="background-color:#d9d9d9; text-align:center;">Fin</th>';
  $html .= '<th style="background-color:#d9d9d9; text-align:center;">Inicio</th>';
  $html .= '<th style="background-color:#d9d9d9; text-align:center;">Fin</th>';
  $html .= '</tr>';
  $html .= '</thead><tbody>';

  foreach ($diasSemana as $dayNumber => $dayName) {
    $segments = $scheduleByDay[$dayNumber] ?? [];
    $morning = $segments[0] ?? null;
    $afternoon = $segments[1] ?? null;

    $morningStart = $morning ? format_time($morning['hora_entrada'] ?? null, '--:--') : '--:--';
    $morningEnd = $morning ? format_time($morning['hora_salida'] ?? null, '--:--') : '--:--';
    $afternoonStart = $afternoon ? format_time($afternoon['hora_entrada'] ?? null, '--:--') : '--:--';
    $afternoonEnd = $afternoon ? format_time($afternoon['hora_salida'] ?? null, '--:--') : '--:--';

    $totalSeconds = 0;
    foreach ($segments as $segment) {
      $entrada = time_to_seconds((string) ($segment['hora_entrada'] ?? ''));
      $salida = time_to_seconds((string) ($segment['hora_salida'] ?? ''));
      if ($entrada !== null && $salida !== null && $salida > $entrada) {
        $totalSeconds += ($salida - $entrada);
      }
    }

    $totalLabel = '--';
    if ($totalSeconds > 0) {
      $hours = intdiv($totalSeconds, 3600);
      $minutes = intdiv($totalSeconds % 3600, 60);
      $totalLabel = $minutes > 0 ? sprintf('%d:%02d horas', $hours, $minutes) : $hours . ' horas';
    }

    $html .= '<tr>';
    $html .= '<td style="text-align:left;">' . htmlspecialchars($dayName, ENT_QUOTES, 'UTF-8') . '</td>';
    $html .= '<td style="text-align:center;">' . htmlspecialchars($morningStart, ENT_QUOTES, 'UTF-8') . '</td>';
    $html .= '<td style="text-align:center;">' . htmlspecialchars($morningEnd, ENT_QUOTES, 'UTF-8') . '</td>';
    $html .= '<td style="text-align:center;">' . htmlspecialchars($afternoonStart, ENT_QUOTES, 'UTF-8') . '</td>';
    $html .= '<td style="text-align:center;">' . htmlspecialchars($afternoonEnd, ENT_QUOTES, 'UTF-8') . '</td>';
    $html .= '<td style="text-align:center;">' . htmlspecialchars($totalLabel, ENT_QUOTES, 'UTF-8') . '</td>';
    $html .= '</tr>';
  }

  $html .= '</tbody></table>';

  return $html;
}

function is_no_attendance_day(DateTimeImmutable $date, array $nonSchoolDays, array $scheduleByDay): bool {
  $currentIso = $date->format('Y-m-d');
  $dayOfWeek = (int) $date->format('N');
  $isWeekend = $dayOfWeek >= 6;
  $hasScheduleForDay = !empty($scheduleByDay[$dayOfWeek]);

  return isset($nonSchoolDays[$currentIso]) || ($isWeekend && !$hasScheduleForDay);
}

function build_calendar_html(DateTimeImmutable $startDate, DateTimeImmutable $endDate, array $nonSchoolDays, array $scheduleByDay): string {
  $startKey = $startDate->format('Y-m-d');
  $endKey = $endDate->format('Y-m-d');
  $monthHtmlChunks = [];
  $firstMonth = new DateTimeImmutable($startDate->format('Y-m-01'));
  $lastMonth = new DateTimeImmutable($endDate->format('Y-m-01'));

  for ($month = $firstMonth; $month <= $lastMonth; $month = $month->modify('+1 month')) {
    $monthNum = (int) $month->format('n');
    $yearNum = (int) $month->format('Y');
    $daysInMonth = (int) $month->format('t');
    $startOffset = (int) $month->format('N');

    $monthHtml = '<h3 style="text-align:center; margin: 10px 0 6px 0;">' . strtoupper(month_name_es($monthNum)) . ' ' . $yearNum . '</h3>';
    $monthHtml .= '<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse; table-layout: fixed; margin-bottom: 14px;">';
    $monthHtml .= '<thead><tr>';
    foreach (['L', 'M', 'X', 'J', 'V', 'S', 'D'] as $dayLabel) {
      $monthHtml .= '<th style="background-color:' . CALENDAR_COLOR_HEADER . '; border:1px solid #333333; text-align:center; padding:4px; font-size:10pt;">' . $dayLabel . '</th>';
    }
    $monthHtml .= '</tr></thead><tbody><tr>';

    for ($blank = 1; $blank < $startOffset; $blank++) {
      $monthHtml .= '<td style="border:1px solid #333333; height:28px;">&nbsp;</td>';
    }

    $column = $startOffset;
    for ($day = 1; $day <= $daysInMonth; $day++, $column++) {
      $currentDate = new DateTimeImmutable(sprintf('%04d-%02d-%02d', $yearNum, $monthNum, $day));
      $curKey = $currentDate->format('Y-m-d');

      $style = 'text-align:center; height:28px; vertical-align:middle; font-size:10pt; border:1px solid #333333;';
      if ($curKey < $startKey || $curKey > $endKey) {
        // Fuera de rango: sin color.
      } elseif ($curKey === $startKey) {
        $style .= ' background-color:' . CALENDAR_COLOR_START . '; color:#ffffff; font-weight:bold;';
      } elseif ($curKey === $endKey) {
        $style .= ' background-color:' . CALENDAR_COLOR_END . '; font-weight:bold;';
      } elseif (is_no_attendance_day($currentDate, $nonSchoolDays, $scheduleByDay)) {
        $style .= ' background-color:' . CALENDAR_COLOR_NO_ATTENDANCE . ';';
      } else {
        $style .= ' background-color:' . CALENDAR_COLOR_ATTENDANCE . ';';
      }

      $monthHtml .= '<td style="' . $style . '">' . $day . '</td>';

      if ($column % 7 === 0 && $day < $daysInMonth) {
        $monthHtml .= '</tr><tr>';
      }
    }

    while ($column % 7 !== 1) {
      $monthHtml .= '<td style="border:1px solid #333333; height:28px;">&nbsp;</td>';
      $column++;
    }

    $monthHtml .= '</tr></tbody></table>';
    $monthHtmlChunks[] = $monthHtml;
  }

  $html = '<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">';

  for ($index = 0; $index < count($monthHtmlChunks); $index += 2) {
    $html .= '<tr>';
    $html .= '<td width="50%" style="vertical-align:top; padding-right:8px;">' . $monthHtmlChunks[$index] . '</td>';

    if (isset($monthHtmlChunks[$index + 1])) {
      $html .= '<td width="50%" style="vertical-align:top; padding-left:8px;">' . $monthHtmlChunks[$index + 1] . '</td>';
    } else {
      $html .= '<td width="50%" style="vertical-align:top; padding-left:8px;">&nbsp;</td>';
    }

    $html .= '</tr>';
  }

  $html .= '</table>';

  return $html;
}

function count_attendance_days(DateTimeImmutable $startDate, DateTimeImmutable $endDate, array $nonSchoolDays, array $scheduleByDay): int {
  $count = 0;

  for ($current = $startDate; $current <= $endDate; $current = $current->modify('+1 day')) {
    if (!is_no_attendance_day($current, $nonSchoolDays, $scheduleByDay)) {
      $count++;
    }
  }

  return $count;
}
