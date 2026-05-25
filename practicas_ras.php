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

  $code = trim((string) ($module['codigo'] ?? ''));
  if ($code !== '') {
    return $code . ' - ' . $main;
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
    return 'RA' . $raId;
  }

  if (preg_match('/(\d+)/', $raw, $matches)) {
    return 'RA' . $matches[1];
  }

  return 'RA' . $raId;
}

function build_saved_percentage_key(?int $moduleId, int $raId): string {
  $normalized_module_id = $moduleId ?? 0;
  return $normalized_module_id . ':' . $raId;
}

function build_course_filter_sql(array $conditions): string {
  if (!$conditions) {
    return '';
  }

  if (count($conditions) === 1) {
    return $conditions[0];
  }

  return '(' . implode(' OR ', $conditions) . ')';
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

$cycles = [];
$cycle_map = [];
try {
  $cycles_stmt = $pdo->query('SELECT id_ciclo, abreviatura, ciclo FROM ciclos ORDER BY abreviatura, ciclo, id_ciclo');
  $cycles = $cycles_stmt->fetchAll();

  foreach ($cycles as $cycle) {
    $id = (string) (int) $cycle['id_ciclo'];
    $cycle_map[$id] = [
      'abreviatura' => (string) ($cycle['abreviatura'] ?? ''),
      'ciclo' => (string) ($cycle['ciclo'] ?? ''),
    ];
  }

} catch (Throwable $exception) {
  $cycles = [];
  $cycle_map = [];
}

$debug_mode = should_debug_practicas_ras();
$debug_details = [];
$active_database = null;

$selected_course_id = trim((string) ($_GET['curso'] ?? $_POST['curso'] ?? $_GET['id_curso_escolar'] ?? $_POST['id_curso_escolar'] ?? ''));
$selected_course_text = trim((string) ($_GET['curso_texto'] ?? $_POST['curso_texto'] ?? ''));
$selected_cycle_id = trim((string) ($_GET['ciclo'] ?? $_POST['ciclo'] ?? ''));
if ($selected_cycle_id !== '' && !isset($cycle_map[$selected_cycle_id])) {
  $selected_cycle_id = '';
}

$selected_cycle_label = '';
if ($selected_cycle_id !== '' && isset($cycle_map[$selected_cycle_id])) {
  $selected_cycle_label = trim($cycle_map[$selected_cycle_id]['abreviatura']);
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

$required_practicas_columns = ['id_ra', 'porcentaje'];
$practicas_columns = [];
$practicas_table_ready = false;
$missing_practicas_columns = [];
$practicas_table_exists = false;
$schema_error_message = null;
$practicas_course_column = null;
$practicas_cycle_column = null;
$practicas_module_column = null;
$practicas_has_course_id_column = false;
$practicas_has_course_text_column = false;

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

  $practicas_course_column = find_column($practicas_columns, ['id_curso_escolar', 'curso_escolar']);
$practicas_cycle_column = find_column($practicas_columns, ['id_ciclo', 'ciclo']);
  $practicas_module_column = find_column($practicas_columns, ['id_modulo']);
  $practicas_has_course_id_column = in_array('id_curso_escolar', $practicas_columns, true);
  $practicas_has_course_text_column = in_array('curso_escolar', $practicas_columns, true);

  $missing_practicas_columns = find_missing_columns($practicas_columns, $required_practicas_columns);
  if ($practicas_course_column === null) {
    $missing_practicas_columns[] = 'curso_escolar o id_curso_escolar';
  }

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
$cycle_storage_value = $selected_cycle_id;

$messages = [];
$errors = [];

$filters_ready = $selected_cycle_id !== '' && (($can_filter_by_select_course && $selected_course_id !== '' && $selected_course_label !== '') || (!$can_filter_by_select_course && $selected_course_label !== ''));

$modules = [];
$ras_by_module = [];
$criteria_by_ra = [];
$saved_percentages = [];
$saved_percentages_rows = [];

if (isset($_GET['exportar_json']) && (string) $_GET['exportar_json'] === '1') {
  $export_data = [];

  if ($filters_ready) {
    $export_conditions = [];
    $export_params = [];

    if ($practicas_has_course_id_column) {
      $export_conditions[] = 'pr.id_curso_escolar = :curso_id';
      $export_params['curso_id'] = (int) $selected_course_id;
    }
    if ($practicas_has_course_text_column) {
      $export_conditions[] = 'pr.curso_escolar = :curso';
      $export_params['curso'] = $selected_course_label;
    }
    if (!$practicas_has_course_id_column && !$practicas_has_course_text_column && $practicas_course_column !== null) {
      $export_conditions[] = 'pr.' . $practicas_course_column . ' = :curso';
      $export_params['curso'] = $selected_course_label;
    }
    if ($practicas_cycle_column !== null) {
      $export_conditions[] = 'pr.' . $practicas_cycle_column . ' = :ciclo';
      if ($practicas_cycle_column === 'id_ciclo') {
        $export_params['ciclo'] = (int) $selected_cycle_id;
      } else {
        $export_params['ciclo'] = $selected_cycle_label;
      }
    }

    $where_sql = $export_conditions ? 'WHERE ' . build_course_filter_sql($export_conditions) : '';
    $export_stmt = $pdo->prepare(
      'SELECT
        COALESCE(m.codigo, "") AS codigoModulo,
        COALESCE(NULLIF(m.materia_propia, ""), NULLIF(m.materia_general, ""), "Módulo sin nombre") AS modulo,
        ra.id_ra,
        ra.numero,
        ra.descripcion
       FROM practicas_ras pr
       INNER JOIN resultados_aprendizaje ra ON ra.id_ra = pr.id_ra
       INNER JOIN modulos m ON m.id_modulo = ra.id_modulo
       ' . $where_sql . '
       ORDER BY m.abreviatura, m.materia_propia, m.materia_general, m.id_modulo, ra.numero, ra.id_ra'
    );
    $export_stmt->execute($export_params);

    foreach ($export_stmt->fetchAll() as $row) {
      $ra_id = (int) ($row['id_ra'] ?? 0);
      $export_data[] = [
        'codigoModulo' => trim((string) ($row['codigoModulo'] ?? '')),
        'modulo' => trim((string) ($row['modulo'] ?? 'Módulo sin nombre')),
        'ra' => format_ra_label($row['numero'] ?? '', $ra_id),
        'descripcion' => (string) ($row['descripcion'] ?? ''),
      ];
    }
  }

  while (ob_get_level() > 0) {
    ob_end_clean();
  }
  header('Content-Type: application/json; charset=utf-8');
  header('Content-Disposition: attachment; filename="ras.json"');
  echo json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
  exit;
}

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

        $course_storage_id_value = null;
        if ($practicas_has_course_id_column) {
          $course_storage_id_value = (int) $selected_course_id;
          if ($course_storage_id_value <= 0) {
            throw new RuntimeException('Debes seleccionar un curso escolar válido antes de guardar.');
          }

        }
        if ($practicas_has_course_text_column && $selected_course_label !== '') {
          $course_storage_value = $selected_course_label;
        }
        if ($practicas_course_column === 'id_curso_escolar' && $course_storage_id_value !== null) {
          $course_storage_value = $course_storage_id_value;
        }

        $course_delete_conditions = [];
        $delete_conditions = [];
        if ($practicas_has_course_id_column && $course_storage_id_value !== null) {
          $course_delete_conditions[] = 'id_curso_escolar = :curso_id';
        }
        if ($practicas_has_course_text_column) {
          $course_delete_conditions[] = 'curso_escolar = :curso';
        }
        if (!$course_delete_conditions && $practicas_course_column !== null) {
          $course_delete_conditions[] = $practicas_course_column . ' = :curso';
        }
        $course_delete_filter = build_course_filter_sql($course_delete_conditions);
        if ($course_delete_filter !== '') {
          $delete_conditions[] = $course_delete_filter;
        }
        if ($practicas_cycle_column !== null) {
          if ($practicas_cycle_column === 'id_ciclo') {
            $cycle_storage_value = (int) $selected_cycle_id;
          } elseif ($selected_cycle_label !== '') {
            $cycle_storage_value = $selected_cycle_label;
          }
          $delete_conditions[] = $practicas_cycle_column . ' = :ciclo';
        }

        $delete_sql = 'DELETE FROM practicas_ras WHERE ' . implode(' AND ', $delete_conditions);
        $delete_stmt = $pdo->prepare($delete_sql);
        $delete_params = [];
        if (in_array('id_curso_escolar = :curso_id', $course_delete_conditions, true)) {
          $delete_params['curso_id'] = $course_storage_id_value;
        }
        if (in_array('curso_escolar = :curso', $course_delete_conditions, true) || (!$practicas_has_course_id_column && $practicas_course_column !== null)) {
          $delete_params['curso'] = $course_storage_value;
        }
        if ($practicas_cycle_column !== null) {
          $delete_params['ciclo'] = $cycle_storage_value;
        }
        $delete_stmt->execute($delete_params);

        if ($percentages_to_save) {
          $insert_columns = [];
          $insert_values = [];
          if ($practicas_has_course_id_column) {
            $insert_columns[] = 'id_curso_escolar';
            $insert_values[] = ':curso_id';
          }
          if ($practicas_has_course_text_column) {
            $insert_columns[] = 'curso_escolar';
            $insert_values[] = ':curso';
          }
          if (!$insert_columns && $practicas_course_column !== null) {
            $insert_columns[] = $practicas_course_column;
            $insert_values[] = ':curso';
          }
          if ($practicas_cycle_column !== null) {
            $insert_columns[] = $practicas_cycle_column;
            $insert_values[] = ':ciclo';
          }
          if ($practicas_module_column !== null) {
            $insert_columns[] = $practicas_module_column;
            $insert_values[] = ':id_modulo';
          }
          $insert_columns[] = 'id_ra';
          $insert_values[] = ':id_ra';
          $insert_columns[] = 'porcentaje';
          $insert_values[] = ':porcentaje';

          $insert_sql = sprintf(
            'INSERT INTO practicas_ras (%s) VALUES (%s)',
            implode(', ', $insert_columns),
            implode(', ', $insert_values)
          );
          $insert_stmt = $pdo->prepare($insert_sql);

          foreach ($percentages_to_save as $ra_id => $percentage) {
            $module_lookup_stmt = $pdo->prepare('SELECT id_modulo FROM resultados_aprendizaje WHERE id_ra = :id_ra LIMIT 1');
            $module_lookup_stmt->execute(['id_ra' => $ra_id]);
            $module_id = $module_lookup_stmt->fetchColumn();
            if ($module_id === false) {
              continue;
            }

            $insert_params = [
              'id_ra' => $ra_id,
              'porcentaje' => $percentage,
            ];
            if ($practicas_has_course_id_column && $course_storage_id_value !== null) {
              $insert_params['curso_id'] = $course_storage_id_value;
            }
            if ($practicas_has_course_text_column || (!$practicas_has_course_id_column && $practicas_course_column !== null)) {
              $insert_params['curso'] = $course_storage_value;
            }
            if ($practicas_cycle_column !== null) {
              $insert_params['ciclo'] = $cycle_storage_value;
            }
            if ($practicas_module_column !== null) {
              $insert_params['id_modulo'] = (int) $module_id;
            }

            $insert_stmt->execute($insert_params);
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
      m.codigo,
      m.abreviatura,
      m.materia_general,
      m.materia_propia,
      COUNT(ra.id_ra) AS total_ras
     FROM modulos m
     INNER JOIN ciclos c ON c.id_ciclo = m.id_ciclo
     INNER JOIN cursos cu ON cu.id_curso = m.id_curso
     INNER JOIN resultados_aprendizaje ra ON ra.id_modulo = m.id_modulo
     WHERE c.id_ciclo = :id_ciclo
       AND cu.curso = 2
     GROUP BY m.id_modulo, m.codigo, m.abreviatura, m.materia_general, m.materia_propia
     ORDER BY m.abreviatura, m.materia_propia, m.materia_general, m.id_modulo'
  );
  $modules_stmt->execute(['id_ciclo' => (int) $selected_cycle_id]);
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

    $ra_ids = array_map(static fn (array $row): int => (int) $row['id_ra'], $ras);
    if ($ra_ids) {
      $criteria_placeholders = implode(',', array_fill(0, count($ra_ids), '?'));
      $criteria_stmt = $pdo->prepare(
        'SELECT ce.id_ra, ce.codigo, ce.descripcion
         FROM criterios_evaluacion ce
         WHERE ce.id_ra IN (' . $criteria_placeholders . ')
         ORDER BY ce.id_ra, ce.codigo, ce.id_ce'
      );
      $criteria_stmt->execute($ra_ids);

      foreach ($criteria_stmt->fetchAll() as $criterion) {
        $ra_id = (int) $criterion['id_ra'];
        if (!isset($criteria_by_ra[$ra_id])) {
          $criteria_by_ra[$ra_id] = [];
        }
        $criteria_by_ra[$ra_id][] = $criterion;
      }
    }

    if ($practicas_has_course_id_column) {
      $course_storage_value = (int) $selected_course_id;
    }
  }

  if ($practicas_table_ready && $course_storage_value !== '') {
      $course_saved_conditions = [];
      $saved_conditions = [];
      if ($practicas_has_course_id_column) {
        $course_saved_conditions[] = 'pr.id_curso_escolar = :curso_id';
      }
      if ($practicas_has_course_text_column) {
        $course_saved_conditions[] = 'pr.curso_escolar = :curso';
      }
      if (!$course_saved_conditions && $practicas_course_column !== null) {
        $course_saved_conditions[] = 'pr.' . $practicas_course_column . ' = :curso';
      }
      $course_saved_filter = build_course_filter_sql($course_saved_conditions);
      if ($course_saved_filter !== '') {
        $saved_conditions[] = $course_saved_filter;
      }
      if ($practicas_cycle_column !== null) {
        $saved_conditions[] = 'pr.' . $practicas_cycle_column . ' = :ciclo';
      }

      $saved_stmt = $pdo->prepare(
        sprintf(
          'SELECT %s AS id_ra, %s AS id_modulo, %s AS porcentaje
           FROM practicas_ras pr
           WHERE %s',
          'pr.id_ra',
          $practicas_module_column !== null ? 'pr.' . $practicas_module_column : 'NULL',
          'pr.porcentaje',
          implode(' AND ', $saved_conditions)
        )
      );
      $saved_params = [];
      if ($practicas_has_course_id_column) {
        $saved_params['curso_id'] = (int) $selected_course_id;
      }
      if ($practicas_has_course_text_column || !$practicas_has_course_id_column) {
        $saved_params['curso'] = $selected_course_label;
      }
      if ($practicas_cycle_column !== null) {
        if ($practicas_cycle_column === 'id_ciclo') {
          $saved_params['ciclo'] = (int) $selected_cycle_id;
        } else {
          $saved_params['ciclo'] = $selected_cycle_label;
        }
      }
      $saved_stmt->execute($saved_params);

      foreach ($saved_stmt->fetchAll() as $saved) {
        $saved_ra_id = (int) ($saved['id_ra'] ?? 0);
        if ($saved_ra_id <= 0) {
          continue;
        }

        $saved_module_id = isset($saved['id_modulo']) && $saved['id_modulo'] !== null
          ? (int) $saved['id_modulo']
          : 0;
        $saved_key = build_saved_percentage_key($saved_module_id, $saved_ra_id);
        $saved_percentages[$saved_key] = (string) ($saved['porcentaje'] ?? '');

        if ($saved_module_id === 0) {
          $legacy_saved_key = build_saved_percentage_key(null, $saved_ra_id);
          $saved_percentages[$legacy_saved_key] = (string) ($saved['porcentaje'] ?? '');
        }
      }

      $saved_rows_stmt = $pdo->prepare(
        sprintf(
          'SELECT %s AS id_ra, %s AS porcentaje, ra.numero, ra.descripcion AS ra_descripcion, m.id_modulo, m.codigo, m.abreviatura, m.materia_general, m.materia_propia
           FROM practicas_ras pr
           INNER JOIN resultados_aprendizaje ra ON ra.id_ra = pr.id_ra
           INNER JOIN modulos m ON m.id_modulo = ra.id_modulo
           WHERE %s
           ORDER BY m.abreviatura, m.materia_propia, m.materia_general, m.id_modulo, ra.numero, ra.id_ra',
          'pr.id_ra',
          'pr.porcentaje',
          implode(' AND ', $saved_conditions)
        )
      );
      $saved_rows_stmt->execute($saved_params);
      $saved_percentages_rows = $saved_rows_stmt->fetchAll();
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
          <h1>Porcentaje RA/CE en empresa</h1>
          <p class="subheading">Define el porcentaje cedido a la empresa por cada resultado de aprendizaje en un curso y ciclo concretos.</p>
        </div>
        <div class="header-actions">
          <a class="primary-button" href="?exportar_json=1<?php echo $can_filter_by_select_course && $selected_course_id !== '' ? '&amp;curso=' . urlencode($selected_course_id) : ($selected_course_text !== '' ? '&amp;curso_texto=' . urlencode($selected_course_text) : ''); ?><?php echo $selected_cycle_id !== '' ? '&amp;ciclo=' . urlencode($selected_cycle_id) : ''; ?>">Exportar a JSON</a>
        </div>
      </header>

      <form method="get" class="topbar" id="practicas-ras-filter-form">
        <div class="topbar-actions entity-grid entity-grid--4">
          <?php if ($can_filter_by_select_course): ?>
            <label class="calendar-select">
              <select name="curso" required onchange="this.form.submit()" aria-label="Curso escolar">
                <option value="">Selecciona curso escolar</option>
                <?php foreach ($courses as $course): ?>
                  <?php $course_value = (string) (int) $course['id_curso_escolar']; ?>
                  <option value="<?php echo htmlspecialchars($course_value, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $selected_course_id === $course_value ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars((string) $course['curso_escolar'], ENT_QUOTES, 'UTF-8'); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </label>
          <?php else: ?>
            <label class="calendar-select">
              <input type="text" name="curso_texto" value="<?php echo htmlspecialchars($selected_course_text, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Ej. 2024/2025" required>
            </label>
          <?php endif; ?>

          <label class="calendar-select">
            <select name="ciclo" required onchange="this.form.submit()" aria-label="Ciclo">
              <option value="">Selecciona ciclo</option>
              <?php foreach ($cycles as $cycle): ?>
                <?php
                  $cycle_value = (string) (int) ($cycle['id_ciclo'] ?? 0);
                  $cycle_short = trim((string) ($cycle['abreviatura'] ?? ''));
                  $cycle_name = trim((string) ($cycle['ciclo'] ?? ''));
                  $cycle_text = $cycle_short !== '' ? $cycle_short : $cycle_name;
                ?>
                <option value="<?php echo htmlspecialchars($cycle_value, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $selected_cycle_id === $cycle_value ? 'selected' : ''; ?>><?php echo htmlspecialchars($cycle_text, ENT_QUOTES, 'UTF-8'); ?></option>
              <?php endforeach; ?>
            </select>
          </label>
        </div>
      </form>

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
          <input type="hidden" name="ciclo" value="<?php echo htmlspecialchars($selected_cycle_id, ENT_QUOTES, 'UTF-8'); ?>">
          <?php if ($can_filter_by_select_course): ?>
            <input type="hidden" name="curso" value="<?php echo htmlspecialchars($selected_course_id, ENT_QUOTES, 'UTF-8'); ?>">
          <?php else: ?>
            <input type="hidden" name="curso_texto" value="<?php echo htmlspecialchars($selected_course_text, ENT_QUOTES, 'UTF-8'); ?>">
          <?php endif; ?>

          <?php if (!$modules): ?>
            <p>No hay módulos para el ciclo seleccionado.</p>
          <?php else: ?>
            <div class="entity-grid entity-grid--4" style="align-items: start;">
              <?php foreach ($modules as $module): ?>
                <?php
                  $module_id = (int) $module['id_modulo'];
                  $module_ras = $ras_by_module[$module_id] ?? [];
                ?>
                <section class="practicas-ras-module panel panel-grid">
                  <div class="panel-header">
                    <h3><?php echo htmlspecialchars(format_module_name($module), ENT_QUOTES, 'UTF-8'); ?></h3>
                  </div>

                  <?php if (!$module_ras): ?>
                    <p>Este módulo no tiene resultados de aprendizaje registrados.</p>
                  <?php else: ?>
                    <table class="practicas-ras-table" style="font-size: 0.9rem;">
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
                            $saved_key = build_saved_percentage_key($module_id, $ra_id);
                            $saved_value = $saved_percentages[$saved_key] ?? '';
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
                              <button
                                type="button"
                                class="practicas-ras-ra-trigger"
                                aria-haspopup="dialog"
                                aria-expanded="false"
                                data-ra-label="<?php echo htmlspecialchars($ra_label, ENT_QUOTES, 'UTF-8'); ?>"
                                data-ra-description="<?php echo htmlspecialchars((string) ($ra['descripcion'] ?? 'Sin descripción'), ENT_QUOTES, 'UTF-8'); ?>"
                                data-ra-criteria="<?php echo htmlspecialchars(json_encode($criteria_by_ra[$ra_id] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]', ENT_QUOTES, 'UTF-8'); ?>"
                              >
                                <strong><?php echo htmlspecialchars($ra_label, ENT_QUOTES, 'UTF-8'); ?></strong>
                              </button>
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

        <section class="panel">
          <div class="panel-header">
            <h3>Porcentajes guardados</h3>
            <p>Resumen del módulo, el RA con porcentaje asignado, el porcentaje y la descripción del resultado de aprendizaje.</p>
          </div>

          <?php if (!$saved_percentages_rows): ?>
            <p>No hay porcentajes guardados para el curso escolar y ciclo seleccionados.</p>
          <?php else: ?>
            <table class="practicas-ras-table">
              <thead>
                <tr>
                  <th scope="col">Módulo</th>
                  <th scope="col">RA</th>
                  <th scope="col">Porcentaje asignado</th>
                  <th scope="col">Descripción del RA</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($saved_percentages_rows as $saved_row): ?>
                  <?php
                    $saved_ra_id = (int) ($saved_row['id_ra'] ?? 0);
                    $saved_ra_label = format_ra_label($saved_row['numero'] ?? '', $saved_ra_id);
                  ?>
                  <tr>
                    <td><?php echo htmlspecialchars(format_module_name($saved_row), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($saved_ra_label, ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars((string) ($saved_row['porcentaje'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>%</td>
                    <td><?php echo htmlspecialchars((string) ($saved_row['ra_descripcion'] ?? 'Sin descripción'), ENT_QUOTES, 'UTF-8'); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </section>

      <?php endif; ?>
    </main>
  </div>

  <div class="practicas-ras-popover-layer" id="ra-detail-layer" hidden>
    <button type="button" class="practicas-ras-popover-backdrop" data-popover-close tabindex="-1" aria-hidden="true"></button>
    <div class="practicas-ras-popover" id="ra-detail-popover" role="dialog" aria-modal="false" aria-labelledby="ra-detail-title" hidden>
      <button type="button" class="practicas-ras-popover__close" data-popover-close aria-label="Cerrar detalle del RA">×</button>
      <h3 id="ra-detail-title" class="practicas-ras-popover__title"></h3>
      <p class="practicas-ras-popover__description" id="ra-detail-description"></p>
      <h4 class="practicas-ras-popover__heading">Criterios de evaluación</h4>
      <ul class="practicas-ras-popover__criteria" id="ra-detail-criteria"></ul>
    </div>
  </div>

  <script>
    (() => {
      const modulesRoot = document.querySelector('.practicas-ras-modules-grid');
      const layer = document.getElementById('ra-detail-layer');
      const popover = document.getElementById('ra-detail-popover');
      if (!modulesRoot || !layer || !popover) return;

      const title = document.getElementById('ra-detail-title');
      const description = document.getElementById('ra-detail-description');
      const criteriaList = document.getElementById('ra-detail-criteria');
      let activeTrigger = null;

      const closePopover = () => {
        popover.hidden = true;
        layer.hidden = true;
        if (activeTrigger) {
          activeTrigger.setAttribute('aria-expanded', 'false');
        }
        activeTrigger = null;
      };

      const setPopoverPosition = (trigger) => {
        const triggerRect = trigger.getBoundingClientRect();
        const popoverRect = popover.getBoundingClientRect();
        const gutter = 12;
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;

        let top = triggerRect.top;
        let left = triggerRect.right + gutter;

        if (left + popoverRect.width > viewportWidth - gutter) {
          left = triggerRect.left - popoverRect.width - gutter;
        }

        if (left < gutter) {
          left = Math.min(viewportWidth - popoverRect.width - gutter, Math.max(gutter, triggerRect.left));
          top = triggerRect.bottom + gutter;
        }

        if (top + popoverRect.height > viewportHeight - gutter) {
          top = Math.max(gutter, viewportHeight - popoverRect.height - gutter);
        }

        popover.style.top = `${Math.max(gutter, top)}px`;
        popover.style.left = `${Math.max(gutter, left)}px`;
      };

      const openPopover = (trigger) => {
        if (activeTrigger && activeTrigger !== trigger) {
          activeTrigger.setAttribute('aria-expanded', 'false');
        }

        title.textContent = trigger.dataset.raLabel || '';
        description.textContent = trigger.dataset.raDescription || 'Sin descripción';
        criteriaList.innerHTML = '';

        let criteria = [];
        try {
          criteria = JSON.parse(trigger.dataset.raCriteria || '[]');
        } catch (error) {
          criteria = [];
        }

        if (!Array.isArray(criteria) || criteria.length === 0) {
          const item = document.createElement('li');
          item.textContent = 'Sin criterios asociados';
          criteriaList.appendChild(item);
        } else {
          criteria.forEach((criterion) => {
            const item = document.createElement('li');
            const code = (criterion.codigo || '').toString().trim();
            const descriptionText = (criterion.descripcion || '').toString().trim();
            item.textContent = code !== '' ? `${code}: ${descriptionText}` : descriptionText;
            criteriaList.appendChild(item);
          });
        }

        activeTrigger = trigger;
        trigger.setAttribute('aria-expanded', 'true');
        layer.hidden = false;
        popover.hidden = false;
        setPopoverPosition(trigger);
      };

      modulesRoot.addEventListener('click', (event) => {
        const trigger = event.target.closest('.practicas-ras-ra-trigger');
        if (!trigger || !modulesRoot.contains(trigger)) {
          return;
        }

        if (activeTrigger === trigger && !popover.hidden) {
          closePopover();
          return;
        }

        openPopover(trigger);
      });

      layer.querySelectorAll('[data-popover-close]').forEach((element) => {
        element.addEventListener('click', closePopover);
      });

      window.addEventListener('resize', () => {
        if (activeTrigger && !popover.hidden) {
          setPopoverPosition(activeTrigger);
        }
      });

      window.addEventListener('scroll', () => {
        if (activeTrigger && !popover.hidden) {
          setPopoverPosition(activeTrigger);
        }
      }, true);

      document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !popover.hidden) {
          closePopover();
        }
      });
    })();
  </script>
</body>
</html>
