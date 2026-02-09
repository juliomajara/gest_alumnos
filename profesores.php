<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/validaciones.php';

$pdo = db();

$is_post = $_SERVER['REQUEST_METHOD'] === 'POST';
if ($is_post) {
  $raw_body = file_get_contents('php://input');
  $payload = json_decode($raw_body ?: '', true);
  if (!is_array($payload)) {
    $payload = $_POST;
  }

  if (($payload['action'] ?? '') === 'update_profesor') {
    header('Content-Type: application/json; charset=UTF-8');

    $id_profesor = isset($payload['id_profesor']) ? (int) $payload['id_profesor'] : 0;
    $apellido1 = trim((string) ($payload['apellido1'] ?? ''));
    $apellido2 = trim((string) ($payload['apellido2'] ?? ''));
    $nombre = trim((string) ($payload['nombre'] ?? ''));
    $telefono = trim((string) ($payload['telefono'] ?? ''));
    $correo = trim((string) ($payload['correo'] ?? ''));
    $dni = trim((string) ($payload['dni'] ?? ''));

    if ($id_profesor <= 0) {
      echo json_encode(['success' => false, 'message' => 'Profesor no válido.']);
      exit;
    }

    if ($telefono !== '' && !es_telefono_movil_espanol($telefono)) {
      echo json_encode(['success' => false, 'message' => 'El teléfono no es válido.']);
      exit;
    }

    if ($correo !== '' && !es_correo_valido($correo)) {
      echo json_encode(['success' => false, 'message' => 'El correo no es válido.']);
      exit;
    }

    if ($dni !== '' && !es_dni_nie_valido($dni)) {
      echo json_encode(['success' => false, 'message' => 'El DNI/NIE no es válido.']);
      exit;
    }

    try {
      $pdo->beginTransaction();

      $update_profesor = $pdo->prepare(
        'UPDATE profesores
         SET apellido1 = :apellido1,
             apellido2 = :apellido2,
             nombre = :nombre,
             dni = :dni
         WHERE id_profesor = :id_profesor'
      );
      $update_profesor->execute([
        'apellido1' => $apellido1,
        'apellido2' => $apellido2 === '' ? null : $apellido2,
        'nombre' => $nombre,
        'dni' => $dni === '' ? null : $dni,
        'id_profesor' => $id_profesor,
      ]);

      $telefono_id_stmt = $pdo->prepare(
        'SELECT id_telefono FROM telefonos WHERE entidad_tipo = :tipo AND id_entidad = :id_profesor ORDER BY id_telefono ASC LIMIT 1'
      );
      $telefono_id_stmt->execute(['tipo' => 'profesor', 'id_profesor' => $id_profesor]);
      $telefono_id = $telefono_id_stmt->fetchColumn();

      if ($telefono === '') {
        if ($telefono_id) {
          $delete_telefono = $pdo->prepare('DELETE FROM telefonos WHERE id_telefono = :id_telefono');
          $delete_telefono->execute(['id_telefono' => $telefono_id]);
        }
      } elseif ($telefono_id) {
        $update_telefono = $pdo->prepare('UPDATE telefonos SET telefono = :telefono WHERE id_telefono = :id_telefono');
        $update_telefono->execute([
          'telefono' => $telefono,
          'id_telefono' => $telefono_id,
        ]);
      } else {
        $insert_telefono = $pdo->prepare(
          'INSERT INTO telefonos (entidad_tipo, id_entidad, telefono, etiqueta) VALUES (:tipo, :id_profesor, :telefono, :etiqueta)'
        );
        $insert_telefono->execute([
          'tipo' => 'profesor',
          'id_profesor' => $id_profesor,
          'telefono' => $telefono,
          'etiqueta' => 'Personal',
        ]);
      }

      $correo_id_stmt = $pdo->prepare(
        'SELECT id_correo FROM correos WHERE entidad_tipo = :tipo AND id_entidad = :id_profesor ORDER BY id_correo ASC LIMIT 1'
      );
      $correo_id_stmt->execute(['tipo' => 'profesor', 'id_profesor' => $id_profesor]);
      $correo_id = $correo_id_stmt->fetchColumn();

      if ($correo === '') {
        if ($correo_id) {
          $delete_correo = $pdo->prepare('DELETE FROM correos WHERE id_correo = :id_correo');
          $delete_correo->execute(['id_correo' => $correo_id]);
        }
      } elseif ($correo_id) {
        $update_correo = $pdo->prepare('UPDATE correos SET direccion_correo = :correo WHERE id_correo = :id_correo');
        $update_correo->execute([
          'correo' => $correo,
          'id_correo' => $correo_id,
        ]);
      } else {
        $insert_correo = $pdo->prepare(
          'INSERT INTO correos (entidad_tipo, id_entidad, direccion_correo, etiqueta) VALUES (:tipo, :id_profesor, :correo, :etiqueta)'
        );
        $insert_correo->execute([
          'tipo' => 'profesor',
          'id_profesor' => $id_profesor,
          'correo' => $correo,
          'etiqueta' => 'Personal',
        ]);
      }

      $pdo->commit();
    } catch (Throwable $exception) {
      if ($pdo->inTransaction()) {
        $pdo->rollBack();
      }
      echo json_encode(['success' => false, 'message' => 'No se pudo guardar la información.']);
      exit;
    }

    echo json_encode([
      'success' => true,
      'apellido1' => $apellido1,
      'apellido2' => $apellido2,
      'nombre' => $nombre,
      'telefono' => $telefono !== '' ? $telefono : 'No disponible',
      'correo' => $correo !== '' ? $correo : 'No disponible',
      'dni' => $dni !== '' ? $dni : 'No disponible',
    ]);
    exit;
  }
}

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
      <td colspan="7">No hay profesores para los filtros seleccionados.</td>
    </tr>
  <?php else: ?>
    <?php foreach ($teachers as $teacher): ?>
      <?php
        $apellido2 = $teacher['apellido2'] ? ' ' . $teacher['apellido2'] : '';
        $nombre_completo = sprintf('%s%s, %s', $teacher['apellido1'], $apellido2, $teacher['nombre']);
        $grupo = $teacher['grupo'] ?: 'Sin tutoría';
        $telefono_value = $teacher['telefono'] ?: '';
        $email_value = $teacher['correo_personal'] ?: '';
        $dni_value = $teacher['dni'] ?: '';
        $telefono = $telefono_value !== '' ? $telefono_value : 'No disponible';
        $email_personal = $email_value !== '' ? $email_value : 'No disponible';
        $dni = $dni_value !== '' ? $dni_value : 'No disponible';
        $modulos = $teacher['modulos_count'] ? (string) $teacher['modulos_count'] : 'Sin módulos';
      ?>
      <tr data-profesor-id="<?php echo (int) $teacher['id_profesor']; ?>">
        <td><?php echo htmlspecialchars($grupo, ENT_QUOTES, 'UTF-8'); ?></td>
        <td class="name-cell">
          <span class="name-text"><?php echo htmlspecialchars($nombre_completo, ENT_QUOTES, 'UTF-8'); ?></span>
          <div class="name-inputs">
            <input
              class="contact-input"
              type="text"
              name="apellido1"
              placeholder="Primer apellido"
              value="<?php echo htmlspecialchars($teacher['apellido1'], ENT_QUOTES, 'UTF-8'); ?>"
            >
            <input
              class="contact-input"
              type="text"
              name="apellido2"
              placeholder="Segundo apellido"
              value="<?php echo htmlspecialchars($teacher['apellido2'], ENT_QUOTES, 'UTF-8'); ?>"
            >
            <input
              class="contact-input"
              type="text"
              name="nombre"
              placeholder="Nombre"
              value="<?php echo htmlspecialchars($teacher['nombre'], ENT_QUOTES, 'UTF-8'); ?>"
            >
          </div>
        </td>
        <td class="contact-cell">
          <span class="contact-text"><?php echo htmlspecialchars($telefono, ENT_QUOTES, 'UTF-8'); ?></span>
          <input
            class="contact-input"
            type="text"
            name="telefono"
            placeholder="Teléfono"
            value="<?php echo htmlspecialchars($telefono_value, ENT_QUOTES, 'UTF-8'); ?>"
          >
        </td>
        <td class="contact-cell">
          <span class="contact-text"><?php echo htmlspecialchars($email_personal, ENT_QUOTES, 'UTF-8'); ?></span>
          <input
            class="contact-input"
            type="email"
            name="correo"
            placeholder="Correo"
            value="<?php echo htmlspecialchars($email_value, ENT_QUOTES, 'UTF-8'); ?>"
          >
        </td>
        <td class="contact-cell">
          <span class="contact-text"><?php echo htmlspecialchars($dni, ENT_QUOTES, 'UTF-8'); ?></span>
          <input
            class="contact-input"
            type="text"
            name="dni"
            placeholder="DNI"
            value="<?php echo htmlspecialchars($dni_value, ENT_QUOTES, 'UTF-8'); ?>"
          >
        </td>
        <td><?php echo htmlspecialchars($modulos, ENT_QUOTES, 'UTF-8'); ?></td>
        <td class="contact-actions-cell">
          <div class="contact-actions">
            <label class="edit-row-toggle">
              <input class="row-edit-toggle" type="checkbox" aria-label="Editar profesor">
              <span>Editar</span>
            </label>
            <button class="ghost-button save-button" type="button">Guardar</button>
            <span class="save-status" aria-live="polite"></span>
          </div>
        </td>
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
        <div class="header-actions">
          <button class="edit-toggle" type="button" id="editToggle">Modo edición</button>
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
          <table class="teacher-table">
            <thead>
              <tr>
                <th>Tutoría</th>
                <th>Apellidos y nombre</th>
                <th>Teléfono</th>
                <th>Correo</th>
                <th>DNI</th>
                <th>Módulos</th>
                <th class="teacher-actions-header">Acciones</th>
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
    const teacherTable = document.querySelector('.teacher-table');
    const editToggle = document.getElementById('editToggle');
    let debounceTimer = null;
    const storageKey = 'teachersEditMode';
    let editMode = localStorage.getItem(storageKey) === 'true';

    const updateEditButton = () => {
      if (!editToggle || !teacherTable) {
        return;
      }
      teacherTable.classList.toggle('is-editing', editMode);
      if (!editMode) {
        teacherTable.querySelectorAll('.row-edit-toggle').forEach((toggle) => {
          toggle.checked = false;
          const row = toggle.closest('tr');
          if (row) {
            row.classList.remove('is-row-editing');
          }
        });
      }
      editToggle.classList.toggle('is-active', editMode);
      editToggle.textContent = editMode ? 'Modo edición activado' : 'Modo edición';
    };

    updateEditButton();

    if (editToggle) {
      editToggle.addEventListener('click', () => {
        editMode = !editMode;
        localStorage.setItem(storageKey, String(editMode));
        updateEditButton();
      });
    }

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
            updateEditButton();
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

    tableBody.addEventListener('change', (event) => {
      const toggle = event.target.closest('.row-edit-toggle');
      if (!toggle) {
        return;
      }

      if (!editMode) {
        toggle.checked = false;
        const row = toggle.closest('tr');
        if (row) {
          row.classList.remove('is-row-editing');
        }
        return;
      }

      const row = toggle.closest('tr');
      if (!row) {
        return;
      }

      row.classList.toggle('is-row-editing', toggle.checked);
    });

    tableBody.addEventListener('click', (event) => {
      const button = event.target.closest('.save-button');
      if (!button) {
        return;
      }

      const row = button.closest('tr');
      if (!row) {
        return;
      }

      const profesorId = row.dataset.profesorId;
      const apellido1Input = row.querySelector('input[name="apellido1"]');
      const apellido2Input = row.querySelector('input[name="apellido2"]');
      const nombreInput = row.querySelector('input[name="nombre"]');
      const telefonoInput = row.querySelector('input[name="telefono"]');
      const correoInput = row.querySelector('input[name="correo"]');
      const dniInput = row.querySelector('input[name="dni"]');
      const status = row.querySelector('.save-status');

      if (!profesorId || !apellido1Input || !apellido2Input || !nombreInput || !telefonoInput || !correoInput || !dniInput || !status) {
        return;
      }

      const apellido1 = apellido1Input.value.trim();
      const apellido2 = apellido2Input.value.trim();
      const nombre = nombreInput.value.trim();
      const telefono = telefonoInput.value.trim();
      const correo = correoInput.value.trim();
      const dni = dniInput.value.trim();

      button.disabled = true;
      status.textContent = 'Guardando...';
      status.classList.remove('is-success', 'is-error');

      fetch('profesores.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'fetch'
        },
        body: JSON.stringify({
          action: 'update_profesor',
          id_profesor: profesorId,
          apellido1,
          apellido2,
          nombre,
          telefono,
          correo,
          dni
        })
      })
        .then((response) => response.json())
        .then((data) => {
          if (!data.success) {
            throw new Error(data.message || 'No se pudo guardar.');
          }

          const nameText = row.querySelector('.name-text');
          if (nameText) {
            const apellido2Value = data.apellido2 ? ` ${data.apellido2}` : '';
            nameText.textContent = `${data.apellido1}${apellido2Value}, ${data.nombre}`;
          }

          const contactTexts = row.querySelectorAll('.contact-cell .contact-text');
          if (contactTexts[0]) {
            contactTexts[0].textContent = data.telefono;
          }
          if (contactTexts[1]) {
            contactTexts[1].textContent = data.correo;
          }
          if (contactTexts[2]) {
            contactTexts[2].textContent = data.dni;
          }

          status.textContent = 'Guardado';
          status.classList.add('is-success');
        })
        .catch((error) => {
          status.textContent = error.message || 'Error al guardar';
          status.classList.add('is-error');
        })
        .finally(() => {
          button.disabled = false;
        });
    });
  </script>
</body>
</html>
