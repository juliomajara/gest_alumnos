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

$default_group_id = (string) ($pdo->query("SELECT valor FROM `config` WHERE `clave` = 'grupo_por_defecto' LIMIT 1")->fetchColumn() ?: '');
$grupo_id = (string) ($_GET['id_grupo'] ?? $default_group_id);
if ($tipo !== 'alumno') {
  $grupo_id = '';
}
if ($grupo_id !== '' && !ctype_digit($grupo_id)) {
  $grupo_id = '';
}

$emails = [];
$alumnos = [];

if ($tipo === 'alumno') {
  $sql_alumnos =
    'SELECT
      a.id_alumno,
      a.apellido1,
      a.apellido2,
      a.nombre,
      MIN(CASE
        WHEN TRIM(COALESCE(c.etiqueta, "")) = "Personal" THEN TRIM(COALESCE(c.direccion_correo, ""))
        ELSE NULL
      END) AS correo_personal,
      MIN(CASE
        WHEN TRIM(COALESCE(c.etiqueta, "")) = "EducaMadrid" THEN TRIM(COALESCE(c.direccion_correo, ""))
        ELSE NULL
      END) AS correo_educamadrid
     FROM alumno_curso ac
     INNER JOIN alumnos a ON a.id_alumno = ac.id_alumno
     LEFT JOIN correos c
       ON c.id_entidad = a.id_alumno
      AND c.entidad_tipo = "alumno"
     WHERE ac.id_curso_escolar = :id_curso_escolar';

  $params_alumnos = ['id_curso_escolar' => $curso_escolar_id];

  if ($grupo_id !== '') {
    $sql_alumnos .= ' AND ac.id_grupo = :id_grupo';
    $params_alumnos['id_grupo'] = (int) $grupo_id;
  }

  $sql_alumnos .= '
     GROUP BY a.id_alumno, a.apellido1, a.apellido2, a.nombre
     ORDER BY a.apellido1, a.apellido2, a.nombre';

  $emails_stmt = $pdo->prepare($sql_alumnos);
  $emails_stmt->execute($params_alumnos);
  $alumnos = $emails_stmt->fetchAll(PDO::FETCH_ASSOC);

  $alumnos_seleccionados = [];
  if (isset($_GET['alumnos']) && is_array($_GET['alumnos'])) {
    foreach ($_GET['alumnos'] as $id_alumno_seleccionado) {
      if (ctype_digit((string) $id_alumno_seleccionado)) {
        $alumnos_seleccionados[] = (int) $id_alumno_seleccionado;
      }
    }
  }
  $correos_personales_seleccionados = [];
  if (isset($_GET['correos_personales']) && is_array($_GET['correos_personales'])) {
    foreach ($_GET['correos_personales'] as $id_alumno_seleccionado) {
      if (ctype_digit((string) $id_alumno_seleccionado)) {
        $correos_personales_seleccionados[] = (int) $id_alumno_seleccionado;
      }
    }
  }
  $correos_educamadrid_seleccionados = [];
  if (isset($_GET['correos_educamadrid']) && is_array($_GET['correos_educamadrid'])) {
    foreach ($_GET['correos_educamadrid'] as $id_alumno_seleccionado) {
      if (ctype_digit((string) $id_alumno_seleccionado)) {
        $correos_educamadrid_seleccionados[] = (int) $id_alumno_seleccionado;
      }
    }
  }

  $seleccion_manual = isset($_GET['seleccion']) && (string) $_GET['seleccion'] === '1';

  foreach ($alumnos as $alumno_item) {
    $id_alumno_item = (int) ($alumno_item['id_alumno'] ?? 0);
    $correo_personal_item = trim((string) ($alumno_item['correo_personal'] ?? ''));
    $correo_educamadrid_item = trim((string) ($alumno_item['correo_educamadrid'] ?? ''));

    $incluir_alumno = $seleccion_manual
      ? in_array($id_alumno_item, $alumnos_seleccionados, true)
      : true;

    if (!$incluir_alumno) {
      continue;
    }

    $incluir_correo_personal = $seleccion_manual
      ? in_array($id_alumno_item, $correos_personales_seleccionados, true)
      : true;
    if ($incluir_correo_personal && $correo_personal_item !== '') {
      $emails[] = $correo_personal_item;
    }

    $incluir_correo_educamadrid = $seleccion_manual
      ? in_array($id_alumno_item, $correos_educamadrid_seleccionados, true)
      : true;
    if ($incluir_correo_educamadrid && $correo_educamadrid_item !== '') {
      $emails[] = $correo_educamadrid_item;
    }
  }
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

        <?php if ($tipo === 'alumno'): ?>
          <form class="panel-grid" method="get">
            <input type="hidden" name="id_curso_escolar" value="<?php echo (int) $curso_escolar_id; ?>">
            <input type="hidden" name="tipo" value="alumno">
            <input type="hidden" name="id_grupo" value="<?php echo htmlspecialchars($grupo_id, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="seleccion" value="1">

            <table>
              <thead>
                <tr>
                  <th>Alumno</th>
                  <th>Nombre del alumno</th>
                  <th>Correo personal</th>
                  <th>Correo de EducaMadrid</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($alumnos as $alumno): ?>
                  <?php
                  $id_alumno = (int) ($alumno['id_alumno'] ?? 0);
                  $apellido1 = trim((string) ($alumno['apellido1'] ?? ''));
                  $apellido2 = trim((string) ($alumno['apellido2'] ?? ''));
                  $nombre = trim((string) ($alumno['nombre'] ?? ''));
                  $correo_personal = trim((string) ($alumno['correo_personal'] ?? ''));
                  $correo_educamadrid = trim((string) ($alumno['correo_educamadrid'] ?? ''));
                  $nombre_completo = sprintf('%s%s, %s', $apellido1, $apellido2 !== '' ? ' ' . $apellido2 : '', $nombre);
                  $alumno_checked = !isset($_GET['seleccion']) || in_array($id_alumno, $alumnos_seleccionados, true);
                  $correo_personal_checked = !isset($_GET['seleccion']) || in_array($id_alumno, $correos_personales_seleccionados, true);
                  $correo_educamadrid_checked = !isset($_GET['seleccion']) || in_array($id_alumno, $correos_educamadrid_seleccionados, true);
                  ?>
                  <tr>
                    <td><input type="checkbox" name="alumnos[]" value="<?php echo $id_alumno; ?>" <?php echo $alumno_checked ? 'checked' : ''; ?>></td>
                    <td><?php echo htmlspecialchars($nombre_completo, ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>
                      <input type="checkbox" name="correos_personales[]" value="<?php echo $id_alumno; ?>" <?php echo $correo_personal_checked ? 'checked' : ''; ?>>
                      <?php echo htmlspecialchars($correo_personal, ENT_QUOTES, 'UTF-8'); ?>
                    </td>
                    <td>
                      <input type="checkbox" name="correos_educamadrid[]" value="<?php echo $id_alumno; ?>" <?php echo $correo_educamadrid_checked ? 'checked' : ''; ?>>
                      <?php echo htmlspecialchars($correo_educamadrid, ENT_QUOTES, 'UTF-8'); ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
            <div>
              <button type="submit" class="edit-toggle">Actualizar correos</button>
            </div>
          </form>
        <?php endif; ?>

        <div class="panel-grid">
          <textarea rows="10" readonly><?php echo htmlspecialchars($emails_texto, ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>
      </section>
    </main>
  </div>
</body>
</html>
