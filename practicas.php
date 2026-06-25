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
        <td><a class="empresa-name-trigger--practicas" href="practica_detalle.php?id_practica=<?php echo (int) $practice['id_practica']; ?>"><?php echo htmlspecialchars($anexo_21, ENT_QUOTES, 'UTF-8'); ?></a></td>
        <td>
          <span class="help-tooltip">
            <span class="alumno-name-trigger" tabindex="0"><?php echo $alumno_html; ?></span>
            <span class="help-tooltip-content" role="tooltip">
              <span class="help-tooltip-title"><?php echo htmlspecialchars($alumno, ENT_QUOTES, 'UTF-8'); ?></span>
              <table class="help-tooltip-table">
                <tbody>
                  <tr><td>NIA</td><td><?php echo $alumno_nia !== '' ? htmlspecialchars($alumno_nia, ENT_QUOTES, 'UTF-8') : 'No disponible'; ?></td></tr>
                  <tr><td>DNI</td><td><?php echo $alumno_dni !== '' ? htmlspecialchars($alumno_dni, ENT_QUOTES, 'UTF-8') : 'No disponible'; ?></td></tr>
                  <tr><td>EducaMadrid</td><td><?php echo $alumno_correo_educamadrid !== '' ? htmlspecialchars($alumno_correo_educamadrid, ENT_QUOTES, 'UTF-8') : 'No disponible'; ?></td></tr>
                  <tr><td>Correo personal</td><td><?php echo $alumno_correo_personal !== '' ? htmlspecialchars($alumno_correo_personal, ENT_QUOTES, 'UTF-8') : 'No disponible'; ?></td></tr>
                  <tr><td>Teléfono</td><td><?php echo $alumno_telefono !== '' ? htmlspecialchars($alumno_telefono, ENT_QUOTES, 'UTF-8') : 'No disponible'; ?></td></tr>
                </tbody>
              </table>
            </span>
          </span>
        </td>
        <td>
          <span class="help-tooltip">
            <span class="empresa-name-trigger--practicas" tabindex="0"><?php echo htmlspecialchars($empresa, ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="help-tooltip-content" role="tooltip">
              <span class="help-tooltip-title"><?php echo htmlspecialchars($empresa, ENT_QUOTES, 'UTF-8'); ?></span>
              <table class="help-tooltip-table">
                <tbody>
                  <tr><td>CIF</td><td><?php echo htmlspecialchars($empresa_cif, ENT_QUOTES, 'UTF-8'); ?></td></tr>
                  <?php if ($empresa_localidad !== ''): ?><tr><td>Localidad</td><td><?php echo htmlspecialchars($empresa_localidad, ENT_QUOTES, 'UTF-8'); ?></td></tr><?php endif; ?>
                  <tr><td>Contacto</td><td><?php echo htmlspecialchars($empresa_contacto_nombre, ENT_QUOTES, 'UTF-8'); ?></td></tr>
                  <?php if ($empresa_contacto_email !== 'No disponible'): ?><tr><td>C. correo</td><td><?php echo htmlspecialchars($empresa_contacto_email, ENT_QUOTES, 'UTF-8'); ?></td></tr><?php endif; ?>
                  <?php if ($empresa_contacto_telefono !== 'No disponible'): ?><tr><td>C. teléfono</td><td><?php echo htmlspecialchars($empresa_contacto_telefono, ENT_QUOTES, 'UTF-8'); ?></td></tr><?php endif; ?>
                  <tr><td>Tutor</td><td><?php echo htmlspecialchars($empresa_tutor_nombre, ENT_QUOTES, 'UTF-8'); ?></td></tr>
                  <?php if ($empresa_tutor_email !== 'No disponible'): ?><tr><td>T. correo</td><td><?php echo htmlspecialchars($empresa_tutor_email, ENT_QUOTES, 'UTF-8'); ?></td></tr><?php endif; ?>
                  <?php if ($empresa_tutor_telefono !== 'No disponible'): ?><tr><td>T. teléfono</td><td><?php echo htmlspecialchars($empresa_tutor_telefono, ENT_QUOTES, 'UTF-8'); ?></td></tr><?php endif; ?>
                </tbody>
              </table>
            </span>
          </span>
        </td>
        <td><?php echo htmlspecialchars($fecha_inicio, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($fecha_fin, ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($fecha_fin_real, ENT_QUOTES, 'UTF-8'); ?></td>
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
          <a class="primary-button" href="includes/practicas_exportar.php?<?php echo htmlspecialchars(http_build_query([
            'q'      => $search_term,
            'orden'  => $current_order,
            'formato' => 'excel',
          ]), ENT_QUOTES, 'UTF-8'); ?>">Exportar Excel</a>
          <a class="primary-button" href="includes/practicas_exportar.php?<?php echo htmlspecialchars(http_build_query([
            'q'      => $search_term,
            'orden'  => $current_order,
            'formato' => 'pdf',
          ]), ENT_QUOTES, 'UTF-8'); ?>">Exportar PDF</a>
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
                <th><a class="practice-link" href="<?php echo htmlspecialchars(build_order_url('anexo', $sort_col, $sort_dir), ENT_QUOTES, 'UTF-8'); ?>">Convenio/Anexo<?php echo sort_ind('anexo', $sort_col, $sort_dir); ?></a></th>
                <th><a class="practice-link" href="<?php echo htmlspecialchars(build_order_url('alumno', $sort_col, $sort_dir), ENT_QUOTES, 'UTF-8'); ?>">Alumno<?php echo sort_ind('alumno', $sort_col, $sort_dir); ?></a></th>
                <th><a class="practice-link" href="<?php echo htmlspecialchars(build_order_url('empresa', $sort_col, $sort_dir), ENT_QUOTES, 'UTF-8'); ?>">Empresa<?php echo sort_ind('empresa', $sort_col, $sort_dir); ?></a></th>
                <th><a class="practice-link" href="<?php echo htmlspecialchars(build_order_url('fecha_inicio', $sort_col, $sort_dir), ENT_QUOTES, 'UTF-8'); ?>">Fecha de inicio<?php echo sort_ind('fecha_inicio', $sort_col, $sort_dir); ?></a></th>
                <th><a class="practice-link" href="<?php echo htmlspecialchars(build_order_url('fecha_fin', $sort_col, $sort_dir), ENT_QUOTES, 'UTF-8'); ?>">Fecha de fin<?php echo sort_ind('fecha_fin', $sort_col, $sort_dir); ?></a></th>
                <th>Fecha fin real</th>
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

  <script>
  (function () {
    const form = document.querySelector('.topbar');
    const searchInput = document.querySelector('input[name="q"]');
    const tableBody = document.querySelector('tbody');
    let debounceTimer = null;

    if (!form || !searchInput || !tableBody) return;

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

    const gap = 10;
    let activeContainer = null;

    const positionTooltip = (container) => {
      const tooltip = container.querySelector('.help-tooltip-content');
      if (!tooltip) return;

      tooltip.style.position = 'fixed';
      tooltip.style.right = 'auto';
      tooltip.style.bottom = 'auto';

      const trigger = container.firstElementChild;
      const anchorRect = (trigger || container).getBoundingClientRect();
      const tooltipRect = tooltip.getBoundingClientRect();
      const vw = window.innerWidth || document.documentElement.clientWidth;
      const vh = window.innerHeight || document.documentElement.clientHeight;

      let left = anchorRect.left;
      let top = anchorRect.bottom + gap;

      if (left + tooltipRect.width > vw - gap) {
        left = anchorRect.right - tooltipRect.width;
      }
      if (top + tooltipRect.height > vh - gap) {
        top = anchorRect.top - tooltipRect.height - gap;
      }

      tooltip.style.left = `${Math.max(gap, Math.min(left, vw - tooltipRect.width - gap))}px`;
      tooltip.style.top = `${Math.max(gap, Math.min(top, vh - tooltipRect.height - gap))}px`;
    };

    tableBody.addEventListener('mouseover', (event) => {
      const container = event.target.closest('.help-tooltip');
      if (!container || !tableBody.contains(container)) return;
      if (activeContainer === container) return;
      activeContainer = container;
      positionTooltip(container);
    });

    tableBody.addEventListener('mouseout', (event) => {
      const container = event.target.closest('.help-tooltip');
      if (!container) return;
      if (container.contains(event.relatedTarget)) return;
      if (activeContainer === container) activeContainer = null;
    });

    window.addEventListener('resize', () => {
      if (activeContainer) positionTooltip(activeContainer);
    });

    window.addEventListener('scroll', () => {
      if (activeContainer) positionTooltip(activeContainer);
    }, { passive: true });
  })();
  </script>
  <script src="assets/copy.js"></script>
</body>
</html>
