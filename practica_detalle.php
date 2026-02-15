<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/vendor/autoload.php';

use Mpdf\Mpdf;

const CALENDAR_COLOR_HEADER = '#d9d9d9';
const CALENDAR_COLOR_ATTENDANCE = '#cce5ff';
const CALENDAR_COLOR_START = '#4a90e2';
const CALENDAR_COLOR_END = '#ccffcc';
const CALENDAR_COLOR_NO_ATTENDANCE = '#ffcccc';

function format_value($value, string $fallback = 'No disponible'): string {
  if ($value === null || $value === '') {
    return $fallback;
  }

  return (string) $value;
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
  $parts = [
    sanitize_filename_component((string) ($practice['empresa_convenio'] ?? ''), 20),
    sanitize_filename_component((string) ($practice['anexo'] ?? ''), 20),
    sanitize_filename_component((string) ($practice['alumno_nombre'] ?? ''), 40),
    sanitize_filename_component((string) ($practice['alumno_apellido1'] ?? ''), 40),
    sanitize_filename_component((string) ($practice['empresa_nombre'] ?? ''), 70),
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

  $student = full_name($practice, 'alumno');
  $company = (string) ($practice['empresa_nombre'] ?? '');
  $dateForFile = date('Y-m-d');

  return sprintf(
    'PlanFormación_%s_%s_%s.pdf',
    $sanitize($student, 80),
    $sanitize($company, 80),
    $sanitize($dateForFile, 20)
  );
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

function build_plan_formacion_html(array $practice, array $scheduleByDay): string {
  $templatePath = __DIR__ . '/docs/practicas_plan_formacion.html';
  if (!is_file($templatePath)) {
    throw new RuntimeException('No se encuentra la plantilla docs/practicas_plan_formacion.html.');
  }

  $html = file_get_contents($templatePath);
  if ($html === false) {
    throw new RuntimeException('No se pudo leer la plantilla del plan de formación.');
  }

  $html = str_replace('src="bandera_CM.png"', 'src="' . htmlspecialchars(__DIR__ . '/docs/bandera_CM.png', ENT_QUOTES, 'UTF-8') . '"', $html);

  $dom = new DOMDocument();
  libxml_use_internal_errors(true);
  $htmlForDom = function_exists('mb_convert_encoding')
    ? mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8')
    : $html;
  $dom->loadHTML($htmlForDom);
  libxml_clear_errors();

  $xpath = new DOMXPath($dom);
  $setById = static function (DOMXPath $xpath, string $id, string $value): void {
    $nodes = $xpath->query('//*[@id="' . $id . '"]');
    if ($nodes instanceof DOMNodeList && $nodes->length > 0) {
      $node = $nodes->item(0);
      if ($node instanceof DOMElement) {
        $node->textContent = $value;
      }
    }
  };

  $studentName = full_name($practice, 'alumno');
  $companyName = format_value($practice['empresa_nombre']);
  $companyCif = format_value($practice['empresa_cif']);
  $companyTutor = full_name($practice, 'tutor');
  $courseName = format_value($practice['curso_escolar']);
  $cycleName = format_value($practice['grupo']);

  $endDateRaw = (string) ($practice['fecha_fin_real'] ?? '');
  if ($endDateRaw === '') {
    $endDateRaw = (string) ($practice['fecha_fin'] ?? '');
  }
  $scheduleSummary = implode(' / ', build_schedule_summary($scheduleByDay));

  $values = [
    'date' => date('d/m/Y'),
    'course' => $courseName,
    'ciclo-formativo' => $cycleName,
    'codigo' => format_value($practice['empresa_convenio']) . ' / ' . format_value($practice['anexo']),
    'curso' => format_value($practice['anexo']),
    'alumno' => $studentName,
    'correo_alumno' => format_value($practice['alumno_email'] ?? null),
    'telefono_alumno' => format_value($practice['alumno_telefono'] ?? null),
    'centro_docente' => 'No disponible',
    'correo_centro' => format_value($practice['centro_email'] ?? null),
    'telefono_centro' => format_value($practice['centro_telefono'] ?? null),
    'tutor_centro' => format_value($practice['tutor_centro'] ?? null),
    'correo_tutor_centro' => format_value($practice['tutor_centro_email'] ?? null),
    'telefono_tutor_centro' => format_value($practice['tutor_centro_telefono'] ?? null),
    'empresa' => $companyName,
    'nif_empresa' => $companyCif,
    'correo_empresa' => format_value($practice['empresa_email'] ?? null),
    'telefono_empresa' => format_value($practice['empresa_telefono'] ?? null),
    'tutor_empresa' => $companyTutor,
    'correo_tutor_empresa' => format_value($practice['tutor_empresa_email'] ?? null),
    'telefono_tutor_empresa' => format_value($practice['tutor_empresa_telefono'] ?? null),
    'periodo_1_numero' => '1',
    'periodo_1_calendario' => format_date((string) ($practice['fecha_inicio'] ?? '')) . ' - ' . format_date($endDateRaw),
    'periodo_1_horario' => $scheduleSummary,
    'total_horas' => format_value($practice['horas'], '0') . ' horas',
    'observaciones' => format_value($practice['observaciones'], 'Sin observaciones'),
    'causas_autorizacion' => (int) ($practice['circ_excep'] ?? 0) === 1
      ? 'Circunstancias excepcionales informadas en la práctica.'
      : 'No requiere autorización extraordinaria.',
  ];

  foreach ($values as $id => $value) {
    $setById($xpath, $id, $value);
  }

  $rendered = $dom->saveHTML();
  if (!is_string($rendered) || trim($rendered) === '') {
    throw new RuntimeException('No se pudo renderizar la plantilla del plan de formación.');
  }

  return $rendered;
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

$dias_semana = [
  1 => 'Lunes',
  2 => 'Martes',
  3 => 'Miércoles',
  4 => 'Jueves',
  5 => 'Viernes',
  6 => 'Sábado',
  7 => 'Domingo',
];

$id_practica_raw = $_GET['id_practica'] ?? ($_GET['id'] ?? null);
$id_practica = filter_var($id_practica_raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$action = isset($_GET['action']) ? (string) $_GET['action'] : '';
$post_action = isset($_POST['action']) ? (string) $_POST['action'] : '';

$load_error = null;
$practice = null;
$schedule_by_day = [];
$calendar_status = null;
$calendar_error = null;
$calendar_file_path = null;
$calendar_file_name = null;
$plan_status = null;
$plan_error = null;
$plan_file_path = null;
$plan_file_name = null;

$document_status_code = isset($_GET['doc_status']) ? (string) $_GET['doc_status'] : '';
if ($document_status_code === 'calendar_generated') {
  $calendar_status = 'Calendario generado correctamente.';
} elseif ($document_status_code === 'plan_generated') {
  $plan_status = 'Plan de formación generado correctamente.';
}

$document_error_message = isset($_GET['doc_error']) ? trim((string) $_GET['doc_error']) : '';
if ($document_error_message !== '') {
  if (str_starts_with($document_error_message, 'Calendario:')) {
    $calendar_error = trim(substr($document_error_message, strlen('Calendario:')));
  } elseif (str_starts_with($document_error_message, 'Plan:')) {
    $plan_error = trim(substr($document_error_message, strlen('Plan:')));
  }
}

if ($id_practica === false || $id_practica === null) {
  $load_error = 'No se ha indicado un identificador de práctica válido.';
} else {
  try {
    $pdo = db();

    $practice_stmt = $pdo->prepare(
      'SELECT
        p.*,
        p.fecha_fin,
        p.fecha_fin_real,
        p.dias_extra,
        pe.estado AS estado_practica,
        a.nia AS alumno_nia,
        a.dni AS alumno_dni,
        a.nombre AS alumno_nombre,
        a.apellido1 AS alumno_apellido1,
        a.apellido2 AS alumno_apellido2,
        e.cif AS empresa_cif,
        e.convenio AS empresa_convenio,
        e.nombre AS empresa_nombre,
        e.apellido1 AS empresa_apellido1,
        e.apellido2 AS empresa_apellido2,
        et.nombre AS tutor_nombre,
        et.apellido1 AS tutor_apellido1,
        et.apellido2 AS tutor_apellido2,
        et.dni AS tutor_dni,
        et.comentarios AS tutor_comentarios,
        d.etiqueta AS direccion_etiqueta,
        d.nombre_via AS direccion_nombre_via,
        d.numero AS direccion_numero,
        d.bloque AS direccion_bloque,
        d.escalera AS direccion_escalera,
        d.planta AS direccion_planta,
        d.puerta AS direccion_puerta,
        d.otros AS direccion_otros,
        d.cp AS direccion_cp,
        d.principal AS direccion_principal,
        v.via AS direccion_via_tipo,
        ld.nombre AS direccion_localidad,
        pd.nombre AS direccion_provincia,
        pa.pais AS direccion_pais,
        ac.id_curso_escolar,
        ce.curso_escolar,
        ac.id_grupo,
        g.grupo
      FROM practicas p
      INNER JOIN alumnos a ON a.id_alumno = p.id_alumno
      INNER JOIN empresas e ON e.id_empresa = p.id_empresa
      LEFT JOIN practicas_estados pe ON pe.id_practicas_estado = p.id_practicas_estado
      LEFT JOIN empresas_tutores et ON et.id_empresas_tutor = p.id_empresa_tutor
      LEFT JOIN direcciones d ON d.id_direccion = p.id_direccion
      LEFT JOIN vias v ON v.id_via = d.id_via
      LEFT JOIN localidades ld ON ld.id_localidad = d.id_localidad
      LEFT JOIN provincias pd ON pd.id_provincia = d.id_provincia
      LEFT JOIN paises pa ON pa.id_pais = d.id_pais
      LEFT JOIN alumno_curso ac
        ON ac.id_alumno = p.id_alumno
       AND ac.id_curso_escolar = (
            SELECT MAX(ac2.id_curso_escolar)
            FROM alumno_curso ac2
            WHERE ac2.id_alumno = p.id_alumno
        )
      LEFT JOIN cursos_escolares ce ON ce.id_curso_escolar = ac.id_curso_escolar
      LEFT JOIN grupos g ON g.id_grupo = ac.id_grupo
      WHERE p.id_practica = :id_practica
      LIMIT 1'
    );

    $practice_stmt->execute(['id_practica' => $id_practica]);
    $practice = $practice_stmt->fetch();

    if ($practice) {
      $schedule_stmt = $pdo->prepare(
        'SELECT id_practicas_horario, dia_semana, hora_entrada, hora_salida
         FROM practicas_horario
         WHERE id_practica = :id_practica
         ORDER BY dia_semana ASC, hora_entrada ASC'
      );
      $schedule_stmt->execute(['id_practica' => $id_practica]);

      foreach ($schedule_stmt->fetchAll() as $row) {
        $day = (int) $row['dia_semana'];
        if (!isset($schedule_by_day[$day])) {
          $schedule_by_day[$day] = [];
        }
        $schedule_by_day[$day][] = $row;
      }

      $calendarDirectory = __DIR__ . '/docs/practicas_info';
      if (!is_dir($calendarDirectory) && !mkdir($calendarDirectory, 0755, true) && !is_dir($calendarDirectory)) {
        throw new RuntimeException('No se pudo crear el directorio de calendarios.');
      }

      $calendar_file_name = build_calendar_pdf_filename($practice);
      $calendar_file_path = $calendarDirectory . '/' . $calendar_file_name;

      $planDirectory = __DIR__ . '/docs/practicas_plan_formacion';
      if (!is_dir($planDirectory) && !mkdir($planDirectory, 0755, true) && !is_dir($planDirectory)) {
        throw new RuntimeException('No se pudo crear el directorio de planes de formación.');
      }

      $plan_file_name = build_plan_pdf_filename($practice);
      $plan_file_path = $planDirectory . '/' . $plan_file_name;

      if ($action === 'descargar_calendario') {
        $realBase = realpath($calendarDirectory);
        $realFile = $calendar_file_path !== null && file_exists($calendar_file_path) ? realpath($calendar_file_path) : false;

        if ($realBase !== false && $realFile !== false && str_starts_with($realFile, $realBase . DIRECTORY_SEPARATOR) && is_readable($realFile)) {
          header('Content-Type: application/pdf');
          header('Content-Disposition: attachment; filename="calendario_practicas.pdf"; filename*=UTF-8\'\'' . rawurlencode($calendar_file_name));
          header('Content-Length: ' . (string) filesize($realFile));
          header('X-Content-Type-Options: nosniff');
          readfile($realFile);
          exit;
        }

        $calendar_error = 'El calendario no existe o no está disponible para descarga.';
      }

      if ($action === 'generar_calendario') {
        $startDateRaw = (string) ($practice['fecha_inicio'] ?? '');
        $endDateRaw = (string) ($practice['fecha_fin_real'] ?? '');
        if ($endDateRaw === '') {
          $endDateRaw = (string) ($practice['fecha_fin'] ?? '');
        }
        $startDate = $startDateRaw !== '' ? (new DateTimeImmutable($startDateRaw))->setTime(0, 0, 0) : false;
        $endDate = $endDateRaw !== '' ? (new DateTimeImmutable($endDateRaw))->setTime(0, 0, 0) : false;

        if (!$startDate || !$endDate || $startDate > $endDate) {
          $calendar_error = 'No se puede generar el calendario porque las fechas de inicio/fin son inválidas.';
        } else {
          $nonSchoolDays = [];
          $nonSchoolSourceNote = '';

          try {
            $festivosStmt = $pdo->prepare(
              'SELECT fecha
               FROM no_lectivos
               WHERE fecha BETWEEN :fecha_inicio AND :fecha_fin'
            );
            $festivosStmt->execute([
              'fecha_inicio' => $startDate->format('Y-m-d'),
              'fecha_fin' => $endDate->format('Y-m-d'),
            ]);

            foreach ($festivosStmt->fetchAll(PDO::FETCH_ASSOC) as $festivo) {
              if (!empty($festivo['fecha'])) {
                $nonSchoolDays[(string) $festivo['fecha']] = true;
              }
            }
          } catch (Throwable $nonSchoolError) {
            $nonSchoolSourceNote = 'Nota: no hay no lectivos configurados en el sistema para esta instalación.';
          }

          $attendanceDays = count_attendance_days($startDate, $endDate, $nonSchoolDays, $schedule_by_day);

          $pdfHtml = '<h1 style="margin-bottom:8px;">Calendario de prácticas</h1>';
          $pdfHtml .= '<p><strong>Alumno:</strong> ' . htmlspecialchars(full_name($practice, 'alumno'), ENT_QUOTES, 'UTF-8') . '</p>';
          $pdfHtml .= '<p><strong>Empresa:</strong> ' . htmlspecialchars((string) ($practice['empresa_nombre'] ?? 'No disponible'), ENT_QUOTES, 'UTF-8') . '</p>';
          $pdfHtml .= '<p><strong>Dirección Centro de Trabajo:</strong> ' . htmlspecialchars(build_address($practice), ENT_QUOTES, 'UTF-8') . '</p>';
          $pdfHtml .= '<p><strong>Fecha inicio:</strong> ' . htmlspecialchars(format_date((string) ($practice['fecha_inicio'] ?? '')), ENT_QUOTES, 'UTF-8') . ' &nbsp;&nbsp; <strong>Fecha fin:</strong> ' . htmlspecialchars(format_date($endDateRaw), ENT_QUOTES, 'UTF-8') . '</p>';
          $pdfHtml .= '<h3 style="margin: 12px 0 6px 0;">Horario semanal</h3>';
          $pdfHtml .= build_weekly_schedule_pdf_table($schedule_by_day, $dias_semana);
          $pdfHtml .= '<h3 style="margin: 12px 0 6px 0;">Calendario mensual</h3>';
          $pdfHtml .= '<p style="font-size:10pt;">Leyenda de colores: azul oscuro (inicio), verde (fin), azul claro (asistencia a empresa) y rojo claro (no asistencia a empresa).</p>';
          if ($nonSchoolSourceNote !== '') {
            $pdfHtml .= '<p style="font-size:10pt;">' . htmlspecialchars($nonSchoolSourceNote, ENT_QUOTES, 'UTF-8') . '</p>';
          }
          $pdfHtml .= build_calendar_html($startDate, $endDate, $nonSchoolDays, $schedule_by_day);
          $pdfHtml .= '<p style="margin-top:8px;"><strong>Total de horas de prácticas: ' . htmlspecialchars(format_value($practice['horas'], '0'), ENT_QUOTES, 'UTF-8') . ' horas</strong></p>';
          $pdfHtml .= '<p><strong>Total de días que asistirá a la empresa: ' . $attendanceDays . ' días</strong></p>';

          try {
            $mpdf = new Mpdf([
              'mode' => 'utf-8',
              'format' => 'A4',
              'margin_left' => 12,
              'margin_right' => 12,
              'margin_top' => 12,
              'margin_bottom' => 12,
            ]);
            $mpdf->WriteHTML($pdfHtml);
            $mpdf->Output($calendar_file_path, \Mpdf\Output\Destination::FILE);
            $calendar_status = 'Calendario generado correctamente.';
          } catch (Throwable $pdfError) {
            $calendar_error = 'No se pudo generar el PDF del calendario en este momento.';
          }
        }
      }

      if ($action === 'descargar_plan_formacion') {
        $realBase = realpath($planDirectory);
        $realFile = $plan_file_path !== null && file_exists($plan_file_path) ? realpath($plan_file_path) : false;

        if ($realBase !== false && $realFile !== false && str_starts_with($realFile, $realBase . DIRECTORY_SEPARATOR) && is_readable($realFile)) {
          header('Content-Type: application/pdf');
          header('Content-Disposition: attachment; filename="plan_formacion.pdf"; filename*=UTF-8\'\'' . rawurlencode($plan_file_name));
          header('Content-Length: ' . (string) filesize($realFile));
          header('X-Content-Type-Options: nosniff');
          readfile($realFile);
          exit;
        }

        $plan_error = 'El plan de formación no existe o no está disponible para descarga.';
      }

      if ($post_action === 'generar_plan_formacion') {
        $requiredFields = [
          'alumno_nombre' => 'nombre del alumno',
          'alumno_apellido1' => 'primer apellido del alumno',
          'empresa_nombre' => 'nombre de la empresa',
          'empresa_convenio' => 'número de convenio',
          'anexo' => 'número de anexo',
          'fecha_inicio' => 'fecha de inicio',
        ];

        $missing = [];
        foreach ($requiredFields as $field => $label) {
          if (trim((string) ($practice[$field] ?? '')) === '') {
            $missing[] = $label;
          }
        }

        $endDateRaw = trim((string) ($practice['fecha_fin_real'] ?? ''));
        if ($endDateRaw === '') {
          $endDateRaw = trim((string) ($practice['fecha_fin'] ?? ''));
        }
        if ($endDateRaw === '') {
          $missing[] = 'fecha de fin';
        }

        if ($missing !== []) {
          redirect_to_practice((int) $id_practica, null, 'Plan: Faltan datos esenciales para generar el PDF: ' . implode(', ', $missing) . '.');
        }

        try {
          $pdfHtml = build_plan_formacion_html($practice, $schedule_by_day);

          $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
          ]);
          $mpdf->WriteHTML($pdfHtml);
          $mpdf->Output($plan_file_path, \Mpdf\Output\Destination::FILE);

          redirect_to_practice((int) $id_practica, 'plan_generated', null);
        } catch (Throwable $planPdfError) {
          $errorMessage = $planPdfError instanceof RuntimeException
            ? $planPdfError->getMessage()
            : 'No se pudo generar el PDF del plan de formación en este momento.';
          redirect_to_practice((int) $id_practica, null, 'Plan: ' . $errorMessage);
        }
      }
    }
  } catch (Throwable $error) {
    $load_error = 'No se ha podido cargar el detalle de la práctica en este momento.';
  }
}

$practice_found = is_array($practice);
$student_name = $practice_found ? full_name($practice, 'alumno') : 'Práctica no encontrada';
$company_name = $practice_found ? full_name($practice, 'empresa') : 'Práctica no encontrada';
$page_title = $practice_found
  ? 'Detalle de práctica #' . (int) $practice['id_practica'] . ' | Gestor de Alumnos'
  : 'Práctica no encontrada | Gestor de Alumnos';
$active_page = 'practicas';
$calendar_exists = $practice_found && $calendar_file_path !== null && is_file($calendar_file_path);
$calendar_generated_at = $calendar_exists ? date('d/m/Y H:i', (int) filemtime($calendar_file_path)) : null;
$plan_exists = $practice_found && $plan_file_path !== null && is_file($plan_file_path);
$plan_generated_at = $plan_exists ? date('d/m/Y H:i', (int) filemtime($plan_file_path)) : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
  <div class="page">
    <?php require __DIR__ . '/includes/sidebar.php'; ?>

    <main class="content">
      <header class="header">
        <div>
          <p class="eyebrow">Detalle de práctica</p>
          <h1><?php echo htmlspecialchars($practice_found ? $student_name : 'Práctica no encontrada', ENT_QUOTES, 'UTF-8'); ?></h1>
          <p class="subheading">Consulta la información completa de la práctica y su horario asociado.</p>
        </div>
        <div class="header-actions">
          <a class="ghost-button" href="practicas.php">Volver a prácticas</a>
        </div>
      </header>

      <?php if ($load_error !== null): ?>
        <section class="panel">
          <div class="panel-header">
            <h3>Error al cargar la práctica</h3>
            <p><?php echo htmlspecialchars($load_error, ENT_QUOTES, 'UTF-8'); ?></p>
          </div>
        </section>
      <?php elseif (!$practice_found): ?>
        <section class="panel">
          <div class="panel-header">
            <h3>Práctica no encontrada</h3>
            <p>No existe ninguna práctica con el identificador indicado.</p>
          </div>
        </section>
      <?php else: ?>
        <?php if ($calendar_status !== null || $calendar_error !== null || $plan_status !== null || $plan_error !== null || $calendar_generated_at !== null || $plan_generated_at !== null): ?>
        <section class="panel">
          <div class="panel-header">
            <h3>Estado de documentos</h3>
              <?php if ($calendar_status !== null): ?>
                <p><?php echo htmlspecialchars($calendar_status, ENT_QUOTES, 'UTF-8'); ?></p>
              <?php endif; ?>
              <?php if ($calendar_error !== null): ?>
                <p><?php echo htmlspecialchars($calendar_error, ENT_QUOTES, 'UTF-8'); ?></p>
              <?php endif; ?>
              <?php if ($plan_status !== null): ?>
                <p><?php echo htmlspecialchars($plan_status, ENT_QUOTES, 'UTF-8'); ?></p>
              <?php endif; ?>
              <?php if ($plan_error !== null): ?>
                <p><?php echo htmlspecialchars($plan_error, ENT_QUOTES, 'UTF-8'); ?></p>
              <?php endif; ?>
              <?php if ($calendar_generated_at !== null): ?>
                <p>Calendario generado el <?php echo htmlspecialchars($calendar_generated_at, ENT_QUOTES, 'UTF-8'); ?>.</p>
              <?php endif; ?>
              <?php if ($plan_generated_at !== null): ?>
                <p>Plan de formación generado el <?php echo htmlspecialchars($plan_generated_at, ENT_QUOTES, 'UTF-8'); ?>.</p>
              <?php endif; ?>
            </div>
          </section>
        <?php endif; ?>
        <div class="grid">
          <section class="panel">
            <div class="panel-header">
              <h3>Resumen</h3>
              <p>Estado general, alumno, empresa y datos principales de la práctica.</p>
            </div>
            <div class="panel-grid">
              <table class="panel-table-aligned">
                <tbody>
                  <tr><th>Estado</th><td><?php echo htmlspecialchars(format_value($practice['estado_practica']), ENT_QUOTES, 'UTF-8'); ?></td></tr>
                  <tr><th>Curso escolar</th><td><?php echo htmlspecialchars(format_value($practice['curso_escolar']), ENT_QUOTES, 'UTF-8'); ?></td></tr>
                  <tr><th>Grupo</th><td><?php echo htmlspecialchars(format_value($practice['grupo']), ENT_QUOTES, 'UTF-8'); ?></td></tr>
                  <tr><th>Alumno</th><td><?php echo htmlspecialchars($student_name, ENT_QUOTES, 'UTF-8'); ?></td></tr>
                  <tr><th>NIA / DNI alumno</th><td><?php echo htmlspecialchars(format_value($practice['alumno_nia']) . ' / ' . format_value($practice['alumno_dni']), ENT_QUOTES, 'UTF-8'); ?></td></tr>
                  <tr><th>Empresa</th><td><?php echo htmlspecialchars($company_name, ENT_QUOTES, 'UTF-8'); ?></td></tr>
                  <tr><th>CIF / Convenio</th><td><?php echo htmlspecialchars(format_value($practice['empresa_cif']) . ' / ' . format_value($practice['empresa_convenio']), ENT_QUOTES, 'UTF-8'); ?></td></tr>
                  <tr><th>Centro de trabajo</th><td><?php echo htmlspecialchars(build_address($practice), ENT_QUOTES, 'UTF-8'); ?></td></tr>
                  <tr><th>Etiqueta dirección</th><td><?php echo htmlspecialchars(format_value($practice['direccion_etiqueta']), ENT_QUOTES, 'UTF-8'); ?></td></tr>
                  <tr><th>Tutor de empresa</th><td><?php echo htmlspecialchars(full_name($practice, 'tutor'), ENT_QUOTES, 'UTF-8'); ?></td></tr>
                  <tr><th>DNI tutor</th><td><?php echo htmlspecialchars(format_value($practice['tutor_dni']), ENT_QUOTES, 'UTF-8'); ?></td></tr>
                  <tr><th>Fecha inicio</th><td><?php echo htmlspecialchars(format_date($practice['fecha_inicio']), ENT_QUOTES, 'UTF-8'); ?></td></tr>
                  <tr><th>Fecha fin calculada</th><td><?php echo htmlspecialchars(format_date($practice['fecha_fin']), ENT_QUOTES, 'UTF-8'); ?></td></tr>
                  <tr><th>Días extra</th><td><?php echo htmlspecialchars((string) ((int) ($practice['dias_extra'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?></td></tr>
                  <tr><th>Fecha fin real</th><td><?php echo htmlspecialchars(format_date($practice['fecha_fin_real'] ?? null, '—'), ENT_QUOTES, 'UTF-8'); ?></td></tr>
                  <tr><th>Horas totales</th><td><?php echo htmlspecialchars(format_value($practice['horas']), ENT_QUOTES, 'UTF-8'); ?></td></tr>
                </tbody>
              </table>
            </div>
          </section>

          <section class="panel">
            <div class="panel-header">
              <h3>Auditoría y metadatos</h3>
              <p>Identificadores técnicos y banderas de anexos de la práctica.</p>
            </div>
            <div class="panel-grid">
              <table class="panel-table-aligned">
                <tbody>
                  <tr><th>ID práctica</th><td><?php echo htmlspecialchars((string) (int) $practice['id_practica'], ENT_QUOTES, 'UTF-8'); ?></td></tr>
                  <tr><th>ID alumno</th><td><?php echo htmlspecialchars((string) (int) $practice['id_alumno'], ENT_QUOTES, 'UTF-8'); ?></td></tr>
                  <tr><th>ID empresa</th><td><?php echo htmlspecialchars((string) (int) $practice['id_empresa'], ENT_QUOTES, 'UTF-8'); ?></td></tr>
                  <tr><th>ID dirección</th><td><?php echo htmlspecialchars(format_value($practice['id_direccion']), ENT_QUOTES, 'UTF-8'); ?></td></tr>
                  <tr><th>ID tutor empresa</th><td><?php echo htmlspecialchars(format_value($practice['id_empresa_tutor']), ENT_QUOTES, 'UTF-8'); ?></td></tr>
                  <tr><th>ID estado práctica</th><td><?php echo htmlspecialchars(format_value($practice['id_practicas_estado']), ENT_QUOTES, 'UTF-8'); ?></td></tr>
                  <tr><th>ID curso escolar</th><td><?php echo htmlspecialchars(format_value($practice['id_curso_escolar']), ENT_QUOTES, 'UTF-8'); ?></td></tr>
                  <tr><th>ID grupo</th><td><?php echo htmlspecialchars(format_value($practice['id_grupo']), ENT_QUOTES, 'UTF-8'); ?></td></tr>
                  <tr><th>Anexo</th><td><?php echo htmlspecialchars(format_value($practice['anexo']), ENT_QUOTES, 'UTF-8'); ?></td></tr>
                  <tr>
                    <th>Circunstancias excepcionales</th>
                    <td>
                      <?php
                        $circ_excep = isset($practice['circ_excep']) ? (int) $practice['circ_excep'] : null;
                        $circ_excep_label = $circ_excep === 1
                          ? 'Requiere solicitud de autorización para la realización de la FFE bajo circunstancias de carácter excepcional.'
                          : 'No';
                        echo htmlspecialchars($circ_excep_label, ENT_QUOTES, 'UTF-8');
                      ?>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </section>
        </div>

        <section class="panel">
          <div class="panel-header">
            <h3>Horario semanal</h3>
            <p>Tramos por día y cómputo de horas diario.</p>
          </div>
          <div class="panel-grid">
            <table class="practica-horario-table practica-horario-detalle-table">
              <thead>
                <tr>
                  <th>Día</th>
                  <th>Entrada mañana</th>
                  <th>Salida mañana</th>
                  <th>Entrada tarde</th>
                  <th>Salida tarde</th>
                  <th>Total</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($dias_semana as $day_number => $day_name): ?>
                  <?php
                    $segments = $schedule_by_day[$day_number] ?? [];
                    $morning = $segments[0] ?? null;
                    $afternoon = $segments[1] ?? null;
                    $total_seconds = 0;
                    foreach ($segments as $segment) {
                      $entrada = strtotime((string) $segment['hora_entrada']);
                      $salida = strtotime((string) $segment['hora_salida']);
                      if ($entrada !== false && $salida !== false && $salida > $entrada) {
                        $total_seconds += ($salida - $entrada);
                      }
                    }
                    $hours = intdiv($total_seconds, 3600);
                    $minutes = intdiv($total_seconds % 3600, 60);
                    $total_label = $total_seconds > 0 ? sprintf('%02d:%02d', $hours, $minutes) : '—';
                  ?>
                  <tr>
                    <td><?php echo htmlspecialchars($day_name, ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars(format_time($morning['hora_entrada'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars(format_time($morning['hora_salida'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars(format_time($afternoon['hora_entrada'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars(format_time($afternoon['hora_salida'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($total_label, ENT_QUOTES, 'UTF-8'); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </section>

        <section class="panel">
          <div class="panel-header">
            <h3>Observaciones</h3>
          </div>
          <div class="panel-grid">
            <p class="practica-observaciones"><?php echo nl2br(htmlspecialchars(format_value($practice['observaciones']), ENT_QUOTES, 'UTF-8')); ?></p>
            <?php if (!empty($practice['tutor_comentarios'])): ?>
              <p class="practica-observaciones-meta"><strong>Comentarios tutor empresa:</strong> <?php echo nl2br(htmlspecialchars((string) $practice['tutor_comentarios'], ENT_QUOTES, 'UTF-8')); ?></p>
            <?php endif; ?>
          </div>
        </section>

        <section class="panel">
          <div class="panel-header">
            <h3>Documentos</h3>
            <p>Generación y descarga de calendario y plan de formación.</p>
          </div>
          <div class="panel-grid">
            <p><strong>Calendario</strong></p>
            <div class="header-actions">
              <a class="primary-button" href="practica_detalle.php?id_practica=<?php echo (int) $id_practica; ?>&action=generar_calendario">Generar calendario</a>
              <?php if ($calendar_exists && $calendar_file_name !== null): ?>
                <a class="ghost-button" href="practica_detalle.php?id_practica=<?php echo (int) $id_practica; ?>&action=descargar_calendario">Descargar calendario</a>
              <?php endif; ?>
            </div>

            <p><strong>Plan de formación</strong></p>
            <div class="header-actions">
              <form method="post" action="practica_detalle.php?id_practica=<?php echo (int) $id_practica; ?>">
                <input type="hidden" name="action" value="generar_plan_formacion">
                <button type="submit" class="primary-button">Generar Plan Formación</button>
              </form>
              <?php if ($plan_exists && $plan_file_name !== null): ?>
                <a class="ghost-button" href="practica_detalle.php?id_practica=<?php echo (int) $id_practica; ?>&action=descargar_plan_formacion">Descargar Plan Formación</a>
              <?php endif; ?>
            </div>
          </div>
        </section>

        <?php
          $main_displayed_columns = [
            'id_practica', 'id_alumno', 'id_empresa', 'id_direccion', 'id_empresa_tutor',
            'anexo', 'id_practicas_estado', 'fecha_inicio', 'fecha_fin', 'horas',
            'requiere_anexo_5', 'requiere_anexo_6', 'observaciones',
          ];
          $additional_columns = [];
          foreach ($practice as $column => $value) {
            if (strpos($column, '_') === false) {
              continue;
            }
            if (!in_array($column, $main_displayed_columns, true) &&
                !str_starts_with($column, 'alumno_') &&
                !str_starts_with($column, 'empresa_') &&
                !str_starts_with($column, 'tutor_') &&
                !str_starts_with($column, 'direccion_') &&
                !in_array($column, ['estado_practica', 'curso_escolar', 'grupo', 'id_curso_escolar', 'id_grupo'], true)) {
              $additional_columns[$column] = $value;
            }
          }
        ?>
        <?php if ($additional_columns): ?>
          <section class="panel">
            <div class="panel-header">
              <h3>Campos adicionales</h3>
              <p>Datos en bruto no mostrados en los bloques principales.</p>
            </div>
            <div class="panel-grid">
              <table class="panel-table-aligned">
                <tbody>
                  <?php foreach ($additional_columns as $column => $value): ?>
                    <tr>
                      <th><?php echo htmlspecialchars($column, ENT_QUOTES, 'UTF-8'); ?></th>
                      <td><?php echo htmlspecialchars(format_value($value), ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </section>
        <?php endif; ?>
      <?php endif; ?>
    </main>
  </div>
</body>
</html>
