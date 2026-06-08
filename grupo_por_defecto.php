<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pdo = db();

$page_title = 'Grupo por defecto | Gestor de Alumnos';
$active_page = 'configuracion';

$errors = [];

function h(?string $value): string {
  return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$grupos = $pdo->query('SELECT id_grupo, grupo FROM grupos ORDER BY grupo')->fetchAll();

$current_value = (string) ($pdo->query("SELECT valor FROM `config` WHERE `clave` = 'grupo_por_defecto' LIMIT 1")->fetchColumn() ?: '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $new_value = trim((string) ($_POST['grupo_por_defecto'] ?? ''));

  if ($new_value !== '') {
    $valid_ids = array_map('intval', array_column($grupos, 'id_grupo'));
    if (!in_array((int) $new_value, $valid_ids, true)) {
      $errors[] = 'El grupo seleccionado no es válido.';
    }
  }

  if (!$errors) {
    $check_stmt = $pdo->prepare("SELECT 1 FROM `config` WHERE `clave` = 'grupo_por_defecto' LIMIT 1");
    $check_stmt->execute();
    if ($check_stmt->fetchColumn()) {
      $save_stmt = $pdo->prepare("UPDATE `config` SET `valor` = :valor WHERE `clave` = 'grupo_por_defecto'");
    } else {
      $save_stmt = $pdo->prepare("INSERT INTO `config` (`clave`, `valor`) VALUES ('grupo_por_defecto', :valor)");
    }
    $save_stmt->execute(['valor' => $new_value]);
    $current_value = $new_value;
    header('Location: grupo_por_defecto.php?ok=1');
    exit;
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo h($page_title); ?></title>
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
          <h1>Grupo por defecto</h1>
          <p class="subheading">El grupo seleccionado aparecerá preseleccionado en el resto de páginas al abrirlas.</p>
        </div>
        <div class="header-actions">
          <button class="edit-toggle" type="button" id="editToggle">Modo edición</button>
          <button type="submit" class="primary-button" id="saveChangesButton" form="grupoPorDefectoForm" hidden>Guardar cambios</button>
        </div>
      </header>

      <?php if (isset($_GET['ok']) && $_GET['ok'] === '1'): ?>
        <section class="panel">
          <p>Cambios guardados correctamente.</p>
        </section>
      <?php endif; ?>

      <form method="post" class="panel entity-form" id="grupoPorDefectoForm">
        <?php if ($errors): ?>
          <ul class="form-errors">
            <?php foreach ($errors as $error): ?>
              <li><?php echo h($error); ?></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>

        <section class="entity-section">
          <div class="entity-grid entity-grid--2">
            <label>
              Grupo por defecto
              <select name="grupo_por_defecto" disabled>
                <option value="">Sin grupo por defecto</option>
                <?php foreach ($grupos as $grupo): ?>
                  <option value="<?php echo (int) $grupo['id_grupo']; ?>" <?php echo (string) $grupo['id_grupo'] === $current_value ? 'selected' : ''; ?>>
                    <?php echo h((string) $grupo['grupo']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </label>
          </div>
        </section>
      </form>
    </main>
  </div>
  <script>
    (() => {
      const form = document.querySelector('form.entity-form');
      const editToggle = document.getElementById('editToggle');
      const saveChangesButton = document.getElementById('saveChangesButton');
      if (!form || !editToggle || !saveChangesButton) {
        return;
      }

      let editMode = false;
      const controls = form.querySelectorAll('input, select, textarea');

      const applyEditMode = () => {
        controls.forEach((control) => {
          if (control.tagName === 'SELECT') {
            control.disabled = !editMode;
            return;
          }
          const inputType = (control.type || '').toLowerCase();
          if (inputType === 'checkbox' || inputType === 'radio' || inputType === 'file' || inputType === 'button') {
            control.disabled = !editMode;
          } else {
            control.readOnly = !editMode;
          }
        });
        editToggle.classList.toggle('is-active', editMode);
        saveChangesButton.hidden = !editMode;
      };

      applyEditMode();

      editToggle.addEventListener('click', () => {
        editMode = !editMode;
        applyEditMode();
      });
    })();
  </script>
</body>
</html>
