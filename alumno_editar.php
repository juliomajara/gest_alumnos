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

function normalize_localidad_lookup_value(string $value): string {
  $normalized = trim(mb_strtolower($value, 'UTF-8'));
  if ($normalized === '') {
    return '';
  }

  $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized);
  if ($ascii !== false) {
    $normalized = $ascii;
  }

  $normalized = preg_replace('/[^a-z0-9]+/u', ' ', $normalized) ?? $normalized;
  return trim(preg_replace('/\s+/u', ' ', $normalized) ?? $normalized);
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

function find_first_existing_column(array $columns, array $candidates): ?string {
  foreach ($candidates as $candidate) {
    if (find_column($columns, $candidate) !== null) {
      return $candidate;
    }
  }

  return null;
}

if (isset($_GET['action'])) {
  header('Content-Type: application/json; charset=utf-8');

  $action = (string) $_GET['action'];

  try {
    if ($action === 'provincia_por_cp_prefix') {
      $prefixRaw = trim((string) ($_GET['prefix'] ?? ''));
      $prefix = preg_replace('/\D+/', '', $prefixRaw) ?? '';

      if (strlen($prefix) < 2) {
        echo json_encode(['ok' => true, 'id_provincia' => null], JSON_UNESCAPED_UNICODE);
        exit;
      }

      $prefix = substr($prefix, 0, 2);
      $stmt = $pdo->prepare('SELECT id_provincia, nombre FROM provincias WHERE cp_prefijo = :prefix LIMIT 1');
      $stmt->execute(['prefix' => (int) $prefix]);
      $row = $stmt->fetch();

      echo json_encode([
        'ok' => true,
        'id_provincia' => $row ? (int) $row['id_provincia'] : null,
        'nombre' => $row['nombre'] ?? null,
      ], JSON_UNESCAPED_UNICODE);
      exit;
    }

    if ($action === 'localidad_get_or_create') {
      $idProvinciaRaw = $_POST['id_provincia'] ?? $_GET['id_provincia'] ?? null;
      $cpRaw = (string) ($_POST['cp'] ?? $_GET['cp'] ?? '');
      $localidadRaw = (string) ($_POST['nombre_localidad'] ?? $_GET['nombre_localidad'] ?? '');

      $idProvincia = is_numeric((string) $idProvinciaRaw) ? (int) $idProvinciaRaw : 0;
      $cp = preg_replace('/[\s-]+/', '', trim($cpRaw)) ?? '';
      $nombreLocalidad = trim($localidadRaw);

      if ($idProvincia <= 0 || $nombreLocalidad === '') {
        echo json_encode(['ok' => true, 'id_localidad' => null], JSON_UNESCAPED_UNICODE);
        exit;
      }

      $nombreLocalidadLookup = normalize_localidad_lookup_value($nombreLocalidad);
      if ($nombreLocalidadLookup === '') {
        echo json_encode(['ok' => true, 'id_localidad' => null], JSON_UNESCAPED_UNICODE);
        exit;
      }

      $stmt = $pdo->prepare('SELECT id_localidad, nombre FROM localidades WHERE id_provincia = :id_provincia ORDER BY nombre');
      $stmt->execute(['id_provincia' => $idProvincia]);

      foreach ($stmt->fetchAll() as $row) {
        $dbLookup = normalize_localidad_lookup_value((string) ($row['nombre'] ?? ''));
        if ($dbLookup !== '' && $dbLookup === $nombreLocalidadLookup) {
          echo json_encode([
            'ok' => true,
            'id_localidad' => (int) $row['id_localidad'],
            'nombre' => (string) $row['nombre'],
          ], JSON_UNESCAPED_UNICODE);
          exit;
        }
      }

      $insert = $pdo->prepare('INSERT INTO localidades (id_provincia, nombre, cp) VALUES (:id_provincia, :nombre, :cp)');
      $insert->execute([
        'id_provincia' => $idProvincia,
        'nombre' => $nombreLocalidad,
        'cp' => $cp !== '' ? $cp : null,
      ]);

      echo json_encode([
        'ok' => true,
        'id_localidad' => (int) $pdo->lastInsertId(),
        'nombre' => $nombreLocalidad,
      ], JSON_UNESCAPED_UNICODE);
      exit;
    }

    echo json_encode(['ok' => false, 'error' => 'Action no soportada'], JSON_UNESCAPED_UNICODE);
    exit;
  } catch (Throwable $exception) {
    echo json_encode(['ok' => false, 'error' => 'Error interno'], JSON_UNESCAPED_UNICODE);
    exit;
  }
}

$alumnoColumns = table_columns($pdo, 'alumnos');
$telefonoColumns = table_columns($pdo, 'telefonos');
$correoColumns = table_columns($pdo, 'correos');

$telefonoPrimaryKey = find_primary_key($telefonoColumns) ?? 'id_telefono';
$correoPrimaryKey = find_primary_key($correoColumns) ?? 'id_correo';

$telefonoTypeField = find_first_existing_column($telefonoColumns, ['tipo', 'etiqueta']);
$correoTypeField = find_first_existing_column($correoColumns, ['tipo', 'etiqueta']);

$provinciaOptions = $pdo->query('SELECT id_provincia, nombre FROM provincias ORDER BY nombre')->fetchAll();
$madridProvinciaId = null;
foreach ($provinciaOptions as $provincia) {
  if (normalize_localidad_lookup_value((string) ($provincia['nombre'] ?? '')) === 'madrid') {
    $madridProvinciaId = (int) $provincia['id_provincia'];
    break;
  }
}

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
  $studentStmt = $pdo->prepare(
    'SELECT a.*, p.nombre AS provincia_nombre, l.nombre AS localidad_nombre
     FROM alumnos a
     LEFT JOIN provincias p ON p.id_provincia = a.id_provincia
     LEFT JOIN localidades l ON l.id_localidad = a.id_localidad
     WHERE a.id_alumno = :id_alumno'
  );
  $studentStmt->execute(['id_alumno' => $idAlumno]);
  $student = $studentStmt->fetch();
}

if ($student) {
  $formAlumnoData = $student;
  if (!isset($formAlumnoData['localidad_nombre'])) {
    $formAlumnoData['localidad_nombre'] = $student['localidad_nombre'] ?? '';
  }
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

      if ($columnName === 'id_localidad') {
        $newAlumnoData[$columnName] = null;
        continue;
      }

      $rawValue = $postedAlumno[$columnName] ?? null;
      $normalizedValue = normalize_column_value($column, $rawValue, $errors, $label);

      if ($normalizedValue === null && !$column['nullable'] && $column['default'] === null && $column['extra'] !== 'auto_increment') {
        $errors[] = sprintf('El campo "%s" es obligatorio.', $label);
      }

      $newAlumnoData[$columnName] = $normalizedValue;
    }

    $idProvincia = isset($newAlumnoData['id_provincia']) ? (int) $newAlumnoData['id_provincia'] : 0;
    if ($idProvincia <= 0) {
      $idProvincia = $madridProvinciaId ?? 0;
      if ($idProvincia > 0) {
        $newAlumnoData['id_provincia'] = $idProvincia;
      }
    }

    $localidadNombre = normalize_text($_POST['alumno_localidad_nombre'] ?? null);
    $formAlumnoData['localidad_nombre'] = $localidadNombre ?? '';

    $formAlumnoData = $newAlumnoData;
    $formAlumnoData['localidad_nombre'] = $localidadNombre ?? '';

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

        if ($localidadNombre !== null && $idProvincia > 0) {
          $stmtLocalidad = $pdo->prepare('SELECT id_localidad, nombre FROM localidades WHERE id_provincia = :id_provincia ORDER BY nombre');
          $stmtLocalidad->execute(['id_provincia' => $idProvincia]);

          $localidadLookup = normalize_localidad_lookup_value($localidadNombre);
          foreach ($stmtLocalidad->fetchAll() as $localidadRow) {
            $dbLookup = normalize_localidad_lookup_value((string) ($localidadRow['nombre'] ?? ''));
            if ($dbLookup !== '' && $dbLookup === $localidadLookup) {
              $newAlumnoData['id_localidad'] = (int) $localidadRow['id_localidad'];
              $formAlumnoData['localidad_nombre'] = (string) $localidadRow['nombre'];
              break;
            }
          }

          if (!isset($newAlumnoData['id_localidad']) || $newAlumnoData['id_localidad'] === null) {
            $insertLocalidad = $pdo->prepare('INSERT INTO localidades (id_provincia, nombre, cp) VALUES (:id_provincia, :nombre, :cp)');
            $insertLocalidad->execute([
              'id_provincia' => $idProvincia,
              'nombre' => $localidadNombre,
              'cp' => normalize_text($newAlumnoData['cp'] ?? null),
            ]);
            $newAlumnoData['id_localidad'] = (int) $pdo->lastInsertId();
          }
        }

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

$alumnoFieldLabels = [
  'nia' => 'NIA',
  'dni' => 'DNI',
  'seg_soc' => 'Seguridad Social',
  'apellido1' => 'Apellido 1',
  'apellido2' => 'Apellido 2',
  'nombre' => 'Nombre',
  'fecha_nacimiento' => 'Fecha nacimiento',
  'nombre_tutor1' => 'Nombre tutor 1',
  'telefono_tutor1' => 'Teléfono tutor 1',
  'correo_tutor1' => 'Correo tutor 1',
  'nombre_tutor2' => 'Nombre tutor 2',
  'telefono_tutor2' => 'Teléfono tutor 2',
  'correo_tutor2' => 'Correo tutor 2',
  'repite_curso' => 'Repite curso',
  'horas_ffe_aprobadas' => 'Horas FFE aprobadas',
  'faltas_10_dia' => 'Faltas 10% día',
  'faltas_10_cantidad' => 'Faltas 10% cantidad',
  'faltas_15_dia' => 'Faltas 15% día',
  'faltas_15_cantidad' => 'Faltas 15% cantidad',
  'comentarios' => 'Comentarios',
];

$blockAlumnoFields = ['nia', 'dni', 'seg_soc', 'apellido1', 'apellido2', 'nombre', 'fecha_nacimiento'];
$blockTutorFields = ['nombre_tutor1', 'telefono_tutor1', 'correo_tutor1', 'nombre_tutor2', 'telefono_tutor2', 'correo_tutor2'];
$blockAcademicoFields = ['repite_curso', 'horas_ffe_aprobadas', 'faltas_10_dia', 'faltas_10_cantidad', 'faltas_15_dia', 'faltas_15_cantidad', 'comentarios'];

$alumnoColumnsByName = [];
foreach ($alumnoColumns as $column) {
  $alumnoColumnsByName[$column['name']] = $column;
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
        <form method="post" class="panel entity-form empresa-form" id="alumnoEditarForm">
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

          <div class="empresa-form-grid">
            <?php
              $cpValue = $formAlumnoData['cp'] ?? '';
              $provinciaSeleccionada = isset($formAlumnoData['id_provincia']) && (int) $formAlumnoData['id_provincia'] > 0
                ? (int) $formAlumnoData['id_provincia']
                : ($madridProvinciaId ?? 0);
              $provinciaNombre = '';
              foreach ($provinciaOptions as $provinciaOption) {
                if ((int) $provinciaOption['id_provincia'] === (int) $provinciaSeleccionada) {
                  $provinciaNombre = (string) ($provinciaOption['nombre'] ?? '');
                  break;
                }
              }
              if ($provinciaNombre === '' && isset($formAlumnoData['provincia_nombre'])) {
                $provinciaNombre = (string) $formAlumnoData['provincia_nombre'];
              }
              $localidadValue = $formAlumnoData['localidad_nombre'] ?? ($student['localidad_nombre'] ?? '');
            ?>

            <section class="panel empresa-block empresa-block-main">
              <div class="panel-header">
                <h3>Datos del alumno</h3>
              </div>
              <div class="entity-grid empresa-datos-grid">
                <?php foreach ($blockAlumnoFields as $columnName): ?>
                  <?php if (!isset($alumnoColumnsByName[$columnName])) { continue; } ?>
                  <?php
                    $column = $alumnoColumnsByName[$columnName];
                    $fieldId = 'alumno_' . $columnName;
                    $label = $alumnoFieldLabels[$columnName] ?? build_field_label($columnName);
                    $value = $formAlumnoData[$columnName] ?? null;
                    $inputType = input_type_for_column($column);
                    $isBoolean = $column['base_type'] === 'tinyint' && str_contains(strtolower($column['type']), 'tinyint(1)');
                  ?>
                  <label for="<?php echo h($fieldId); ?>">
                    <?php echo h($label); ?><?php echo !$column['nullable'] ? ' *' : ''; ?>
                    <?php if ($isBoolean): ?>
                      <select id="<?php echo h($fieldId); ?>" name="alumno[<?php echo h($columnName); ?>]">
                        <option value="">Seleccionar</option>
                        <option value="1" <?php echo (string) $value === '1' ? 'selected' : ''; ?>>Sí</option>
                        <option value="0" <?php echo (string) $value === '0' ? 'selected' : ''; ?>>No</option>
                      </select>
                    <?php elseif ($inputType === 'textarea'): ?>
                      <textarea id="<?php echo h($fieldId); ?>" name="alumno[<?php echo h($columnName); ?>]"><?php echo h((string) ($value ?? '')); ?></textarea>
                    <?php else: ?>
                      <input id="<?php echo h($fieldId); ?>" type="<?php echo h($inputType); ?>" name="alumno[<?php echo h($columnName); ?>]" value="<?php echo h((string) ($value ?? '')); ?>">
                    <?php endif; ?>
                  </label>
                <?php endforeach; ?>
              </div>
            </section>

            <section class="panel empresa-block empresa-block-direcciones">
              <div class="panel-header">
                <h3>Datos tutores</h3>
              </div>
              <div class="entity-grid empresa-datos-grid">
                <?php foreach ($blockTutorFields as $columnName): ?>
                  <?php if (!isset($alumnoColumnsByName[$columnName])) { continue; } ?>
                  <?php
                    $column = $alumnoColumnsByName[$columnName];
                    $fieldId = 'alumno_' . $columnName;
                    $label = $alumnoFieldLabels[$columnName] ?? build_field_label($columnName);
                    $value = $formAlumnoData[$columnName] ?? null;
                    $inputType = input_type_for_column($column);
                  ?>
                  <label for="<?php echo h($fieldId); ?>">
                    <?php echo h($label); ?><?php echo !$column['nullable'] ? ' *' : ''; ?>
                    <input id="<?php echo h($fieldId); ?>" type="<?php echo h($inputType); ?>" name="alumno[<?php echo h($columnName); ?>]" value="<?php echo h((string) ($value ?? '')); ?>">
                  </label>
                <?php endforeach; ?>
              </div>
            </section>

            <section class="panel empresa-block empresa-block-contactos">
              <div class="panel-header">
                <h3>Datos académicos</h3>
              </div>
              <div class="entity-grid empresa-datos-grid">
                <?php foreach ($blockAcademicoFields as $columnName): ?>
                  <?php if (!isset($alumnoColumnsByName[$columnName])) { continue; } ?>
                  <?php
                    $column = $alumnoColumnsByName[$columnName];
                    $fieldId = 'alumno_' . $columnName;
                    $label = $alumnoFieldLabels[$columnName] ?? build_field_label($columnName);
                    $value = $formAlumnoData[$columnName] ?? null;
                    $inputType = input_type_for_column($column);
                    $isBoolean = $column['base_type'] === 'tinyint' && str_contains(strtolower($column['type']), 'tinyint(1)');
                  ?>
                  <label for="<?php echo h($fieldId); ?>">
                    <?php echo h($label); ?><?php echo !$column['nullable'] ? ' *' : ''; ?>
                    <?php if ($isBoolean): ?>
                      <select id="<?php echo h($fieldId); ?>" name="alumno[<?php echo h($columnName); ?>]">
                        <option value="">Seleccionar</option>
                        <option value="1" <?php echo (string) $value === '1' ? 'selected' : ''; ?>>Sí</option>
                        <option value="0" <?php echo (string) $value === '0' ? 'selected' : ''; ?>>No</option>
                      </select>
                    <?php elseif ($inputType === 'textarea'): ?>
                      <textarea id="<?php echo h($fieldId); ?>" name="alumno[<?php echo h($columnName); ?>]"><?php echo h((string) ($value ?? '')); ?></textarea>
                    <?php else: ?>
                      <input id="<?php echo h($fieldId); ?>" type="<?php echo h($inputType); ?>" name="alumno[<?php echo h($columnName); ?>]" value="<?php echo h((string) ($value ?? '')); ?>">
                    <?php endif; ?>
                  </label>
                <?php endforeach; ?>
                <label for="alumno_cp_lookup">
                  Código postal
                  <input id="alumno_cp_lookup" type="text" name="alumno[cp]" value="<?php echo h((string) $cpValue); ?>">
                </label>
                <label for="alumno_provincia_lookup">
                  Provincia
                  <input id="alumno_provincia_lookup" type="text" name="alumno_provincia_nombre" value="<?php echo h((string) $provinciaNombre); ?>">
                </label>
                <input id="alumno_provincia_lookup_id" type="hidden" name="alumno[id_provincia]" value="<?php echo (int) $provinciaSeleccionada; ?>">
                <label for="alumno_localidad_lookup">
                  Localidad
                  <input id="alumno_localidad_lookup" type="text" name="alumno_localidad_nombre" value="<?php echo h((string) $localidadValue); ?>">
                </label>
                <p class="subheading" id="cpLookupStatus" hidden></p>
              </div>
            </section>

            <section class="panel empresa-block empresa-block-tutores">
              <div class="panel-header">
                <h3>Medios de contacto</h3>
              </div>
              <section class="entity-section empresa-subsection" id="mediosContactoSection">
                <div class="grid empresa-medios-grid">
                  <section class="entity-nested-section" id="telefonosAlumnoSection">
                    <div class="entity-section-header">
                      <h4>Teléfonos</h4>
                      <button type="button" class="edit-toggle" data-add-item="#telefonosAlumnoList" data-template="#telefonoTemplate">Añadir teléfono</button>
                    </div>
                    <div class="entity-stack" id="telefonosAlumnoList" data-next-index="0">
                      <?php foreach ($telefonos as $index => $telefono): ?>
                        <div class="entity-repeatable-item empresa-inline-item" data-existing-item="1">
                          <input type="hidden" name="telefonos[existing][<?php echo (int) $index; ?>][<?php echo h($telefonoPrimaryKey); ?>]" value="<?php echo (int) ($telefono[$telefonoPrimaryKey] ?? 0); ?>">
                          <input type="hidden" name="telefonos[existing][<?php echo (int) $index; ?>][_delete]" value="0" data-delete-field>
                          <div class="entity-inline-grid empresa-contact-item-grid">
                            <label for="tel_existing_<?php echo (int) $index; ?>_telefono">
                              Número
                              <input id="tel_existing_<?php echo (int) $index; ?>_telefono" type="text" name="telefonos[existing][<?php echo (int) $index; ?>][telefono]" value="<?php echo h((string) ($telefono['telefono'] ?? '')); ?>">
                            </label>
                            <?php if ($telefonoTypeField !== null): ?>
                              <?php $telefonoTypeValue = (string) ($telefono[$telefonoTypeField] ?? ''); ?>
                              <label for="tel_existing_<?php echo (int) $index; ?>_tipo">
                                Tipo
                                <select id="tel_existing_<?php echo (int) $index; ?>_tipo" name="telefonos[existing][<?php echo (int) $index; ?>][<?php echo h($telefonoTypeField); ?>]">
                                  <option value="">Selecciona</option>
                                  <option value="Personal" <?php echo $telefonoTypeValue === 'Personal' ? 'selected' : ''; ?>>Personal</option>
                                  <option value="Otro" <?php echo $telefonoTypeValue === 'Otro' ? 'selected' : ''; ?>>Otro</option>
                                </select>
                              </label>
                            <?php endif; ?>
                            <button type="button" class="ghost-button" data-remove-item>Eliminar</button>
                          </div>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  </section>

                  <section class="entity-nested-section" id="correosAlumnoSection">
                    <div class="entity-section-header">
                      <h4>Correos</h4>
                      <button type="button" class="edit-toggle" data-add-item="#correosAlumnoList" data-template="#correoTemplate">Añadir correo</button>
                    </div>
                    <div class="entity-stack" id="correosAlumnoList" data-next-index="0">
                      <?php foreach ($correos as $index => $correo): ?>
                        <div class="entity-repeatable-item empresa-inline-item" data-existing-item="1">
                          <input type="hidden" name="correos[existing][<?php echo (int) $index; ?>][<?php echo h($correoPrimaryKey); ?>]" value="<?php echo (int) ($correo[$correoPrimaryKey] ?? 0); ?>">
                          <input type="hidden" name="correos[existing][<?php echo (int) $index; ?>][_delete]" value="0" data-delete-field>
                          <div class="entity-inline-grid empresa-contact-item-grid">
                            <label for="correo_existing_<?php echo (int) $index; ?>_direccion_correo">
                              Correo electrónico
                              <input id="correo_existing_<?php echo (int) $index; ?>_direccion_correo" type="email" name="correos[existing][<?php echo (int) $index; ?>][direccion_correo]" value="<?php echo h((string) ($correo['direccion_correo'] ?? '')); ?>">
                            </label>
                            <?php if ($correoTypeField !== null): ?>
                              <?php $correoTypeValue = (string) ($correo[$correoTypeField] ?? ''); ?>
                              <label for="correo_existing_<?php echo (int) $index; ?>_tipo">
                                Tipo
                                <select id="correo_existing_<?php echo (int) $index; ?>_tipo" name="correos[existing][<?php echo (int) $index; ?>][<?php echo h($correoTypeField); ?>]">
                                  <option value="">Selecciona</option>
                                  <option value="Personal" <?php echo $correoTypeValue === 'Personal' ? 'selected' : ''; ?>>Personal</option>
                                  <option value="EducaMadrid" <?php echo $correoTypeValue === 'EducaMadrid' ? 'selected' : ''; ?>>EducaMadrid</option>
                                </select>
                              </label>
                            <?php endif; ?>
                            <button type="button" class="ghost-button" data-remove-item>Eliminar</button>
                          </div>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  </section>
                </div>
              </section>
            </section>

            <section class="panel empresa-block empresa-block-direcciones">
              <div class="panel-header">
                <h3>Acciones</h3>
                <p>Guarda los cambios del alumno o vuelve a la ficha.</p>
              </div>
              <div class="form-actions">
                <button type="submit" class="primary-button">Guardar cambios</button>
                <a class="ghost-button" href="alumno_detalle.php?id_alumno=<?php echo (int) $idAlumno; ?>">Cancelar</a>
              </div>
            </section>
          </div>
        </form>
      <?php endif; ?>
    </main>
  </div>

  <template id="telefonoTemplate">
    <div class="entity-repeatable-item empresa-inline-item">
      <div class="entity-inline-grid empresa-contact-item-grid">
        <label>
          Número
          <input type="text" name="telefonos[new][__INDEX__][telefono]">
        </label>
        <?php if ($telefonoTypeField !== null): ?>
          <label>
            Tipo
            <select name="telefonos[new][__INDEX__][<?php echo h($telefonoTypeField); ?>]">
              <option value="">Selecciona</option>
              <option value="Personal">Personal</option>
              <option value="Otro">Otro</option>
            </select>
          </label>
        <?php endif; ?>
        <button type="button" class="ghost-button" data-remove-item>Eliminar</button>
      </div>
    </div>
  </template>

  <template id="correoTemplate">
    <div class="entity-repeatable-item empresa-inline-item">
      <div class="entity-inline-grid empresa-contact-item-grid">
        <label>
          Correo electrónico
          <input type="email" name="correos[new][__INDEX__][direccion_correo]">
        </label>
        <?php if ($correoTypeField !== null): ?>
          <label>
            Tipo
            <select name="correos[new][__INDEX__][<?php echo h($correoTypeField); ?>]">
              <option value="">Selecciona</option>
              <option value="Personal">Personal</option>
              <option value="EducaMadrid">EducaMadrid</option>
            </select>
          </label>
        <?php endif; ?>
        <button type="button" class="ghost-button" data-remove-item>Eliminar</button>
      </div>
    </div>
  </template>

  <script>
    (function () {
      const replaceIndex = (value, index) => value.replaceAll('__INDEX__', String(index));
      const cpInput = document.querySelector('#alumno_cp_lookup');
      const provinciaInput = document.querySelector('#alumno_provincia_lookup');
      const provinciaIdInput = document.querySelector('#alumno_provincia_lookup_id');
      const localidadInput = document.querySelector('#alumno_localidad_lookup');
      const cpStatus = document.querySelector('#cpLookupStatus');
      const provinciasIndex = new Map();
      <?php foreach ($provinciaOptions as $provincia): ?>
        provinciasIndex.set('<?php echo addslashes(normalize_localidad_lookup_value((string) ($provincia['nombre'] ?? ''))); ?>', {
          id: '<?php echo (int) $provincia['id_provincia']; ?>',
          nombre: '<?php echo addslashes((string) ($provincia['nombre'] ?? '')); ?>',
        });
      <?php endforeach; ?>

      const fetchJson = async (url, options = {}) => {
        const response = await fetch(url, {
          headers: { Accept: 'application/json', ...(options.headers || {}) },
          ...options,
        });

        if (!response.ok) {
          return null;
        }

        return response.json();
      };

      const setCpStatus = (message = '', isVisible = false) => {
        if (!cpStatus) {
          return;
        }

        cpStatus.textContent = isVisible ? message : '';
        cpStatus.hidden = !isVisible;
      };

      const normalizePostalCode = (value) => String(value || '').trim().replace(/[\s-]+/g, '');
      const normalizeLookupText = (value) => String(value || '')
        .trim()
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, ' ')
        .trim();

      const syncProvinciaId = () => {
        if (!provinciaInput || !provinciaIdInput) {
          return;
        }

        const provinciaNormalized = normalizeLookupText(provinciaInput.value || '');
        const provinciaMatch = provinciasIndex.get(provinciaNormalized);
        if (!provinciaMatch) {
          return;
        }

        provinciaInput.value = provinciaMatch.nombre;
        provinciaIdInput.value = provinciaMatch.id;
      };

      const fetchAddressFromPostalCode = async () => {
        if (!cpInput || !provinciaInput || !localidadInput) {
          return;
        }

        const postalCode = normalizePostalCode(cpInput.value || '');
        if (!postalCode || postalCode.length < 2) {
          return;
        }

        setCpStatus('⏳ Cargando datos de dirección…', true);

        try {
          const response = await fetch(`https://api.zippopotam.us/es/${encodeURIComponent(postalCode)}`);
          if (!response.ok) {
            setCpStatus('', false);
            return;
          }

          const data = await response.json();
          const place = Array.isArray(data.places) && data.places.length > 0 ? data.places[0] : null;
          const provinciaName = String(place?.state || '').trim();
          const localidadName = String(place?.['place name'] || '').trim();

          if (provinciaName) {
            provinciaInput.value = provinciaName;
            syncProvinciaId();
          }

          if (localidadName) {
            localidadInput.value = localidadName;
          }

          setCpStatus('', false);
        } catch (_) {
          setCpStatus('', false);
        }
      };

      const addItemFromTemplate = (listSelector, templateSelector) => {
        const list = document.querySelector(listSelector);
        const template = document.querySelector(templateSelector);
        if (!list || !template) {
          return null;
        }

        const itemIndex = Number(list.dataset.nextIndex || 0);
        const html = replaceIndex(template.innerHTML, itemIndex);
        list.insertAdjacentHTML('beforeend', html);
        list.dataset.nextIndex = String(itemIndex + 1);
        return list.lastElementChild;
      };

      document.addEventListener('click', (event) => {
        const addButton = event.target.closest('[data-add-item]');
        if (addButton) {
          addItemFromTemplate(addButton.dataset.addItem, addButton.dataset.template);
          return;
        }

        const removeButton = event.target.closest('[data-remove-item]');
        if (!removeButton) {
          return;
        }

        const wrapper = removeButton.closest('.entity-repeatable-item');
        if (!wrapper) {
          return;
        }

        if (wrapper.dataset.existingItem === '1') {
          const deleteField = wrapper.querySelector('[data-delete-field]');
          if (deleteField) {
            deleteField.value = '1';
          }
        }

        wrapper.remove();
      });

      cpInput?.addEventListener('blur', fetchAddressFromPostalCode);
      provinciaInput?.addEventListener('blur', syncProvinciaId);
    })();
  </script>
</body>
</html>
