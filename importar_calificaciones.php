<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pdo = db();

$page_title = 'Importar calificaciones | Gestor de Alumnos';
$active_page = 'alumnos';

$messages = [];
$errors = [];
$result = [
  'importadas' => 0,
  'alumnos_no_encontrados' => [],
  'modulos_no_encontrados' => [],
  'errores' => [],
];

$active_course_id = (int) ($pdo->query('SELECT id_curso_escolar FROM cursos_escolares WHERE activo = 1 LIMIT 1')->fetchColumn() ?: 0);

$default_group_id = (int) ($pdo->query("SELECT valor FROM `config` WHERE `clave` = 'grupo_por_defecto' LIMIT 1")->fetchColumn() ?: 0);
$selected = [
  'id_curso_escolar' => (int) ($_POST['id_curso_escolar'] ?? $active_course_id),
  'id_ciclo' => 0,
  'id_curso' => 0,
  'id_grupo' => (int) ($_POST['id_grupo'] ?? $_GET['id_grupo'] ?? $default_group_id),
  'id_evaluacion' => (int) ($_POST['id_evaluacion'] ?? 0),
];

$cursos_escolares = $pdo->query('SELECT id_curso_escolar, curso_escolar FROM cursos_escolares ORDER BY activo DESC, id_curso_escolar DESC')->fetchAll(PDO::FETCH_ASSOC);
$grupos = $pdo->query('SELECT id_grupo, id_ciclo, id_curso, grupo FROM grupos ORDER BY grupo')->fetchAll(PDO::FETCH_ASSOC);
$evaluaciones = $pdo->query('SELECT id_evaluacion, nombre AS evaluacion FROM evaluaciones ORDER BY id_evaluacion')->fetchAll(PDO::FETCH_ASSOC);

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

function parse_calificacion(string $raw): array|false|null {
  $raw = trim($raw);
  if ($raw === '') {
    return null;
  }

  if (preg_match('/^([A-Za-z]{1,10})-([0-9]+(?:[\.,][0-9]+)?)$/', $raw, $m)) {
    return [
      'calificacion_original' => $raw,
      'prefijo' => strtoupper($m[1]),
      'nota' => (float) str_replace(',', '.', $m[2]),
    ];
  }

  if (preg_match('/^([0-9]+(?:[\.,][0-9]+)?)-([A-Za-z]{1,10})$/', $raw, $m)) {
    return [
      'calificacion_original' => $raw,
      'prefijo' => strtoupper($m[2]),
      'nota' => (float) str_replace(',', '.', $m[1]),
    ];
  }

  if (preg_match('/^[0-9]+(?:[\.,][0-9]+)?$/', $raw)) {
    return [
      'calificacion_original' => $raw,
      'prefijo' => null,
      'nota' => (float) str_replace(',', '.', $raw),
    ];
  }

  if (preg_match('/^[A-Za-z0-9]{1,20}$/', $raw)) {
    return [
      'calificacion_original' => $raw,
      'prefijo' => strtoupper($raw),
      'nota' => null,
    ];
  }

  return false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if ($selected['id_curso_escolar'] <= 0 || $selected['id_grupo'] <= 0 || $selected['id_evaluacion'] <= 0) {
    $errors[] = 'Debes seleccionar curso escolar, grupo y evaluación.';
  }

  if (!isset($_FILES['csv_calificaciones']) || !is_uploaded_file($_FILES['csv_calificaciones']['tmp_name'])) {
    $errors[] = 'Debes seleccionar un archivo CSV válido.';
  }

  if ($errors === []) {
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

    $evaluacion_valida_stmt = $pdo->prepare('SELECT 1 FROM evaluaciones WHERE id_evaluacion = :id_evaluacion LIMIT 1');
    $evaluacion_valida_stmt->execute([
      'id_evaluacion' => $selected['id_evaluacion'],
    ]);

    if (!$evaluacion_valida_stmt->fetchColumn()) {
      $errors[] = 'La evaluación seleccionada no es válida.';
    }
  }

  if ($errors === []) {
    $handle = fopen($_FILES['csv_calificaciones']['tmp_name'], 'rb');
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
        $module_codes = [];
        for ($i = 1, $total = count($header); $i < $total; $i++) {
          $code = mb_strtoupper(normalize_csv_text((string) $header[$i]), 'UTF-8');
          if ($code !== '') {
            $module_codes[$i] = $code;
          }
        }

        if ($module_codes === []) {
          $errors[] = 'No se han encontrado códigos de módulo en la cabecera del CSV.';
        } else {
          $unique_codes = array_values(array_unique(array_values($module_codes)));
          $placeholders = implode(',', array_fill(0, count($unique_codes), '?'));
          $sql_modules = 'SELECT id_modulo, id_curso, codigo FROM modulos WHERE id_ciclo = ? AND UPPER(codigo) IN (' . $placeholders . ')';
          $params_modules = array_merge([$selected['id_ciclo']], $unique_codes);
          $stmt_modules = $pdo->prepare($sql_modules);
          $stmt_modules->execute($params_modules);

          $modules_by_code = [];
          while ($module_row = $stmt_modules->fetch(PDO::FETCH_ASSOC)) {
            $code = mb_strtoupper((string) $module_row['codigo'], 'UTF-8');
            if (!isset($modules_by_code[$code])) {
              $modules_by_code[$code] = [
                'id_modulo' => (int) $module_row['id_modulo'],
                'id_curso' => (int) $module_row['id_curso'],
              ];
            }
          }

          foreach ($unique_codes as $code) {
            if (!isset($modules_by_code[$code])) {
              $result['modulos_no_encontrados'][] = $code;
            }
          }

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

          $upsert_stmt = $pdo->prepare(
            'INSERT INTO calificaciones
              (id_alumno, id_curso_escolar, id_ciclo, id_curso, id_grupo, id_modulo, id_evaluacion, calificacion_original, prefijo, nota)
             VALUES
              (:id_alumno, :id_curso_escolar, :id_ciclo, :id_curso, :id_grupo, :id_modulo, :id_evaluacion, :calificacion_original, :prefijo, :nota)
             ON DUPLICATE KEY UPDATE
              calificacion_original = VALUES(calificacion_original),
              prefijo = VALUES(prefijo),
              nota = VALUES(nota),
              fecha_importacion = CURRENT_TIMESTAMP'
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

              $id_alumno = (int) $student_index[$student_key];

              foreach ($module_codes as $column_index => $module_code) {
                if (!isset($modules_by_code[$module_code])) {
                  continue;
                }

                $raw_grade = normalize_csv_text((string) ($row[$column_index] ?? ''));
                if ($raw_grade === '') {
                  continue;
                }

                $parsed = parse_calificacion($raw_grade);
                if ($parsed === false) {
                  $result['errores'][] = 'Línea ' . $line . ' (' . $name_csv . ', módulo ' . $module_code . '): formato de calificación no válido (' . $raw_grade . ').';
                  continue;
                }

                if ($parsed === null) {
                  continue;
                }

                $upsert_stmt->execute([
                  'id_alumno' => $id_alumno,
                  'id_curso_escolar' => $selected['id_curso_escolar'],
                  'id_ciclo' => $selected['id_ciclo'],
                  'id_curso' => $modules_by_code[$module_code]['id_curso'],
                  'id_grupo' => $selected['id_grupo'],
                  'id_modulo' => (int) $modules_by_code[$module_code]['id_modulo'],
                  'id_evaluacion' => $selected['id_evaluacion'],
                  'calificacion_original' => $parsed['calificacion_original'],
                  'prefijo' => $parsed['prefijo'],
                  'nota' => $parsed['nota'],
                ]);

                $result['importadas']++;
              }
            }

            $pdo->commit();
          } catch (Throwable $e) {
            $pdo->rollBack();
            $errors[] = 'Error durante la importación: ' . $e->getMessage();
          }

          $result['alumnos_no_encontrados'] = array_values(array_unique($result['alumnos_no_encontrados']));
          $result['modulos_no_encontrados'] = array_values(array_unique($result['modulos_no_encontrados']));
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
          <h1>Importar calificaciones</h1>
          <p class="subheading">Selecciona el contexto académico y carga el CSV de calificaciones.</p>
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
            <p>Resumen del proceso de carga del CSV de calificaciones.</p>
          </div>
          <div class="import-counts">
            <div class="import-count import-count--ok">
              <span class="import-count-value"><?php echo (int) $result['importadas']; ?></span>
              <span class="import-count-label">Calificaciones importadas</span>
            </div>
            <div class="import-count <?php echo count($result['alumnos_no_encontrados']) > 0 ? 'import-count--warn' : ''; ?>">
              <span class="import-count-value"><?php echo count($result['alumnos_no_encontrados']); ?></span>
              <span class="import-count-label">Alumnos no encontrados</span>
            </div>
            <div class="import-count <?php echo count($result['modulos_no_encontrados']) > 0 ? 'import-count--warn' : ''; ?>">
              <span class="import-count-value"><?php echo count($result['modulos_no_encontrados']); ?></span>
              <span class="import-count-label">Módulos no encontrados</span>
            </div>
            <div class="import-count <?php echo count($result['errores']) > 0 ? 'import-count--error' : ''; ?>">
              <span class="import-count-value"><?php echo count($result['errores']); ?></span>
              <span class="import-count-label">Errores de formato</span>
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
              <p class="import-list-hint">El nombre del CSV no coincide con ningún alumno del grupo. Comprueba tildes, espacios y el orden de apellidos.</p>
            </div>
          <?php endif; ?>

          <?php if ($result['modulos_no_encontrados'] !== []): ?>
            <div class="import-list-section">
              <div class="import-list-header import-list-header--warn">Módulos no encontrados (<?php echo count($result['modulos_no_encontrados']); ?>)</div>
              <ul class="import-list-items">
                <?php foreach ($result['modulos_no_encontrados'] as $item): ?>
                  <li><?php echo htmlspecialchars($item, ENT_QUOTES, 'UTF-8'); ?></li>
                <?php endforeach; ?>
              </ul>
              <p class="import-list-hint">El código de la cabecera del CSV no coincide con ningún módulo del ciclo seleccionado. El código debe ser exactamente igual al registrado en la base de datos (mayúsculas y sin espacios extra).</p>
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
              <p class="import-list-hint">Las calificaciones deben ser numéricas (ej: <code>7.5</code>) o con prefijo (ej: <code>Teoría-7.5</code>). Las celdas vacías se ignoran.</p>
            </div>
          <?php endif; ?>
        </section>
      <?php endif; ?>

      <form method="post" enctype="multipart/form-data" class="panel entity-form">
        <section class="entity-section">
          <div class="panel-header">
            <h3>Contexto académico</h3>
            <p>Selecciona curso escolar, grupo y evaluación para esta importación.</p>
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
              Evaluación
              <select name="id_evaluacion" required>
                <option value="">Selecciona evaluación</option>
                <?php foreach ($evaluaciones as $item): ?>
                  <option value="<?php echo (int) $item['id_evaluacion']; ?>" <?php echo (int) $item['id_evaluacion'] === $selected['id_evaluacion'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars((string) $item['evaluacion'], ENT_QUOTES, 'UTF-8'); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </label>
          </div>

          <div class="entity-grid">
            <div class="file-field">
              <span class="file-field-label">Archivo CSV</span>
              <div class="file-field-control">
                <label class="file-field-btn" for="csvCalificacionesInput">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                  Seleccionar archivo
                </label>
                <span class="file-field-name" id="csvCalificacionesName">Ningún archivo seleccionado</span>
                <input type="file" id="csvCalificacionesInput" name="csv_calificaciones" accept=".csv,text/csv" required class="file-field-input">
              </div>
            </div>
          </div>
          <script>
            (function () {
              var inp = document.getElementById('csvCalificacionesInput');
              var nm = document.getElementById('csvCalificacionesName');
              if (inp && nm) {
                inp.addEventListener('change', function () {
                  nm.textContent = this.files[0] ? this.files[0].name : 'Ningún archivo seleccionado';
                });
              }
            })();
          </script>

          <div class="form-actions">
            <button type="submit" class="primary-button">Importar calificaciones</button>
          </div>
        </section>
      </form>
    </main>
  </div>
</body>
</html>
