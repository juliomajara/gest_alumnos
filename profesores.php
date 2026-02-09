<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pdo = db();

$active_course_id = $pdo->query('SELECT id_curso_escolar FROM cursos_escolares WHERE activo = 1 LIMIT 1')->fetchColumn();
$active_course_id = $active_course_id ? (int) $active_course_id : 0;

$search_term = trim((string) ($_GET['q'] ?? ''));
$selected_group = (string) ($_GET['grupo_id'] ?? '');
$selected_group = $selected_group === '' ? '' : $selected_group;

$groups_stmt = $pdo->prepare(
  'SELECT DISTINCT g.id_grupo, g.grupo
   FROM grupos g
   INNER JOIN grupos_tutores gt
    ON gt.id_grupo = g.id_grupo
    AND gt.id_curso_escolar = :active_course_id
   ORDER BY g.grupo'
);
$groups_stmt->execute(['active_course_id' => $active_course_id]);
$groups = $groups_stmt->fetchAll();

$filters = [];
$params = [
  'active_course_id_gt' => $active_course_id,
  'active_course_id_mp' => $active_course_id,
];

if ($search_term !== '') {
  $filters[] = '(p.nombre LIKE :search_term OR p.apellido1 LIKE :search_term1 OR p.apellido2 LIKE :search_term2 OR p.dni LIKE :search_term3)';
  $params['search_term'] = '%' . $search_term . '%';
  $params['search_term1'] = '%' . $search_term . '%';
  $params['search_term2'] = '%' . $search_term . '%';
  $params['search_term3'] = '%' . $search_term . '%';
}

if ($selected_group !== '') {
  if ($selected_group === 'sin') {
    $filters[] = 'g.id_grupo IS NULL';
  } elseif (ctype_digit($selected_group)) {
    $filters[] = 'g.id_grupo = :group_id';
    $params['group_id'] = (int) $selected_group;
  }
}

$where_clause = $filters ? 'WHERE ' . implode(' AND ', $filters) : '';

$teachers_stmt = $pdo->prepare(
  'SELECT
    p.id_profesor,
    p.apellido1,
    p.apellido2,
    p.nombre,
    p.dni,
    g.grupo,
    t.telefono,
    c.direccion_correo AS correo_personal,
    COUNT(DISTINCT mp.id_modulo) AS modulos_count
  FROM profesores p
  LEFT JOIN grupos_tutores gt
    ON gt.id_profesor = p.id_profesor
    AND gt.id_curso_escolar = :active_course_id_gt
  LEFT JOIN grupos g
    ON g.id_grupo = gt.id_grupo
  LEFT JOIN modulos_profesores mp
    ON mp.id_profesor = p.id_profesor
    AND mp.id_curso_escolar = :active_course_id_mp
  LEFT JOIN (
    SELECT id_entidad, MIN(telefono) AS telefono
    FROM telefonos
    WHERE entidad_tipo = \'profesor\'
    GROUP BY id_entidad
  ) t ON t.id_entidad = p.id_profesor
  LEFT JOIN (
    SELECT id_entidad, MIN(direccion_correo) AS direccion_correo
    FROM correos
    WHERE entidad_tipo = \'profesor\'
    GROUP BY id_entidad
  ) c ON c.id_entidad = p.id_profesor
  ' . $where_clause . '
  GROUP BY p.id_profesor, g.id_grupo
  ORDER BY g.grupo, p.apellido1, p.apellido2, p.nombre'
);

$teachers_stmt->execute($params);
$teachers = $teachers_stmt->fetchAll();

function render_teacher_rows(array $teachers): string
{
  ob_start();
  if (!$teachers): ?>
    <tr>
      <td colspan="6">No hay profesores para los filtros seleccionados.</td>
    </tr>
  <?php else: ?>
    <?php foreach ($teachers as $teacher): ?>
      <?php
        $apellido2 = $teacher['apellido2'] ? ' ' . $teacher['apellido2'] : '';
        $nombre_completo = sprintf('%s%s, %s', $teacher['apellido1'], $apellido2, $teacher['nombre']);
        $grupo = $teacher['grupo'] ?: 'Sin tutoría';
        $telefono = $teacher['telefono'] ?: 'No disponible';
        $email_personal = $teacher['correo_personal'] ?: 'No disponible';
        $dni = $teacher['dni'] ?: 'No disponible';
        $modulos = $teacher['modulos_count'] ? (string) $teacher['modulos_count'] : 'Sin módulos';
      ?>
      <tr>
        <td><?php echo htmlspecialchars($grupo, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($nombre_completo, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($telefono, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($email_personal, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($dni, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($modulos, ENT_QUOTES, 'UTF-8'); ?></td>
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
          <p class="subheading">Consulta el listado de docentes, sus tutorías y módulos asignados.</p>
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
        <div class="topbar-actions">
          <label class="calendar-select">
            <select name="grupo_id">
              <option value="" <?php echo $selected_group === '' ? 'selected' : ''; ?>>Todas las tutorías</option>
              <?php foreach ($groups as $group): ?>
                <option value="<?php echo (int) $group['id_grupo']; ?>" <?php echo (string) $group['id_grupo'] === $selected_group ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($group['grupo'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
              <?php endforeach; ?>
              <option value="sin" <?php echo $selected_group === 'sin' ? 'selected' : ''; ?>>Sin tutoría</option>
            </select>
          </label>
        </div>
      </form>

      <section class="panel">
        <div class="panel-header">
          <h3>Listado de profesores</h3>
          <p>Grupo tutor, datos de contacto, identificación y módulos asignados.</p>
        </div>

        <div class="panel-grid">
          <table>
            <thead>
              <tr>
                <th>Tutoría</th>
                <th>Apellidos y nombre</th>
                <th>Teléfono</th>
                <th>Correo</th>
                <th>DNI</th>
                <th>Módulos</th>
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
    const groupSelect = document.querySelector('select[name="grupo_id"]');
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

    groupSelect.addEventListener('change', () => {
      updateResults();
    });
  </script>
</body>
</html>
