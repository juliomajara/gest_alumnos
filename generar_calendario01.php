<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/practicas_pdfs.php';

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

  $parts = array_filter([
    $via,
    trim((string) ($practice['direccion_numero'] ?? '')),
    ($practice['direccion_bloque'] ?? '') !== '' ? 'Bloque ' . $practice['direccion_bloque'] : '',
    ($practice['direccion_escalera'] ?? '') !== '' ? 'Esc. ' . $practice['direccion_escalera'] : '',
    ($practice['direccion_planta'] ?? '') !== '' ? 'Planta ' . $practice['direccion_planta'] : '',
    ($practice['direccion_puerta'] ?? '') !== '' ? 'Puerta ' . $practice['direccion_puerta'] : '',
    trim((string) ($practice['direccion_otros'] ?? '')),
    trim((string) ($practice['direccion_cp'] ?? '')),
    trim((string) ($practice['direccion_localidad'] ?? '')),
    trim((string) ($practice['direccion_provincia'] ?? '')),
    trim((string) ($practice['direccion_pais'] ?? '')),
  ], static fn (string $item): bool => $item !== '');

  return $parts ? implode(', ', $parts) : 'No disponible';
}

function ensure_mpdf_temp_dir(): string {
  $tempDir = __DIR__ . '/docs/.mpdf_tmp';
  if (!is_dir($tempDir) && !mkdir($tempDir, 0755, true) && !is_dir($tempDir)) {
    throw new RuntimeException('No se pudo crear el directorio temporal de mPDF.');
  }
  if (!is_writable($tempDir)) {
    throw new RuntimeException('El directorio temporal de mPDF no tiene permisos de escritura.');
  }
  return $tempDir;
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
    if ($dayOfWeek >= 6) {
      return 'day nolectivo day-outside';
    }
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

function build_practice_month_index(DateTimeImmutable $start, DateTimeImmutable $end): array {
  $months = [];

  $firstMonth = new DateTimeImmutable($start->format('Y-m-01'));
  $lastMonth = new DateTimeImmutable($end->format('Y-m-01'));
  for ($current = $firstMonth; $current <= $lastMonth; $current = $current->modify('+1 month')) {
    $months[$current->format('Y-m')] = true;
  }

  return $months;
}

function append_class(DOMElement $element, string $className): void { // MODIFICADO
  $current = trim((string) $element->getAttribute('class')); // MODIFICADO
  $classes = $current === '' ? [] : (preg_split('/\s+/', $current) ?: []); // MODIFICADO
  if (!in_array($className, $classes, true)) { // MODIFICADO
    $classes[] = $className; // MODIFICADO
    $element->setAttribute('class', trim(implode(' ', $classes))); // MODIFICADO
  } // MODIFICADO
} // MODIFICADO

function as_file_uri(string $absolutePath): string {
  $normalized = str_replace(DIRECTORY_SEPARATOR, '/', $absolutePath);
  if (preg_match('/^[A-Za-z]:\//', $normalized) === 1) {
    return 'file:///' . $normalized;
  }
  return 'file://' . $normalized;
}

function resolve_logo_path(string $templateDir, ?string $configuredLogoPath = null): ?string {
  $candidates = [];
  $configuredLogoPath = trim((string) $configuredLogoPath);
  if ($configuredLogoPath !== '') {
    $candidates[] = $configuredLogoPath;
  }

  $envLogoPath = trim((string) getenv('CALENDARIO_LOGO_PATH'));
  if ($envLogoPath !== '') {
    $candidates[] = $envLogoPath;
  }

  $candidates[] = 'logo_IES.png';
  $candidates[] = $templateDir . '/logo_IES.png';
  $candidates[] = __DIR__ . '/logo_IES.png';
  $candidates[] = __DIR__ . '/docs/logo_IES.png';

  foreach ($candidates as $candidate) {
    $candidate = trim((string) $candidate);
    if ($candidate === '') {
      continue;
    }

    $resolved = realpath($candidate);
    if ($resolved === false && !str_starts_with($candidate, '/')) {
      $resolved = realpath($templateDir . '/' . ltrim($candidate, '/'));
    }
    if ($resolved === false && !str_starts_with($candidate, '/')) {
      $resolved = realpath(__DIR__ . '/' . ltrim($candidate, '/'));
    }

    if ($resolved !== false && is_file($resolved)) {
      return $resolved;
    }
  }

  return null;
}

function postprocess_calendar_html(string $html, array $scheduleByDay, string $institutoNombre, string $templateDir, ?string $configuredLogoPath = null): string { // MODIFICADO
  $dom = new DOMDocument(); // MODIFICADO
  $prevUseErrors = libxml_use_internal_errors(true); // MODIFICADO
  $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html); // MODIFICADO
  libxml_clear_errors(); // MODIFICADO
  libxml_use_internal_errors($prevUseErrors); // MODIFICADO

  $xpath = new DOMXPath($dom); // MODIFICADO

  $imgNodes = $xpath->query('//img[@src]');
  if ($imgNodes !== false) {
    foreach ($imgNodes as $imgNode) {
      if (!$imgNode instanceof DOMElement) {
        continue;
      }
      $src = trim((string) $imgNode->getAttribute('src'));
      if ($src === '' || preg_match('#^(?:https?:)?//#i', $src) || str_starts_with($src, 'data:')) {
        continue;
      }

      if (strcasecmp(basename($src), 'logo_IES.png') === 0) {
        $logoPath = resolve_logo_path($templateDir, $configuredLogoPath);
        if ($logoPath !== null) {
          $imgNode->setAttribute('src', as_file_uri($logoPath));
          continue;
        }

        $transparentPixel = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO7+2Q8AAAAASUVORK5CYII=';
        $imgNode->setAttribute('src', 'data:image/png;base64,' . $transparentPixel);
        continue;
      }

      $absolutePath = realpath($templateDir . '/' . ltrim($src, '/'));
      if ($absolutePath === false) {
        $absolutePath = realpath(__DIR__ . '/' . ltrim($src, '/'));
      }
      if ($absolutePath !== false && is_file($absolutePath)) {
        $imgNode->setAttribute('src', as_file_uri($absolutePath));
      } else {
        $transparentPixel = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO7+2Q8AAAAASUVORK5CYII=';
        $imgNode->setAttribute('src', 'data:image/png;base64,' . $transparentPixel);
      }
    }
  }

  $headNode = $xpath->query('//head')->item(0);
  if ($headNode instanceof DOMElement) {
    $styleNode = $dom->createElement('style');
    $styleCss = ".calendar-month thead { display: table-header-group !important; visibility: visible !important; }\n"
      . ".calendar-month thead tr { display: table-row !important; }\n"
      . ".calendar-month thead th { display: table-cell !important; }\n"
      . ".calendar-month th { color: #000 !important; padding: 4px 3px !important; }\n"
      . ".calendar-month .month-title { background: #f0f0f0 !important; color: #000 !important; font-weight: bold !important; text-align: center !important; }\n"
      . ".calendar-month .dow { background: #fafafa !important; font-weight: bold !important; text-align: center !important; }\n"
      . ".calendar-month .nolectivo { background: #f8d7da !important; }\n"
      . ".calendar-month .tutoria { background: #cfe2ff !important; }\n"
      . ".calendar-month .empresa { background: #d9f2d9 !important; }\n"
      . ".legend .swatch, .legend-box { display: inline-block !important; width: 18px !important; height: 12px !important; line-height: 12px !important; border: 1px solid #000 !important; vertical-align: middle !important; }\n"
      . ".legend .swatch-red, .legend-box.legend-red { background: #f8d7da !important; }\n"
      . ".legend .swatch-blue, .legend-box.legend-blue { background: #cfe2ff !important; }\n"
      . ".legend .swatch-green, .legend-box.legend-green { background: #d9f2d9 !important; }\n";
    $styleNode->appendChild($dom->createTextNode($styleCss));
    $headNode->appendChild($styleNode);
  }

  if (trim($institutoNombre) !== '') { // MODIFICADO
    $instNode = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " inst-name ")]')->item(0); // MODIFICADO
    if ($instNode instanceof DOMElement) { // MODIFICADO
      while ($instNode->firstChild) { // MODIFICADO
        $instNode->removeChild($instNode->firstChild); // MODIFICADO
      } // MODIFICADO
      $instNode->appendChild($dom->createTextNode($institutoNombre)); // MODIFICADO
    } // MODIFICADO
  } // MODIFICADO

  $dayNames = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo']; // MODIFICADO
  $scheduleTable = $xpath->query('//table[contains(concat(" ", normalize-space(@class), " "), " schedule ")]')->item(0); // MODIFICADO
  if ($scheduleTable instanceof DOMElement) { // MODIFICADO
    $rows = []; // MODIFICADO
    foreach ($scheduleTable->getElementsByTagName('tr') as $tr) { // MODIFICADO
      $rows[] = $tr; // MODIFICADO
    } // MODIFICADO

    for ($day = 1; $day <= 7; $day++) { // MODIFICADO
      $rowIndex = $day + 1; // MODIFICADO
      if (!isset($rows[$rowIndex]) || !$rows[$rowIndex] instanceof DOMElement) { // MODIFICADO
        continue; // MODIFICADO
      } // MODIFICADO

      if (!has_assigned_schedule_for_day($scheduleByDay[$day] ?? [])) { // MODIFICADO
        if ($rows[$rowIndex]->parentNode !== null) { // MODIFICADO
          $rows[$rowIndex]->parentNode->removeChild($rows[$rowIndex]); // MODIFICADO
        } // MODIFICADO
      } else { // MODIFICADO
        $firstCell = $rows[$rowIndex]->getElementsByTagName('td')->item(0); // MODIFICADO
        if ($firstCell instanceof DOMElement) { // MODIFICADO
          $firstCell->nodeValue = $dayNames[$day]; // MODIFICADO
        } // MODIFICADO
      } // MODIFICADO
    } // MODIFICADO
  } // MODIFICADO

  $monthsGrid = $xpath->query('//table[contains(concat(" ", normalize-space(@class), " "), " months-grid ")]')->item(0); // MODIFICADO
  if ($monthsGrid instanceof DOMElement) { // MODIFICADO
    $rows = []; // MODIFICADO
    foreach ($monthsGrid->getElementsByTagName('tr') as $tr) { // MODIFICADO
      $rows[] = $tr; // MODIFICADO
    } // MODIFICADO
    foreach ($rows as $tr) { // MODIFICADO
      $cells = []; // MODIFICADO
      foreach ($tr->getElementsByTagName('td') as $td) { // MODIFICADO
        $cells[] = $td; // MODIFICADO
      } // MODIFICADO
      foreach ($cells as $td) { // MODIFICADO
        $hasMonth = false; // MODIFICADO
        foreach ($td->getElementsByTagName('table') as $innerTable) { // MODIFICADO
          if (strpos(' ' . $innerTable->getAttribute('class') . ' ', ' month ') !== false) { // MODIFICADO
            $hasMonth = true; // MODIFICADO
            break; // MODIFICADO
          } // MODIFICADO
        } // MODIFICADO
        if (!$hasMonth && trim((string) $td->textContent) === '') { // MODIFICADO
          if ($td->parentNode !== null) { // MODIFICADO
            $td->parentNode->removeChild($td); // MODIFICADO
          } // MODIFICADO
        } // MODIFICADO
      } // MODIFICADO

      $remainingCells = $tr->getElementsByTagName('td'); // MODIFICADO
      if ($remainingCells->length === 0) { // MODIFICADO
        if ($tr->parentNode !== null) { // MODIFICADO
          $tr->parentNode->removeChild($tr); // MODIFICADO
        } // MODIFICADO
      } // MODIFICADO
    } // MODIFICADO
  } // MODIFICADO

  $legendTable = $xpath->query('//table[contains(concat(" ", normalize-space(@class), " "), " legend ")]')->item(0);
  if ($legendTable instanceof DOMElement) {
    $legendRows = $legendTable->getElementsByTagName('tr');
    if ($legendRows->length >= 2) {
      $legendSecondRow = $legendRows->item(1);
      $firstRowCells = $legendSecondRow instanceof DOMElement ? $legendSecondRow->getElementsByTagName('td') : null;
      if ($firstRowCells instanceof DOMNodeList && $firstRowCells->length >= 2) {
        $targetCell = $firstRowCells->item(1);
        if ($targetCell instanceof DOMElement) {
          $targetCell->nodeValue = 'No lectivo / fin de semana';
        }
      }
    }

    $alreadyHasGreen = false;
    foreach ($legendTable->getElementsByTagName('div') as $div) {
      if (strpos(' ' . $div->getAttribute('class') . ' ', ' swatch-green ') !== false) {
        $alreadyHasGreen = true;
        break;
      }
    }

    if (!$alreadyHasGreen) {
      $greenRow = $dom->createElement('tr');

      $cell1 = $dom->createElement('td');
      $greenSwatch = $dom->createElement('span');
      $greenSwatch->setAttribute('class', 'legend-box legend-green');
      $greenSwatch->appendChild($dom->createTextNode(' '));
      $cell1->appendChild($greenSwatch);

      $cell2 = $dom->createElement('td');
      $strong = $dom->createElement('strong', 'Día de prácticas en empresa');
      $cell2->appendChild($strong);

      $cell3 = $dom->createElement('td', 'Día asignado para la realización de prácticas en la empresa.');

      $greenRow->appendChild($cell1);
      $greenRow->appendChild($cell2);
      $greenRow->appendChild($cell3);

      $legendTable->appendChild($greenRow);
    }
  }

  $swatchNodes = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " swatch ")]');
  if ($swatchNodes !== false) {
    foreach ($swatchNodes as $swatchNode) {
      if (!$swatchNode instanceof DOMElement) {
        continue;
      }

      if (trim((string) $swatchNode->textContent) === '') {
        $swatchNode->appendChild($dom->createTextNode("\u{00A0}"));
      }
    }
  }

  $fieldNodes = $xpath->query('//table[contains(concat(" ", normalize-space(@class), " "), " outer ")]//*[self::td or self::th][not(contains(concat(" ", normalize-space(@class), " "), " section-title "))]'); // MODIFICADO
  if ($fieldNodes !== false) { // MODIFICADO
    foreach ($fieldNodes as $node) { // MODIFICADO
      if ($node instanceof DOMElement) { // MODIFICADO
        append_class($node, 'muted'); // MODIFICADO
      } // MODIFICADO
    } // MODIFICADO
  } // MODIFICADO

  $result = $dom->saveHTML(); // MODIFICADO
  return $result !== false ? $result : $html; // MODIFICADO
} // MODIFICADO

function build_month_table(DateTimeImmutable $month, DateTimeImmutable $start, DateTimeImmutable $end, array $noLectivos, array $tutorias, array $scheduleByDay): string {
  $year = (int) $month->format('Y');
  $monthNum = (int) $month->format('n');
  $days = (int) $month->format('t');
  $offset = (int) $month->format('N');

  $html = '<table class="month calendar-month">';
  $html .= '<thead>';
  $html .= '<tr><th colspan="7" class="month-title">' . month_name_es($monthNum) . ' ' . $year . '</th></tr>';
  $html .= '<tr><th class="dow">L</th><th class="dow">M</th><th class="dow">X</th><th class="dow">J</th><th class="dow">V</th><th class="dow">S</th><th class="dow">D</th></tr>';
  $html .= '</thead><tbody><tr>';

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

  return $html . '</tr></tbody></table>';
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
  $endRaw = (string) ($practice['fecha_fin_real'] ?? '');
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

  $institutoNombre = ''; // MODIFICADO
  $logoPathConfig = '';
  try { // MODIFICADO
    $configStmt = $pdo->prepare('SELECT clave, valor FROM config WHERE clave IN ("instituto_nombre", "calendario_logo_path")');
    $configStmt->execute();
    foreach ($configStmt->fetchAll(PDO::FETCH_ASSOC) as $configRow) {
      $clave = trim((string) ($configRow['clave'] ?? ''));
      $valor = trim((string) ($configRow['valor'] ?? ''));
      if ($clave === 'instituto_nombre') {
        $institutoNombre = $valor;
      }
      if ($clave === 'calendario_logo_path') {
        $logoPathConfig = $valor;
      }
    }
  } catch (Throwable $e) { // MODIFICADO
    $institutoNombre = ''; // MODIFICADO
    $logoPathConfig = '';
  } // MODIFICADO

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

  $templatePath = __DIR__ . '/docs/calendario.html';
  $template = file_get_contents($templatePath);
  if ($template === false) {
    throw new RuntimeException('No se pudo leer la plantilla del calendario.');
  }

  preg_match_all('/\{\{[A-Z0-9_]+\}\}/', $template, $matches);
  $templateMarkers = array_unique($matches[0] ?? []);

  $practiceForPaths = $practice;
  unset($practiceForPaths['empresa_nombre_comercial']);
  $paths = practicas_get_document_paths($practiceForPaths);

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
    '{{EMPRESA_DIRECCION}}' => build_address($practice),
    '{{CONTACTO_NOMBRE}}' => $contactoNombre,
    '{{CONTACTO_TELEFONO}}' => fallback_text($practice['tutor_empresa_telefono'] ?? null),
    '{{CONTACTO_CORREO}}' => fallback_text($practice['tutor_empresa_email'] ?? null),
    '{{TUTOR_EMPRESA_NOMBRE}}' => $contactoNombre,
    '{{TUTOR_EMPRESA_TELEFONO}}' => fallback_text($practice['tutor_empresa_telefono'] ?? null),
    '{{TUTOR_EMPRESA_CORREO}}' => fallback_text($practice['tutor_empresa_email'] ?? null),
    '{{OBSERVACIONES}}' => trim((string) ($practice['observaciones'] ?? '')),
  ];

  $replacements += build_schedule_tokens($scheduleByDay);

  $practiceMonths = build_practice_month_index($startDate, $endDate);

  $monthBase = new DateTimeImmutable($startDate->format('Y-m-01'));
  for ($i = 1; $i <= 12; $i++) {
    $monthDate = $monthBase->modify('+' . ($i - 1) . ' month');
    $monthKey = $monthDate->format('Y-m');
    $shouldRender = isset($practiceMonths[$monthKey]);
    $replacements['{{MES_' . str_pad((string) $i, 2, '0', STR_PAD_LEFT) . '}}'] = $shouldRender ? build_month_table($monthDate, $startDate, $endDate, $noLectivos, $tutorias, $scheduleByDay) : '';
  }

  foreach ($templateMarkers as $marker) {
    if (!array_key_exists($marker, $replacements)) {
      $replacements[$marker] = '';
    }
  }

  $html = str_replace(array_keys($replacements), array_values($replacements), $template);
  $html = postprocess_calendar_html($html, $scheduleByDay, $institutoNombre, dirname($templatePath), $logoPathConfig); // MODIFICADO

  $debugFlag = $_GET['mpdf_debug'] ?? getenv('CALENDARIO_MPDF_DEBUG') ?? '0';
  $mpdfDebug = in_array(strtolower(trim((string) $debugFlag)), ['1', 'true', 'yes', 'on'], true);

  $mpdf = new Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'tempDir' => ensure_mpdf_temp_dir(),
    'debug' => $mpdfDebug,
  ]);
  $mpdf->showImageErrors = $mpdfDebug;
  $mpdf->SetBasePath(dirname($templatePath) . '/');

  $tempFilePath = $paths['calendar_file_path'] . '.tmp';
  if (file_exists($tempFilePath)) {
    @unlink($tempFilePath);
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
  redirect_back_or_detail((int) ($id ?: 0), null, 'Calendario: No se pudo generar el PDF del calendario en este momento.');
}
