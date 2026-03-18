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

$normal_evaluation_names = [
  '1ª evaluación',
  '2ª evaluación',
  'Evaluación ordinaria',
  'Evaluación extraordinaria',
];
$normal_placeholders = implode(',', array_fill(0, count($normal_evaluation_names), '?'));

$latest_normal_evaluation_stmt = $pdo->prepare(
  'SELECT c.id_evaluacion
   FROM calificaciones c
   INNER JOIN evaluaciones e ON e.id_evaluacion = c.id_evaluacion
   WHERE e.nombre IN (' . $normal_placeholders . ')
   ORDER BY c.fecha_importacion DESC, c.id_calificacion DESC
   LIMIT 1'
);
$latest_normal_evaluation_stmt->execute($normal_evaluation_names);
$latest_normal_evaluation_id = (int) ($latest_normal_evaluation_stmt->fetchColumn() ?: 0);

if ($latest_normal_evaluation_id <= 0) {
  $fallback_normal_evaluation_stmt = $pdo->prepare(
    'SELECT id_evaluacion
     FROM evaluaciones
     WHERE nombre IN (' . $normal_placeholders . ')
     ORDER BY id_evaluacion DESC
     LIMIT 1'
  );
  $fallback_normal_evaluation_stmt->execute($normal_evaluation_names);
  $latest_normal_evaluation_id = (int) ($fallback_normal_evaluation_stmt->fetchColumn() ?: 0);
}

$evaluations = $pdo->query('SELECT id_evaluacion, nombre FROM evaluaciones ORDER BY id_evaluacion')->fetchAll();
if ($latest_normal_evaluation_id <= 0 && $evaluations !== []) {
  $latest_normal_evaluation_id = (int) $evaluations[0]['id_evaluacion'];
}

$selected_course_id = isset($_GET['id_curso_escolar']) && ctype_digit((string) $_GET['id_curso_escolar'])
  ? (int) $_GET['id_curso_escolar']
  : $active_course_id;

$selected_group = (string) ($_GET['id_grupo'] ?? '');
$selected_group = ctype_digit($selected_group) ? $selected_group : '';

$selected_student = (string) ($_GET['id_alumno'] ?? '');
$selected_student = ctype_digit($selected_student) ? $selected_student : '';

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

$students_filter_sql =
  'SELECT a.id_alumno, a.apellido1, a.apellido2, a.nombre
   FROM alumno_curso ac
   INNER JOIN alumnos a ON a.id_alumno = ac.id_alumno
   WHERE ac.id_curso_escolar = :id_curso_escolar';
$students_filter_params = [
  'id_curso_escolar' => $selected_course_id,
];

if ($selected_group !== '') {
  $students_filter_sql .= ' AND ac.id_grupo = :id_grupo';
  $students_filter_params['id_grupo'] = (int) $selected_group;
}

$students_filter_sql .= ' ORDER BY a.apellido1, a.apellido2, a.nombre';

$students_filter_stmt = $pdo->prepare($students_filter_sql);
$students_filter_stmt->execute($students_filter_params);
$student_options = $students_filter_stmt->fetchAll();

$evaluation_ids = array_map(static fn (array $evaluation): int => (int) $evaluation['id_evaluacion'], $evaluations);
if (!in_array($selected_evaluation, $evaluation_ids, true)) {
  $selected_evaluation = $latest_normal_evaluation_id;
}

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
   WHERE ac.id_curso_escolar = :id_curso_escolar';
$students_params = [
  'id_curso_escolar' => $selected_course_id,
];

if ($selected_group !== '') {
  $students_sql .= ' AND ac.id_grupo = :id_grupo';
  $students_params['id_grupo'] = (int) $selected_group;
}

if ($selected_student !== '') {
  $students_sql .= ' AND ac.id_alumno = :id_alumno';
  $students_params['id_alumno'] = (int) $selected_student;
}

$students_sql .= ' ORDER BY g.grupo, a.apellido1, a.apellido2, a.nombre';

$students_stmt = $pdo->prepare($students_sql);
$students_stmt->execute($students_params);
$students = $students_stmt->fetchAll();

$modules_sql =
  'SELECT DISTINCT
     m.id_modulo,
     m.codigo,
     m.abreviatura,
     m.materia_general,
     m.materia_propia
   FROM modulos m
   INNER JOIN alumno_curso ac
     ON ac.id_ciclo = m.id_ciclo
    AND ac.id_curso = m.id_curso
   WHERE ac.id_curso_escolar = :id_curso_escolar';
$modules_params = [
  'id_curso_escolar' => $selected_course_id,
];

if ($selected_group !== '') {
  $modules_sql .= ' AND ac.id_grupo = :id_grupo';
  $modules_params['id_grupo'] = (int) $selected_group;
}

if ($selected_student !== '') {
  $modules_sql .= ' AND ac.id_alumno = :id_alumno';
  $modules_params['id_alumno'] = (int) $selected_student;
}

$modules_sql .= ' ORDER BY m.id_ciclo, m.id_curso, m.codigo, m.id_modulo';

$modules_stmt = $pdo->prepare($modules_sql);
$modules_stmt->execute($modules_params);
$modules = $modules_stmt->fetchAll();

$grades_by_student = [];
if ($students !== [] && $modules !== [] && $selected_evaluation > 0) {
  $grade_filters = ['c.id_curso_escolar = :id_curso_escolar', 'c.id_evaluacion = :id_evaluacion'];
  $grade_params = [
    'id_curso_escolar' => $selected_course_id,
    'id_evaluacion' => $selected_evaluation,
  ];

  if ($selected_group !== '') {
    $grade_filters[] = 'c.id_grupo = :id_grupo';
    $grade_params['id_grupo'] = (int) $selected_group;
  }

  if ($selected_student !== '') {
    $grade_filters[] = 'c.id_alumno = :id_alumno';
    $grade_params['id_alumno'] = (int) $selected_student;
  }

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
          <p class="subheading">Consulta las notas por curso escolar, grupo, alumno y evaluación.</p>
        </div>
      </header>

      <form class="topbar" method="get">
        <div class="topbar-actions">
          <label class="calendar-select">
            <select name="id_curso_escolar" onchange="this.form.submit()">
              <?php foreach ($courses as $course): ?>
                <option value="<?php echo (int) $course['id_curso_escolar']; ?>" <?php echo (int) $course['id_curso_escolar'] === $selected_course_id ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($course['curso_escolar'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </label>

          <label class="calendar-select">
            <select name="id_grupo" onchange="this.form.submit()">
              <option value="">Todos los grupos</option>
              <?php foreach ($groups as $group): ?>
                <option value="<?php echo (int) $group['id_grupo']; ?>" <?php echo (string) $group['id_grupo'] === $selected_group ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($group['grupo'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </label>

          <label class="calendar-select">
            <select name="id_alumno" onchange="this.form.submit()">
              <option value="">Todos los alumnos</option>
              <?php foreach ($student_options as $student): ?>
                <?php
                  $apellido2 = trim((string) ($student['apellido2'] ?? ''));
                  $nombre_completo = trim((string) $student['apellido1'])
                    . ($apellido2 !== '' ? ' ' . $apellido2 : '')
                    . ', '
                    . trim((string) $student['nombre']);
                ?>
                <option value="<?php echo (int) $student['id_alumno']; ?>" <?php echo (string) $student['id_alumno'] === $selected_student ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($nombre_completo, ENT_QUOTES, 'UTF-8'); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </label>

          <label class="calendar-select">
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

      <section class="panel">
        <div class="panel-header">
          <h3>Tabla de calificaciones</h3>
          <p>Alumnado del contexto seleccionado y sus notas por módulo para la evaluación elegida.</p>
        </div>

        <div class="panel-grid">
          <table>
            <thead>
              <tr>
                <th>Grupo</th>
                <th>Apellidos y nombre</th>
                <?php foreach ($modules as $module): ?>
                  <?php
                    $module_label = trim((string) $module['codigo']);
                    $module_name = trim((string) ($module['materia_general'] ?? ''));
                    if ($module_name === '') {
                      $module_name = trim((string) ($module['materia_propia'] ?? ''));
                    }
                    if ($module_label !== '' && $module_name !== '') {
                      $module_label .= ' · ' . $module_name;
                    } elseif ($module_name !== '') {
                      $module_label = $module_name;
                    } elseif ($module_label === '') {
                      $module_label = trim((string) ($module['abreviatura'] ?? 'Módulo'));
                    }
                  ?>
                  <th><?php echo htmlspecialchars($module_label, ENT_QUOTES, 'UTF-8'); ?></th>
                <?php endforeach; ?>
              </tr>
            </thead>
            <tbody>
              <?php if ($students === []): ?>
                <tr>
                  <td colspan="<?php echo 2 + count($modules); ?>">No hay alumnos para los filtros seleccionados.</td>
                </tr>
              <?php elseif ($modules === []): ?>
                <tr>
                  <td colspan="2">No hay módulos disponibles para el contexto seleccionado.</td>
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
                    $grupo = trim((string) ($student['grupo'] ?? ''));
                    if ($grupo === '') {
                      $grupo = 'Sin grupo';
                    }
                  ?>
                  <tr>
                    <td><?php echo htmlspecialchars($grupo, ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($nombre_completo, ENT_QUOTES, 'UTF-8'); ?></td>
                    <?php foreach ($modules as $module): ?>
                      <?php
                        $id_modulo = (int) $module['id_modulo'];
                        $display_grade = $grades_by_student[$id_alumno][$id_modulo] ?? '—';
                      ?>
                      <td><?php echo htmlspecialchars($display_grade, ENT_QUOTES, 'UTF-8'); ?></td>
                    <?php endforeach; ?>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>
    </main>
  </div>
</body>
</html>
