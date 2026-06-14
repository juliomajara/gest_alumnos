<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$page_title = 'Listado de prácticas | Gestor de Alumnos';
$active_page = 'practicas';

function format_date_listado(?string $value): string
{
  if ($value === null || $value === '') {
    return '—';
  }
  $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);
  return $date ? $date->format('d/m/Y') : $value;
}

function build_order_url_listado(string $col, string $cur_col, string $cur_dir): string
{
  $params = $_GET;
  $params['orden'] = $col . '_' . (($col === $cur_col && $cur_dir === 'asc') ? 'desc' : 'asc');
  $query = http_build_query($params);
  return 'practicas_listado.php' . ($query !== '' ? '?' . $query : '');
}
function sort_ind_listado(string $col, string $cur_col, string $cur_dir): string
{
  if ($col !== $cur_col) return '';
  return $cur_dir === 'asc' ? ' ▲' : ' ▼';
}

$load_error = null;
$active_course_id = null;
$practices = [];

$allowed_orders = ['alumno_asc', 'alumno_desc', 'localidad_alumno_asc', 'localidad_alumno_desc', 'localidad_empresa_asc', 'localidad_empresa_desc', 'fecha_inicio_asc', 'fecha_inicio_desc', 'fecha_fin_asc', 'fecha_fin_desc'];
$order_param = (string) ($_GET['orden'] ?? '');
$current_order = in_array($order_param, $allowed_orders, true) ? $order_param : 'alumno_asc';
$_last_us = strrpos($current_order, '_');
$sort_col = $_last_us !== false ? substr($current_order, 0, $_last_us) : 'alumno';
$sort_dir = $_last_us !== false ? substr($current_order, $_last_us + 1) : 'asc';

try {
  $pdo = db();
  $active_course_id = $pdo->query('SELECT id_curso_escolar FROM cursos_escolares WHERE activo = 1 LIMIT 1')->fetchColumn();

  if ($active_course_id !== false && $active_course_id !== null) {
    $dir = $sort_dir === 'desc' ? 'DESC' : 'ASC';
    $order_clause = match ($sort_col) {
      'localidad_alumno'  => "ORDER BY la.nombre $dir, a.apellido1 ASC, a.apellido2 ASC, a.nombre ASC",
      'localidad_empresa' => "ORDER BY le.nombre $dir, a.apellido1 ASC, a.apellido2 ASC, a.nombre ASC",
      'fecha_inicio'      => "ORDER BY p.fecha_inicio $dir, a.apellido1 ASC, a.nombre ASC",
      'fecha_fin'         => "ORDER BY p.fecha_fin_extra $dir, a.apellido1 ASC, a.nombre ASC",
      default             => "ORDER BY a.apellido1 $dir, a.apellido2 $dir, a.nombre $dir, p.id_practica ASC",
    };

    $stmt = $pdo->prepare(
      'SELECT DISTINCT
         p.id_practica,
         a.id_alumno,
         a.nombre      AS alumno_nombre,
         a.apellido1   AS alumno_apellido1,
         a.apellido2   AS alumno_apellido2,
         a.nia         AS alumno_nia,
         a.dni         AS alumno_dni,
         la.nombre     AS localidad_alumno,
         le.nombre     AS localidad_empresa,
         p.fecha_inicio,
         p.fecha_fin_extra,
         atal.telefono AS alumno_telefono,
         acor_educa.direccion_correo AS alumno_correo_educamadrid,
         acor_personal.direccion_correo AS alumno_correo_personal
       FROM practicas p
       INNER JOIN alumnos a       ON a.id_alumno   = p.id_alumno
       INNER JOIN alumno_curso ac ON ac.id_alumno  = p.id_alumno
                                  AND ac.id_curso_escolar = :active_course_id
       LEFT JOIN localidades la   ON la.id_localidad = a.id_localidad
       LEFT JOIN direcciones d    ON d.id_direccion  = p.id_direccion
       LEFT JOIN localidades le   ON le.id_localidad = d.id_localidad
       LEFT JOIN (
         SELECT id_entidad, MIN(telefono) AS telefono
         FROM telefonos
         WHERE entidad_tipo = "alumno"
         GROUP BY id_entidad
       ) atal ON atal.id_entidad = a.id_alumno
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
       ' . $order_clause
    );
    $stmt->execute(['active_course_id' => (int) $active_course_id]);
    $practices = $stmt->fetchAll();
  }
} catch (Throwable $error) {
  $load_error = 'No se ha podido cargar el listado de prácticas.';
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
          <h1>Listado de prácticas</h1>
          <p class="subheading">Localidades y fechas de las prácticas del curso actual.</p>
        </div>
      </header>

      <nav class="tab-nav">
        <a class="tab-nav-link" href="practicas.php">Prácticas</a>
        <a class="tab-nav-link" href="practicas_dias.php">Días de prácticas</a>
        <a class="tab-nav-link" href="practicas_documentacion.php">Documentación</a>
        <a class="tab-nav-link" href="practicas_anexos.php">Seguimiento de Anexos</a>
        <a class="tab-nav-link active" href="practicas_listado.php">Listado</a>
        <a class="tab-nav-link" href="practicas_contacto.php">Correos</a>
      </nav>

      <section class="panel">
        <div class="panel-grid">
          <?php if ($load_error !== null): ?>
            <p><?php echo htmlspecialchars($load_error, ENT_QUOTES, 'UTF-8'); ?></p>
          <?php elseif ($active_course_id === false || $active_course_id === null): ?>
            <p>No hay un curso activo configurado.</p>
          <?php else: ?>
            <table>
              <thead>
                <tr>
                  <th><a class="practice-link" href="<?php echo htmlspecialchars(build_order_url_listado('alumno', $sort_col, $sort_dir), ENT_QUOTES, 'UTF-8'); ?>">Alumno<?php echo sort_ind_listado('alumno', $sort_col, $sort_dir); ?></a></th>
                  <th><a class="practice-link" href="<?php echo htmlspecialchars(build_order_url_listado('localidad_alumno', $sort_col, $sort_dir), ENT_QUOTES, 'UTF-8'); ?>">Localidad alumno<?php echo sort_ind_listado('localidad_alumno', $sort_col, $sort_dir); ?></a></th>
                  <th><a class="practice-link" href="<?php echo htmlspecialchars(build_order_url_listado('localidad_empresa', $sort_col, $sort_dir), ENT_QUOTES, 'UTF-8'); ?>">Localidad centro de trabajo<?php echo sort_ind_listado('localidad_empresa', $sort_col, $sort_dir); ?></a></th>
                  <th><a class="practice-link" href="<?php echo htmlspecialchars(build_order_url_listado('fecha_inicio', $sort_col, $sort_dir), ENT_QUOTES, 'UTF-8'); ?>">Fecha inicio<?php echo sort_ind_listado('fecha_inicio', $sort_col, $sort_dir); ?></a></th>
                  <th><a class="practice-link" href="<?php echo htmlspecialchars(build_order_url_listado('fecha_fin', $sort_col, $sort_dir), ENT_QUOTES, 'UTF-8'); ?>">Fecha fin real<?php echo sort_ind_listado('fecha_fin', $sort_col, $sort_dir); ?></a></th>
                </tr>
              </thead>
              <tbody>
                <?php if ($practices === []): ?>
                  <tr>
                    <td colspan="5">No hay prácticas registradas para el curso actual.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($practices as $practice): ?>
                    <?php
                      $apellidos = trim(implode(' ', array_filter([
                        (string) ($practice['alumno_apellido1'] ?? ''),
                        (string) ($practice['alumno_apellido2'] ?? ''),
                      ], static fn ($v) => trim((string) $v) !== '')));
                      $nombre = trim((string) ($practice['alumno_nombre'] ?? ''));
                      $nombre_completo = $apellidos !== '' && $nombre !== ''
                        ? $apellidos . ', ' . $nombre
                        : ($apellidos !== '' ? $apellidos : ($nombre !== '' ? $nombre : 'No disponible'));
                      $alumno_id = (int) ($practice['id_alumno'] ?? 0);
                      $alumno_nia = trim((string) ($practice['alumno_nia'] ?? ''));
                      $alumno_dni = trim((string) ($practice['alumno_dni'] ?? ''));
                      $alumno_telefono = trim((string) ($practice['alumno_telefono'] ?? ''));
                      $alumno_correo_educamadrid = trim((string) ($practice['alumno_correo_educamadrid'] ?? ''));
                      $alumno_correo_personal = trim((string) ($practice['alumno_correo_personal'] ?? ''));
                    ?>
                    <tr>
                      <td>
                        <span
                          class="alumno-name-trigger"
                          role="button"
                          tabindex="0"
                          aria-haspopup="dialog"
                          aria-expanded="false"
                          data-alumno-id="<?php echo $alumno_id; ?>"
                          data-alumno-nombre="<?php echo htmlspecialchars($nombre_completo, ENT_QUOTES, 'UTF-8'); ?>"
                          data-alumno-nia="<?php echo htmlspecialchars($alumno_nia, ENT_QUOTES, 'UTF-8'); ?>"
                          data-alumno-dni="<?php echo htmlspecialchars($alumno_dni, ENT_QUOTES, 'UTF-8'); ?>"
                          data-alumno-telefono="<?php echo htmlspecialchars($alumno_telefono, ENT_QUOTES, 'UTF-8'); ?>"
                          data-alumno-correo-educamadrid="<?php echo htmlspecialchars($alumno_correo_educamadrid, ENT_QUOTES, 'UTF-8'); ?>"
                          data-alumno-correo-personal="<?php echo htmlspecialchars($alumno_correo_personal, ENT_QUOTES, 'UTF-8'); ?>"
                          data-practica-id="<?php echo (int) $practice['id_practica']; ?>"
                        ><?php echo htmlspecialchars($nombre_completo, ENT_QUOTES, 'UTF-8'); ?></span>
                      </td>
                      <td><?php echo htmlspecialchars((string) ($practice['localidad_alumno'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars((string) ($practice['localidad_empresa'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars(format_date_listado($practice['fecha_inicio'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars(format_date_listado($practice['fecha_fin_extra'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      </section>
    </main>
  </div>

  <div class="practicas-ras-popover-layer" id="alumno-detail-layer" hidden>
    <button type="button" class="practicas-ras-popover-backdrop" data-popover-close tabindex="-1" aria-hidden="true"></button>
    <div class="practicas-ras-popover practicas-ras-popover--modulo practicas-ras-popover--empresa" id="alumno-detail-popover" role="dialog" aria-modal="false" aria-labelledby="alumno-detail-title" hidden>
      <div class="practicas-ras-popover__header">
        <span class="practicas-ras-popover__eyebrow">Alumno</span>
        <span id="alumno-detail-title" class="practicas-ras-popover__title"></span>
        <button type="button" class="practicas-ras-popover__close" data-popover-close aria-label="Cerrar detalle del alumno">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14" aria-hidden="true">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>
      <ul class="practicas-ras-popover__criteria" id="alumno-detail-data"></ul>
      <div class="practicas-ras-popover__footer">
        <a id="alumno-detail-link" class="practicas-ras-popover__link" href="#">Ver detalles de alumno →</a>
      </div>
    </div>
  </div>

  <script>
    const tableBody = document.querySelector('tbody');
    const alumnoLayer = document.getElementById('alumno-detail-layer');
    const alumnoPopover = document.getElementById('alumno-detail-popover');
    const alumnoTitle = document.getElementById('alumno-detail-title');
    const alumnoDetailList = document.getElementById('alumno-detail-data');
    const alumnoDetailLink = document.getElementById('alumno-detail-link');
    let activeAlumnoTrigger = null;

    if (alumnoLayer && alumnoPopover && alumnoTitle && alumnoDetailList) {
      const setAlumnoPopoverPosition = (trigger) => {
        const triggerRect = trigger.getBoundingClientRect();
        const popoverRect = alumnoPopover.getBoundingClientRect();
        const gutter = 12;
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;

        let top = triggerRect.top;
        let left = triggerRect.right + gutter;

        if (left + popoverRect.width > viewportWidth - gutter) {
          left = triggerRect.left - popoverRect.width - gutter;
        }

        if (left < gutter) {
          left = Math.min(viewportWidth - popoverRect.width - gutter, Math.max(gutter, triggerRect.left));
          top = triggerRect.bottom + gutter;
        }

        if (top + popoverRect.height > viewportHeight - gutter) {
          top = Math.max(gutter, viewportHeight - popoverRect.height - gutter);
        }

        alumnoPopover.style.top = `${Math.max(gutter, top)}px`;
        alumnoPopover.style.left = `${Math.max(gutter, left)}px`;
      };

      const closeAlumnoPopover = () => {
        alumnoPopover.hidden = true;
        alumnoLayer.hidden = true;
        if (activeAlumnoTrigger) {
          activeAlumnoTrigger.setAttribute('aria-expanded', 'false');
        }
        activeAlumnoTrigger = null;
      };

      const getAlumnoValueOrFallback = (value) => {
        const normalized = (value || '').trim();
        return normalized !== '' ? normalized : 'No disponible';
      };

      const addAlumnoInfoItem = (label, value) => {
        const item = document.createElement('li');
        const strong = document.createElement('strong');
        strong.textContent = label;
        item.appendChild(strong);
        const valueSpan = document.createElement('span');
        valueSpan.textContent = value;
        item.appendChild(valueSpan);
        alumnoDetailList.appendChild(item);
      };

      const addAlumnoCopyItem = (label, value) => {
        const item = document.createElement('li');
        const strong = document.createElement('strong');
        strong.textContent = label;
        item.appendChild(strong);
        const valueSpan = document.createElement('span');
        if (value !== '') {
          const trigger = document.createElement('span');
          trigger.className = 'copy-trigger';
          trigger.dataset.copy = value;
          trigger.textContent = value;
          valueSpan.appendChild(trigger);
        } else {
          valueSpan.textContent = 'No disponible';
        }
        item.appendChild(valueSpan);
        alumnoDetailList.appendChild(item);
      };

      const openAlumnoPopover = (trigger) => {
        if (activeAlumnoTrigger && activeAlumnoTrigger !== trigger) {
          activeAlumnoTrigger.setAttribute('aria-expanded', 'false');
        }

        alumnoTitle.textContent = trigger.dataset.alumnoNombre || 'Alumno';

        if (alumnoDetailLink) {
          const alumnoId = (trigger.dataset.alumnoId || '').trim();
          alumnoDetailLink.setAttribute('href', alumnoId !== '' ? `alumno_detalle.php?id_alumno=${encodeURIComponent(alumnoId)}` : '#');
        }

        alumnoDetailList.innerHTML = '';
        addAlumnoInfoItem('NIA', getAlumnoValueOrFallback(trigger.dataset.alumnoNia));
        addAlumnoInfoItem('DNI', getAlumnoValueOrFallback(trigger.dataset.alumnoDni));
        addAlumnoCopyItem('EducaMadrid', (trigger.dataset.alumnoCorreoEducamadrid || '').trim());
        addAlumnoCopyItem('Correo personal', (trigger.dataset.alumnoCorreoPersonal || '').trim());
        addAlumnoCopyItem('Teléfono', (trigger.dataset.alumnoTelefono || '').trim());

        activeAlumnoTrigger = trigger;
        trigger.setAttribute('aria-expanded', 'true');
        alumnoLayer.hidden = false;
        alumnoPopover.hidden = false;
        setAlumnoPopoverPosition(trigger);
      };

      tableBody.addEventListener('click', (event) => {
        const trigger = event.target.closest('.alumno-name-trigger');
        if (trigger && tableBody.contains(trigger)) {
          if (activeAlumnoTrigger === trigger && !alumnoPopover.hidden) {
            closeAlumnoPopover();
            return;
          }
          openAlumnoPopover(trigger);
          return;
        }
      });

      tableBody.addEventListener('keydown', (event) => {
        const trigger = event.target.closest('.alumno-name-trigger');
        if (trigger && tableBody.contains(trigger) && (event.key === 'Enter' || event.key === ' ')) {
          event.preventDefault();
          trigger.click();
        }
      });

      alumnoLayer.querySelectorAll('[data-popover-close]').forEach((element) => {
        element.addEventListener('click', closeAlumnoPopover);
      });

      window.addEventListener('resize', () => {
        if (activeAlumnoTrigger && !alumnoPopover.hidden) {
          setAlumnoPopoverPosition(activeAlumnoTrigger);
        }
      });

      window.addEventListener('scroll', () => {
        if (activeAlumnoTrigger && !alumnoPopover.hidden) {
          setAlumnoPopoverPosition(activeAlumnoTrigger);
        }
      }, true);

      document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !alumnoPopover.hidden) {
          closeAlumnoPopover();
        }
      });
    }
  </script>
  <script src="assets/copy.js"></script>
</body>
</html>
