<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pdo = db();

$page_title = 'Calificaciones | Gestor de Alumnos';
$active_page = 'calificaciones';

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

$selected_group = (string) ($_GET['id_grupo'] ?? '');
$selected_group = ctype_digit($selected_group) ? $selected_group : '';

$search_term = trim((string) ($_GET['q'] ?? ''));
$show_all_students = isset($_GET['mostrar_todos']) && (string) $_GET['mostrar_todos'] === '1';

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
       g.grupo
     FROM alumno_curso ac
     INNER JOIN alumnos a ON a.id_alumno = ac.id_alumno
     LEFT JOIN grupos g ON g.id_grupo = ac.id_grupo
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
      </header>

      <form class="topbar" method="get">
        <?php if ($show_all_students): ?>
          <input type="hidden" name="mostrar_todos" value="1">
        <?php endif; ?>
        <div class="topbar-actions entity-grid entity-grid--4">
          <label class="calendar-select">
            <select name="id_curso_escolar" onchange="this.form.submit()" aria-label="Curso escolar">
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
            Analizar resultados
          </a>
        </div>
      </form>

      <?php if ($show_results): ?>
        <section class="panel">
          <div class="panel-header">
            <?php
              $selected_group_name = $selected_group !== '' ? trim((string) ($groups_by_id[(int) $selected_group] ?? '')) : '';
              if ($selected_group_name === '') {
                $selected_group_name = 'Sin grupo';
              }
              $selected_evaluation_name = trim((string) ($evaluation_name_by_id[$selected_evaluation] ?? ''));
            ?>
            <h3>Tabla de calificaciones de <?php echo htmlspecialchars($selected_group_name, ENT_QUOTES, 'UTF-8'); ?> - <?php echo htmlspecialchars($selected_evaluation_name, ENT_QUOTES, 'UTF-8'); ?></h3>
            <p>Alumnado del contexto seleccionado y sus notas por módulo para la evaluación elegida.</p>
          </div>

          <div class="panel-grid">
            <table>
              <thead>
                <tr>
                  <th>Apellidos y nombre</th>
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
                      <span class="help-tooltip">
                        <span tabindex="0"><?php echo htmlspecialchars($module_code, ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="help-tooltip-content" role="tooltip">
                          <span class="help-tooltip-title">
                            <?php echo htmlspecialchars($module_tooltip_title, ENT_QUOTES, 'UTF-8'); ?>
                          </span>
                          <div>
                            <?php if ($module_name !== ''): ?>
                              <div><?php echo htmlspecialchars($module_name, ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php endif; ?>
                          </div>
                        </span>
                      </span>
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
                      <td><a class="practice-link" href="alumno_detalle.php?id_alumno=<?php echo (int) $id_alumno; ?>"><?php echo htmlspecialchars($nombre_completo, ENT_QUOTES, 'UTF-8'); ?></a></td>
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
                        <td data-grade-cell="1">
                          <span class="help-tooltip">
                            <span tabindex="0"><?php echo htmlspecialchars($display_grade, ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class="help-tooltip-content" role="tooltip">
                              <span class="help-tooltip-title">
                                <?php echo htmlspecialchars($module_tooltip_title, ENT_QUOTES, 'UTF-8'); ?>
                              </span>
                              <?php if ($module_name !== ''): ?>
                                <div><?php echo htmlspecialchars($module_name, ENT_QUOTES, 'UTF-8'); ?></div>
                              <?php endif; ?>
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
      if (!tooltipContainers.length) {
        return;
      }

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
          const gradeElement = cell.querySelector('.help-tooltip > span[tabindex]');
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
  </script>
</body>
</html>
