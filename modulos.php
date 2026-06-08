<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pdo = db();

$default_group_id = (int) ($pdo->query("SELECT valor FROM `config` WHERE `clave` = 'grupo_por_defecto' LIMIT 1")->fetchColumn() ?: 0);
$grupos = $pdo->query('SELECT id_grupo, grupo FROM grupos ORDER BY grupo')->fetchAll();
$valid_group_ids = array_map('intval', array_column($grupos, 'id_grupo'));
$selected_group_id = isset($_GET['id_grupo']) ? (int) $_GET['id_grupo'] : $default_group_id;
if (!in_array($selected_group_id, $valid_group_ids, true)) {
  $selected_group_id = 0;
}
$selected_group_name = '';
foreach ($grupos as $g) {
  if ((int) $g['id_grupo'] === $selected_group_id) {
    $selected_group_name = (string) $g['grupo'];
    break;
  }
}

$allowed_orders_mod = ['nombre_asc', 'nombre_desc', 'codigo_asc', 'codigo_desc', 'horas_semanales_asc', 'horas_semanales_desc', 'horas_totales_asc', 'horas_totales_desc'];
$order_param_mod = (string) ($_GET['orden'] ?? '');
$current_order_mod = in_array($order_param_mod, $allowed_orders_mod, true) ? $order_param_mod : 'nombre_asc';
$_last_us_mod = strrpos($current_order_mod, '_');
$sort_col_mod = $_last_us_mod !== false ? substr($current_order_mod, 0, $_last_us_mod) : 'nombre';
$sort_dir_mod = $_last_us_mod !== false ? substr($current_order_mod, $_last_us_mod + 1) : 'asc';

function build_order_url_modulos(string $col, string $cur_col, string $cur_dir): string {
  $params = $_GET;
  $params['orden'] = $col . '_' . (($col === $cur_col && $cur_dir === 'asc') ? 'desc' : 'asc');
  unset($params['ajax']);
  $query = http_build_query($params);
  return 'modulos.php' . ($query !== '' ? '?' . $query : '');
}
function sort_ind_modulos(string $col, string $cur_col, string $cur_dir): string {
  if ($col !== $cur_col) return '';
  return $cur_dir === 'asc' ? ' ▲' : ' ▼';
}

$modules = [];
$empty_message = '';

if ($selected_group_id === 0) {
  $empty_message = "Selecciona un grupo para ver los m\xC3\xB3dulos.";
} else {
  $dir_mod = $sort_dir_mod === 'desc' ? 'DESC' : 'ASC';
  $order_by_sql = match ($sort_col_mod) {
    'nombre'          => "ORDER BY COALESCE(m.materia_propia, m.materia_general) $dir_mod, c.abreviatura ASC, cu.curso ASC",
    'codigo'          => "ORDER BY m.codigo $dir_mod, c.abreviatura ASC, cu.curso ASC",
    'horas_semanales' => "ORDER BY COALESCE(m.horas_semanales, 0) $dir_mod, c.abreviatura ASC, cu.curso ASC",
    'horas_totales'   => "ORDER BY COALESCE(m.horas_totales, 0) $dir_mod, c.abreviatura ASC, cu.curso ASC",
    default           => 'ORDER BY c.abreviatura ASC, cu.curso ASC, m.abreviatura ASC',
  };
  $modules_stmt = $pdo->prepare(
    'SELECT
      m.id_modulo,
      m.id_ciclo,
      c.abreviatura AS ciclo_abreviatura,
      m.id_curso,
      cu.curso,
      m.codigo,
      m.abreviatura,
      m.materia_general,
      m.materia_propia,
      m.horas_semanales,
      m.horas_totales,
      COUNT(DISTINCT ra.id_ra) AS total_ra,
      COUNT(DISTINCT ce.id_ce) AS total_ce
    FROM modulos m
    LEFT JOIN ciclos c ON c.id_ciclo = m.id_ciclo
    LEFT JOIN cursos cu ON cu.id_curso = m.id_curso
    LEFT JOIN resultados_aprendizaje ra ON ra.id_modulo = m.id_modulo
    LEFT JOIN criterios_evaluacion ce ON ce.id_ra = ra.id_ra
    WHERE EXISTS (
      SELECT 1
      FROM grupos g
      WHERE g.id_ciclo = m.id_ciclo
        AND g.id_curso = m.id_curso
        AND g.id_grupo = :id_grupo
    )
    GROUP BY
      m.id_modulo,
      m.id_ciclo,
      c.abreviatura,
      m.id_curso,
      cu.curso,
      m.codigo,
      m.abreviatura,
      m.materia_general,
      m.materia_propia,
      m.horas_semanales,
      m.horas_totales
    ' . $order_by_sql
  );

  $modules_stmt->execute(['id_grupo' => $selected_group_id]);
  $modules = $modules_stmt->fetchAll();

  if ($modules === []) {
    $empty_message = "No hay m\xC3\xB3dulos para los filtros seleccionados.";
  }
}

function render_module_rows(array $modules, string $empty_message): string
{
  ob_start();
  if (!$modules): ?>
    <tr>
      <td colspan="8"><?php echo htmlspecialchars($empty_message, ENT_QUOTES, 'UTF-8'); ?></td>
    </tr>
  <?php else: ?>
    <?php foreach ($modules as $module): ?>
      <?php
        $ciclo = 'No disponible';
        $ciclo_abreviatura = trim((string) ($module['ciclo_abreviatura'] ?? ''));
        $curso_numero = trim((string) ($module['curso'] ?? ''));
        $curso_numero = str_replace("\xC2\xBA", '', $curso_numero);
        if ($ciclo_abreviatura !== '' || $curso_numero !== '') {
          $ciclo = $ciclo_abreviatura . $curso_numero;
        }

        $codigo = $module['codigo'] ?: 'No disponible';
        $abreviatura = $module['abreviatura'] ?: 'No disponible';
        $materia_propia = trim((string) ($module['materia_propia'] ?? ''));
        $materia_general = trim((string) ($module['materia_general'] ?? ''));
        $nombre = $materia_propia !== '' ? $materia_propia : ($materia_general !== '' ? $materia_general : 'No disponible');
        $id_modulo = (int) ($module['id_modulo'] ?? 0);
        $horas_semanales = $module['horas_semanales'] !== null ? (string) $module['horas_semanales'] : 'No disponible';
        $horas_totales = $module['horas_totales'] !== null ? (string) $module['horas_totales'] : 'No disponible';
        $total_ra = (int) ($module['total_ra'] ?? 0);
        $total_ce = (int) ($module['total_ce'] ?? 0);
      ?>
      <tr>
        <td><?php echo htmlspecialchars($ciclo, ENT_QUOTES, 'UTF-8'); ?></td>
        <td class="documents-cell">
          <?php if ($id_modulo > 0): ?>
            <a class="practice-link" href="modulo_detalle.php?id_modulo=<?php echo urlencode((string) $id_modulo); ?>"><?php echo htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'); ?></a>
          <?php else: ?>
            <?php echo htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'); ?>
          <?php endif; ?>
        </td>
        <td><?php echo htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($abreviatura, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($horas_semanales, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($horas_totales, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) $total_ra, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars((string) $total_ce, ENT_QUOTES, 'UTF-8'); ?></td>
      </tr>
    <?php endforeach; ?>
  <?php endif;

  return ob_get_clean();
}

$rows_html = render_module_rows($modules, $empty_message);

if (($_GET['ajax'] ?? '') === '1') {
  header('Content-Type: text/html; charset=UTF-8');
  echo $rows_html;
  exit;
}

$page_title = "M\xC3\xB3dulos | Gestor de Alumnos";
$active_page = 'modulos';
$modules_heading = $selected_group_id === 0
  ? 'Listado de m&oacute;dulos'
  : 'Listado de m&oacute;dulos de ' . htmlspecialchars($selected_group_name, ENT_QUOTES, 'UTF-8');
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
          <h1>M&oacute;dulos</h1>
          <p class="subheading">Consulta la informaci&oacute;n de los m&oacute;dulos formativos y su adscripci&oacute;n acad&eacute;mica.</p>
        </div>
      </header>

      <form method="get" class="topbar">
        <input type="hidden" name="orden" value="<?php echo htmlspecialchars($current_order_mod, ENT_QUOTES, 'UTF-8'); ?>">
        <div class="topbar-actions entity-grid entity-grid--4">
          <label class="calendar-select">
            <select name="id_grupo" id="id_grupo" aria-label="Filtrar por grupo">
              <option value="" <?php echo $selected_group_id === 0 ? 'selected' : ''; ?>>Selecciona grupo</option>
              <?php foreach ($grupos as $g): ?>
                <option value="<?php echo (int) $g['id_grupo']; ?>" <?php echo (int) $g['id_grupo'] === $selected_group_id ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars((string) $g['grupo'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </label>
        </div>
      </form>

      <section class="panel" id="modules-panel"<?php if ($selected_group_id === 0): ?> hidden<?php endif; ?>>
        <div class="panel-header">
          <h3 id="modules-heading"><?php echo $modules_heading; ?></h3>
          <p>Nivel, ciclo, curso y datos generales de cada m&oacute;dulo registrado.</p>
        </div>

        <div class="panel-grid">
          <table>
            <thead>
              <tr>
                <th>Ciclo</th>
                <th><a class="practice-link" href="<?php echo htmlspecialchars(build_order_url_modulos('nombre', $sort_col_mod, $sort_dir_mod), ENT_QUOTES, 'UTF-8'); ?>">Nombre<?php echo sort_ind_modulos('nombre', $sort_col_mod, $sort_dir_mod); ?></a></th>
                <th><a class="practice-link" href="<?php echo htmlspecialchars(build_order_url_modulos('codigo', $sort_col_mod, $sort_dir_mod), ENT_QUOTES, 'UTF-8'); ?>">C&oacute;digo<?php echo sort_ind_modulos('codigo', $sort_col_mod, $sort_dir_mod); ?></a></th>
                <th>Abreviatura</th>
                <th><a class="practice-link" href="<?php echo htmlspecialchars(build_order_url_modulos('horas_semanales', $sort_col_mod, $sort_dir_mod), ENT_QUOTES, 'UTF-8'); ?>">Horas semanales<?php echo sort_ind_modulos('horas_semanales', $sort_col_mod, $sort_dir_mod); ?></a></th>
                <th><a class="practice-link" href="<?php echo htmlspecialchars(build_order_url_modulos('horas_totales', $sort_col_mod, $sort_dir_mod), ENT_QUOTES, 'UTF-8'); ?>">Horas totales<?php echo sort_ind_modulos('horas_totales', $sort_col_mod, $sort_dir_mod); ?></a></th>
                <th>RA</th>
                <th>CE</th>
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
    const form = document.querySelector('form.topbar');
    const groupSelect = document.querySelector('select[name="id_grupo"]');
    const tableBody = document.querySelector('tbody');
    const modulesPanel = document.getElementById('modules-panel');
    const modulesHeading = document.querySelector('#modules-heading');

    const updateHeading = () => {
      if (!modulesHeading) {
        return;
      }

      const selectedOption = groupSelect.options[groupSelect.selectedIndex];
      modulesHeading.textContent = groupSelect.value === ''
        ? 'Listado de m\u00F3dulos'
        : `Listado de m\u00F3dulos de ${selectedOption.text}`;
    };

    const updateSortLinks = () => {
      document.querySelectorAll('thead a.practice-link').forEach((link) => {
        const url = new URL(link.href, window.location.href);
        const idGrupo = groupSelect.value;
        if (idGrupo) {
          url.searchParams.set('id_grupo', idGrupo);
        } else {
          url.searchParams.delete('id_grupo');
        }
        link.href = url.toString();
      });
    };

    const updateResults = () => {
      const params = new URLSearchParams(new FormData(form));
      const urlParams = new URLSearchParams(params);

      params.set('ajax', '1');

      fetch(`modulos.php?${params.toString()}`, {
        headers: {
          'X-Requested-With': 'fetch'
        }
      })
        .then((response) => response.text())
        .then((html) => {
          tableBody.innerHTML = html;
          if (modulesPanel) {
            modulesPanel.hidden = groupSelect.value === '';
          }
          updateHeading();
          updateSortLinks();
          history.replaceState(null, '', `?${urlParams.toString()}`);
        })
        .catch(() => {});
    };

    updateSortLinks();

    form.addEventListener('submit', (event) => {
      event.preventDefault();
      updateResults();
    });

    groupSelect.addEventListener('change', () => {
      updateResults();
    });
  </script>
</body>
</html>
