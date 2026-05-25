<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pdo = null;
try {
  $pdo = db();
} catch (Throwable $exception) {
  $pdo = null;
}

function table_exists(?PDO $pdo, string $table): bool
{
  if (!$pdo instanceof PDO) {
    return false;
  }

  try {
    $stmt = $pdo->prepare(
      'SELECT 1
       FROM information_schema.TABLES
       WHERE TABLE_SCHEMA = DATABASE()
         AND TABLE_NAME = :table_name
       LIMIT 1'
    );
    $stmt->execute(['table_name' => $table]);
    return (bool) $stmt->fetchColumn();
  } catch (Throwable $exception) {
    return false;
  }
}

function fetch_scalar(?PDO $pdo, string $sql, array $params = []): ?int
{
  if (!$pdo instanceof PDO) {
    return null;
  }

  try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $value = $stmt->fetchColumn();
    return $value !== false ? (int) $value : 0;
  } catch (Throwable $exception) {
    return null;
  }
}

function fetch_rows(?PDO $pdo, string $sql, array $params = []): ?array
{
  if (!$pdo instanceof PDO) {
    return null;
  }

  try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
  } catch (Throwable $exception) {
    return null;
  }
}

$has_alumnos = table_exists($pdo, 'alumnos');
$has_alumno_curso = table_exists($pdo, 'alumno_curso');
$has_cursos_escolares = table_exists($pdo, 'cursos_escolares');
$has_practicas = table_exists($pdo, 'practicas');
$has_empresas = table_exists($pdo, 'empresas');
$has_modulos = table_exists($pdo, 'modulos');
$has_alumno_modulo = table_exists($pdo, 'alumno_modulo');
$has_profesores = table_exists($pdo, 'profesores');
$has_grupos_tutores = table_exists($pdo, 'grupos_tutores');
$has_calificaciones = table_exists($pdo, 'calificaciones');
$has_asistencia_mensual = table_exists($pdo, 'asistencia_mensual');
$has_practicas_anexos = table_exists($pdo, 'practicas_anexos');

$total_alumnos = $has_alumnos
  ? fetch_scalar($pdo, 'SELECT COUNT(*) FROM alumnos')
  : null;

$alumnos_curso_activo = ($has_alumno_curso && $has_cursos_escolares)
  ? fetch_scalar(
    $pdo,
    'SELECT COUNT(*)
     FROM alumno_curso ac
     INNER JOIN cursos_escolares ce ON ce.id_curso_escolar = ac.id_curso_escolar
     WHERE ce.activo = 1'
  )
  : null;

$total_practicas = $has_practicas
  ? fetch_scalar($pdo, 'SELECT COUNT(*) FROM practicas')
  : null;

$practicas_activas = $has_practicas
  ? fetch_scalar(
    $pdo,
    'SELECT COUNT(*)
     FROM practicas p
     WHERE COALESCE(p.cancelada, 0) = 0
       AND p.fecha_inicio IS NOT NULL
       AND p.fecha_inicio <= CURDATE()
       AND COALESCE(p.fecha_fin_extra, p.fecha_fin) IS NOT NULL
       AND COALESCE(p.fecha_fin_extra, p.fecha_fin) >= CURDATE()'
  )
  : null;

$total_empresas = $has_empresas
  ? fetch_scalar($pdo, 'SELECT COUNT(*) FROM empresas')
  : null;

$total_modulos = $has_modulos
  ? fetch_scalar($pdo, 'SELECT COUNT(*) FROM modulos')
  : null;

$total_asignaciones_modulo = $has_alumno_modulo
  ? fetch_scalar($pdo, 'SELECT COUNT(*) FROM alumno_modulo')
  : null;

$total_profesores = $has_profesores
  ? fetch_scalar($pdo, 'SELECT COUNT(*) FROM profesores')
  : null;

$total_tutores_grupo = $has_grupos_tutores
  ? fetch_scalar($pdo, 'SELECT COUNT(DISTINCT id_profesor) FROM grupos_tutores')
  : null;

$practicas_por_estado = $has_practicas
  ? fetch_rows(
    $pdo,
    'SELECT
      CASE
        WHEN COALESCE(p.cancelada, 0) = 1 THEN "Cancelada"
        WHEN p.fecha_inicio IS NULL OR p.fecha_inicio = "" OR COALESCE(p.fecha_fin_extra, p.fecha_fin) IS NULL OR COALESCE(p.fecha_fin_extra, p.fecha_fin) = "" THEN "No disponible"
        WHEN CURDATE() < p.fecha_inicio THEN "En espera"
        WHEN CURDATE() <= COALESCE(p.fecha_fin_extra, p.fecha_fin) THEN "En curso"
        ELSE "Finalizada"
      END AS estado,
      COUNT(*) AS total
     FROM practicas p
     GROUP BY estado
     ORDER BY total DESC, estado ASC'
  )
  : null;

$ultimas_practicas = ($has_practicas && $has_alumnos && $has_empresas)
  ? fetch_rows(
    $pdo,
    'SELECT
      p.id_practica,
      p.fecha_inicio,
      p.fecha_fin,
      CASE
        WHEN COALESCE(p.cancelada, 0) = 1 THEN "Cancelada"
        WHEN p.fecha_inicio IS NULL OR p.fecha_inicio = "" OR COALESCE(p.fecha_fin_extra, p.fecha_fin) IS NULL OR COALESCE(p.fecha_fin_extra, p.fecha_fin) = "" THEN "No disponible"
        WHEN CURDATE() < p.fecha_inicio THEN "En espera"
        WHEN CURDATE() <= COALESCE(p.fecha_fin_extra, p.fecha_fin) THEN "En curso"
        ELSE "Finalizada"
      END AS estado,
      a.nombre AS alumno_nombre,
      a.apellido1 AS alumno_apellido1,
      a.apellido2 AS alumno_apellido2,
      e.nombre AS empresa_nombre,
      e.apellido1 AS empresa_apellido1,
      e.apellido2 AS empresa_apellido2
     FROM practicas p
     INNER JOIN alumnos a ON a.id_alumno = p.id_alumno
     INNER JOIN empresas e ON e.id_empresa = p.id_empresa
     ORDER BY p.id_practica DESC
     LIMIT 6'
  )
  : null;

$alumnos_por_curso_activo = ($has_alumno_curso && $has_cursos_escolares)
  ? fetch_rows(
    $pdo,
    'SELECT ce.curso_escolar, COUNT(*) AS total
     FROM alumno_curso ac
     INNER JOIN cursos_escolares ce ON ce.id_curso_escolar = ac.id_curso_escolar
     WHERE ce.activo = 1
     GROUP BY ce.id_curso_escolar, ce.curso_escolar
     ORDER BY ce.curso_escolar ASC'
  )
  : null;


$total_calificaciones = $has_calificaciones
  ? fetch_scalar($pdo, 'SELECT COUNT(*) FROM calificaciones')
  : null;

$asistencias_mes_actual = $has_asistencia_mensual
  ? fetch_scalar(
    $pdo,
    'SELECT COUNT(*)
     FROM asistencia_mensual
     WHERE anio = YEAR(CURDATE())
       AND mes = MONTH(CURDATE())'
  )
  : null;

$anexos_generados = $has_practicas_anexos
  ? fetch_scalar($pdo, 'SELECT COUNT(*) FROM practicas_anexos')
  : null;

$practicas_sin_fecha_inicio = $has_practicas
  ? fetch_scalar(
    $pdo,
    'SELECT COUNT(*)
     FROM practicas
     WHERE COALESCE(cancelada, 0) = 0
       AND (fecha_inicio IS NULL OR fecha_inicio = "")'
  )
  : null;

$curso_activo_info = $has_cursos_escolares
  ? fetch_rows(
    $pdo,
    'SELECT id_curso_escolar, curso_escolar
     FROM cursos_escolares
     WHERE activo = 1
     ORDER BY id_curso_escolar DESC
     LIMIT 1'
  )
  : null;

$curso_activo_nombre = (!empty($curso_activo_info) && isset($curso_activo_info[0]['curso_escolar']))
  ? (string) $curso_activo_info[0]['curso_escolar']
  : null;

$page_title = 'Dashboard | Gestor de Alumnos';
$active_page = 'dashboard';
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

      <!-- HERO BANNER -->
      <div class="db-hero">
        <div class="db-hero-left">
          <div class="db-hero-eyebrow">
            <?php echo $curso_activo_nombre ? htmlspecialchars($curso_activo_nombre, ENT_QUOTES, 'UTF-8') : 'Sin curso activo'; ?>
          </div>
          <h1 class="db-hero-title">Panel de control</h1>
          <p class="db-hero-sub">Métricas en tiempo real del sistema académico</p>
        </div>
        <div class="db-hero-right">
          <div class="db-hero-date">
            <span class="db-hero-date-day"><?php echo date('d'); ?></span>
            <div class="db-hero-date-info">
              <span class="db-hero-date-month"><?php
                $db_months = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
                echo $db_months[(int)date('n') - 1] . ' ' . date('Y');
              ?></span>
              <span class="db-hero-date-dow"><?php
                $db_days = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
                echo $db_days[(int)date('w')];
              ?></span>
            </div>
          </div>
        </div>
      </div>

      <!-- KPI CARDS -->
      <section class="db-kpis">
        <article class="db-kpi db-kpi--blue">
          <div class="db-kpi-icon"><img src="assets/icons/alumnos.svg" alt=""></div>
          <div class="db-kpi-body">
            <div class="db-kpi-value"><?php echo $total_alumnos !== null ? number_format($total_alumnos, 0, ',', '.') : '&#8212;'; ?></div>
            <div class="db-kpi-label">Alumnos registrados</div>
          </div>
        </article>
        <article class="db-kpi db-kpi--green">
          <div class="db-kpi-icon"><img src="assets/icons/alumnos.svg" alt=""></div>
          <div class="db-kpi-body">
            <div class="db-kpi-value"><?php echo $alumnos_curso_activo !== null ? number_format($alumnos_curso_activo, 0, ',', '.') : '&#8212;'; ?></div>
            <div class="db-kpi-label">En curso activo</div>
          </div>
        </article>
        <article class="db-kpi db-kpi--amber">
          <div class="db-kpi-icon"><img src="assets/icons/practicas.svg" alt=""></div>
          <div class="db-kpi-body">
            <div class="db-kpi-value"><?php echo $total_practicas !== null ? number_format($total_practicas, 0, ',', '.') : '&#8212;'; ?></div>
            <div class="db-kpi-label">Prácticas totales</div>
          </div>
        </article>
        <article class="db-kpi db-kpi--indigo">
          <div class="db-kpi-icon"><img src="assets/icons/practicas.svg" alt=""></div>
          <div class="db-kpi-body">
            <div class="db-kpi-value"><?php echo $practicas_activas !== null ? number_format($practicas_activas, 0, ',', '.') : '&#8212;'; ?></div>
            <div class="db-kpi-label">Prácticas activas</div>
          </div>
        </article>
        <article class="db-kpi db-kpi--purple">
          <div class="db-kpi-icon"><img src="assets/icons/empresas.svg" alt=""></div>
          <div class="db-kpi-body">
            <div class="db-kpi-value"><?php echo $total_empresas !== null ? number_format($total_empresas, 0, ',', '.') : '&#8212;'; ?></div>
            <div class="db-kpi-label">Empresas</div>
          </div>
        </article>
        <article class="db-kpi db-kpi--pink">
          <div class="db-kpi-icon"><img src="assets/icons/profesores.svg" alt=""></div>
          <div class="db-kpi-body">
            <div class="db-kpi-value"><?php echo $total_profesores !== null ? number_format($total_profesores, 0, ',', '.') : '&#8212;'; ?></div>
            <div class="db-kpi-label">Profesores</div>
          </div>
        </article>
        <article class="db-kpi db-kpi--teal">
          <div class="db-kpi-icon"><img src="assets/icons/calificaciones.svg" alt=""></div>
          <div class="db-kpi-body">
            <div class="db-kpi-value"><?php echo $total_calificaciones !== null ? number_format($total_calificaciones, 0, ',', '.') : '&#8212;'; ?></div>
            <div class="db-kpi-label">Calificaciones</div>
          </div>
        </article>
        <article class="db-kpi db-kpi--orange">
          <div class="db-kpi-icon"><img src="assets/icons/asistencia.svg" alt=""></div>
          <div class="db-kpi-body">
            <div class="db-kpi-value"><?php echo $asistencias_mes_actual !== null ? number_format($asistencias_mes_actual, 0, ',', '.') : '&#8212;'; ?></div>
            <div class="db-kpi-label">Asistencia <?php echo date('m/Y'); ?></div>
          </div>
        </article>
      </section>

      <!-- ACCESOS RÁPIDOS -->
      <section>
        <h2 class="db-section-title">Accesos rápidos</h2>
        <div class="db-quicklinks">
          <a href="alumnos.php" class="db-quicklink">
            <span class="db-ql-icon"><img src="assets/icons/alumnos.svg" alt=""></span>
            <span class="db-ql-label">Alumnos</span>
          </a>
          <a href="asistencia.php" class="db-quicklink">
            <span class="db-ql-icon"><img src="assets/icons/asistencia.svg" alt=""></span>
            <span class="db-ql-label">Asistencia</span>
          </a>
          <a href="calificaciones.php" class="db-quicklink">
            <span class="db-ql-icon"><img src="assets/icons/calificaciones.svg" alt=""></span>
            <span class="db-ql-label">Calificaciones</span>
          </a>
          <a href="modulos.php" class="db-quicklink">
            <span class="db-ql-icon"><img src="assets/icons/modulos.svg" alt=""></span>
            <span class="db-ql-label">Módulos</span>
          </a>
          <a href="horarios.php" class="db-quicklink">
            <span class="db-ql-icon"><img src="assets/icons/horarios.svg" alt=""></span>
            <span class="db-ql-label">Horarios</span>
          </a>
          <a href="empresas.php" class="db-quicklink">
            <span class="db-ql-icon"><img src="assets/icons/empresas.svg" alt=""></span>
            <span class="db-ql-label">Empresas</span>
          </a>
          <a href="practicas.php" class="db-quicklink">
            <span class="db-ql-icon"><img src="assets/icons/practicas.svg" alt=""></span>
            <span class="db-ql-label">Prácticas</span>
          </a>
          <a href="profesores.php" class="db-quicklink">
            <span class="db-ql-icon"><img src="assets/icons/profesores.svg" alt=""></span>
            <span class="db-ql-label">Profesores</span>
          </a>
          <a href="practicas_anexos.php" class="db-quicklink">
            <span class="db-ql-icon"><img src="assets/icons/documentacion.svg" alt=""></span>
            <span class="db-ql-label">Anexos</span>
          </a>
          <a href="configuracion.php" class="db-quicklink">
            <span class="db-ql-icon"><img src="assets/icons/configuracion.svg" alt=""></span>
            <span class="db-ql-label">Configuración</span>
          </a>
        </div>
      </section>

      <!-- PANELES -->
      <section class="grid">

        <!-- Estado prácticas -->
        <article class="panel">
          <div class="panel-header">
            <h3>Estado de prácticas</h3>
            <p>Distribución actual por estado de cada práctica.</p>
          </div>
          <?php if (!$practicas_por_estado): ?>
            <p class="db-empty">Sin datos disponibles.</p>
          <?php else: ?>
            <?php
              $db_total_estado = array_sum(array_column($practicas_por_estado, 'total'));
              $db_estado_badge_map = [
                'En curso'      => 'db-estado--green',
                'En espera'     => 'db-estado--amber',
                'Finalizada'    => 'db-estado--blue',
                'Cancelada'     => 'db-estado--red',
              ];
            ?>
            <ul class="db-estado-list">
              <?php foreach ($practicas_por_estado as $fila_estado): ?>
                <?php
                  $db_estado_val   = (string) $fila_estado['estado'];
                  $db_estado_count = (int) $fila_estado['total'];
                  $db_pct          = $db_total_estado > 0 ? round($db_estado_count / $db_total_estado * 100) : 0;
                  $db_estado_cls   = $db_estado_badge_map[$db_estado_val] ?? 'db-estado--gray';
                ?>
                <li class="db-estado-item">
                  <div class="db-estado-head">
                    <span class="db-estado-badge <?php echo $db_estado_cls; ?>"><?php echo htmlspecialchars($db_estado_val, ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="db-estado-count"><?php echo $db_estado_count; ?> <small>(<?php echo $db_pct; ?>%)</small></span>
                  </div>
                  <div class="db-progress-bar">
                    <div class="db-progress-fill <?php echo $db_estado_cls; ?>" style="width:<?php echo $db_pct; ?>%"></div>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>
            <a href="practicas.php" class="db-panel-link-footer">Ver todas las prácticas &rarr;</a>
          <?php endif; ?>
        </article>

        <!-- Últimas prácticas -->
        <article class="panel">
          <div class="panel-header">
            <h3>Últimas prácticas registradas</h3>
            <p>Las 6 prácticas más recientes del sistema.</p>
          </div>
          <div class="panel-grid">
            <table>
              <thead>
                <tr>
                  <th>Alumno</th>
                  <th>Empresa</th>
                  <th>Estado</th>
                  <th>Inicio</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!$ultimas_practicas): ?>
                  <tr>
                    <td colspan="4">Sin datos disponibles</td>
                  </tr>
                <?php else: ?>
                  <?php
                    $db_badge_map = [
                      'En curso'   => 'db-badge--green',
                      'En espera'  => 'db-badge--amber',
                      'Finalizada' => 'db-badge--blue',
                      'Cancelada'  => 'db-badge--red',
                    ];
                  ?>
                  <?php foreach ($ultimas_practicas as $practica): ?>
                    <?php
                      $nombre_alumno = trim(implode(' ', array_filter([
                        trim((string) ($practica['alumno_apellido1'] ?? '')),
                        trim((string) ($practica['alumno_apellido2'] ?? '')),
                        trim((string) ($practica['alumno_nombre'] ?? '')),
                      ], static fn ($valor) => $valor !== '')));

                      $nombre_empresa = trim(implode(' ', array_filter([
                        trim((string) ($practica['empresa_nombre'] ?? '')),
                        trim((string) ($practica['empresa_apellido1'] ?? '')),
                        trim((string) ($practica['empresa_apellido2'] ?? '')),
                      ], static fn ($valor) => $valor !== '')));

                      $db_estado_practica = (string) $practica['estado'];
                      $db_badge_cls = $db_badge_map[$db_estado_practica] ?? 'db-badge--gray';
                    ?>
                    <tr>
                      <td><?php echo htmlspecialchars($nombre_alumno !== '' ? $nombre_alumno : 'No disponible', ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars($nombre_empresa !== '' ? $nombre_empresa : 'No disponible', ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><span class="db-badge <?php echo $db_badge_cls; ?>"><?php echo htmlspecialchars($db_estado_practica, ENT_QUOTES, 'UTF-8'); ?></span></td>
                      <td><?php echo htmlspecialchars((string) ($practica['fecha_inicio'] ?: '&#8212;'), ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
          <a href="practicas.php" class="db-panel-link-footer">Ver todas las prácticas &rarr;</a>
        </article>

        <!-- Resumen académico -->
        <article class="panel">
          <div class="panel-header">
            <h3>Resumen académico</h3>
            <p>Módulos, asignaciones y tutores por grupo.</p>
          </div>
          <div class="panel-grid">
            <a class="panel-link panel-link-with-icon" href="modulos.php">
              <span class="nav-icon"><span class="nav-icon-image" data-icon="modulos"></span></span>
              <span class="panel-link-text">
                <span>Módulos registrados</span>
                <small><?php echo $total_modulos !== null ? number_format($total_modulos, 0, ',', '.') : 'Sin datos'; ?> configurados</small>
              </span>
            </a>
            <a class="panel-link panel-link-with-icon" href="modulos.php">
              <span class="nav-icon"><span class="nav-icon-image" data-icon="alumnos"></span></span>
              <span class="panel-link-text">
                <span>Asignaciones alumno-módulo</span>
                <small><?php echo $total_asignaciones_modulo !== null ? number_format($total_asignaciones_modulo, 0, ',', '.') : 'Sin datos'; ?> relaciones activas</small>
              </span>
            </a>
            <a class="panel-link panel-link-with-icon" href="profesores.php">
              <span class="nav-icon"><span class="nav-icon-image" data-icon="profesores"></span></span>
              <span class="panel-link-text">
                <span>Tutores asignados a grupos</span>
                <small><?php echo $total_tutores_grupo !== null ? number_format($total_tutores_grupo, 0, ',', '.') : 'Sin datos'; ?> con tutoría activa</small>
              </span>
            </a>
            <a class="panel-link panel-link-with-icon" href="alumnos.php">
              <span class="nav-icon"><span class="nav-icon-image" data-icon="calificaciones"></span></span>
              <span class="panel-link-text">
                <span>Cursos activos con alumnado</span>
                <small>
                  <?php if (!$alumnos_por_curso_activo): ?>
                    Sin datos disponibles
                  <?php else: ?>
                    <?php
                      $resumen_cursos = array_map(
                        static fn ($fila) => $fila['curso_escolar'] . ': ' . $fila['total'],
                        $alumnos_por_curso_activo
                      );
                      echo htmlspecialchars(implode(' · ', $resumen_cursos), ENT_QUOTES, 'UTF-8');
                    ?>
                  <?php endif; ?>
                </small>
              </span>
            </a>
          </div>
        </article>

        <!-- Seguimiento operativo -->
        <article class="panel">
          <div class="panel-header">
            <h3>Seguimiento operativo</h3>
            <p>Indicadores útiles para la gestión diaria.</p>
          </div>
          <div class="panel-grid">
            <a class="panel-link panel-link-with-icon" href="calificaciones.php">
              <span class="nav-icon"><span class="nav-icon-image" data-icon="calificaciones"></span></span>
              <span class="panel-link-text">
                <span>Calificaciones totales</span>
                <small><?php echo $total_calificaciones !== null ? number_format($total_calificaciones, 0, ',', '.') : 'Sin datos'; ?> evaluaciones registradas</small>
              </span>
            </a>
            <a class="panel-link panel-link-with-icon" href="asistencia.php">
              <span class="nav-icon"><span class="nav-icon-image" style="mask-image:url('assets/icons/asistencia.svg');-webkit-mask-image:url('assets/icons/asistencia.svg')"></span></span>
              <span class="panel-link-text">
                <span>Asistencia (mes actual)</span>
                <small><?php echo $asistencias_mes_actual !== null ? number_format($asistencias_mes_actual, 0, ',', '.') : 'Sin datos'; ?> partes en <?php echo date('m/Y'); ?></small>
              </span>
            </a>
            <a class="panel-link panel-link-with-icon" href="practicas_anexos.php">
              <span class="nav-icon"><span class="nav-icon-image" data-icon="practicas"></span></span>
              <span class="panel-link-text">
                <span>Anexos generados</span>
                <small><?php echo $anexos_generados !== null ? number_format($anexos_generados, 0, ',', '.') : 'Sin datos'; ?> documentos emitidos</small>
              </span>
            </a>
            <a class="panel-link panel-link-with-icon<?php echo ($practicas_sin_fecha_inicio !== null && $practicas_sin_fecha_inicio > 0) ? ' db-alert-link' : ''; ?>" href="practicas.php">
              <span class="nav-icon"><span class="nav-icon-image" data-icon="practicas"></span></span>
              <span class="panel-link-text">
                <span><?php echo ($practicas_sin_fecha_inicio !== null && $practicas_sin_fecha_inicio > 0) ? '&#9888;&#65039; Prácticas sin fecha de inicio' : 'Prácticas sin fecha de inicio'; ?></span>
                <small><?php echo $practicas_sin_fecha_inicio !== null ? number_format($practicas_sin_fecha_inicio, 0, ',', '.') : 'Sin datos'; ?> pendientes de planificación</small>
              </span>
            </a>
            <a class="panel-link panel-link-with-icon" href="alumnos.php">
              <span class="nav-icon"><span class="nav-icon-image" data-icon="alumnos"></span></span>
              <span class="panel-link-text">
                <span>Curso escolar activo</span>
                <small><?php echo htmlspecialchars($curso_activo_nombre ?? 'No definido', ENT_QUOTES, 'UTF-8'); ?></small>
              </span>
            </a>
          </div>
        </article>

      </section>
    </main>
  </div>
</body>
</html>
