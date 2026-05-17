<?php
declare(strict_types=1);

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/practicas_pdfs.php';

use Mpdf\Mpdf;
use Mpdf\Output\Destination;

function fallback_text($value, string $fallback = 'No disponible'): string {
  $value = trim((string) ($value ?? ''));
  return $value === '' ? $fallback : $value;
}

function format_date_es(?string $value, string $fallback = 'No disponible'): string {
  $value = trim((string) $value);
  if ($value === '') {
    return $fallback;
  }

  $dt = DateTimeImmutable::createFromFormat('Y-m-d', $value);
  return $dt ? $dt->format('d/m/Y') : $value;
}

function format_time_es(?string $value, string $fallback = '--:--'): string {
  $value = trim((string) $value);
  if ($value === '') {
    return $fallback;
  }

  $dt = DateTimeImmutable::createFromFormat('H:i:s', $value)
    ?: DateTimeImmutable::createFromFormat('H:i', $value);

  return $dt ? $dt->format('H:i') : $value;
}

function to_seconds(?string $value): int {
  $value = trim((string) $value);
  if ($value === '') {
    return 0;
  }

  $parts = explode(':', $value);
  if (count($parts) < 2) {
    return 0;
  }

  return ((int) $parts[0] * 3600) + ((int) $parts[1] * 60) + (int) ($parts[2] ?? 0);
}

function build_person_name(array $row, string $prefix): string {
  $apellido1 = trim((string) ($row[$prefix . '_apellido1'] ?? ''));
  $apellido2 = trim((string) ($row[$prefix . '_apellido2'] ?? ''));
  $nombre = trim((string) ($row[$prefix . '_nombre'] ?? ''));

  $apellidos = trim($apellido1 . ' ' . $apellido2);
  $full = trim(($apellidos !== '' ? $apellidos . ', ' : '') . $nombre, ' ,');

  return $full === '' ? 'No disponible' : $full;
}

function build_address(array $practice): string {
  $via = trim(implode(' ', array_filter([
    trim((string) ($practice['direccion_via_tipo'] ?? '')),
    trim((string) ($practice['direccion_nombre_via'] ?? '')),
  ])));

  $provincia = trim((string) ($practice['direccion_provincia'] ?? ''));
  $parts = array_filter([
    $via,
    trim((string) ($practice['direccion_numero'] ?? '')),
    ($practice['direccion_bloque'] ?? '') !== '' ? 'Bloque ' . $practice['direccion_bloque'] : '',
    ($practice['direccion_escalera'] ?? '') !== '' ? 'Esc. ' . $practice['direccion_escalera'] : '',
    ($practice['direccion_planta'] ?? '') !== '' ? 'Planta ' . $practice['direccion_planta'] : '',
    ($practice['direccion_puerta'] ?? '') !== '' ? 'Puerta ' . $practice['direccion_puerta'] : '',
    trim((string) ($practice['direccion_cp'] ?? '')),
    trim((string) ($practice['direccion_localidad'] ?? '')),
    $provincia !== '' ? '(' . $provincia . ')' : '',
  ], static fn (string $item): bool => $item !== '');

  return $parts ? implode(', ', $parts) : 'No disponible';
}

function build_observaciones(?string $complementoDireccion, ?string $observacionesPractica): string {
  $complementoDireccion = trim((string) $complementoDireccion);
  $observacionesPractica = trim((string) $observacionesPractica);

  $partes = [];
  if ($complementoDireccion !== '') {
    $partes[] = 'Complemento a la dirección: ' . $complementoDireccion;
  }
  if ($observacionesPractica !== '') {
    $partes[] = $observacionesPractica;
  }

  return implode('<br>', $partes);
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
  $months = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
    7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
  ];
  return $months[$month] ?? '';
}

function day_css_class(DateTimeImmutable $date, DateTimeImmutable $start, DateTimeImmutable $end, array $noLectivos, array $tutorias, array $scheduleByDay): string {
  $key = $date->format('Y-m-d');
  $dayOfWeek = (int) $date->format('N');
  if ($key < $start->format('Y-m-d') || $key > $end->format('Y-m-d')) {
    return 'day day-outside';
  }
  if ($dayOfWeek >= 6 || isset($noLectivos[$key])) {
    return 'day nolectivo';
  }
  if (isset($tutorias[$key])) {
    return 'day tutoria';
  }
  if (has_assigned_schedule_for_day($scheduleByDay[$dayOfWeek] ?? [])) {
    return 'day empresa';
  }
  return 'day';
}

function has_assigned_schedule_for_day(array $rows): bool { // MODIFICADO
  foreach ($rows as $row) { // MODIFICADO
    $entrada = trim((string) ($row['hora_entrada'] ?? '')); // MODIFICADO
    $salida = trim((string) ($row['hora_salida'] ?? '')); // MODIFICADO
    if ($entrada !== '' || $salida !== '') { // MODIFICADO
      return true; // MODIFICADO
    } // MODIFICADO
  } // MODIFICADO
  return false; // MODIFICADO
} // MODIFICADO


function build_months_grid_html(DateTimeImmutable $start, DateTimeImmutable $end, array $noLectivos, array $tutorias, array $scheduleByDay): string {
  $monthsHtml = [];
  $firstMonth = new DateTimeImmutable($start->format('Y-m-01'));
  $lastMonth = new DateTimeImmutable($end->format('Y-m-01'));

  for ($current = $firstMonth; $current <= $lastMonth; $current = $current->modify('+1 month')) {
    $monthsHtml[] = build_month_table($current, $start, $end, $noLectivos, $tutorias, $scheduleByDay);
  }

  $rowsHtml = '';
  $total = count($monthsHtml);
  for ($i = 0; $i < $total; $i += 3) {
    $rowsHtml .= '<tr>';
    for ($j = 0; $j < 3; $j++) {
      $monthHtml = $monthsHtml[$i + $j] ?? '';
      $rowsHtml .= '<td style="width:33.333%;">' . $monthHtml . '</td>';
    }
    $rowsHtml .= '</tr>';
  }

  return $rowsHtml;
}

function build_schedule_rows(array $scheduleByDay): string {
  $dayNames = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];

  $rowsHtml = '';
  foreach ($dayNames as $day => $dayName) {
    if (!has_assigned_schedule_for_day($scheduleByDay[$day] ?? [])) {
      continue;
    }

    $rows = $scheduleByDay[$day] ?? [];
    $am = $rows[0] ?? null;
    $pm = $rows[1] ?? null;

    $amEnt = format_time_es($am['hora_entrada'] ?? null, '--:--');
    $amSal = format_time_es($am['hora_salida'] ?? null, '--:--');
    $pmEnt = format_time_es($pm['hora_entrada'] ?? null, '--:--');
    $pmSal = format_time_es($pm['hora_salida'] ?? null, '--:--');

    $seconds = 0;
    foreach ($rows as $r) {
      $in = to_seconds((string) ($r['hora_entrada'] ?? ''));
      $out = to_seconds((string) ($r['hora_salida'] ?? ''));
      if ($out > $in) {
        $seconds += ($out - $in);
      }
    }

    $hoursLabel = '--';
    if ($seconds > 0) {
      $h = intdiv($seconds, 3600);
      $m = intdiv($seconds % 3600, 60);
      $hoursLabel = $m > 0 ? sprintf('%d:%02d', $h, $m) : (string) $h;
    }

    $rowsHtml .= '<tr>'
      . '<td>' . $dayName . '</td>'
      . '<td>' . $amEnt . '</td>'
      . '<td>' . $amSal . '</td>'
      . '<td>' . $pmEnt . '</td>'
      . '<td>' . $pmSal . '</td>'
      . '<td>' . $hoursLabel . '</td>'
      . '</tr>';
  }

  return $rowsHtml;
}

function build_month_table(DateTimeImmutable $month, DateTimeImmutable $start, DateTimeImmutable $end, array $noLectivos, array $tutorias, array $scheduleByDay): string {
  $year = (int) $month->format('Y');
  $monthNum = (int) $month->format('n');
  $days = (int) $month->format('t');
  $offset = (int) $month->format('N');

  $html = '<table class="month calendar-month">';
  $html .= '<tr><th colspan="7" class="month-title">' . month_name_es($monthNum) . ' ' . $year . '</th></tr>';
  $html .= '<tr><th class="dow">L</th><th class="dow">M</th><th class="dow">X</th><th class="dow">J</th><th class="dow">V</th><th class="dow">S</th><th class="dow">D</th></tr>';
  $html .= '<tr>';

  for ($i = 1; $i < $offset; $i++) {
    $html .= '<td class="day empty">&nbsp;</td>';
  }

  $col = $offset;
  for ($d = 1; $d <= $days; $d++, $col++) {
    $date = new DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $monthNum, $d));
    $class = day_css_class($date, $start, $end, $noLectivos, $tutorias, $scheduleByDay);
    $attrs = '';
    if (strpos(' ' . $class . ' ', ' empresa ') !== false) {
      $attrs = ' bgcolor="#d9f2d9"';
    }
    $html .= '<td class="' . $class . '"' . $attrs . '>' . $d . '</td>';
    if ($col % 7 === 0 && $d < $days) {
      $html .= '</tr><tr>';
    }
  }

  while ($col % 7 !== 1) {
    $html .= '<td class="day empty">&nbsp;</td>';
    $col++;
  }

  return $html . '</tr></table>';
}

function build_schedule_tokens(array $scheduleByDay): array {
  $codes = [1 => 'LUN', 2 => 'MAR', 3 => 'MIE', 4 => 'JUE', 5 => 'VIE', 6 => 'SAB', 7 => 'DOM'];
  $tokens = [];

  foreach ($codes as $day => $code) {
    $rows = $scheduleByDay[$day] ?? [];
    $am = $rows[0] ?? null;
    $pm = $rows[1] ?? null;

    $amEnt = format_time_es($am['hora_entrada'] ?? null);
    $amSal = format_time_es($am['hora_salida'] ?? null);
    $pmEnt = format_time_es($pm['hora_entrada'] ?? null);
    $pmSal = format_time_es($pm['hora_salida'] ?? null);

    $seconds = 0;
    foreach ($rows as $r) {
      $in = to_seconds((string) ($r['hora_entrada'] ?? ''));
      $out = to_seconds((string) ($r['hora_salida'] ?? ''));
      if ($out > $in) {
        $seconds += ($out - $in);
      }
    }

    $hoursLabel = '--';
    if ($seconds > 0) {
      $h = intdiv($seconds, 3600);
      $m = intdiv($seconds % 3600, 60);
      $hoursLabel = $m > 0 ? sprintf('%d:%02d', $h, $m) : (string) $h;
    }

    $tokens['{{' . $code . '_AM_ENT}}'] = $amEnt;
    $tokens['{{' . $code . '_AM_SAL}}'] = $amSal;
    $tokens['{{' . $code . '_PM_ENT}}'] = $pmEnt;
    $tokens['{{' . $code . '_PM_SAL}}'] = $pmSal;
    $tokens['{{' . $code . '_H}}'] = $hoursLabel;
  }

  return $tokens;
}

function build_course_label(array $practice): string {
  $curso = trim((string) ($practice['curso'] ?? ''));
  if ($curso !== '') {
    return $curso;
  }

  $start = trim((string) ($practice['fecha_inicio'] ?? ''));
  if ($start === '') {
    return 'No disponible';
  }

  $dt = DateTimeImmutable::createFromFormat('Y-m-d', $start);
  if (!$dt) {
    return 'No disponible';
  }

  $year = (int) $dt->format('n') >= 9 ? (int) $dt->format('Y') : (int) $dt->format('Y') - 1;
  return $year . '/' . ($year + 1);
}

function redirect_back_or_detail(int $practiceId, ?string $status = null, ?string $error = null): void {
  $ref = trim((string) ($_SERVER['HTTP_REFERER'] ?? ''));
  if ($ref !== '') {
    $parts = parse_url($ref);
    if ($parts !== false && isset($parts['host'], $_SERVER['HTTP_HOST']) && strcasecmp((string) $parts['host'], (string) $_SERVER['HTTP_HOST']) === 0) {
      $query = [];
      if (isset($parts['query'])) {
        parse_str($parts['query'], $query);
      }
      if ($status !== null) {
        $query['doc_status'] = $status;
        unset($query['doc_error']);
      } elseif ($error !== null) {
        unset($query['doc_status']);
      }
      if ($error !== null) {
        $query['doc_error'] = $error;
      }
      $path = ($parts['path'] ?? 'practica_detalle.php');
      $newUrl = $path . ($query ? '?' . http_build_query($query) : '');
      if (isset($parts['fragment'])) {
        $newUrl .= '#' . $parts['fragment'];
      }
      header('Location: ' . $newUrl);
      exit;
    }
  }

  practicas_redirect_to_detail($practiceId, $status, $error);
}

$idRaw = $_GET['id_practica'] ?? null;
$id = filter_var($idRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false || $id === null) {
  redirect_back_or_detail(0, null, 'Calendario: No se ha indicado un identificador de práctica válido.');
}

$debugFlag = $_GET['mpdf_debug'] ?? getenv('CALENDARIO_MPDF_DEBUG') ?? '0';
$mpdfDebug = in_array(strtolower(trim((string) $debugFlag)), ['1', 'true', 'yes', 'on'], true);

$tempFilePath = null;

try {
  $pdo = db();

  $stmt = $pdo->prepare('SELECT p.*,a.nombre AS alumno_nombre,a.apellido1 AS alumno_apellido1,a.apellido2 AS alumno_apellido2,e.convenio AS empresa_convenio,e.nombre AS empresa_nombre,e.nombre_comercial AS empresa_nombre_comercial,et.nombre AS tutor_empresa_nombre,et.apellido1 AS tutor_empresa_apellido1,(SELECT t1.telefono FROM telefonos t1 WHERE t1.entidad_tipo = "alumno" AND t1.id_entidad = a.id_alumno ORDER BY t1.id_telefono ASC LIMIT 1) AS alumno_telefono,(SELECT c1.direccion_correo FROM correos c1 WHERE c1.entidad_tipo = "alumno" AND c1.id_entidad = a.id_alumno ORDER BY CASE WHEN TRIM(COALESCE(c1.etiqueta, "")) = "EducaMadrid" THEN 1 WHEN TRIM(COALESCE(c1.etiqueta, "")) = "Personal" THEN 2 ELSE 3 END, c1.id_correo ASC LIMIT 1) AS alumno_email,(SELECT t2.telefono FROM telefonos t2 WHERE t2.entidad_tipo = "empresa_tutor" AND t2.id_entidad = et.id_empresas_tutor ORDER BY t2.id_telefono ASC LIMIT 1) AS tutor_empresa_telefono,(SELECT c2.direccion_correo FROM correos c2 WHERE c2.entidad_tipo = "empresa_tutor" AND c2.id_entidad = et.id_empresas_tutor ORDER BY c2.id_correo ASC LIMIT 1) AS tutor_empresa_email,d.nombre_via AS direccion_nombre_via,d.numero AS direccion_numero,d.bloque AS direccion_bloque,d.escalera AS direccion_escalera,d.planta AS direccion_planta,d.puerta AS direccion_puerta,d.otros AS direccion_otros,d.cp AS direccion_cp,v.via AS direccion_via_tipo,ld.nombre AS direccion_localidad,pd.nombre AS direccion_provincia,pa.pais AS direccion_pais FROM practicas p LEFT JOIN alumnos a ON a.id_alumno=p.id_alumno LEFT JOIN empresas e ON e.id_empresa=p.id_empresa LEFT JOIN empresas_tutores et ON et.id_empresas_tutor=p.id_empresa_tutor LEFT JOIN direcciones d ON d.id_direccion=p.id_direccion LEFT JOIN vias v ON v.id_via=d.id_via LEFT JOIN localidades ld ON ld.id_localidad=d.id_localidad LEFT JOIN provincias pd ON pd.id_provincia=d.id_provincia LEFT JOIN paises pa ON pa.id_pais=d.id_pais WHERE p.id_practica=:id LIMIT 1');
  $stmt->execute(['id' => $id]);
  $practice = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$practice) {
    redirect_back_or_detail((int) $id, null, 'Calendario: No se encontró la práctica solicitada.');
  }

  $scheduleByDay = [];
  $scheduleStmt = $pdo->prepare('SELECT dia_semana, hora_entrada, hora_salida FROM practicas_horario WHERE id_practica=:id ORDER BY dia_semana ASC, hora_entrada ASC');
  $scheduleStmt->execute(['id' => $id]);
  foreach ($scheduleStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $scheduleByDay[(int) $row['dia_semana']][] = $row;
  }

  $startRaw = (string) ($practice['fecha_inicio'] ?? '');
  $endRaw = (string) ($practice['fecha_fin_extra'] ?? '');
  if (trim($endRaw) === '') {
    $endRaw = (string) ($practice['fecha_fin'] ?? '');
  }

  $startDate = DateTimeImmutable::createFromFormat('Y-m-d', $startRaw);
  $endDate = DateTimeImmutable::createFromFormat('Y-m-d', $endRaw);
  if (!$startDate || !$endDate || $startDate > $endDate) {
    redirect_back_or_detail((int) $id, null, 'Calendario: No se puede generar el calendario porque las fechas de inicio/fin son inválidas.');
  }

  $noLectivos = [];
  try {
    $noLectivosStmt = $pdo->prepare('SELECT fecha FROM no_lectivos WHERE fecha BETWEEN :fi AND :ff');
    $noLectivosStmt->execute(['fi' => $startDate->format('Y-m-d'), 'ff' => $endDate->format('Y-m-d')]);
    foreach ($noLectivosStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
      if (!empty($row['fecha'])) {
        $noLectivos[(string) $row['fecha']] = true;
      }
    }
  } catch (Throwable $e) {
  }

  $institutoNombre = 'NOMBRE DEL INSTITUTO';
  try {
    $configStmt = $pdo->prepare('SELECT clave, valor FROM config WHERE clave IN ("instituto_nombre")');
    $configStmt->execute();
    foreach ($configStmt->fetchAll(PDO::FETCH_ASSOC) as $configRow) {
      $clave = trim((string) ($configRow['clave'] ?? ''));
      $valor = trim((string) ($configRow['valor'] ?? ''));
      if ($clave === 'instituto_nombre') {
        $institutoNombre = $valor;
      }
    }
  } catch (Throwable $e) {
    $institutoNombre = 'NOMBRE DEL INSTITUTO';
  }

  $tutorias = [];
  try {
    $tutoriasStmt = $pdo->prepare('SELECT fecha FROM tutorias WHERE fecha BETWEEN :fi AND :ff');
    $tutoriasStmt->execute(['fi' => $startDate->format('Y-m-d'), 'ff' => $endDate->format('Y-m-d')]);
    foreach ($tutoriasStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
      if (!empty($row['fecha'])) {
        $tutorias[(string) $row['fecha']] = true;
      }
    }
  } catch (Throwable $e) {
  }

  $templateCandidates = [
    __DIR__ . '/docs/calendario.html',
    __DIR__ . '/calendario.html',
  ];
  $templatePath = null;
  $template = false;
  foreach ($templateCandidates as $candidate) {
    if (!is_file($candidate) || !is_readable($candidate)) {
      continue;
    }
    $content = file_get_contents($candidate);
    if ($content !== false) {
      $templatePath = $candidate;
      $template = $content;
      break;
    }
  }
  if ($template === false || $templatePath === null) {
    throw new RuntimeException('No se pudo leer la plantilla del calendario. Rutas probadas: ' . implode(', ', $templateCandidates));
  }

  preg_match_all('/\{\{[A-Z0-9_]+\}\}/', $template, $matches);
  $templateMarkers = array_unique($matches[0] ?? []);

  $paths = practicas_get_document_paths($practice);

  $contactoNombre = fallback_text(trim((string) ($practice['tutor_empresa_nombre'] ?? '') . ' ' . (string) ($practice['tutor_empresa_apellido1'] ?? '')));

  $replacements = [
    '{{CURSO}}' => build_course_label($practice),
    '{{FECHA_INICIO}}' => format_date_es($startDate->format('Y-m-d')),
    '{{FECHA_FIN}}' => format_date_es($endDate->format('Y-m-d')),
    '{{ALUMNO_NOMBRE}}' => build_person_name($practice, 'alumno'),
    '{{ALUMNO_TELEFONO}}' => fallback_text($practice['alumno_telefono'] ?? null),
    '{{ALUMNO_CORREO}}' => fallback_text($practice['alumno_email'] ?? null),
    '{{EMPRESA_NOMBRE}}' => fallback_text($practice['empresa_nombre_comercial'] ?? ($practice['empresa_nombre'] ?? null)),
    '{{EMPRESA_CENTRO}}' => fallback_text($practice['empresa_nombre'] ?? null),
    '{{DIR_CENTRO_TRABAJO_FMT}}' => build_address($practice),
    '{{CONTACTO_NOMBRE}}' => $contactoNombre,
    '{{CONTACTO_TELEFONO}}' => fallback_text($practice['tutor_empresa_telefono'] ?? null),
    '{{CONTACTO_CORREO}}' => fallback_text($practice['tutor_empresa_email'] ?? null),
    '{{TUTOR_EMPRESA_NOMBRE}}' => $contactoNombre,
    '{{TUTOR_EMPRESA_TELEFONO}}' => fallback_text($practice['tutor_empresa_telefono'] ?? null),
    '{{TUTOR_EMPRESA_CORREO}}' => fallback_text($practice['tutor_empresa_email'] ?? null),
    '{{OBSERVACIONES}}' => build_observaciones($practice['direccion_otros'] ?? null, $practice['observaciones'] ?? null),
    '{{INSTITUTO_NOMBRE}}' => fallback_text($institutoNombre, 'NOMBRE DEL INSTITUTO'),
    '{{HORARIO_FILAS}}' => build_schedule_rows($scheduleByDay),
  ];

  $replacements += build_schedule_tokens($scheduleByDay);

  $replacements['{{MESES_GRID}}'] = build_months_grid_html($startDate, $endDate, $noLectivos, $tutorias, $scheduleByDay);

  foreach ($templateMarkers as $marker) {
    if (!array_key_exists($marker, $replacements)) {
      $replacements[$marker] = '';
    }
  }

  $html = strtr($template, $replacements);

  $mpdf = new Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'tempDir' => ensure_mpdf_temp_dir(),
    'debug' => $mpdfDebug,
  ]);
  $mpdf->showImageErrors = $mpdfDebug;
  $mpdf->SetBasePath(dirname($templatePath) . '/');

  $tempFilePath = $paths['calendar_file_path'] . '.tmp';
  $htmlDebugPath = $paths['calendar_file_path'] . '.html.tmp';
  $targetDir = dirname($paths['calendar_file_path']);

  if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
    throw new RuntimeException('No se pudo crear el directorio de destino del calendario: ' . $targetDir);
  }
  $testFile = $targetDir . '/_write_test.tmp';
  if (@file_put_contents($testFile, '1') === false) {
      throw new RuntimeException('El directorio de destino del calendario no tiene permisos de escritura: ' . $targetDir);
  }
  @unlink($testFile);

  if (file_exists($tempFilePath)) {
    @unlink($tempFilePath);
  }
  if ($mpdfDebug && @file_put_contents($htmlDebugPath, $html) === false) {
    throw new RuntimeException('No se pudo guardar el HTML de depuración del calendario: ' . $htmlDebugPath);
  }

  $mpdf->WriteHTML($html);
  $mpdf->Output($tempFilePath, Destination::FILE);

  if (!file_exists($tempFilePath) || filesize($tempFilePath) <= 0) {
    throw new RuntimeException('No se ha podido crear el PDF del calendario.');
  }

  if (!@rename($tempFilePath, $paths['calendar_file_path'])) {
    throw new RuntimeException('No se ha podido guardar el PDF del calendario.');
  }

  redirect_back_or_detail((int) $id, 'calendar_generated', null);
} catch (Throwable $e) {
  if ($tempFilePath !== null && file_exists($tempFilePath)) {
    @unlink($tempFilePath);
  }
  if ($mpdfDebug) {
    error_log(sprintf(
      'Calendario mPDF error: %s in %s:%d',
      $e->getMessage(),
      $e->getFile(),
      $e->getLine()
    ));
    redirect_back_or_detail((int) ($id ?: 0), null, 'Calendario: ' . $e->getMessage());
  }
  redirect_back_or_detail((int) ($id ?: 0), null, 'Calendario: No se pudo generar el PDF del calendario en este momento.');
}
