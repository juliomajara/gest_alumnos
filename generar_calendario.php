<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/practicas_pdfs.php';

use Mpdf\Mpdf;

const CALENDAR_COLOR_HEADER = '#d9d9d9';
const CALENDAR_COLOR_ATTENDANCE = '#cce5ff';
const CALENDAR_COLOR_START = '#4a90e2';
const CALENDAR_COLOR_END = '#ccffcc';
const CALENDAR_COLOR_NO_ATTENDANCE = '#ffcccc';

function format_value($value, string $fallback = 'No disponible'): string {
  return ($value === null || $value === '') ? $fallback : (string) $value;
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

function full_name(array $row, string $prefix): string {
  $parts = array_filter([
    trim((string) ($row[$prefix . '_apellido1'] ?? '')),
    trim((string) ($row[$prefix . '_apellido2'] ?? '')),
  ], static fn (string $value): bool => $value !== '');
  $name = trim((string) ($row[$prefix . '_nombre'] ?? ''));

  return ($parts === [] && $name === '') ? 'No disponible' : trim(implode(' ', $parts) . ', ' . $name, ' ,');
}

function build_address(array $practice): string {
  $via = trim(implode(' ', array_filter([
    trim((string) ($practice['direccion_via_tipo'] ?? '')),
    trim((string) ($practice['direccion_nombre_via'] ?? '')),
  ], static fn (string $value): bool => $value !== '')));

  $parts = array_filter([
    $via,
    trim((string) ($practice['direccion_numero'] ?? '')),
    ($practice['direccion_bloque'] ?? '') !== '' ? 'Bloque ' . $practice['direccion_bloque'] : '',
    ($practice['direccion_escalera'] ?? '') !== '' ? 'Esc. ' . $practice['direccion_escalera'] : '',
    ($practice['direccion_planta'] ?? '') !== '' ? 'Planta ' . $practice['direccion_planta'] : '',
    ($practice['direccion_puerta'] ?? '') !== '' ? 'Puerta ' . $practice['direccion_puerta'] : '',
    trim((string) ($practice['direccion_otros'] ?? '')),
  ], static fn (string $value): bool => $value !== '');

  $cp = trim(implode(' ', array_filter([
    trim((string) ($practice['direccion_cp'] ?? '')),
    trim((string) ($practice['direccion_localidad'] ?? '')),
  ], static fn (string $value): bool => $value !== '')));

  if ($cp !== '') {
    $parts[] = $cp;
  }

  foreach (['direccion_provincia', 'direccion_pais'] as $field) {
    $value = trim((string) ($practice[$field] ?? ''));
    if ($value !== '') {
      $parts[] = $value;
    }
  }

  return $parts ? implode(', ', $parts) : 'No disponible';
}

function ensure_mpdf_temp_dir(): string {
  $tempDir = __DIR__ . '/docs/.mpdf_tmp';

  if (!is_dir($tempDir) && !mkdir($tempDir, 0755, true) && !is_dir($tempDir)) {
    throw new RuntimeException('No se pudo crear el directorio temporal para PDFs.');
  }

  if (!is_writable($tempDir)) {
    throw new RuntimeException('El directorio temporal para PDFs no tiene permisos de escritura.');
  }

  return $tempDir;
}

function month_name_es(int $month): string {
  $names = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
    7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
  ];

  return $names[$month] ?? '';
}

function time_to_seconds(?string $value): ?int {
  if ($value === null || $value === '') {
    return null;
  }

  $parts = explode(':', $value);
  if (count($parts) < 2) {
    return null;
  }

  return ((int) $parts[0]) * 3600 + ((int) $parts[1]) * 60 + ((int) ($parts[2] ?? 0));
}

function build_weekly_schedule_pdf_table(array $scheduleByDay, array $diasSemana): string {
  $html = '<table width="100%" border="1" cellpadding="4" cellspacing="0" style="border-collapse: collapse; font-size: 10pt; margin-bottom: 14px;">';
  $html .= '<thead><tr>';
  $html .= '<th rowspan="2" style="background-color:#d9d9d9; text-align:left;">Día</th>';
  $html .= '<th colspan="2" style="background-color:#d9d9d9; text-align:center;">Horario Mañana</th>';
  $html .= '<th colspan="2" style="background-color:#d9d9d9; text-align:center;">Horario tarde</th>';
  $html .= '<th rowspan="2" style="background-color:#d9d9d9; text-align:center;">Total</th>';
  $html .= '</tr><tr>';
  $html .= '<th style="background-color:#d9d9d9; text-align:center;">Inicio</th>';
  $html .= '<th style="background-color:#d9d9d9; text-align:center;">Fin</th>';
  $html .= '<th style="background-color:#d9d9d9; text-align:center;">Inicio</th>';
  $html .= '<th style="background-color:#d9d9d9; text-align:center;">Fin</th>';
  $html .= '</tr></thead><tbody>';

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
    $html .= '<td>' . htmlspecialchars($dayName, ENT_QUOTES, 'UTF-8') . '</td>';
    $html .= '<td style="text-align:center;">' . htmlspecialchars($morningStart, ENT_QUOTES, 'UTF-8') . '</td>';
    $html .= '<td style="text-align:center;">' . htmlspecialchars($morningEnd, ENT_QUOTES, 'UTF-8') . '</td>';
    $html .= '<td style="text-align:center;">' . htmlspecialchars($afternoonStart, ENT_QUOTES, 'UTF-8') . '</td>';
    $html .= '<td style="text-align:center;">' . htmlspecialchars($afternoonEnd, ENT_QUOTES, 'UTF-8') . '</td>';
    $html .= '<td style="text-align:center;">' . htmlspecialchars($totalLabel, ENT_QUOTES, 'UTF-8') . '</td>';
    $html .= '</tr>';
  }

  return $html . '</tbody></table>';
}

function is_no_attendance_day(DateTimeImmutable $date, array $nonSchoolDays, array $scheduleByDay): bool {
  $dateKey = $date->format('Y-m-d');
  if (isset($nonSchoolDays[$dateKey])) {
    return true;
  }

  $dayOfWeek = (int) $date->format('N');
  return !isset($scheduleByDay[$dayOfWeek]) || $scheduleByDay[$dayOfWeek] === [];
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
    $html .= '<td width="50%" style="vertical-align:top; padding-left:8px;">' . ($monthHtmlChunks[$index + 1] ?? '&nbsp;') . '</td>';
    $html .= '</tr>';
  }

  return $html . '</table>';
}


$idRaw = $_GET['id_practica'] ?? ($_POST['id_practica'] ?? ($_GET['id'] ?? ($_POST['id'] ?? null)));
$id = filter_var($idRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false || $id === null) {
  practicas_redirect_to_detail(0, null, 'Calendario: No se ha indicado un identificador de práctica válido.');
}

$tempFilePath = null;

try {
  $pdo = db();
  $stmt = $pdo->prepare('SELECT p.*,a.nombre AS alumno_nombre,a.apellido1 AS alumno_apellido1,a.apellido2 AS alumno_apellido2,e.convenio AS empresa_convenio,e.nombre_comercial AS empresa_nombre,d.nombre_via AS direccion_nombre_via,d.numero AS direccion_numero,d.bloque AS direccion_bloque,d.escalera AS direccion_escalera,d.planta AS direccion_planta,d.puerta AS direccion_puerta,d.otros AS direccion_otros,d.cp AS direccion_cp,v.via AS direccion_via_tipo,ld.nombre AS direccion_localidad,pd.nombre AS direccion_provincia,pa.pais AS direccion_pais FROM practicas p LEFT JOIN alumnos a ON a.id_alumno=p.id_alumno LEFT JOIN empresas e ON e.id_empresa=p.id_empresa LEFT JOIN direcciones d ON d.id_direccion=p.id_direccion LEFT JOIN vias v ON v.id_via=d.id_via LEFT JOIN localidades ld ON ld.id_localidad=d.id_localidad LEFT JOIN provincias pd ON pd.id_provincia=d.id_provincia LEFT JOIN paises pa ON pa.id_pais=d.id_pais WHERE p.id_practica=:id LIMIT 1');
  $stmt->execute(['id' => $id]);
  $practice = $stmt->fetch();

  if (!$practice) {
    practicas_redirect_to_detail((int) $id, null, 'Calendario: No se encontró la práctica solicitada.');
  }

  $scheduleByDay = [];
  $scheduleStmt = $pdo->prepare('SELECT dia_semana, hora_entrada, hora_salida FROM practicas_horario WHERE id_practica=:id ORDER BY dia_semana ASC, hora_entrada ASC');
  $scheduleStmt->execute(['id' => $id]);
  foreach ($scheduleStmt->fetchAll() as $row) {
    $day = (int) $row['dia_semana'];
    $scheduleByDay[$day][] = $row;
  }

  $paths = practicas_get_document_paths($practice);
  $startDateRaw = (string) ($practice['fecha_inicio'] ?? '');
  $endDateRaw = (string) ($practice['fecha_fin_real'] ?? '');
  if ($endDateRaw === '') {
    $endDateRaw = (string) ($practice['fecha_fin'] ?? '');
  }

  $startDate = $startDateRaw !== '' ? (new DateTimeImmutable($startDateRaw))->setTime(0, 0, 0) : false;
  $endDate = $endDateRaw !== '' ? (new DateTimeImmutable($endDateRaw))->setTime(0, 0, 0) : false;
  if (!$startDate || !$endDate || $startDate > $endDate) {
    practicas_redirect_to_detail((int) $id, null, 'Calendario: No se puede generar el calendario porque las fechas de inicio/fin son inválidas.');
  }

  $nonSchoolDays = [];
  $nonSchoolSourceNote = '';
  try {
    $nonSchoolStmt = $pdo->prepare('SELECT fecha FROM no_lectivos WHERE fecha BETWEEN :fi AND :ff');
    $nonSchoolStmt->execute(['fi' => $startDate->format('Y-m-d'), 'ff' => $endDate->format('Y-m-d')]);
    foreach ($nonSchoolStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
      if (!empty($row['fecha'])) {
        $nonSchoolDays[(string) $row['fecha']] = true;
      }
    }
  } catch (Throwable $error) {
    $nonSchoolSourceNote = 'Nota: no hay no lectivos configurados en el sistema para esta instalación.';
  }

  $diasSemana = [
    1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo',
  ];

  $pdfHtml = '<h1 style="margin-bottom:8px;">Calendario de prácticas</h1>';
  $pdfHtml .= '<p><strong>Alumno:</strong> ' . htmlspecialchars(full_name($practice, 'alumno'), ENT_QUOTES, 'UTF-8') . '</p>';
  $pdfHtml .= '<p><strong>Empresa:</strong> ' . htmlspecialchars((string) ($practice['empresa_nombre'] ?? 'No disponible'), ENT_QUOTES, 'UTF-8') . '</p>';
  $pdfHtml .= '<p><strong>Dirección Centro de Trabajo:</strong> ' . htmlspecialchars(build_address($practice), ENT_QUOTES, 'UTF-8') . '</p>';
  $pdfHtml .= '<p><strong>Fecha inicio:</strong> ' . htmlspecialchars(format_date((string) ($practice['fecha_inicio'] ?? '')), ENT_QUOTES, 'UTF-8') . ' &nbsp;&nbsp; <strong>Fecha fin:</strong> ' . htmlspecialchars(format_date($endDateRaw), ENT_QUOTES, 'UTF-8') . '</p>';
  $pdfHtml .= '<h3 style="margin: 12px 0 6px 0;">Horario semanal</h3>';
  $pdfHtml .= build_weekly_schedule_pdf_table($scheduleByDay, $diasSemana);
  $pdfHtml .= '<h3 style="margin: 12px 0 6px 0;">Calendario mensual</h3>';
  if ($nonSchoolSourceNote !== '') {
    $pdfHtml .= '<p style="font-size:9pt; color:#666666;">' . htmlspecialchars($nonSchoolSourceNote, ENT_QUOTES, 'UTF-8') . '</p>';
  }
  $pdfHtml .= build_calendar_html($startDate, $endDate, $nonSchoolDays, $scheduleByDay);
  $observaciones = trim((string) ($practice['observaciones'] ?? ''));
  $pdfHtml .= '<p style="font-family: Arial; margin-top: 12px;"><strong>Observaciones:</strong><br>' . htmlspecialchars($observaciones, ENT_QUOTES, 'UTF-8') . '</p>';

  $mpdf = new Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'default_font' => 'Arial',
    'margin_left' => 12,
    'margin_right' => 12,
    'margin_top' => 12,
    'margin_bottom' => 12,
    'tempDir' => ensure_mpdf_temp_dir(),
  ]);

  $css = '<style>html, body, .container { width: 100% !important; overflow: hidden; font-family: Arial !important; } * { font-family: Arial !important; } table { table-layout: fixed !important; width: 100% !important; border-collapse: collapse; } td, th { word-break: break-all !important; overflow-wrap: break-word !important; } img { max-width: 150px !important; }</style>';
  $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
  $mpdf->WriteHTML($pdfHtml, \Mpdf\HTMLParserMode::HTML_BODY);

  $tempFilePath = $paths['calendar_file_path'] . '.tmp';
  if (file_exists($tempFilePath) && !@unlink($tempFilePath)) {
    throw new RuntimeException('No se pudo limpiar el archivo temporal previo del calendario.');
  }

  $mpdf->Output($tempFilePath, \Mpdf\Output\Destination::FILE);

  if (!file_exists($tempFilePath) || filesize($tempFilePath) === 0) {
    throw new RuntimeException('No se ha podido crear el PDF del calendario.');
  }

  if (!@rename($tempFilePath, $paths['calendar_file_path'])) {
    throw new RuntimeException('No se ha podido guardar el PDF del calendario.');
  }

  practicas_redirect_to_detail((int) $id, 'calendar_generated', null);
} catch (Throwable $error) {
  if ($tempFilePath !== null && file_exists($tempFilePath)) {
    @unlink($tempFilePath);
  }

  practicas_redirect_to_detail((int) ($id ?: 0), null, 'Calendario: No se pudo generar el PDF del calendario en este momento.');
}
