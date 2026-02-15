<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pdo = db();

function h($value): string {
  return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function normalize_text($value): ?string {
  if ($value === null) {
    return null;
  }

  $trimmed = trim((string) $value);
  return $trimmed === '' ? null : $trimmed;
}

function parse_base_type(string $type): string {
  if (preg_match('/^([a-zA-Z]+)/', $type, $matches)) {
    return strtolower($matches[1]);
  }

  return strtolower($type);
}

function table_columns(PDO $pdo, string $table): array {
  $stmt = $pdo->query('DESCRIBE `' . str_replace('`', '', $table) . '`');
  $columns = [];

  foreach ($stmt->fetchAll() as $column) {
    $columns[] = [
      'name' => (string) $column['Field'],
      'type' => (string) $column['Type'],
      'base_type' => parse_base_type((string) $column['Type']),
      'nullable' => (string) $column['Null'] === 'YES',
      'key' => (string) $column['Key'],
      'default' => $column['Default'],
      'extra' => (string) $column['Extra'],
    ];
  }

  return $columns;
}

function find_column(array $columns, string $name): ?array {
  foreach ($columns as $column) {
    if ($column['name'] === $name) {
      return $column;
    }
  }

  return null;
}

function parse_boolean_value($value): ?int {
  if ($value === null || $value === '') {
    return null;
  }

  if ($value === '1' || $value === 1 || $value === true) {
    return 1;
  }

  if ($value === '0' || $value === 0 || $value === false) {
    return 0;
  }

  return null;
}

function validate_date(?string $value): bool {
  if ($value === null || $value === '') {
    return false;
  }

  $date = DateTime::createFromFormat('Y-m-d', $value);
  return $date && $date->format('Y-m-d') === $value;
}

function normalize_column_value(array $column, $rawValue, array &$errors, string $label): mixed {
  $name = $column['name'];
  $baseType = $column['base_type'];
  $isNullable = $column['nullable'];
  $typeDefinition = strtolower($column['type']);

  if (is_string($rawValue)) {
    $rawValue = trim($rawValue);
  }

  if ($rawValue === '') {
    $rawValue = null;
  }

  $isBoolean = $baseType === 'tinyint' && str_contains($typeDefinition, 'tinyint(1)');

  if ($isBoolean) {
    $boolValue = parse_boolean_value($rawValue);
    if ($boolValue === null) {
      if ($rawValue !== null) {
        $errors[] = sprintf('El campo "%s" debe ser Sí o No.', $label);
      }
      return $isNullable ? null : 0;
    }
    return $boolValue;
  }

  if ($baseType === 'date') {
    if ($rawValue === null) {
      return null;
    }

    if (!validate_date((string) $rawValue)) {
      $errors[] = sprintf('La fecha indicada en "%s" no es válida.', $label);
      return $rawValue;
    }

    return $rawValue;
  }

  if (in_array($baseType, ['int', 'smallint', 'bigint', 'mediumint', 'tinyint'], true)) {
    if ($rawValue === null) {
      return $isNullable ? null : 0;
    }

    if (filter_var($rawValue, FILTER_VALIDATE_INT) === false) {
      $errors[] = sprintf('El campo "%s" debe ser numérico.', $label);
      return $rawValue;
    }

    return (int) $rawValue;
  }

  if (in_array($baseType, ['decimal', 'float', 'double'], true)) {
    if ($rawValue === null) {
      return $isNullable ? null : 0;
    }

    if (!is_numeric((string) $rawValue)) {
      $errors[] = sprintf('El campo "%s" debe ser un número válido.', $label);
      return $rawValue;
    }

    return (string) $rawValue;
  }

  if ($rawValue === null) {
    return null;
  }

  $textValue = (string) $rawValue;
  if ((stripos($name, 'correo') !== false || stripos($name, 'email') !== false) && $textValue !== '' && !filter_var($textValue, FILTER_VALIDATE_EMAIL)) {
    $errors[] = sprintf('El campo "%s" debe contener un correo válido.', $label);
  }

  return $textValue;
}

function build_field_label(string $columnName): string {
  return ucfirst(str_replace('_', ' ', $columnName));
}

function input_type_for_column(array $column): string {
  $baseType = $column['base_type'];
  $name = $column['name'];

  if ($baseType === 'date') {
    return 'date';
  }

  if (in_array($baseType, ['int', 'smallint', 'bigint', 'mediumint', 'tinyint', 'decimal', 'float', 'double'], true)) {
    return 'number';
  }

  if (stripos($name, 'correo') !== false || stripos($name, 'email') !== false) {
    return 'email';
  }

  if ($baseType === 'text') {
    return 'textarea';
  }

  return 'text';
}

function find_primary_key(array $columns): ?string {
  foreach ($columns as $column) {
    if ($column['key'] === 'PRI') {
      return $column['name'];
    }
  }

  return null;
}

function fetch_by_student(PDO $pdo, string $table, int $idAlumno): array {
  $stmt = $pdo->prepare('SELECT * FROM `' . $table . '` WHERE entidad_tipo = :tipo AND id_entidad = :id_entidad ORDER BY 1 ASC');
  $stmt->execute([
    'tipo' => 'alumno',
    'id_entidad' => $idAlumno,
  ]);

  return $stmt->fetchAll();
}

function prepare_related_rows(array $postedRows): array {
  return is_array($postedRows) ? $postedRows : [];
}

$alumnoColumns = table_columns($pdo, 'alumnos');
$telefonoColumns = table_columns($pdo, 'telefonos');
$correoColumns = table_columns($pdo, 'correos');

$telefonoPrimaryKey = find_primary_key($telefonoColumns) ?? 'id_telefono';
$correoPrimaryKey = find_primary_key($correoColumns) ?? 'id_correo';

$idAlumno = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($idAlumno <= 0 && isset($_GET['id_alumno'])) {
  $idAlumno = (int) $_GET['id_alumno'];
}
if ($idAlumno <= 0 && isset($_POST['id_alumno'])) {
  $idAlumno = (int) $_POST['id_alumno'];
}

$errors = [];
$student = null;
$formAlumnoData = [];
$telefonos = [];
$correos = [];

if ($idAlumno > 0) {
  $studentStmt = $pdo->prepare('SELECT * FROM alumnos WHERE id_alumno = :id_alumno');
  $studentStmt->execute(['id_alumno' => $idAlumno]);
  $student = $studentStmt->fetch();
}

if ($student) {
  $formAlumnoData = $student;
  $telefonos = fetch_by_student($pdo, 'telefonos', $idAlumno);
  $correos = fetch_by_student($pdo, 'correos', $idAlumno);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if ($idAlumno <= 0 || !$student) {
    $errors[] = 'No se puede editar el alumno solicitado porque no existe.';
  } else {
    $postedAlumno = is_array($_POST['alumno'] ?? null) ? $_POST['alumno'] : [];
    $newAlumnoData = [];

    foreach ($alumnoColumns as $column) {
      $columnName = $column['name'];
      $label = build_field_label($columnName);

      if ($columnName === 'id_alumno') {
        $newAlumnoData[$columnName] = $idAlumno;
        continue;
      }

      $rawValue = $postedAlumno[$columnName] ?? null;
      $normalizedValue = normalize_column_value($column, $rawValue, $errors, $label);

      if ($normalizedValue === null && !$column['nullable'] && $column['default'] === null && $column['extra'] !== 'auto_increment') {
        $errors[] = sprintf('El campo "%s" es obligatorio.', $label);
      }

      $newAlumnoData[$columnName] = $normalizedValue;
    }

    $formAlumnoData = $newAlumnoData;

    $telefonoRowsPosted = prepare_related_rows($_POST['telefonos'] ?? []);
    $correoRowsPosted = prepare_related_rows($_POST['correos'] ?? []);

    $telefonoUpdates = [];
    $telefonoInserts = [];
    $telefonoDeletes = [];

    $telefonoExistingRows = prepare_related_rows($telefonoRowsPosted['existing'] ?? []);
    $telefonoNewRows = prepare_related_rows($telefonoRowsPosted['new'] ?? []);

    foreach ($telefonoExistingRows as $row) {
      if (!is_array($row)) {
        continue;
      }

      $rowId = isset($row[$telefonoPrimaryKey]) ? (int) $row[$telefonoPrimaryKey] : 0;
      if ($rowId <= 0) {
        continue;
      }

      $markDelete = isset($row['_delete']) && (string) $row['_delete'] === '1';
      if ($markDelete) {
        $telefonoDeletes[] = $rowId;
        continue;
      }

      $normalized = [];
      foreach ($telefonoColumns as $column) {
        $name = $column['name'];
        if ($name === $telefonoPrimaryKey) {
          continue;
        }

        if ($name === 'id_entidad') {
          $normalized[$name] = $idAlumno;
          continue;
        }

        if ($name === 'entidad_tipo') {
          $normalized[$name] = 'alumno';
          continue;
        }

        $normalized[$name] = normalize_column_value($column, $row[$name] ?? null, $errors, 'Teléfono: ' . build_field_label($name));
      }

      if (normalize_text($normalized['telefono'] ?? null) === null) {
        $errors[] = 'Cada teléfono existente debe tener un valor o marcarse para eliminar.';
      }

      $telefonoUpdates[] = [
        'id' => $rowId,
        'data' => $normalized,
      ];
    }

    foreach ($telefonoNewRows as $row) {
      if (!is_array($row)) {
        continue;
      }

      $normalized = [];
      $hasValues = false;

      foreach ($telefonoColumns as $column) {
        $name = $column['name'];
        if ($name === $telefonoPrimaryKey) {
          continue;
        }

        if ($name === 'id_entidad') {
          $normalized[$name] = $idAlumno;
          continue;
        }

        if ($name === 'entidad_tipo') {
          $normalized[$name] = 'alumno';
          continue;
        }

        $value = normalize_column_value($column, $row[$name] ?? null, $errors, 'Nuevo teléfono: ' . build_field_label($name));
        if ($value !== null && $value !== '') {
          $hasValues = true;
        }
        $normalized[$name] = $value;
      }

      if (!$hasValues) {
        continue;
      }

      if (normalize_text($normalized['telefono'] ?? null) === null) {
        $errors[] = 'Los nuevos teléfonos deben incluir número para guardarse.';
        continue;
      }

      $telefonoInserts[] = $normalized;
    }

    $correoUpdates = [];
    $correoInserts = [];
    $correoDeletes = [];

    $correoExistingRows = prepare_related_rows($correoRowsPosted['existing'] ?? []);
    $correoNewRows = prepare_related_rows($correoRowsPosted['new'] ?? []);

    foreach ($correoExistingRows as $row) {
      if (!is_array($row)) {
        continue;
      }

      $rowId = isset($row[$correoPrimaryKey]) ? (int) $row[$correoPrimaryKey] : 0;
      if ($rowId <= 0) {
        continue;
      }

      $markDelete = isset($row['_delete']) && (string) $row['_delete'] === '1';
      if ($markDelete) {
        $correoDeletes[] = $rowId;
        continue;
      }

      $normalized = [];
      foreach ($correoColumns as $column) {
        $name = $column['name'];
        if ($name === $correoPrimaryKey) {
          continue;
        }

        if ($name === 'id_entidad') {
          $normalized[$name] = $idAlumno;
          continue;
        }

        if ($name === 'entidad_tipo') {
          $normalized[$name] = 'alumno';
          continue;
        }

        $normalized[$name] = normalize_column_value($column, $row[$name] ?? null, $errors, 'Correo: ' . build_field_label($name));
      }

      if (normalize_text($normalized['direccion_correo'] ?? null) === null) {
        $errors[] = 'Cada correo existente debe tener una dirección o marcarse para eliminar.';
      }

      $correoUpdates[] = [
        'id' => $rowId,
        'data' => $normalized,
      ];
    }

    foreach ($correoNewRows as $row) {
      if (!is_array($row)) {
        continue;
      }

      $normalized = [];
      $hasValues = false;

      foreach ($correoColumns as $column) {
        $name = $column['name'];
        if ($name === $correoPrimaryKey) {
          continue;
        }

        if ($name === 'id_entidad') {
          $normalized[$name] = $idAlumno;
          continue;
        }

        if ($name === 'entidad_tipo') {
          $normalized[$name] = 'alumno';
          continue;
        }

        $value = normalize_column_value($column, $row[$name] ?? null, $errors, 'Nuevo correo: ' . build_field_label($name));
        if ($value !== null && $value !== '') {
          $hasValues = true;
        }
        $normalized[$name] = $value;
      }

      if (!$hasValues) {
        continue;
      }

      if (normalize_text($normalized['direccion_correo'] ?? null) === null) {
        $errors[] = 'Los nuevos correos deben incluir la dirección para guardarse.';
        continue;
      }

      if (!filter_var((string) $normalized['direccion_correo'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Los nuevos correos deben tener un formato válido.';
      }

      $correoInserts[] = $normalized;
    }

    $telefonos = array_values(array_filter(array_merge($telefonoUpdates, array_map(static fn(array $row): array => ['id' => 0, 'data' => $row], $telefonoInserts))));
    $correos = array_values(array_filter(array_merge($correoUpdates, array_map(static fn(array $row): array => ['id' => 0, 'data' => $row], $correoInserts))));

    if (!$errors) {
      try {
        $pdo->beginTransaction();

        $alumnoUpdateColumns = array_values(array_filter(array_column($alumnoColumns, 'name'), static fn(string $name): bool => $name !== 'id_alumno'));
        $setAlumno = implode(', ', array_map(static fn(string $name): string => '`' . $name . '` = :' . $name, $alumnoUpdateColumns));
        $sqlAlumnoUpdate = 'UPDATE alumnos SET ' . $setAlumno . ' WHERE id_alumno = :id_alumno';
        $stmtAlumnoUpdate = $pdo->prepare($sqlAlumnoUpdate);

        $paramsAlumno = ['id_alumno' => $idAlumno];
        foreach ($alumnoUpdateColumns as $columnName) {
          $paramsAlumno[$columnName] = $newAlumnoData[$columnName] ?? null;
        }
        $stmtAlumnoUpdate->execute($paramsAlumno);

        if ($telefonoDeletes) {
          $placeholders = implode(', ', array_fill(0, count($telefonoDeletes), '?'));
          $sqlDeletePhone = 'DELETE FROM telefonos WHERE `' . $telefonoPrimaryKey . '` IN (' . $placeholders . ') AND entidad_tipo = ? AND id_entidad = ?';
          $params = array_merge($telefonoDeletes, ['alumno', $idAlumno]);
          $pdo->prepare($sqlDeletePhone)->execute($params);
        }

        foreach ($telefonoUpdates as $item) {
          $data = $item['data'];
          $setParts = implode(', ', array_map(static fn(string $name): string => '`' . $name . '` = :' . $name, array_keys($data)));
          $sql = 'UPDATE telefonos SET ' . $setParts . ' WHERE `' . $telefonoPrimaryKey . '` = :row_id AND entidad_tipo = :scope_tipo AND id_entidad = :scope_id';
          $stmt = $pdo->prepare($sql);
          $stmt->execute(array_merge($data, [
            'row_id' => $item['id'],
            'scope_tipo' => 'alumno',
            'scope_id' => $idAlumno,
          ]));
        }

        foreach ($telefonoInserts as $data) {
          $columns = array_keys($data);
          $sql = 'INSERT INTO telefonos (`' . implode('`, `', $columns) . '`) VALUES (:' . implode(', :', $columns) . ')';
          $pdo->prepare($sql)->execute($data);
        }

        if ($correoDeletes) {
          $placeholders = implode(', ', array_fill(0, count($correoDeletes), '?'));
          $sqlDeleteEmail = 'DELETE FROM correos WHERE `' . $correoPrimaryKey . '` IN (' . $placeholders . ') AND entidad_tipo = ? AND id_entidad = ?';
          $params = array_merge($correoDeletes, ['alumno', $idAlumno]);
          $pdo->prepare($sqlDeleteEmail)->execute($params);
        }

        foreach ($correoUpdates as $item) {
          $data = $item['data'];
          $setParts = implode(', ', array_map(static fn(string $name): string => '`' . $name . '` = :' . $name, array_keys($data)));
          $sql = 'UPDATE correos SET ' . $setParts . ' WHERE `' . $correoPrimaryKey . '` = :row_id AND entidad_tipo = :scope_tipo AND id_entidad = :scope_id';
          $stmt = $pdo->prepare($sql);
          $stmt->execute(array_merge($data, [
            'row_id' => $item['id'],
            'scope_tipo' => 'alumno',
            'scope_id' => $idAlumno,
          ]));
        }

        foreach ($correoInserts as $data) {
          $columns = array_keys($data);
          $sql = 'INSERT INTO correos (`' . implode('`, `', $columns) . '`) VALUES (:' . implode(', :', $columns) . ')';
          $pdo->prepare($sql)->execute($data);
        }

        $pdo->commit();
        header('Location: alumno_detalle.php?id_alumno=' . $idAlumno . '&updated=1');
        exit;
      } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
          $pdo->rollBack();
        }
        $errors[] = 'No se ha podido guardar la información del alumno. Inténtalo de nuevo.';
      }
    }

    if ($telefonoRowsPosted) {
      $telefonos = [];
      foreach (prepare_related_rows($telefonoRowsPosted['existing'] ?? []) as $row) {
        if (!is_array($row)) {
          continue;
        }

        if (isset($row['_delete']) && (string) $row['_delete'] === '1') {
          continue;
        }

        $telefonos[] = $row;
      }
      foreach (prepare_related_rows($telefonoRowsPosted['new'] ?? []) as $row) {
        if (!is_array($row)) {
          continue;
        }
        $telefonos[] = $row;
      }
    }

    if ($correoRowsPosted) {
      $correos = [];
      foreach (prepare_related_rows($correoRowsPosted['existing'] ?? []) as $row) {
        if (!is_array($row)) {
          continue;
        }

        if (isset($row['_delete']) && (string) $row['_delete'] === '1') {
          continue;
        }

        $correos[] = $row;
      }
      foreach (prepare_related_rows($correoRowsPosted['new'] ?? []) as $row) {
        if (!is_array($row)) {
          continue;
        }
        $correos[] = $row;
      }
    }
  }
}

$page_title = 'Editar alumno | Gestor de Alumnos';
$active_page = 'alumnos';
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
          <p class="eyebrow">Alumnos</p>
          <h1>Editar alumno</h1>
          <p class="subheading">Actualiza todos los datos del alumno y sus medios de contacto.</p>
        </div>
        <div class="header-actions">
          <a class="ghost-button" href="alumno_detalle.php?id_alumno=<?php echo (int) $idAlumno; ?>">Cancelar y volver</a>
        </div>
      </header>

      <?php if ($idAlumno <= 0): ?>
        <section class="panel">
          <div class="panel-header">
            <h3>ID no válido</h3>
            <p>Debes indicar un identificador de alumno válido para editar.</p>
          </div>
          <div class="form-actions">
            <a class="ghost-button" href="alumnos.php">Volver al listado</a>
          </div>
        </section>
      <?php elseif (!$student): ?>
        <section class="panel">
          <div class="panel-header">
            <h3>Alumno no encontrado</h3>
            <p>No existe ningún alumno con el id indicado.</p>
          </div>
          <div class="form-actions">
            <a class="ghost-button" href="alumnos.php">Volver al listado</a>
          </div>
        </section>
      <?php else: ?>
        <form method="post" class="panel entity-form" id="alumnoEditarForm">
          <input type="hidden" name="id_alumno" value="<?php echo (int) $idAlumno; ?>">

          <?php if ($errors): ?>
            <section class="entity-section">
              <div class="panel-header">
                <h3>Revisa los datos introducidos</h3>
              </div>
              <ul class="form-errors">
                <?php foreach ($errors as $error): ?>
                  <li><?php echo h($error); ?></li>
                <?php endforeach; ?>
              </ul>
            </section>
          <?php endif; ?>

          <section class="entity-section">
            <div class="panel-header">
              <h3>Datos del alumno</h3>
              <p>Se incluyen todas las columnas de la tabla <strong>alumnos</strong>.</p>
            </div>
            <div class="entity-grid">
              <?php foreach ($alumnoColumns as $column): ?>
                <?php
                  $columnName = $column['name'];
                  $fieldId = 'alumno_' . $columnName;
                  $label = build_field_label($columnName);
                  $value = $formAlumnoData[$columnName] ?? null;
                  $inputType = input_type_for_column($column);
                  $isBoolean = $column['base_type'] === 'tinyint' && str_contains(strtolower($column['type']), 'tinyint(1)');
                  $isReadOnly = $columnName === 'id_alumno';
                ?>
                <label for="<?php echo h($fieldId); ?>">
                  <?php echo h($label); ?><?php echo !$column['nullable'] && !$isReadOnly ? ' *' : ''; ?>
                  <?php if ($isBoolean): ?>
                    <select id="<?php echo h($fieldId); ?>" name="alumno[<?php echo h($columnName); ?>]" <?php echo $isReadOnly ? 'disabled' : ''; ?>>
                      <option value="">Seleccionar</option>
                      <option value="1" <?php echo (string) $value === '1' ? 'selected' : ''; ?>>Sí</option>
                      <option value="0" <?php echo (string) $value === '0' ? 'selected' : ''; ?>>No</option>
                    </select>
                  <?php elseif ($inputType === 'textarea'): ?>
                    <textarea id="<?php echo h($fieldId); ?>" name="alumno[<?php echo h($columnName); ?>]" <?php echo $isReadOnly ? 'readonly' : ''; ?>><?php echo h((string) ($value ?? '')); ?></textarea>
                  <?php else: ?>
                    <input
                      id="<?php echo h($fieldId); ?>"
                      type="<?php echo h($inputType); ?>"
                      name="alumno[<?php echo h($columnName); ?>]"
                      value="<?php echo h((string) ($value ?? '')); ?>"
                      <?php echo $isReadOnly ? 'readonly' : ''; ?>
                    >
                  <?php endif; ?>
                </label>
              <?php endforeach; ?>
            </div>
          </section>

          <section class="entity-section">
            <div class="entity-section-header">
              <div class="panel-header">
                <h3>Teléfonos</h3>
                <p>Edita, elimina o añade teléfonos para el alumno.</p>
              </div>
              <button type="button" class="edit-toggle" data-add-item="#telefonosList" data-template="#telefonoTemplate">Añadir teléfono</button>
            </div>
            <div class="entity-stack" id="telefonosList">
              <?php foreach ($telefonos as $index => $telefono): ?>
                <article class="entity-repeatable-item">
                  <input type="hidden" name="telefonos[existing][<?php echo (int) $index; ?>][<?php echo h($telefonoPrimaryKey); ?>]" value="<?php echo (int) ($telefono[$telefonoPrimaryKey] ?? 0); ?>">
                  <div class="entity-grid">
                    <?php foreach ($telefonoColumns as $column): ?>
                      <?php
                        $name = $column['name'];
                        if ($name === $telefonoPrimaryKey || $name === 'id_entidad' || $name === 'entidad_tipo') {
                          continue;
                        }
                        $fieldId = 'tel_existing_' . $index . '_' . $name;
                        $inputType = input_type_for_column($column);
                        $value = $telefono[$name] ?? '';
                      ?>
                      <label for="<?php echo h($fieldId); ?>">
                        <?php echo h(build_field_label($name)); ?>
                        <input id="<?php echo h($fieldId); ?>" type="<?php echo h($inputType); ?>" name="telefonos[existing][<?php echo (int) $index; ?>][<?php echo h($name); ?>]" value="<?php echo h((string) $value); ?>">
                      </label>
                    <?php endforeach; ?>
                    <label>
                      Eliminar
                      <select name="telefonos[existing][<?php echo (int) $index; ?>][_delete]">
                        <option value="0" selected>No</option>
                        <option value="1">Sí</option>
                      </select>
                    </label>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>

            <div class="entity-stack" id="telefonosNuevosList">
              <article class="entity-repeatable-item">
                <div class="entity-grid">
                  <?php foreach ($telefonoColumns as $column): ?>
                    <?php
                      $name = $column['name'];
                      if ($name === $telefonoPrimaryKey || $name === 'id_entidad' || $name === 'entidad_tipo') {
                        continue;
                      }
                      $fieldId = 'tel_new_0_' . $name;
                      $inputType = input_type_for_column($column);
                    ?>
                    <label for="<?php echo h($fieldId); ?>">
                      <?php echo h(build_field_label($name)); ?>
                      <input id="<?php echo h($fieldId); ?>" type="<?php echo h($inputType); ?>" name="telefonos[new][0][<?php echo h($name); ?>]" value="">
                    </label>
                  <?php endforeach; ?>
                </div>
              </article>
            </div>
          </section>

          <section class="entity-section">
            <div class="entity-section-header">
              <div class="panel-header">
                <h3>Correos</h3>
                <p>Edita, elimina o añade correos para el alumno.</p>
              </div>
              <button type="button" class="edit-toggle" data-add-item="#correosList" data-template="#correoTemplate">Añadir correo</button>
            </div>

            <div class="entity-stack" id="correosList">
              <?php foreach ($correos as $index => $correo): ?>
                <article class="entity-repeatable-item">
                  <input type="hidden" name="correos[existing][<?php echo (int) $index; ?>][<?php echo h($correoPrimaryKey); ?>]" value="<?php echo (int) ($correo[$correoPrimaryKey] ?? 0); ?>">
                  <div class="entity-grid">
                    <?php foreach ($correoColumns as $column): ?>
                      <?php
                        $name = $column['name'];
                        if ($name === $correoPrimaryKey || $name === 'id_entidad' || $name === 'entidad_tipo') {
                          continue;
                        }
                        $fieldId = 'correo_existing_' . $index . '_' . $name;
                        $inputType = input_type_for_column($column);
                        $value = $correo[$name] ?? '';
                      ?>
                      <label for="<?php echo h($fieldId); ?>">
                        <?php echo h(build_field_label($name)); ?>
                        <input id="<?php echo h($fieldId); ?>" type="<?php echo h($inputType); ?>" name="correos[existing][<?php echo (int) $index; ?>][<?php echo h($name); ?>]" value="<?php echo h((string) $value); ?>">
                      </label>
                    <?php endforeach; ?>
                    <label>
                      Eliminar
                      <select name="correos[existing][<?php echo (int) $index; ?>][_delete]">
                        <option value="0" selected>No</option>
                        <option value="1">Sí</option>
                      </select>
                    </label>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>

            <div class="entity-stack" id="correosNuevosList">
              <article class="entity-repeatable-item">
                <div class="entity-grid">
                  <?php foreach ($correoColumns as $column): ?>
                    <?php
                      $name = $column['name'];
                      if ($name === $correoPrimaryKey || $name === 'id_entidad' || $name === 'entidad_tipo') {
                        continue;
                      }
                      $fieldId = 'correo_new_0_' . $name;
                      $inputType = input_type_for_column($column);
                    ?>
                    <label for="<?php echo h($fieldId); ?>">
                      <?php echo h(build_field_label($name)); ?>
                      <input id="<?php echo h($fieldId); ?>" type="<?php echo h($inputType); ?>" name="correos[new][0][<?php echo h($name); ?>]" value="">
                    </label>
                  <?php endforeach; ?>
                </div>
              </article>
            </div>
          </section>

          <div class="form-actions">
            <button type="submit" class="primary-button">Guardar cambios</button>
            <a class="ghost-button" href="alumno_detalle.php?id_alumno=<?php echo (int) $idAlumno; ?>">Cancelar</a>
          </div>
        </form>
      <?php endif; ?>
    </main>
  </div>

  <template id="telefonoTemplate">
    <article class="entity-repeatable-item">
      <div class="entity-grid">
        <?php foreach ($telefonoColumns as $column): ?>
          <?php
            $name = $column['name'];
            if ($name === $telefonoPrimaryKey || $name === 'id_entidad' || $name === 'entidad_tipo') {
              continue;
            }
            $inputType = input_type_for_column($column);
          ?>
          <label>
            <?php echo h(build_field_label($name)); ?>
            <input type="<?php echo h($inputType); ?>" data-name="<?php echo h($name); ?>" value="">
          </label>
        <?php endforeach; ?>
      </div>
    </article>
  </template>

  <template id="correoTemplate">
    <article class="entity-repeatable-item">
      <div class="entity-grid">
        <?php foreach ($correoColumns as $column): ?>
          <?php
            $name = $column['name'];
            if ($name === $correoPrimaryKey || $name === 'id_entidad' || $name === 'entidad_tipo') {
              continue;
            }
            $inputType = input_type_for_column($column);
          ?>
          <label>
            <?php echo h(build_field_label($name)); ?>
            <input type="<?php echo h($inputType); ?>" data-name="<?php echo h($name); ?>" value="">
          </label>
        <?php endforeach; ?>
      </div>
    </article>
  </template>

  <script>
    (function () {
      const buildName = (group, index, field) => `${group}[new][${index}][${field}]`;

      const addButtons = document.querySelectorAll('[data-add-item][data-template]');
      addButtons.forEach((button) => {
        button.addEventListener('click', () => {
          const templateSelector = button.getAttribute('data-template');
          const template = document.querySelector(templateSelector);
          if (!template) {
            return;
          }

          const target = templateSelector === '#telefonoTemplate'
            ? document.querySelector('#telefonosNuevosList')
            : document.querySelector('#correosNuevosList');

          if (!target) {
            return;
          }

          const groupName = templateSelector === '#telefonoTemplate' ? 'telefonos' : 'correos';
          const index = target.querySelectorAll('.entity-repeatable-item').length;
          const fragment = template.content.cloneNode(true);

          fragment.querySelectorAll('[data-name]').forEach((input) => {
            const fieldName = input.getAttribute('data-name');
            input.setAttribute('name', buildName(groupName, index, fieldName));
          });

          target.appendChild(fragment);
        });
      });
    })();
  </script>
</body>
</html>
