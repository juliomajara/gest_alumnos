<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pdo = db();

$active_course_id = $pdo->query('SELECT id_curso_escolar FROM cursos_escolares WHERE activo = 1 LIMIT 1')->fetchColumn();
$active_course_id = $active_course_id ? (int) $active_course_id : 0;

$search_term = trim((string) ($_GET['q'] ?? ''));
$allowed_orders_prof = ['nombre_asc', 'nombre_desc'];
$order_param_prof = (string) ($_GET['orden'] ?? '');
$current_order_prof = in_array($order_param_prof, $allowed_orders_prof, true) ? $order_param_prof : 'nombre_asc';
$sort_dir_prof = str_ends_with($current_order_prof, '_desc') ? 'desc' : 'asc';

function build_order_url_prof(string $cur_dir): string {
  $params = $_GET;
  $params['orden'] = 'nombre_' . ($cur_dir === 'asc' ? 'desc' : 'asc');
  unset($params['ajax']);
  $query = http_build_query($params);
  return 'profesores.php' . ($query !== '' ? '?' . $query : '');
}

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
$dir_prof = $sort_dir_prof === 'desc' ? 'DESC' : 'ASC';
$order_by_sql_prof = "p.apellido1 $dir_prof, p.apellido2 $dir_prof, p.nombre $dir_prof";
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
        m.id_modulo,
        "||",
        m.abreviatura,
        "||",
        COALESCE(NULLIF(m.materia_propia, ""), m.materia_general),
        "||",
        COALESCE(ci.ciclo, ""),
        "||",
        COALESCE(cu.curso, ""),
        "||",
        m.codigo,
        "||",
        m.horas_semanales,
        "||",
        m.horas_totales
      )
      ORDER BY m.abreviatura
      SEPARATOR "§§"
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
  LEFT JOIN ciclos ci ON ci.id_ciclo = m.id_ciclo
  LEFT JOIN cursos cu ON cu.id_curso = m.id_curso
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
  ORDER BY ' . $order_by_sql_prof
);

$teachers_stmt->execute($params);
$teachers = $teachers_stmt->fetchAll();

function display_value(mixed $value): string
{
  if ($value === null) {
    return '';
  }

  $normalized = trim((string) $value);
  if ($normalized === '' || mb_strtolower($normalized, 'UTF-8') === 'no disponible') {
    return '';
  }

  return $normalized;
}

function format_teacher_name(mixed $apellido1, mixed $apellido2, mixed $nombre): string
{
  $parts = [];
  $firstSurname = display_value($apellido1);
  $secondSurname = display_value($apellido2);
  $firstName = display_value($nombre);

  if ($firstSurname !== '') {
    $parts[] = $firstSurname;
  }

  if ($secondSurname !== '') {
    $parts[] = $secondSurname;
  }

  $surnameBlock = implode(' ', $parts);

  if ($surnameBlock !== '' && $firstName !== '') {
    return $surnameBlock . ', ' . $firstName;
  }

  if ($surnameBlock !== '') {
    return $surnameBlock;
  }

  return $firstName;
}

function format_modules(mixed $modules): string
{
  $cleanModules = display_value($modules);
  if ($cleanModules === '') {
    return '';
  }

  $items = array_filter(array_map('trim', explode('§§', $cleanModules)), static fn (string $item): bool => $item !== '');

  if (!$items) {
    return '';
  }

  $renderedItems = [];
  foreach ($items as $item) {
    $parts = array_map('trim', explode('||', $item));

    $id_modulo = (int) ($parts[0] ?? 0);
    $abreviatura = $parts[1] ?? '';
    $nombre_completo = $parts[2] ?? '';
    $ciclo = $parts[3] ?? '';
    $curso = $parts[4] ?? '';
    $codigo = $parts[5] ?? '';
    $horas_semanales = $parts[6] ?? '';
    $horas_totales = $parts[7] ?? '';

    if ($abreviatura === '') {
      continue;
    }

    $normalized_cycle = mb_strtoupper(trim((string) $ciclo), 'UTF-8');
    if ($normalized_cycle === 'SISTEMAS MICROINFORMÁTICOS Y REDES' || $normalized_cycle === 'SISTEMAS MICROINFORMATICOS Y REDES') {
      $normalized_cycle = 'SMR';
    } elseif ($normalized_cycle === 'DESARROLLO DE APLICACIONES WEB') {
      $normalized_cycle = 'DAW';
    } elseif ($normalized_cycle === 'DESARROLLO DE APLICACIONES MULTIPLATAFORMA') {
      $normalized_cycle = 'DAM';
    }

    $compact_cycle = preg_replace('/\s+/', '', $normalized_cycle . trim((string) $curso));
    $visible_name = $nombre_completo !== '' ? $nombre_completo : $abreviatura;
    $visible_label = $visible_name;

    $renderedItems[] = '<span '
      . 'class="empresa-name-trigger empresa-name-trigger--practicas" '
      . 'role="button" tabindex="0" aria-haspopup="dialog" aria-expanded="false" '
      . 'data-modulo-id="' . htmlspecialchars((string) $id_modulo, ENT_QUOTES, 'UTF-8') . '" '
      . 'data-modulo-nombre="' . htmlspecialchars($visible_label, ENT_QUOTES, 'UTF-8') . '" '
      . 'data-modulo-ciclo="' . htmlspecialchars($ciclo, ENT_QUOTES, 'UTF-8') . '" '
      . 'data-modulo-curso="' . htmlspecialchars($curso, ENT_QUOTES, 'UTF-8') . '" '
      . 'data-modulo-codigo="' . htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8') . '" '
      . 'data-modulo-horas-semanales="' . htmlspecialchars((string) $horas_semanales, ENT_QUOTES, 'UTF-8') . '" '
      . 'data-modulo-horas-totales="' . htmlspecialchars((string) $horas_totales, ENT_QUOTES, 'UTF-8') . '" '
      . 'data-modulo-compact="' . htmlspecialchars((string) $compact_cycle, ENT_QUOTES, 'UTF-8') . '" '
      . '>'
      . htmlspecialchars($abreviatura, ENT_QUOTES, 'UTF-8')
      . '</span>';
  }

  return implode(', ', $renderedItems);
}

function format_copyable_values(mixed $value): string
{
  $clean = display_value($value);
  if ($clean === '') {
    return '';
  }
  $items = array_filter(array_map('trim', explode(',', $clean)), static fn(string $s): bool => $s !== '');
  if (!$items) {
    return '';
  }
  $parts = [];
  foreach ($items as $item) {
    $escaped = htmlspecialchars($item, ENT_QUOTES, 'UTF-8');
    $parts[] = '<span class="copy-trigger" role="button" tabindex="0" title="Copiar al portapapeles" data-copy="' . $escaped . '">' . $escaped . '</span>';
  }
  return implode(', ', $parts);
}

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
        $nombreProfesor = format_teacher_name($teacher['apellido1'] ?? null, $teacher['apellido2'] ?? null, $teacher['nombre'] ?? null);
        $dni = display_value($teacher['dni'] ?? null);
        $grupos_tutor = display_value($teacher['grupos_tutor'] ?? null);
        $modulos = format_modules($teacher['modulos'] ?? null);
        $telefonos = display_value($teacher['telefonos'] ?? null);
        $correos = display_value($teacher['correos'] ?? null);
        $editarUrl = 'profesor_editar.php?id=' . (int) $teacher['id_profesor'];
      ?>
      <tr>
        <td><?php echo htmlspecialchars($nombreProfesor, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($dni, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($grupos_tutor, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo $modulos; ?></td>
        <td><?php echo format_copyable_values($telefonos); ?></td>
        <td><?php echo format_copyable_values($correos); ?></td>
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
        <input type="hidden" name="orden" value="<?php echo htmlspecialchars($current_order_prof, ENT_QUOTES, 'UTF-8'); ?>">
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
                <th><a class="practice-link" href="<?php echo htmlspecialchars(build_order_url_prof($sort_dir_prof), ENT_QUOTES, 'UTF-8'); ?>">Profesor<?php echo $sort_dir_prof === 'asc' ? ' ▲' : ' ▼'; ?></a></th>
                <th>DNI</th>
                <th>Tutor</th>
                <th>Módulos</th>
                <th>Teléfono</th>
                <th>Correo</th>
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

  <div class="modulo-tooltip" id="modulo-tooltip" hidden>
    <span class="modulo-tooltip__name" id="modulo-tooltip-name"></span>
    <span class="modulo-tooltip__separator" aria-hidden="true"></span>
    <span class="modulo-tooltip__hint">Haz clic para saber más</span>
  </div>

  <div class="practicas-ras-popover-layer" id="modulo-detail-layer" hidden>
    <button type="button" class="practicas-ras-popover-backdrop" data-popover-close tabindex="-1" aria-hidden="true"></button>
    <div class="practicas-ras-popover practicas-ras-popover--modulo" id="modulo-detail-popover" role="dialog" aria-modal="false" aria-labelledby="modulo-detail-title" hidden>
      <div class="practicas-ras-popover__header">
        <span class="practicas-ras-popover__eyebrow">Módulo</span>
        <span id="modulo-detail-title" class="practicas-ras-popover__title"></span>
        <button type="button" class="practicas-ras-popover__close" data-popover-close aria-label="Cerrar detalle del módulo">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14" aria-hidden="true">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>
      <ul class="practicas-ras-popover__criteria" id="modulo-detail-data"></ul>
      <div class="practicas-ras-popover__footer">
        <a id="modulo-detail-link" class="practicas-ras-popover__link" href="#">Ver módulo completo →</a>
      </div>
    </div>
  </div>

  <script>
    const form = document.querySelector('.topbar');
    const searchInput = document.querySelector('input[name="q"]');
    const tableBody = document.querySelector('tbody');
    const layer = document.getElementById('modulo-detail-layer');
    const popover = document.getElementById('modulo-detail-popover');
    const title = document.getElementById('modulo-detail-title');
    const detailLink = document.getElementById('modulo-detail-link');
    const detailList = document.getElementById('modulo-detail-data');
    const tooltip = document.getElementById('modulo-tooltip');
    const tooltipName = document.getElementById('modulo-tooltip-name');
    let debounceTimer = null;
    let activeTrigger = null;
    let tooltipVisible = false;

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

    if (layer && popover && title && detailList) {
      const setPopoverPosition = (trigger) => {
        const triggerRect = trigger.getBoundingClientRect();
        const popoverRect = popover.getBoundingClientRect();
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

        popover.style.top = `${Math.max(gutter, top)}px`;
        popover.style.left = `${Math.max(gutter, left)}px`;
      };

      const closePopover = () => {
        popover.hidden = true;
        layer.hidden = true;
        if (activeTrigger) {
          activeTrigger.setAttribute('aria-expanded', 'false');
        }
        activeTrigger = null;
      };

      const getValueOrFallback = (value) => {
        const normalized = (value || '').trim();
        return normalized !== '' ? normalized : 'No disponible';
      };

      const addInfoItem = (label, value) => {
        const item = document.createElement('li');
        const strong = document.createElement('strong');
        strong.textContent = label;
        item.appendChild(strong);
        const valueSpan = document.createElement('span');
        valueSpan.textContent = value;
        item.appendChild(valueSpan);
        detailList.appendChild(item);
      };

      const openPopover = (trigger) => {
        if (activeTrigger && activeTrigger !== trigger) {
          activeTrigger.setAttribute('aria-expanded', 'false');
        }

        title.textContent = trigger.dataset.moduloNombre || 'Módulo';
        const moduloId = (trigger.dataset.moduloId || '').trim();
        if (detailLink) {
          detailLink.setAttribute('href', moduloId !== '' ? `modulo_detalle.php?id_modulo=${encodeURIComponent(moduloId)}` : '#');
        }
        detailList.innerHTML = '';

        addInfoItem('Ciclo', getValueOrFallback(trigger.dataset.moduloCiclo));
        addInfoItem('Curso', getValueOrFallback(trigger.dataset.moduloCurso));
        addInfoItem('Código', getValueOrFallback(trigger.dataset.moduloCodigo));
        addInfoItem('Horas semanales', getValueOrFallback(trigger.dataset.moduloHorasSemanales));
        addInfoItem('Horas totales', getValueOrFallback(trigger.dataset.moduloHorasTotales));

        activeTrigger = trigger;
        trigger.setAttribute('aria-expanded', 'true');
        layer.hidden = false;
        popover.hidden = false;
        setPopoverPosition(trigger);
      };

      tableBody.addEventListener('click', (event) => {
        const trigger = event.target.closest('.empresa-name-trigger');
        if (trigger && tableBody.contains(trigger)) {
          if (activeTrigger === trigger && !popover.hidden) {
            closePopover();
            return;
          }

          openPopover(trigger);
          return;
        }
      });

      tableBody.addEventListener('keydown', (event) => {
        const trigger = event.target.closest('.empresa-name-trigger');
        if (trigger && tableBody.contains(trigger) && (event.key === 'Enter' || event.key === ' ')) {
          event.preventDefault();
          trigger.click();
        }
      });

      layer.querySelectorAll('[data-popover-close]').forEach((element) => {
        element.addEventListener('click', closePopover);
      });

      window.addEventListener('resize', () => {
        if (activeTrigger && !popover.hidden) {
          setPopoverPosition(activeTrigger);
        }
      });

      window.addEventListener('scroll', () => {
        if (activeTrigger && !popover.hidden) {
          setPopoverPosition(activeTrigger);
        }
      }, true);

      document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !popover.hidden) {
          closePopover();
        }
      });
    }

    if (tooltip && tooltipName) {
      const updateTooltipPosition = (x, y) => {
        const offset = 14;
        let left = x + offset;
        let top = y + offset;
        const w = tooltip.offsetWidth || 200;
        const h = tooltip.offsetHeight || 60;
        if (left + w > window.innerWidth - 8) {
          left = x - w - offset;
        }
        if (top + h > window.innerHeight - 8) {
          top = y - h - offset;
        }
        tooltip.style.left = `${Math.max(8, left)}px`;
        tooltip.style.top = `${Math.max(8, top)}px`;
      };

      tableBody.addEventListener('mouseover', (event) => {
        const trigger = event.target.closest('.empresa-name-trigger--practicas');
        if (trigger && tableBody.contains(trigger) && (!popover || popover.hidden)) {
          tooltipName.textContent = trigger.dataset.moduloNombre || trigger.textContent;
          tooltip.hidden = false;
          tooltipVisible = true;
          updateTooltipPosition(event.clientX, event.clientY);
        } else if (tooltipVisible && !event.target.closest('.empresa-name-trigger--practicas')) {
          tooltip.hidden = true;
          tooltipVisible = false;
        }
      });

      tableBody.addEventListener('mousemove', (event) => {
        if (tooltipVisible) {
          updateTooltipPosition(event.clientX, event.clientY);
        }
      });

      tableBody.addEventListener('mouseout', (event) => {
        if (tooltipVisible && !event.relatedTarget?.closest('.empresa-name-trigger--practicas')) {
          tooltip.hidden = true;
          tooltipVisible = false;
        }
      });
    }

    document.addEventListener('click', (event) => {
      const trigger = event.target.closest('.copy-trigger');
      if (!trigger) return;
      const text = (trigger.dataset.copy || '').trim();
      if (!text) return;
      navigator.clipboard.writeText(text).then(() => {
        trigger.classList.add('copy-trigger--copied');
        setTimeout(() => trigger.classList.remove('copy-trigger--copied'), 1600);
      }).catch(() => {});
    });

    document.addEventListener('keydown', (event) => {
      if ((event.key === 'Enter' || event.key === ' ') && event.target.closest('.copy-trigger')) {
        event.preventDefault();
        event.target.closest('.copy-trigger').click();
      }
    });
  </script>
</body>
</html>
