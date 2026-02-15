<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pdo = db();

function h($value): string
{
  return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function normalize_text($value): ?string
{
  if ($value === null) {
    return null;
  }

  $trimmed = trim((string) $value);
  return $trimmed === '' ? null : $trimmed;
}

function sanitize_contact_rows($value, string $field): array
{
  if (!is_array($value)) {
    return [];
  }

  $rows = [];
  foreach ($value as $row) {
    if (!is_array($row)) {
      continue;
    }

    $rows[] = [
      'id' => isset($row['id']) ? (int) $row['id'] : 0,
      'value' => normalize_text($row[$field] ?? null),
      'etiqueta' => normalize_text($row['etiqueta'] ?? null),
      'delete' => isset($row['_delete']) && (string) $row['_delete'] === '1',
    ];
  }

  return $rows;
}

$idProfesor = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($idProfesor <= 0 && isset($_POST['id_profesor'])) {
  $idProfesor = (int) $_POST['id_profesor'];
}

$activeCourseId = $pdo->query('SELECT id_curso_escolar FROM cursos_escolares WHERE activo = 1 LIMIT 1')->fetchColumn();
$activeCourseId = $activeCourseId ? (int) $activeCourseId : 0;

$errors = [];
$message = null;

$grupos = $pdo->query('SELECT id_grupo, grupo FROM grupos ORDER BY grupo')->fetchAll();
$modulosDisponibles = $pdo->query('SELECT id_modulo, abreviatura, materia_general, materia_propia FROM modulos ORDER BY abreviatura, materia_general')->fetchAll();

$profesor = null;
$telefonos = [];
$correos = [];
$selectedGroupTutor = 0;
$selectedModulos = [];

if ($idProfesor > 0) {
  $stmtProfesor = $pdo->prepare('SELECT id_profesor, apellido1, apellido2, nombre, dni FROM profesores WHERE id_profesor = :id_profesor');
  $stmtProfesor->execute(['id_profesor' => $idProfesor]);
  $profesor = $stmtProfesor->fetch();
}

if ($profesor) {
  $stmtTelefonos = $pdo->prepare('SELECT id_telefono, telefono, etiqueta FROM telefonos WHERE entidad_tipo = :tipo AND id_entidad = :id_entidad ORDER BY id_telefono');
  $stmtTelefonos->execute(['tipo' => 'profesor', 'id_entidad' => $idProfesor]);
  $telefonos = $stmtTelefonos->fetchAll();

  $stmtCorreos = $pdo->prepare('SELECT id_correo, direccion_correo, etiqueta FROM correos WHERE entidad_tipo = :tipo AND id_entidad = :id_entidad ORDER BY id_correo');
  $stmtCorreos->execute(['tipo' => 'profesor', 'id_entidad' => $idProfesor]);
  $correos = $stmtCorreos->fetchAll();

  if ($activeCourseId > 0) {
    $stmtTutor = $pdo->prepare('SELECT id_grupo FROM grupos_tutores WHERE id_profesor = :id_profesor AND id_curso_escolar = :id_curso_escolar LIMIT 1');
    $stmtTutor->execute([
      'id_profesor' => $idProfesor,
      'id_curso_escolar' => $activeCourseId,
    ]);
    $selectedGroupTutor = (int) ($stmtTutor->fetchColumn() ?: 0);

    $stmtModulos = $pdo->prepare('SELECT id_modulo FROM modulos_profesores WHERE id_profesor = :id_profesor AND id_curso_escolar = :id_curso_escolar');
    $stmtModulos->execute([
      'id_profesor' => $idProfesor,
      'id_curso_escolar' => $activeCourseId,
    ]);
    $selectedModulos = array_map('intval', $stmtModulos->fetchAll(PDO::FETCH_COLUMN));
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!$profesor || $idProfesor <= 0) {
    $errors[] = 'No se puede editar el profesor solicitado porque no existe.';
  } else {
    $apellido1 = normalize_text($_POST['apellido1'] ?? null);
    $apellido2 = normalize_text($_POST['apellido2'] ?? null);
    $nombre = normalize_text($_POST['nombre'] ?? null);
    $dni = normalize_text($_POST['dni'] ?? null);
    $selectedGroupTutor = isset($_POST['id_grupo_tutor']) ? (int) $_POST['id_grupo_tutor'] : 0;

    $selectedModulos = [];
    if (is_array($_POST['modulos'] ?? null)) {
      $selectedModulos = array_values(array_unique(array_map('intval', $_POST['modulos'])));
      $selectedModulos = array_values(array_filter($selectedModulos, static fn (int $value): bool => $value > 0));
    }

    if ($apellido1 === null) {
      $errors[] = 'El campo Apellido 1 es obligatorio.';
    }

    if ($nombre === null) {
      $errors[] = 'El campo Nombre es obligatorio.';
    }

    $telefonoRows = sanitize_contact_rows($_POST['telefonos'] ?? [], 'telefono');
    $correoRows = sanitize_contact_rows($_POST['correos'] ?? [], 'direccion_correo');

    foreach ($correoRows as $row) {
      if (!$row['delete'] && $row['value'] !== null && !filter_var($row['value'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Uno de los correos no tiene un formato válido.';
        break;
      }
    }

    if (!$errors) {
      try {
        $pdo->beginTransaction();

        $stmtUpdateProfesor = $pdo->prepare(
          'UPDATE profesores
           SET apellido1 = :apellido1, apellido2 = :apellido2, nombre = :nombre, dni = :dni
           WHERE id_profesor = :id_profesor'
        );
        $stmtUpdateProfesor->execute([
          'apellido1' => $apellido1,
          'apellido2' => $apellido2,
          'nombre' => $nombre,
          'dni' => $dni,
          'id_profesor' => $idProfesor,
        ]);

        $existingPhoneIds = $pdo->prepare('SELECT id_telefono FROM telefonos WHERE entidad_tipo = :tipo AND id_entidad = :id_entidad');
        $existingPhoneIds->execute(['tipo' => 'profesor', 'id_entidad' => $idProfesor]);
        $phoneIdsMap = array_flip(array_map('intval', $existingPhoneIds->fetchAll(PDO::FETCH_COLUMN)));

        $stmtUpdatePhone = $pdo->prepare('UPDATE telefonos SET telefono = :telefono, etiqueta = :etiqueta WHERE id_telefono = :id_telefono AND entidad_tipo = :tipo AND id_entidad = :id_entidad');
        $stmtInsertPhone = $pdo->prepare('INSERT INTO telefonos (entidad_tipo, id_entidad, telefono, etiqueta) VALUES (:tipo, :id_entidad, :telefono, :etiqueta)');
        $stmtDeletePhone = $pdo->prepare('DELETE FROM telefonos WHERE id_telefono = :id_telefono AND entidad_tipo = :tipo AND id_entidad = :id_entidad');

        foreach ($telefonoRows as $row) {
          $rowId = (int) $row['id'];
          $value = $row['value'];

          if ($rowId > 0 && isset($phoneIdsMap[$rowId])) {
            if ($row['delete'] || $value === null) {
              $stmtDeletePhone->execute(['id_telefono' => $rowId, 'tipo' => 'profesor', 'id_entidad' => $idProfesor]);
              continue;
            }

            $stmtUpdatePhone->execute([
              'telefono' => $value,
              'etiqueta' => $row['etiqueta'],
              'id_telefono' => $rowId,
              'tipo' => 'profesor',
              'id_entidad' => $idProfesor,
            ]);
            continue;
          }

          if (!$row['delete'] && $value !== null) {
            $stmtInsertPhone->execute([
              'tipo' => 'profesor',
              'id_entidad' => $idProfesor,
              'telefono' => $value,
              'etiqueta' => $row['etiqueta'],
            ]);
          }
        }

        $existingEmailIds = $pdo->prepare('SELECT id_correo FROM correos WHERE entidad_tipo = :tipo AND id_entidad = :id_entidad');
        $existingEmailIds->execute(['tipo' => 'profesor', 'id_entidad' => $idProfesor]);
        $emailIdsMap = array_flip(array_map('intval', $existingEmailIds->fetchAll(PDO::FETCH_COLUMN)));

        $stmtUpdateEmail = $pdo->prepare('UPDATE correos SET direccion_correo = :direccion_correo, etiqueta = :etiqueta WHERE id_correo = :id_correo AND entidad_tipo = :tipo AND id_entidad = :id_entidad');
        $stmtInsertEmail = $pdo->prepare('INSERT INTO correos (entidad_tipo, id_entidad, direccion_correo, etiqueta) VALUES (:tipo, :id_entidad, :direccion_correo, :etiqueta)');
        $stmtDeleteEmail = $pdo->prepare('DELETE FROM correos WHERE id_correo = :id_correo AND entidad_tipo = :tipo AND id_entidad = :id_entidad');

        foreach ($correoRows as $row) {
          $rowId = (int) $row['id'];
          $value = $row['value'];

          if ($rowId > 0 && isset($emailIdsMap[$rowId])) {
            if ($row['delete'] || $value === null) {
              $stmtDeleteEmail->execute(['id_correo' => $rowId, 'tipo' => 'profesor', 'id_entidad' => $idProfesor]);
              continue;
            }

            $stmtUpdateEmail->execute([
              'direccion_correo' => $value,
              'etiqueta' => $row['etiqueta'],
              'id_correo' => $rowId,
              'tipo' => 'profesor',
              'id_entidad' => $idProfesor,
            ]);
            continue;
          }

          if (!$row['delete'] && $value !== null) {
            $stmtInsertEmail->execute([
              'tipo' => 'profesor',
              'id_entidad' => $idProfesor,
              'direccion_correo' => $value,
              'etiqueta' => $row['etiqueta'],
            ]);
          }
        }

        if ($activeCourseId > 0) {
          $stmtDeleteTutor = $pdo->prepare('DELETE FROM grupos_tutores WHERE id_profesor = :id_profesor AND id_curso_escolar = :id_curso_escolar');
          $stmtDeleteTutor->execute(['id_profesor' => $idProfesor, 'id_curso_escolar' => $activeCourseId]);

          if ($selectedGroupTutor > 0) {
            $stmtInsertTutor = $pdo->prepare('INSERT INTO grupos_tutores (id_grupo, id_profesor, id_curso_escolar) VALUES (:id_grupo, :id_profesor, :id_curso_escolar)');
            $stmtInsertTutor->execute([
              'id_grupo' => $selectedGroupTutor,
              'id_profesor' => $idProfesor,
              'id_curso_escolar' => $activeCourseId,
            ]);
          }

          $stmtDeleteModulos = $pdo->prepare('DELETE FROM modulos_profesores WHERE id_profesor = :id_profesor AND id_curso_escolar = :id_curso_escolar');
          $stmtDeleteModulos->execute(['id_profesor' => $idProfesor, 'id_curso_escolar' => $activeCourseId]);

          if ($selectedModulos) {
            $stmtInsertModulo = $pdo->prepare('INSERT INTO modulos_profesores (id_modulo, id_profesor, id_curso_escolar) VALUES (:id_modulo, :id_profesor, :id_curso_escolar)');
            foreach ($selectedModulos as $idModulo) {
              $stmtInsertModulo->execute([
                'id_modulo' => $idModulo,
                'id_profesor' => $idProfesor,
                'id_curso_escolar' => $activeCourseId,
              ]);
            }
          }
        }

        $pdo->commit();
        header('Location: profesores.php?updated=1');
        exit;
      } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
          $pdo->rollBack();
        }
        $errors[] = 'No se pudo guardar la información del profesor. Inténtalo de nuevo.';
      }
    }

    $profesor['apellido1'] = $apellido1;
    $profesor['apellido2'] = $apellido2;
    $profesor['nombre'] = $nombre;
    $profesor['dni'] = $dni;

    $telefonos = [];
    foreach ($telefonoRows as $row) {
      if ($row['delete']) {
        continue;
      }
      $telefonos[] = [
        'id_telefono' => $row['id'] > 0 ? $row['id'] : '',
        'telefono' => $row['value'] ?? '',
        'etiqueta' => $row['etiqueta'] ?? '',
      ];
    }

    $correos = [];
    foreach ($correoRows as $row) {
      if ($row['delete']) {
        continue;
      }
      $correos[] = [
        'id_correo' => $row['id'] > 0 ? $row['id'] : '',
        'direccion_correo' => $row['value'] ?? '',
        'etiqueta' => $row['etiqueta'] ?? '',
      ];
    }
  }
}

$page_title = 'Editar profesor | Gestor de Alumnos';
$active_page = 'profesores';
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
          <h1>Editar profesor</h1>
          <p class="subheading">Actualiza datos personales, tutoría, módulos y datos de contacto del profesor.</p>
        </div>
      </header>

      <?php if ($idProfesor <= 0): ?>
        <section class="panel">
          <p>No se ha indicado un identificador de profesor válido.</p>
          <a class="edit-toggle" href="profesores.php">Volver a profesores</a>
        </section>
      <?php elseif (!$profesor): ?>
        <section class="panel">
          <p>El profesor solicitado no existe.</p>
          <a class="edit-toggle" href="profesores.php">Volver a profesores</a>
        </section>
      <?php else: ?>
        <form method="post" class="panel entity-form">
          <input type="hidden" name="id_profesor" value="<?php echo (int) $idProfesor; ?>">

          <?php if ($errors): ?>
            <ul class="form-errors">
              <?php foreach ($errors as $error): ?>
                <li><?php echo h($error); ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>

          <section class="entity-section">
            <div class="entity-grid entity-grid--4">
              <label>
                Apellido 1
                <input type="text" name="apellido1" required value="<?php echo h($profesor['apellido1'] ?? ''); ?>">
              </label>
              <label>
                Apellido 2
                <input type="text" name="apellido2" value="<?php echo h($profesor['apellido2'] ?? ''); ?>">
              </label>
              <label>
                Nombre
                <input type="text" name="nombre" required value="<?php echo h($profesor['nombre'] ?? ''); ?>">
              </label>
              <label>
                DNI
                <input type="text" name="dni" value="<?php echo h($profesor['dni'] ?? ''); ?>">
              </label>
            </div>
          </section>

          <section class="entity-section">
            <div class="entity-grid entity-grid--3">
              <label>
                Grupo tutor
                <select name="id_grupo_tutor">
                  <option value="0">Sin tutoría</option>
                  <?php foreach ($grupos as $grupo): ?>
                    <?php $groupId = (int) $grupo['id_grupo']; ?>
                    <option value="<?php echo $groupId; ?>"<?php echo $groupId === (int) $selectedGroupTutor ? ' selected' : ''; ?>><?php echo h($grupo['grupo']); ?></option>
                  <?php endforeach; ?>
                </select>
              </label>
            </div>
          </section>

          <section class="entity-section">
            <div class="entity-section-header">
              <h3>Módulos</h3>
            </div>
            <div class="entity-stack">
              <?php if (!$modulosDisponibles): ?>
                <p>No hay módulos disponibles.</p>
              <?php else: ?>
                <?php foreach ($modulosDisponibles as $modulo): ?>
                  <?php
                    $idModulo = (int) $modulo['id_modulo'];
                    $labelModulo = trim((string) $modulo['abreviatura'] . ' - ' . ($modulo['materia_propia'] ?: $modulo['materia_general']));
                  ?>
                  <label>
                    <span>
                      <input type="checkbox" name="modulos[]" value="<?php echo $idModulo; ?>"<?php echo in_array($idModulo, $selectedModulos, true) ? ' checked' : ''; ?>>
                      <?php echo h($labelModulo); ?>
                    </span>
                  </label>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </section>

          <section class="entity-section">
            <div class="entity-section-header">
              <h3>Teléfonos</h3>
              <button type="button" class="edit-toggle" id="addPhone">Añadir teléfono</button>
            </div>
            <div class="entity-stack" id="phonesList">
              <?php foreach ($telefonos as $index => $telefono): ?>
                <div class="entity-repeatable-item">
                  <input type="hidden" name="telefonos[<?php echo (int) $index; ?>][id]" value="<?php echo (int) ($telefono['id_telefono'] ?? 0); ?>">
                  <input type="hidden" name="telefonos[<?php echo (int) $index; ?>][_delete]" value="0" class="delete-flag">
                  <div class="entity-inline-grid">
                    <label>
                      Teléfono
                      <input type="text" name="telefonos[<?php echo (int) $index; ?>][telefono]" value="<?php echo h($telefono['telefono'] ?? ''); ?>">
                    </label>
                    <label>
                      Etiqueta
                      <input type="text" name="telefonos[<?php echo (int) $index; ?>][etiqueta]" value="<?php echo h($telefono['etiqueta'] ?? ''); ?>">
                    </label>
                  </div>
                  <button type="button" class="ghost-button remove-row">Eliminar</button>
                </div>
              <?php endforeach; ?>
            </div>
          </section>

          <section class="entity-section">
            <div class="entity-section-header">
              <h3>Correos</h3>
              <button type="button" class="edit-toggle" id="addEmail">Añadir correo</button>
            </div>
            <div class="entity-stack" id="emailsList">
              <?php foreach ($correos as $index => $correo): ?>
                <div class="entity-repeatable-item">
                  <input type="hidden" name="correos[<?php echo (int) $index; ?>][id]" value="<?php echo (int) ($correo['id_correo'] ?? 0); ?>">
                  <input type="hidden" name="correos[<?php echo (int) $index; ?>][_delete]" value="0" class="delete-flag">
                  <div class="entity-inline-grid">
                    <label>
                      Correo
                      <input type="email" name="correos[<?php echo (int) $index; ?>][direccion_correo]" value="<?php echo h($correo['direccion_correo'] ?? ''); ?>">
                    </label>
                    <label>
                      Etiqueta
                      <input type="text" name="correos[<?php echo (int) $index; ?>][etiqueta]" value="<?php echo h($correo['etiqueta'] ?? ''); ?>">
                    </label>
                  </div>
                  <button type="button" class="ghost-button remove-row">Eliminar</button>
                </div>
              <?php endforeach; ?>
            </div>
          </section>

          <div class="form-actions">
            <button type="submit" class="primary-button">Guardar</button>
            <a class="ghost-button" href="profesores.php">Cancelar</a>
          </div>
        </form>
      <?php endif; ?>
    </main>
  </div>

  <template id="phoneTemplate">
    <div class="entity-repeatable-item">
      <input type="hidden" name="__NAME__[__INDEX__][id]" value="0">
      <input type="hidden" name="__NAME__[__INDEX__][_delete]" value="0" class="delete-flag">
      <div class="entity-inline-grid">
        <label>
          Teléfono
          <input type="text" name="__NAME__[__INDEX__][telefono]" value="">
        </label>
        <label>
          Etiqueta
          <input type="text" name="__NAME__[__INDEX__][etiqueta]" value="">
        </label>
      </div>
      <button type="button" class="ghost-button remove-row">Eliminar</button>
    </div>
  </template>

  <template id="emailTemplate">
    <div class="entity-repeatable-item">
      <input type="hidden" name="__NAME__[__INDEX__][id]" value="0">
      <input type="hidden" name="__NAME__[__INDEX__][_delete]" value="0" class="delete-flag">
      <div class="entity-inline-grid">
        <label>
          Correo
          <input type="email" name="__NAME__[__INDEX__][direccion_correo]" value="">
        </label>
        <label>
          Etiqueta
          <input type="text" name="__NAME__[__INDEX__][etiqueta]" value="">
        </label>
      </div>
      <button type="button" class="ghost-button remove-row">Eliminar</button>
    </div>
  </template>

  <script>
    (() => {
      const addRepeatable = (listSelector, templateSelector, baseName) => {
        const list = document.querySelector(listSelector);
        const template = document.querySelector(templateSelector);
        if (!list || !template) {
          return;
        }

        const index = list.querySelectorAll('.entity-repeatable-item').length;
        const html = template.innerHTML
          .replaceAll('__NAME__', baseName)
          .replaceAll('__INDEX__', String(index));

        list.insertAdjacentHTML('beforeend', html);
      };

      const setupRemove = (containerSelector) => {
        const container = document.querySelector(containerSelector);
        if (!container) {
          return;
        }

        container.addEventListener('click', (event) => {
          const target = event.target;
          if (!(target instanceof HTMLElement) || !target.classList.contains('remove-row')) {
            return;
          }

          const item = target.closest('.entity-repeatable-item');
          if (!item) {
            return;
          }

          const idInput = item.querySelector('input[name$="[id]"]');
          const deleteInput = item.querySelector('.delete-flag');

          if (idInput instanceof HTMLInputElement && Number(idInput.value) > 0 && deleteInput instanceof HTMLInputElement) {
            deleteInput.value = '1';
            item.style.display = 'none';
            return;
          }

          item.remove();
        });
      };

      const addPhoneButton = document.querySelector('#addPhone');
      if (addPhoneButton) {
        addPhoneButton.addEventListener('click', () => addRepeatable('#phonesList', '#phoneTemplate', 'telefonos'));
      }

      const addEmailButton = document.querySelector('#addEmail');
      if (addEmailButton) {
        addEmailButton.addEventListener('click', () => addRepeatable('#emailsList', '#emailTemplate', 'correos'));
      }

      setupRemove('#phonesList');
      setupRemove('#emailsList');
    })();
  </script>
</body>
</html>
