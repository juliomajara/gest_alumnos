<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

if (!defined('PRACTICAS_RAS_DEBUG')) {
  define('PRACTICAS_RAS_DEBUG', false);
}

$pdo = db();
$page_title = 'Porcentaje RA/CE en empresa | Gestor de Alumnos';
$active_page = 'configuracion';

function format_module_name(array $module): string {
  $main = trim((string) ($module['materia_propia'] ?? ''));
  if ($main === '') {
    $main = trim((string) ($module['materia_general'] ?? ''));
  }
  if ($main === '') {
    $main = 'Módulo sin nombre';
  }

  $code = trim((string) ($module['abreviatura'] ?? ''));
  if ($code !== '') {
    return $code . ' · ' . $main;
  }

  return $main;
}

function normalize_percentage_value(mixed $value): ?float {
  if ($value === null) {
    return null;
  }

  if (is_string($value)) {
    $value = str_replace(',', '.', trim($value));
    if ($value === '') {
      return null;
    }
  }

  if (!is_numeric($value)) {
    return null;
  }

  return (float) $value;
}

function find_column(array $columns, array $candidates): ?string {
  foreach ($candidates as $candidate) {
    if (in_array($candidate, $columns, true)) {
      return $candidate;
    }
  }

  return null;
}

function format_ra_label(mixed $number, int $raId): string {
  $raw = trim((string) $number);
  if ($raw === '') {
    return 'RA' . $raId . '.';
  }

  if (preg_match('/(\d+)/', $raw, $matches)) {
    return 'RA' . $matches[1] . '.';
  }

  return 'RA' . $raId . '.';
}

function should_debug_practicas_ras(): bool {
  if (PRACTICAS_RAS_DEBUG) {
    return true;
  }

  $debug_value = $_GET['debug_practicas_ras'] ?? $_POST['debug_practicas_ras'] ?? '';
  return in_array((string) $debug_value, ['1', 'true', 'on', 'yes'], true);
}

function has_column(PDO $pdo, string $table, string $column): bool {
  $stmt = $pdo->prepare(
    'SELECT COUNT(*)
     FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name AND COLUMN_NAME = :column_name'
  );
  $stmt->execute([
    'table_name' => $table,
    'column_name' => $column,
  ]);

  return (int) $stmt->fetchColumn() > 0;
}

function has_index(PDO $pdo, string $table, string $index): bool {
  $stmt = $pdo->prepare(
    'SELECT COUNT(*)
     FROM INFORMATION_SCHEMA.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name AND INDEX_NAME = :index_name'
  );
  $stmt->execute([
    'table_name' => $table,
    'index_name' => $index,
  ]);

  return (int) $stmt->fetchColumn() > 0;
}

function find_missing_columns(array $columns, array $required): array {
  $missing = [];
  foreach ($required as $required_column) {
    if (!in_array($required_column, $columns, true)) {
      $missing[] = $required_column;
    }
  }

  return $missing;
}

$courses = [];
$course_map = [];
$can_filter_by_select_course = false;

try {
  $courses_stmt = $pdo->query('SELECT id_curso_escolar, curso_escolar, activo FROM cursos_escolares ORDER BY activo DESC, id_curso_escolar DESC');
  $courses = $courses_stmt->fetchAll();

  foreach ($courses as $course) {
    $id = (string) (int) $course['id_curso_escolar'];
    $course_map[$id] = (string) $course['curso_escolar'];
  }

  $can_filter_by_select_course = !empty($courses);
} catch (Throwable $exception) {
  $courses = [];
  $course_map = [];
  $can_filter_by_select_course = false;
}

$available_cycles = ['SMR', 'ASIR', 'DAW', 'DAM'];
$debug_mode = should_debug_practicas_ras();
$debug_details = [];
$active_database = null;

$selected_course_id = trim((string) ($_GET['curso'] ?? $_POST['curso'] ?? ''));
$selected_course_text = trim((string) ($_GET['curso_texto'] ?? $_POST['curso_texto'] ?? ''));
$selected_cycle = trim((string) ($_GET['ciclo'] ?? $_POST['ciclo'] ?? ''));
if (!in_array($selected_cycle, $available_cycles, true)) {
  $selected_cycle = '';
}

$selected_course_label = '';
if ($can_filter_by_select_course) {
  if ($selected_course_id === '' && count($courses) > 0) {
    $active_course = null;
    foreach ($courses as $course) {
      if ((int) ($course['activo'] ?? 0) === 1) {
        $active_course = $course;
        break;
      }
    }

    $selected_course_id = (string) (int) (($active_course ?? $courses[0])['id_curso_escolar'] ?? 0);
  }
  if ($selected_course_id !== '' && isset($course_map[$selected_course_id])) {
    $selected_course_label = $course_map[$selected_course_id];
  }
} else {
  $selected_course_label = $selected_course_text;
}

$required_practicas_columns = ['curso_escolar', 'ciclo', 'id_modulo', 'id_ra', 'porcentaje'];
$practicas_columns = [];
$practicas_table_ready = false;
$missing_practicas_columns = [];
$practicas_table_exists = false;
$schema_error_message = null;

try {
  $active_database = $pdo->query('SELECT DATABASE()')->fetchColumn();
  $active_database = $active_database === false ? '' : (string) $active_database;

  $table_exists_stmt = $pdo->prepare(
    'SELECT COUNT(*)
     FROM INFORMATION_SCHEMA.TABLES
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name'
  );
  $table_exists_stmt->execute(['table_name' => 'practicas_ras']);
  $practicas_table_exists = (int) $table_exists_stmt->fetchColumn() > 0;

  if (!$practicas_table_exists) {
    $pdo->exec(
      'CREATE TABLE IF NOT EXISTS practicas_ras (
        id_practica_ra INT UNSIGNED NOT NULL AUTO_INCREMENT,
        curso_escolar VARCHAR(20) NOT NULL,
        ciclo VARCHAR(20) NOT NULL,
        id_modulo INT UNSIGNED NOT NULL,
        id_ra INT UNSIGNED NOT NULL,
        porcentaje DECIMAL(5,2) NOT NULL,
        PRIMARY KEY (id_practica_ra),
        UNIQUE KEY uq_practicas_ras_context (curso_escolar, ciclo, id_modulo, id_ra),
        KEY idx_practicas_ras_curso_ciclo (curso_escolar, ciclo),
        KEY idx_practicas_ras_id_ra (id_ra)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $practicas_table_exists = true;
  }

  if ($practicas_table_exists) {
    if (!has_column($pdo, 'practicas_ras', 'curso_escolar')) {
      $pdo->exec('ALTER TABLE practicas_ras ADD COLUMN curso_escolar VARCHAR(20) NULL AFTER id_practica_ra');
    }
    if (!has_column($pdo, 'practicas_ras', 'ciclo')) {
      $pdo->exec('ALTER TABLE practicas_ras ADD COLUMN ciclo VARCHAR(20) NULL AFTER curso_escolar');
    }
    if (!has_column($pdo, 'practicas_ras', 'id_modulo')) {
      $pdo->exec('ALTER TABLE practicas_ras ADD COLUMN id_modulo INT UNSIGNED NULL AFTER ciclo');
    }
    if (!has_column($pdo, 'practicas_ras', 'id_ra')) {
      $pdo->exec('ALTER TABLE practicas_ras ADD COLUMN id_ra INT UNSIGNED NULL AFTER id_modulo');
    }
    if (!has_column($pdo, 'practicas_ras', 'porcentaje')) {
      $pdo->exec('ALTER TABLE practicas_ras ADD COLUMN porcentaje DECIMAL(5,2) NULL AFTER id_ra');
    }

    if (has_column($pdo, 'practicas_ras', 'id_curso_escolar') && has_column($pdo, 'practicas_ras', 'curso_escolar')) {
      $pdo->exec(
        'UPDATE practicas_ras pr
         LEFT JOIN cursos_escolares ce ON ce.id_curso_escolar = pr.id_curso_escolar
         SET pr.curso_escolar = COALESCE(pr.curso_escolar, ce.curso_escolar)
         WHERE pr.curso_escolar IS NULL OR pr.curso_escolar = ""'
      );
    }

    if (has_column($pdo, 'practicas_ras', 'id_ciclo') && has_column($pdo, 'practicas_ras', 'ciclo')) {
      $pdo->exec(
        'UPDATE practicas_ras pr
         LEFT JOIN ciclos c ON c.id_ciclo = pr.id_ciclo
         SET pr.ciclo = COALESCE(pr.ciclo, c.abreviatura)
         WHERE pr.ciclo IS NULL OR pr.ciclo = ""'
      );
    }

    if (!has_index($pdo, 'practicas_ras', 'idx_practicas_ras_curso_ciclo')) {
      $pdo->exec('ALTER TABLE practicas_ras ADD INDEX idx_practicas_ras_curso_ciclo (curso_escolar, ciclo)');
    }
    if (!has_index($pdo, 'practicas_ras', 'idx_practicas_ras_id_ra')) {
      $pdo->exec('ALTER TABLE practicas_ras ADD INDEX idx_practicas_ras_id_ra (id_ra)');
    }
    if (!has_index($pdo, 'practicas_ras', 'uq_practicas_ras_context')) {
      $pdo->exec('ALTER TABLE practicas_ras ADD UNIQUE INDEX uq_practicas_ras_context (curso_escolar, ciclo, id_modulo, id_ra)');
    }
  }

  if ($practicas_table_exists) {
    $columns_stmt = $pdo->query('SHOW COLUMNS FROM practicas_ras');
    foreach ($columns_stmt->fetchAll() as $column) {
      $practicas_columns[] = (string) $column['Field'];
    }
  }

  $missing_practicas_columns = find_missing_columns($practicas_columns, $required_practicas_columns);
  $practicas_table_ready = $practicas_table_exists && !$missing_practicas_columns;
} catch (Throwable $exception) {
  $practicas_table_ready = false;
  $schema_error_message = $exception->getMessage();
  if ($debug_mode && $exception instanceof PDOException) {
    $debug_details[] = 'SQLSTATE: ' . ($exception->errorInfo[0] ?? $exception->getCode());
    $debug_details[] = 'Excepción SQL: ' . $exception->getMessage();
  }
}

$course_storage_value = $selected_course_label;
$cycle_storage_value = $selected_cycle;

$messages = [];
$errors = [];

$filters_ready = $selected_cycle !== '' && (($can_filter_by_select_course && $selected_course_id !== '' && $selected_course_label !== '') || (!$can_filter_by_select_course && $selected_course_label !== ''));

$modules = [];
$ras_by_module = [];
$saved_percentages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'guardar') {
  if (!$practicas_table_ready) {
    if (!$practicas_table_exists) {
      $errors[] = 'La tabla no existe en la BD ' . ($active_database !== '' ? $active_database : '(sin base activa)') . '.';
    } elseif ($missing_practicas_columns) {
      $errors[] = 'La tabla practicas_ras existe, pero faltan columnas: ' . implode(', ', $missing_practicas_columns) . '.';
    } elseif ($schema_error_message !== null) {
      $errors[] = 'No se ha podido validar la estructura de practicas_ras: ' . $schema_error_message;
    } else {
      $errors[] = 'La tabla practicas_ras no está lista para guardar por un problema de estructura no identificado.';
    }
  }

  if (!$filters_ready) {
    $errors[] = 'Selecciona curso escolar y ciclo antes de guardar.';
  }

  if (!$errors) {
    $raw_percentages = is_array($_POST['porcentajes'] ?? null) ? $_POST['porcentajes'] : [];

    $percentages_to_save = [];
    foreach ($raw_percentages as $ra_id => $value) {
      $ra_id_int = (int) $ra_id;
      if ($ra_id_int <= 0) {
        continue;
      }

      $normalized = normalize_percentage_value($value);
      if ($normalized === null) {
        continue;
      }

      if ($normalized < 0 || $normalized > 100) {
        $errors[] = 'El porcentaje del RA ' . $ra_id_int . ' debe estar entre 0 y 100.';
        continue;
      }

      $percentages_to_save[$ra_id_int] = $normalized;
    }

    if (!$errors) {
      try {
        $pdo->beginTransaction();

        $delete_sql = sprintf(
          'DELETE FROM practicas_ras WHERE %s = :curso AND %s = :ciclo',
          'curso_escolar',
          'ciclo'
        );
        $delete_stmt = $pdo->prepare($delete_sql);
        $delete_stmt->execute([
          'curso' => $course_storage_value,
          'ciclo' => $cycle_storage_value,
        ]);

        if ($percentages_to_save) {
          $insert_sql = sprintf(
            'INSERT INTO practicas_ras (%s, %s, %s, %s, %s) VALUES (:curso, :ciclo, :id_modulo, :id_ra, :porcentaje)',
            'curso_escolar',
            'ciclo',
            'id_modulo',
            'id_ra',
            'porcentaje'
          );
          $insert_stmt = $pdo->prepare($insert_sql);

          foreach ($percentages_to_save as $ra_id => $percentage) {
            $module_lookup_stmt = $pdo->prepare('SELECT id_modulo FROM resultados_aprendizaje WHERE id_ra = :id_ra LIMIT 1');
            $module_lookup_stmt->execute(['id_ra' => $ra_id]);
            $module_id = $module_lookup_stmt->fetchColumn();
            if ($module_id === false) {
              continue;
            }

            $insert_stmt->execute([
              'curso' => $course_storage_value,
              'ciclo' => $cycle_storage_value,
              'id_modulo' => (int) $module_id,
              'id_ra' => $ra_id,
              'porcentaje' => $percentage,
            ]);
          }
        }

        $pdo->commit();
        $messages[] = 'Porcentajes guardados correctamente.';
      } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
          $pdo->rollBack();
        }
        if ($exception instanceof PDOException) {
          $errors[] = 'No se han podido guardar los porcentajes. SQLSTATE ' . ($exception->errorInfo[0] ?? $exception->getCode()) . ': ' . $exception->getMessage();
          if ($debug_mode) {
            $debug_details[] = 'Error guardando en BD ' . ($active_database !== '' ? $active_database : '(sin base activa)') . '.';
          }
        } else {
          $errors[] = 'No se han podido guardar los porcentajes: ' . $exception->getMessage();
        }
      }
    }
  }
}

if ($filters_ready) {
  $modules_stmt = $pdo->prepare(
    'SELECT
      m.id_modulo,
      m.abreviatura,
      m.materia_general,
      m.materia_propia,
      COUNT(ra.id_ra) AS total_ras
     FROM modulos m
     INNER JOIN ciclos c ON c.id_ciclo = m.id_ciclo
     INNER JOIN cursos cu ON cu.id_curso = m.id_curso
     INNER JOIN resultados_aprendizaje ra ON ra.id_modulo = m.id_modulo
     WHERE c.abreviatura = :ciclo
       AND cu.curso = 2
     GROUP BY m.id_modulo, m.abreviatura, m.materia_general, m.materia_propia
     ORDER BY m.abreviatura, m.materia_propia, m.materia_general, m.id_modulo'
  );
  $modules_stmt->execute(['ciclo' => $selected_cycle]);
  $modules = $modules_stmt->fetchAll();

  if ($modules) {
    $module_ids = array_map(static fn (array $row): int => (int) $row['id_modulo'], $modules);
    $ra_placeholders = implode(',', array_fill(0, count($module_ids), '?'));

    $ras_stmt = $pdo->prepare(
      'SELECT id_ra, id_modulo, numero, descripcion
       FROM resultados_aprendizaje
       WHERE id_modulo IN (' . $ra_placeholders . ')
       ORDER BY id_modulo, numero, id_ra'
    );
    $ras_stmt->execute($module_ids);
    $ras = $ras_stmt->fetchAll();

    foreach ($ras as $ra) {
      $module_id = (int) $ra['id_modulo'];
      if (!isset($ras_by_module[$module_id])) {
        $ras_by_module[$module_id] = [];
      }
      $ras_by_module[$module_id][] = $ra;
    }

    if ($practicas_table_ready && $course_storage_value !== '' && $cycle_storage_value !== null) {
      $saved_stmt = $pdo->prepare(
        sprintf(
          'SELECT %s AS id_ra, %s AS porcentaje
           FROM practicas_ras
           WHERE %s = :curso AND %s = :ciclo',
          'id_ra',
          'porcentaje',
          'curso_escolar',
          'ciclo'
        )
      );
      $saved_stmt->execute([
        'curso' => $course_storage_value,
        'ciclo' => $cycle_storage_value,
      ]);

      foreach ($saved_stmt->fetchAll() as $saved) {
        $saved_percentages[(int) $saved['id_ra']] = (string) $saved['porcentaje'];
      }
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

    <main class="content practicas-ras">
      <header class="header">
        <div>
          <p class="eyebrow">Configuración de prácticas</p>
          <h1>Porcentaje RA/CE en empresa</h1>
          <p class="subheading">Define el porcentaje cedido a la empresa por cada resultado de aprendizaje en un curso y ciclo concretos.</p>
        </div>
      </header>

      <section class="panel">
        <div class="panel-header">
          <h3>Filtros</h3>
          <p>Selecciona curso escolar y ciclo para cargar módulos y resultados de aprendizaje.</p>
        </div>

        <form method="get" class="entity-form panel-grid">
          <div class="entity-grid entity-grid--3">
            <?php if ($can_filter_by_select_course): ?>
              <label>
                Curso escolar
                <select name="curso" required>
                  <?php foreach ($courses as $course): ?>
                    <?php $course_value = (string) (int) $course['id_curso_escolar']; ?>
                    <option value="<?php echo htmlspecialchars($course_value, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $selected_course_id === $course_value ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars((string) $course['curso_escolar'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </label>
            <?php else: ?>
              <label>
                Curso escolar
                <input type="text" name="curso_texto" value="<?php echo htmlspecialchars($selected_course_text, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Ej. 2024/2025" required>
              </label>
            <?php endif; ?>

            <label>
              Ciclo
              <select name="ciclo" required>
                <option value="">Selecciona ciclo</option>
                <?php foreach ($available_cycles as $cycle): ?>
                  <option value="<?php echo htmlspecialchars($cycle, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $selected_cycle === $cycle ? 'selected' : ''; ?>><?php echo htmlspecialchars($cycle, ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
              </select>
            </label>

            <div class="practicas-ras-filter-actions">
              <button type="submit" class="primary-button">Cargar</button>
            </div>
          </div>
        </form>
      </section>

      <?php if ($errors): ?>
        <section class="panel">
          <div class="panel-header">
            <h3>Revisa los datos</h3>
          </div>
          <ul class="form-errors">
            <?php foreach ($errors as $error): ?>
              <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
            <?php endforeach; ?>
            <?php if ($debug_mode): ?>
              <li>BD activa: <?php echo htmlspecialchars($active_database !== '' ? $active_database : '(sin base activa)', ENT_QUOTES, 'UTF-8'); ?></li>
              <?php foreach ($debug_details as $detail): ?>
                <li><?php echo htmlspecialchars($detail, ENT_QUOTES, 'UTF-8'); ?></li>
              <?php endforeach; ?>
            <?php endif; ?>
          </ul>
        </section>
      <?php endif; ?>

      <?php if ($messages): ?>
        <section class="panel">
          <div class="panel-header">
            <h3><?php echo htmlspecialchars($messages[0], ENT_QUOTES, 'UTF-8'); ?></h3>
          </div>
        </section>
      <?php endif; ?>

      <?php if ($filters_ready): ?>
        <form method="post" class="panel entity-form panel-grid">
          <div class="panel-header">
            <h3>Distribución por módulos y resultados de aprendizaje</h3>
            <p>Revisa cada módulo y asigna el porcentaje cedido a la empresa para cada resultado de aprendizaje.</p>
          </div>

          <input type="hidden" name="action" value="guardar">
          <input type="hidden" name="ciclo" value="<?php echo htmlspecialchars($selected_cycle, ENT_QUOTES, 'UTF-8'); ?>">
          <?php if ($can_filter_by_select_course): ?>
            <input type="hidden" name="curso" value="<?php echo htmlspecialchars($selected_course_id, ENT_QUOTES, 'UTF-8'); ?>">
          <?php else: ?>
            <input type="hidden" name="curso_texto" value="<?php echo htmlspecialchars($selected_course_text, ENT_QUOTES, 'UTF-8'); ?>">
          <?php endif; ?>

          <?php if (!$modules): ?>
            <p>No hay módulos para el ciclo seleccionado.</p>
          <?php else: ?>
            <div class="practicas-ras-tree">
              <?php foreach ($modules as $module): ?>
                <?php
                  $module_id = (int) $module['id_modulo'];
                  $module_ras = $ras_by_module[$module_id] ?? [];
                ?>
                <section class="practicas-ras-module panel panel-grid">
                  <div class="panel-header">
                    <h3><?php echo htmlspecialchars(format_module_name($module) . ' (' . count($module_ras) . ' RAs)', ENT_QUOTES, 'UTF-8'); ?></h3>
                  </div>

                  <?php if (!$module_ras): ?>
                    <p>Este módulo no tiene resultados de aprendizaje registrados.</p>
                  <?php else: ?>
                    <table class="practicas-ras-table">
                      <thead>
                        <tr>
                          <th scope="col">% cedido a la empresa</th>
                          <th scope="col">Resultado de aprendizaje</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($module_ras as $ra): ?>
                          <?php
                            $ra_id = (int) $ra['id_ra'];
                            $ra_number = trim((string) ($ra['numero'] ?? ''));
                            $ra_label = format_ra_label($ra['numero'] ?? '', $ra_id);
                            $saved_value = $saved_percentages[$ra_id] ?? '';
                          ?>
                          <tr>
                            <td class="practicas-ras-percentage-cell">
                              <label>
                                <span class="sr-only">Porcentaje empresa para RA <?php echo htmlspecialchars($ra_number !== '' ? $ra_number : (string) $ra_id, ENT_QUOTES, 'UTF-8'); ?></span>
                                <input
                                  class="practicas-ras-percentage-input"
                                  type="number"
                                  name="porcentajes[<?php echo $ra_id; ?>]"
                                  min="0"
                                  max="100"
                                  step="0.01"
                                  value="<?php echo htmlspecialchars($saved_value, ENT_QUOTES, 'UTF-8'); ?>"
                                >
                              </label>
                            </td>
                            <td>
                              <p><strong><?php echo htmlspecialchars($ra_label, ENT_QUOTES, 'UTF-8'); ?></strong> <?php echo htmlspecialchars((string) ($ra['descripcion'] ?? 'Sin descripción'), ENT_QUOTES, 'UTF-8'); ?></p>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  <?php endif; ?>
                </section>
              <?php endforeach; ?>
            </div>

            <div class="form-actions">
              <button type="submit" class="primary-button">Guardar</button>
              <button type="reset" class="ghost-button">Limpiar cambios</button>
            </div>
          <?php endif; ?>
        </form>
      <?php endif; ?>
    </main>
  </div>
</body>
</html>
