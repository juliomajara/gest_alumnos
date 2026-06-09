<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pdo = db();

function format_date_contacto(?string $value): string
{
  if ($value === null || $value === '') {
    return '—';
  }
  $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);
  return $date !== false ? $date->format('d/m/Y') : $value;
}

function parse_date_input_contacto(string $key): ?string
{
  $val = trim((string) ($_GET[$key] ?? ''));
  if ($val === '') {
    return null;
  }
  $d = DateTimeImmutable::createFromFormat('Y-m-d', $val);
  return ($d !== false && $d->format('Y-m-d') === $val) ? $val : null;
}

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

$inicio_desde = parse_date_input_contacto('inicio_desde');
$inicio_hasta = parse_date_input_contacto('inicio_hasta');
$fin_desde    = parse_date_input_contacto('fin_desde');
$fin_hasta    = parse_date_input_contacto('fin_hasta');

$date_filters = [];
$params = ['id_curso_escolar' => $course_id];

if ($inicio_desde !== null) {
  $date_filters[] = 'p.fecha_inicio >= :inicio_desde';
  $params['inicio_desde'] = $inicio_desde;
}
if ($inicio_hasta !== null) {
  $date_filters[] = 'p.fecha_inicio <= :inicio_hasta';
  $params['inicio_hasta'] = $inicio_hasta;
}
if ($fin_desde !== null) {
  $date_filters[] = 'p.fecha_fin >= :fin_desde';
  $params['fin_desde'] = $fin_desde;
}
if ($fin_hasta !== null) {
  $date_filters[] = 'p.fecha_fin <= :fin_hasta';
  $params['fin_hasta'] = $fin_hasta;
}

$where_extra = $date_filters ? 'AND ' . implode(' AND ', $date_filters) : '';

$load_error = null;
$practices = [];

try {
  $stmt = $pdo->prepare(
    'SELECT DISTINCT
       p.id_practica,
       p.fecha_inicio,
       p.fecha_fin,
       a.id_alumno,
       a.nombre    AS alumno_nombre,
       a.apellido1 AS alumno_apellido1,
       a.apellido2 AS alumno_apellido2,
       acor_educa.direccion_correo    AS alumno_correo_educamadrid,
       acor_personal.direccion_correo AS alumno_correo_personal
     FROM practicas p
     INNER JOIN alumnos a ON a.id_alumno = p.id_alumno
     INNER JOIN alumno_curso ac
       ON ac.id_alumno = p.id_alumno
       AND ac.id_curso_escolar = :id_curso_escolar
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
     WHERE 1 = 1 ' . $where_extra . '
     ORDER BY a.apellido1 ASC, a.apellido2 ASC, a.nombre ASC, p.id_practica ASC'
  );
  $stmt->execute($params);
  $practices = $stmt->fetchAll();
} catch (Throwable $error) {
  $load_error = 'No se ha podido cargar el listado de alumnos con prácticas.';
}

$page_title = 'Correos de prácticas | Gestor de Alumnos';
$active_page = 'practicas';
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
          <h1>Correos de prácticas</h1>
          <p class="subheading">Selecciona alumnos para recopilar sus correos y enviar comunicaciones.</p>
        </div>
      </header>

      <nav class="tab-nav">
        <a class="tab-nav-link" href="practicas.php">Prácticas</a>
        <a class="tab-nav-link" href="practicas_dias.php">Días de prácticas</a>
        <a class="tab-nav-link" href="practicas_documentacion.php">Documentación</a>
        <a class="tab-nav-link" href="practicas_anexos.php">Seguimiento de Anexos</a>
        <a class="tab-nav-link" href="practicas_listado.php">Listado</a>
        <a class="tab-nav-link active" href="practicas_contacto.php">Correos</a>
      </nav>

      <section class="panel">
        <div class="panel-header">
          <h3>Filtros</h3>
          <p>Selecciona el curso y filtra por rango de fechas de inicio y fin de las prácticas.</p>
        </div>
        <form method="get" class="entity-form entity-stack">
          <div class="entity-grid">
            <label>
              Curso escolar
              <select name="id_curso_escolar">
                <?php if ($courses === []): ?>
                  <option value="">No hay cursos disponibles</option>
                <?php else: ?>
                  <?php foreach ($courses as $course): ?>
                    <option
                      value="<?php echo (int) $course['id_curso_escolar']; ?>"
                      <?php echo (int) $course['id_curso_escolar'] === $course_id ? 'selected' : ''; ?>
                    ><?php echo htmlspecialchars((string) $course['curso_escolar'], ENT_QUOTES, 'UTF-8'); ?></option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
            </label>
            <label>
              Inicio desde
              <input type="date" name="inicio_desde" value="<?php echo htmlspecialchars((string) ($inicio_desde ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </label>
            <label>
              Inicio hasta
              <input type="date" name="inicio_hasta" value="<?php echo htmlspecialchars((string) ($inicio_hasta ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </label>
            <label>
              Fin desde
              <input type="date" name="fin_desde" value="<?php echo htmlspecialchars((string) ($fin_desde ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </label>
            <label>
              Fin hasta
              <input type="date" name="fin_hasta" value="<?php echo htmlspecialchars((string) ($fin_hasta ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </label>
          </div>
          <div>
            <button type="submit" class="primary-button">Aplicar filtros</button>
          </div>
        </form>
      </section>

      <section class="panel">
        <div class="panel-header">
          <h3>Alumnos con prácticas</h3>
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
                  <th>Fecha inicio</th>
                  <th>Fecha fin</th>
                  <th>Correo EducaMadrid</th>
                  <th>Correo personal</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($practices === []): ?>
                  <tr>
                    <td colspan="6">No hay prácticas para los filtros seleccionados.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($practices as $practice): ?>
                    <?php
                      $apellido2 = trim((string) ($practice['alumno_apellido2'] ?? ''));
                      $apellidos = trim(implode(' ', array_filter([
                        (string) ($practice['alumno_apellido1'] ?? ''),
                        $apellido2,
                      ], static fn ($v) => trim((string) $v) !== '')));
                      $nombre = trim((string) ($practice['alumno_nombre'] ?? ''));
                      $nombre_completo = $apellidos !== '' && $nombre !== ''
                        ? $apellidos . ', ' . $nombre
                        : ($apellidos !== '' ? $apellidos : ($nombre !== '' ? $nombre : 'No disponible'));
                      $correo_educa    = trim((string) ($practice['alumno_correo_educamadrid'] ?? ''));
                      $correo_personal = trim((string) ($practice['alumno_correo_personal'] ?? ''));
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
                      <td><?php echo htmlspecialchars(format_date_contacto($practice['fecha_inicio'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars(format_date_contacto($practice['fecha_fin'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
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
