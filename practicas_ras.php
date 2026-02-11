<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

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

$courses = [];
$course_map = [];
$can_filter_by_select_course = false;

try {
  $courses_stmt = $pdo->query('SELECT id_curso_escolar, curso_escolar FROM cursos_escolares ORDER BY id_curso_escolar DESC');
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

$selected_course_id = trim((string) ($_GET['curso'] ?? $_POST['curso'] ?? ''));
$selected_course_text = trim((string) ($_GET['curso_texto'] ?? $_POST['curso_texto'] ?? ''));
$selected_cycle = trim((string) ($_GET['ciclo'] ?? $_POST['ciclo'] ?? ''));
if (!in_array($selected_cycle, $available_cycles, true)) {
  $selected_cycle = '';
}

$selected_course_label = '';
if ($can_filter_by_select_course) {
  if ($selected_course_id === '' && count($courses) > 0) {
    $selected_course_id = (string) (int) $courses[0]['id_curso_escolar'];
  }
  if ($selected_course_id !== '' && isset($course_map[$selected_course_id])) {
    $selected_course_label = $course_map[$selected_course_id];
  }
} else {
  $selected_course_label = $selected_course_text;
}

$practicas_columns = [];
$practicas_table_ready = false;
$column_course = null;
$column_cycle = null;
$column_module = null;
$column_ra = null;
$column_percentage = null;

try {
  $columns_stmt = $pdo->query('SHOW COLUMNS FROM practicas_ras');
  foreach ($columns_stmt->fetchAll() as $column) {
    $practicas_columns[] = (string) $column['Field'];
  }

  $column_course = find_column($practicas_columns, ['curso_escolar', 'id_curso_escolar']);
  $column_cycle = find_column($practicas_columns, ['ciclo', 'id_ciclo']);
  $column_module = find_column($practicas_columns, ['id_modulo']);
  $column_ra = find_column($practicas_columns, ['id_ra']);
  $column_percentage = find_column($practicas_columns, ['porcentaje']);

  $practicas_table_ready = $column_course !== null
    && $column_cycle !== null
    && $column_module !== null
    && $column_ra !== null
    && $column_percentage !== null;
} catch (Throwable $exception) {
  $practicas_columns = [];
  $practicas_table_ready = false;
}

$course_storage_value = $column_course === 'id_curso_escolar' ? $selected_course_id : $selected_course_label;
$cycle_storage_value = $column_cycle === 'id_ciclo' ? null : $selected_cycle;

if ($column_cycle === 'id_ciclo' && $selected_cycle !== '') {
  try {
    $cycle_id_stmt = $pdo->prepare('SELECT id_ciclo FROM ciclos WHERE abreviatura = :abreviatura LIMIT 1');
    $cycle_id_stmt->execute(['abreviatura' => $selected_cycle]);
    $cycle_storage_value = $cycle_id_stmt->fetchColumn();
    if ($cycle_storage_value !== false) {
      $cycle_storage_value = (string) (int) $cycle_storage_value;
    } else {
      $cycle_storage_value = null;
    }
  } catch (Throwable $exception) {
    $cycle_storage_value = null;
  }
}

$messages = [];
$errors = [];

$filters_ready = $selected_cycle !== '' && (($can_filter_by_select_course && $selected_course_id !== '' && $selected_course_label !== '') || (!$can_filter_by_select_course && $selected_course_label !== ''));

$modules = [];
$ras_by_module = [];
$ces_by_ra = [];
$saved_percentages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'guardar') {
  if (!$practicas_table_ready) {
    $errors[] = 'La tabla practicas_ras no está disponible o no contiene las columnas necesarias para guardar.';
  }

  if (!$filters_ready) {
    $errors[] = 'Selecciona curso escolar y ciclo antes de guardar.';
  }

  if ($column_cycle === 'id_ciclo' && $cycle_storage_value === null) {
    $errors[] = 'No se ha podido resolver el ciclo seleccionado.';
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
          $column_course,
          $column_cycle
        );
        $delete_stmt = $pdo->prepare($delete_sql);
        $delete_stmt->execute([
          'curso' => $course_storage_value,
          'ciclo' => $cycle_storage_value,
        ]);

        if ($percentages_to_save) {
          $insert_sql = sprintf(
            'INSERT INTO practicas_ras (%s, %s, %s, %s, %s) VALUES (:curso, :ciclo, :id_modulo, :id_ra, :porcentaje)',
            $column_course,
            $column_cycle,
            $column_module,
            $column_ra,
            $column_percentage
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
        $errors[] = 'No se han podido guardar los porcentajes. Revisa la configuración de la tabla practicas_ras.';
      }
    }
  }
}

if ($filters_ready) {
  $modules_stmt = $pdo->prepare(
    'SELECT m.id_modulo, m.abreviatura, m.materia_general, m.materia_propia
     FROM modulos m
     INNER JOIN ciclos c ON c.id_ciclo = m.id_ciclo
     WHERE c.abreviatura = :ciclo
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

    $ra_ids = [];
    foreach ($ras as $ra) {
      $module_id = (int) $ra['id_modulo'];
      if (!isset($ras_by_module[$module_id])) {
        $ras_by_module[$module_id] = [];
      }
      $ras_by_module[$module_id][] = $ra;
      $ra_ids[] = (int) $ra['id_ra'];
    }

    if ($ra_ids) {
      $ce_placeholders = implode(',', array_fill(0, count($ra_ids), '?'));
      $ces_stmt = $pdo->prepare(
        'SELECT id_ce, id_ra, codigo, descripcion
         FROM criterios_evaluacion
         WHERE id_ra IN (' . $ce_placeholders . ')
         ORDER BY id_ra, codigo, id_ce'
      );
      $ces_stmt->execute($ra_ids);

      foreach ($ces_stmt->fetchAll() as $ce) {
        $ra_id = (int) $ce['id_ra'];
        if (!isset($ces_by_ra[$ra_id])) {
          $ces_by_ra[$ra_id] = [];
        }
        $ces_by_ra[$ra_id][] = $ce;
      }
    }

    if ($practicas_table_ready && $course_storage_value !== '' && $cycle_storage_value !== null) {
      $saved_stmt = $pdo->prepare(
        sprintf(
          'SELECT %s AS id_ra, %s AS porcentaje
           FROM practicas_ras
           WHERE %s = :curso AND %s = :ciclo',
          $column_ra,
          $column_percentage,
          $column_course,
          $column_cycle
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
          <p>Selecciona curso escolar y ciclo para cargar módulos, RA y CE.</p>
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
            <p>Expande cada módulo y RA para consultar los CE asociados e indicar el porcentaje para empresa.</p>
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
                <details class="practicas-ras-module">
                  <summary>
                    <span><?php echo htmlspecialchars(format_module_name($module), ENT_QUOTES, 'UTF-8'); ?></span>
                    <small><?php echo count($module_ras); ?> RA</small>
                  </summary>

                  <div class="practicas-ras-content">
                    <?php if (!$module_ras): ?>
                      <p>Este módulo no tiene resultados de aprendizaje registrados.</p>
                    <?php else: ?>
                      <?php foreach ($module_ras as $ra): ?>
                        <?php
                          $ra_id = (int) $ra['id_ra'];
                          $criteria = $ces_by_ra[$ra_id] ?? [];
                          $ra_number = trim((string) ($ra['numero'] ?? ''));
                          $saved_value = $saved_percentages[$ra_id] ?? '';
                        ?>
                        <details class="practicas-ras-ra">
                          <summary>
                            <div class="practicas-ras-ra-summary">
                              <span>
                                <strong>RA <?php echo htmlspecialchars($ra_number !== '' ? $ra_number : '-', ENT_QUOTES, 'UTF-8'); ?></strong>
                                <?php echo htmlspecialchars((string) ($ra['descripcion'] ?? 'Sin descripción'), ENT_QUOTES, 'UTF-8'); ?>
                              </span>
                              <label>
                                % empresa
                                <input
                                  type="number"
                                  name="porcentajes[<?php echo $ra_id; ?>]"
                                  min="0"
                                  max="100"
                                  step="0.01"
                                  value="<?php echo htmlspecialchars($saved_value, ENT_QUOTES, 'UTF-8'); ?>"
                                >
                              </label>
                            </div>
                          </summary>

                          <div class="practicas-ras-content practicas-ras-criteria">
                            <?php if (!$criteria): ?>
                              <p>Sin criterios de evaluación para este RA.</p>
                            <?php else: ?>
                              <ul>
                                <?php foreach ($criteria as $criterion): ?>
                                  <li>
                                    <strong><?php echo htmlspecialchars((string) ($criterion['codigo'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <?php echo htmlspecialchars((string) ($criterion['descripcion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                  </li>
                                <?php endforeach; ?>
                              </ul>
                            <?php endif; ?>
                          </div>
                        </details>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </div>
                </details>
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
