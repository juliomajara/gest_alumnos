<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pdo = db();

$search_term = trim((string) ($_GET['q'] ?? ''));
$allowed_orders = ['alumno', 'empresa'];
$order_param = (string) ($_GET['orden'] ?? 'alumno');
$current_order = in_array($order_param, $allowed_orders, true) ? $order_param : 'alumno';

$status = (string) ($_GET['status'] ?? '');
$error_code = (string) ($_GET['error'] ?? '');
$error_detail = trim((string) ($_GET['detail'] ?? ''));
$flash_message = null;

if ($status === 'deleted') {
  $flash_message = 'La práctica se ha eliminado correctamente.';
} elseif ($error_code !== '') {
  if ($error_code === 'invalid_id') {
    $flash_message = 'No se ha podido completar la operación porque el identificador de la práctica no es válido.';
  } elseif ($error_code === 'not_found') {
    $flash_message = 'No se ha encontrado la práctica solicitada o ya no está disponible.';
  } elseif ($error_code === 'bad_confirmation') {
    $flash_message = 'No se ha eliminado la práctica porque la confirmación del primer apellido es incorrecta.';
  } elseif ($error_code === 'invalid_csrf') {
    $flash_message = 'No se ha podido validar la solicitud de eliminación. Vuelve a intentarlo desde el listado.';
  } elseif ($error_code === 'delete_failed') {
    $flash_message = 'Se produjo un error al eliminar la práctica y se revirtieron todos los cambios.';
  } else {
    $flash_message = 'No se pudo completar la operación solicitada.';
  }

  if ($error_detail !== '') {
    $flash_message .= ' Detalle: ' . $error_detail;
  }
}

function format_date(?string $value, string $fallback = 'No disponible'): string
{
  if ($value === null || $value === '') {
    return $fallback;
  }

  $date = DateTime::createFromFormat('Y-m-d', $value);

  if ($date !== false && $date->format('Y-m-d') === $value) {
    return $date->format('d/m/Y');
  }

  return $value;
}

function calculate_practice_status(array $practice): string
{
  if ((int) ($practice['cancelada'] ?? 0) === 1) {
    return 'Cancelada';
  }

  $fecha_inicio = (string) ($practice['fecha_inicio'] ?? '');
  $fecha_fin_real = (string) ($practice['fecha_fin_real'] ?? '');

  if ($fecha_inicio === '' || $fecha_fin_real === '') {
    return 'No disponible';
  }

  $today = (new DateTimeImmutable('today'))->format('Y-m-d');

  if ($today < $fecha_inicio) {
    return 'En espera';
  }

  if ($today <= $fecha_fin_real) {
    return 'En curso';
  }

  return 'Finalizada';
}

function build_order_url(string $order): string
{
  $params = $_GET;
  $params['orden'] = $order;
  unset($params['ajax']);

  $query = http_build_query($params);

  return 'practicas.php' . ($query !== '' ? '?' . $query : '');
}

$filters = [];
$params = [];

if ($search_term !== '') {
  $filters[] = '(
    a.nombre LIKE :search_term_alumno_nombre
    OR a.apellido1 LIKE :search_term_alumno_apellido1
    OR a.apellido2 LIKE :search_term_alumno_apellido2
    OR a.dni LIKE :search_term_alumno_dni
    OR e.nombre LIKE :search_term_empresa_nombre
    OR e.apellido1 LIKE :search_term_empresa_apellido1
    OR e.apellido2 LIKE :search_term_empresa_apellido2
    OR e.cif LIKE :search_term_empresa_cif
    OR CAST(e.convenio AS CHAR) LIKE :search_term_empresa_convenio
  )';
  $search_like = '%' . $search_term . '%';
  $params['search_term_alumno_nombre'] = $search_like;
  $params['search_term_alumno_apellido1'] = $search_like;
  $params['search_term_alumno_apellido2'] = $search_like;
  $params['search_term_alumno_dni'] = $search_like;
  $params['search_term_empresa_nombre'] = $search_like;
  $params['search_term_empresa_apellido1'] = $search_like;
  $params['search_term_empresa_apellido2'] = $search_like;
  $params['search_term_empresa_cif'] = $search_like;
  $params['search_term_empresa_convenio'] = $search_like;
}

$where_clause = $filters ? 'WHERE ' . implode(' AND ', $filters) : '';

$order_clause = $current_order === 'empresa'
  ? 'ORDER BY e.nombre ASC, e.apellido1 ASC, e.apellido2 ASC, p.id_practica DESC'
  : 'ORDER BY a.apellido1 ASC, a.apellido2 ASC, a.nombre ASC, p.id_practica DESC';

$practices_stmt = $pdo->prepare(
  'SELECT
    p.id_practica,
    p.anexo,
    p.fecha_inicio,
    p.fecha_fin,
    p.fecha_fin_real,
    p.cancelada,
    a.nombre AS alumno_nombre,
    a.apellido1 AS alumno_apellido1,
    a.apellido2 AS alumno_apellido2,
    e.nombre AS empresa_nombre,
    e.apellido1 AS empresa_apellido1,
    e.apellido2 AS empresa_apellido2,
    e.convenio
  FROM practicas p
  INNER JOIN alumnos a
    ON a.id_alumno = p.id_alumno
  INNER JOIN empresas e
    ON e.id_empresa = p.id_empresa
  ' . $where_clause . '
  ' . $order_clause
);

$practices_stmt->execute($params);
$practices = $practices_stmt->fetchAll();

function render_practice_rows(array $practices): string
{
  ob_start();
  if (!$practices): ?>
    <tr>
      <td colspan="7">No hay prácticas para los filtros seleccionados.</td>
    </tr>
  <?php else: ?>
    <?php foreach ($practices as $practice): ?>
      <?php
        $alumno_apellido2 = trim((string) ($practice['alumno_apellido2'] ?? ''));
        $alumno_apellidos = trim(implode(' ', array_filter([
          $practice['alumno_apellido1'] ?? '',
          $alumno_apellido2,
        ], static fn ($value) => trim((string) $value) !== '')));
        $alumno_nombre = trim((string) ($practice['alumno_nombre'] ?? ''));
        $alumno = $alumno_apellidos !== '' || $alumno_nombre !== ''
          ? trim($alumno_apellidos . ', ' . $alumno_nombre, ' ,')
          : 'No disponible';

        $empresa = trim(implode(' ', array_filter([
          $practice['empresa_nombre'] ?? '',
          $practice['empresa_apellido1'] ?? '',
          $practice['empresa_apellido2'] ?? '',
        ], static fn ($value) => trim((string) $value) !== '')));
        $empresa = $empresa !== '' ? $empresa : 'No disponible';

        $fecha_inicio = format_date($practice['fecha_inicio'] ?? null);
        $fecha_fin = format_date($practice['fecha_fin'] ?? null);

        $convenio = $practice['convenio'] !== null && $practice['convenio'] !== ''
          ? (string) $practice['convenio']
          : 'No disponible';
        $anexo = $practice['anexo'] !== null && $practice['anexo'] !== ''
          ? (string) $practice['anexo']
          : 'No disponible';
        $anexo_21 = $convenio . ' / ' . $anexo;

        $estado = calculate_practice_status($practice);
      ?>
      <tr>
        <td>
          <a class="practice-link" href="practica_detalle.php?id_practica=<?php echo urlencode((string) $practice['id_practica']); ?>">
            <?php echo htmlspecialchars($alumno, ENT_QUOTES, 'UTF-8'); ?>
          </a>
        </td>
        <td><?php echo htmlspecialchars($empresa, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($fecha_inicio, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($fecha_fin, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($anexo_21, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($estado, ENT_QUOTES, 'UTF-8'); ?></td>
        <td>
          <a class="practice-link" href="practica_eliminar.php?id_practica=<?php echo urlencode((string) $practice['id_practica']); ?>">
            Eliminar
          </a>
        </td>
      </tr>
    <?php endforeach; ?>
  <?php endif;

  return ob_get_clean();
}

$rows_html = render_practice_rows($practices);

if (($_GET['ajax'] ?? '') === '1') {
  header('Content-Type: text/html; charset=UTF-8');
  echo $rows_html;
  exit;
}

$page_title = 'Prácticas | Gestor de Alumnos';
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
          <h1>Prácticas</h1>
          <p class="subheading">Consulta las prácticas registradas y su estado para cada alumno y empresa.</p>
        </div>
        <div class="header-actions">
          <a class="edit-toggle" href="practica_nueva.php">Añadir nueva práctica</a>
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
            placeholder="Buscar por alumno, empresa, DNI, CIF o convenio"
            aria-label="Buscar por alumno, empresa, DNI, CIF o convenio"
            value="<?php echo htmlspecialchars($search_term, ENT_QUOTES, 'UTF-8'); ?>"
          >
          <input type="hidden" name="orden" value="<?php echo htmlspecialchars($current_order, ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="topbar-actions"></div>
      </form>

      <section class="panel">
        <?php if ($flash_message !== null): ?>
          <p><?php echo htmlspecialchars($flash_message, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <div class="panel-header">
          <h3>Listado de prácticas</h3>
          <p>Alumno, empresa, periodo, anexo 2.1 y estado de cada práctica.</p>
        </div>

        <div class="panel-grid">
          <table>
            <thead>
              <tr>
                <th><a class="practice-link" href="<?php echo htmlspecialchars(build_order_url('alumno'), ENT_QUOTES, 'UTF-8'); ?>">Alumno</a></th>
                <th><a class="practice-link" href="<?php echo htmlspecialchars(build_order_url('empresa'), ENT_QUOTES, 'UTF-8'); ?>">Empresa</a></th>
                <th>Fecha de inicio</th>
                <th>Fecha de fin</th>
                <th>Anexo 2.1</th>
                <th>Estado</th>
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

        fetch(`practicas.php?${params.toString()}`, {
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
