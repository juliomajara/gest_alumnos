<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pdo = db();

$search_term = trim((string) ($_GET['q'] ?? ''));

$filters = [];
$params = [];

if ($search_term !== '') {
  $filters[] = '(m.codigo LIKE :search_term OR m.abreviatura LIKE :search_term1 OR m.materia_general LIKE :search_term2 OR m.materia_propia LIKE :search_term3 OR m.tipo LIKE :search_term4)';
  $params['search_term'] = '%' . $search_term . '%';
  $params['search_term1'] = '%' . $search_term . '%';
  $params['search_term2'] = '%' . $search_term . '%';
  $params['search_term3'] = '%' . $search_term . '%';
  $params['search_term4'] = '%' . $search_term . '%';
}

$where_clause = $filters ? 'WHERE ' . implode(' AND ', $filters) : '';

$modules_stmt = $pdo->prepare(
  'SELECT
    m.id_ciclo,
    c.abreviatura AS ciclo_abreviatura,
    m.id_curso,
    cu.curso,
    m.codigo,
    m.abreviatura,
    m.materia_general,
    m.materia_propia,
    m.horas_semanales,
    m.horas_totales
  FROM modulos m
  LEFT JOIN ciclos c ON c.id_ciclo = m.id_ciclo
  LEFT JOIN cursos cu ON cu.id_curso = m.id_curso
  ' . $where_clause . '
  ORDER BY c.abreviatura, cu.curso, m.abreviatura'
);

$modules_stmt->execute($params);
$modules = $modules_stmt->fetchAll();

function render_module_rows(array $modules): string
{
  ob_start();
  if (!$modules): ?>
    <tr>
      <td colspan="6">No hay módulos para los filtros seleccionados.</td>
    </tr>
  <?php else: ?>
    <?php foreach ($modules as $module): ?>
      <?php
        $ciclo = 'No disponible';
        $ciclo_abreviatura = trim((string) ($module['ciclo_abreviatura'] ?? ''));
        $curso_numero = trim((string) ($module['curso'] ?? ''));
        if ($ciclo_abreviatura !== '' || $curso_numero !== '') {
          $ciclo = $ciclo_abreviatura . $curso_numero;
        }

        $codigo = $module['codigo'] ?: 'No disponible';
        $abreviatura = $module['abreviatura'] ?: 'No disponible';
        $materia_propia = trim((string) ($module['materia_propia'] ?? ''));
        $materia_general = trim((string) ($module['materia_general'] ?? ''));
        $nombre = $materia_propia !== '' ? $materia_propia : ($materia_general !== '' ? $materia_general : 'No disponible');
        $horas_semanales = $module['horas_semanales'] !== null ? (string) $module['horas_semanales'] : 'No disponible';
        $horas_totales = $module['horas_totales'] !== null ? (string) $module['horas_totales'] : 'No disponible';
      ?>
      <tr>
        <td><?php echo htmlspecialchars($ciclo, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($abreviatura, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($horas_semanales, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($horas_totales, ENT_QUOTES, 'UTF-8'); ?></td>
      </tr>
    <?php endforeach; ?>
  <?php endif;

  return ob_get_clean();
}

$rows_html = render_module_rows($modules);

if (($_GET['ajax'] ?? '') === '1') {
  header('Content-Type: text/html; charset=UTF-8');
  echo $rows_html;
  exit;
}

$page_title = 'Módulos | Gestor de Alumnos';
$active_page = 'modulos';
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
          <h1>Módulos</h1>
          <p class="subheading">Consulta la información de los módulos formativos y su adscripción académica.</p>
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
            placeholder="Buscar por código, abreviatura o materia"
            aria-label="Buscar por código, abreviatura o materia"
            value="<?php echo htmlspecialchars($search_term, ENT_QUOTES, 'UTF-8'); ?>"
          >
        </div>
        <div class="topbar-actions"></div>
      </form>

      <section class="panel">
        <div class="panel-header">
          <h3>Listado de módulos</h3>
          <p>Nivel, ciclo, curso y datos generales de cada módulo registrado.</p>
        </div>

        <div class="panel-grid">
          <table>
            <thead>
              <tr>
                <th>Ciclo</th>
                <th>Código</th>
                <th>Abreviatura</th>
                <th>Nombre</th>
                <th>Horas semanales</th>
                <th>Horas totales</th>
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

        fetch(`modulos.php?${params.toString()}`, {
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
  </script>
</body>
</html>
