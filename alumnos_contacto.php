<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pdo = db();

$courses = $pdo->query(
  'SELECT id_curso_escolar, curso_escolar, activo FROM cursos_escolares ORDER BY activo DESC, id_curso_escolar DESC'
)->fetchAll(PDO::FETCH_ASSOC);

$active_course_id = 0;
foreach ($courses as $c) {
  if ((int) ($c['activo'] ?? 0) === 1) {
    $active_course_id = (int) $c['id_curso_escolar'];
    break;
  }
}
if ($active_course_id === 0 && $courses !== []) {
  $active_course_id = (int) $courses[0]['id_curso_escolar'];
}

$course_id = isset($_GET['id_curso_escolar']) && ctype_digit((string) $_GET['id_curso_escolar'])
  ? (int) $_GET['id_curso_escolar']
  : $active_course_id;

$default_group_id = (string) ($pdo->query("SELECT valor FROM `config` WHERE `clave` = 'grupo_por_defecto' LIMIT 1")->fetchColumn() ?: '');
$selected_group = (string) ($_GET['id_grupo'] ?? $default_group_id);
$selected_group = ctype_digit($selected_group) ? $selected_group : '';

$groups = [];
if ($course_id > 0) {
  $groups_stmt = $pdo->prepare(
    'SELECT DISTINCT g.id_grupo, g.grupo
     FROM grupos g
     INNER JOIN alumno_curso ac
       ON ac.id_grupo = g.id_grupo
       AND ac.id_curso_escolar = :id_curso_escolar
     ORDER BY g.grupo'
  );
  $groups_stmt->execute(['id_curso_escolar' => $course_id]);
  $groups = $groups_stmt->fetchAll();
}

$load_error = null;
$students = [];

if ($course_id > 0 && $selected_group !== '') {
  try {
    $stmt = $pdo->prepare(
      'SELECT
         a.id_alumno,
         a.nombre    AS alumno_nombre,
         a.apellido1 AS alumno_apellido1,
         a.apellido2 AS alumno_apellido2,
         acor_educa.direccion_correo    AS alumno_correo_educamadrid,
         acor_personal.direccion_correo AS alumno_correo_personal
       FROM alumnos a
       INNER JOIN alumno_curso ac
         ON ac.id_alumno = a.id_alumno
         AND ac.id_curso_escolar = :id_curso_escolar
         AND ac.id_grupo = :id_grupo
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
       ORDER BY a.apellido1 ASC, a.apellido2 ASC, a.nombre ASC'
    );
    $stmt->execute(['id_curso_escolar' => $course_id, 'id_grupo' => (int) $selected_group]);
    $students = $stmt->fetchAll();
  } catch (Throwable $error) {
    $load_error = 'No se ha podido cargar el listado de alumnos.';
  }
}

$page_title = 'Correos de alumnos | Gestor de Alumnos';
$active_page = 'alumnos';

$_tab_params = [];
if ($course_id > 0) $_tab_params['id_curso_escolar'] = $course_id;
if ($selected_group !== '') $_tab_params['id_grupo'] = $selected_group;
$_tab_qs = $_tab_params ? '?' . htmlspecialchars(http_build_query($_tab_params), ENT_QUOTES, 'UTF-8') : '';
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
          <h1>Correos de alumnos</h1>
          <p class="subheading">Selecciona alumnos del grupo para recopilar sus correos y enviar comunicaciones.</p>
        </div>
      </header>

      <nav class="tab-nav">
        <a class="tab-nav-link" href="alumnos.php<?php echo $_tab_qs; ?>">Alumnos</a>
        <a class="tab-nav-link" href="asistencia.php<?php echo $_tab_qs; ?>">Asistencia</a>
        <a class="tab-nav-link" href="calificaciones.php<?php echo $_tab_qs; ?>">Calificaciones</a>
        <a class="tab-nav-link active" href="alumnos_contacto.php">Correos</a>
      </nav>

      <form method="get" class="topbar">
        <div class="topbar-actions entity-grid entity-grid--4">
          <label class="calendar-select">
            <select name="id_curso_escolar" aria-label="Curso escolar">
              <option value="">Selecciona curso escolar</option>
              <?php foreach ($courses as $course): ?>
                <option
                  value="<?php echo (int) $course['id_curso_escolar']; ?>"
                  <?php echo (int) $course['id_curso_escolar'] === $course_id ? 'selected' : ''; ?>
                ><?php echo htmlspecialchars((string) $course['curso_escolar'], ENT_QUOTES, 'UTF-8'); ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label class="calendar-select">
            <select name="id_grupo" aria-label="Grupo">
              <option value="">Selecciona grupo</option>
              <?php foreach ($groups as $group): ?>
                <option
                  value="<?php echo (int) $group['id_grupo']; ?>"
                  <?php echo (string) $group['id_grupo'] === $selected_group ? 'selected' : ''; ?>
                ><?php echo htmlspecialchars((string) $group['grupo'], ENT_QUOTES, 'UTF-8'); ?></option>
              <?php endforeach; ?>
            </select>
          </label>
        </div>
      </form>

      <section class="panel">
        <div class="panel-header">
          <h3>Alumnos del grupo</h3>
          <p>Selecciona los alumnos cuyos correos quieres recopilar.</p>
        </div>
        <div class="panel-grid">
          <?php if ($load_error !== null): ?>
            <p><?php echo htmlspecialchars($load_error, ENT_QUOTES, 'UTF-8'); ?></p>
          <?php elseif ($courses === []): ?>
            <p>No hay cursos escolares disponibles.</p>
          <?php else: ?>
            <table>
              <thead>
                <tr>
                  <th><input type="checkbox" id="select-all" aria-label="Seleccionar todos los alumnos"></th>
                  <th>Alumno</th>
                  <th>Correo EducaMadrid</th>
                  <th>Correo personal</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($selected_group === ''): ?>
                  <tr>
                    <td colspan="4">Selecciona un grupo para ver los alumnos.</td>
                  </tr>
                <?php elseif ($students === []): ?>
                  <tr>
                    <td colspan="4">No hay alumnos en el grupo seleccionado.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($students as $student): ?>
                    <?php
                      $apellido2 = trim((string) ($student['alumno_apellido2'] ?? ''));
                      $apellidos = trim(implode(' ', array_filter([
                        (string) ($student['alumno_apellido1'] ?? ''),
                        $apellido2,
                      ], static fn ($v) => trim((string) $v) !== '')));
                      $nombre = trim((string) ($student['alumno_nombre'] ?? ''));
                      $nombre_completo = $apellidos !== '' && $nombre !== ''
                        ? $apellidos . ', ' . $nombre
                        : ($apellidos !== '' ? $apellidos : ($nombre !== '' ? $nombre : 'No disponible'));
                      $correo_educa    = trim((string) ($student['alumno_correo_educamadrid'] ?? ''));
                      $correo_personal = trim((string) ($student['alumno_correo_personal'] ?? ''));
                    ?>
                    <tr>
                      <td>
                        <input
                          type="checkbox"
                          class="alumno-checkbox"
                          data-correo-educamadrid="<?php echo htmlspecialchars($correo_educa, ENT_QUOTES, 'UTF-8'); ?>"
                          data-correo-personal="<?php echo htmlspecialchars($correo_personal, ENT_QUOTES, 'UTF-8'); ?>"
                          aria-label="Seleccionar <?php echo htmlspecialchars($nombre_completo, ENT_QUOTES, 'UTF-8'); ?>"
                        >
                      </td>
                      <td><?php echo htmlspecialchars($nombre_completo, ENT_QUOTES, 'UTF-8'); ?></td>
                      <td>
                        <?php if ($correo_educa !== ''): ?>
                          <span class="copy-trigger" data-copy="<?php echo htmlspecialchars($correo_educa, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($correo_educa, ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php else: ?>
                          —
                        <?php endif; ?>
                      </td>
                      <td>
                        <?php if ($correo_personal !== ''): ?>
                          <span class="copy-trigger" data-copy="<?php echo htmlspecialchars($correo_personal, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($correo_personal, ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php else: ?>
                          —
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      </section>

      <section class="panel">
        <div class="panel-header">
          <h3>Correos seleccionados</h3>
          <p>Copia y pega los correos en tu gestor de correo para enviar mensajes masivos.</p>
        </div>
        <div class="entity-form entity-stack">
          <div class="entity-grid entity-grid--3">
            <label>
              Tipo de correo
              <select id="tipo-correo">
                <option value="educamadrid">Solo EducaMadrid</option>
                <option value="personal">Solo personales</option>
                <option value="todos">Todos los correos registrados</option>
              </select>
            </label>
          </div>
          <label>
            Correos de los alumnos seleccionados
            <textarea id="correos-output" readonly rows="5" placeholder="Selecciona alumnos en la tabla para ver sus correos aquí."></textarea>
          </label>
        </div>
      </section>
    </main>
  </div>

  <script>
    const filterForm = document.querySelector('form.topbar');
    if (filterForm) {
      filterForm.querySelectorAll('select').forEach((select) => {
        select.addEventListener('change', () => { filterForm.submit(); });
      });
    }

    const selectAll = document.getElementById('select-all');
    const tipoCorreo = document.getElementById('tipo-correo');
    const correoOutput = document.getElementById('correos-output');

    const getAllCheckboxes = () => document.querySelectorAll('.alumno-checkbox');
    const getCheckedCheckboxes = () => document.querySelectorAll('.alumno-checkbox:checked');

    const updateCorreos = () => {
      if (!tipoCorreo || !correoOutput) {
        return;
      }

      const tipo = tipoCorreo.value;
      const checked = getCheckedCheckboxes();
      const seen = new Set();
      const emails = [];

      checked.forEach((cb) => {
        const educa    = (cb.dataset.correoEducamadrid || '').trim();
        const personal = (cb.dataset.correoPersonal || '').trim();

        if (tipo === 'educamadrid') {
          if (educa && !seen.has(educa)) {
            seen.add(educa);
            emails.push(educa);
          }
        } else if (tipo === 'personal') {
          if (personal && !seen.has(personal)) {
            seen.add(personal);
            emails.push(personal);
          }
        } else {
          if (educa && !seen.has(educa)) {
            seen.add(educa);
            emails.push(educa);
          }
          if (personal && !seen.has(personal)) {
            seen.add(personal);
            emails.push(personal);
          }
        }
      });

      correoOutput.value = emails.join(', ');
    };

    const updateSelectAllState = () => {
      if (!selectAll) {
        return;
      }
      const all     = getAllCheckboxes();
      const checked = getCheckedCheckboxes();
      selectAll.checked       = all.length > 0 && checked.length === all.length;
      selectAll.indeterminate = checked.length > 0 && checked.length < all.length;
    };

    if (selectAll) {
      selectAll.addEventListener('change', () => {
        getAllCheckboxes().forEach((cb) => { cb.checked = selectAll.checked; });
        updateCorreos();
      });
    }

    document.addEventListener('change', (event) => {
      if (event.target.classList.contains('alumno-checkbox')) {
        updateSelectAllState();
        updateCorreos();
      }
    });

    if (tipoCorreo) {
      tipoCorreo.addEventListener('change', updateCorreos);
    }
  </script>
  <script src="assets/copy.js"></script>
</body>
</html>
