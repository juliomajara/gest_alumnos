<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pdo = db();

$page_title = 'Importar asistencia mensual | Gestor de Alumnos';
$active_page = 'alumnos';

$messages = [];
$errors = [];
$result = [
  'importados' => 0,
  'actualizados' => 0,
  'alumnos_no_encontrados' => [],
  'filas_error_formato' => [],
  'errores' => [],
  'avisos' => [],
];

$active_course_id = (int) ($pdo->query('SELECT id_curso_escolar FROM cursos_escolares WHERE activo = 1 ORDER BY id_curso_escolar DESC LIMIT 1')->fetchColumn() ?: 0);
$current_month = (int) date('n');

$default_group_id = (int) ($pdo->query("SELECT valor FROM `config` WHERE `clave` = 'grupo_por_defecto' LIMIT 1")->fetchColumn() ?: 0);
$selected = [
  'id_curso_escolar' => (int) ($_POST['id_curso_escolar'] ?? $active_course_id),
  'id_ciclo' => 0,
  'id_curso' => 0,
  'id_grupo' => (int) ($_POST['id_grupo'] ?? $_GET['id_grupo'] ?? $default_group_id),
  'id_mes' => (int) ($_POST['id_mes'] ?? $current_month),
];

$cursos_escolares = $pdo->query('SELECT id_curso_escolar, curso_escolar FROM cursos_escolares ORDER BY activo DESC, id_curso_escolar DESC')->fetchAll(PDO::FETCH_ASSOC);
$grupos = $pdo->query('SELECT id_grupo, id_ciclo, id_curso, grupo FROM grupos ORDER BY grupo')->fetchAll(PDO::FETCH_ASSOC);
$meses = $pdo->query('SELECT id_mes, mes, orden FROM meses ORDER BY orden, id_mes')->fetchAll(PDO::FETCH_ASSOC);

$mes_valido_actual = false;
foreach ($meses as $mes_item) {
  if ((int) $mes_item['id_mes'] === $selected['id_mes']) {
    $mes_valido_actual = true;
    break;
  }
}
if (!$mes_valido_actual && $meses !== []) {
  $selected['id_mes'] = (int) $meses[0]['id_mes'];
}

function normalize_name(string $value): string {
  $value = str_replace("\xc2\xa0", ' ', $value);
  $value = str_replace("\xEF\xBB\xBF", '', $value);
  $value = str_replace(',', ' , ', $value);
  $value = mb_strtolower(trim($value), 'UTF-8');
  $translit = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
  if ($translit !== false) {
    $value = mb_strtolower($translit, 'UTF-8');
  }
  $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
  $value = preg_replace('/\s*,\s*/u', ',', $value) ?? $value;

  return trim($value);
}

function normalize_csv_text(string $value): string {
  $value = str_replace("\xEF\xBB\xBF", '', $value);
  $value = str_replace("\xc2\xa0", ' ', $value);
  $value = trim($value);

  if ($value === '') {
    return '';
  }

  if (!mb_check_encoding($value, 'UTF-8')) {
    $converted = @mb_convert_encoding($value, 'UTF-8', 'Windows-1252,ISO-8859-1,UTF-8');
    if (is_string($converted) && $converted !== '') {
      $value = $converted;
    }
  }

  return trim($value);
}

function parse_asistencia_cell(string $raw): array|false {
  $raw = trim($raw);
  if ($raw === '') {
    return [
      'J' => 0,
      'I' => 0,
      'R' => 0,
    ];
  }

  if ($raw === 'CI') {
    return [
      'TIPO' => 'CI',
      'J' => 0,
      'I' => 0,
      'R' => 0,
    ];
  }

  if ($raw === 'CJ') {
    return [
      'TIPO' => 'CJ',
      'J' => 0,
      'I' => 0,
      'R' => 0,
    ];
  }

  if (preg_match('/^\s*([0-9]+)\s*J\s*-\s*([0-9]+)\s*I\s*-\s*([0-9]+)\s*R\s*$/iu', $raw, $m)) {
    return [
      'J' => (int) $m[1],
      'I' => (int) $m[2],
      'R' => (int) $m[3],
    ];
  }

  return false;
}


function compute_threshold_reached_date(array $day_injustificadas, float $base_injustificadas, float $threshold): ?array {
  if ($threshold <= 0) {
    return null;
  }

  $acumuladas = $base_injustificadas;
  ksort($day_injustificadas);
  foreach ($day_injustificadas as $date => $horas_injustificadas) {
    $acumuladas += (float) $horas_injustificadas;
    if ($acumuladas >= $threshold) {
      return [
        'fecha' => $date,
        'cantidad' => $acumuladas,
      ];
    }
  }

  return null;
}

function parse_day_of_month_from_header(string $header_label): ?int {
  if (preg_match('/\b([0-9]{1,2})\b/u', $header_label, $m)) {
    $day = (int) $m[1];
    if ($day >= 1 && $day <= 31) {
      return $day;
    }
  }
  return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if ($selected['id_curso_escolar'] <= 0 || $selected['id_grupo'] <= 0 || $selected['id_mes'] <= 0) {
    $errors[] = 'Debes seleccionar curso escolar, grupo y mes.';
  }

  if (!isset($_FILES['csv_asistencia']) || !is_uploaded_file($_FILES['csv_asistencia']['tmp_name'])) {
    $errors[] = 'Debes seleccionar un archivo CSV válido.';
  } else {
    $csv_name = (string) ($_FILES['csv_asistencia']['name'] ?? '');
    $csv_ext = strtolower((string) pathinfo($csv_name, PATHINFO_EXTENSION));
    $mime = '';
    if (function_exists('finfo_open')) {
      $finfo = finfo_open(FILEINFO_MIME_TYPE);
      if ($finfo !== false) {
        $detected = finfo_file($finfo, $_FILES['csv_asistencia']['tmp_name']);
        if (is_string($detected)) {
          $mime = strtolower($detected);
        }
        finfo_close($finfo);
      }
    }

    $mime_valid = in_array($mime, ['text/csv', 'text/plain', 'application/vnd.ms-excel', 'application/csv'], true);
    if ($csv_ext !== 'csv' && !$mime_valid) {
      $errors[] = 'El archivo seleccionado no parece ser un CSV válido.';
    }
  }

  if ($errors === []) {
    $curso_valido_stmt = $pdo->prepare('SELECT 1 FROM cursos_escolares WHERE id_curso_escolar = :id_curso_escolar LIMIT 1');
    $curso_valido_stmt->execute([
      'id_curso_escolar' => $selected['id_curso_escolar'],
    ]);

    if (!$curso_valido_stmt->fetchColumn()) {
      $errors[] = 'El curso escolar seleccionado no es válido.';
    }

    $grupo_valido_stmt = $pdo->prepare('SELECT id_ciclo, id_curso FROM grupos WHERE id_grupo = :id_grupo LIMIT 1');
    $grupo_valido_stmt->execute([
      'id_grupo' => $selected['id_grupo'],
    ]);
    $grupo_contexto = $grupo_valido_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$grupo_contexto) {
      $errors[] = 'El grupo seleccionado no es válido.';
    } else {
      $selected['id_ciclo'] = (int) $grupo_contexto['id_ciclo'];
      $selected['id_curso'] = (int) $grupo_contexto['id_curso'];
    }

    $mes_valido_stmt = $pdo->prepare('SELECT 1 FROM meses WHERE id_mes = :id_mes LIMIT 1');
    $mes_valido_stmt->execute([
      'id_mes' => $selected['id_mes'],
    ]);

    if (!$mes_valido_stmt->fetchColumn()) {
      $errors[] = 'El mes seleccionado no es válido.';
    }
  }

  if ($errors === []) {
    $handle = fopen($_FILES['csv_asistencia']['tmp_name'], 'rb');
    if (!$handle) {
      $errors[] = 'No se pudo abrir el archivo CSV.';
    } else {
      $header = fgetcsv($handle, 0, ',', '"', '\\');
      if (!is_array($header) || count($header) < 2) {
        $errors[] = 'La cabecera del CSV no es válida.';
      } else {
        $header = array_map(static fn($v): string => normalize_csv_text((string) $v), $header);
        if (mb_strtolower((string) $header[0], 'UTF-8') !== mb_strtolower('Alumno/a', 'UTF-8')) {
          $errors[] = 'La primera columna de la cabecera debe ser "Alumno/a".';
        }
      }

      if ($errors === []) {
        $day_columns = [];
        for ($i = 1, $total = count($header); $i < $total; $i++) {
          $day_columns[] = $i;
        }

        if ($day_columns === []) {
          $errors[] = 'No se han encontrado columnas de días en la cabecera del CSV.';
        } else {
          $students_stmt = $pdo->prepare(
            'SELECT a.id_alumno, a.apellido1, a.apellido2, a.nombre
             FROM alumno_curso ac
             INNER JOIN alumnos a ON a.id_alumno = ac.id_alumno
             WHERE ac.id_curso_escolar = :id_curso_escolar
               AND ac.id_ciclo = :id_ciclo
               AND ac.id_curso = :id_curso
               AND ac.id_grupo = :id_grupo'
          );
          $students_stmt->execute([
            'id_curso_escolar' => $selected['id_curso_escolar'],
            'id_ciclo' => $selected['id_ciclo'],
            'id_curso' => $selected['id_curso'],
            'id_grupo' => $selected['id_grupo'],
          ]);

          $student_index = [];
          while ($student = $students_stmt->fetch(PDO::FETCH_ASSOC)) {
            $id_alumno = (int) $student['id_alumno'];
            $apellido1 = trim((string) ($student['apellido1'] ?? ''));
            $apellido2 = trim((string) ($student['apellido2'] ?? ''));
            $nombre = trim((string) ($student['nombre'] ?? ''));

            $variants = [
              trim($apellido1 . ' ' . $apellido2 . ', ' . $nombre),
              trim($apellido1 . ', ' . $nombre),
              trim($apellido1 . ' ' . $apellido2 . ' ' . $nombre),
              trim($apellido1 . ' ' . $nombre),
            ];

            foreach ($variants as $variant) {
              $key = normalize_name($variant);
              if ($key !== '' && !isset($student_index[$key])) {
                $student_index[$key] = $id_alumno;
              }
            }
          }

          $curso_escolar_texto_stmt = $pdo->prepare('SELECT curso_escolar FROM cursos_escolares WHERE id_curso_escolar = :id_curso_escolar LIMIT 1');
          $curso_escolar_texto_stmt->execute([
            'id_curso_escolar' => $selected['id_curso_escolar'],
          ]);
          $curso_escolar_texto = (string) ($curso_escolar_texto_stmt->fetchColumn() ?: '');
          $anio_inicio = 0;
          $anio_fin = 0;
          if (preg_match('/([0-9]{4}).*?([0-9]{4})/u', $curso_escolar_texto, $m)) {
            $anio_inicio = (int) $m[1];
            $anio_fin = (int) $m[2];
          }

          $year_for_month = $selected['id_mes'] >= 9 ? $anio_inicio : $anio_fin;
          if ($year_for_month <= 0) {
            $year_for_month = (int) date('Y');
          }

          $horas_por_alumno_dia = [];
          $horario_grupo_total_stmt = $pdo->prepare(
            'SELECT COUNT(*)
             FROM horarios_grupos
             WHERE id_curso_escolar = :id_curso_escolar
               AND id_grupo = :id_grupo'
          );
          $horario_grupo_total_stmt->execute([
            'id_curso_escolar' => $selected['id_curso_escolar'],
            'id_grupo' => $selected['id_grupo'],
          ]);
          $hay_horario_grupo = ((int) $horario_grupo_total_stmt->fetchColumn()) > 0;

          $horas_stmt = $pdo->prepare(
            'SELECT am.id_alumno, hg.dia_semana, COUNT(*) AS horas
             FROM horarios_grupos hg
             INNER JOIN alumno_modulo am ON am.id_modulo = hg.id_modulo
             WHERE hg.id_curso_escolar = :id_curso_escolar
               AND hg.id_grupo = :id_grupo
             GROUP BY am.id_alumno, hg.dia_semana'
          );
          $horas_stmt->execute([
            'id_curso_escolar' => $selected['id_curso_escolar'],
            'id_grupo' => $selected['id_grupo'],
          ]);
          while ($h = $horas_stmt->fetch(PDO::FETCH_ASSOC)) {
            $horas_por_alumno_dia[(int) $h['id_alumno']][(int) $h['dia_semana']] = (int) $h['horas'];
          }

          $column_day_week = [];
          foreach ($day_columns as $column_index) {
            $header_label = normalize_csv_text((string) ($header[$column_index] ?? ''));
            $day_of_month = parse_day_of_month_from_header($header_label);
            if ($day_of_month === null || !checkdate($selected['id_mes'], $day_of_month, $year_for_month)) {
              $column_day_week[$column_index] = null;
              continue;
            }
            $weekday = (int) date('N', strtotime(sprintf('%04d-%02d-%02d', $year_for_month, $selected['id_mes'], $day_of_month)));
            $column_day_week[$column_index] = $weekday;
          }

          $select_existing_stmt = $pdo->prepare(
            'SELECT id_asistencia
             FROM asistencia_mensual
             WHERE id_alumno = :id_alumno
               AND id_curso_escolar = :id_curso_escolar
               AND id_mes = :id_mes
             LIMIT 1'
          );

          $update_stmt = $pdo->prepare(
            'UPDATE asistencia_mensual
             SET faltas_justificadas = :faltas_justificadas,
                 faltas_injustificadas = :faltas_injustificadas,
                 retrasos = :retrasos
             WHERE id_asistencia = :id_asistencia'
          );

          $insert_stmt = $pdo->prepare(
            'INSERT INTO asistencia_mensual
              (id_alumno, id_curso_escolar, id_mes, faltas_justificadas, faltas_injustificadas, retrasos)
             VALUES
              (:id_alumno, :id_curso_escolar, :id_mes, :faltas_justificadas, :faltas_injustificadas, :retrasos)'
          );

          $hours_total_student_stmt = $pdo->prepare(
            'SELECT SUM(COALESCE(m.horas_totales, 0)) AS horas_totales_matriculadas
             FROM alumno_modulo am
             INNER JOIN modulos m ON m.id_modulo = am.id_modulo
             WHERE am.id_alumno = :id_alumno
               AND (
                 COALESCE(m.horas_semanales, 0) > 0
                 OR LOWER(COALESCE(m.materia_general, "")) LIKE "%proyecto intermodular%"
                 OR LOWER(COALESCE(m.materia_propia, "")) LIKE "%proyecto intermodular%"
               )'
          );
          $attendance_before_month_stmt = $pdo->prepare(
            'SELECT COALESCE(SUM(am.faltas_injustificadas), 0)
             FROM asistencia_mensual am
             INNER JOIN meses m_anterior ON m_anterior.id_mes = am.id_mes
             INNER JOIN meses m_actual ON m_actual.id_mes = :id_mes
             WHERE am.id_alumno = :id_alumno
               AND am.id_curso_escolar = :id_curso_escolar
               AND m_anterior.orden < m_actual.orden'
          );
          $student_thresholds_stmt = $pdo->prepare(
            'SELECT faltas_10_dia, faltas_10_cantidad, faltas_15_dia, faltas_15_cantidad
             FROM alumnos
             WHERE id_alumno = :id_alumno
             LIMIT 1'
          );

          $line = 1;
          $pdo->beginTransaction();
          try {
            while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
              $line++;
              $name_csv = normalize_csv_text((string) ($row[0] ?? ''));
              if ($name_csv === '') {
                continue;
              }

              $student_key = normalize_name($name_csv);
              if (!isset($student_index[$student_key])) {
                $result['alumnos_no_encontrados'][] = $name_csv;
                continue;
              }

              $totales_j = 0;
              $totales_i = 0;
              $totales_r = 0;
              $injustificadas_por_fecha = [];

              foreach ($day_columns as $column_index) {
                $raw_cell = normalize_csv_text((string) ($row[$column_index] ?? ''));
                $parsed = parse_asistencia_cell($raw_cell);

                if ($parsed === false) {
                  $result['filas_error_formato'][$line] = true;
                  $header_label = normalize_csv_text((string) ($header[$column_index] ?? ('Columna ' . ($column_index + 1))));
                  $result['errores'][] = 'Línea ' . $line . ' (' . $name_csv . ', ' . $header_label . '): formato no válido (' . $raw_cell . ').';
                  continue;
                }

                if (isset($parsed['TIPO']) && ($parsed['TIPO'] === 'CI' || $parsed['TIPO'] === 'CJ')) {
                  $id_alumno_actual = (int) $student_index[$student_key];
                  $weekday = $column_day_week[$column_index] ?? null;
                  $horas = 0;
                  $header_label = normalize_csv_text((string) ($header[$column_index] ?? ('Columna ' . ($column_index + 1))));
                  if ($weekday === null) {
                    $result['avisos'][] = 'No se pudo calcular el día de la semana para la columna "' . $header_label . '" (línea ' . $line . ', ' . $name_csv . ').';
                  } elseif ($weekday >= 6) {
                    $result['avisos'][] = 'CI/CJ en fin de semana para ' . $name_csv . ' (' . $header_label . '). Se usa 0.';
                  } else {
                    $horas = (int) ($horas_por_alumno_dia[$id_alumno_actual][$weekday] ?? 0);
                    if (!$hay_horario_grupo) {
                      $result['avisos'][] = 'No se pudo calcular CI/CJ para ' . $name_csv . ' en ' . $header_label . ' porque no hay horario guardado para el grupo.';
                    } elseif ($horas === 0) {
                      $result['avisos'][] = 'CI/CJ con 0 horas para ' . $name_csv . ' en ' . $header_label . ' según horario/matrícula.';
                    }
                  }
                  $parsed['J'] = $parsed['TIPO'] === 'CJ' ? $horas : 0;
                  $parsed['I'] = $parsed['TIPO'] === 'CI' ? $horas : 0;
                }

                $totales_j += (int) $parsed['J'];
                $totales_i += (int) $parsed['I'];
                $totales_r += (int) $parsed['R'];

                $day_of_month = parse_day_of_month_from_header(normalize_csv_text((string) ($header[$column_index] ?? '')));
                if ($day_of_month !== null && checkdate($selected['id_mes'], $day_of_month, $year_for_month)) {
                  $fecha_columna = sprintf('%04d-%02d-%02d', $year_for_month, $selected['id_mes'], $day_of_month);
                  if (!isset($injustificadas_por_fecha[$fecha_columna])) {
                    $injustificadas_por_fecha[$fecha_columna] = 0.0;
                  }
                  $injustificadas_por_fecha[$fecha_columna] += (float) $parsed['I'];
                }
              }

              $id_alumno_actual = (int) $student_index[$student_key];

              $hours_total_student_stmt->execute(['id_alumno' => $id_alumno_actual]);
              $horas_totales_matriculadas = (float) ($hours_total_student_stmt->fetchColumn() ?: 0);

              if ($horas_totales_matriculadas <= 0) {
                $result['avisos'][] = 'No se han podido calcular umbrales para ' . $name_csv . ' por falta de horas matriculadas válidas.';
              } else {
                $attendance_before_month_stmt->execute([
                  'id_alumno' => $id_alumno_actual,
                  'id_curso_escolar' => $selected['id_curso_escolar'],
                  'id_mes' => $selected['id_mes'],
                ]);
                $base_injustificadas = (float) ($attendance_before_month_stmt->fetchColumn() ?: 0);

                $alcance_10 = compute_threshold_reached_date($injustificadas_por_fecha, $base_injustificadas, $horas_totales_matriculadas * 0.10);
                $alcance_15 = compute_threshold_reached_date($injustificadas_por_fecha, $base_injustificadas, $horas_totales_matriculadas * 0.15);

                $student_thresholds_stmt->execute(['id_alumno' => $id_alumno_actual]);
                $thresholds_actuales = $student_thresholds_stmt->fetch(PDO::FETCH_ASSOC) ?: [];

                $update_fields = [];
                $update_params = ['id_alumno' => $id_alumno_actual];

                if ($alcance_10 !== null) {
                  $fecha_actual_10 = (string) ($thresholds_actuales['faltas_10_dia'] ?? '');
                  if ($fecha_actual_10 === '' || $alcance_10['fecha'] < $fecha_actual_10 || ($alcance_10['fecha'] === $fecha_actual_10 && (string) ($thresholds_actuales['faltas_10_cantidad'] ?? '') !== (string) $alcance_10['cantidad'])) {
                    $update_fields[] = 'faltas_10_dia = :faltas_10_dia';
                    $update_fields[] = 'faltas_10_cantidad = :faltas_10_cantidad';
                    $update_params['faltas_10_dia'] = $alcance_10['fecha'];
                    $update_params['faltas_10_cantidad'] = $alcance_10['cantidad'];
                  }
                }

                if ($alcance_15 !== null) {
                  $fecha_actual_15 = (string) ($thresholds_actuales['faltas_15_dia'] ?? '');
                  if ($fecha_actual_15 === '' || $alcance_15['fecha'] < $fecha_actual_15 || ($alcance_15['fecha'] === $fecha_actual_15 && (string) ($thresholds_actuales['faltas_15_cantidad'] ?? '') !== (string) $alcance_15['cantidad'])) {
                    $update_fields[] = 'faltas_15_dia = :faltas_15_dia';
                    $update_fields[] = 'faltas_15_cantidad = :faltas_15_cantidad';
                    $update_params['faltas_15_dia'] = $alcance_15['fecha'];
                    $update_params['faltas_15_cantidad'] = $alcance_15['cantidad'];
                  }
                }

                if ($update_fields !== []) {
                  $update_umbral_stmt = $pdo->prepare('UPDATE alumnos SET ' . implode(', ', $update_fields) . ' WHERE id_alumno = :id_alumno');
                  $update_umbral_stmt->execute($update_params);
                }
              }

              $select_existing_stmt->execute([
                'id_alumno' => (int) $student_index[$student_key],
                'id_curso_escolar' => $selected['id_curso_escolar'],
                'id_mes' => $selected['id_mes'],
              ]);
              $existing_id = $select_existing_stmt->fetchColumn();

              if ($existing_id) {
                $update_stmt->execute([
                  'id_asistencia' => (int) $existing_id,
                  'faltas_justificadas' => $totales_j,
                  'faltas_injustificadas' => $totales_i,
                  'retrasos' => $totales_r,
                ]);
                $result['actualizados']++;
              } elseif ($totales_j > 0 || $totales_i > 0 || $totales_r > 0) {
                $insert_stmt->execute([
                  'id_alumno' => (int) $student_index[$student_key],
                  'id_curso_escolar' => $selected['id_curso_escolar'],
                  'id_mes' => $selected['id_mes'],
                  'faltas_justificadas' => $totales_j,
                  'faltas_injustificadas' => $totales_i,
                  'retrasos' => $totales_r,
                ]);
                $result['importados']++;
              }
            }

            $pdo->commit();
          } catch (Throwable $e) {
            $pdo->rollBack();
            $errors[] = 'Error durante la importación: ' . $e->getMessage();
          }

          $result['alumnos_no_encontrados'] = array_values(array_unique($result['alumnos_no_encontrados']));
          $result['filas_error_formato'] = array_keys($result['filas_error_formato']);
          sort($result['filas_error_formato']);
          $result['avisos'] = array_values(array_unique($result['avisos']));
          if ($errors === []) {
            $messages[] = 'Importación finalizada.';
          }
        }
      }

      fclose($handle);
    }
  }
}
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
          <h1>Importar asistencia mensual</h1>
          <p class="subheading">Selecciona curso escolar, grupo y mes para cargar el CSV de asistencia.</p>
        </div>
      </header>

      <?php if ($errors !== []): ?>
        <section class="panel">
          <div class="panel-header">
            <h3>No se puede continuar</h3>
            <p>Corrige los siguientes errores antes de intentar importar de nuevo.</p>
          </div>
          <div class="import-list-section">
            <ul class="import-list-items import-list-items--error">
              <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        </section>
      <?php endif; ?>

      <?php if ($messages !== []): ?>
        <section class="panel">
          <div class="panel-header">
            <h3>Resultado de la importación</h3>
            <p>Resumen del proceso de carga del CSV de asistencia.</p>
          </div>
          <div class="import-counts">
            <div class="import-count import-count--ok">
              <span class="import-count-value"><?php echo (int) $result['importados']; ?></span>
              <span class="import-count-label">Registros nuevos</span>
            </div>
            <div class="import-count import-count--ok">
              <span class="import-count-value"><?php echo (int) $result['actualizados']; ?></span>
              <span class="import-count-label">Registros actualizados</span>
            </div>
            <div class="import-count <?php echo count($result['alumnos_no_encontrados']) > 0 ? 'import-count--warn' : ''; ?>">
              <span class="import-count-value"><?php echo count($result['alumnos_no_encontrados']); ?></span>
              <span class="import-count-label">Alumnos no encontrados</span>
            </div>
            <div class="import-count <?php echo count($result['filas_error_formato']) > 0 ? 'import-count--error' : ''; ?>">
              <span class="import-count-value"><?php echo count($result['filas_error_formato']); ?></span>
              <span class="import-count-label">Filas con error de formato</span>
            </div>
            <div class="import-count <?php echo count($result['avisos']) > 0 ? 'import-count--warn' : ''; ?>">
              <span class="import-count-value"><?php echo count($result['avisos']); ?></span>
              <span class="import-count-label">Avisos</span>
            </div>
          </div>

          <?php if ($result['alumnos_no_encontrados'] !== []): ?>
            <div class="import-list-section">
              <div class="import-list-header import-list-header--warn">Alumnos no encontrados (<?php echo count($result['alumnos_no_encontrados']); ?>)</div>
              <ul class="import-list-items">
                <?php foreach ($result['alumnos_no_encontrados'] as $item): ?>
                  <li><?php echo htmlspecialchars($item, ENT_QUOTES, 'UTF-8'); ?></li>
                <?php endforeach; ?>
              </ul>
              <p class="import-list-hint">El nombre del CSV no coincide con ningún alumno matriculado en el grupo. Comprueba tildes, espacios y el orden de apellidos.</p>
            </div>
          <?php endif; ?>

          <?php if ($result['filas_error_formato'] !== []): ?>
            <div class="import-list-section">
              <div class="import-list-header import-list-header--error">Filas con error de formato (<?php echo count($result['filas_error_formato']); ?>)</div>
              <ul class="import-list-items import-list-items--error">
                <?php foreach ($result['filas_error_formato'] as $linea): ?>
                  <li>Línea <?php echo (int) $linea; ?></li>
                <?php endforeach; ?>
              </ul>
              <p class="import-list-hint">Las celdas deben tener el formato <code>0J-0I-0R</code>, <code>CI</code> o <code>CJ</code>. Las celdas vacías se ignoran.</p>
            </div>
          <?php endif; ?>

          <?php if ($result['errores'] !== []): ?>
            <div class="import-list-section">
              <div class="import-list-header import-list-header--error">Errores de formato (<?php echo count($result['errores']); ?>)</div>
              <ul class="import-list-items import-list-items--error">
                <?php foreach ($result['errores'] as $item): ?>
                  <li><?php echo htmlspecialchars($item, ENT_QUOTES, 'UTF-8'); ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <?php if ($result['avisos'] !== []): ?>
            <div class="import-list-section">
              <div class="import-list-header import-list-header--warn">Avisos (<?php echo count($result['avisos']); ?>)</div>
              <ul class="import-list-items">
                <?php foreach ($result['avisos'] as $item): ?>
                  <li><?php echo htmlspecialchars($item, ENT_QUOTES, 'UTF-8'); ?></li>
                <?php endforeach; ?>
              </ul>
              <p class="import-list-hint">Los avisos no impiden la importación pero pueden indicar datos incompletos o configuración de horario ausente.</p>
            </div>
          <?php endif; ?>
        </section>
      <?php endif; ?>

      <form method="post" enctype="multipart/form-data" class="panel entity-form">
        <section class="entity-section">
          <div class="panel-header">
            <h3>Contexto académico</h3>
            <p>Selecciona curso escolar, grupo y mes para esta importación.</p>
          </div>

          <div class="entity-grid entity-grid--4">
            <label>
              Curso escolar
              <select name="id_curso_escolar" required>
                <option value="">Selecciona curso escolar</option>
                <?php foreach ($cursos_escolares as $item): ?>
                  <option value="<?php echo (int) $item['id_curso_escolar']; ?>" <?php echo (int) $item['id_curso_escolar'] === $selected['id_curso_escolar'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars((string) $item['curso_escolar'], ENT_QUOTES, 'UTF-8'); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </label>

            <label>
              Grupo
              <select name="id_grupo" required>
                <option value="">Selecciona grupo</option>
                <?php foreach ($grupos as $item): ?>
                  <option value="<?php echo (int) $item['id_grupo']; ?>" <?php echo (int) $item['id_grupo'] === $selected['id_grupo'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars((string) $item['grupo'], ENT_QUOTES, 'UTF-8'); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </label>

            <label>
              Mes
              <select name="id_mes" required>
                <option value="">Selecciona mes</option>
                <?php foreach ($meses as $item): ?>
                  <option value="<?php echo (int) $item['id_mes']; ?>" <?php echo (int) $item['id_mes'] === $selected['id_mes'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars((string) $item['mes'], ENT_QUOTES, 'UTF-8'); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </label>
          </div>

          <div class="entity-grid">
            <div class="file-field">
              <span class="file-field-label">Archivo CSV</span>
              <div class="file-field-control">
                <label class="file-field-btn" for="csvAsistenciaInput">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                  Seleccionar archivo
                </label>
                <span class="file-field-name" id="csvAsistenciaName">Ningún archivo seleccionado</span>
                <input type="file" id="csvAsistenciaInput" name="csv_asistencia" accept=".csv,text/csv" required class="file-field-input">
              </div>
            </div>
          </div>
          <script>
            (function () {
              var inp = document.getElementById('csvAsistenciaInput');
              var nm = document.getElementById('csvAsistenciaName');
              if (inp && nm) {
                inp.addEventListener('change', function () {
                  nm.textContent = this.files[0] ? this.files[0].name : 'Ningún archivo seleccionado';
                });
              }
            })();
          </script>

          <div class="form-actions">
            <button type="submit" class="primary-button">Importar asistencia</button>
          </div>
        </section>
      </form>
    </main>
  </div>
</body>
</html>
