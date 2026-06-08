<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pdo = db();

$id_modulo = isset($_GET['id_modulo']) ? (int) $_GET['id_modulo'] : 0;

function format_value($value, string $fallback = 'No disponible'): string {
  if ($value === null || $value === '') {
    return $fallback;
  }

  return (string) $value;
}

$module = null;
$learning_results = [];
$criteria_by_ra = [];

if ($id_modulo > 0) {
  $module_stmt = $pdo->prepare(
    'SELECT m.*, n.nivel, c.abreviatura AS ciclo_abreviatura, cu.curso
     FROM modulos m
     LEFT JOIN niveles n ON n.id_nivel = m.id_nivel
     LEFT JOIN ciclos c ON c.id_ciclo = m.id_ciclo
     LEFT JOIN cursos cu ON cu.id_curso = m.id_curso
     WHERE m.id_modulo = :id_modulo'
  );
  $module_stmt->execute(['id_modulo' => $id_modulo]);
  $module = $module_stmt->fetch();
}

if ($module) {
  $learning_results_stmt = $pdo->prepare(
    'SELECT id_ra, numero, descripcion
     FROM resultados_aprendizaje
     WHERE id_modulo = :id_modulo
     ORDER BY numero, id_ra'
  );
  $learning_results_stmt->execute(['id_modulo' => $id_modulo]);
  $learning_results = $learning_results_stmt->fetchAll();

  $criteria_stmt = $pdo->prepare(
    'SELECT ce.id_ce, ce.id_ra, ce.codigo, ce.descripcion
     FROM criterios_evaluacion ce
     INNER JOIN resultados_aprendizaje ra ON ra.id_ra = ce.id_ra
     WHERE ra.id_modulo = :id_modulo
     ORDER BY ra.numero, ce.codigo, ce.id_ce'
  );
  $criteria_stmt->execute(['id_modulo' => $id_modulo]);

  foreach ($criteria_stmt->fetchAll() as $criterion) {
    $ra_id = (int) $criterion['id_ra'];
    if (!isset($criteria_by_ra[$ra_id])) {
      $criteria_by_ra[$ra_id] = [];
    }

    $criteria_by_ra[$ra_id][] = $criterion;
  }
}

$module_name = 'Módulo no encontrado';
if ($module) {
  $materia_propia = trim((string) ($module['materia_propia'] ?? ''));
  $materia_general = trim((string) ($module['materia_general'] ?? ''));
  $module_name = $materia_propia !== '' ? $materia_propia : ($materia_general !== '' ? $materia_general : 'Módulo sin nombre');
}

$page_title = $module
  ? sprintf('%s | Gestor de Alumnos', $module_name)
  : 'Módulo no encontrado | Gestor de Alumnos';
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
          <p class="eyebrow">Detalle de módulo</p>
          <h1><?php echo htmlspecialchars($module_name, ENT_QUOTES, 'UTF-8'); ?></h1>
          <p class="subheading">Consulta la información general del módulo y sus resultados de aprendizaje con criterios de evaluación.</p>
        </div>
        <div class="header-actions">
          <a class="ghost-button" href="modulos.php">Volver a módulos</a>
        </div>
      </header>

      <?php if (!$module): ?>
        <section class="panel">
          <div class="panel-header">
            <h3>Módulo no encontrado</h3>
            <p>No hemos podido localizar el módulo solicitado. Comprueba el enlace y vuelve al listado de módulos.</p>
          </div>
          <div class="panel-grid">
            <a class="ghost-button" href="modulos.php">Volver a módulos</a>
          </div>
        </section>
      <?php else: ?>
        <div class="grid">
          <section class="panel">
            <div class="panel-header">
              <h3>Datos del módulo</h3>
              <p>Identificación y nombre del módulo.</p>
            </div>
            <div class="practica-detalle-campos">
              <div class="practica-detalle-campo">
                <span class="practica-detalle-campo-etiqueta">Código</span>
                <span class="practica-detalle-campo-valor"><?php echo htmlspecialchars(format_value($module['codigo']), ENT_QUOTES, 'UTF-8'); ?></span>
              </div>
              <div class="practica-detalle-campo">
                <span class="practica-detalle-campo-etiqueta">Abreviatura</span>
                <span class="practica-detalle-campo-valor"><?php echo htmlspecialchars(format_value($module['abreviatura']), ENT_QUOTES, 'UTF-8'); ?></span>
              </div>
              <div class="practica-detalle-campo">
                <span class="practica-detalle-campo-etiqueta">Tipo</span>
                <span class="practica-detalle-campo-valor"><?php echo htmlspecialchars(format_value($module['tipo']), ENT_QUOTES, 'UTF-8'); ?></span>
              </div>
              <div class="practica-detalle-campo">
                <span class="practica-detalle-campo-etiqueta">Materia general</span>
                <span class="practica-detalle-campo-valor"><?php echo htmlspecialchars(format_value($module['materia_general']), ENT_QUOTES, 'UTF-8'); ?></span>
              </div>
              <div class="practica-detalle-campo">
                <span class="practica-detalle-campo-etiqueta">Materia propia</span>
                <span class="practica-detalle-campo-valor"><?php echo htmlspecialchars(format_value($module['materia_propia']), ENT_QUOTES, 'UTF-8'); ?></span>
              </div>
            </div>
          </section>

          <section class="panel">
            <div class="panel-header">
              <h3>Clasificación y carga horaria</h3>
              <p>Nivel, ciclo, curso y horas asignadas al módulo.</p>
            </div>
            <div class="practica-detalle-campos">
              <div class="practica-detalle-campo">
                <span class="practica-detalle-campo-etiqueta">Nivel</span>
                <span class="practica-detalle-campo-valor"><?php echo htmlspecialchars(format_value($module['nivel']), ENT_QUOTES, 'UTF-8'); ?></span>
              </div>
              <div class="practica-detalle-campo">
                <span class="practica-detalle-campo-etiqueta">Ciclo</span>
                <span class="practica-detalle-campo-valor"><?php echo htmlspecialchars(format_value($module['ciclo_abreviatura']), ENT_QUOTES, 'UTF-8'); ?></span>
              </div>
              <div class="practica-detalle-campo">
                <span class="practica-detalle-campo-etiqueta">Curso</span>
                <span class="practica-detalle-campo-valor"><?php echo htmlspecialchars(format_value($module['curso']), ENT_QUOTES, 'UTF-8'); ?></span>
              </div>
              <div class="practica-detalle-campo">
                <span class="practica-detalle-campo-etiqueta">Horas semanales</span>
                <span class="practica-detalle-campo-valor"><?php echo htmlspecialchars(format_value($module['horas_semanales']), ENT_QUOTES, 'UTF-8'); ?></span>
              </div>
              <div class="practica-detalle-campo">
                <span class="practica-detalle-campo-etiqueta">Horas totales</span>
                <span class="practica-detalle-campo-valor"><?php echo htmlspecialchars(format_value($module['horas_totales']), ENT_QUOTES, 'UTF-8'); ?></span>
              </div>
            </div>
          </section>
        </div>

        <section class="panel">
          <div class="panel-header">
            <h3>Resultados de aprendizaje</h3>
            <p>Bloques de resultados de aprendizaje y criterios de evaluación asociados.</p>
          </div>
          <?php if (!$learning_results): ?>
            <p>Este módulo no tiene resultados de aprendizaje registrados.</p>
          <?php else: ?>
            <div class="modulo-ra-list">
              <?php foreach ($learning_results as $ra): ?>
                <?php
                  $ra_id = (int) $ra['id_ra'];
                  $criteria = $criteria_by_ra[$ra_id] ?? [];
                  $ra_number = (string) ($ra['numero'] ?? '');
                ?>
                <div class="modulo-ra">
                  <div class="modulo-ra__header">
                    <span class="modulo-ra__number">RA <?php echo htmlspecialchars($ra_number !== '' ? $ra_number : '-', ENT_QUOTES, 'UTF-8'); ?></span>
                    <p class="modulo-ra__desc"><?php echo nl2br(htmlspecialchars(format_value($ra['descripcion']), ENT_QUOTES, 'UTF-8')); ?></p>
                  </div>
                  <?php if (!$criteria): ?>
                    <p>No hay criterios de evaluación asociados a este resultado de aprendizaje.</p>
                  <?php else: ?>
                    <ul class="modulo-ra__criteria">
                      <?php foreach ($criteria as $criterion): ?>
                        <?php $codigo = trim((string) ($criterion['codigo'] ?? '')); ?>
                        <li>
                          <span class="modulo-ra__code"><?php echo htmlspecialchars($codigo !== '' ? $codigo . ')' : '-', ENT_QUOTES, 'UTF-8'); ?></span>
                          <span><?php echo htmlspecialchars(format_value($criterion['descripcion']), ENT_QUOTES, 'UTF-8'); ?></span>
                        </li>
                      <?php endforeach; ?>
                    </ul>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </section>
      <?php endif; ?>
    </main>
  </div>
</body>
</html>
