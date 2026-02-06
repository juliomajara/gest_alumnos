<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pdo = db();

$active_course_id = $pdo->query('SELECT id_curso_escolar FROM cursos_escolares WHERE activo = 1 LIMIT 1')->fetchColumn();
$active_course_id = $active_course_id ? (int) $active_course_id : 0;

$students_stmt = $pdo->prepare(
  'SELECT
    a.id_alumno,
    a.apellido1,
    a.apellido2,
    a.nombre,
    a.telefono,
    g.grupo,
    pe.estado AS practicas_estado
  FROM alumnos a
  LEFT JOIN alumno_curso ac
    ON ac.id_alumno = a.id_alumno
    AND ac.id_curso_escolar = :active_course_id
  LEFT JOIN grupos g
    ON g.id_grupo = ac.id_grupo
  LEFT JOIN (
    SELECT p1.id_alumno, p1.id_practicas_estado
    FROM practicas p1
    INNER JOIN (
      SELECT id_alumno, MAX(id_practica) AS max_id
      FROM practicas
      GROUP BY id_alumno
    ) p2 ON p1.id_practica = p2.max_id
  ) p ON p.id_alumno = a.id_alumno
  LEFT JOIN practicas_estados pe
    ON pe.id_practicas_estado = p.id_practicas_estado
  ORDER BY g.grupo, a.apellido1, a.apellido2, a.nombre'
);

$students_stmt->execute(['active_course_id' => $active_course_id]);
$students = $students_stmt->fetchAll();

$page_title = 'Alumnos | Gestor de Alumnos';
$active_page = 'alumnos';
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
          <p class="eyebrow">Listado</p>
          <h1>Alumnos</h1>
          <p class="subheading">Consulta la información básica del alumnado y accede al detalle completo.</p>
        </div>
      </header>

      <section class="panel">
        <div class="panel-header">
          <h3>Listado de alumnos</h3>
          <p>Grupo, apellidos y nombre, teléfono y estado actual de prácticas.</p>
        </div>

        <div class="panel-grid">
          <table>
            <thead>
              <tr>
                <th>Grupo</th>
                <th>Apellidos y nombre</th>
                <th>Teléfono</th>
                <th>Prácticas</th>
                <th>Detalle</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!$students): ?>
                <tr>
                  <td colspan="5">No hay alumnos registrados.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($students as $student): ?>
                  <?php
                    $apellido2 = $student['apellido2'] ? ' ' . $student['apellido2'] : '';
                    $nombre_completo = sprintf('%s%s, %s', $student['apellido1'], $apellido2, $student['nombre']);
                    $grupo = $student['grupo'] ?: 'Sin grupo';
                    $telefono = $student['telefono'] ?: 'No disponible';
                    $estado = $student['practicas_estado'] ?: 'Sin estado';
                    $detalle_url = sprintf('alumno_detalle.php?id_alumno=%d', (int) $student['id_alumno']);
                  ?>
                  <tr>
                    <td><?php echo htmlspecialchars($grupo, ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($nombre_completo, ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($telefono, ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($estado, ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>
                      <a href="<?php echo htmlspecialchars($detalle_url, ENT_QUOTES, 'UTF-8'); ?>">Ver ficha</a>
                    </td>
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
