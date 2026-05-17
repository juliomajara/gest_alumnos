<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/generar_claves_json.php';

$pdo = db();

$active_course_id = $pdo->query('SELECT id_curso_escolar FROM cursos_escolares WHERE activo = 1 ORDER BY id_curso_escolar DESC LIMIT 1')->fetchColumn();
$active_course_id = $active_course_id ? (int) $active_course_id : 0;

$courses = $pdo->query('SELECT id_curso_escolar, curso_escolar FROM cursos_escolares ORDER BY activo DESC, id_curso_escolar DESC')->fetchAll();

$selected_course_id = (int) ($_GET['id_curso_escolar'] ?? $active_course_id);
$search_term = trim((string) ($_GET['q'] ?? ''));
$selected_group = (string) ($_GET['grupo_id'] ?? '');
$selected_group = $selected_group === '' ? '' : $selected_group;

$groups = [];
if ($selected_course_id > 0) {
  $groups_stmt = $pdo->prepare(
    'SELECT DISTINCT g.id_grupo, g.grupo
     FROM grupos g
     INNER JOIN alumno_curso ac
      ON ac.id_grupo = g.id_grupo
      AND ac.id_curso_escolar = :id_curso_escolar
     ORDER BY g.grupo'
  );
  $groups_stmt->execute(['id_curso_escolar' => $selected_course_id]);
  $groups = $groups_stmt->fetchAll();
}

$selected_group_name = '';
foreach ($groups as $group) {
  if ((string) ($group['id_grupo'] ?? '') === $selected_group) {
    $selected_group_name = (string) ($group['grupo'] ?? '');
    break;
  }
}

$students = [];
$empty_message = '';

if ($selected_course_id <= 0) {
  $empty_message = 'Selecciona un curso y un grupo para ver los alumnos.';
} elseif ($selected_group === '') {
  $empty_message = 'Selecciona un grupo para ver los alumnos.';
} else {
  $filters = [];
  $params = ['id_curso_escolar' => $selected_course_id];

  if ($search_term !== '') {
    $filters[] = '(a.nombre LIKE :search_term OR a.apellido1 LIKE :search_term1 OR a.apellido2 LIKE :search_term2)';
    $params['search_term'] = '%' . $search_term . '%';
    $params['search_term1'] = '%' . $search_term . '%';
    $params['search_term2'] = '%' . $search_term . '%';
  }

  if ($selected_group === 'sin') {
    $filters[] = 'g.id_grupo IS NULL';
  } elseif (ctype_digit($selected_group)) {
    $filters[] = 'g.id_grupo = :group_id';
    $params['group_id'] = (int) $selected_group;
  } else {
    $filters[] = '1 = 0';
  }

  $where_clause = $filters ? 'WHERE ' . implode(' AND ', $filters) : '';

  $students_stmt = $pdo->prepare(
    'SELECT
      a.id_alumno,
      a.apellido1,
      a.apellido2,
      a.nombre,
      t.telefono,
      c.direccion_correo AS correo_personal,
      a.nia,
      a.dni,
      g.grupo
    FROM alumnos a
    LEFT JOIN alumno_curso ac
      ON ac.id_alumno = a.id_alumno
      AND ac.id_curso_escolar = :id_curso_escolar
    LEFT JOIN grupos g
      ON g.id_grupo = ac.id_grupo
    LEFT JOIN (
      SELECT id_entidad, MIN(telefono) AS telefono
      FROM telefonos
      WHERE entidad_tipo = \'alumno\'
      GROUP BY id_entidad
    ) t ON t.id_entidad = a.id_alumno
    LEFT JOIN (
      SELECT id_entidad, MIN(direccion_correo) AS direccion_correo
      FROM correos
      WHERE entidad_tipo = \'alumno\'
      GROUP BY id_entidad
    ) c ON c.id_entidad = a.id_alumno
    ' . $where_clause . '
    ORDER BY g.grupo, a.apellido1, a.apellido2, a.nombre'
  );

  $students_stmt->execute($params);
  $students = $students_stmt->fetchAll();

  if ($students === []) {
    $empty_message = $search_term !== ''
      ? 'No hay resultados para la búsqueda en el curso/grupo seleccionado.'
      : 'No hay alumnos para los filtros seleccionados.';
  }
}

function render_student_rows(array $students, string $empty_message): string
{
  ob_start();
  if (!$students): ?>
    <tr>
      <td colspan="6"><?php echo htmlspecialchars($empty_message, ENT_QUOTES, 'UTF-8'); ?></td>
    </tr>
  <?php else: ?>
    <?php foreach ($students as $student): ?>
      <?php
        $apellido2 = $student['apellido2'] ? ' ' . $student['apellido2'] : '';
        $nombre_completo = sprintf('%s%s, %s', $student['apellido1'], $apellido2, $student['nombre']);
        $grupo = $student['grupo'] ?: 'Sin grupo';
        $telefono = $student['telefono'] ?: 'No disponible';
        $email_personal = $student['correo_personal'] ?: 'No disponible';
        $nia = $student['nia'] ?: 'No disponible';
        $dni = $student['dni'] ?: 'No disponible';
        $detalle_url = sprintf('alumno_detalle.php?id_alumno=%d', (int) $student['id_alumno']);
      ?>
      <tr>
        <td><?php echo htmlspecialchars($grupo, ENT_QUOTES, 'UTF-8'); ?></td>
        <td>
          <a class="practice-link" href="<?php echo htmlspecialchars($detalle_url, ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo htmlspecialchars($nombre_completo, ENT_QUOTES, 'UTF-8'); ?>
          </a>
        </td>
        <td><?php echo htmlspecialchars($telefono, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($email_personal, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($nia, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($dni, ENT_QUOTES, 'UTF-8'); ?></td>
      </tr>
    <?php endforeach; ?>
  <?php endif;

  return ob_get_clean();
}


if (($_GET['exportar_claves_json'] ?? '') === '1') {
  generarClavesJsonDesdeAlumnos($students);
}

$rows_html = render_student_rows($students, $empty_message);

if (($_GET['ajax'] ?? '') === '1') {
  header('Content-Type: text/html; charset=UTF-8');
  echo $rows_html;
  exit;
}

$page_title = 'Alumnos | Gestor de Alumnos';
$active_page = 'alumnos';
$students_heading = $selected_group_name !== ''
  ? 'Listado de alumnos de ' . htmlspecialchars($selected_group_name, ENT_QUOTES, 'UTF-8')
  : 'Listado de alumnos';
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
          <h1>Alumnos</h1>
          <p class="subheading">Consulta la información básica del alumnado y accede al detalle completo.</p>
        </div>
      </header>

      <form method="get" class="panel entity-form">
        <section class="entity-section">
          <div class="panel-header">
            <h3>Filtros</h3>
            <p>Selecciona curso escolar, grupo y/o busca por nombre o apellidos.</p>
            <p>
              <a
                id="exportar-claves-json"
                class="button-secondary"
                href="<?php echo htmlspecialchars('alumnos.php?' . http_build_query(array_merge($_GET, ['exportar_claves_json' => '1'])), ENT_QUOTES, 'UTF-8'); ?>"
              >Exportar claves a JSON</a>
            </p>
          </div>

          <div class="entity-grid entity-grid--4">
            <label>
              <select name="id_curso_escolar">
                <option value="" <?php echo $selected_course_id <= 0 ? 'selected' : ''; ?>>Selecciona curso escolar</option>
                <?php foreach ($courses as $course): ?>
                  <option value="<?php echo (int) $course['id_curso_escolar']; ?>" <?php echo (int) $course['id_curso_escolar'] === $selected_course_id ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($course['curso_escolar'], ENT_QUOTES, 'UTF-8'); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </label>

            <label>
              <select name="grupo_id">
                <option value="" <?php echo $selected_group === '' ? 'selected' : ''; ?>>Selecciona grupo</option>
                <?php foreach ($groups as $group): ?>
                  <option value="<?php echo (int) $group['id_grupo']; ?>" <?php echo (string) $group['id_grupo'] === $selected_group ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($group['grupo'], ENT_QUOTES, 'UTF-8'); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </label>

            <label>
              <input
                type="search"
                name="q"
                placeholder="Buscar por nombre o apellidos"
                aria-label="Buscar por nombre o apellidos"
                value="<?php echo htmlspecialchars($search_term, ENT_QUOTES, 'UTF-8'); ?>"
              >
            </label>
          </div>
        </section>
      </form>

      <section class="panel">
        <div class="panel-header">
          <h3 id="students-heading"><?php echo $students_heading; ?></h3>
          <p>Grupo, apellidos y nombre, datos de contacto e identificadores básicos.</p>
        </div>

        <div class="panel-grid">
          <table>
            <thead>
              <tr>
                <th>Grupo</th>
                <th>Apellidos y nombre</th>
                <th>Teléfono</th>
                <th>Correo personal</th>
                <th>NIA</th>
                <th>DNI</th>
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
    const form = document.querySelector('form.entity-form');
    const searchInput = document.querySelector('input[name="q"]');
    const groupSelect = document.querySelector('select[name="grupo_id"]');
    const courseSelect = document.querySelector('select[name="id_curso_escolar"]');
    const tableBody = document.querySelector('tbody');
    const exportJsonLink = document.querySelector('#exportar-claves-json');
    const studentsHeading = document.querySelector('#students-heading');
    let debounceTimer = null;

    const updateExportLink = (params) => {
      if (!exportJsonLink) {
        return;
      }

      const exportParams = new URLSearchParams(params);
      exportParams.delete('ajax');
      exportParams.set('exportar_claves_json', '1');
      exportJsonLink.href = `alumnos.php?${exportParams.toString()}`;
    };

    const updateHeading = () => {
      if (!studentsHeading) {
        return;
      }

      const selectedOption = groupSelect.options[groupSelect.selectedIndex];
      const groupName = selectedOption ? selectedOption.text.trim() : '';

      studentsHeading.textContent = groupSelect.value !== '' && groupName !== ''
        ? `Listado de alumnos de ${groupName}`
        : 'Listado de alumnos';
    };

    const updateResults = (withDebounce = false) => {
      if (debounceTimer) {
        window.clearTimeout(debounceTimer);
      }

      const run = () => {
        const params = new URLSearchParams(new FormData(form));
        const urlParams = new URLSearchParams(params);

        params.set('ajax', '1');
        updateExportLink(urlParams);

        fetch(`alumnos.php?${params.toString()}`, {
          headers: {
            'X-Requested-With': 'fetch'
          }
        })
          .then((response) => response.text())
          .then((html) => {
            tableBody.innerHTML = html;
            updateHeading();
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

    courseSelect.addEventListener('change', () => {
      groupSelect.value = '';
      updateResults();
    });

    updateExportLink(new URLSearchParams(new FormData(form)));
    updateHeading();
  </script>
</body>
</html>
