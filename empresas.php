<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pdo = db();

$search_term = trim((string) ($_GET['q'] ?? ''));

$filters = [];
$params = [];

if ($search_term !== '') {
  $filters[] = '(TRIM(CONCAT_WS(" ", e.nombre, e.apellido1, e.apellido2)) LIKE :search_term OR e.cif LIKE :search_term OR CAST(e.convenio AS CHAR) LIKE :search_term)';
  $params['search_term'] = '%' . $search_term . '%';
}

$where_clause = $filters ? 'WHERE ' . implode(' AND ', $filters) : '';

$companies_stmt = $pdo->prepare(
  'SELECT
    e.id_empresa,
    e.cif,
    e.nombre,
    e.apellido1,
    e.apellido2,
    e.convenio,
    e.notas,
    t.telefono,
    c.direccion_correo AS correo
  FROM empresas e
  LEFT JOIN (
    SELECT id_entidad, MIN(telefono) AS telefono
    FROM telefonos
    WHERE entidad_tipo = \'empresa\'
    GROUP BY id_entidad
  ) t ON t.id_entidad = e.id_empresa
  LEFT JOIN (
    SELECT id_entidad, MIN(direccion_correo) AS direccion_correo
    FROM correos
    WHERE entidad_tipo = \'empresa\'
    GROUP BY id_entidad
  ) c ON c.id_entidad = e.id_empresa
  ' . $where_clause . '
  ORDER BY e.nombre, e.apellido1, e.apellido2'
);

$companies_stmt->execute($params);
$companies = $companies_stmt->fetchAll();

function render_company_rows(array $companies): string
{
  ob_start();
  if (!$companies): ?>
    <tr>
      <td colspan="5">No hay empresas para los filtros seleccionados.</td>
    </tr>
  <?php else: ?>
    <?php foreach ($companies as $company): ?>
      <?php
        $cif = $company['cif'] ?: 'No disponible';
        $nombreCompleto = trim(implode(' ', array_filter([
          $company['nombre'] ?? '',
          $company['apellido1'] ?? '',
          $company['apellido2'] ?? ''
        ], static fn ($value) => trim((string) $value) !== '')));
        $nombre = $nombreCompleto !== '' ? $nombreCompleto : 'No disponible';
        $telefono = $company['telefono'] ?: 'No disponible';
        $correo = $company['correo'] ?: 'No disponible';
        $convenio = $company['convenio'] ? (string) $company['convenio'] : 'No disponible';
      ?>
      <tr>
        <td><?php echo htmlspecialchars($convenio, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($cif, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($telefono, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($correo, ENT_QUOTES, 'UTF-8'); ?></td>
      </tr>
    <?php endforeach; ?>
  <?php endif;

  return ob_get_clean();
}

$rows_html = render_company_rows($companies);

if (($_GET['ajax'] ?? '') === '1') {
  header('Content-Type: text/html; charset=UTF-8');
  echo $rows_html;
  exit;
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
                <th>Convenio</th>
                <th>Nombre</th>
                <th>CIF</th>
                <th>Teléfono</th>
                <th>Correo</th>
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
  </script>
</body>
</html>
