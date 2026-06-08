<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pdo = db();

$page_title = 'Calificaciones | Gestor de Alumnos';
$active_page = 'alumnos';

$courses = $pdo->query(
  'SELECT id_curso_escolar, curso_escolar, activo
   FROM cursos_escolares
   ORDER BY activo DESC, id_curso_escolar DESC'
)->fetchAll();

$active_course_id = $pdo->query('SELECT id_curso_escolar FROM cursos_escolares WHERE activo = 1 ORDER BY id_curso_escolar DESC LIMIT 1')->fetchColumn();
if (!$active_course_id && $courses !== []) {
  $active_course_id = (int) $courses[0]['id_curso_escolar'];
}
$active_course_id = (int) $active_course_id;

$selected_course_id = isset($_GET['id_curso_escolar']) && ctype_digit((string) $_GET['id_curso_escolar'])
  ? (int) $_GET['id_curso_escolar']
  : $active_course_id;

$default_group_id = (string) ($pdo->query("SELECT valor FROM `config` WHERE `clave` = 'grupo_por_defecto' LIMIT 1")->fetchColumn() ?: '');
$selected_group = (string) ($_GET['id_grupo'] ?? $default_group_id);
$selected_group = ctype_digit($selected_group) ? $selected_group : '';

$search_term = trim((string) ($_GET['q'] ?? ''));
$show_all_students = isset($_GET['mostrar_todos']) && (string) $_GET['mostrar_todos'] === '1';

$sort_col_raw = preg_replace('/[^a-z0-9_]/', '', (string) ($_GET['orden_col'] ?? ''));
$sort_modulo_id = 0;
if (preg_match('/^modulo_(\d+)$/', $sort_col_raw, $sort_matches)) {
  $sort_modulo_id = (int) $sort_matches[1];
}
$sort_dir = ((string) ($_GET['orden_dir'] ?? 'asc') === 'desc') ? 'desc' : 'asc';

function build_order_url_cal(int $modulo_id, int $cur_sort_id, string $cur_sort_dir): string {
  $params = $_GET;
  $params['orden_col'] = 'modulo_' . $modulo_id;
  $params['orden_dir'] = ($cur_sort_id === $modulo_id && $cur_sort_dir === 'desc') ? 'asc' : 'desc';
  $query = http_build_query($params);
  return 'calificaciones.php' . ($query !== '' ? '?' . $query : '');
}

function sort_ind_cal(int $modulo_id, int $cur_sort_id, string $cur_sort_dir): string {
  if ($modulo_id !== $cur_sort_id) {
    return '';
  }
  return $cur_sort_dir === 'asc' ? ' ▲' : ' ▼';
}

$sort_by_nombre = ($sort_col_raw === 'nombre');

function grade_sort_key(string $grade): ?float {
  $g = trim($grade);
  if ($g === '' || $g === '—') {
    return null;
  }
  if ($g === 'NE') {
    return -300.0;
  }
  if (preg_match('/^CV-(\d+(?:\.\d+)?)$/i', $g, $m)) {
    return -200.0 + (float) $m[1];
  }
  if (preg_match('/^TC-(\d+(?:\.\d+)?)$/i', $g, $m)) {
    return -100.0 + (float) $m[1];
  }
  if (preg_match('/^(\d+(?:\.\d+)?)-MH$/i', $g, $m)) {
    return (float) $m[1] + 0.5;
  }
  if (preg_match('/^(\d+(?:\.\d+)?)-P$/i', $g, $m)) {
    return (float) $m[1] - 0.5;
  }
  if (is_numeric($g)) {
    return (float) $g;
  }
  return null;
}

function build_order_url_nombre_cal(string $cur_sort_col_raw, string $cur_sort_dir): string {
  $params = $_GET;
  $params['orden_col'] = 'nombre';
  $params['orden_dir'] = ($cur_sort_col_raw === 'nombre' && $cur_sort_dir === 'asc') ? 'desc' : 'asc';
  $query = http_build_query($params);
  return 'calificaciones.php' . ($query !== '' ? '?' . $query : '');
}

function sort_ind_nombre_cal(string $cur_sort_col_raw, string $cur_sort_dir): string {
  if ($cur_sort_col_raw !== 'nombre') {
    return '';
  }
  return $cur_sort_dir === 'asc' ? ' ▲' : ' ▼';
}

$normal_evaluation_names = [
  '1ª evaluación',
  '2ª evaluación',
  'Evaluación ordinaria',
  'Evaluación extraordinaria',
];
$normal_evaluation_order = array_flip($normal_evaluation_names);
$normal_evaluation_placeholders = [];
$normal_evaluation_params = [];
foreach ($normal_evaluation_names as $normal_evaluation_index => $normal_evaluation_name) {
  $normal_evaluation_param = ':normal_eval_' . $normal_evaluation_index;
  $normal_evaluation_placeholders[] = $normal_evaluation_param;
  $normal_evaluation_params['normal_eval_' . $normal_evaluation_index] = $normal_evaluation_name;
}
$normal_placeholders = implode(',', $normal_evaluation_placeholders);

$latest_normal_evaluation_sql =
  'SELECT DISTINCT e.id_evaluacion, e.nombre
   FROM calificaciones c
   INNER JOIN evaluaciones e ON e.id_evaluacion = c.id_evaluacion
   WHERE c.id_curso_escolar = :id_curso_escolar
     AND e.nombre IN (' . $normal_placeholders . ')';
$latest_normal_evaluation_params = array_merge(
  ['id_curso_escolar' => $selected_course_id],
  $normal_evaluation_params
);
if ($selected_group !== '') {
  $latest_normal_evaluation_sql .= ' AND c.id_grupo = :id_grupo';
  $latest_normal_evaluation_params['id_grupo'] = (int) $selected_group;
}
$latest_normal_evaluation_stmt = $pdo->prepare($latest_normal_evaluation_sql);
$latest_normal_evaluation_stmt->execute($latest_normal_evaluation_params);
$available_normal_evaluations = $latest_normal_evaluation_stmt->fetchAll();

$latest_normal_evaluation_id = 0;
$latest_normal_evaluation_index = -1;
foreach ($available_normal_evaluations as $evaluation_row) {
  $evaluation_name = (string) ($evaluation_row['nombre'] ?? '');
  if (!array_key_exists($evaluation_name, $normal_evaluation_order)) {
    continue;
  }
  $evaluation_index = $normal_evaluation_order[$evaluation_name];
  if ($evaluation_index >= $latest_normal_evaluation_index) {
    $latest_normal_evaluation_index = $evaluation_index;
    $latest_normal_evaluation_id = (int) $evaluation_row['id_evaluacion'];
  }
}

if ($latest_normal_evaluation_id <= 0) {
  $fallback_normal_evaluation_stmt = $pdo->prepare(
    'SELECT id_evaluacion, nombre
     FROM evaluaciones
     WHERE nombre IN (' . $normal_placeholders . ')'
  );
  $fallback_normal_evaluation_stmt->execute($normal_evaluation_params);
  $fallback_normal_evaluations = $fallback_normal_evaluation_stmt->fetchAll();

  foreach ($fallback_normal_evaluations as $evaluation_row) {
    $evaluation_name = (string) ($evaluation_row['nombre'] ?? '');
    if (!array_key_exists($evaluation_name, $normal_evaluation_order)) {
      continue;
    }
    $evaluation_index = $normal_evaluation_order[$evaluation_name];
    if ($evaluation_index >= $latest_normal_evaluation_index) {
      $latest_normal_evaluation_index = $evaluation_index;
      $latest_normal_evaluation_id = (int) $evaluation_row['id_evaluacion'];
    }
  }
}

$evaluations = $pdo->query('SELECT id_evaluacion, nombre FROM evaluaciones ORDER BY id_evaluacion')->fetchAll();
if ($latest_normal_evaluation_id <= 0 && $evaluations !== []) {
  $latest_normal_evaluation_id = (int) $evaluations[0]['id_evaluacion'];
}

$selected_evaluation = isset($_GET['id_evaluacion']) && ctype_digit((string) $_GET['id_evaluacion'])
  ? (int) $_GET['id_evaluacion']
  : $latest_normal_evaluation_id;

$groups_stmt = $pdo->prepare(
  'SELECT DISTINCT g.id_grupo, g.grupo
   FROM alumno_curso ac
   INNER JOIN grupos g ON g.id_grupo = ac.id_grupo
   WHERE ac.id_curso_escolar = :id_curso_escolar
   ORDER BY g.grupo'
);
$groups_stmt->execute([
  'id_curso_escolar' => $selected_course_id,
]);
$groups = $groups_stmt->fetchAll();
$groups_by_id = [];
foreach ($groups as $group) {
  $groups_by_id[(int) $group['id_grupo']] = (string) $group['grupo'];
}

$evaluation_ids = array_map(static fn (array $evaluation): int => (int) $evaluation['id_evaluacion'], $evaluations);
if (!in_array($selected_evaluation, $evaluation_ids, true)) {
  $selected_evaluation = $latest_normal_evaluation_id;
}
$evaluation_name_by_id = [];
foreach ($evaluations as $evaluation) {
  $evaluation_name_by_id[(int) $evaluation['id_evaluacion']] = (string) $evaluation['nombre'];
}

$show_results = $selected_group !== '';
$students = [];
$visible_students = [];
$modules = [];
$grades_by_student = [];
$grades_history_by_student = [];
if ($show_results) {
  $students_sql =
    'SELECT
       a.id_alumno,
       a.apellido1,
       a.apellido2,
       a.nombre,
       a.nia AS alumno_nia,
       a.dni AS alumno_dni,
       g.grupo,
       atal.telefono AS alumno_telefono,
       acor_educa.direccion_correo AS alumno_correo_educamadrid,
       acor_personal.direccion_correo AS alumno_correo_personal
     FROM alumno_curso ac
     INNER JOIN alumnos a ON a.id_alumno = ac.id_alumno
     LEFT JOIN grupos g ON g.id_grupo = ac.id_grupo
     LEFT JOIN (
       SELECT id_entidad, MIN(telefono) AS telefono
       FROM telefonos
       WHERE entidad_tipo = "alumno"
       GROUP BY id_entidad
     ) atal ON atal.id_entidad = a.id_alumno
     LEFT JOIN (
       SELECT id_entidad, MIN(direccion_correo) AS direccion_correo
       FROM correos
       WHERE entidad_tipo = "alumno" AND etiqueta = "educamadrid"
       GROUP BY id_entidad
     ) acor_educa ON acor_educa.id_entidad = a.id_alumno
     LEFT JOIN (
       SELECT id_entidad, MIN(direccion_correo) AS direccion_correo
       FROM correos
       WHERE entidad_tipo = "alumno" AND etiqueta = "personal"
       GROUP BY id_entidad
     ) acor_personal ON acor_personal.id_entidad = a.id_alumno
     WHERE ac.id_curso_escolar = :id_curso_escolar
       AND ac.id_grupo = :id_grupo
       AND (
         :search_term = \'\'
         OR a.nombre LIKE :search_term_like_nombre
         OR a.apellido1 LIKE :search_term_like_apellido1
         OR a.apellido2 LIKE :search_term_like_apellido2
       )
     ORDER BY g.grupo, a.apellido1, a.apellido2, a.nombre';
  $students_stmt = $pdo->prepare($students_sql);
  $search_term_like = '%' . $search_term . '%';
  $students_stmt->execute([
    'id_curso_escolar' => $selected_course_id,
    'id_grupo' => (int) $selected_group,
    'search_term' => $search_term,
    'search_term_like_nombre' => $search_term_like,
    'search_term_like_apellido1' => $search_term_like,
    'search_term_like_apellido2' => $search_term_like,
  ]);
  $students = $students_stmt->fetchAll();

  $modules_sql =
    'SELECT DISTINCT
       m.id_modulo,
       m.id_ciclo,
       m.id_curso,
       m.codigo,
       m.abreviatura,
       m.materia_general,
       m.materia_propia
     FROM modulos m
     INNER JOIN alumno_curso ac
       ON ac.id_ciclo = m.id_ciclo
      AND ac.id_curso = m.id_curso
     WHERE ac.id_curso_escolar = :id_curso_escolar
       AND ac.id_grupo = :id_grupo
       AND COALESCE(m.tipo, \'\') <> \'FFE\'
     ORDER BY m.id_ciclo, m.id_curso, m.codigo, m.id_modulo';
  $modules_stmt = $pdo->prepare($modules_sql);
  $modules_stmt->execute([
    'id_curso_escolar' => $selected_course_id,
    'id_grupo' => (int) $selected_group,
  ]);
  $modules = $modules_stmt->fetchAll();

  if ($selected_evaluation > 0) {
    $additional_modules_stmt = $pdo->prepare(
      'SELECT DISTINCT
         m.id_modulo,
         m.id_ciclo,
         m.id_curso,
         m.codigo,
         m.abreviatura,
         m.materia_general,
         m.materia_propia
       FROM calificaciones c
       INNER JOIN modulos m ON m.id_modulo = c.id_modulo
       INNER JOIN alumno_curso ac
         ON ac.id_alumno = c.id_alumno
        AND ac.id_curso_escolar = c.id_curso_escolar
        AND ac.id_grupo = c.id_grupo
       WHERE c.id_curso_escolar = :id_curso_escolar
         AND c.id_grupo = :id_grupo
         AND c.id_evaluacion = :id_evaluacion
         AND (c.nota IS NOT NULL OR TRIM(COALESCE(c.calificacion_original, \'\')) <> \'\')
         AND m.id_ciclo = ac.id_ciclo
         AND m.id_curso <> ac.id_curso
         AND COALESCE(m.tipo, \'\') <> \'FFE\'
       ORDER BY m.id_ciclo, m.id_curso, m.codigo, m.id_modulo'
    );
    $additional_modules_stmt->execute([
      'id_curso_escolar' => $selected_course_id,
      'id_grupo' => (int) $selected_group,
      'id_evaluacion' => $selected_evaluation,
    ]);
    $additional_modules = $additional_modules_stmt->fetchAll();

    if ($additional_modules !== []) {
      $modules_by_id = [];
      foreach ($modules as $module) {
        $modules_by_id[(int) $module['id_modulo']] = $module;
      }
      foreach ($additional_modules as $module) {
        $modules_by_id[(int) $module['id_modulo']] = $module;
      }
      $modules = array_values($modules_by_id);
      usort(
        $modules,
        static fn (array $module_a, array $module_b): int =>
          [(int) $module_a['id_ciclo'], (int) $module_a['id_curso'], (string) $module_a['codigo'], (int) $module_a['id_modulo']]
          <=>
          [(int) $module_b['id_ciclo'], (int) $module_b['id_curso'], (string) $module_b['codigo'], (int) $module_b['id_modulo']]
      );
    }
  }
}

if ($show_results && $students !== [] && $modules !== [] && $selected_evaluation > 0) {
  $grade_filters = ['c.id_curso_escolar = :id_curso_escolar', 'c.id_evaluacion = :id_evaluacion'];
  $grade_params = [
    'id_curso_escolar' => $selected_course_id,
    'id_evaluacion' => $selected_evaluation,
  ];

  $grade_filters[] = 'c.id_grupo = :id_grupo';
  $grade_params['id_grupo'] = (int) $selected_group;

  $grades_stmt = $pdo->prepare(
    'SELECT
       c.id_alumno,
       c.id_modulo,
       c.calificacion_original,
       c.nota
     FROM calificaciones c
     WHERE ' . implode(' AND ', $grade_filters)
  );
  $grades_stmt->execute($grade_params);
  $grades = $grades_stmt->fetchAll();

  foreach ($grades as $grade) {
    $id_alumno = (int) $grade['id_alumno'];
    $id_modulo = (int) $grade['id_modulo'];
    $calificacion_original = trim((string) ($grade['calificacion_original'] ?? ''));

    if ($calificacion_original !== '') {
      $display_grade = $calificacion_original;
    } elseif ($grade['nota'] !== null && $grade['nota'] !== '') {
      $display_grade = rtrim(rtrim(number_format((float) $grade['nota'], 2, '.', ''), '0'), '.');
    } else {
      $display_grade = '—';
    }

    $grades_by_student[$id_alumno][$id_modulo] = $display_grade;
  }

  $grades_history_stmt = $pdo->prepare(
    'SELECT
       c.id_alumno,
       c.id_modulo,
       e.nombre AS evaluacion_nombre,
       c.calificacion_original,
       c.nota
     FROM calificaciones c
     INNER JOIN evaluaciones e ON e.id_evaluacion = c.id_evaluacion
     WHERE c.id_curso_escolar = :id_curso_escolar
       AND c.id_grupo = :id_grupo
     ORDER BY e.id_evaluacion, c.id_calificacion'
  );
  $grades_history_stmt->execute([
    'id_curso_escolar' => $selected_course_id,
    'id_grupo' => (int) $selected_group,
  ]);
  $grades_history = $grades_history_stmt->fetchAll();

  foreach ($grades_history as $history_row) {
    $id_alumno = (int) $history_row['id_alumno'];
    $id_modulo = (int) $history_row['id_modulo'];
    $calificacion_original = trim((string) ($history_row['calificacion_original'] ?? ''));

    if ($calificacion_original !== '') {
      $display_grade = $calificacion_original;
    } elseif ($history_row['nota'] !== null && $history_row['nota'] !== '') {
      $display_grade = rtrim(rtrim(number_format((float) $history_row['nota'], 2, '.', ''), '0'), '.');
    } else {
      $display_grade = '—';
    }

    $grades_history_by_student[$id_alumno][$id_modulo][] = [
      'evaluacion' => (string) $history_row['evaluacion_nombre'],
      'nota' => $display_grade,
    ];
  }
}

if ($show_results && $students !== [] && $modules !== []) {
  if ($show_all_students) {
    $visible_students = $students;
  } else {
    foreach ($students as $student) {
      $id_alumno = (int) $student['id_alumno'];
      $has_grade_in_shown_modules = false;

      foreach ($modules as $module) {
        $id_modulo = (int) $module['id_modulo'];
        $display_grade = $grades_by_student[$id_alumno][$id_modulo] ?? '—';
        if ($display_grade !== '—') {
          $has_grade_in_shown_modules = true;
          break;
        }
      }

      if ($has_grade_in_shown_modules) {
        $visible_students[] = $student;
      }
    }
  }

  $visible_module_ids = [];
  foreach ($modules as $module) {
    $id_modulo = (int) $module['id_modulo'];
    foreach ($visible_students as $student) {
      $id_alumno = (int) $student['id_alumno'];
      $display_grade = $grades_by_student[$id_alumno][$id_modulo] ?? '—';
      if ($display_grade !== '—') {
        $visible_module_ids[$id_modulo] = true;
        break;
      }
    }
  }

  if ($visible_module_ids !== []) {
    $modules = array_values(array_filter(
      $modules,
      static fn (array $module): bool => isset($visible_module_ids[(int) $module['id_modulo']])
    ));
  } else {
    $modules = [];
  }

  if ($sort_by_nombre && $visible_students !== []) {
    usort($visible_students, static function (array $a, array $b) use ($sort_dir): int {
      $name_a = trim((string) ($a['apellido1'] ?? '')) . ' ' . trim((string) ($a['apellido2'] ?? '')) . ' ' . trim((string) ($a['nombre'] ?? ''));
      $name_b = trim((string) ($b['apellido1'] ?? '')) . ' ' . trim((string) ($b['apellido2'] ?? '')) . ' ' . trim((string) ($b['nombre'] ?? ''));
      $cmp = strcmp(mb_strtolower($name_a, 'UTF-8'), mb_strtolower($name_b, 'UTF-8'));
      return $sort_dir === 'desc' ? -$cmp : $cmp;
    });
  } elseif ($sort_modulo_id > 0 && $visible_students !== []) {
    usort($visible_students, static function (array $a, array $b) use ($grades_by_student, $sort_modulo_id, $sort_dir): int {
      $grade_a = $grades_by_student[(int) $a['id_alumno']][$sort_modulo_id] ?? '—';
      $grade_b = $grades_by_student[(int) $b['id_alumno']][$sort_modulo_id] ?? '—';
      $num_a = grade_sort_key($grade_a);
      $num_b = grade_sort_key($grade_b);
      if ($num_a === null && $num_b === null) {
        return 0;
      }
      if ($num_a === null) {
        return 1;
      }
      if ($num_b === null) {
        return -1;
      }
      $cmp = $num_a <=> $num_b;
      return $sort_dir === 'desc' ? -$cmp : $cmp;
    });
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
          <h1>Calificaciones</h1>
          <p class="subheading">Consulta las notas por curso escolar, grupo y evaluación.</p>
        </div>
        <div class="header-actions">
          <a class="primary-button" href="importar_calificaciones.php<?php echo (int) $selected_group > 0 ? '?id_grupo=' . (int) $selected_group : ''; ?>">Importar calificaciones</a>
        </div>
      </header>

      <?php
        $_tab_params_alumnos = [];
        if ($selected_course_id > 0) $_tab_params_alumnos['id_curso_escolar'] = $selected_course_id;
        if ($selected_group !== '') $_tab_params_alumnos['id_grupo'] = $selected_group;
        $_tab_qs_alumnos = $_tab_params_alumnos ? '?' . htmlspecialchars(http_build_query($_tab_params_alumnos), ENT_QUOTES, 'UTF-8') : '';

        $_tab_params_asistencia = [];
        if ($selected_course_id > 0) $_tab_params_asistencia['id_curso_escolar'] = $selected_course_id;
        if ($selected_group !== '') $_tab_params_asistencia['id_grupo'] = $selected_group;
        $_tab_qs_asistencia = $_tab_params_asistencia ? '?' . htmlspecialchars(http_build_query($_tab_params_asistencia), ENT_QUOTES, 'UTF-8') : '';
      ?>
      <nav class="tab-nav">
        <a class="tab-nav-link" href="alumnos.php<?php echo $_tab_qs_alumnos; ?>">Alumnos</a>
        <a class="tab-nav-link" href="asistencia.php<?php echo $_tab_qs_asistencia; ?>">Asistencia</a>
        <a class="tab-nav-link active" href="calificaciones.php">Calificaciones</a>
      </nav>

      <form class="topbar" method="get">
        <?php if ($show_all_students): ?>
          <input type="hidden" name="mostrar_todos" value="1">
        <?php endif; ?>
        <div class="topbar-actions entity-grid entity-grid--4">
          <label class="calendar-select">
            <select name="id_curso_escolar" onchange="this.form.submit()" aria-label="Curso escolar">
              <option value="">Selecciona curso escolar</option>
              <?php foreach ($courses as $course): ?>
                <option value="<?php echo (int) $course['id_curso_escolar']; ?>" <?php echo (int) $course['id_curso_escolar'] === $selected_course_id ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($course['curso_escolar'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </label>

          <label class="calendar-select">
            <select name="id_grupo" onchange="this.form.submit()" aria-label="Grupo">
              <option value="">Selecciona grupo</option>
              <?php foreach ($groups as $group): ?>
                <option value="<?php echo (int) $group['id_grupo']; ?>" <?php echo (string) $group['id_grupo'] === $selected_group ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($group['grupo'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </label>

          <label class="calendar-select">
            <select name="id_evaluacion" onchange="this.form.submit()" aria-label="Evaluación">
              <?php foreach ($evaluations as $evaluation): ?>
                <option value="<?php echo (int) $evaluation['id_evaluacion']; ?>" <?php echo (int) $evaluation['id_evaluacion'] === $selected_evaluation ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($evaluation['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </label>


          <div class="topbar-search">
            <span class="search-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="7"></circle>
                <line x1="16.65" y1="16.65" x2="21" y2="21"></line>
              </svg>
            </span>
            <input
              type="search"
              name="q"
              placeholder="Buscar por nombre o apellidos"
              aria-label="Buscar por nombre o apellidos"
              value="<?php echo htmlspecialchars($search_term, ENT_QUOTES, 'UTF-8'); ?>"
            >
          </div>
        </div>
      </form>

      <?php if ($show_results): ?>
        <section class="panel">
          <div class="panel-header panel-header-with-actions">
            <?php
              $selected_group_name = $selected_group !== '' ? trim((string) ($groups_by_id[(int) $selected_group] ?? '')) : '';
              if ($selected_group_name === '') {
                $selected_group_name = 'Sin grupo';
              }
              $selected_evaluation_name = trim((string) ($evaluation_name_by_id[$selected_evaluation] ?? ''));
            ?>
            <div>
              <h3>Tabla de calificaciones de <?php echo htmlspecialchars($selected_group_name, ENT_QUOTES, 'UTF-8'); ?> - <?php echo htmlspecialchars($selected_evaluation_name, ENT_QUOTES, 'UTF-8'); ?></h3>
              <p>Alumnado del contexto seleccionado y sus notas por módulo para la evaluación elegida.</p>
            </div>
            <div class="panel-header-actions">
              <a
                class="edit-toggle<?php echo $show_all_students ? ' is-active' : ''; ?>"
                href="calificaciones.php?<?php echo htmlspecialchars(http_build_query([
                  'id_curso_escolar' => $selected_course_id,
                  'id_grupo' => $selected_group,
                  'id_evaluacion' => $selected_evaluation,
                  'q' => $search_term,
                  'mostrar_todos' => $show_all_students ? '0' : '1',
                ]), ENT_QUOTES, 'UTF-8'); ?>"
              >
                Mostrar todos
              </a>
              <button type="button" class="edit-toggle" id="resaltar-notas">Resaltar</button>
              <a
                class="edit-toggle"
                href="calificaciones_analisis.php?<?php echo htmlspecialchars(http_build_query([
                  'id_curso_escolar' => $selected_course_id,
                  'id_grupo' => $selected_group,
                  'id_evaluacion' => $selected_evaluation,
                ]), ENT_QUOTES, 'UTF-8'); ?>"
              >
                Analizar
              </a>
            </div>
          </div>

          <div class="panel-grid">
            <table>
              <thead>
                <tr>
                  <th><a class="practice-link" href="<?php echo htmlspecialchars(build_order_url_nombre_cal($sort_col_raw, $sort_dir), ENT_QUOTES, 'UTF-8'); ?>">Apellidos y nombre<?php echo sort_ind_nombre_cal($sort_col_raw, $sort_dir); ?></a></th>
                  <?php foreach ($modules as $module): ?>
                    <?php
                      $module_code = trim((string) $module['codigo']);
                      if ($module_code === '') {
                        $module_code = trim((string) ($module['abreviatura'] ?? 'Módulo'));
                      }
                      $module_name = trim((string) ($module['materia_general'] ?? ''));
                      if ($module_name === '') {
                        $module_name = trim((string) ($module['materia_propia'] ?? ''));
                      }
                      $module_abbreviation = trim((string) ($module['abreviatura'] ?? ''));
                      $module_course = (int) ($module['id_curso'] ?? 0);
                      $module_course_label = $module_course > 0 ? $module_course . 'º' : '';
                      $module_title_parts = [$module_code];
                      if ($module_abbreviation !== '') {
                        $module_title_parts[] = $module_abbreviation;
                      }
                      if ($module_course_label !== '') {
                        $module_title_parts[] = $module_course_label;
                      }
                      $module_tooltip_title = implode(' - ', $module_title_parts);
                    ?>
                    <th>
                      <span
                        class="empresa-name-trigger empresa-name-trigger--practicas"
                        role="button"
                        tabindex="0"
                        aria-haspopup="dialog"
                        aria-expanded="false"
                        data-modulo-id="<?php echo (int) $module['id_modulo']; ?>"
                        data-modulo-nombre="<?php echo htmlspecialchars($module_name !== '' ? $module_name : $module_abbreviation, ENT_QUOTES, 'UTF-8'); ?>"
                        data-modulo-curso="<?php echo htmlspecialchars($module_course_label, ENT_QUOTES, 'UTF-8'); ?>"
                        data-modulo-codigo="<?php echo htmlspecialchars($module_code, ENT_QUOTES, 'UTF-8'); ?>"
                        data-modulo-abreviatura="<?php echo htmlspecialchars($module_abbreviation, ENT_QUOTES, 'UTF-8'); ?>"
                      ><a class="practice-link" href="<?php echo htmlspecialchars(build_order_url_cal((int) $module['id_modulo'], $sort_modulo_id, $sort_dir), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($module_code, ENT_QUOTES, 'UTF-8') . sort_ind_cal((int) $module['id_modulo'], $sort_modulo_id, $sort_dir); ?></a></span>
                    </th>
                  <?php endforeach; ?>
                </tr>
              </thead>
              <tbody>
                <?php if ($students === []): ?>
                  <tr>
                    <td colspan="<?php echo 1 + count($modules); ?>">No hay alumnos para los filtros seleccionados.</td>
                  </tr>
                <?php elseif ($modules === []): ?>
                  <tr>
                    <td>No hay módulos disponibles para el contexto seleccionado.</td>
                  </tr>
                <?php elseif ($visible_students === []): ?>
                  <tr>
                    <td colspan="<?php echo 1 + count($modules); ?>">No hay alumnos con calificaciones para los filtros seleccionados.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($visible_students as $student): ?>
                    <?php
                      $id_alumno = (int) $student['id_alumno'];
                      $apellido2 = trim((string) ($student['apellido2'] ?? ''));
                      $nombre_completo = trim((string) $student['apellido1'])
                        . ($apellido2 !== '' ? ' ' . $apellido2 : '')
                        . ', '
                        . trim((string) $student['nombre']);
                    ?>
                    <tr>
                      <td><span
                        class="alumno-name-trigger"
                        role="button"
                        tabindex="0"
                        aria-haspopup="dialog"
                        aria-expanded="false"
                        data-alumno-id="<?php echo $id_alumno; ?>"
                        data-alumno-nombre="<?php echo htmlspecialchars($nombre_completo, ENT_QUOTES, 'UTF-8'); ?>"
                        data-alumno-nia="<?php echo htmlspecialchars((string) ($student['alumno_nia'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                        data-alumno-dni="<?php echo htmlspecialchars((string) ($student['alumno_dni'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                        data-alumno-telefono="<?php echo htmlspecialchars((string) ($student['alumno_telefono'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                        data-alumno-correo-educamadrid="<?php echo htmlspecialchars((string) ($student['alumno_correo_educamadrid'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                        data-alumno-correo-personal="<?php echo htmlspecialchars((string) ($student['alumno_correo_personal'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                      ><?php echo htmlspecialchars($nombre_completo, ENT_QUOTES, 'UTF-8'); ?></span></td>
                      <?php foreach ($modules as $module): ?>
                        <?php
                          $id_modulo = (int) $module['id_modulo'];
                          $module_code = trim((string) $module['codigo']);
                          if ($module_code === '') {
                            $module_code = trim((string) ($module['abreviatura'] ?? 'Módulo'));
                          }
                          $module_name = trim((string) ($module['materia_general'] ?? ''));
                          if ($module_name === '') {
                            $module_name = trim((string) ($module['materia_propia'] ?? ''));
                          }
                          $module_abbreviation = trim((string) ($module['abreviatura'] ?? ''));
                          $module_course = (int) ($module['id_curso'] ?? 0);
                          $module_course_label = $module_course > 0 ? $module_course . 'º' : '';
                          $module_title_parts = [$module_code];
                          if ($module_abbreviation !== '') {
                            $module_title_parts[] = $module_abbreviation;
                          }
                          if ($module_course_label !== '') {
                            $module_title_parts[] = $module_course_label;
                          }
                          $module_tooltip_title = implode(' - ', $module_title_parts);
                          $display_grade = $grades_by_student[$id_alumno][$id_modulo] ?? '—';
                          $history_rows = $grades_history_by_student[$id_alumno][$id_modulo] ?? [];
                        ?>
                        <?php
                          $tooltip_title = $module_code;
                          if ($module_name !== '') {
                            $tooltip_title .= ' – ' . $module_name;
                          }
                        ?>
                        <td data-grade-cell="1">
                          <span class="help-tooltip">
                            <button type="button" class="nota-grade-trigger"><?php echo htmlspecialchars($display_grade, ENT_QUOTES, 'UTF-8'); ?></button>
                            <span class="help-tooltip-content" role="tooltip">
                              <span class="help-tooltip-title">
                                <?php echo htmlspecialchars($tooltip_title, ENT_QUOTES, 'UTF-8'); ?>
                              </span>
                              <?php if ($history_rows === []): ?>
                                <div>Sin calificaciones registradas.</div>
                              <?php else: ?>
                                <table>
                                  <thead>
                                    <tr>
                                      <th>Evaluación</th>
                                      <th>Nota</th>
                                    </tr>
                                  </thead>
                                  <tbody>
                                    <?php foreach ($history_rows as $history_row): ?>
                                      <tr>
                                        <td><?php echo htmlspecialchars($history_row['evaluacion'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($history_row['nota'], ENT_QUOTES, 'UTF-8'); ?></td>
                                      </tr>
                                    <?php endforeach; ?>
                                  </tbody>
                                </table>
                              <?php endif; ?>
                            </span>
                          </span>
                        </td>
                      <?php endforeach; ?>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>
      <?php endif; ?>
    </main>
  </div>
  <div class="modulo-tooltip" id="modulo-tooltip-cal" hidden>
    <span class="modulo-tooltip__name" id="modulo-tooltip-cal-name"></span>
  </div>

  <div class="practicas-ras-popover-layer" id="modulo-detail-layer-cal" hidden>
    <button type="button" class="practicas-ras-popover-backdrop" data-popover-close-cal tabindex="-1" aria-hidden="true"></button>
    <div class="practicas-ras-popover practicas-ras-popover--modulo" id="modulo-detail-popover-cal" role="dialog" aria-modal="false" aria-labelledby="modulo-detail-title-cal" hidden>
      <div class="practicas-ras-popover__header">
        <span class="practicas-ras-popover__eyebrow">Módulo</span>
        <span id="modulo-detail-title-cal" class="practicas-ras-popover__title"></span>
        <button type="button" class="practicas-ras-popover__close" data-popover-close-cal aria-label="Cerrar detalle del módulo">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14" aria-hidden="true">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>
      <ul class="practicas-ras-popover__criteria" id="modulo-detail-data-cal"></ul>
      <div class="practicas-ras-popover__footer">
        <a id="modulo-detail-link-cal" class="practicas-ras-popover__link" href="#">Ver módulo completo →</a>
      </div>
    </div>
  </div>

  <div class="practicas-ras-popover-layer" id="alumno-detail-layer" hidden>
    <button type="button" class="practicas-ras-popover-backdrop" data-popover-close tabindex="-1" aria-hidden="true"></button>
    <div class="practicas-ras-popover practicas-ras-popover--modulo practicas-ras-popover--empresa" id="alumno-detail-popover" role="dialog" aria-modal="false" aria-labelledby="alumno-detail-title" hidden>
      <div class="practicas-ras-popover__header">
        <span class="practicas-ras-popover__eyebrow">Alumno</span>
        <span id="alumno-detail-title" class="practicas-ras-popover__title"></span>
        <button type="button" class="practicas-ras-popover__close" data-popover-close aria-label="Cerrar detalle del alumno">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14" aria-hidden="true">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>
      <ul class="practicas-ras-popover__criteria" id="alumno-detail-data"></ul>
      <div class="practicas-ras-popover__footer">
        <a id="alumno-detail-link" class="practicas-ras-popover__link" href="#">Ver alumno completo →</a>
      </div>
    </div>
  </div>

  <script>
    (function () {
      const form = document.querySelector('.topbar');
      const searchInput = form ? form.querySelector('input[name="q"]') : null;
      let searchDebounceTimer = null;

      if (form && searchInput) {
        searchInput.addEventListener('input', () => {
          if (searchDebounceTimer) {
            window.clearTimeout(searchDebounceTimer);
          }

          searchDebounceTimer = window.setTimeout(() => {
            form.submit();
          }, 250);
        });
      }

      const tooltipContainers = document.querySelectorAll('.help-tooltip');

      const gap = 10;
      let activeTooltip = null;
      let activeContainer = null;

      tooltipContainers.forEach((container) => {
        const tooltip = container.querySelector('.help-tooltip-content');
        if (!tooltip) {
          return;
        }

        tooltip.style.position = 'fixed';
        tooltip.style.left = '0px';
        tooltip.style.top = '0px';
        tooltip.style.right = 'auto';
        tooltip.style.bottom = 'auto';
      });

      const hideTooltip = (tooltip) => {
        if (!tooltip) {
          return;
        }

        tooltip.style.right = 'auto';
        tooltip.style.bottom = 'auto';
      };

      const positionTooltip = (container) => {
        const tooltip = container.querySelector('.help-tooltip-content');
        if (!tooltip) {
          return;
        }

        tooltip.style.position = 'fixed';
        tooltip.style.left = '0px';
        tooltip.style.top = '0px';
        tooltip.style.right = 'auto';
        tooltip.style.bottom = 'auto';

        const trigger = container.firstElementChild;
        const anchorRect = (trigger || container).getBoundingClientRect();
        const tooltipRect = tooltip.getBoundingClientRect();
        const viewportWidth = window.innerWidth || document.documentElement.clientWidth;
        const viewportHeight = window.innerHeight || document.documentElement.clientHeight;

        let left = anchorRect.left;
        let top = anchorRect.bottom + gap;

        if (left + tooltipRect.width > viewportWidth - gap) {
          left = anchorRect.right - tooltipRect.width;
        }

        if (top + tooltipRect.height > viewportHeight - gap) {
          top = anchorRect.top - tooltipRect.height - gap;
        }

        left = Math.max(gap, Math.min(left, viewportWidth - tooltipRect.width - gap));
        top = Math.max(gap, Math.min(top, viewportHeight - tooltipRect.height - gap));

        tooltip.style.left = `${left}px`;
        tooltip.style.top = `${top}px`;
      };

      const showTooltip = (container) => {
        const tooltip = container.querySelector('.help-tooltip-content');
        if (!tooltip) {
          return;
        }

        activeContainer = container;
        activeTooltip = tooltip;
        positionTooltip(container);
      };

      const clearActiveTooltip = (container) => {
        const tooltip = container.querySelector('.help-tooltip-content');
        if (!tooltip) {
          return;
        }

        hideTooltip(tooltip);

        if (activeContainer === container) {
          activeContainer = null;
          activeTooltip = null;
        }
      };

      tooltipContainers.forEach((container) => {
        container.addEventListener('mouseenter', () => showTooltip(container));
        container.addEventListener('focusin', () => showTooltip(container));
        container.addEventListener('mouseleave', () => clearActiveTooltip(container));
        container.addEventListener('focusout', (event) => {
          const nextTarget = event.relatedTarget;
          if (!nextTarget || !container.contains(nextTarget)) {
            clearActiveTooltip(container);
          }
        });
      });

      window.addEventListener('resize', () => {
        if (activeContainer && activeTooltip) {
          positionTooltip(activeContainer);
        }
      });

      window.addEventListener('scroll', () => {
        if (activeContainer && activeTooltip) {
          positionTooltip(activeContainer);
        }
      }, { passive: true });

      const resaltarNotasButton = document.getElementById('resaltar-notas');
      const gradeCells = document.querySelectorAll('td[data-grade-cell="1"]');
      const gradesTable = gradeCells.length > 0 ? gradeCells[0].closest('table') : null;
      const numericGradePattern = /^\d+(?:\.\d+)?$/;
      let highlightEnabled = false;

      const clearHighlight = () => {
        gradeCells.forEach((cell) => {
          cell.classList.remove('generation-feedback-success', 'generation-feedback-error');
        });
      };

      const applyHighlight = () => {
        clearHighlight();
        gradeCells.forEach((cell) => {
          const gradeElement = cell.querySelector('.nota-grade-trigger');
          const gradeText = gradeElement ? gradeElement.textContent.trim() : '';
          if (!numericGradePattern.test(gradeText)) {
            return;
          }

          const numericGrade = Number.parseFloat(gradeText);
          if (Number.isNaN(numericGrade)) {
            return;
          }

          if (numericGrade < 5) {
            cell.classList.add('generation-feedback-error');
          } else {
            cell.classList.add('generation-feedback-success');
          }
        });
      };

      if (resaltarNotasButton && gradeCells.length > 0) {
        resaltarNotasButton.addEventListener('click', () => {
          highlightEnabled = !highlightEnabled;
          resaltarNotasButton.classList.toggle('is-active', highlightEnabled);
          if (gradesTable) {
            gradesTable.classList.toggle('grade-highlight-active', highlightEnabled);
          }

          if (highlightEnabled) {
            applyHighlight();
            return;
          }

          clearHighlight();
        });
      }
    })();

    (function () {
      const modTooltip = document.getElementById('modulo-tooltip-cal');
      const modTooltipName = document.getElementById('modulo-tooltip-cal-name');
      const modLayer = document.getElementById('modulo-detail-layer-cal');
      const modPopover = document.getElementById('modulo-detail-popover-cal');
      const modTitle = document.getElementById('modulo-detail-title-cal');
      const modDetailLink = document.getElementById('modulo-detail-link-cal');
      const modDetailList = document.getElementById('modulo-detail-data-cal');
      const modThead = document.querySelector('.panel-grid table thead');

      if (!modThead || !modTooltip) {
        return;
      }

      let modActiveTrigger = null;
      let modTooltipVisible = false;

      const setPopoverPosition = (trigger) => {
        const triggerRect = trigger.getBoundingClientRect();
        const popoverRect = modPopover.getBoundingClientRect();
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
        modPopover.style.top = `${Math.max(gutter, top)}px`;
        modPopover.style.left = `${Math.max(gutter, left)}px`;
      };

      const closePopover = () => {
        modPopover.hidden = true;
        modLayer.hidden = true;
        if (modActiveTrigger) {
          modActiveTrigger.setAttribute('aria-expanded', 'false');
        }
        modActiveTrigger = null;
      };

      const getVal = (v) => ((v || '').trim() !== '' ? (v || '').trim() : 'No disponible');

      const addInfoItem = (label, value) => {
        const item = document.createElement('li');
        const strong = document.createElement('strong');
        strong.textContent = label;
        item.appendChild(strong);
        const valueSpan = document.createElement('span');
        valueSpan.textContent = value;
        item.appendChild(valueSpan);
        modDetailList.appendChild(item);
      };

      const modEyebrow = modPopover.querySelector('.practicas-ras-popover__eyebrow');

      const openPopover = (trigger) => {
        if (modActiveTrigger && modActiveTrigger !== trigger) {
          modActiveTrigger.setAttribute('aria-expanded', 'false');
        }
        if (modEyebrow) { modEyebrow.textContent = 'Módulo'; }
        modTitle.textContent = trigger.dataset.moduloNombre || 'Módulo';
        const moduloId = (trigger.dataset.moduloId || '').trim();
        if (modDetailLink) {
          modDetailLink.setAttribute('href', moduloId !== '' ? `modulo_detalle.php?id_modulo=${encodeURIComponent(moduloId)}` : '#');
        }
        modDetailList.innerHTML = '';
        addInfoItem('Código', getVal(trigger.dataset.moduloCodigo));
        addInfoItem('Abreviatura', getVal(trigger.dataset.moduloAbreviatura));
        addInfoItem('Curso', getVal(trigger.dataset.moduloCurso));
        addInfoItem('Nombre', getVal(trigger.dataset.moduloNombre));
        modActiveTrigger = trigger;
        trigger.setAttribute('aria-expanded', 'true');
        modLayer.hidden = false;
        modPopover.hidden = false;
        setPopoverPosition(trigger);
      };

      const updateTooltipPosition = (x, y) => {
        const offset = 14;
        let left = x + offset;
        let top = y + offset;
        const w = modTooltip.offsetWidth || 200;
        const h = modTooltip.offsetHeight || 60;
        if (left + w > window.innerWidth - 8) { left = x - w - offset; }
        if (top + h > window.innerHeight - 8) { top = y - h - offset; }
        modTooltip.style.left = `${Math.max(8, left)}px`;
        modTooltip.style.top = `${Math.max(8, top)}px`;
      };

      modThead.addEventListener('mouseover', (event) => {
        const trigger = event.target.closest('.empresa-name-trigger--practicas');
        if (trigger && modThead.contains(trigger) && modPopover.hidden) {
          modTooltipName.textContent = trigger.dataset.moduloNombre || trigger.textContent.trim();
          modTooltip.hidden = false;
          modTooltipVisible = true;
          updateTooltipPosition(event.clientX, event.clientY);
        } else if (modTooltipVisible && !event.target.closest('.empresa-name-trigger--practicas')) {
          modTooltip.hidden = true;
          modTooltipVisible = false;
        }
      });

      modThead.addEventListener('mousemove', (event) => {
        if (modTooltipVisible) {
          updateTooltipPosition(event.clientX, event.clientY);
        }
      });

      modThead.addEventListener('mouseleave', () => {
        modTooltip.hidden = true;
        modTooltipVisible = false;
      });

      modThead.addEventListener('click', (event) => {
        const trigger = event.target.closest('.empresa-name-trigger--practicas');
        if (!trigger || !modThead.contains(trigger)) {
          return;
        }
        if (event.target.closest('a')) {
          return;
        }
        modTooltip.hidden = true;
        modTooltipVisible = false;
        if (modActiveTrigger === trigger && !modPopover.hidden) {
          closePopover();
          return;
        }
        openPopover(trigger);
      });

      modThead.addEventListener('keydown', (event) => {
        const trigger = event.target.closest('.empresa-name-trigger--practicas');
        if (trigger && modThead.contains(trigger) && (event.key === 'Enter' || event.key === ' ')) {
          event.preventDefault();
          trigger.click();
        }
      });

      modLayer.querySelectorAll('[data-popover-close-cal]').forEach((el) => {
        el.addEventListener('click', closePopover);
      });

      window.addEventListener('resize', () => {
        if (modActiveTrigger && !modPopover.hidden) {
          setPopoverPosition(modActiveTrigger);
        }
      });

      window.addEventListener('scroll', () => {
        if (modActiveTrigger && !modPopover.hidden) {
          setPopoverPosition(modActiveTrigger);
        }
      }, true);

      document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modPopover.hidden) {
          closePopover();
        }
      });
    })();
  </script>
  <script>
    const tableBody = document.querySelector('tbody');
    const alumnoLayer = document.getElementById('alumno-detail-layer');
    const alumnoPopover = document.getElementById('alumno-detail-popover');
    const alumnoTitle = document.getElementById('alumno-detail-title');
    const alumnoDetailList = document.getElementById('alumno-detail-data');
    const alumnoDetailLink = document.getElementById('alumno-detail-link');
    let activeAlumnoTrigger = null;

    if (alumnoLayer && alumnoPopover && alumnoTitle && alumnoDetailList) {
      const setAlumnoPopoverPosition = (trigger) => {
        const triggerRect = trigger.getBoundingClientRect();
        const popoverRect = alumnoPopover.getBoundingClientRect();
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

        alumnoPopover.style.top = `${Math.max(gutter, top)}px`;
        alumnoPopover.style.left = `${Math.max(gutter, left)}px`;
      };

      const closeAlumnoPopover = () => {
        alumnoPopover.hidden = true;
        alumnoLayer.hidden = true;
        if (activeAlumnoTrigger) {
          activeAlumnoTrigger.setAttribute('aria-expanded', 'false');
        }
        activeAlumnoTrigger = null;
      };

      const getAlumnoValueOrFallback = (value) => {
        const normalized = (value || '').trim();
        return normalized !== '' ? normalized : 'No disponible';
      };

      const addAlumnoInfoItem = (label, value) => {
        const item = document.createElement('li');
        const strong = document.createElement('strong');
        strong.textContent = label;
        item.appendChild(strong);
        const valueSpan = document.createElement('span');
        valueSpan.textContent = value;
        item.appendChild(valueSpan);
        alumnoDetailList.appendChild(item);
      };

      const addAlumnoCopyItem = (label, value) => {
        const item = document.createElement('li');
        const strong = document.createElement('strong');
        strong.textContent = label;
        item.appendChild(strong);
        const valueSpan = document.createElement('span');
        if (value !== '') {
          const trigger = document.createElement('span');
          trigger.className = 'copy-trigger';
          trigger.dataset.copy = value;
          trigger.textContent = value;
          valueSpan.appendChild(trigger);
        } else {
          valueSpan.textContent = 'No disponible';
        }
        item.appendChild(valueSpan);
        alumnoDetailList.appendChild(item);
      };

      const openAlumnoPopover = (trigger) => {
        if (activeAlumnoTrigger && activeAlumnoTrigger !== trigger) {
          activeAlumnoTrigger.setAttribute('aria-expanded', 'false');
        }

        alumnoTitle.textContent = trigger.dataset.alumnoNombre || 'Alumno';

        if (alumnoDetailLink) {
          const alumnoId = (trigger.dataset.alumnoId || '').trim();
          alumnoDetailLink.setAttribute('href', alumnoId !== '' ? `alumno_detalle.php?id_alumno=${encodeURIComponent(alumnoId)}` : '#');
        }

        alumnoDetailList.innerHTML = '';
        addAlumnoInfoItem('NIA', getAlumnoValueOrFallback(trigger.dataset.alumnoNia));
        addAlumnoInfoItem('DNI', getAlumnoValueOrFallback(trigger.dataset.alumnoDni));
        addAlumnoCopyItem('EducaMadrid', (trigger.dataset.alumnoCorreoEducamadrid || '').trim());
        addAlumnoCopyItem('Correo personal', (trigger.dataset.alumnoCorreoPersonal || '').trim());
        addAlumnoCopyItem('Teléfono', (trigger.dataset.alumnoTelefono || '').trim());

        activeAlumnoTrigger = trigger;
        trigger.setAttribute('aria-expanded', 'true');
        alumnoLayer.hidden = false;
        alumnoPopover.hidden = false;
        setAlumnoPopoverPosition(trigger);
      };

      tableBody.addEventListener('click', (event) => {
        const trigger = event.target.closest('.alumno-name-trigger');
        if (trigger && tableBody.contains(trigger)) {
          if (activeAlumnoTrigger === trigger && !alumnoPopover.hidden) {
            closeAlumnoPopover();
            return;
          }
          openAlumnoPopover(trigger);
          return;
        }
      });

      tableBody.addEventListener('keydown', (event) => {
        const trigger = event.target.closest('.alumno-name-trigger');
        if (trigger && tableBody.contains(trigger) && (event.key === 'Enter' || event.key === ' ')) {
          event.preventDefault();
          trigger.click();
        }
      });

      alumnoLayer.querySelectorAll('[data-popover-close]').forEach((element) => {
        element.addEventListener('click', closeAlumnoPopover);
      });

      window.addEventListener('resize', () => {
        if (activeAlumnoTrigger && !alumnoPopover.hidden) {
          setAlumnoPopoverPosition(activeAlumnoTrigger);
        }
      });

      window.addEventListener('scroll', () => {
        if (activeAlumnoTrigger && !alumnoPopover.hidden) {
          setAlumnoPopoverPosition(activeAlumnoTrigger);
        }
      }, true);

      document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !alumnoPopover.hidden) {
          closeAlumnoPopover();
        }
      });
    }
  </script>
  <script src="assets/copy.js"></script>
</body>
</html>
