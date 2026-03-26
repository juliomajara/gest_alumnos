<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pdo = db();

$page_title = 'Emails | Gestor de Alumnos';
$active_page = 'utilidades';

$cursos_stmt = $pdo->query('SELECT id_curso_escolar, curso_escolar, activo FROM cursos_escolares ORDER BY activo DESC, id_curso_escolar DESC');
$cursos_escolares = $cursos_stmt->fetchAll(PDO::FETCH_ASSOC);

$curso_activo_id = 0;
foreach ($cursos_escolares as $curso_item) {
  if ((int) ($curso_item['activo'] ?? 0) === 1) {
    $curso_activo_id = (int) $curso_item['id_curso_escolar'];
    break;
  }
}
if ($curso_activo_id === 0 && $cursos_escolares) {
  $curso_activo_id = (int) $cursos_escolares[0]['id_curso_escolar'];
}

$curso_escolar_id = isset($_GET['id_curso_escolar']) && ctype_digit((string) $_GET['id_curso_escolar'])
  ? (int) $_GET['id_curso_escolar']
  : $curso_activo_id;

$tipo = (string) ($_GET['tipo'] ?? 'alumno');
if (!in_array($tipo, ['alumno', 'empresa', 'profesor'], true)) {
  $tipo = 'alumno';
}

$grupos_stmt = $pdo->prepare(
  'SELECT DISTINCT g.id_grupo, g.grupo
   FROM grupos g
   INNER JOIN alumno_curso ac
     ON ac.id_grupo = g.id_grupo
   WHERE ac.id_curso_escolar = :id_curso_escolar
   ORDER BY g.grupo'
);
$grupos_stmt->execute(['id_curso_escolar' => $curso_escolar_id]);
$grupos = $grupos_stmt->fetchAll(PDO::FETCH_ASSOC);

$grupo_id = (string) ($_GET['id_grupo'] ?? '');
if ($tipo !== 'alumno') {
  $grupo_id = '';
}
if ($grupo_id !== '' && !ctype_digit($grupo_id)) {
  $grupo_id = '';
}

$emails = [];

if ($tipo === 'alumno') {
  $sql_alumnos =
    'SELECT DISTINCT c.direccion_correo
     FROM alumno_curso ac
     INNER JOIN alumnos a ON a.id_alumno = ac.id_alumno
     INNER JOIN correos c
       ON c.id_entidad = a.id_alumno
      AND c.entidad_tipo = "alumno"
     WHERE ac.id_curso_escolar = :id_curso_escolar
       AND TRIM(COALESCE(c.direccion_correo, "")) <> ""';

  $params_alumnos = ['id_curso_escolar' => $curso_escolar_id];

  if ($grupo_id !== '') {
    $sql_alumnos .= ' AND ac.id_grupo = :id_grupo';
    $params_alumnos['id_grupo'] = (int) $grupo_id;
  }

  $sql_alumnos .= ' ORDER BY c.direccion_correo';

  $emails_stmt = $pdo->prepare($sql_alumnos);
  $emails_stmt->execute($params_alumnos);
  $emails = $emails_stmt->fetchAll(PDO::FETCH_COLUMN);
} elseif ($tipo === 'empresa') {
  $emails_stmt = $pdo->prepare(
    'SELECT DISTINCT c.direccion_correo
     FROM practicas p
     INNER JOIN alumno_curso ac
       ON ac.id_alumno = p.id_alumno
      AND ac.id_curso_escolar = :id_curso_escolar
     INNER JOIN empresas_contactos ec
       ON ec.id_empresa = p.id_empresa
     INNER JOIN correos c
       ON c.id_entidad = ec.id_empresa_contacto
      AND c.entidad_tipo = "empresa_contacto"
     WHERE TRIM(COALESCE(c.direccion_correo, "")) <> ""
     ORDER BY c.direccion_correo'
  );
  $emails_stmt->execute(['id_curso_escolar' => $curso_escolar_id]);
  $emails = $emails_stmt->fetchAll(PDO::FETCH_COLUMN);
} else {
  $emails_stmt = $pdo->prepare(
    'SELECT DISTINCT c.direccion_correo
     FROM profesores p
     INNER JOIN correos c
       ON c.id_entidad = p.id_profesor
      AND c.entidad_tipo = "profesor"
     WHERE TRIM(COALESCE(c.direccion_correo, "")) <> ""
       AND (
         EXISTS (
           SELECT 1
           FROM grupos_tutores gt
           WHERE gt.id_profesor = p.id_profesor
             AND gt.id_curso_escolar = :id_curso_escolar_tutores
         )
         OR EXISTS (
           SELECT 1
           FROM modulos_profesores mp
           WHERE mp.id_profesor = p.id_profesor
             AND mp.id_curso_escolar = :id_curso_escolar_modulos
         )
       )
     ORDER BY c.direccion_correo'
  );
  $emails_stmt->execute([
    'id_curso_escolar_tutores' => $curso_escolar_id,
    'id_curso_escolar_modulos' => $curso_escolar_id,
  ]);
  $emails = $emails_stmt->fetchAll(PDO::FETCH_COLUMN);
}

$emails = array_values(array_unique(array_filter(array_map(static fn ($email): string => trim((string) $email), $emails), static fn (string $email): bool => $email !== '')));
$emails_texto = implode('; ', $emails);
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
          <h1>Emails</h1>
          <p class="subheading">Consulta y copia direcciones de correo por curso, tipo y grupo.</p>
        </div>
      </header>

      <section class="panel">
        <div class="panel-header">
          <h3>Filtros</h3>
          <p>Selecciona el contexto para obtener los correos listos para copiar.</p>
        </div>

        <form class="topbar" method="get">
          <div class="topbar-actions">
            <label class="calendar-select">
              <select name="id_curso_escolar">
                <?php foreach ($cursos_escolares as $curso): ?>
                  <option value="<?php echo (int) $curso['id_curso_escolar']; ?>" <?php echo (int) $curso['id_curso_escolar'] === $curso_escolar_id ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars((string) $curso['curso_escolar'], ENT_QUOTES, 'UTF-8'); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </label>

            <label class="calendar-select">
              <select name="tipo">
                <option value="alumno" <?php echo $tipo === 'alumno' ? 'selected' : ''; ?>>alumno</option>
                <option value="empresa" <?php echo $tipo === 'empresa' ? 'selected' : ''; ?>>empresa</option>
                <option value="profesor" <?php echo $tipo === 'profesor' ? 'selected' : ''; ?>>profesor</option>
              </select>
            </label>

            <?php if ($tipo === 'alumno'): ?>
              <label class="calendar-select">
                <select name="id_grupo">
                  <option value="">Todos los grupos</option>
                  <?php foreach ($grupos as $grupo): ?>
                    <option value="<?php echo (int) $grupo['id_grupo']; ?>" <?php echo (string) $grupo['id_grupo'] === $grupo_id ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars((string) $grupo['grupo'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </label>
            <?php endif; ?>
          </div>
          <button type="submit" class="edit-toggle">Aplicar filtros</button>
        </form>
      </section>

      <section class="panel">
        <div class="panel-header">
          <h3>Correos encontrados</h3>
          <p><?php echo count($emails); ?> correo(s) disponible(s) para copiar.</p>
        </div>

        <div class="panel-grid">
          <textarea rows="10" readonly><?php echo htmlspecialchars($emails_texto, ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>
      </section>
    </main>
  </div>
</body>
</html>
