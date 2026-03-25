<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pdo = db();

$page_title = 'Asistencia mensual por alumno | Gestor de Alumnos';
$active_page = 'configuracion';

$errors = [];

$active_course_id = (int) ($pdo->query('SELECT id_curso_escolar FROM cursos_escolares WHERE activo = 1 ORDER BY id_curso_escolar DESC LIMIT 1')->fetchColumn() ?: 0);

$selected = [
  'id_curso_escolar' => (int) ($_GET['id_curso_escolar'] ?? $active_course_id),
  'id_ciclo' => 0,
  'id_curso' => 0,
  'id_grupo' => (int) ($_GET['id_grupo'] ?? 0),
];

$cursos_escolares = $pdo->query('SELECT id_curso_escolar, curso_escolar FROM cursos_escolares ORDER BY activo DESC, id_curso_escolar DESC')->fetchAll(PDO::FETCH_ASSOC);
$grupos = $pdo->query('SELECT id_grupo, id_ciclo, id_curso, grupo FROM grupos ORDER BY grupo')->fetchAll(PDO::FETCH_ASSOC);
$meses = $pdo->query('SELECT id_mes, mes, orden FROM meses ORDER BY orden, id_mes')->fetchAll(PDO::FETCH_ASSOC);

$students = [];
$attendance_rows = [];

if ($selected['id_curso_escolar'] > 0 && $selected['id_grupo'] > 0) {
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

  if ($errors === []) {
    $students_stmt = $pdo->prepare(
      'SELECT a.id_alumno, a.apellido1, a.apellido2, a.nombre
       FROM alumno_curso ac
       INNER JOIN alumnos a ON a.id_alumno = ac.id_alumno
       WHERE ac.id_curso_escolar = :id_curso_escolar
         AND ac.id_ciclo = :id_ciclo
         AND ac.id_curso = :id_curso
         AND ac.id_grupo = :id_grupo
       ORDER BY a.apellido1, a.apellido2, a.nombre'
    );
    $students_stmt->execute([
      'id_curso_escolar' => $selected['id_curso_escolar'],
      'id_ciclo' => $selected['id_ciclo'],
      'id_curso' => $selected['id_curso'],
      'id_grupo' => $selected['id_grupo'],
    ]);
    $students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);

    $attendance_stmt = $pdo->prepare(
      'SELECT am.id_alumno,
              am.id_mes,
              SUM(am.faltas_justificadas) AS faltas_justificadas,
              SUM(am.faltas_injustificadas) AS faltas_injustificadas,
              SUM(am.retrasos) AS retrasos
       FROM asistencia_mensual am
       INNER JOIN alumno_curso ac ON ac.id_alumno = am.id_alumno
       WHERE am.id_curso_escolar = :id_curso_escolar
         AND ac.id_curso_escolar = :id_curso_escolar_ac
         AND ac.id_ciclo = :id_ciclo
         AND ac.id_curso = :id_curso
         AND ac.id_grupo = :id_grupo
       GROUP BY am.id_alumno, am.id_mes'
    );
    $attendance_stmt->execute([
      'id_curso_escolar' => $selected['id_curso_escolar'],
      'id_curso_escolar_ac' => $selected['id_curso_escolar'],
      'id_ciclo' => $selected['id_ciclo'],
      'id_curso' => $selected['id_curso'],
      'id_grupo' => $selected['id_grupo'],
    ]);
    $attendance_rows = $attendance_stmt->fetchAll(PDO::FETCH_ASSOC);
  }
}

$attendance_index = [];
foreach ($attendance_rows as $row) {
  $id_alumno = (int) $row['id_alumno'];
  $id_mes = (int) $row['id_mes'];

  if (!isset($attendance_index[$id_alumno])) {
    $attendance_index[$id_alumno] = [];
  }

  $attendance_index[$id_alumno][$id_mes] = [
    'faltas_justificadas' => (int) $row['faltas_justificadas'],
    'faltas_injustificadas' => (int) $row['faltas_injustificadas'],
    'retrasos' => (int) $row['retrasos'],
  ];
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
          <h1>Asistencia mensual por alumno</h1>
          <p class="subheading">Consulta faltas justificadas, injustificadas y retrasos por alumno y mes.</p>
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

      <form method="get" class="panel entity-form">
        <section class="entity-section">
          <div class="panel-header">
            <h3>Filtros</h3>
            <p>Selecciona curso escolar y grupo para ver la asistencia agrupada por mes.</p>
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
          </div>

          <div class="form-actions">
            <button type="submit" class="button-primary">Mostrar asistencia</button>
            <a class="ghost-button" href="utilidades.php">Volver</a>
          </div>
        </section>
      </form>

      <section class="panel">
        <div class="panel-header">
          <h3>Listado de asistencia</h3>
          <p>Totales mensuales por alumno.</p>
        </div>

        <div class="panel-grid">
          <table>
            <thead>
              <tr>
                <th>Alumno</th>
                <th>Mes</th>
                <th>Faltas justificadas</th>
                <th>Faltas injustificadas</th>
                <th>Retrasos</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($selected['id_curso_escolar'] <= 0 || $selected['id_grupo'] <= 0): ?>
                <tr>
                  <td colspan="5">Selecciona curso escolar y grupo para consultar la asistencia.</td>
                </tr>
              <?php elseif ($errors !== []): ?>
                <tr>
                  <td colspan="5">No se pudo cargar la asistencia por los errores indicados.</td>
                </tr>
              <?php elseif ($students === []): ?>
                <tr>
                  <td colspan="5">No hay alumnos matriculados para el filtro seleccionado.</td>
                </tr>
              <?php elseif ($meses === []): ?>
                <tr>
                  <td colspan="5">No hay meses configurados.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($students as $student): ?>
                  <?php
                    $student_name = trim(
                      (string) ($student['apellido1'] ?? '')
                      . ' '
                      . (string) ($student['apellido2'] ?? '')
                      . ', '
                      . (string) ($student['nombre'] ?? '')
                    );
                    $id_alumno = (int) $student['id_alumno'];
                  ?>
                  <?php foreach ($meses as $mes): ?>
                    <?php
                      $id_mes = (int) $mes['id_mes'];
                      $totales = $attendance_index[$id_alumno][$id_mes] ?? [
                        'faltas_justificadas' => 0,
                        'faltas_injustificadas' => 0,
                        'retrasos' => 0,
                      ];
                    ?>
                    <tr>
                      <td><?php echo htmlspecialchars($student_name, ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars((string) $mes['mes'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo (int) $totales['faltas_justificadas']; ?></td>
                      <td><?php echo (int) $totales['faltas_injustificadas']; ?></td>
                      <td><?php echo (int) $totales['retrasos']; ?></td>
                    </tr>
                  <?php endforeach; ?>
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
