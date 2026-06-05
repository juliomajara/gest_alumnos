<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$page_title = 'Listado de prácticas | Gestor de Alumnos';
$active_page = 'practicas';

function format_date_listado(?string $value): string
{
  if ($value === null || $value === '') {
    return '—';
  }
  $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);
  return $date ? $date->format('d/m/Y') : $value;
}

function build_order_url_listado(string $col, string $cur_col, string $cur_dir): string
{
  $params = $_GET;
  $params['orden'] = $col . '_' . (($col === $cur_col && $cur_dir === 'asc') ? 'desc' : 'asc');
  $query = http_build_query($params);
  return 'practicas_listado.php' . ($query !== '' ? '?' . $query : '');
}
function sort_ind_listado(string $col, string $cur_col, string $cur_dir): string
{
  if ($col !== $cur_col) return '';
  return $cur_dir === 'asc' ? ' ▲' : ' ▼';
}

$load_error = null;
$active_course_id = null;
$practices = [];

$allowed_orders = ['alumno_asc', 'alumno_desc', 'localidad_alumno_asc', 'localidad_alumno_desc', 'localidad_empresa_asc', 'localidad_empresa_desc', 'fecha_inicio_asc', 'fecha_inicio_desc', 'fecha_fin_asc', 'fecha_fin_desc'];
$order_param = (string) ($_GET['orden'] ?? '');
$current_order = in_array($order_param, $allowed_orders, true) ? $order_param : 'alumno_asc';
$_last_us = strrpos($current_order, '_');
$sort_col = $_last_us !== false ? substr($current_order, 0, $_last_us) : 'alumno';
$sort_dir = $_last_us !== false ? substr($current_order, $_last_us + 1) : 'asc';

try {
  $pdo = db();
  $active_course_id = $pdo->query('SELECT id_curso_escolar FROM cursos_escolares WHERE activo = 1 LIMIT 1')->fetchColumn();

  if ($active_course_id !== false && $active_course_id !== null) {
    $dir = $sort_dir === 'desc' ? 'DESC' : 'ASC';
    $order_clause = match ($sort_col) {
      'localidad_alumno'  => "ORDER BY la.nombre $dir, a.apellido1 ASC, a.apellido2 ASC, a.nombre ASC",
      'localidad_empresa' => "ORDER BY le.nombre $dir, a.apellido1 ASC, a.apellido2 ASC, a.nombre ASC",
      'fecha_inicio'      => "ORDER BY p.fecha_inicio $dir, a.apellido1 ASC, a.nombre ASC",
      'fecha_fin'         => "ORDER BY p.fecha_fin_extra $dir, a.apellido1 ASC, a.nombre ASC",
      default             => "ORDER BY a.apellido1 $dir, a.apellido2 $dir, a.nombre $dir, p.id_practica ASC",
    };

    $stmt = $pdo->prepare(
      'SELECT DISTINCT
         p.id_practica,
         a.nombre      AS alumno_nombre,
         a.apellido1   AS alumno_apellido1,
         a.apellido2   AS alumno_apellido2,
         la.nombre     AS localidad_alumno,
         le.nombre     AS localidad_empresa,
         p.fecha_inicio,
         p.fecha_fin_extra
       FROM practicas p
       INNER JOIN alumnos a       ON a.id_alumno   = p.id_alumno
       INNER JOIN alumno_curso ac ON ac.id_alumno  = p.id_alumno
                                  AND ac.id_curso_escolar = :active_course_id
       LEFT JOIN localidades la   ON la.id_localidad = a.id_localidad
       LEFT JOIN direcciones d    ON d.id_direccion  = p.id_direccion
       LEFT JOIN localidades le   ON le.id_localidad = d.id_localidad
       ' . $order_clause
    );
    $stmt->execute(['active_course_id' => (int) $active_course_id]);
    $practices = $stmt->fetchAll();
  }
} catch (Throwable $error) {
  $load_error = 'No se ha podido cargar el listado de prácticas.';
}
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
          <h1>Listado de prácticas</h1>
          <p class="subheading">Localidades y fechas de las prácticas del curso actual.</p>
        </div>
      </header>

      <nav class="tab-nav">
        <a class="tab-nav-link" href="practicas.php">Prácticas</a>
        <a class="tab-nav-link" href="practicas_dias.php">Días de prácticas</a>
        <a class="tab-nav-link" href="practicas_documentacion.php">Documentación</a>
        <a class="tab-nav-link" href="practicas_anexos.php">Seguimiento de Anexos</a>
        <a class="tab-nav-link active" href="practicas_listado.php">Listado</a>
      </nav>

      <section class="panel">
        <div class="panel-grid">
          <?php if ($load_error !== null): ?>
            <p><?php echo htmlspecialchars($load_error, ENT_QUOTES, 'UTF-8'); ?></p>
          <?php elseif ($active_course_id === false || $active_course_id === null): ?>
            <p>No hay un curso activo configurado.</p>
          <?php else: ?>
            <table>
              <thead>
                <tr>
                  <th><a class="practice-link" href="<?php echo htmlspecialchars(build_order_url_listado('alumno', $sort_col, $sort_dir), ENT_QUOTES, 'UTF-8'); ?>">Alumno<?php echo sort_ind_listado('alumno', $sort_col, $sort_dir); ?></a></th>
                  <th><a class="practice-link" href="<?php echo htmlspecialchars(build_order_url_listado('localidad_alumno', $sort_col, $sort_dir), ENT_QUOTES, 'UTF-8'); ?>">Localidad alumno<?php echo sort_ind_listado('localidad_alumno', $sort_col, $sort_dir); ?></a></th>
                  <th><a class="practice-link" href="<?php echo htmlspecialchars(build_order_url_listado('localidad_empresa', $sort_col, $sort_dir), ENT_QUOTES, 'UTF-8'); ?>">Localidad centro de trabajo<?php echo sort_ind_listado('localidad_empresa', $sort_col, $sort_dir); ?></a></th>
                  <th><a class="practice-link" href="<?php echo htmlspecialchars(build_order_url_listado('fecha_inicio', $sort_col, $sort_dir), ENT_QUOTES, 'UTF-8'); ?>">Fecha inicio<?php echo sort_ind_listado('fecha_inicio', $sort_col, $sort_dir); ?></a></th>
                  <th><a class="practice-link" href="<?php echo htmlspecialchars(build_order_url_listado('fecha_fin', $sort_col, $sort_dir), ENT_QUOTES, 'UTF-8'); ?>">Fecha fin real<?php echo sort_ind_listado('fecha_fin', $sort_col, $sort_dir); ?></a></th>
                </tr>
              </thead>
              <tbody>
                <?php if ($practices === []): ?>
                  <tr>
                    <td colspan="5">No hay prácticas registradas para el curso actual.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($practices as $practice): ?>
                    <?php
                      $apellidos = trim(implode(' ', array_filter([
                        (string) ($practice['alumno_apellido1'] ?? ''),
                        (string) ($practice['alumno_apellido2'] ?? ''),
                      ], static fn ($v) => trim((string) $v) !== '')));
                      $nombre = trim((string) ($practice['alumno_nombre'] ?? ''));
                      $nombre_completo = $apellidos !== '' && $nombre !== ''
                        ? $apellidos . ', ' . $nombre
                        : ($apellidos !== '' ? $apellidos : ($nombre !== '' ? $nombre : 'No disponible'));
                    ?>
                    <tr>
                      <td>
                        <a class="practice-link" href="practica_detalle.php?id_practica=<?php echo urlencode((string) (int) $practice['id_practica']); ?>">
                          <?php echo htmlspecialchars($nombre_completo, ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                      </td>
                      <td><?php echo htmlspecialchars((string) ($practice['localidad_alumno'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars((string) ($practice['localidad_empresa'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars(format_date_listado($practice['fecha_inicio'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars(format_date_listado($practice['fecha_fin_extra'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      </section>
    </main>
  </div>
</body>
</html>
