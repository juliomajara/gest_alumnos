<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pdo = db();

$search_term = trim((string) ($_GET['q'] ?? ''));
$allowed_orders_emp = ['nombre_asc', 'nombre_desc', 'convenio_asc', 'convenio_desc', 'practicas_asc', 'practicas_desc'];
$order_param_emp = (string) ($_GET['orden'] ?? '');
$current_order_emp = in_array($order_param_emp, $allowed_orders_emp, true) ? $order_param_emp : 'nombre_asc';
$_last_us_emp = strrpos($current_order_emp, '_');
$sort_col_emp = $_last_us_emp !== false ? substr($current_order_emp, 0, $_last_us_emp) : 'nombre';
$sort_dir_emp = $_last_us_emp !== false ? substr($current_order_emp, $_last_us_emp + 1) : 'asc';

function build_order_url_emp(string $col, string $cur_col, string $cur_dir): string {
  $params = $_GET;
  $params['orden'] = $col . '_' . (($col === $cur_col && $cur_dir === 'asc') ? 'desc' : 'asc');
  unset($params['ajax']);
  $query = http_build_query($params);
  return 'empresas.php' . ($query !== '' ? '?' . $query : '');
}
function sort_ind_emp(string $col, string $cur_col, string $cur_dir): string {
  if ($col !== $cur_col) return '';
  return $cur_dir === 'asc' ? ' ▲' : ' ▼';
}

$filters = [];
$params = [];

if ($search_term !== '') {
  $filters[] = '(
    TRIM(CONCAT_WS(" ", e.nombre, e.apellido1, e.apellido2)) LIKE :search_term_nombre
    OR e.cif LIKE :search_term_cif
    OR CAST(e.convenio AS CHAR) LIKE :search_term_convenio
  )';
  $search_like = '%' . $search_term . '%';
  $params['search_term_nombre'] = $search_like;
  $params['search_term_cif'] = $search_like;
  $params['search_term_convenio'] = $search_like;
}

$where_clause = $filters ? 'WHERE ' . implode(' AND ', $filters) : '';

$dir_emp = $sort_dir_emp === 'desc' ? 'DESC' : 'ASC';
$nombre_expr_emp = "CASE WHEN TRIM(COALESCE(e.nombre_comercial, '')) <> '' AND TRIM(CONCAT_WS(' ', e.nombre, e.apellido1, e.apellido2)) <> TRIM(COALESCE(e.nombre_comercial, '')) THEN TRIM(COALESCE(e.nombre_comercial, '')) ELSE TRIM(CONCAT_WS(' ', e.nombre, e.apellido1, e.apellido2)) END";
$order_by_clause = match ($sort_col_emp) {
  'convenio' => "e.convenio + 0 $dir_emp",
  default    => "$nombre_expr_emp $dir_emp",
};

$companies_stmt = $pdo->prepare(
  'SELECT
    e.id_empresa,
    e.cif,
    e.nombre,
    e.nombre_comercial,
    e.apellido1,
    e.apellido2,
    e.convenio,
    e.notas,
    TRIM(CONCAT_WS(" ", fc.nombre, fc.apellido1)) AS contacto,
    COALESCE(NULLIF(TRIM(t.telefono), \'\'), te.telefono) AS telefono,
    c.direccion_correo AS correo
  FROM empresas e
  LEFT JOIN (
    SELECT
      ec.id_empresa,
      ec.id_empresa_contacto,
      ec.nombre,
      ec.apellido1
    FROM empresas_contactos ec
    INNER JOIN (
      SELECT id_empresa, MIN(id_empresa_contacto) AS first_contacto_id
      FROM empresas_contactos
      GROUP BY id_empresa
    ) first_contacto
      ON first_contacto.first_contacto_id = ec.id_empresa_contacto
  ) fc ON fc.id_empresa = e.id_empresa
  LEFT JOIN (
    SELECT
      t1.id_entidad,
      t1.telefono
    FROM telefonos t1
    INNER JOIN (
      SELECT id_entidad, MIN(id_telefono) AS first_telefono_id
      FROM telefonos
      WHERE entidad_tipo = \'empresa_contacto\'
      GROUP BY id_entidad
    ) first_telefono
      ON first_telefono.first_telefono_id = t1.id_telefono
    WHERE t1.entidad_tipo = \'empresa_contacto\'
  ) t ON t.id_entidad = fc.id_empresa_contacto
  LEFT JOIN (
    SELECT
      t2.id_entidad,
      t2.telefono
    FROM telefonos t2
    INNER JOIN (
      SELECT id_entidad, MIN(id_telefono) AS first_telefono_id
      FROM telefonos
      WHERE entidad_tipo = \'empresa\'
      GROUP BY id_entidad
    ) first_empresa_telefono
      ON first_empresa_telefono.first_telefono_id = t2.id_telefono
    WHERE t2.entidad_tipo = \'empresa\'
  ) te ON te.id_entidad = e.id_empresa
  LEFT JOIN (
    SELECT
      c1.id_entidad,
      c1.direccion_correo
    FROM correos c1
    INNER JOIN (
      SELECT id_entidad, MIN(id_correo) AS first_correo_id
      FROM correos
      WHERE entidad_tipo = \'empresa_contacto\'
      GROUP BY id_entidad
    ) first_correo
      ON first_correo.first_correo_id = c1.id_correo
    WHERE c1.entidad_tipo = \'empresa_contacto\'
  ) c ON c.id_entidad = fc.id_empresa_contacto
  ' . $where_clause . '
  ORDER BY ' . $order_by_clause
);

$companies_stmt->execute($params);
$companies = $companies_stmt->fetchAll();

$practicas_emp_stmt = $pdo->query(
  'SELECT p.id_empresa,
          ce.curso_escolar,
          COUNT(DISTINCT p.id_alumno) AS num_alumnos,
          COALESCE(ci.abreviatura, \'\') AS ciclo
   FROM practicas p
   INNER JOIN alumno_curso ac ON ac.id_alumno = p.id_alumno
   INNER JOIN (
     SELECT id_alumno, MAX(id_curso_escolar) AS max_ce
     FROM alumno_curso
     GROUP BY id_alumno
   ) ac_max ON ac_max.id_alumno = p.id_alumno AND ac_max.max_ce = ac.id_curso_escolar
   INNER JOIN cursos_escolares ce ON ce.id_curso_escolar = ac.id_curso_escolar
   LEFT JOIN ciclos ci ON ci.id_ciclo = ac.id_ciclo
   WHERE p.cancelada = 0
   GROUP BY p.id_empresa, ce.id_curso_escolar, ci.id_ciclo, ce.curso_escolar, ci.abreviatura
   ORDER BY ce.curso_escolar ASC, ci.abreviatura ASC'
);
$practicas_por_empresa = [];
foreach ($practicas_emp_stmt->fetchAll() as $pr_row) {
  $practicas_por_empresa[(int) $pr_row['id_empresa']][] = $pr_row;
}

if ($sort_col_emp === 'practicas') {
  usort($companies, static function (array $a, array $b) use ($practicas_por_empresa, $sort_dir_emp): int {
    $pa = $practicas_por_empresa[(int) ($a['id_empresa'] ?? 0)] ?? [];
    $pb = $practicas_por_empresa[(int) ($b['id_empresa'] ?? 0)] ?? [];
    $last_a = $pa ? $pa[count($pa) - 1] : null;
    $last_b = $pb ? $pb[count($pb) - 1] : null;
    $curso_a = $last_a ? (string) $last_a['curso_escolar'] : '';
    $curso_b = $last_b ? (string) $last_b['curso_escolar'] : '';
    $cmp = strcmp($curso_a, $curso_b);
    if ($cmp !== 0) {
      return $sort_dir_emp === 'asc' ? $cmp : -$cmp;
    }
    $n_a = $last_a ? (int) $last_a['num_alumnos'] : 0;
    $n_b = $last_b ? (int) $last_b['num_alumnos'] : 0;
    return $sort_dir_emp === 'asc' ? ($n_a - $n_b) : ($n_b - $n_a);
  });
}

function render_company_rows(array $companies, array $practicas_por_empresa): string
{
  ob_start();
  if (!$companies): ?>
    <tr>
      <td colspan="7">No hay empresas para los filtros seleccionados.</td>
    </tr>
  <?php else: ?>
    <?php foreach ($companies as $company): ?>
      <?php
        $cif = (string) ($company['cif'] ?? '');
        $nombreCompleto = trim(implode(' ', array_filter([
          $company['nombre'] ?? '',
          $company['apellido1'] ?? '',
          $company['apellido2'] ?? ''
        ], static fn ($value) => trim((string) $value) !== '')));
        $nombreComercial = trim((string) ($company['nombre_comercial'] ?? ''));
        $nombreApellido1 = trim(implode(' ', array_filter([
          $company['nombre'] ?? '',
          $company['apellido1'] ?? ''
        ], static fn ($value) => trim((string) $value) !== '')));
        $nombre = $nombreCompleto !== '' ? $nombreCompleto : 'No disponible';
        if ($nombreComercial !== '' && $nombreComercial !== $nombreCompleto) {
          $nombre = $nombreComercial;
          if ($nombreApellido1 !== '') {
            $nombre .= ' (' . $nombreApellido1 . ')';
          }
        }
        $contacto = (string) ($company['contacto'] ?? '');
        $idEmpresa = (int) ($company['id_empresa'] ?? 0);
        $detalleUrl = 'empresa_detalle.php?id_empresa=' . $idEmpresa;
        $telefono = (string) ($company['telefono'] ?? '');
        $correo = (string) ($company['correo'] ?? '');
        $convenio = (string) ($company['convenio'] ?? '');
        $practicas_emp = $practicas_por_empresa[$idEmpresa] ?? [];
      ?>
      <tr>
        <td><?php echo htmlspecialchars($convenio, ENT_QUOTES, 'UTF-8'); ?></td>
        <td>
          <a
            href="<?php echo htmlspecialchars($detalleUrl, ENT_QUOTES, 'UTF-8'); ?>"
            class="practice-link"
          ><?php echo htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'); ?></a>
        </td>
        <td><?php echo htmlspecialchars($cif, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($contacto, ENT_QUOTES, 'UTF-8'); ?></td>
        <td>
          <?php if ($telefono !== ''): ?>
            <span class="copy-trigger" data-copy="<?php echo htmlspecialchars($telefono, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($telefono, ENT_QUOTES, 'UTF-8'); ?></span>
          <?php endif; ?>
        </td>
        <td>
          <?php if ($correo !== ''): ?>
            <span class="copy-trigger" data-copy="<?php echo htmlspecialchars($correo, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($correo, ENT_QUOTES, 'UTF-8'); ?></span>
          <?php endif; ?>
        </td>
        <td>
          <?php foreach ($practicas_emp as $pi => $pr): ?>
            <?php if ($pi > 0): ?><br><?php endif; ?>
            <span
              class="practicas-emp-trigger empresa-name-trigger--practicas"
              role="button"
              tabindex="0"
              aria-haspopup="dialog"
              aria-expanded="false"
              data-empresa-id="<?php echo $idEmpresa; ?>"
              data-empresa-nombre="<?php echo htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'); ?>"
            ><?php
              echo htmlspecialchars($pr['curso_escolar'], ENT_QUOTES, 'UTF-8')
                . ' - ' . (int) $pr['num_alumnos'];
              if ($pr['ciclo'] !== '') {
                echo ' - ' . htmlspecialchars($pr['ciclo'], ENT_QUOTES, 'UTF-8');
              }
            ?></span>
          <?php endforeach; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  <?php endif;

  return ob_get_clean();
}

$rows_html = render_company_rows($companies, $practicas_por_empresa);

if (($_GET['ajax'] ?? '') === '1') {
  header('Content-Type: text/html; charset=UTF-8');
  echo $rows_html;
  exit;
}

$alumnos_emp_stmt = $pdo->query(
  'SELECT p.id_empresa,
          ce.curso_escolar,
          a.id_alumno,
          TRIM(CONCAT_WS(\' \', a.nombre, a.apellido1, a.apellido2)) AS alumno_nombre,
          COALESCE(ci.abreviatura, \'\') AS ciclo,
          p.cancelada,
          p.fecha_inicio,
          p.fecha_fin_extra
   FROM practicas p
   INNER JOIN alumnos a ON a.id_alumno = p.id_alumno
   INNER JOIN alumno_curso ac ON ac.id_alumno = p.id_alumno
   INNER JOIN (
     SELECT id_alumno, MAX(id_curso_escolar) AS max_ce
     FROM alumno_curso
     GROUP BY id_alumno
   ) ac_max ON ac_max.id_alumno = p.id_alumno AND ac_max.max_ce = ac.id_curso_escolar
   INNER JOIN cursos_escolares ce ON ce.id_curso_escolar = ac.id_curso_escolar
   LEFT JOIN ciclos ci ON ci.id_ciclo = ac.id_ciclo
   ORDER BY ce.curso_escolar DESC, a.apellido1, a.apellido2, a.nombre'
);
$today = date('Y-m-d');
$alumnos_por_empresa = [];
foreach ($alumnos_emp_stmt->fetchAll() as $al_row) {
  $id_emp = (int) $al_row['id_empresa'];
  if ((int) $al_row['cancelada'] === 1) {
    $estado = 'Cancelada';
  } elseif ($al_row['fecha_fin_extra'] !== null && (string) $al_row['fecha_fin_extra'] < $today) {
    $estado = 'Finalizada';
  } elseif ($al_row['fecha_inicio'] !== null && (string) $al_row['fecha_inicio'] > $today) {
    $estado = 'En espera';
  } else {
    $estado = 'En curso';
  }
  $alumnos_por_empresa[$id_emp][] = [
    'curso'      => (string) $al_row['curso_escolar'],
    'id_alumno'  => (int) $al_row['id_alumno'],
    'nombre'     => (string) $al_row['alumno_nombre'],
    'ciclo'      => (string) $al_row['ciclo'],
    'estado'     => $estado,
  ];
}

$page_title = 'Empresas | Gestor de Alumnos';
$active_page = 'empresas';
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
          <h1>Empresas</h1>
          <p class="subheading">Consulta la información básica de las empresas registradas en el sistema.</p>
        </div>
        <div class="header-actions">
          <a class="edit-toggle edit-toggle-success" href="empresa_nueva.php"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>Añadir empresa</a>
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
            placeholder="Buscar por nombre o CIF"
            aria-label="Buscar por nombre o CIF"
            value="<?php echo htmlspecialchars($search_term, ENT_QUOTES, 'UTF-8'); ?>"
          >
        </div>
        <div class="topbar-actions"></div>
        <input type="hidden" name="orden" value="<?php echo htmlspecialchars($current_order_emp, ENT_QUOTES, 'UTF-8'); ?>">
      </form>

      <section class="panel">
        <div class="panel-header">
          <h3>Listado de empresas</h3>
          <p>CIF, razón social, datos de contacto, convenio y notas registradas.</p>
        </div>

        <div class="panel-grid">
          <table>
            <thead>
              <tr>
                <th><a class="practice-link" href="<?php echo htmlspecialchars(build_order_url_emp('convenio', $sort_col_emp, $sort_dir_emp), ENT_QUOTES, 'UTF-8'); ?>">Convenio<?php echo sort_ind_emp('convenio', $sort_col_emp, $sort_dir_emp); ?></a></th>
                <th><a class="practice-link" href="<?php echo htmlspecialchars(build_order_url_emp('nombre', $sort_col_emp, $sort_dir_emp), ENT_QUOTES, 'UTF-8'); ?>">Nombre<?php echo sort_ind_emp('nombre', $sort_col_emp, $sort_dir_emp); ?></a></th>
                <th>CIF</th>
                <th>Contacto</th>
                <th>Teléfono</th>
                <th>Correo</th>
                <th><?php
                  $_pr_next = ($sort_col_emp === 'practicas' && $sort_dir_emp === 'desc') ? 'asc' : 'desc';
                  $_pr_p = $_GET; $_pr_p['orden'] = 'practicas_' . $_pr_next; unset($_pr_p['ajax']);
                  $_pr_q = http_build_query($_pr_p);
                  $_pr_url = 'empresas.php' . ($_pr_q !== '' ? '?' . $_pr_q : '');
                ?><a class="practice-link" href="<?php echo htmlspecialchars($_pr_url, ENT_QUOTES, 'UTF-8'); ?>">Prácticas<?php echo sort_ind_emp('practicas', $sort_col_emp, $sort_dir_emp); ?></a></th>
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

        fetch(`empresas.php?${params.toString()}`, {
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


    const copyUsingFallback = (text) => {
      const helper = document.createElement('textarea');
      helper.value = text;
      helper.setAttribute('readonly', 'readonly');
      helper.style.position = 'fixed';
      helper.style.opacity = '0';
      helper.style.pointerEvents = 'none';
      document.body.appendChild(helper);
      helper.select();
      helper.setSelectionRange(0, helper.value.length);
      document.execCommand('copy');
      helper.remove();
    };

    const copyText = (text) => {
      if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
        return navigator.clipboard.writeText(text).catch(() => {
          copyUsingFallback(text);
        });
      }

      copyUsingFallback(text);
      return Promise.resolve();
    };

    const showCopied = (element) => {
      const originalText = element.dataset.originalText ?? element.textContent;
      const textToCopy = element.dataset.copy ?? '';
      if (textToCopy === '') {
        return;
      }

      element.dataset.originalText = originalText;

      if (element.dataset.copyTimerId) {
        window.clearTimeout(Number(element.dataset.copyTimerId));
      }

      element.textContent = 'Copiado!';
      copyText(textToCopy);

      const timerId = window.setTimeout(() => {
        element.textContent = element.dataset.originalText ?? originalText;
        delete element.dataset.copyTimerId;
      }, 1000);

      element.dataset.copyTimerId = String(timerId);
    };

    document.addEventListener('click', (event) => {
      const target = event.target.closest('[data-copy]');
      if (!target || target.classList.contains('copy-trigger')) {
        return;
      }

      showCopied(target);
    });


  </script>
  <script src="assets/copy.js"></script>

  <div class="practicas-ras-popover-layer" id="practicas-emp-layer" hidden>
    <button type="button" class="practicas-ras-popover-backdrop" data-emp-pract-close tabindex="-1" aria-hidden="true"></button>
    <div class="practicas-ras-popover practicas-ras-popover--modulo practicas-ras-popover--emp-alumnos" id="practicas-emp-popover" role="dialog" aria-modal="false" aria-labelledby="practicas-emp-title" hidden>
      <div class="practicas-ras-popover__header">
        <span class="practicas-ras-popover__eyebrow">Empresa</span>
        <span id="practicas-emp-title" class="practicas-ras-popover__title"></span>
        <button type="button" class="practicas-ras-popover__close" data-emp-pract-close aria-label="Cerrar detalle">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14" aria-hidden="true">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>
      <ul class="practicas-ras-popover__criteria" id="practicas-emp-data"></ul>
    </div>
  </div>

  <script>
    const alumnosPorEmpresa = <?php echo json_encode($alumnos_por_empresa, JSON_UNESCAPED_UNICODE); ?>;
    const empPractLayer = document.getElementById('practicas-emp-layer');
    const empPractPopover = document.getElementById('practicas-emp-popover');
    const empPractTitle = document.getElementById('practicas-emp-title');
    const empPractData = document.getElementById('practicas-emp-data');
    let activeEmpPractTrigger = null;

    const setEmpPractPosition = (trigger) => {
      const triggerRect = trigger.getBoundingClientRect();
      const popoverRect = empPractPopover.getBoundingClientRect();
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

      empPractPopover.style.top = `${Math.max(gutter, top)}px`;
      empPractPopover.style.left = `${Math.max(gutter, left)}px`;
    };

    const closeEmpPractPopover = () => {
      empPractPopover.hidden = true;
      empPractLayer.hidden = true;
      if (activeEmpPractTrigger) {
        activeEmpPractTrigger.setAttribute('aria-expanded', 'false');
      }
      activeEmpPractTrigger = null;
    };

    const openEmpPractPopover = (trigger) => {
      if (activeEmpPractTrigger && activeEmpPractTrigger !== trigger) {
        activeEmpPractTrigger.setAttribute('aria-expanded', 'false');
      }

      const empresaId = trigger.dataset.empresaId || '';
      const alumnos = alumnosPorEmpresa[empresaId] || [];

      empPractTitle.textContent = trigger.dataset.empresaNombre || 'Empresa';

      empPractData.innerHTML = '';
      if (alumnos.length === 0) {
        const item = document.createElement('li');
        item.appendChild(document.createElement('strong'));
        const span = document.createElement('span');
        span.textContent = 'Sin alumnos registrados';
        item.appendChild(span);
        empPractData.appendChild(item);
      } else {
        const byCourse = {};
        const courseOrder = [];
        alumnos.forEach((a) => {
          if (!byCourse[a.curso]) {
            byCourse[a.curso] = [];
            courseOrder.push(a.curso);
          }
          byCourse[a.curso].push(a);
        });
        courseOrder.forEach((curso) => {
          const item = document.createElement('li');
          const strong = document.createElement('strong');
          strong.textContent = curso;
          item.appendChild(strong);
          const span = document.createElement('span');
          byCourse[curso].forEach((a, i) => {
            if (i > 0) span.appendChild(document.createElement('br'));
            const link = document.createElement('a');
            link.className = 'empresa-name-trigger--practicas';
            link.href = `alumno_detalle.php?id_alumno=${encodeURIComponent(a.id_alumno)}`;
            link.textContent = a.ciclo ? `• ${a.nombre} (${a.ciclo})` : `• ${a.nombre}`;
            span.appendChild(link);
          });
          item.appendChild(span);
          empPractData.appendChild(item);
        });
      }

      activeEmpPractTrigger = trigger;
      trigger.setAttribute('aria-expanded', 'true');
      empPractLayer.hidden = false;
      empPractPopover.hidden = false;
      setEmpPractPosition(trigger);
    };

    document.addEventListener('click', (event) => {
      const trigger = event.target.closest('.practicas-emp-trigger');
      if (trigger) {
        if (activeEmpPractTrigger === trigger && !empPractPopover.hidden) {
          closeEmpPractPopover();
          return;
        }
        openEmpPractPopover(trigger);
        return;
      }
    });

    document.addEventListener('keydown', (event) => {
      const trigger = event.target.closest('.practicas-emp-trigger');
      if (trigger && (event.key === 'Enter' || event.key === ' ')) {
        event.preventDefault();
        trigger.click();
        return;
      }
      if (event.key === 'Escape' && !empPractPopover.hidden) {
        closeEmpPractPopover();
      }
    });

    empPractLayer.querySelectorAll('[data-emp-pract-close]').forEach((el) => {
      el.addEventListener('click', closeEmpPractPopover);
    });

    window.addEventListener('resize', () => {
      if (activeEmpPractTrigger && !empPractPopover.hidden) {
        setEmpPractPosition(activeEmpPractTrigger);
      }
    });

    window.addEventListener('scroll', () => {
      if (activeEmpPractTrigger && !empPractPopover.hidden) {
        setEmpPractPosition(activeEmpPractTrigger);
      }
    }, true);
  </script>
</body>
</html>
