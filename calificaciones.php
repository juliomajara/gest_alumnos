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
     ORDER BY g.grupo, a.apellido1, a.apellido2, a.nombre';
  $students_stmt = $pdo->prepare($students_sql);
  $students_stmt->execute([
    'id_curso_escolar' => $selected_course_id,
    'id_grupo' => (int) $selected_group,
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
        <div class="topbar-actions entity-grid entity-grid--3">
          <label class="calendar-select">
            <span class="calendar-select-label">Curso escolar</span>
            <select name="id_curso_escolar" onchange="this.form.submit()">
              <?php foreach ($courses as $course): ?>
                <option value="<?php echo (int) $course['id_curso_escolar']; ?>" <?php echo (int) $course['id_curso_escolar'] === $selected_course_id ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($course['curso_escolar'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </label>

          <label class="calendar-select">
            <span class="calendar-select-label">Grupo</span>
            <select name="id_grupo" onchange="this.form.submit()">
              <option value="">Selecciona grupo</option>
              <?php foreach ($groups as $group): ?>
                <option value="<?php echo (int) $group['id_grupo']; ?>" <?php echo (string) $group['id_grupo'] === $selected_group ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($group['grupo'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </label>

          <label class="calendar-select">
            <span class="calendar-select-label">Evaluación</span>
            <select name="id_evaluacion" onchange="this.form.submit()">
              <?php foreach ($evaluations as $evaluation): ?>
                <option value="<?php echo (int) $evaluation['id_evaluacion']; ?>" <?php echo (int) $evaluation['id_evaluacion'] === $selected_evaluation ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($evaluation['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </label>
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
                    ?>
                    <th>
                      <span class="help-tooltip">
                        <span tabindex="0"><?php echo htmlspecialchars($module_code, ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="help-tooltip-content" role="tooltip">
                          <span class="help-tooltip-title">
                            <?php echo htmlspecialchars($module_code, ENT_QUOTES, 'UTF-8'); ?>
                            <?php if ($module_abbreviation !== ''): ?>
                              <?php echo ' ' . htmlspecialchars($module_abbreviation, ENT_QUOTES, 'UTF-8'); ?>
                            <?php endif; ?>
                            <?php if ($module_course_label !== ''): ?>
                              <?php echo ' (' . htmlspecialchars($module_course_label, ENT_QUOTES, 'UTF-8') . ')'; ?>
                            <?php endif; ?>
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
                <?php else: ?>
                  <?php foreach ($students as $student): ?>
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
                          $display_grade = $grades_by_student[$id_alumno][$id_modulo] ?? '—';
                          $history_rows = $grades_history_by_student[$id_alumno][$id_modulo] ?? [];
                        ?>
                        <td>
                          <span class="help-tooltip">
                            <span tabindex="0"><?php echo htmlspecialchars($display_grade, ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class="help-tooltip-content" role="tooltip">
                              <span class="help-tooltip-title">Detalle por evaluación</span>
                              <div><strong>Código:</strong> <?php echo htmlspecialchars($module_code, ENT_QUOTES, 'UTF-8'); ?></div>
                              <?php if ($module_name !== ''): ?>
                                <div><strong>Módulo:</strong> <?php echo htmlspecialchars($module_name, ENT_QUOTES, 'UTF-8'); ?></div>
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
</body>
</html>
