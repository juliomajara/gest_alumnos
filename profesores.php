<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pdo = db();

$active_course_id = $pdo->query('SELECT id_curso_escolar FROM cursos_escolares WHERE activo = 1 LIMIT 1')->fetchColumn();
$active_course_id = $active_course_id ? (int) $active_course_id : 0;

$search_term = trim((string) ($_GET['q'] ?? ''));

$filters = [];
$params = [
  'active_course_id_tutores' => $active_course_id,
  'active_course_id_modulos' => $active_course_id,
];

if ($search_term !== '') {
  $filters[] = '(p.nombre LIKE :search_term OR p.apellido1 LIKE :search_term1 OR p.apellido2 LIKE :search_term2 OR p.dni LIKE :search_term3)';
  $params['search_term'] = '%' . $search_term . '%';
  $params['search_term1'] = '%' . $search_term . '%';
  $params['search_term2'] = '%' . $search_term . '%';
  $params['search_term3'] = '%' . $search_term . '%';
}

$where_clause = $filters ? 'WHERE ' . implode(' AND ', $filters) : '';

// Nota para evitar HY093 en PDO: no reutilizar el mismo nombre de placeholder en múltiples JOIN.
$teachers_stmt = $pdo->prepare(
  'SELECT
    p.id_profesor,
    p.apellido1,
    p.apellido2,
    p.nombre,
    p.dni,
    GROUP_CONCAT(DISTINCT g.grupo ORDER BY g.grupo SEPARATOR ", ") AS grupos_tutor,
    GROUP_CONCAT(
      DISTINCT CONCAT(
        m.abreviatura,
        " - ",
        COALESCE(NULLIF(m.materia_propia, ""), m.materia_general)
      )
      ORDER BY m.abreviatura
      SEPARATOR ", "
    ) AS modulos
    ,t.telefonos
    ,c.correos
  FROM profesores p
  LEFT JOIN grupos_tutores gt
    ON gt.id_profesor = p.id_profesor
    AND gt.id_curso_escolar = :active_course_id_tutores
  LEFT JOIN grupos g ON g.id_grupo = gt.id_grupo
  LEFT JOIN modulos_profesores mp
    ON mp.id_profesor = p.id_profesor
    AND mp.id_curso_escolar = :active_course_id_modulos
  LEFT JOIN modulos m ON m.id_modulo = mp.id_modulo
  LEFT JOIN (
    SELECT
      id_entidad,
      GROUP_CONCAT(DISTINCT telefono ORDER BY telefono SEPARATOR ", ") AS telefonos
    FROM telefonos
    WHERE entidad_tipo = \'profesor\'
    GROUP BY id_entidad
  ) t ON t.id_entidad = p.id_profesor
  LEFT JOIN (
    SELECT
      id_entidad,
      GROUP_CONCAT(DISTINCT direccion_correo ORDER BY direccion_correo SEPARATOR ", ") AS correos
    FROM correos
    WHERE entidad_tipo = \'profesor\'
    GROUP BY id_entidad
  ) c ON c.id_entidad = p.id_profesor
  ' . $where_clause . '
  GROUP BY p.id_profesor, p.apellido1, p.apellido2, p.nombre, p.dni
  ORDER BY p.apellido1, p.apellido2, p.nombre'
);

$teachers_stmt->execute($params);
$teachers = $teachers_stmt->fetchAll();

function render_teacher_rows(array $teachers): string
{
  ob_start();
  if (!$teachers): ?>
    <tr>
      <td colspan="10">No hay profesores para los filtros seleccionados.</td>
    </tr>
  <?php else: ?>
    <?php foreach ($teachers as $teacher): ?>
      <?php
        $id_profesor = $teacher['id_profesor'] ? (string) $teacher['id_profesor'] : 'No disponible';
        $apellido1 = $teacher['apellido1'] ?: 'No disponible';
        $apellido2 = $teacher['apellido2'] ?: 'No disponible';
        $nombre = $teacher['nombre'] ?: 'No disponible';
        $dni = $teacher['dni'] ?: 'No disponible';
        $grupos_tutor = $teacher['grupos_tutor'] ?: 'Sin tutoría';
        $modulos = $teacher['modulos'] ?: 'Sin módulos';
        $telefonos = $teacher['telefonos'] ?: 'No disponible';
        $correos = $teacher['correos'] ?: 'No disponible';
        $editarUrl = 'profesor_editar.php?id=' . (int) $teacher['id_profesor'];
      ?>
      <tr>
        <td><?php echo htmlspecialchars($id_profesor, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($apellido1, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($apellido2, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($dni, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($grupos_tutor, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($modulos, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($telefonos, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($correos, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><a href="<?php echo htmlspecialchars($editarUrl, ENT_QUOTES, 'UTF-8'); ?>">Editar</a></td>
      </tr>
    <?php endforeach; ?>
  <?php endif;

  return ob_get_clean();
}

$rows_html = render_teacher_rows($teachers);

if (($_GET['ajax'] ?? '') === '1') {
  header('Content-Type: text/html; charset=UTF-8');
  echo $rows_html;
  exit;
}

$page_title = 'Profesores | Gestor de Alumnos';
$active_page = 'profesores';
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
          <h1>Profesores</h1>
          <p class="subheading">Consulta la información del profesorado y su relación con tutorías y módulos.</p>
        </div>
      </header>

      <form class="topbar" method="get">
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
            placeholder="Buscar por nombre, apellidos o DNI"
            aria-label="Buscar por nombre, apellidos o DNI"
            value="<?php echo htmlspecialchars($search_term, ENT_QUOTES, 'UTF-8'); ?>"
          >
        </div>
        <div class="topbar-actions"></div>
      </form>

      <section class="panel">
        <div class="panel-header">
          <h3>Listado de profesores</h3>
          <p>Identificadores, datos personales, tutorías asignadas y módulos impartidos.</p>
        </div>

        <div class="panel-grid">
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Apellido 1</th>
                <th>Apellido 2</th>
                <th>Nombre</th>
                <th>DNI</th>
                <th>Grupo tutor</th>
                <th>Módulos</th>
                <th>Teléfonos</th>
                <th>Correos</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php echo $rows_html; ?>
            </tbody>
          </table>
        </div>
      </section>
    </main>
  </div>
  <script>
    const form = document.querySelector('.topbar');
    const searchInput = document.querySelector('input[name="q"]');
    const tableBody = document.querySelector('tbody');
    let debounceTimer = null;

    const updateResults = (withDebounce = false) => {
      if (debounceTimer) {
        window.clearTimeout(debounceTimer);
      }

      const run = () => {
        const params = new URLSearchParams(new FormData(form));
        const urlParams = new URLSearchParams(params);

        params.set('ajax', '1');

        fetch(`profesores.php?${params.toString()}`, {
          headers: {
            'X-Requested-With': 'fetch'
          }
        })
          .then((response) => response.text())
          .then((html) => {
            tableBody.innerHTML = html;
            history.replaceState(null, '', `?${urlParams.toString()}`);
          })
          .catch(() => {});
      };

      if (withDebounce) {
        debounceTimer = window.setTimeout(run, 250);
        return;
      }

      run();
    };

    form.addEventListener('submit', (event) => {
      event.preventDefault();
      updateResults();
    });

    searchInput.addEventListener('input', () => {
      updateResults(true);
    });
  </script>
</body>
</html>
