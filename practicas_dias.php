<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function format_student_name(array $row, string $prefix): string
{
  $apellido1 = trim((string) ($row[$prefix . '_apellido1'] ?? ''));
  $apellido2 = trim((string) ($row[$prefix . '_apellido2'] ?? ''));
  $nombre = trim((string) ($row[$prefix . '_nombre'] ?? ''));

  $apellidos = trim(implode(' ', array_filter([
    $apellido1,
    $apellido2,
  ], static fn (string $value): bool => $value !== '')));

  if ($apellidos === '' && $nombre === '') {
    return 'No disponible';
  }

  if ($apellidos === '') {
    return $nombre;
  }

  if ($nombre === '') {
    return $apellidos;
  }

  return $apellidos . ', ' . $nombre;
}

function calculate_practice_status(array $practice): string
{
  if ((int) ($practice['cancelada'] ?? 0) === 1) {
    return 'Cancelada';
  }

  $fecha_inicio = (string) ($practice['fecha_inicio'] ?? '');
  $fecha_fin_extra = (string) ($practice['fecha_fin_extra'] ?? '');

  if ($fecha_inicio === '' || $fecha_fin_extra === '') {
    return 'No disponible';
  }

  $today = (new DateTimeImmutable('today'))->format('Y-m-d');

  if ($today < $fecha_inicio) {
    return 'En espera';
  }

  if ($today <= $fecha_fin_extra) {
    return 'En curso';
  }

  return 'Finalizada';
}

$page_title = 'Días de prácticas | Gestor de Alumnos';
$active_page = 'practicas';

$months = [
  9 => 'sep',
  10 => 'oct',
  11 => 'nov',
  12 => 'dic',
  1 => 'ene',
  2 => 'feb',
  3 => 'mar',
  4 => 'abr',
  5 => 'may',
  6 => 'jun',
];

$allowed_orders = ['alumno_asc', 'alumno_desc', 'horas_asc', 'horas_desc', 'estado_asc', 'estado_desc'];
$order_param = (string) ($_GET['orden'] ?? '');
$current_order = in_array($order_param, $allowed_orders, true) ? $order_param : 'alumno_asc';
[$sort_col, $sort_dir] = explode('_', $current_order, 2);

function sort_url_dias(string $col, string $cur_col, string $cur_dir): string {
  $params = $_GET;
  $params['orden'] = $col . '_' . (($col === $cur_col && $cur_dir === 'asc') ? 'desc' : 'asc');
  return 'practicas_dias.php?' . http_build_query($params);
}
function sort_ind_dias(string $col, string $cur_col, string $cur_dir): string {
  if ($col !== $cur_col) return '';
  return $cur_dir === 'asc' ? ' ▲' : ' ▼';
}
$solo_activos = (string) ($_GET['solo_activos'] ?? '') === '1';

$fecha_param = trim((string) ($_GET['fecha'] ?? ''));
$fecha_seleccionada = null;
if ($fecha_param !== '') {
  $d = DateTimeImmutable::createFromFormat('Y-m-d', $fecha_param);
  if ($d !== false && $d->format('Y-m-d') === $fecha_param) {
    $fecha_seleccionada = $d->setTime(0, 0, 0);
  }
}
$fecha_input_value = ($fecha_seleccionada ?? new DateTimeImmutable('today'))->format('Y-m-d');

$student_rows = [];
$load_error = null;

try {
  $pdo = db();
  $active_course_id = $pdo->query('SELECT id_curso_escolar FROM cursos_escolares WHERE activo = 1 LIMIT 1')->fetchColumn();

  if ($active_course_id !== false) {
    $practices_stmt = $pdo->prepare(
      'SELECT DISTINCT
        p.id_practica,
        p.id_alumno,
        p.fecha_inicio,
        p.fecha_fin_extra,
        p.fecha_fin_real,
        p.cancelada,
        a.nombre AS alumno_nombre,
        a.apellido1 AS alumno_apellido1,
        a.apellido2 AS alumno_apellido2,
        a.nia AS alumno_nia,
        a.dni AS alumno_dni,
        atal.telefono AS alumno_telefono,
        acor_educa.direccion_correo AS alumno_correo_educamadrid,
        acor_personal.direccion_correo AS alumno_correo_personal
      FROM practicas p
      INNER JOIN alumnos a ON a.id_alumno = p.id_alumno
      INNER JOIN alumno_curso ac ON ac.id_alumno = p.id_alumno
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
      WHERE ac.id_curso_escolar = :active_course_id
      ORDER BY a.apellido1 ASC, a.apellido2 ASC, a.nombre ASC, p.id_practica ASC'
    );
    $practices_stmt->execute(['active_course_id' => $active_course_id]);
    $practices = $practices_stmt->fetchAll();

    if ($practices !== []) {
      $practice_ids = array_map(static fn (array $practice): int => (int) $practice['id_practica'], $practices);
      $placeholders = implode(', ', array_fill(0, count($practice_ids), '?'));

      $schedule_stmt = $pdo->prepare(
        'SELECT id_practica, dia_semana, hora_entrada, hora_salida
         FROM practicas_horario
         WHERE id_practica IN (' . $placeholders . ')
         ORDER BY id_practica ASC, dia_semana ASC, hora_entrada ASC'
      );
      $schedule_stmt->execute($practice_ids);
      $schedule_rows = $schedule_stmt->fetchAll();

      $non_teaching_days = $pdo->query('SELECT fecha FROM no_lectivos')->fetchAll(PDO::FETCH_COLUMN);
      $non_teaching_days_lookup = array_fill_keys($non_teaching_days, true);

      $schedule_by_practice = [];
      foreach ($schedule_rows as $row) {
        $practice_id = (int) $row['id_practica'];
        $day = (int) $row['dia_semana'];

        if (!isset($schedule_by_practice[$practice_id])) {
          $schedule_by_practice[$practice_id] = [];
        }
        if (!isset($schedule_by_practice[$practice_id][$day])) {
          $schedule_by_practice[$practice_id][$day] = [];
        }

        $schedule_by_practice[$practice_id][$day][] = $row;
      }

      $hoy = $fecha_seleccionada ?? new DateTimeImmutable('today');
      $current_month = (int) $hoy->format('n');
      $current_year = (int) $hoy->format('Y');
      $course_start_year = $current_month >= 9 ? $current_year : $current_year - 1;

      require __DIR__ . '/includes/practicas_dias_calculo.php';

      foreach ($practices as $p) {
        $sid = (int) $p['id_alumno'];
        if (!isset($student_rows[$sid])) {
          continue;
        }
        $fi = (string) ($p['fecha_inicio'] ?? '');
        $ff = (string) ($p['fecha_fin_extra'] ?? '');
        if (!isset($student_rows[$sid]['fecha_inicio'])) {
          $student_rows[$sid]['fecha_inicio'] = $fi;
          $student_rows[$sid]['fecha_fin'] = $ff;
        } else {
          if ($fi !== '' && ($student_rows[$sid]['fecha_inicio'] === '' || $fi < $student_rows[$sid]['fecha_inicio'])) {
            $student_rows[$sid]['fecha_inicio'] = $fi;
          }
          if ($ff !== '' && $ff > $student_rows[$sid]['fecha_fin']) {
            $student_rows[$sid]['fecha_fin'] = $ff;
          }
        }
        if (!isset($student_rows[$sid]['alumno_id'])) {
          $student_rows[$sid]['alumno_id'] = $sid;
          $student_rows[$sid]['alumno_nia'] = trim((string) ($p['alumno_nia'] ?? ''));
          $student_rows[$sid]['alumno_dni'] = trim((string) ($p['alumno_dni'] ?? ''));
          $student_rows[$sid]['alumno_telefono'] = trim((string) ($p['alumno_telefono'] ?? ''));
          $student_rows[$sid]['alumno_correo_educamadrid'] = trim((string) ($p['alumno_correo_educamadrid'] ?? ''));
          $student_rows[$sid]['alumno_correo_personal'] = trim((string) ($p['alumno_correo_personal'] ?? ''));
        }
      }

      foreach (array_keys($months) as $month_number) {
        $month_year = $month_number >= 9 ? $course_start_year : $course_start_year + 1;
        $months[$month_number] = sprintf('%s %s', $months[$month_number], substr((string) $month_year, -2));
      }

      if ($solo_activos) {
        $student_rows = array_filter(
          $student_rows,
          static fn (array $student): bool => (bool) ($student['had_current_month_in_course'] ?? false)
        );
      }

      usort($student_rows, static function (array $left, array $right) use ($sort_col, $sort_dir): int {
        $name_cmp = [$left['apellido1'], $left['name']] <=> [$right['apellido1'], $right['name']];
        $cmp = match ($sort_col) {
          'horas'  => ((float) $left['seconds'] <=> (float) $right['seconds']) ?: $name_cmp,
          'estado' => ($left['status'] <=> $right['status']) ?: $name_cmp,
          default  => $name_cmp,
        };
        return $sort_dir === 'desc' ? -$cmp : $cmp;
      });
    }
  }
} catch (Throwable $error) {
  $load_error = 'No se ha podido cargar el resumen de días de prácticas en este momento.';
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
          <h1>Días de prácticas por alumno</h1>
          <p class="subheading">Consulta los días de prácticas por mes y las horas realizadas a día de hoy.</p>
        </div>
        <div class="header-actions">
          <?php
            $solo_activos_params = $_GET;
            if ($solo_activos) {
              unset($solo_activos_params['solo_activos']);
            } else {
              $solo_activos_params['solo_activos'] = '1';
            }
            $solo_activos_query = http_build_query($solo_activos_params);
          ?>
          <a class="edit-toggle<?php echo $solo_activos ? ' is-active' : ''; ?>" href="practicas_dias.php<?php echo $solo_activos_query !== '' ? '?' . htmlspecialchars($solo_activos_query, ENT_QUOTES, 'UTF-8') : ''; ?>">Solo activos</a>
        </div>
      </header>

      <nav class="tab-nav">
        <a class="tab-nav-link" href="practicas.php">Prácticas</a>
        <a class="tab-nav-link active" href="practicas_dias.php">Días de prácticas</a>
        <a class="tab-nav-link" href="practicas_documentacion.php">Documentación</a>
        <a class="tab-nav-link" href="practicas_anexos.php">Seguimiento de Anexos</a>
        <a class="tab-nav-link" href="practicas_listado.php">Listado</a>
        <a class="tab-nav-link" href="practicas_contacto.php">Correos</a>
      </nav>

      <section class="panel">
        <div class="panel-header-with-actions">
          <div class="panel-header">
            <h3>Resumen mensual del curso actual</h3>
            <p>Alumnado con prácticas registradas, días por mes y horas acumuladas.</p>
          </div>
          <div class="panel-header-actions">
            <form method="get" id="form-fecha-calculo">
              <input type="hidden" name="orden" value="<?php echo htmlspecialchars($current_order, ENT_QUOTES, 'UTF-8'); ?>">
              <?php if ($solo_activos): ?><input type="hidden" name="solo_activos" value="1"><?php endif; ?>
              <input type="date" id="fecha-calculo" name="fecha" value="<?php echo htmlspecialchars($fecha_input_value, ENT_QUOTES, 'UTF-8'); ?>">
            </form>
          </div>
        </div>

        <div class="panel-grid">
          <table>
            <thead>
              <tr>
                <th><a class="practice-link" href="<?php echo htmlspecialchars(sort_url_dias('alumno', $sort_col, $sort_dir), ENT_QUOTES, 'UTF-8'); ?>">Alumno<?php echo sort_ind_dias('alumno', $sort_col, $sort_dir); ?></a></th>
                <th>Fecha inicio</th>
                <th>Fecha fin</th>
                <?php foreach ($months as $month_name): ?>
                  <th><?php echo htmlspecialchars($month_name, ENT_QUOTES, 'UTF-8'); ?></th>
                <?php endforeach; ?>
                <th><a class="practice-link" href="<?php echo htmlspecialchars(sort_url_dias('horas', $sort_col, $sort_dir), ENT_QUOTES, 'UTF-8'); ?>">Horas realizadas<?php echo sort_ind_dias('horas', $sort_col, $sort_dir); ?></a></th>
                <th><a class="practice-link" href="<?php echo htmlspecialchars(sort_url_dias('estado', $sort_col, $sort_dir), ENT_QUOTES, 'UTF-8'); ?>">Estado<?php echo sort_ind_dias('estado', $sort_col, $sort_dir); ?></a></th>
              </tr>
            </thead>
            <tbody>
              <?php if ($load_error !== null): ?>
                <tr>
                  <td colspan="15"><?php echo htmlspecialchars($load_error, ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
              <?php elseif ($student_rows === []): ?>
                <tr>
                  <td colspan="15">No hay prácticas registradas para el curso actual.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($student_rows as $student_row): ?>
                  <tr>
                    <td>
                      <span
                        class="alumno-name-trigger"
                        role="button"
                        tabindex="0"
                        aria-haspopup="dialog"
                        aria-expanded="false"
                        data-alumno-id="<?php echo (int) ($student_row['alumno_id'] ?? 0); ?>"
                        data-alumno-nombre="<?php echo htmlspecialchars((string) $student_row['name'], ENT_QUOTES, 'UTF-8'); ?>"
                        data-alumno-nia="<?php echo htmlspecialchars((string) ($student_row['alumno_nia'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                        data-alumno-dni="<?php echo htmlspecialchars((string) ($student_row['alumno_dni'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                        data-alumno-telefono="<?php echo htmlspecialchars((string) ($student_row['alumno_telefono'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                        data-alumno-correo-educamadrid="<?php echo htmlspecialchars((string) ($student_row['alumno_correo_educamadrid'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                        data-alumno-correo-personal="<?php echo htmlspecialchars((string) ($student_row['alumno_correo_personal'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                        data-practica-id="<?php echo (int) $student_row['id_practica']; ?>"
                      ><?php echo htmlspecialchars((string) $student_row['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </td>
                    <td><?php $fi = (string) ($student_row['fecha_inicio'] ?? ''); echo $fi !== '' ? date('d/m/Y', strtotime($fi)) : '—'; ?></td>
                    <td><?php $ff = (string) ($student_row['fecha_fin'] ?? ''); echo $ff !== '' ? date('d/m/Y', strtotime($ff)) : '—'; ?></td>
                    <?php foreach (array_keys($months) as $month_number): ?>
                      <td>
                        <?php $month_value = (string) $student_row['months'][$month_number] . (count($student_row['month_practice_ids'][$month_number] ?? []) > 1 ? '*' : ''); ?>
                        <?php if ($month_number === $current_month): ?>
                          <strong><?php echo htmlspecialchars($month_value, ENT_QUOTES, 'UTF-8'); ?></strong>
                        <?php else: ?>
                          <?php echo htmlspecialchars($month_value, ENT_QUOTES, 'UTF-8'); ?>
                        <?php endif; ?>
                      </td>
                    <?php endforeach; ?>
                    <td><?php echo htmlspecialchars(number_format(((float) $student_row['seconds']) / 3600, 2, ',', '.'), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars((string) $student_row['status'], ENT_QUOTES, 'UTF-8'); ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
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
        <a id="alumno-detail-link" class="practicas-ras-popover__link" href="#">Ver alumno completo →</a>
      </div>
    </div>
  </div>

  <script>
    (function () {
      var inp = document.getElementById('fecha-calculo');
      if (inp) {
        inp.addEventListener('change', function () { this.form.submit(); });
      }
    })();
  </script>
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
