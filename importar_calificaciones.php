<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pdo = db();

$page_title = 'Importar calificaciones | Gestor de Alumnos';
$active_page = 'configuracion';

$messages = [];
$errors = [];
$result = [
  'importadas' => 0,
  'alumnos_no_encontrados' => [],
  'modulos_no_encontrados' => [],
  'errores' => [],
];

$selected = [
  'id_curso_escolar' => (int) ($_POST['id_curso_escolar'] ?? 0),
  'id_ciclo' => (int) ($_POST['id_ciclo'] ?? 0),
  'id_curso' => (int) ($_POST['id_curso'] ?? 0),
  'id_grupo' => (int) ($_POST['id_grupo'] ?? 0),
];

$cursos_escolares = $pdo->query('SELECT id_curso_escolar, curso_escolar FROM cursos_escolares ORDER BY activo DESC, id_curso_escolar DESC')->fetchAll(PDO::FETCH_ASSOC);
$ciclos = $pdo->query('SELECT id_ciclo, abreviatura, ciclo FROM ciclos ORDER BY abreviatura, ciclo')->fetchAll(PDO::FETCH_ASSOC);
$cursos = $pdo->query('SELECT id_curso, curso FROM cursos ORDER BY id_curso')->fetchAll(PDO::FETCH_ASSOC);
$grupos = $pdo->query('SELECT id_grupo, id_ciclo, id_curso, grupo FROM grupos ORDER BY grupo')->fetchAll(PDO::FETCH_ASSOC);

function normalize_name(string $value): string {
  $value = str_replace("\xc2\xa0", ' ', $value);
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

function parse_calificacion(string $raw): ?array {
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

  if (preg_match('/^[0-9]+(?:[\.,][0-9]+)?$/', $raw)) {
    return [
      'calificacion_original' => $raw,
      'prefijo' => null,
      'nota' => (float) str_replace(',', '.', $raw),
    ];
  }

  return false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if ($selected['id_curso_escolar'] <= 0 || $selected['id_ciclo'] <= 0 || $selected['id_curso'] <= 0 || $selected['id_grupo'] <= 0) {
    $errors[] = 'Debes seleccionar curso escolar, ciclo, curso y grupo.';
  }

  if (!isset($_FILES['csv_calificaciones']) || !is_uploaded_file($_FILES['csv_calificaciones']['tmp_name'])) {
    $errors[] = 'Debes seleccionar un archivo CSV válido.';
  }

  if ($errors === []) {
    $grupo_valido_stmt = $pdo->prepare('SELECT 1 FROM grupos WHERE id_grupo = :id_grupo AND id_ciclo = :id_ciclo AND id_curso = :id_curso LIMIT 1');
    $grupo_valido_stmt->execute([
      'id_grupo' => $selected['id_grupo'],
      'id_ciclo' => $selected['id_ciclo'],
      'id_curso' => $selected['id_curso'],
    ]);

    if (!$grupo_valido_stmt->fetchColumn()) {
      $errors[] = 'El grupo seleccionado no pertenece al ciclo y curso indicados.';
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
        $header = array_map(static fn($v): string => trim((string) $v), $header);
        if (mb_strtolower((string) $header[0], 'UTF-8') !== mb_strtolower('Alumno/a', 'UTF-8')) {
          $errors[] = 'La primera columna de la cabecera debe ser "Alumno/a".';
        }
      }

      if ($errors === []) {
        $module_codes = [];
        for ($i = 1, $total = count($header); $i < $total; $i++) {
          $code = trim((string) $header[$i]);
          if ($code !== '') {
            $module_codes[$i] = $code;
          }
        }

        if ($module_codes === []) {
          $errors[] = 'No se han encontrado códigos de módulo en la cabecera del CSV.';
        } else {
          $unique_codes = array_values(array_unique(array_values($module_codes)));
          $placeholders = implode(',', array_fill(0, count($unique_codes), '?'));
          $sql_modules = 'SELECT id_modulo, codigo FROM modulos WHERE id_ciclo = ? AND id_curso = ? AND codigo IN (' . $placeholders . ')';
          $params_modules = array_merge([$selected['id_ciclo'], $selected['id_curso']], $unique_codes);
          $stmt_modules = $pdo->prepare($sql_modules);
          $stmt_modules->execute($params_modules);

          $modules_by_code = [];
          while ($module_row = $stmt_modules->fetch(PDO::FETCH_ASSOC)) {
            $code = (string) $module_row['codigo'];
            if (!isset($modules_by_code[$code])) {
              $modules_by_code[$code] = (int) $module_row['id_modulo'];
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
              (id_alumno, id_curso_escolar, id_ciclo, id_curso, id_grupo, id_modulo, calificacion_original, prefijo, nota)
             VALUES
              (:id_alumno, :id_curso_escolar, :id_ciclo, :id_curso, :id_grupo, :id_modulo, :calificacion_original, :prefijo, :nota)
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
              $name_csv = trim((string) ($row[0] ?? ''));
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

                $raw_grade = trim((string) ($row[$column_index] ?? ''));
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
                  'id_curso' => $selected['id_curso'],
                  'id_grupo' => $selected['id_grupo'],
                  'id_modulo' => (int) $modules_by_code[$module_code],
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
            <h3>Errores</h3>
          </div>
          <ul class="form-errors">
            <?php foreach ($errors as $error): ?>
              <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
            <?php endforeach; ?>
          </ul>
        </section>
      <?php endif; ?>

      <?php if ($messages !== []): ?>
        <section class="panel">
          <div class="panel-header">
            <h3>Resultado</h3>
          </div>
          <div class="panel-grid">
            <?php foreach ($messages as $message): ?>
              <p><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endforeach; ?>
            <p>Registros importados: <?php echo (int) $result['importadas']; ?></p>
            <p>Alumnos no encontrados: <?php echo count($result['alumnos_no_encontrados']); ?></p>
            <p>Módulos no encontrados: <?php echo count($result['modulos_no_encontrados']); ?></p>
            <p>Errores de proceso: <?php echo count($result['errores']); ?></p>
          </div>

          <?php if ($result['alumnos_no_encontrados'] !== []): ?>
            <div class="panel-header">
              <h3>Alumnos no encontrados</h3>
            </div>
            <ul class="form-errors">
              <?php foreach ($result['alumnos_no_encontrados'] as $item): ?>
                <li><?php echo htmlspecialchars($item, ENT_QUOTES, 'UTF-8'); ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>

          <?php if ($result['modulos_no_encontrados'] !== []): ?>
            <div class="panel-header">
              <h3>Módulos no encontrados</h3>
            </div>
            <ul class="form-errors">
              <?php foreach ($result['modulos_no_encontrados'] as $item): ?>
                <li><?php echo htmlspecialchars($item, ENT_QUOTES, 'UTF-8'); ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>

          <?php if ($result['errores'] !== []): ?>
            <div class="panel-header">
              <h3>Errores concretos</h3>
            </div>
            <ul class="form-errors">
              <?php foreach ($result['errores'] as $item): ?>
                <li><?php echo htmlspecialchars($item, ENT_QUOTES, 'UTF-8'); ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </section>
      <?php endif; ?>

      <form method="post" enctype="multipart/form-data" class="panel entity-form">
        <section class="entity-section">
          <div class="panel-header">
            <h3>Contexto académico</h3>
            <p>Selecciona curso escolar, ciclo, curso y grupo para esta importación.</p>
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
              Ciclo
              <select name="id_ciclo" required>
                <option value="">Selecciona ciclo</option>
                <?php foreach ($ciclos as $item): ?>
                  <?php $label = trim((string) $item['abreviatura']) !== '' ? ((string) $item['abreviatura'] . ' - ' . (string) $item['ciclo']) : (string) $item['ciclo']; ?>
                  <option value="<?php echo (int) $item['id_ciclo']; ?>" <?php echo (int) $item['id_ciclo'] === $selected['id_ciclo'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </label>

            <label>
              Curso
              <select name="id_curso" required>
                <option value="">Selecciona curso</option>
                <?php foreach ($cursos as $item): ?>
                  <option value="<?php echo (int) $item['id_curso']; ?>" <?php echo (int) $item['id_curso'] === $selected['id_curso'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars((string) $item['curso'], ENT_QUOTES, 'UTF-8'); ?>
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
          </div>

          <div class="entity-grid">
            <label>
              Archivo CSV
              <input type="file" name="csv_calificaciones" accept=".csv,text/csv" required>
            </label>
          </div>

          <div class="form-actions">
            <button type="submit" class="button-primary">Importar calificaciones</button>
          </div>
        </section>
      </form>
    </main>
  </div>
</body>
</html>
