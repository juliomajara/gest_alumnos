<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pdo = db();

$search_term = trim((string) ($_GET['q'] ?? ''));

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
    c.direccion_correo AS correo,
    (
      SELECT TRIM(CONCAT_WS(" ", ec.nombre, ec.apellido1, ec.apellido2))
      FROM empresas_contactos ec
      WHERE ec.id_empresa = e.id_empresa
      ORDER BY ec.id_empresa_contacto ASC
      LIMIT 1
    ) AS nombre_contacto,
    (
      SELECT cct.direccion_correo
      FROM correos cct
      WHERE cct.entidad_tipo = "empresa_contacto"
        AND cct.id_entidad = (
          SELECT ec1.id_empresa_contacto
          FROM empresas_contactos ec1
          WHERE ec1.id_empresa = e.id_empresa
          ORDER BY ec1.id_empresa_contacto ASC
          LIMIT 1
        )
      ORDER BY cct.id_correo ASC
      LIMIT 1
    ) AS email_contacto,
    (
      SELECT tct.telefono
      FROM telefonos tct
      WHERE tct.entidad_tipo = "empresa_contacto"
        AND tct.id_entidad = (
          SELECT ec2.id_empresa_contacto
          FROM empresas_contactos ec2
          WHERE ec2.id_empresa = e.id_empresa
          ORDER BY ec2.id_empresa_contacto ASC
          LIMIT 1
        )
      ORDER BY tct.id_telefono ASC
      LIMIT 1
    ) AS telefono_contacto,
    (
      SELECT TRIM(CONCAT_WS(" ", et.nombre, et.apellido1, et.apellido2))
      FROM empresas_tutores et
      WHERE et.id_empresa = e.id_empresa
      ORDER BY et.id_empresas_tutor ASC
      LIMIT 1
    ) AS nombre_tutor,
    (
      SELECT cet.direccion_correo
      FROM correos cet
      WHERE cet.entidad_tipo = "empresa_tutor"
        AND cet.id_entidad = (
          SELECT et1.id_empresas_tutor
          FROM empresas_tutores et1
          WHERE et1.id_empresa = e.id_empresa
          ORDER BY et1.id_empresas_tutor ASC
          LIMIT 1
        )
      ORDER BY cet.id_correo ASC
      LIMIT 1
    ) AS email_tutor,
    (
      SELECT tet.telefono
      FROM telefonos tet
      WHERE tet.entidad_tipo = "empresa_tutor"
        AND tet.id_entidad = (
          SELECT et2.id_empresas_tutor
          FROM empresas_tutores et2
          WHERE et2.id_empresa = e.id_empresa
          ORDER BY et2.id_empresas_tutor ASC
          LIMIT 1
        )
      ORDER BY tet.id_telefono ASC
      LIMIT 1
    ) AS telefono_tutor
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
      <td colspan="6">No hay empresas para los filtros seleccionados.</td>
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
        $idEmpresa = (int) ($company['id_empresa'] ?? 0);
        $detalleUrl = 'empresa_detalle.php?id_empresa=' . $idEmpresa;
        $editarUrl = 'empresa_editar.php?id_empresa=' . $idEmpresa;
        $telefono = $company['telefono'] ?: 'No disponible';
        $correo = $company['correo'] ?: 'No disponible';
        $convenio = $company['convenio'] ? (string) $company['convenio'] : 'No disponible';
        $nombreContacto = trim((string) ($company['nombre_contacto'] ?? '')) !== '' ? (string) $company['nombre_contacto'] : 'No disponible';
        $emailContacto = trim((string) ($company['email_contacto'] ?? '')) !== '' ? (string) $company['email_contacto'] : 'No disponible';
        $telefonoContacto = trim((string) ($company['telefono_contacto'] ?? '')) !== '' ? (string) $company['telefono_contacto'] : 'No disponible';
        $nombreTutor = trim((string) ($company['nombre_tutor'] ?? '')) !== '' ? (string) $company['nombre_tutor'] : 'No disponible';
        $emailTutor = trim((string) ($company['email_tutor'] ?? '')) !== '' ? (string) $company['email_tutor'] : 'No disponible';
        $telefonoTutor = trim((string) ($company['telefono_tutor'] ?? '')) !== '' ? (string) $company['telefono_tutor'] : 'No disponible';
      ?>
      <tr>
        <td><?php echo htmlspecialchars($convenio, ENT_QUOTES, 'UTF-8'); ?></td>
        <td>
          <a
            href="<?php echo htmlspecialchars($detalleUrl, ENT_QUOTES, 'UTF-8'); ?>"
            class="empresa-name-trigger"
            aria-haspopup="dialog"
            aria-expanded="false"
            data-empresa-nombre="<?php echo htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'); ?>"
            data-empresa-cif="<?php echo htmlspecialchars($cif, ENT_QUOTES, 'UTF-8'); ?>"
            data-contacto-nombre="<?php echo htmlspecialchars($nombreContacto, ENT_QUOTES, 'UTF-8'); ?>"
            data-contacto-email="<?php echo htmlspecialchars($emailContacto, ENT_QUOTES, 'UTF-8'); ?>"
            data-contacto-telefono="<?php echo htmlspecialchars($telefonoContacto, ENT_QUOTES, 'UTF-8'); ?>"
            data-tutor-nombre="<?php echo htmlspecialchars($nombreTutor, ENT_QUOTES, 'UTF-8'); ?>"
            data-tutor-email="<?php echo htmlspecialchars($emailTutor, ENT_QUOTES, 'UTF-8'); ?>"
            data-tutor-telefono="<?php echo htmlspecialchars($telefonoTutor, ENT_QUOTES, 'UTF-8'); ?>"
          ><?php echo htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'); ?></a>
        </td>
        <td><?php echo htmlspecialchars($cif, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($telefono, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($correo, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><a href="<?php echo htmlspecialchars($editarUrl, ENT_QUOTES, 'UTF-8'); ?>">Editar</a></td>
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
          <a class="edit-toggle" href="empresa_nueva.php">Añadir nueva empresa</a>
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

  <div class="practicas-ras-popover-layer" id="empresa-detail-layer" hidden>
    <button type="button" class="practicas-ras-popover-backdrop" data-popover-close tabindex="-1" aria-hidden="true"></button>
    <div class="practicas-ras-popover" id="empresa-detail-popover" role="dialog" aria-modal="false" aria-labelledby="empresa-detail-title" hidden>
      <button type="button" class="practicas-ras-popover__close" data-popover-close aria-label="Cerrar detalle de la empresa">×</button>
      <h3 id="empresa-detail-title" class="practicas-ras-popover__title"></h3>
      <ul class="practicas-ras-popover__criteria" id="empresa-detail-data"></ul>
    </div>
  </div>

  <script>
    const form = document.querySelector('.topbar');
    const searchInput = document.querySelector('input[name="q"]');
    const tableBody = document.querySelector('tbody');
    const layer = document.getElementById('empresa-detail-layer');
    const popover = document.getElementById('empresa-detail-popover');
    const title = document.getElementById('empresa-detail-title');
    const detailList = document.getElementById('empresa-detail-data');
    let debounceTimer = null;
    let activeTrigger = null;

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

    if (layer && popover && title && detailList) {
      const copyToClipboard = async (value) => {
        if (navigator.clipboard && window.isSecureContext) {
          await navigator.clipboard.writeText(value);
          return;
        }

        const helper = document.createElement('textarea');
        helper.value = value;
        helper.setAttribute('readonly', '');
        helper.style.position = 'absolute';
        helper.style.left = '-9999px';
        document.body.appendChild(helper);
        helper.select();
        document.execCommand('copy');
        document.body.removeChild(helper);
      };

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

      const addInfoItem = (label, value, canCopy = false) => {
        const item = document.createElement('li');
        const strong = document.createElement('strong');
        strong.textContent = `${label}: `;
        item.appendChild(strong);

        if (canCopy && value !== 'No disponible') {
          const button = document.createElement('button');
          button.type = 'button';
          button.className = 'ghost-button';
          button.textContent = value;
          button.dataset.copyValue = value;
          item.appendChild(button);
        } else {
          item.appendChild(document.createTextNode(value));
        }

        detailList.appendChild(item);
      };

      const openPopover = (trigger) => {
        if (activeTrigger && activeTrigger !== trigger) {
          activeTrigger.setAttribute('aria-expanded', 'false');
        }

        title.textContent = trigger.dataset.empresaNombre || 'Empresa';
        detailList.innerHTML = '';

        addInfoItem('Nombre de la empresa', trigger.dataset.empresaNombre || 'No disponible');
        addInfoItem('CIF', trigger.dataset.empresaCif || 'No disponible');
        addInfoItem('Nombre de la persona de contacto', trigger.dataset.contactoNombre || 'No disponible');
        addInfoItem('Correo de la persona de contacto', trigger.dataset.contactoEmail || 'No disponible', true);
        addInfoItem('Teléfono de la persona de contacto', trigger.dataset.contactoTelefono || 'No disponible', true);
        addInfoItem('Nombre del tutor', trigger.dataset.tutorNombre || 'No disponible');
        addInfoItem('Correo del tutor', trigger.dataset.tutorEmail || 'No disponible', true);
        addInfoItem('Teléfono del tutor', trigger.dataset.tutorTelefono || 'No disponible', true);

        activeTrigger = trigger;
        trigger.setAttribute('aria-expanded', 'true');
        layer.hidden = false;
        popover.hidden = false;
        setPopoverPosition(trigger);
      };

      tableBody.addEventListener('click', (event) => {
        const trigger = event.target.closest('.empresa-name-trigger');
        if (trigger && tableBody.contains(trigger)) {
          if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button === 1) {
            return;
          }

          event.preventDefault();

          if (activeTrigger === trigger && !popover.hidden) {
            closePopover();
            return;
          }

          openPopover(trigger);
          return;
        }

        const copyButton = event.target.closest('[data-copy-value]');
        if (!copyButton || !popover.contains(copyButton)) {
          return;
        }

        const copyValue = copyButton.dataset.copyValue || '';
        if (copyValue === '') {
          return;
        }

        copyToClipboard(copyValue)
          .then(() => {
            const originalText = copyButton.textContent;
            copyButton.textContent = 'Copiado';
            window.setTimeout(() => {
              copyButton.textContent = originalText;
            }, 1000);
          })
          .catch(() => {});
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
  </script>
</body>
</html>
