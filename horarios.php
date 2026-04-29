<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pdo = db();

$courses = $pdo->query('SELECT id_curso_escolar, curso_escolar, activo FROM cursos_escolares ORDER BY activo DESC, curso_escolar DESC')->fetchAll(PDO::FETCH_ASSOC);
$groups = $pdo->query('SELECT id_grupo, grupo FROM grupos ORDER BY grupo')->fetchAll(PDO::FETCH_ASSOC);

$validCourseIds = array_map(static fn(array $c): int => (int) $c['id_curso_escolar'], $courses);
$validGroupIds = array_map(static fn(array $g): int => (int) $g['id_grupo'], $groups);

$defaultCourse = 0;
foreach ($courses as $courseItem) {
  if ((int) ($courseItem['activo'] ?? 0) === 1) {
    $defaultCourse = (int) $courseItem['id_curso_escolar'];
    break;
  }
}
if ($defaultCourse <= 0 && isset($courses[0]['id_curso_escolar'])) {
  $defaultCourse = (int) $courses[0]['id_curso_escolar'];
}

$defaultGroup = isset($groups[0]['id_grupo']) ? (int) $groups[0]['id_grupo'] : 0;

$requestedCourse = isset($_GET['id_curso_escolar']) ? (int) $_GET['id_curso_escolar'] : 0;
$requestedGroup = isset($_GET['id_grupo']) ? (int) $_GET['id_grupo'] : 0;

$selectedCourse = in_array($requestedCourse, $validCourseIds, true) ? $requestedCourse : $defaultCourse;
$selectedGroup = in_array($requestedGroup, $validGroupIds, true) ? $requestedGroup : $defaultGroup;

$tramos = $pdo->query('SELECT id_horario_tramo, numero_tramo, hora_inicio, hora_fin FROM horarios_tramos ORDER BY numero_tramo')->fetchAll(PDO::FETCH_ASSOC);

$modulesStmt = $pdo->prepare(
  'SELECT DISTINCT
    m.id_modulo,
    COALESCE(NULLIF(m.materia_propia, ""), NULLIF(m.materia_general, ""), m.abreviatura, m.codigo, CONCAT("Módulo ", m.id_modulo)) AS nombre,
    COALESCE(m.horas_semanales, 0) AS horas_semanales,
    m.codigo,
    m.abreviatura
  FROM alumno_curso ac
  INNER JOIN alumno_modulo am ON am.id_alumno = ac.id_alumno
  INNER JOIN modulos m ON m.id_modulo = am.id_modulo
  WHERE ac.id_curso_escolar = :id_curso_escolar
    AND ac.id_grupo = :id_grupo
  GROUP BY m.id_modulo, m.codigo, m.abreviatura, m.materia_propia, m.materia_general, m.horas_semanales
  ORDER BY m.codigo, nombre'
);
$modulesStmt->execute(['id_curso_escolar' => $selectedCourse, 'id_grupo' => $selectedGroup]);
$modules = $modulesStmt->fetchAll(PDO::FETCH_ASSOC);

$moduleColorMap = [];
$paletteIndex = 0;
foreach ($modules as $module) {
  $limit = (int) ($module['horas_semanales'] ?? 0);
  if ($limit > 0) {
    $moduleColorMap[(int) $module['id_modulo']] = 'module-color-' . (($paletteIndex % 10) + 1);
    $paletteIndex++;
  }
}

$scheduleRows = [];
if ($selectedCourse > 0 && $selectedGroup > 0) {
  $scheduleStmt = $pdo->prepare(
    'SELECT hg.dia_semana, hg.id_horario_tramo, hg.id_modulo,
            COALESCE(NULLIF(m.materia_propia, ""), NULLIF(m.materia_general, ""), m.abreviatura, m.codigo, CONCAT("Módulo ", m.id_modulo)) AS nombre,
            m.codigo,
            CONCAT_WS(" ", p.nombre, p.apellido1, p.apellido2) AS profesor
     FROM horarios_grupos hg
     INNER JOIN modulos m ON m.id_modulo = hg.id_modulo
     LEFT JOIN profesores p ON p.id_profesor = hg.id_profesor
     WHERE hg.id_curso_escolar = :id_curso_escolar
       AND hg.id_grupo = :id_grupo
     ORDER BY hg.dia_semana, hg.id_horario_tramo'
  );
  $scheduleStmt->execute(['id_curso_escolar' => $selectedCourse, 'id_grupo' => $selectedGroup]);
  $scheduleRows = $scheduleStmt->fetchAll(PDO::FETCH_ASSOC);
}

$scheduleMap = [];
foreach ($scheduleRows as $row) {
  $scheduleMap[(int) $row['dia_semana'] . '-' . (int) $row['id_horario_tramo']] = $row;
}

$page_title = 'Horarios | Gestor de Alumnos';
$active_page = 'horarios';
?>
<!doctype html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?></title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="assets/styles.css"></head>
<body><div class="page"><?php require __DIR__ . '/includes/sidebar.php'; ?><main class="content">
<header class="header"><div><h1>Horarios</h1><p class="subheading">Consulta los horarios semanales de los grupos.</p></div></header>
<section class="panel"><div class="panel-header"><h3>Configuración</h3></div>
<form class="entity-form horarios-configuracion" method="get" action="horarios.php"><label>Curso escolar<select name="id_curso_escolar" onchange="this.form.submit()"><?php foreach ($courses as $c): ?><option value="<?php echo (int) $c['id_curso_escolar']; ?>" <?php echo (int) $c['id_curso_escolar'] === $selectedCourse ? 'selected' : ''; ?>><?php echo htmlspecialchars((string) $c['curso_escolar'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></label><label>Grupo<select name="id_grupo" onchange="this.form.submit()"><?php foreach ($groups as $g): ?><option value="<?php echo (int) $g['id_grupo']; ?>" <?php echo (int) $g['id_grupo'] === $selectedGroup ? 'selected' : ''; ?>><?php echo htmlspecialchars((string) $g['grupo'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></label><a class="btn" href="horarios_editar.php?id_curso_escolar=<?php echo (int) $selectedCourse; ?>&id_grupo=<?php echo (int) $selectedGroup; ?>">Editar</a><a class="btn" href="horarios_importar.php?id_curso_escolar=<?php echo (int) $selectedCourse; ?>&id_grupo=<?php echo (int) $selectedGroup; ?>">Importar</a></form>

<?php if ($scheduleRows === []): ?>
  <p class="subheading">No hay horario guardado para el curso y grupo seleccionados.</p>
<?php else: ?>
  <div class="panel-grid horarios-grid-wrap"><table class="horarios-grid horarios-view-table"><thead><tr><th>Tramo</th><th>Lunes</th><th>Martes</th><th>Miércoles</th><th>Jueves</th><th>Viernes</th></tr></thead><tbody><?php foreach ($tramos as $tramo): ?><tr><td><?php echo (int) $tramo['numero_tramo']; ?>ª<?php if (!empty($tramo['hora_inicio']) && !empty($tramo['hora_fin'])): ?> (<?php echo htmlspecialchars(substr((string) $tramo['hora_inicio'], 0, 5), ENT_QUOTES, 'UTF-8'); ?>-<?php echo htmlspecialchars(substr((string) $tramo['hora_fin'], 0, 5), ENT_QUOTES, 'UTF-8'); ?>)<?php endif; ?></td><?php for ($day = 1; $day <= 5; $day++): $key = $day . '-' . (int) $tramo['id_horario_tramo']; $cell = $scheduleMap[$key] ?? null; ?><?php if ($cell !== null): $moduleId = (int) $cell['id_modulo']; $colorClass = $moduleColorMap[$moduleId] ?? 'module-color-disabled'; $moduleName = trim((string) ($cell['nombre'] ?? '')); $moduleCode = trim((string) ($cell['codigo'] ?? '')); $moduleTeacher = trim((string) ($cell['profesor'] ?? '')); $titleParts = [$moduleName]; if ($moduleCode !== '') { $titleParts[] = 'Código: ' . $moduleCode; } if ($moduleTeacher !== '') { $titleParts[] = 'Profesor: ' . $moduleTeacher; } $moduleTitle = implode(' | ', $titleParts); ?><td class="horarios-module-cell <?php echo htmlspecialchars($colorClass, ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo htmlspecialchars($moduleTitle, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($moduleName, ENT_QUOTES, 'UTF-8'); ?></td><?php else: ?><td class="horarios-empty-cell"></td><?php endif; ?><?php endfor; ?></tr><?php endforeach; ?></tbody></table></div>
<?php endif; ?>
</section>
</main></div></body></html>
