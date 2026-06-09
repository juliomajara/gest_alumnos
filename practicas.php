<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pdo = db();

$search_term = trim((string) ($_GET['q'] ?? ''));
$allowed_orders = ['alumno_asc', 'alumno_desc', 'empresa_asc', 'empresa_desc', 'fecha_inicio_asc', 'fecha_inicio_desc', 'fecha_fin_asc', 'fecha_fin_desc', 'anexo_asc', 'anexo_desc', 'estado_asc', 'estado_desc'];
$order_param = (string) ($_GET['orden'] ?? '');
$current_order = in_array($order_param, $allowed_orders, true) ? $order_param : 'alumno_asc';
$_last_us = strrpos($current_order, '_');
$sort_col = $_last_us !== false ? substr($current_order, 0, $_last_us) : 'alumno';
$sort_dir = $_last_us !== false ? substr($current_order, $_last_us + 1) : 'asc';

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

  $fecha_inicio = $practice['fecha_inicio'] ?? null;
  $fecha_fin_efectiva = $practice['fecha_fin_extra'] ?? $practice['fecha_fin'] ?? null;

  if ($fecha_inicio === null || $fecha_fin_efectiva === null) {
    return 'No disponible';
  }

  $today = (new DateTimeImmutable('today'))->format('Y-m-d');

  if ($today < $fecha_inicio) {
    return 'En espera';
  }

  if ($today <= $fecha_fin_efectiva) {
    return 'En curso';
  }

  return 'Finalizada';
}

function build_order_url(string $col, string $cur_col, string $cur_dir): string
{
  $params = $_GET;
  $params['orden'] = $col . '_' . (($col === $cur_col && $cur_dir === 'asc') ? 'desc' : 'asc');
  unset($params['ajax']);
  $query = http_build_query($params);
  return 'practicas.php' . ($query !== '' ? '?' . $query : '');
}
function sort_ind(string $col, string $cur_col, string $cur_dir): string
{
  if ($col !== $cur_col) return '';
  return $cur_dir === 'asc' ? ' ▲' : ' ▼';
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

$dir = $sort_dir === 'desc' ? 'DESC' : 'ASC';
$order_clause = match ($sort_col) {
  'empresa'      => "ORDER BY e.nombre $dir, e.apellido1 $dir, e.apellido2 $dir, p.id_practica DESC",
  'fecha_inicio' => "ORDER BY p.fecha_inicio $dir, p.id_practica DESC",
  'fecha_fin'    => "ORDER BY COALESCE(p.fecha_fin_extra, p.fecha_fin) $dir, p.id_practica DESC",
  'anexo'        => "ORDER BY CAST(p.anexo AS UNSIGNED) $dir, p.id_practica DESC",
  'estado'       => "ORDER BY CASE WHEN p.cancelada = 1 THEN 1 WHEN p.fecha_inicio IS NULL OR COALESCE(p.fecha_fin_extra, p.fecha_fin) IS NULL THEN 2 WHEN CURDATE() < p.fecha_inicio THEN 3 WHEN CURDATE() <= COALESCE(p.fecha_fin_extra, p.fecha_fin) THEN 4 ELSE 5 END $dir, p.id_practica DESC",
  default        => "ORDER BY a.apellido1 $dir, a.apellido2 $dir, a.nombre $dir, p.id_practica DESC",
};

$practices_stmt = $pdo->prepare(
  'SELECT
    p.id_practica,
    p.anexo,
    p.fecha_inicio,
    p.fecha_fin,
    p.fecha_fin_extra,
    p.cancelada,
    p.fecha_fin_real,
    a.id_alumno,
    a.nombre AS alumno_nombre,
    a.apellido1 AS alumno_apellido1,
    a.apellido2 AS alumno_apellido2,
    a.nia AS alumno_nia,
    a.dni AS alumno_dni,
    e.id_empresa,
    e.nombre AS empresa_nombre,
    e.nombre_comercial AS empresa_nombre_comercial,
    e.apellido1 AS empresa_apellido1,
    e.apellido2 AS empresa_apellido2,
    e.convenio,
    e.cif AS empresa_cif,
    (
      SELECT TRIM(CONCAT_WS(" ", ec.nombre, ec.apellido1, ec.apellido2))
      FROM empresas_contactos ec
      WHERE ec.id_empresa = e.id_empresa
      ORDER BY ec.id_empresa_contacto ASC
      LIMIT 1
    ) AS empresa_contacto_nombre,
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
    ) AS empresa_contacto_email,
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
    ) AS empresa_contacto_telefono,
    (
      SELECT TRIM(CONCAT_WS(" ", et.nombre, et.apellido1, et.apellido2))
      FROM empresas_tutores et
      WHERE et.id_empresa = e.id_empresa
      ORDER BY et.id_empresas_tutor ASC
      LIMIT 1
    ) AS empresa_tutor_nombre,
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
    ) AS empresa_tutor_email,
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
    ) AS empresa_tutor_telefono,
    ld.nombre AS practica_localidad,
    atal.telefono AS alumno_telefono,
    acor_educa.direccion_correo AS alumno_correo_educamadrid,
    acor_personal.direccion_correo AS alumno_correo_personal
  FROM practicas p
  INNER JOIN alumnos a
    ON a.id_alumno = p.id_alumno
  INNER JOIN empresas e
    ON e.id_empresa = p.id_empresa
  LEFT JOIN direcciones d
    ON d.id_direccion = p.id_direccion
  LEFT JOIN localidades ld
    ON ld.id_localidad = d.id_localidad
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
  ' . $where_clause . '
  ' . $order_clause
);

$practices_stmt->execute($params);
$practices = $practices_stmt->fetchAll();

function render_practice_rows(array $practices): string
{
  $format_person_display_name = static function (?string $full_name): string {
    $parts = preg_split('/\s+/', trim((string) $full_name)) ?: [];
    $parts = array_values(array_filter($parts, static fn ($part) => $part !== ''));

    if ($parts === []) {
      return 'No disponible';
    }

    if (count($parts) === 1) {
      return $parts[0];
    }

    return $parts[0] . ' ' . $parts[1];
  };

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

        $empresa_nombre_completo = trim(implode(' ', array_filter([
          $practice['empresa_nombre'] ?? '',
          $practice['empresa_apellido1'] ?? '',
          $practice['empresa_apellido2'] ?? '',
        ], static fn ($value) => trim((string) $value) !== '')));
        $empresa_nombre_comercial = trim((string) ($practice['empresa_nombre_comercial'] ?? ''));
        $empresa_nombre_apellido1 = trim(implode(' ', array_filter([
          $practice['empresa_nombre'] ?? '',
          $practice['empresa_apellido1'] ?? '',
        ], static fn ($value) => trim((string) $value) !== '')));
        $empresa = $empresa_nombre_completo !== '' ? $empresa_nombre_completo : 'No disponible';
        if ($empresa_nombre_comercial !== '' && $empresa_nombre_comercial !== $empresa_nombre_completo) {
          $empresa = $empresa_nombre_comercial;
          if ($empresa_nombre_apellido1 !== '') {
            $empresa .= ' (' . $empresa_nombre_apellido1 . ')';
          }
        }
        $empresa_cif = trim((string) ($practice['empresa_cif'] ?? ''));
        $empresa_cif = $empresa_cif !== '' ? $empresa_cif : 'No disponible';
        $empresa_contacto_nombre = $format_person_display_name($practice['empresa_contacto_nombre'] ?? null);
        $empresa_contacto_email = trim((string) ($practice['empresa_contacto_email'] ?? ''));
        $empresa_contacto_email = $empresa_contacto_email !== '' ? $empresa_contacto_email : 'No disponible';
        $empresa_contacto_telefono = trim((string) ($practice['empresa_contacto_telefono'] ?? ''));
        $empresa_contacto_telefono = $empresa_contacto_telefono !== '' ? $empresa_contacto_telefono : 'No disponible';
        $empresa_tutor_nombre = $format_person_display_name($practice['empresa_tutor_nombre'] ?? null);
        $empresa_tutor_email = trim((string) ($practice['empresa_tutor_email'] ?? ''));
        $empresa_tutor_email = $empresa_tutor_email !== '' ? $empresa_tutor_email : 'No disponible';
        $empresa_tutor_telefono = trim((string) ($practice['empresa_tutor_telefono'] ?? ''));
        $empresa_tutor_telefono = $empresa_tutor_telefono !== '' ? $empresa_tutor_telefono : 'No disponible';
        $empresa_localidad = trim((string) ($practice['practica_localidad'] ?? ''));

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
        $fecha_fin_real_raw = $practice['fecha_fin_real'] ?? null;
        if ($estado === 'Finalizada') {
          $fecha_fin_real = ($fecha_fin_real_raw !== null && $fecha_fin_real_raw !== '')
            ? format_date($fecha_fin_real_raw)
            : 'Fecha prevista';
        } elseif ($estado === 'Cancelada') {
          $fecha_fin_real = format_date($fecha_fin_real_raw);
        } else {
          $fecha_fin_real = '';
        }
        $alumno_id = (int) ($practice['id_alumno'] ?? 0);
        $alumno_nia = trim((string) ($practice['alumno_nia'] ?? ''));
        $alumno_dni = trim((string) ($practice['alumno_dni'] ?? ''));
        $alumno_telefono = trim((string) ($practice['alumno_telefono'] ?? ''));
        $alumno_correo_educamadrid = trim((string) ($practice['alumno_correo_educamadrid'] ?? ''));
        $alumno_correo_personal = trim((string) ($practice['alumno_correo_personal'] ?? ''));
        $alumno_html = htmlspecialchars($alumno, ENT_QUOTES, 'UTF-8');

        if ((int) ($practice['cancelada'] ?? 0) === 1) {
          $alumno_html = '<s>' . $alumno_html . '</s>';
        }
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
            data-alumno-nombre="<?php echo htmlspecialchars($alumno, ENT_QUOTES, 'UTF-8'); ?>"
            data-alumno-nia="<?php echo htmlspecialchars($alumno_nia, ENT_QUOTES, 'UTF-8'); ?>"
            data-alumno-dni="<?php echo htmlspecialchars($alumno_dni, ENT_QUOTES, 'UTF-8'); ?>"
            data-alumno-telefono="<?php echo htmlspecialchars($alumno_telefono, ENT_QUOTES, 'UTF-8'); ?>"
            data-alumno-correo-educamadrid="<?php echo htmlspecialchars($alumno_correo_educamadrid, ENT_QUOTES, 'UTF-8'); ?>"
            data-alumno-correo-personal="<?php echo htmlspecialchars($alumno_correo_personal, ENT_QUOTES, 'UTF-8'); ?>"
            data-practica-id="<?php echo (int) $practice['id_practica']; ?>"
          ><?php echo $alumno_html; ?></span>
        </td>
        <td>
          <span
            class="empresa-name-trigger empresa-name-trigger--practicas"
            role="button"
            tabindex="0"
            aria-haspopup="dialog"
            aria-expanded="false"
            data-empresa-id="<?php echo (int) $practice['id_empresa']; ?>"
            data-empresa-localidad="<?php echo htmlspecialchars($empresa_localidad, ENT_QUOTES, 'UTF-8'); ?>"
            data-empresa-nombre="<?php echo htmlspecialchars($empresa, ENT_QUOTES, 'UTF-8'); ?>"
            data-empresa-cif="<?php echo htmlspecialchars($empresa_cif, ENT_QUOTES, 'UTF-8'); ?>"
            data-contacto-nombre="<?php echo htmlspecialchars($empresa_contacto_nombre, ENT_QUOTES, 'UTF-8'); ?>"
            data-contacto-email="<?php echo htmlspecialchars($empresa_contacto_email, ENT_QUOTES, 'UTF-8'); ?>"
            data-contacto-telefono="<?php echo htmlspecialchars($empresa_contacto_telefono, ENT_QUOTES, 'UTF-8'); ?>"
            data-tutor-nombre="<?php echo htmlspecialchars($empresa_tutor_nombre, ENT_QUOTES, 'UTF-8'); ?>"
            data-tutor-email="<?php echo htmlspecialchars($empresa_tutor_email, ENT_QUOTES, 'UTF-8'); ?>"
            data-tutor-telefono="<?php echo htmlspecialchars($empresa_tutor_telefono, ENT_QUOTES, 'UTF-8'); ?>"
          ><?php echo htmlspecialchars($empresa, ENT_QUOTES, 'UTF-8'); ?></span>
        </td>
        <td><?php echo htmlspecialchars($fecha_inicio, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($fecha_fin, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($fecha_fin_real, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($anexo_21, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($estado, ENT_QUOTES, 'UTF-8'); ?></td>
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
          <a class="edit-toggle edit-toggle-success" href="practica_nueva.php"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>Añadir práctica</a>
        </div>
      </header>

      <nav class="tab-nav">
        <a class="tab-nav-link active" href="practicas.php">Prácticas</a>
        <a class="tab-nav-link" href="practicas_dias.php">Días de prácticas</a>
        <a class="tab-nav-link" href="practicas_documentacion.php">Documentación</a>
        <a class="tab-nav-link" href="practicas_anexos.php">Seguimiento de Anexos</a>
        <a class="tab-nav-link" href="practicas_listado.php">Listado</a>
        <a class="tab-nav-link" href="practicas_contacto.php">Correos</a>
      </nav>

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
                <th><a class="practice-link" href="<?php echo htmlspecialchars(build_order_url('alumno', $sort_col, $sort_dir), ENT_QUOTES, 'UTF-8'); ?>">Alumno<?php echo sort_ind('alumno', $sort_col, $sort_dir); ?></a></th>
                <th><a class="practice-link" href="<?php echo htmlspecialchars(build_order_url('empresa', $sort_col, $sort_dir), ENT_QUOTES, 'UTF-8'); ?>">Empresa<?php echo sort_ind('empresa', $sort_col, $sort_dir); ?></a></th>
                <th><a class="practice-link" href="<?php echo htmlspecialchars(build_order_url('fecha_inicio', $sort_col, $sort_dir), ENT_QUOTES, 'UTF-8'); ?>">Fecha de inicio<?php echo sort_ind('fecha_inicio', $sort_col, $sort_dir); ?></a></th>
                <th><a class="practice-link" href="<?php echo htmlspecialchars(build_order_url('fecha_fin', $sort_col, $sort_dir), ENT_QUOTES, 'UTF-8'); ?>">Fecha de fin<?php echo sort_ind('fecha_fin', $sort_col, $sort_dir); ?></a></th>
                <th>Fecha fin real</th>
                <th><a class="practice-link" href="<?php echo htmlspecialchars(build_order_url('anexo', $sort_col, $sort_dir), ENT_QUOTES, 'UTF-8'); ?>">Anexo 2.1<?php echo sort_ind('anexo', $sort_col, $sort_dir); ?></a></th>
                <th><a class="practice-link" href="<?php echo htmlspecialchars(build_order_url('estado', $sort_col, $sort_dir), ENT_QUOTES, 'UTF-8'); ?>">Estado<?php echo sort_ind('estado', $sort_col, $sort_dir); ?></a></th>
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
    <div class="practicas-ras-popover practicas-ras-popover--modulo practicas-ras-popover--empresa" id="empresa-detail-popover" role="dialog" aria-modal="false" aria-labelledby="empresa-detail-title" hidden>
      <div class="practicas-ras-popover__header">
        <span class="practicas-ras-popover__eyebrow">Empresa</span>
        <span id="empresa-detail-title" class="practicas-ras-popover__title"></span>
        <button type="button" class="practicas-ras-popover__close" data-popover-close aria-label="Cerrar detalle de la empresa">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14" aria-hidden="true">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>
      <ul class="practicas-ras-popover__criteria" id="empresa-detail-data"></ul>
      <div class="practicas-ras-popover__footer">
        <a id="empresa-detail-link" class="practicas-ras-popover__link" href="#">Ver empresa completa →</a>
      </div>
    </div>
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
    const form = document.querySelector('.topbar');
    const searchInput = document.querySelector('input[name="q"]');
    const tableBody = document.querySelector('tbody');
    const layer = document.getElementById('empresa-detail-layer');
    const popover = document.getElementById('empresa-detail-popover');
    const title = document.getElementById('empresa-detail-title');
    const detailList = document.getElementById('empresa-detail-data');
    const detailLink = document.getElementById('empresa-detail-link');
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

      const getValueOrFallback = (value) => {
        const normalized = (value || '').trim();
        return normalized !== '' ? normalized : 'No disponible';
      };

      const createCopyNode = (value) => {
        if (value === 'No disponible') {
          return document.createTextNode(value);
        }

        const trigger = document.createElement('span');
        trigger.className = 'copy-trigger';
        trigger.dataset.copy = value;
        trigger.textContent = value;
        return trigger;
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

      const addPersonItem = (label, person, email, phone) => {
        const item = document.createElement('li');
        const strong = document.createElement('strong');
        strong.textContent = label;
        item.appendChild(strong);

        const valueSpan = document.createElement('span');
        valueSpan.appendChild(document.createTextNode(person));

        const emailLine = document.createElement('div');
        emailLine.appendChild(createCopyNode(email));
        valueSpan.appendChild(emailLine);

        const phoneLine = document.createElement('div');
        phoneLine.appendChild(createCopyNode(phone));
        valueSpan.appendChild(phoneLine);

        item.appendChild(valueSpan);
        detailList.appendChild(item);
      };

      const openPopover = (trigger) => {
        if (activeTrigger && activeTrigger !== trigger) {
          activeTrigger.setAttribute('aria-expanded', 'false');
        }

        title.textContent = trigger.dataset.empresaNombre || 'Empresa';
        if (detailLink) {
          const empresaId = (trigger.dataset.empresaId || '').trim();
          detailLink.setAttribute('href', empresaId !== '' ? `empresa_detalle.php?id_empresa=${encodeURIComponent(empresaId)}` : '#');
        }
        detailList.innerHTML = '';

        addInfoItem('CIF', getValueOrFallback(trigger.dataset.empresaCif));
        addInfoItem('Localidad', getValueOrFallback(trigger.dataset.empresaLocalidad));
        addPersonItem(
          'Persona de contacto',
          getValueOrFallback(trigger.dataset.contactoNombre),
          getValueOrFallback(trigger.dataset.contactoEmail),
          getValueOrFallback(trigger.dataset.contactoTelefono)
        );
        addPersonItem(
          'Tutor',
          getValueOrFallback(trigger.dataset.tutorNombre),
          getValueOrFallback(trigger.dataset.tutorEmail),
          getValueOrFallback(trigger.dataset.tutorTelefono)
        );

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

      popover.addEventListener('click', (event) => {
        const copyButton = event.target.closest('[data-copy-value]');
        if (!copyButton) {
          return;
        }

        event.preventDefault();

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
            if (window.showCopyToast) { window.showCopyToast(copyValue); }
          })
          .catch(() => {});
      });

      tableBody.addEventListener('keydown', (event) => {
        const trigger = event.target.closest('.empresa-name-trigger');
        if (trigger && tableBody.contains(trigger) && (event.key === 'Enter' || event.key === ' ')) {
          event.preventDefault();
          trigger.click();
        }
      });

      popover.addEventListener('keydown', (event) => {
        const copyTarget = event.target.closest('[data-copy-value]');
        if (copyTarget && (event.key === 'Enter' || event.key === ' ')) {
          event.preventDefault();
          copyTarget.click();
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
