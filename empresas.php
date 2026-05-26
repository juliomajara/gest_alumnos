<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pdo = db();

$search_term = trim((string) ($_GET['q'] ?? ''));
$sort = (string) ($_GET['sort'] ?? '');

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

$order_by_clause = 'e.nombre, e.apellido1, e.apellido2';
if ($sort === 'convenio') {
  $order_by_clause = 'e.convenio + 0 ASC';
} elseif ($sort === 'nombre') {
  $order_by_clause = 'CASE
    WHEN TRIM(COALESCE(e.nombre_comercial, \'\')) <> \'\'
      AND TRIM(CONCAT_WS(" ", e.nombre, e.apellido1, e.apellido2)) <> TRIM(COALESCE(e.nombre_comercial, \'\'))
      THEN TRIM(COALESCE(e.nombre_comercial, \'\'))
    ELSE TRIM(CONCAT_WS(" ", e.nombre, e.apellido1, e.apellido2))
  END ASC';
}

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

function render_company_rows(array $companies): string
{
  ob_start();
  if (!$companies): ?>
    <tr>
      <td colspan="6">No hay empresas para los filtros seleccionados.</td>
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
            <span data-copy="<?php echo htmlspecialchars($telefono, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($telefono, ENT_QUOTES, 'UTF-8'); ?></span>
          <?php endif; ?>
        </td>
        <td>
          <?php if ($correo !== ''): ?>
            <span data-copy="<?php echo htmlspecialchars($correo, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($correo, ENT_QUOTES, 'UTF-8'); ?></span>
          <?php endif; ?>
        </td>
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
        <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort, ENT_QUOTES, 'UTF-8'); ?>">
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
                <th><a class="practice-link" href="?<?php echo htmlspecialchars(http_build_query(['q' => $search_term, 'sort' => 'convenio']), ENT_QUOTES, 'UTF-8'); ?>">Convenio</a></th>
                <th><a class="practice-link" href="?<?php echo htmlspecialchars(http_build_query(['q' => $search_term, 'sort' => 'nombre']), ENT_QUOTES, 'UTF-8'); ?>">Nombre</a></th>
                <th>CIF</th>
                <th>Contacto</th>
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
      if (!target) {
        return;
      }

      showCopied(target);
    });


  </script>
</body>
</html>
