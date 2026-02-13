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
$has_practicas_estados = table_exists($pdo, 'practicas_estados');
$has_empresas = table_exists($pdo, 'empresas');
$has_modulos = table_exists($pdo, 'modulos');
$has_alumno_modulo = table_exists($pdo, 'alumno_modulo');
$has_profesores = table_exists($pdo, 'profesores');
$has_grupos_tutores = table_exists($pdo, 'grupos_tutores');

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

$practicas_activas = ($has_practicas && $has_practicas_estados)
  ? fetch_scalar(
    $pdo,
    'SELECT COUNT(*)
     FROM practicas p
     LEFT JOIN practicas_estados pe ON pe.id_practicas_estado = p.id_practicas_estado
     WHERE p.fecha_inicio <= CURDATE()
       AND (p.fecha_fin IS NULL OR p.fecha_fin >= CURDATE())
       AND (pe.estado IS NULL OR LOWER(pe.estado) NOT IN ("finalizada", "cancelada", "cerrada"))'
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

$practicas_por_estado = ($has_practicas && $has_practicas_estados)
  ? fetch_rows(
    $pdo,
    'SELECT COALESCE(pe.estado, "Sin estado") AS estado, COUNT(*) AS total
     FROM practicas p
     LEFT JOIN practicas_estados pe ON pe.id_practicas_estado = p.id_practicas_estado
     GROUP BY pe.estado
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
      COALESCE(pe.estado, "Sin estado") AS estado,
      a.nombre AS alumno_nombre,
      a.apellido1 AS alumno_apellido1,
      a.apellido2 AS alumno_apellido2,
      e.nombre AS empresa_nombre,
      e.apellido1 AS empresa_apellido1,
      e.apellido2 AS empresa_apellido2
     FROM practicas p
     INNER JOIN alumnos a ON a.id_alumno = p.id_alumno
     INNER JOIN empresas e ON e.id_empresa = p.id_empresa
     LEFT JOIN practicas_estados pe ON pe.id_practicas_estado = p.id_practicas_estado
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
      <header class="header">
        <div>
          <p class="eyebrow">Panel de control</p>
          <h1>Dashboard</h1>
          <p class="subheading">Métricas reales del sistema para alumnos, prácticas, empresas, módulos y profesorado.</p>
        </div>
      </header>

      <section class="cards">
        <article class="card highlight">
          <div>
            <p class="card-label">Alumnos registrados</p>
            <h2><?php echo $total_alumnos !== null ? number_format($total_alumnos, 0, ',', '.') : '—'; ?></h2>
            <p class="card-note"><?php echo $total_alumnos !== null ? 'Total general de alumnos.' : 'Sin datos disponibles'; ?></p>
          </div>
          <span class="card-badge">Alumnos</span>
        </article>

        <article class="card">
          <div>
            <p class="card-label">Alumnos en curso activo</p>
            <h2><?php echo $alumnos_curso_activo !== null ? number_format($alumnos_curso_activo, 0, ',', '.') : '—'; ?></h2>
            <p class="card-note"><?php echo $alumnos_curso_activo !== null ? 'Vinculados a cursos escolares activos.' : 'Sin datos disponibles'; ?></p>
          </div>
          <span class="card-badge">Curso</span>
        </article>

        <article class="card">
          <div>
            <p class="card-label">Prácticas totales</p>
            <h2><?php echo $total_practicas !== null ? number_format($total_practicas, 0, ',', '.') : '—'; ?></h2>
            <p class="card-note"><?php echo $total_practicas !== null ? 'Todas las prácticas registradas.' : 'Sin datos disponibles'; ?></p>
          </div>
          <span class="card-badge">Prácticas</span>
        </article>

        <article class="card">
          <div>
            <p class="card-label">Prácticas activas</p>
            <h2><?php echo $practicas_activas !== null ? number_format($practicas_activas, 0, ',', '.') : '—'; ?></h2>
            <p class="card-note"><?php echo $practicas_activas !== null ? 'En fecha vigente y no finalizadas.' : 'Sin datos disponibles'; ?></p>
          </div>
          <span class="card-badge">Activas</span>
        </article>

        <article class="card">
          <div>
            <p class="card-label">Empresas registradas</p>
            <h2><?php echo $total_empresas !== null ? number_format($total_empresas, 0, ',', '.') : '—'; ?></h2>
            <p class="card-note"><?php echo $total_empresas !== null ? 'Empresas disponibles para prácticas.' : 'Sin datos disponibles'; ?></p>
          </div>
          <span class="card-badge">Empresas</span>
        </article>

        <article class="card">
          <div>
            <p class="card-label">Profesores</p>
            <h2><?php echo $total_profesores !== null ? number_format($total_profesores, 0, ',', '.') : '—'; ?></h2>
            <p class="card-note"><?php echo $total_profesores !== null ? 'Docentes dados de alta en el sistema.' : 'Sin datos disponibles'; ?></p>
          </div>
          <span class="card-badge">Profesorado</span>
        </article>
      </section>

      <section class="grid">
        <article class="panel">
          <div class="panel-header">
            <h3>Resumen de prácticas por estado</h3>
            <p>Distribución actual según estado de la práctica.</p>
          </div>
          <ul class="activity">
            <?php if (!$practicas_por_estado): ?>
              <li>
                <div>
                  <strong>Sin datos disponibles</strong>
                  <p>No se han encontrado estados de prácticas.</p>
                </div>
              </li>
            <?php else: ?>
              <?php foreach ($practicas_por_estado as $fila_estado): ?>
                <li>
                  <div>
                    <strong><?php echo htmlspecialchars((string) $fila_estado['estado'], ENT_QUOTES, 'UTF-8'); ?></strong>
                    <p><?php echo (int) $fila_estado['total']; ?> práctica(s)</p>
                  </div>
                  <span class="status"><?php echo (int) $fila_estado['total']; ?></span>
                </li>
              <?php endforeach; ?>
            <?php endif; ?>
          </ul>
        </article>

        <article class="panel">
          <div class="panel-header">
            <h3>Últimas prácticas registradas</h3>
            <p>Listado reciente de prácticas creadas en el sistema.</p>
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
                    ?>
                    <tr>
                      <td><?php echo htmlspecialchars($nombre_alumno !== '' ? $nombre_alumno : 'No disponible', ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars($nombre_empresa !== '' ? $nombre_empresa : 'No disponible', ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars((string) $practica['estado'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars((string) ($practica['fecha_inicio'] ?: 'No disponible'), ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </article>

        <article class="panel">
          <div class="panel-header">
            <h3>Resumen académico</h3>
            <p>Módulos, asignaciones y tutores por grupo del sistema.</p>
          </div>
          <div class="panel-grid">
            <a class="panel-link" href="modulos.php">
              <span>Módulos registrados: <?php echo $total_modulos !== null ? number_format($total_modulos, 0, ',', '.') : 'Sin datos disponibles'; ?></span>
              <small>Total de módulos configurados.</small>
            </a>
            <a class="panel-link" href="modulos.php">
              <span>Asignaciones alumno-módulo: <?php echo $total_asignaciones_modulo !== null ? number_format($total_asignaciones_modulo, 0, ',', '.') : 'Sin datos disponibles'; ?></span>
              <small>Relaciones activas entre alumnado y módulos.</small>
            </a>
            <a class="panel-link" href="profesores.php">
              <span>Tutores asignados a grupos: <?php echo $total_tutores_grupo !== null ? number_format($total_tutores_grupo, 0, ',', '.') : 'Sin datos disponibles'; ?></span>
              <small>Profesores con tutoría en grupos.</small>
            </a>
            <a class="panel-link" href="alumnos.php">
              <span>Cursos activos con alumnado: <?php echo $alumnos_por_curso_activo ? count($alumnos_por_curso_activo) : 0; ?></span>
              <small>
                <?php if (!$alumnos_por_curso_activo): ?>
                  Sin datos disponibles.
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
            </a>
          </div>
        </article>
      </section>
    </main>
  </div>
</body>
</html>
