<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/db.php';

function normalize_value(string $value): string
{
  return mb_strtolower(trim($value), 'UTF-8');
}

function format_optional_date(?string $value): string
{
  if ($value === null || trim($value) === '') {
    return 'No disponible';
  }

  $date = DateTime::createFromFormat('Y-m-d', $value);
  if ($date !== false && $date->format('Y-m-d') === $value) {
    return $date->format('d/m/Y');
  }

  return $value;
}

function redirect_to_practicas(array $params = []): void
{
  $query = http_build_query($params);
  header('Location: practicas.php' . ($query !== '' ? '?' . $query : ''));
  exit;
}

$id_practica = filter_input(INPUT_GET, 'id_practica', FILTER_VALIDATE_INT);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id_practica = filter_input(INPUT_POST, 'id_practica', FILTER_VALIDATE_INT);
}

if ($id_practica === false || $id_practica === null || $id_practica <= 0) {
  redirect_to_practicas(['error' => 'invalid_id']);
}

$pdo = db();

$practice_stmt = $pdo->prepare(
  'SELECT
    p.id_practica,
    p.fecha_inicio,
    p.fecha_fin,
    p.anexo,
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
  WHERE p.id_practica = :id_practica'
);
$practice_stmt->execute(['id_practica' => $id_practica]);
$practice = $practice_stmt->fetch();

if (!$practice) {
  redirect_to_practicas(['error' => 'not_found']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['cancel'])) {
    redirect_to_practicas();
  }

  $csrf_token = (string) ($_POST['csrf_token'] ?? '');
  $session_token = (string) ($_SESSION['practica_eliminar_csrf_' . $id_practica] ?? '');

  if ($session_token === '' || !hash_equals($session_token, $csrf_token)) {
    redirect_to_practicas(['error' => 'invalid_csrf']);
  }

  $apellido_confirmacion = (string) ($_POST['apellido_confirmacion'] ?? '');
  $apellido_esperado = (string) ($practice['alumno_apellido1'] ?? '');

  if (normalize_value($apellido_confirmacion) !== normalize_value($apellido_esperado)) {
    redirect_to_practicas(['error' => 'bad_confirmation']);
  }

  try {
    $pdo->beginTransaction();

    $delete_horario_stmt = $pdo->prepare('DELETE FROM practicas_horario WHERE id_practica = :id_practica');
    $delete_horario_stmt->execute(['id_practica' => $id_practica]);

    $delete_practice_stmt = $pdo->prepare('DELETE FROM practicas WHERE id_practica = :id_practica');
    $delete_practice_stmt->execute(['id_practica' => $id_practica]);

    if ($delete_practice_stmt->rowCount() !== 1) {
      throw new RuntimeException('No se pudo eliminar el registro principal de la práctica.');
    }

    $pdo->commit();
    unset($_SESSION['practica_eliminar_csrf_' . $id_practica]);

    redirect_to_practicas(['status' => 'deleted']);
  } catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
      $pdo->rollBack();
    }

    $sql_state = 'N/A';
    $pdo_code = 'N/A';

    if ($exception instanceof PDOException) {
      $sql_state = (string) $exception->getCode();
      $error_info = $exception->errorInfo ?? null;
      if (is_array($error_info) && isset($error_info[1])) {
        $pdo_code = (string) $error_info[1];
      }
    }

    $detail = sprintf(
      'SQLSTATE: %s; Código PDO: %s; Explicación: fallo durante el borrado transaccional y se revirtió la operación completa.',
      $sql_state,
      $pdo_code
    );

    redirect_to_practicas([
      'error' => 'delete_failed',
      'detail' => $detail,
    ]);
  }
}

$csrf_token = bin2hex(random_bytes(32));
$_SESSION['practica_eliminar_csrf_' . $id_practica] = $csrf_token;

$alumno_nombre = trim(implode(' ', array_filter([
  trim((string) ($practice['alumno_apellido1'] ?? '')),
  trim((string) ($practice['alumno_apellido2'] ?? '')),
  trim((string) ($practice['alumno_nombre'] ?? '')),
], static fn ($value) => $value !== '')));

$empresa_nombre = trim(implode(' ', array_filter([
  trim((string) ($practice['empresa_nombre'] ?? '')),
  trim((string) ($practice['empresa_apellido1'] ?? '')),
  trim((string) ($practice['empresa_apellido2'] ?? '')),
], static fn ($value) => $value !== '')));

$anexo = $practice['convenio'] !== null && $practice['convenio'] !== ''
  ? (string) $practice['convenio'] . ' / ' . ((string) ($practice['anexo'] ?? ''))
  : (string) ($practice['anexo'] ?? 'No disponible');

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Confirmar eliminación de práctica</title>
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
          <h1>Eliminar práctica</h1>
          <p class="subheading">Confirma el borrado definitivo de la práctica seleccionada.</p>
        </div>
      </header>

      <section class="panel panel-grid">
        <h3>Datos de la práctica</h3>
        <p><strong>Alumno:</strong> <?php echo htmlspecialchars($alumno_nombre !== '' ? $alumno_nombre : 'No disponible', ENT_QUOTES, 'UTF-8'); ?></p>
        <p><strong>Empresa:</strong> <?php echo htmlspecialchars($empresa_nombre !== '' ? $empresa_nombre : 'No disponible', ENT_QUOTES, 'UTF-8'); ?></p>
        <p><strong>Fecha de inicio:</strong> <?php echo htmlspecialchars(format_optional_date($practice['fecha_inicio'] ?? null), ENT_QUOTES, 'UTF-8'); ?></p>
        <p><strong>Fecha de fin:</strong> <?php echo htmlspecialchars(format_optional_date($practice['fecha_fin'] ?? null), ENT_QUOTES, 'UTF-8'); ?></p>
        <p><strong>Anexo 2.1:</strong> <?php echo htmlspecialchars($anexo, ENT_QUOTES, 'UTF-8'); ?></p>
      </section>

      <section class="panel panel-grid">
        <h3>Confirmación obligatoria</h3>
        <p>Para eliminar esta práctica escribe el primer apellido del alumno asociado.</p>

        <form method="post">
          <input type="hidden" name="id_practica" value="<?php echo htmlspecialchars((string) $id_practica, ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">

          <label for="apellido_confirmacion">Primer apellido del alumno</label>
          <input
            id="apellido_confirmacion"
            type="text"
            name="apellido_confirmacion"
            required
            autocomplete="off"
          >

          <div class="header-actions">
            <button type="submit" class="edit-toggle">Eliminar definitivamente</button>
            <button type="submit" name="cancel" value="1" class="edit-toggle">Cancelar</button>
          </div>
        </form>
      </section>
    </main>
  </div>
</body>
</html>
