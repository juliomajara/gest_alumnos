<?php
/**
 * Página para ver y actualizar la configuración del centro en la tabla `config`.
 * Detecta automáticamente si `config` es de tipo clave/valor (`clave` + `valor`)
 * o si es una tabla de un único registro con múltiples columnas editables.
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pdo = db();
$errors = [];
$isKeyValue = false;
$configColumns = [];
$keyValueRows = [];
$formValues = [];
$singleRow = [];
$singleRowPrimaryWhere = [];

function h(?string $value): string {
  return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function baseType(string $sqlType): string {
  $normalized = strtolower(trim($sqlType));
  if (($pos = strpos($normalized, '(')) !== false) {
    return substr($normalized, 0, $pos);
  }
  return $normalized;
}

function isNumericSqlType(string $sqlType): bool {
  $type = baseType($sqlType);
  return in_array($type, ['tinyint', 'smallint', 'mediumint', 'int', 'integer', 'bigint', 'decimal', 'numeric', 'float', 'double', 'real', 'bit'], true);
}

try {
  $describeStmt = $pdo->query('DESCRIBE `config`');
  $configColumns = $describeStmt->fetchAll();

  if (!$configColumns) {
    $errors[] = 'La tabla config no tiene columnas definidas.';
  }

  $columnMap = [];
  foreach ($configColumns as $column) {
    $columnMap[$column['Field']] = $column;
  }

  $isKeyValue = isset($columnMap['clave'], $columnMap['valor']);

  if ($isKeyValue) {
    $rowsStmt = $pdo->prepare('SELECT * FROM `config` ORDER BY `clave`');
    $rowsStmt->execute();
    $keyValueRows = $rowsStmt->fetchAll();

    foreach ($keyValueRows as $row) {
      $key = (string) ($row['clave'] ?? '');
      if ($key === '') {
        continue;
      }
      $formValues[$key] = (string) ($row['valor'] ?? '');
    }
  } else {
    $rowStmt = $pdo->prepare('SELECT * FROM `config` LIMIT 1');
    $rowStmt->execute();
    $singleRow = $rowStmt->fetch() ?: [];

    if (!$singleRow) {
      $errors[] = 'No hay registros en la tabla config para editar.';
    }

    foreach ($configColumns as $column) {
      if (($column['Key'] ?? '') === 'PRI') {
        $field = (string) $column['Field'];
        if (array_key_exists($field, $singleRow)) {
          $singleRowPrimaryWhere[$field] = $singleRow[$field];
        }
      }
    }

    foreach ($configColumns as $column) {
      $field = (string) $column['Field'];
      if (!array_key_exists($field, $singleRow)) {
        continue;
      }
      $formValues[$field] = $singleRow[$field] === null ? '' : (string) $singleRow[$field];
    }
  }

  if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$errors) {
    if ($isKeyValue) {
      $postedValues = $_POST['values'] ?? [];
      if (!is_array($postedValues)) {
        $postedValues = [];
      }

      foreach ($keyValueRows as $row) {
        $key = (string) ($row['clave'] ?? '');
        if ($key === '') {
          continue;
        }

        $rawInput = $postedValues[$key] ?? '';
        $trimmed = trim((string) $rawInput);

        $valorColumn = $columnMap['valor'];
        $allowNull = (($valorColumn['Null'] ?? 'NO') === 'YES');
        $valueToStore = $trimmed;

        if ($trimmed === '' && $allowNull) {
          $valueToStore = null;
        }

        $formValues[$key] = $trimmed;

        $row['__new_valor'] = $valueToStore;
      }

      if (!$errors) {
        $pdo->beginTransaction();

        $updateStmt = $pdo->prepare('UPDATE `config` SET `valor` = :valor WHERE `clave` = :clave');
        foreach ($keyValueRows as $row) {
          $key = (string) ($row['clave'] ?? '');
          if ($key === '') {
            continue;
          }

          $updateStmt->bindValue(':clave', $key, PDO::PARAM_STR);
          if (($row['__new_valor'] ?? null) === null) {
            $updateStmt->bindValue(':valor', null, PDO::PARAM_NULL);
          } else {
            $updateStmt->bindValue(':valor', (string) $row['__new_valor'], PDO::PARAM_STR);
          }
          $updateStmt->execute();
        }

        $pdo->commit();
        header('Location: datos_centro.php?ok=1');
        exit;
      }
    } else {
      if (!$singleRow) {
        $errors[] = 'No hay registro de configuración para actualizar.';
      } else {
        $updates = [];
        $bindings = [];

        foreach ($configColumns as $column) {
          $field = (string) $column['Field'];
          $extra = strtolower((string) ($column['Extra'] ?? ''));
          $isPrimary = (($column['Key'] ?? '') === 'PRI');

          if (str_contains($extra, 'auto_increment') || str_contains($extra, 'generated')) {
            continue;
          }
          if ($isPrimary) {
            continue;
          }

          $rawInput = $_POST['row'][$field] ?? '';
          $trimmed = trim((string) $rawInput);
          $allowNull = (($column['Null'] ?? 'NO') === 'YES');

          if ($trimmed === '') {
            $valueToStore = $allowNull ? null : '';
          } else {
            if (isNumericSqlType((string) ($column['Type'] ?? '')) && !is_numeric($trimmed)) {
              $errors[] = sprintf('El campo "%s" debe ser numérico.', $field);
            }
            $valueToStore = $trimmed;
          }

          $formValues[$field] = $trimmed;
          $updates[] = sprintf('`%s` = :set_%s', $field, $field);
          $bindings['set_' . $field] = $valueToStore;
        }

        if (!$updates) {
          $errors[] = 'No hay columnas editables en la tabla config.';
        }

        if (!$singleRowPrimaryWhere) {
          $errors[] = 'No se pudo identificar la clave primaria para actualizar el registro.';
        }

        if (!$errors) {
          $whereParts = [];
          foreach ($singleRowPrimaryWhere as $pkField => $pkValue) {
            $whereParts[] = sprintf('`%s` = :where_%s', $pkField, $pkField);
            $bindings['where_' . $pkField] = $pkValue;
          }

          $sql = sprintf(
            'UPDATE `config` SET %s WHERE %s',
            implode(', ', $updates),
            implode(' AND ', $whereParts)
          );

          $pdo->beginTransaction();
          $updateStmt = $pdo->prepare($sql);

          foreach ($bindings as $name => $value) {
            if ($value === null) {
              $updateStmt->bindValue(':' . $name, null, PDO::PARAM_NULL);
            } else {
              $updateStmt->bindValue(':' . $name, (string) $value, PDO::PARAM_STR);
            }
          }

          $updateStmt->execute();
          $pdo->commit();

          header('Location: datos_centro.php?ok=1');
          exit;
        }
      }
    }
  }
} catch (Throwable $exception) {
  if ($pdo->inTransaction()) {
    $pdo->rollBack();
  }
  $errors[] = 'Se produjo un error al procesar la configuración: ' . $exception->getMessage();
}

$page_title = 'Datos del centro | Gestor de Alumnos';
$active_page = 'configuracion';
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
          <h1>Datos del centro</h1>
          <p class="subheading">Consulta y modifica la configuración general del centro.</p>
        </div>
        <div class="header-actions">
          <button class="edit-toggle" type="button" id="editToggle">Modo edición</button>
        </div>
      </header>

      <?php if (isset($_GET['ok']) && $_GET['ok'] === '1'): ?>
        <section class="panel">
          <p>Cambios guardados correctamente.</p>
        </section>
      <?php endif; ?>

      <form method="post" class="panel entity-form">
        <?php if ($errors): ?>
          <ul class="form-errors">
            <?php foreach ($errors as $error): ?>
              <li><?php echo h($error); ?></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>

        <?php if ($isKeyValue): ?>
          <section class="entity-section">
            <div class="entity-grid entity-grid--2">
              <?php foreach ($keyValueRows as $row): ?>
                <?php $key = (string) ($row['clave'] ?? ''); ?>
                <?php if ($key === '') { continue; } ?>
                <label>
                  <?php echo h($key); ?>
                  <input type="text" name="values[<?php echo h($key); ?>]" value="<?php echo h($formValues[$key] ?? ''); ?>" readonly>
                </label>
              <?php endforeach; ?>
            </div>
          </section>
        <?php else: ?>
          <section class="entity-section">
            <div class="entity-grid entity-grid--2">
              <?php foreach ($configColumns as $column): ?>
                <?php
                  $field = (string) $column['Field'];
                  $extra = strtolower((string) ($column['Extra'] ?? ''));
                  $isPrimary = (($column['Key'] ?? '') === 'PRI');
                  if ($isPrimary || str_contains($extra, 'auto_increment') || str_contains($extra, 'generated')) {
                    continue;
                  }
                ?>
                <label>
                  <?php echo h($field); ?>
                  <input type="text" name="row[<?php echo h($field); ?>]" value="<?php echo h($formValues[$field] ?? ''); ?>" readonly>
                </label>
              <?php endforeach; ?>
            </div>
          </section>
        <?php endif; ?>

        <div class="form-actions">
          <button type="submit" class="primary-button">Guardar cambios</button>
          <a class="ghost-button" href="configuracion.php">Volver</a>
        </div>
      </form>
    </main>
  </div>
  <script>
    (() => {
      const form = document.querySelector('form.entity-form');
      const editToggle = document.getElementById('editToggle');
      if (!form || !editToggle) {
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
