<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pdo = db();

function normalize_text($value): ?string {
  if ($value === null) {
    return null;
  }

  $trimmed = trim((string) $value);
  return $trimmed === '' ? null : $trimmed;
}

function sanitize_phone_entries(array $items): array {
  $clean = [];

  foreach ($items as $item) {
    if (!is_array($item)) {
      continue;
    }

    $telefono = normalize_text($item['telefono'] ?? null);
    $etiqueta = normalize_text($item['etiqueta'] ?? null);

    if ($telefono === null) {
      continue;
    }

    $clean[] = [
      'telefono' => $telefono,
      'etiqueta' => $etiqueta,
    ];
  }

  return $clean;
}

function sanitize_email_entries(array $items): array {
  $clean = [];

  foreach ($items as $item) {
    if (!is_array($item)) {
      continue;
    }

    $correo = normalize_text($item['correo'] ?? null);
    $etiqueta = normalize_text($item['etiqueta'] ?? null);

    if ($correo === null) {
      continue;
    }

    $clean[] = [
      'correo' => $correo,
      'etiqueta' => $etiqueta,
    ];
  }

  return $clean;
}

function normalize_entity_list($value): array {
  return is_array($value) ? $value : [];
}

function normalize_lookup_value(string $value): string {
  $normalized = trim(mb_strtolower($value, 'UTF-8'));
  if ($normalized === '') {
    return '';
  }

  $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized);
  if ($ascii !== false) {
    $normalized = $ascii;
  }

  $normalized = preg_replace('/\b(provincia|provincia de|comunidad|comunidad de|principado|region|regiao|región|de|del|d\')\b/u', ' ', $normalized) ?? $normalized;
  $normalized = preg_replace('/[^a-z0-9]+/u', ' ', $normalized) ?? $normalized;
  $normalized = trim(preg_replace('/\s+/u', ' ', $normalized) ?? $normalized);

  return $normalized;
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

function guess_iso_code_from_country_name(string $countryName): string {
  $normalized = normalize_lookup_value($countryName);
  if ($normalized === '') {
    return '';
  }

  $map = [
    'espana' => 'ES',
    'spain' => 'ES',
    'portugal' => 'PT',
    'francia' => 'FR',
    'france' => 'FR',
    'alemania' => 'DE',
    'germany' => 'DE',
    'italia' => 'IT',
    'italy' => 'IT',
    'reino unido' => 'GB',
    'united kingdom' => 'GB',
    'gran bretana' => 'GB',
    'irlanda' => 'IE',
    'ireland' => 'IE',
    'belgica' => 'BE',
    'belgium' => 'BE',
    'paises bajos' => 'NL',
    'netherlands' => 'NL',
    'luxemburgo' => 'LU',
    'luxembourg' => 'LU',
    'suiza' => 'CH',
    'switzerland' => 'CH',
    'andorra' => 'AD',
    'estados unidos' => 'US',
    'united states' => 'US',
    'mexico' => 'MX',
    'argentina' => 'AR',
    'chile' => 'CL',
    'colombia' => 'CO',
  ];

  return $map[$normalized] ?? '';
}

function calculate_lookup_score(string $needle, string $candidate): float {
  if ($needle === '' || $candidate === '') {
    return 0.0;
  }

  if ($needle === $candidate) {
    return 1000.0;
  }

  $score = 0.0;
  if (str_contains($candidate, $needle) || str_contains($needle, $candidate)) {
    $score += 250.0;
  }

  similar_text($needle, $candidate, $similarityPercent);
  $score += $similarityPercent;

  return $score;
}

function pick_best_lookup_match(string $needle, array $rows, string $nameKey): ?array {
  $normalizedNeedle = normalize_lookup_value($needle);
  if ($normalizedNeedle === '') {
    return null;
  }

  $bestRow = null;
  $bestScore = 0.0;

  foreach ($rows as $row) {
    $normalizedCandidate = normalize_lookup_value((string) ($row[$nameKey] ?? ''));
    if ($normalizedCandidate === '') {
      continue;
    }

    $score = calculate_lookup_score($normalizedNeedle, $normalizedCandidate);
    if ($score > $bestScore) {
      $bestScore = $score;
      $bestRow = $row;
    }
  }

  return $bestScore >= 45.0 ? $bestRow : null;
}

function build_save_error_message(Throwable $exception): string {
  $details = [];

  $message = trim($exception->getMessage());
  if ($message !== '') {
    $details[] = $message;
  }

  if ($exception instanceof PDOException) {
    $errorInfo = $exception->errorInfo;
    if (is_array($errorInfo)) {
      $sqlState = isset($errorInfo[0]) ? trim((string) $errorInfo[0]) : '';
      $driverCode = isset($errorInfo[1]) ? trim((string) $errorInfo[1]) : '';
      $driverMessage = isset($errorInfo[2]) ? trim((string) $errorInfo[2]) : '';

      if ($sqlState !== '') {
        $details[] = 'SQLSTATE: ' . $sqlState;
      }
      if ($driverCode !== '') {
        $details[] = 'Código PDO: ' . $driverCode;
      }
      if ($driverMessage !== '' && $driverMessage !== $message) {
        $details[] = $driverMessage;
      }
    }
  }

  if (!$details) {
    $details[] = 'Error interno sin detalle adicional.';
  }

  return 'No se pudo guardar la empresa. Motivo: ' . implode(' | ', $details);
}

if (isset($_GET['action'])) {
  header('Content-Type: application/json; charset=utf-8');

  $action = (string) $_GET['action'];

  try {
    if ($action === 'list_localidades') {
      $id_provincia = isset($_GET['id_provincia']) ? (int) $_GET['id_provincia'] : 0;
      if ($id_provincia <= 0) {
        echo json_encode(['ok' => true, 'items' => []], JSON_UNESCAPED_UNICODE);
        exit;
      }

      $stmt = $pdo->prepare('SELECT id_localidad, nombre FROM localidades WHERE id_provincia = :id_provincia ORDER BY nombre');
      $stmt->execute(['id_provincia' => $id_provincia]);
      echo json_encode(['ok' => true, 'items' => $stmt->fetchAll()], JSON_UNESCAPED_UNICODE);
      exit;
    }

    if ($action === 'country_code') {
      $id_pais = isset($_GET['id_pais']) ? (int) $_GET['id_pais'] : 0;
      if ($id_pais <= 0) {
        echo json_encode(['ok' => true, 'code' => ''], JSON_UNESCAPED_UNICODE);
        exit;
      }

      $stmt = $pdo->prepare('SELECT pais, codigo_iso FROM paises WHERE id_pais = :id_pais LIMIT 1');
      $stmt->execute(['id_pais' => $id_pais]);
      $row = $stmt->fetch();

      if (!$row) {
        echo json_encode(['ok' => true, 'code' => ''], JSON_UNESCAPED_UNICODE);
        exit;
      }

      $iso = strtoupper(trim((string) ($row['codigo_iso'] ?? '')));
      if (!preg_match('/^[A-Z]{2}$/', $iso)) {
        $iso = guess_iso_code_from_country_name((string) ($row['pais'] ?? ''));
      }

      echo json_encode(['ok' => true, 'code' => $iso], JSON_UNESCAPED_UNICODE);
      exit;
    }

    if ($action === 'lookup_provincia') {
      $name = trim((string) ($_GET['name'] ?? ''));
      $id_pais = isset($_GET['id_pais']) ? (int) $_GET['id_pais'] : 0;

      if ($name === '') {
        echo json_encode(['ok' => true, 'id_provincia' => null], JSON_UNESCAPED_UNICODE);
        exit;
      }

      if ($id_pais > 0) {
        $stmt = $pdo->prepare('SELECT id_provincia, nombre FROM provincias WHERE id_pais = :id_pais ORDER BY nombre');
        $stmt->execute(['id_pais' => $id_pais]);
      } else {
        $stmt = $pdo->query('SELECT id_provincia, nombre FROM provincias ORDER BY nombre');
      }

      $match = pick_best_lookup_match($name, $stmt->fetchAll(), 'nombre');
      echo json_encode(['ok' => true, 'id_provincia' => $match !== null ? (int) $match['id_provincia'] : null, 'nombre' => $match['nombre'] ?? null], JSON_UNESCAPED_UNICODE);
      exit;
    }

    if ($action === 'lookup_localidad') {
      $name = trim((string) ($_GET['name'] ?? ''));
      $id_provincia = isset($_GET['id_provincia']) ? (int) $_GET['id_provincia'] : 0;
      $id_pais = isset($_GET['id_pais']) ? (int) $_GET['id_pais'] : 0;

      if ($name === '') {
        echo json_encode(['ok' => true, 'id_localidad' => null], JSON_UNESCAPED_UNICODE);
        exit;
      }

      $sql = 'SELECT l.id_localidad, l.nombre FROM localidades l INNER JOIN provincias p ON p.id_provincia = l.id_provincia WHERE 1=1';
      $params = [];
      if ($id_provincia > 0) {
        $sql .= ' AND l.id_provincia = :id_provincia';
        $params['id_provincia'] = $id_provincia;
      }
      if ($id_pais > 0) {
        $sql .= ' AND p.id_pais = :id_pais';
        $params['id_pais'] = $id_pais;
      }
      $sql .= ' ORDER BY l.nombre';

      $stmt = $pdo->prepare($sql);
      $stmt->execute($params);

      $match = pick_best_lookup_match($name, $stmt->fetchAll(), 'nombre');
      echo json_encode(['ok' => true, 'id_localidad' => $match !== null ? (int) $match['id_localidad'] : null, 'nombre' => $match['nombre'] ?? null], JSON_UNESCAPED_UNICODE);
      exit;
    }

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

$via_options = $pdo->query('SELECT id_via, via FROM vias ORDER BY via')->fetchAll();
$pais_options = $pdo->query('SELECT id_pais, pais, codigo_iso FROM paises ORDER BY pais')->fetchAll();
$provincia_options = $pdo->query('SELECT id_provincia, nombre FROM provincias ORDER BY nombre')->fetchAll();
$pais_id_to_code = [];
$default_spain_country_id = '';
foreach ($pais_options as $pais) {
  $id = isset($pais['id_pais']) ? (int) $pais['id_pais'] : 0;
  $iso = strtoupper(trim((string) ($pais['codigo_iso'] ?? '')));
  $country_name = (string) ($pais['pais'] ?? '');

  if (!preg_match('/^[A-Z]{2}$/', $iso)) {
    $iso = guess_iso_code_from_country_name((string) ($pais['pais'] ?? ''));
  }

  if ($id > 0 && $iso !== '') {
    $pais_id_to_code[(string) $id] = strtoupper($iso);
  }

  if ($default_spain_country_id === '' && $id > 0) {
    $normalized_country_name = normalize_lookup_value($country_name);
    if (strtoupper($iso) === 'ES' || $normalized_country_name === 'espana' || $normalized_country_name === 'spain') {
      $default_spain_country_id = (string) $id;
    }
  }
}

$id_empresa = isset($_GET['id_empresa']) ? (int) $_GET['id_empresa'] : 0;
$errors = [];
$load_error = null;

$form_values = [
  'empresa' => [
    'cif' => '',
    'nombre' => '',
    'apellido1' => '',
    'apellido2' => '',
    'nombre_comercial' => '', // MODIFICADO
    'numero_convenio' => '',
  ],
  'telefonos_empresa' => [],
  'correos_empresa' => [],
  'direcciones' => [],
  'contactos' => [],
  'tutores' => [],
];

if ($id_empresa <= 0) {
  $load_error = 'No se ha indicado una empresa válida para editar.';
} else {
  $stmt = $pdo->prepare('SELECT id_empresa, cif, nombre, apellido1, apellido2, convenio, nombre_comercial FROM empresas WHERE id_empresa = :id_empresa LIMIT 1'); // MODIFICADO
  $stmt->execute(['id_empresa' => $id_empresa]);
  $empresa_db = $stmt->fetch();

  if (!$empresa_db) {
    $load_error = 'La empresa indicada no existe o ya no está disponible.';
  } else {
    $form_values['empresa'] = [
      'cif' => (string) ($empresa_db['cif'] ?? ''),
      'nombre' => (string) ($empresa_db['nombre'] ?? ''),
      'apellido1' => (string) ($empresa_db['apellido1'] ?? ''),
      'apellido2' => (string) ($empresa_db['apellido2'] ?? ''),
      'nombre_comercial' => (string) ($empresa_db['nombre_comercial'] ?? ''), // MODIFICADO
      'numero_convenio' => $empresa_db['convenio'] !== null ? (string) $empresa_db['convenio'] : '',
    ];

    $stmt = $pdo->prepare('SELECT telefono, etiqueta FROM telefonos WHERE entidad_tipo = :entidad_tipo AND id_entidad = :id_entidad ORDER BY id_telefono');
    $stmt->execute(['entidad_tipo' => 'empresa', 'id_entidad' => $id_empresa]);
    $form_values['telefonos_empresa'] = array_map(static fn(array $row): array => [
      'telefono' => (string) ($row['telefono'] ?? ''),
      'etiqueta' => (string) ($row['etiqueta'] ?? ''),
    ], $stmt->fetchAll());

    $stmt = $pdo->prepare('SELECT direccion_correo AS correo, etiqueta FROM correos WHERE entidad_tipo = :entidad_tipo AND id_entidad = :id_entidad ORDER BY id_correo');
    $stmt->execute(['entidad_tipo' => 'empresa', 'id_entidad' => $id_empresa]);
    $form_values['correos_empresa'] = array_map(static fn(array $row): array => [
      'correo' => (string) ($row['correo'] ?? ''),
      'etiqueta' => (string) ($row['etiqueta'] ?? ''),
    ], $stmt->fetchAll());

    $stmt = $pdo->prepare('SELECT id_direccion, etiqueta, id_via, nombre_via, numero, bloque, escalera, planta, puerta, cp, otros, id_pais, id_provincia, id_localidad FROM direcciones WHERE id_empresa = :id_empresa ORDER BY id_direccion');
    $stmt->execute(['id_empresa' => $id_empresa]);
    $form_values['direcciones'] = array_map(static fn(array $row): array => [
      'id_direccion' => $row['id_direccion'] !== null ? (string) $row['id_direccion'] : '',
      'etiqueta' => (string) ($row['etiqueta'] ?? ''),
      'id_via' => $row['id_via'] !== null ? (string) $row['id_via'] : '',
      'nombre_via' => (string) ($row['nombre_via'] ?? ''),
      'numero' => (string) ($row['numero'] ?? ''),
      'bloque' => (string) ($row['bloque'] ?? ''),
      'escalera' => (string) ($row['escalera'] ?? ''),
      'planta' => (string) ($row['planta'] ?? ''),
      'puerta' => (string) ($row['puerta'] ?? ''),
      'cp' => (string) ($row['cp'] ?? ''),
      'otros' => (string) ($row['otros'] ?? ''),
      'id_pais' => $row['id_pais'] !== null ? (string) $row['id_pais'] : $default_spain_country_id,
      'id_provincia' => $row['id_provincia'] !== null ? (string) $row['id_provincia'] : '',
      'localidad' => '',
      'id_localidad' => $row['id_localidad'] !== null ? (string) $row['id_localidad'] : '',
    ], $stmt->fetchAll());

    $localidad_ids = [];
    foreach ($form_values['direcciones'] as $direccion) {
      if (($direccion['id_localidad'] ?? '') !== '') {
        $localidad_ids[] = (int) $direccion['id_localidad'];
      }
    }

    if ($localidad_ids) {
      $placeholders = implode(',', array_fill(0, count($localidad_ids), '?'));
      $stmt = $pdo->prepare('SELECT id_localidad, nombre FROM localidades WHERE id_localidad IN (' . $placeholders . ')');
      $stmt->execute($localidad_ids);
      $localidades_map = [];
      foreach ($stmt->fetchAll() as $localidad) {
        $localidades_map[(string) ((int) ($localidad['id_localidad'] ?? 0))] = (string) ($localidad['nombre'] ?? '');
      }

      foreach ($form_values['direcciones'] as &$direccion) {
        $idLocalidad = (string) ($direccion['id_localidad'] ?? '');
        if ($idLocalidad !== '' && isset($localidades_map[$idLocalidad])) {
          $direccion['localidad'] = $localidades_map[$idLocalidad];
        }
      }
      unset($direccion);
    }

    $stmt = $pdo->prepare('SELECT id_empresa_contacto, nombre, apellido1, apellido2, cargo FROM empresas_contactos WHERE id_empresa = :id_empresa ORDER BY id_empresa_contacto');
    $stmt->execute(['id_empresa' => $id_empresa]);
    $contactos_db = $stmt->fetchAll();

    foreach ($contactos_db as $contacto_db) {
      $id_contacto = (int) ($contacto_db['id_empresa_contacto'] ?? 0);
      $contacto = [
        'nombre' => (string) ($contacto_db['nombre'] ?? ''),
        'apellido1' => (string) ($contacto_db['apellido1'] ?? ''),
        'apellido2' => (string) ($contacto_db['apellido2'] ?? ''),
        'cargo' => (string) ($contacto_db['cargo'] ?? ''),
        'telefonos' => [],
        'correos' => [],
      ];

      if ($id_contacto > 0) {
        $stmt = $pdo->prepare('SELECT telefono, etiqueta FROM telefonos WHERE entidad_tipo = :entidad_tipo AND id_entidad = :id_entidad ORDER BY id_telefono');
        $stmt->execute(['entidad_tipo' => 'empresa_contacto', 'id_entidad' => $id_contacto]);
        $contacto['telefonos'] = array_map(static fn(array $row): array => [
          'telefono' => (string) ($row['telefono'] ?? ''),
          'etiqueta' => (string) ($row['etiqueta'] ?? ''),
        ], $stmt->fetchAll());

        $stmt = $pdo->prepare('SELECT direccion_correo AS correo, etiqueta FROM correos WHERE entidad_tipo = :entidad_tipo AND id_entidad = :id_entidad ORDER BY id_correo');
        $stmt->execute(['entidad_tipo' => 'empresa_contacto', 'id_entidad' => $id_contacto]);
        $contacto['correos'] = array_map(static fn(array $row): array => [
          'correo' => (string) ($row['correo'] ?? ''),
          'etiqueta' => (string) ($row['etiqueta'] ?? ''),
        ], $stmt->fetchAll());
      }

      $form_values['contactos'][] = $contacto;
    }

    $stmt = $pdo->prepare('SELECT id_empresas_tutor, nombre, apellido1, apellido2, dni FROM empresas_tutores WHERE id_empresa = :id_empresa ORDER BY id_empresas_tutor');
    $stmt->execute(['id_empresa' => $id_empresa]);
    $tutores_db = $stmt->fetchAll();

    foreach ($tutores_db as $tutor_db) {
      $id_tutor = (int) ($tutor_db['id_empresas_tutor'] ?? 0);
      $tutor = [
        'id_empresas_tutor' => (string) $id_tutor, // MODIFICADO
        'nombre' => (string) ($tutor_db['nombre'] ?? ''),
        'apellido1' => (string) ($tutor_db['apellido1'] ?? ''),
        'apellido2' => (string) ($tutor_db['apellido2'] ?? ''),
        'dni' => (string) ($tutor_db['dni'] ?? ''),
        'telefonos' => [],
        'correos' => [],
      ];

      if ($id_tutor > 0) {
        $stmt = $pdo->prepare('SELECT telefono, etiqueta FROM telefonos WHERE entidad_tipo = :entidad_tipo AND id_entidad = :id_entidad ORDER BY id_telefono');
        $stmt->execute(['entidad_tipo' => 'empresa_tutor', 'id_entidad' => $id_tutor]);
        $tutor['telefonos'] = array_map(static fn(array $row): array => [
          'telefono' => (string) ($row['telefono'] ?? ''),
          'etiqueta' => (string) ($row['etiqueta'] ?? ''),
        ], $stmt->fetchAll());

        $stmt = $pdo->prepare('SELECT direccion_correo AS correo, etiqueta FROM correos WHERE entidad_tipo = :entidad_tipo AND id_entidad = :id_entidad ORDER BY id_correo');
        $stmt->execute(['entidad_tipo' => 'empresa_tutor', 'id_entidad' => $id_tutor]);
        $tutor['correos'] = array_map(static fn(array $row): array => [
          'correo' => (string) ($row['correo'] ?? ''),
          'etiqueta' => (string) ($row['etiqueta'] ?? ''),
        ], $stmt->fetchAll());
      }

      $form_values['tutores'][] = $tutor;
    }
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $load_error === null) {
  $form_values = $_POST;
  $empresa = [
    'cif' => normalize_text($_POST['empresa']['cif'] ?? null),
    'nombre' => normalize_text($_POST['empresa']['nombre'] ?? null),
    'apellido1' => normalize_text($_POST['empresa']['apellido1'] ?? null),
    'apellido2' => normalize_text($_POST['empresa']['apellido2'] ?? null),
    'nombre_comercial' => normalize_text($_POST['empresa']['nombre_comercial'] ?? null), // MODIFICADO
    'numero_convenio' => normalize_text($_POST['empresa']['numero_convenio'] ?? null),
  ];

  if ($empresa['nombre'] === null) {
    $errors[] = 'El nombre de la empresa es obligatorio.';
  }

  if ($empresa['numero_convenio'] !== null && !ctype_digit($empresa['numero_convenio'])) {
    $errors[] = 'El número de convenio debe ser numérico.';
  }

  $telefonos_empresa = sanitize_phone_entries(normalize_entity_list($_POST['telefonos_empresa'] ?? []));
  $correos_empresa = sanitize_email_entries(normalize_entity_list($_POST['correos_empresa'] ?? []));

  $direcciones = [];
  foreach (normalize_entity_list($_POST['direcciones'] ?? []) as $direccion) {
    if (!is_array($direccion)) {
      continue;
    }

    $entry = [
      'id_direccion' => normalize_text($direccion['id_direccion'] ?? null),
      'etiqueta' => normalize_text($direccion['etiqueta'] ?? null),
      'id_via' => normalize_text($direccion['id_via'] ?? null),
      'nombre_via' => normalize_text($direccion['nombre_via'] ?? null),
      'numero' => normalize_text($direccion['numero'] ?? null),
      'bloque' => normalize_text($direccion['bloque'] ?? null),
      'escalera' => normalize_text($direccion['escalera'] ?? null),
      'planta' => normalize_text($direccion['planta'] ?? null),
      'puerta' => normalize_text($direccion['puerta'] ?? null),
      'cp' => normalize_text($direccion['cp'] ?? null),
      'id_pais' => normalize_text($direccion['id_pais'] ?? null),
      'id_provincia' => normalize_text($direccion['id_provincia'] ?? null),
      'localidad' => normalize_text($direccion['localidad'] ?? null),
      'id_localidad' => normalize_text($direccion['id_localidad'] ?? null),
      'otros' => normalize_text($direccion['otros'] ?? null),
    ];

    $has_any_value = false;
    foreach ($entry as $key => $value) {
      if ($key === 'id_direccion') {
        continue;
      }
      if ($value !== null) {
        $has_any_value = true;
        break;
      }
    }

    if (!$has_any_value) {
      continue;
    }

    $direcciones[] = $entry;
  }

  $contactos = [];
  foreach (normalize_entity_list($_POST['contactos'] ?? []) as $contacto) {
    if (!is_array($contacto)) {
      continue;
    }

    $entry = [
      'nombre' => normalize_text($contacto['nombre'] ?? null),
      'apellido1' => normalize_text($contacto['apellido1'] ?? null),
      'apellido2' => normalize_text($contacto['apellido2'] ?? null),
      'cargo' => normalize_text($contacto['cargo'] ?? null),
      'telefonos' => sanitize_phone_entries(normalize_entity_list($contacto['telefonos'] ?? [])),
      'correos' => sanitize_email_entries(normalize_entity_list($contacto['correos'] ?? [])),
    ];

    if ($entry['nombre'] === null && $entry['apellido1'] === null && $entry['apellido2'] === null && $entry['cargo'] === null && !$entry['telefonos'] && !$entry['correos']) {
      continue;
    }

    if ($entry['nombre'] === null || $entry['apellido1'] === null) {
      $errors[] = 'Cada persona de contacto debe tener al menos nombre y apellido1.';
    }

    $contactos[] = $entry;
  }

  $tutores = [];
  foreach (normalize_entity_list($_POST['tutores'] ?? []) as $tutor) {
    if (!is_array($tutor)) {
      continue;
    }

    $entry = [
      'id_empresas_tutor' => normalize_text($tutor['id_empresas_tutor'] ?? null), // MODIFICADO
      'nombre' => normalize_text($tutor['nombre'] ?? null),
      'apellido1' => normalize_text($tutor['apellido1'] ?? null),
      'apellido2' => normalize_text($tutor['apellido2'] ?? null),
      'dni' => normalize_text($tutor['dni'] ?? null),
      'telefonos' => sanitize_phone_entries(normalize_entity_list($tutor['telefonos'] ?? [])),
      'correos' => sanitize_email_entries(normalize_entity_list($tutor['correos'] ?? [])),
    ];

    if ($entry['nombre'] === null && $entry['apellido1'] === null && $entry['apellido2'] === null && $entry['dni'] === null && !$entry['telefonos'] && !$entry['correos']) {
      continue;
    }

    if ($entry['nombre'] === null || $entry['apellido1'] === null) {
      $errors[] = 'Cada tutor debe tener al menos nombre y apellido1.';
    }

    if ($entry['id_empresas_tutor'] !== null && !ctype_digit($entry['id_empresas_tutor'])) { // MODIFICADO
      $errors[] = 'El identificador del tutor no es válido.';
    }

    $tutores[] = $entry;
  }

  if (!$errors) {
    try {
      $pdo->beginTransaction();

      $update_empresa = $pdo->prepare(
        'UPDATE empresas
         SET cif = :cif, nombre = :nombre, apellido1 = :apellido1, apellido2 = :apellido2, nombre_comercial = :nombre_comercial, convenio = :convenio
         WHERE id_empresa = :id_empresa'
      );
      $update_empresa->execute([
        'cif' => $empresa['cif'],
        'nombre' => $empresa['nombre'],
        'apellido1' => $empresa['apellido1'],
        'apellido2' => $empresa['apellido2'],
        'nombre_comercial' => $empresa['nombre_comercial'], // MODIFICADO
        'convenio' => $empresa['numero_convenio'] !== null ? (int) $empresa['numero_convenio'] : null,
        'id_empresa' => $id_empresa,
      ]);

      $pdo->prepare('DELETE FROM telefonos WHERE entidad_tipo = :entidad_tipo AND id_entidad = :id_entidad')->execute([
        'entidad_tipo' => 'empresa',
        'id_entidad' => $id_empresa,
      ]);
      $pdo->prepare('DELETE FROM correos WHERE entidad_tipo = :entidad_tipo AND id_entidad = :id_entidad')->execute([
        'entidad_tipo' => 'empresa',
        'id_entidad' => $id_empresa,
      ]);
      // MODIFICADO: mantener id_direccion existentes usando UPDATE/INSERT en lugar de DELETE.
      $direccionIdsStmt = $pdo->prepare('SELECT id_direccion FROM direcciones WHERE id_empresa = :id_empresa ORDER BY id_direccion');
      $direccionIdsStmt->execute(['id_empresa' => $id_empresa]);
      $direccion_ids_disponibles = [];
      foreach ($direccionIdsStmt->fetchAll() as $direccionRow) {
        $idDireccion = (int) ($direccionRow['id_direccion'] ?? 0);
        if ($idDireccion > 0) {
          $direccion_ids_disponibles[(string) $idDireccion] = true;
        }
      }

      $contactoIds = $pdo->prepare('SELECT id_empresa_contacto FROM empresas_contactos WHERE id_empresa = :id_empresa');
      $contactoIds->execute(['id_empresa' => $id_empresa]);
      foreach ($contactoIds->fetchAll() as $row) {
        $idContacto = (int) ($row['id_empresa_contacto'] ?? 0);
        if ($idContacto > 0) {
          $pdo->prepare('DELETE FROM telefonos WHERE entidad_tipo = :entidad_tipo AND id_entidad = :id_entidad')->execute([
            'entidad_tipo' => 'empresa_contacto',
            'id_entidad' => $idContacto,
          ]);
          $pdo->prepare('DELETE FROM correos WHERE entidad_tipo = :entidad_tipo AND id_entidad = :id_entidad')->execute([
            'entidad_tipo' => 'empresa_contacto',
            'id_entidad' => $idContacto,
          ]);
        }
      }
      $pdo->prepare('DELETE FROM empresas_contactos WHERE id_empresa = :id_empresa')->execute(['id_empresa' => $id_empresa]);

      $tutorIds = $pdo->prepare('SELECT id_empresas_tutor FROM empresas_tutores WHERE id_empresa = :id_empresa'); // MODIFICADO
      $tutorIds->execute(['id_empresa' => $id_empresa]);
      $tutor_ids_existentes = [];
      foreach ($tutorIds->fetchAll() as $row) {
        $idTutor = (int) ($row['id_empresas_tutor'] ?? 0);
        if ($idTutor > 0) {
          $tutor_ids_existentes[(string) $idTutor] = true;
        }
      }

      $insert_telefono = $pdo->prepare(
        'INSERT INTO telefonos (entidad_tipo, id_entidad, telefono, etiqueta)
         VALUES (:entidad_tipo, :id_entidad, :telefono, :etiqueta)'
      );

      foreach ($telefonos_empresa as $telefono) {
        $insert_telefono->execute([
          'entidad_tipo' => 'empresa',
          'id_entidad' => $id_empresa,
          'telefono' => $telefono['telefono'],
          'etiqueta' => $telefono['etiqueta'],
        ]);
      }

      $insert_correo = $pdo->prepare(
        'INSERT INTO correos (entidad_tipo, id_entidad, direccion_correo, etiqueta)
         VALUES (:entidad_tipo, :id_entidad, :direccion_correo, :etiqueta)'
      );

      foreach ($correos_empresa as $correo) {
        $insert_correo->execute([
          'entidad_tipo' => 'empresa',
          'id_entidad' => $id_empresa,
          'direccion_correo' => $correo['correo'],
          'etiqueta' => $correo['etiqueta'],
        ]);
      }

      $insert_direccion = $pdo->prepare(
        'INSERT INTO direcciones (
          id_empresa, id_pais, id_provincia, id_localidad, id_via, nombre_via, numero, bloque, escalera,
          planta, puerta, etiqueta, cp, otros, principal
        ) VALUES (
          :id_empresa, :id_pais, :id_provincia, :id_localidad, :id_via, :nombre_via, :numero, :bloque, :escalera,
          :planta, :puerta, :etiqueta, :cp, :otros, :principal
        )'
      );

      $update_direccion = $pdo->prepare(
        'UPDATE direcciones
         SET id_pais = :id_pais,
             id_provincia = :id_provincia,
             id_localidad = :id_localidad,
             id_via = :id_via,
             nombre_via = :nombre_via,
             numero = :numero,
             bloque = :bloque,
             escalera = :escalera,
             planta = :planta,
             puerta = :puerta,
             etiqueta = :etiqueta,
             cp = :cp,
             otros = :otros,
             principal = :principal
         WHERE id_direccion = :id_direccion AND id_empresa = :id_empresa'
      );

      foreach ($direcciones as $direccion) {
        $direccion_params = [
          'id_empresa' => $id_empresa,
          'id_pais' => $direccion['id_pais'] !== null ? (int) $direccion['id_pais'] : null,
          'id_provincia' => $direccion['id_provincia'] !== null ? (int) $direccion['id_provincia'] : null,
          'id_localidad' => $direccion['id_localidad'] !== null ? (int) $direccion['id_localidad'] : null,
          'id_via' => $direccion['id_via'] !== null ? (int) $direccion['id_via'] : null,
          'nombre_via' => $direccion['nombre_via'],
          'numero' => $direccion['numero'],
          'bloque' => $direccion['bloque'],
          'escalera' => $direccion['escalera'],
          'planta' => $direccion['planta'],
          'puerta' => $direccion['puerta'],
          'etiqueta' => $direccion['etiqueta'],
          'cp' => $direccion['cp'],
          'otros' => $direccion['otros'],
          'principal' => strtolower((string) ($direccion['etiqueta'] ?? '')) === 'principal' ? 1 : 0,
        ];

        $id_direccion = $direccion['id_direccion'] !== null ? (int) $direccion['id_direccion'] : 0;
        if ($id_direccion > 0 && isset($direccion_ids_disponibles[(string) $id_direccion])) {
          $update_direccion->execute($direccion_params + ['id_direccion' => $id_direccion]);
          continue;
        }

        $insert_direccion->execute($direccion_params);
      }

      $insert_contacto = $pdo->prepare(
        'INSERT INTO empresas_contactos (id_empresa, apellido1, apellido2, nombre, cargo)
         VALUES (:id_empresa, :apellido1, :apellido2, :nombre, :cargo)'
      );

      foreach ($contactos as $contacto) {
        $insert_contacto->execute([
          'id_empresa' => $id_empresa,
          'apellido1' => $contacto['apellido1'] ?? '',
          'apellido2' => $contacto['apellido2'],
          'nombre' => $contacto['nombre'] ?? '',
          'cargo' => $contacto['cargo'],
        ]);

        $id_contacto = (int) $pdo->lastInsertId();

        foreach ($contacto['telefonos'] as $telefono) {
          $insert_telefono->execute([
            'entidad_tipo' => 'empresa_contacto',
            'id_entidad' => $id_contacto,
            'telefono' => $telefono['telefono'],
            'etiqueta' => $telefono['etiqueta'],
          ]);
        }

        foreach ($contacto['correos'] as $correo) {
          $insert_correo->execute([
            'entidad_tipo' => 'empresa_contacto',
            'id_entidad' => $id_contacto,
            'direccion_correo' => $correo['correo'],
            'etiqueta' => $correo['etiqueta'],
          ]);
        }
      }

      $insert_tutor = $pdo->prepare(
        'INSERT INTO empresas_tutores (id_empresa, apellido1, apellido2, nombre, dni)
         VALUES (:id_empresa, :apellido1, :apellido2, :nombre, :dni)'
      );
      $update_tutor = $pdo->prepare( // MODIFICADO
        'UPDATE empresas_tutores
         SET apellido1 = :apellido1, apellido2 = :apellido2, nombre = :nombre, dni = :dni
         WHERE id_empresas_tutor = :id_empresas_tutor AND id_empresa = :id_empresa'
      );

      $delete_tutor_telefonos = $pdo->prepare('DELETE FROM telefonos WHERE entidad_tipo = :entidad_tipo AND id_entidad = :id_entidad'); // MODIFICADO
      $delete_tutor_correos = $pdo->prepare('DELETE FROM correos WHERE entidad_tipo = :entidad_tipo AND id_entidad = :id_entidad');
      $tutor_ids_mantenidos = [];

      foreach ($tutores as $tutor) {
        $id_tutor = 0;
        $id_tutor_post = $tutor['id_empresas_tutor'] ?? null;

        if ($id_tutor_post !== null && isset($tutor_ids_existentes[$id_tutor_post])) {
          $id_tutor = (int) $id_tutor_post;
          $update_tutor->execute([
            'id_empresa' => $id_empresa,
            'id_empresas_tutor' => $id_tutor,
            'apellido1' => $tutor['apellido1'] ?? '',
            'apellido2' => $tutor['apellido2'],
            'nombre' => $tutor['nombre'] ?? '',
            'dni' => $tutor['dni'],
          ]);
        } else {
          $insert_tutor->execute([
            'id_empresa' => $id_empresa,
            'apellido1' => $tutor['apellido1'] ?? '',
            'apellido2' => $tutor['apellido2'],
            'nombre' => $tutor['nombre'] ?? '',
            'dni' => $tutor['dni'],
          ]);
          $id_tutor = (int) $pdo->lastInsertId();
        }

        if ($id_tutor <= 0) {
          continue;
        }

        $tutor_ids_mantenidos[(string) $id_tutor] = $id_tutor;

        $delete_tutor_telefonos->execute([
          'entidad_tipo' => 'empresa_tutor',
          'id_entidad' => $id_tutor,
        ]);
        $delete_tutor_correos->execute([
          'entidad_tipo' => 'empresa_tutor',
          'id_entidad' => $id_tutor,
        ]);

        foreach ($tutor['telefonos'] as $telefono) {
          $insert_telefono->execute([
            'entidad_tipo' => 'empresa_tutor',
            'id_entidad' => $id_tutor,
            'telefono' => $telefono['telefono'],
            'etiqueta' => $telefono['etiqueta'],
          ]);
        }

        foreach ($tutor['correos'] as $correo) {
          $insert_correo->execute([
            'entidad_tipo' => 'empresa_tutor',
            'id_entidad' => $id_tutor,
            'direccion_correo' => $correo['correo'],
            'etiqueta' => $correo['etiqueta'],
          ]);
        }
      }

      $ids_borrables = []; // MODIFICADO
      if ($tutor_ids_mantenidos) { // MODIFICADO
        $ids_mantenidos = array_values($tutor_ids_mantenidos);
        $placeholders = implode(',', array_fill(0, count($ids_mantenidos), '?'));
        $select_borrables_sql =
          'SELECT et.id_empresas_tutor
           FROM empresas_tutores et
           WHERE et.id_empresa = ?
             AND et.id_empresas_tutor NOT IN (' . $placeholders . ')
             AND NOT EXISTS (
               SELECT 1 FROM practicas p WHERE p.id_empresa_tutor = et.id_empresas_tutor
             )';
        $select_borrables_stmt = $pdo->prepare($select_borrables_sql);
        $select_borrables_stmt->execute(array_merge([$id_empresa], $ids_mantenidos));
      } else {
        $select_borrables_stmt = $pdo->prepare(
          'SELECT et.id_empresas_tutor
           FROM empresas_tutores et
           WHERE et.id_empresa = ?
             AND NOT EXISTS (
               SELECT 1 FROM practicas p WHERE p.id_empresa_tutor = et.id_empresas_tutor
             )'
        );
        $select_borrables_stmt->execute([$id_empresa]);
      }

      foreach ($select_borrables_stmt->fetchAll(PDO::FETCH_COLUMN) as $id_borrable) { // MODIFICADO
        $id_borrable = (int) $id_borrable;
        if ($id_borrable > 0) {
          $ids_borrables[] = $id_borrable;
        }
      }

      if ($ids_borrables) { // MODIFICADO
        $placeholders = implode(',', array_fill(0, count($ids_borrables), '?'));
        $delete_tutor_medios_stmt = $pdo->prepare(
          'DELETE FROM telefonos
           WHERE entidad_tipo = ?
             AND id_entidad IN (' . $placeholders . ')'
        );
        $delete_tutor_medios_stmt->execute(array_merge(['empresa_tutor'], $ids_borrables));

        $delete_tutor_correos_stmt = $pdo->prepare(
          'DELETE FROM correos
           WHERE entidad_tipo = ?
             AND id_entidad IN (' . $placeholders . ')'
        );
        $delete_tutor_correos_stmt->execute(array_merge(['empresa_tutor'], $ids_borrables));
      }

      if ($tutor_ids_mantenidos) { // MODIFICADO
        $ids_mantenidos = array_values($tutor_ids_mantenidos);
        $placeholders = implode(',', array_fill(0, count($ids_mantenidos), '?'));
        $delete_sql =
          'DELETE FROM empresas_tutores
           WHERE id_empresa = ?
             AND id_empresas_tutor NOT IN (' . $placeholders . ')
             AND NOT EXISTS (
               SELECT 1 FROM practicas p WHERE p.id_empresa_tutor = empresas_tutores.id_empresas_tutor
             )';
        $delete_stmt = $pdo->prepare($delete_sql);
        $delete_stmt->execute(array_merge([$id_empresa], $ids_mantenidos));
      } else {
        $delete_stmt = $pdo->prepare(
          'DELETE FROM empresas_tutores
           WHERE id_empresa = ?
             AND NOT EXISTS (
               SELECT 1 FROM practicas p WHERE p.id_empresa_tutor = empresas_tutores.id_empresas_tutor
             )'
        );
        $delete_stmt->execute([$id_empresa]);
      }

      $pdo->commit();
      header('Location: empresa_detalle.php?id_empresa=' . $id_empresa);
      exit;
    } catch (Throwable $exception) {
      if ($pdo->inTransaction()) {
        $pdo->rollBack();
      }
      $errors[] = build_save_error_message($exception);
    }
  }
}

$page_title = 'Editar empresa | Gestor de Alumnos';
$active_page = 'empresas';
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
          <h1>Editar empresa</h1>
          <p class="subheading">Actualiza los datos de la empresa, sus medios de contacto, direcciones, contactos y tutores.</p>
        </div>
        <div class="header-actions">
          <button type="submit" form="empresaForm" class="primary-button">Guardar cambios</button><?php // MODIFICADO ?>
          <a class="ghost-button" href="empresa_detalle.php?id_empresa=<?php echo (int) $id_empresa; ?>">Volver</a>
        </div>
      </header>

      <?php if ($load_error !== null): ?>
        <section class="panel">
          <div class="panel-header">
            <h3>No se puede editar esta empresa</h3>
          </div>
          <ul class="form-errors">
            <li><?php echo htmlspecialchars($load_error, ENT_QUOTES, 'UTF-8'); ?></li>
          </ul>
          <div class="form-actions">
            <a class="ghost-button" href="empresas.php">Volver a empresas</a>
          </div>
        </section>
      <?php else: ?>
        <?php if ($errors): ?>
          <section class="panel">
            <div class="panel-header">
              <h3>Revisa los datos del formulario</h3>
            </div>
            <ul class="form-errors">
              <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
              <?php endforeach; ?>
            </ul>
          </section>
        <?php endif; ?>

      <form method="post" class="panel entity-form empresa-form" id="empresaForm">
        <div class="empresa-form-grid">
          <section class="panel empresa-block empresa-block-main">
            <div class="panel-header">
              <h3>Datos de la empresa</h3>
              <p>Información identificativa y administrativa principal.</p>
            </div>

            <div class="entity-grid empresa-datos-grid">
              <label>
                Nombre *
                <input type="text" name="empresa[nombre]" value="<?php echo htmlspecialchars((string) ($form_values['empresa']['nombre'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
              </label>
              <label>
                Apellido 1
                <input type="text" name="empresa[apellido1]" value="<?php echo htmlspecialchars((string) ($form_values['empresa']['apellido1'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
              </label>
              <label>
                Apellido 2
                <input type="text" name="empresa[apellido2]" value="<?php echo htmlspecialchars((string) ($form_values['empresa']['apellido2'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
              </label>
              <label>
                Nombre comercial
                <input type="text" name="empresa[nombre_comercial]" value="<?php echo htmlspecialchars((string) ($form_values['empresa']['nombre_comercial'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"> <?php // MODIFICADO ?>
              </label>
              <label>
                CIF
                <input type="text" name="empresa[cif]" value="<?php echo htmlspecialchars((string) ($form_values['empresa']['cif'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
              </label>
              <label>
                Número de convenio
                <input type="text" name="empresa[numero_convenio]" value="<?php echo htmlspecialchars((string) ($form_values['empresa']['numero_convenio'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
              </label>
            </div>

            <section class="entity-section empresa-subsection" id="mediosContactoSection">
              <div class="entity-section-header">
                <h3>Medios de contacto</h3>
              </div>
              <div class="grid empresa-medios-grid">
                <section class="entity-nested-section" id="telefonosEmpresaSection">
                  <div class="entity-section-header">
                    <h4>Teléfonos</h4>
                    <button type="button" class="edit-toggle" data-add-item="#telefonosEmpresaList" data-template="#telefonoEmpresaTemplate">Añadir teléfono</button>
                  </div>
                  <div class="entity-stack" id="telefonosEmpresaList"></div>
                </section>

                <section class="entity-nested-section" id="correosEmpresaSection">
                  <div class="entity-section-header">
                    <h4>Correos</h4>
                    <button type="button" class="edit-toggle" data-add-item="#correosEmpresaList" data-template="#correoEmpresaTemplate">Añadir correo</button>
                  </div>
                  <div class="entity-stack" id="correosEmpresaList"></div>
                </section>
              </div>
            </section>
          </section>

          <section class="panel empresa-block empresa-block-direcciones">
            <div class="panel-header">
              <h3>Direcciones</h3>
              <p>Direcciones asociadas a la empresa.</p>
            </div>

            <div class="entity-stack" id="direccionesList"></div>
            <div>
              <button type="button" class="edit-toggle" data-add-item="#direccionesList" data-template="#direccionTemplate">Añadir dirección</button>
            </div>
          </section>

          <section class="panel empresa-block empresa-block-contactos">
            <div class="panel-header">
              <h3>Personas de contacto</h3>
              <p>Personas con las que se gestiona la comunicación habitual.</p>
            </div>
            <div class="entity-section-header">
              <button type="button" class="edit-toggle" id="addContactoButton">Añadir persona de contacto</button>
            </div>
            <div class="entity-stack" id="contactosList"></div>
          </section>

          <section class="panel empresa-block empresa-block-tutores">
            <div class="panel-header">
              <h3>Tutores de la empresa</h3>
              <p>Tutores vinculados para el seguimiento de prácticas.</p>
            </div>
            <div class="entity-section-header">
              <button type="button" class="edit-toggle" id="addTutorButton">Añadir tutor</button>
            </div>
            <div class="entity-stack" id="tutoresList"></div>
          </section>
        </div>

        <template id="telefonoEmpresaTemplate">
          <div class="entity-repeatable-item empresa-inline-item">
            <div class="entity-inline-grid empresa-contact-item-grid">
              <label>
                Número
                <input type="text" name="telefonos_empresa[__INDEX__][telefono]">
              </label>
              <label>
                Etiqueta
                <select name="telefonos_empresa[__INDEX__][etiqueta]">
                  <option value="Trabajo">Trabajo</option>
                  <option value="Personal">Personal</option>
                  <option value="Otro">Otro</option>
                </select>
              </label>
              <button type="button" class="ghost-button" data-remove-item>Eliminar</button>
            </div>
          </div>
        </template>

        <template id="correoEmpresaTemplate">
          <div class="entity-repeatable-item empresa-inline-item">
            <div class="entity-inline-grid empresa-contact-item-grid">
              <label>
                Correo
                <input type="email" name="correos_empresa[__INDEX__][correo]">
              </label>
              <label>
                Etiqueta
                <select name="correos_empresa[__INDEX__][etiqueta]">
                  <option value="Trabajo">Trabajo</option>
                  <option value="Personal">Personal</option>
                  <option value="Otro">Otro</option>
                </select>
              </label>
              <button type="button" class="ghost-button" data-remove-item>Eliminar</button>
            </div>
          </div>
        </template>

        <template id="direccionTemplate">
          <div class="entity-repeatable-item empresa-direccion-item">
            <!-- MODIFICADO: mantener id_direccion para actualizar sin recrear la dirección -->
            <input type="hidden" name="direcciones[__INDEX__][id_direccion]">
            <div class="entity-grid empresa-direccion-grid">
              <div class="entity-grid entity-grid--3">
                <label class="direccion-etiqueta">
                  Etiqueta
                  <select name="direcciones[__INDEX__][etiqueta]">
                    <option value="Principal">Principal</option>
                    <option value="Centro de Trabajo">Centro de Trabajo</option>
                  </select>
                </label>
                <label class="direccion-via">
                  Tipo de vía
                  <select name="direcciones[__INDEX__][id_via]">
                    <option value="">Selecciona</option>
                    <?php foreach ($via_options as $via): ?>
                      <option value="<?php echo (int) $via['id_via']; ?>"><?php echo htmlspecialchars($via['via'], ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                  </select>
                </label>
                <label class="direccion-nombre-via">
                  Nombre de la vía
                  <input type="text" name="direcciones[__INDEX__][nombre_via]">
                </label>
              </div>
              <div class="entity-grid empresa-direccion-grid-row--5">
                <label class="direccion-numero">
                  Número
                  <input type="text" name="direcciones[__INDEX__][numero]">
                </label>
                <label class="direccion-bloque">
                  Bloque
                  <input type="text" name="direcciones[__INDEX__][bloque]">
                </label>
                <label class="direccion-escalera">
                  Escalera
                  <input type="text" name="direcciones[__INDEX__][escalera]">
                </label>
                <label class="direccion-puerta">
                  Puerta
                  <input type="text" name="direcciones[__INDEX__][puerta]">
                </label>
                <label class="direccion-planta">
                  Planta
                  <input type="text" name="direcciones[__INDEX__][planta]">
                </label>
              </div>
              <div class="entity-grid entity-grid--3">
                <label class="direccion-pais">
                  País
                  <select name="direcciones[__INDEX__][id_pais]">
                    <option value="">Selecciona</option>
                    <?php foreach ($pais_options as $pais): ?>
                      <option value="<?php echo (int) $pais['id_pais']; ?>"><?php echo htmlspecialchars($pais['pais'], ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                  </select>
                </label>
                <label class="direccion-cp">
                  Código postal
                  <input type="text" name="direcciones[__INDEX__][cp]">
                </label>
              </div>
              <div class="entity-grid entity-grid--3">
                <label class="direccion-provincia">
                  Provincia
                  <select name="direcciones[__INDEX__][id_provincia]">
                    <option value="">Selecciona</option>
                    <?php foreach ($provincia_options as $provincia): ?>
                      <option value="<?php echo (int) $provincia['id_provincia']; ?>"><?php echo htmlspecialchars($provincia['nombre'], ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                  </select>
                </label>
                <label class="direccion-localidad">
                  Localidad
                  <input type="text" name="direcciones[__INDEX__][localidad]" autocomplete="off">
                  <input type="hidden" name="direcciones[__INDEX__][id_localidad]">
                </label>
              </div>
              <div class="entity-grid entity-grid--full">
                <label class="direccion-otros">
                  Otros
                  <input type="text" name="direcciones[__INDEX__][otros]">
                </label>
              </div>
            </div>
            <button type="button" class="ghost-button" data-remove-item>Eliminar dirección</button>
          </div>
        </template>

        <?php // MODIFICADO: eliminado botón "Cancelar" ?>
        <?php // MODIFICADO: eliminado bloque inferior .form-actions ?>
      </form>
      <?php endif; ?>
    </main>
  </div>

  <script>
    (() => {
      const formElement = document.getElementById('empresaForm');
      if (!formElement) {
        return;
      }

      const normalizeText = (value) => (value || '')
      .toString()
      .trim()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .toLowerCase();

    const empresaNombreInput = document.querySelector('input[name="empresa[nombre]"]'); // MODIFICADO
    const empresaApellido1Input = document.querySelector('input[name="empresa[apellido1]"]'); // MODIFICADO
    const empresaApellido2Input = document.querySelector('input[name="empresa[apellido2]"]'); // MODIFICADO
    const empresaNombreComercialInput = document.querySelector('input[name="empresa[nombre_comercial]"]'); // MODIFICADO

    const composeNombreComercial = () => [ // MODIFICADO
      empresaNombreInput?.value?.trim() || '', // MODIFICADO
      empresaApellido1Input?.value?.trim() || '', // MODIFICADO
      empresaApellido2Input?.value?.trim() || '', // MODIFICADO
    ].filter(Boolean).join(' ').trim(); // MODIFICADO

    const syncNombreComercial = () => { // MODIFICADO
      if (!empresaNombreComercialInput || empresaNombreComercialInput.dataset.userEdited === '1') { // MODIFICADO
        return; // MODIFICADO
      }

      empresaNombreComercialInput.value = composeNombreComercial(); // MODIFICADO
    };

    if (empresaNombreComercialInput) { // MODIFICADO
      const initialAutoValue = composeNombreComercial(); // MODIFICADO
      const initialCurrentValue = (empresaNombreComercialInput.value || '').trim(); // MODIFICADO
      if (initialCurrentValue !== '' && initialCurrentValue !== initialAutoValue) { // MODIFICADO
        empresaNombreComercialInput.dataset.userEdited = '1'; // MODIFICADO
      }

      empresaNombreComercialInput.addEventListener('input', () => { // MODIFICADO
        empresaNombreComercialInput.dataset.userEdited = '1'; // MODIFICADO
      });
    }

    [empresaNombreInput, empresaApellido1Input, empresaApellido2Input].forEach((input) => { // MODIFICADO
      if (!input) { // MODIFICADO
        return; // MODIFICADO
      }

      input.addEventListener('input', syncNombreComercial); // MODIFICADO
    });

    syncNombreComercial(); // MODIFICADO

    const paisIdToCode = <?php echo json_encode($pais_id_to_code, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const initialFormValues = <?php echo json_encode($form_values, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

    const resolveCountryCode = async (paisSelect) => {
      if (!paisSelect || !paisSelect.value) {
        return '';
      }

      const idPais = String(paisSelect.value);
      const inlineCode = paisIdToCode[idPais] || '';
      if (inlineCode) {
        return inlineCode;
      }

      const data = await fetchJson(`empresa_editar.php?action=country_code&id_pais=${encodeURIComponent(idPais)}`);
      const code = (data?.ok ? String(data.code || '').trim().toUpperCase() : '');
      if (code) {
        paisIdToCode[idPais] = code;
      }

      return code;
    };

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

    const setCpLookupLoading = (direccionItem, isLoading) => {
      if (!direccionItem) {
        return;
      }

      const cpInput = direccionItem.querySelector('input[name*="[cp]"]');
      const cpLabel = cpInput?.closest('label');
      if (!cpLabel) {
        return;
      }

      let status = cpLabel.querySelector('[data-cp-loading-status]');
      if (!status) {
        status = document.createElement('small');
        status.setAttribute('data-cp-loading-status', '1');
        status.hidden = true;
        cpLabel.appendChild(status);
      }

      status.hidden = !isLoading;
      status.textContent = isLoading ? '⏳ Cargando datos de dirección…' : '';
    };

    const fetchProvinciaPorCpPrefix = async (prefix) => {
      if (!prefix || prefix.length < 2) {
        return null;
      }

      const data = await fetchJson(
        `empresa_editar.php?action=provincia_por_cp_prefix&prefix=${encodeURIComponent(prefix)}`
      );

      return data?.ok ? data.id_provincia : null;
    };

    const getOrCreateLocalidad = async (idProvincia, cp, nombreLocalidad) => {
      if (!idProvincia || !nombreLocalidad) {
        return null;
      }

      const body = new URLSearchParams({
        id_provincia: String(idProvincia),
        cp: String(cp || ''),
        nombre_localidad: String(nombreLocalidad || ''),
      });

      const data = await fetchJson('empresa_editar.php?action=localidad_get_or_create', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: body.toString(),
      });

      return data?.ok ? data : null;
    };

    const normalizePostalCode = (value) => String(value || '').trim().replace(/[\s-]+/g, '');

    const resetDireccionLocalidad = (direccionItem) => {
      if (!direccionItem) {
        return;
      }

      const localidadInput = direccionItem.querySelector('input[name*="[localidad]"]');
      const idLocalidadInput = direccionItem.querySelector('input[name*="[id_localidad]"]');

      if (localidadInput) {
        localidadInput.value = '';
      }

      if (idLocalidadInput) {
        idLocalidadInput.value = '';
      }
    };

    const fetchAddressFromPostalCode = async (direccionItem, options = {}) => {
      if (!direccionItem) {
        return;
      }

      const forceRefresh = options.forceRefresh === true;

      const paisSelect = direccionItem.querySelector('select[name*="[id_pais]"]');
      const cpInput = direccionItem.querySelector('input[name*="[cp]"]');
      const provinciaSelect = direccionItem.querySelector('select[name*="[id_provincia]"]');
      const localidadInput = direccionItem.querySelector('input[name*="[localidad]"]');
      const idLocalidadInput = direccionItem.querySelector('input[name*="[id_localidad]"]');

      const postalCode = normalizePostalCode(cpInput?.value || '');
      const idPais = (paisSelect?.value || '').trim();
      const prefix = postalCode.slice(0, 2);
      const cpChanged = direccionItem.dataset.lastCp !== postalCode;

      if (!idPais || !postalCode || postalCode.length < 2) {
        return;
      }

      if (cpChanged) {
        direccionItem.dataset.lastCp = postalCode;
        resetDireccionLocalidad(direccionItem);
        delete direccionItem.dataset.lastLookupKey;
      }

      setCpLookupLoading(direccionItem, true);

      try {
        const countryCode = await resolveCountryCode(paisSelect);
        if (!countryCode) {
          return;
        }

        const lookupKey = `${idPais}-${postalCode}`;
        if (!cpChanged && !forceRefresh && direccionItem.dataset.lastLookupKey === lookupKey) {
          return;
        }

        direccionItem.dataset.lastLookupKey = lookupKey;
        direccionItem.dataset.lookupKey = lookupKey;

        const provinciaWasEmpty = provinciaSelect ? !provinciaSelect.value : false;
        const idProvincia = await fetchProvinciaPorCpPrefix(prefix);
        if (idProvincia && provinciaSelect && (provinciaWasEmpty || provinciaSelect.value !== String(idProvincia))) {
          provinciaSelect.value = String(idProvincia);
        }

        const response = await fetch(`https://api.zippopotam.us/${encodeURIComponent(countryCode)}/${encodeURIComponent(postalCode)}`);
        if (!response.ok || direccionItem.dataset.lookupKey !== lookupKey) {
          return;
        }

        const data = await response.json();
        const place = Array.isArray(data.places) && data.places.length > 0 ? data.places[0] : null;
        if (!place || direccionItem.dataset.lookupKey !== lookupKey) {
          return;
        }

        const localidadName = String(place['place name'] || '').trim();
        if (!localidadName) {
          return;
        }

        if (localidadInput) {
          localidadInput.value = localidadName;
        }

        const provinciaParaLocalidad = provinciaSelect?.value ? Number(provinciaSelect.value) : 0;
        if (!provinciaParaLocalidad) {
          return;
        }

        const localidadDb = await getOrCreateLocalidad(provinciaParaLocalidad, postalCode, localidadName);
        if (!localidadDb || direccionItem.dataset.lookupKey !== lookupKey) {
          return;
        }

        if (idLocalidadInput) {
          idLocalidadInput.value = localidadDb.id_localidad ? String(localidadDb.id_localidad) : '';
        }

        if (localidadInput && localidadDb.nombre) {
          localidadInput.value = String(localidadDb.nombre);
        }
      } catch (_) {
        // Silencio intencional para no mostrar errores técnicos.
      } finally {
        setCpLookupLoading(direccionItem, false);
      }
    };

    const replaceIndex = (value, index) => value.replaceAll('__INDEX__', String(index));

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
      if (removeButton) {
        const wrapper = removeButton.closest('.entity-repeatable-item');
        if (wrapper) {
          wrapper.remove();
        }
      }

    });

    document.addEventListener('blur', (event) => {
      const cpInput = event.target.closest('.empresa-direccion-item input[name*="[cp]"]');
      if (cpInput) {
        fetchAddressFromPostalCode(cpInput.closest('.empresa-direccion-item'));
      }
    }, true);

    document.addEventListener('change', (event) => {
      const cpInput = event.target.closest('.empresa-direccion-item input[name*="[cp]"]');
      if (cpInput) {
        fetchAddressFromPostalCode(cpInput.closest('.empresa-direccion-item'));
        return;
      }

      const provinciaSelect = event.target.closest('.empresa-direccion-item select[name*="[id_provincia]"]');
      if (!provinciaSelect || !event.isTrusted) {
        return;
      }

      const direccionItem = provinciaSelect.closest('.empresa-direccion-item');
      resetDireccionLocalidad(direccionItem);
      fetchAddressFromPostalCode(direccionItem, { forceRefresh: true });
    });

    document.addEventListener('input', (event) => {
      const localidadInput = event.target.closest('.empresa-direccion-item input[name*="[localidad]"]');
      if (!localidadInput || !event.isTrusted) {
        return;
      }

      const direccionItem = localidadInput.closest('.empresa-direccion-item');
      const idLocalidadInput = direccionItem?.querySelector('input[name*="[id_localidad]"]');
      if (idLocalidadInput) {
        idLocalidadInput.value = '';
      }
    });

    const createPhoneBlock = (namePrefix) => {
      const wrapper = document.createElement('div');
      wrapper.className = 'entity-repeatable-item empresa-inline-item';
      wrapper.innerHTML = `
        <div class="entity-inline-grid empresa-contact-item-grid">
          <label>
            Número
            <input type="text" name="${namePrefix}[telefonos][__INDEX__][telefono]">
          </label>
          <label>
            Etiqueta
            <select name="${namePrefix}[telefonos][__INDEX__][etiqueta]">
              <option value="Trabajo">Trabajo</option>
              <option value="Personal">Personal</option>
              <option value="Otro">Otro</option>
            </select>
          </label>
          <button type="button" class="ghost-button" data-remove-item>Eliminar</button>
        </div>
      `;
      return wrapper;
    };

    const createEmailBlock = (namePrefix) => {
      const wrapper = document.createElement('div');
      wrapper.className = 'entity-repeatable-item empresa-inline-item';
      wrapper.innerHTML = `
        <div class="entity-inline-grid empresa-contact-item-grid">
          <label>
            Correo
            <input type="email" name="${namePrefix}[correos][__INDEX__][correo]">
          </label>
          <label>
            Etiqueta
            <select name="${namePrefix}[correos][__INDEX__][etiqueta]">
              <option value="Trabajo">Trabajo</option>
              <option value="Personal">Personal</option>
              <option value="Otro">Otro</option>
            </select>
          </label>
          <button type="button" class="ghost-button" data-remove-item>Eliminar</button>
        </div>
      `;
      return wrapper;
    };

    const attachNestedControls = (entityCard, entityType, entityIndex) => {
      const phoneList = entityCard.querySelector('.nested-phone-list');
      const emailList = entityCard.querySelector('.nested-email-list');
      const addPhone = entityCard.querySelector('.nested-add-phone');
      const addEmail = entityCard.querySelector('.nested-add-email');

      const entityPrefix = `${entityType}[${entityIndex}]`;

      const addNestedBlock = (list, blockFactory) => {
        const blockIndex = Number(list.dataset.nextIndex || 0);
        const block = blockFactory(entityPrefix);
        block.innerHTML = replaceIndex(block.innerHTML, blockIndex);
        list.appendChild(block);
        list.dataset.nextIndex = String(blockIndex + 1);
      };

      addPhone.addEventListener('click', () => addNestedBlock(phoneList, createPhoneBlock));
      addEmail.addEventListener('click', () => addNestedBlock(emailList, createEmailBlock));
    };

    const createEntityCard = (entityType, entityIndex, extraFieldLabel, extraFieldName) => {
      const card = document.createElement('div');
      card.className = 'entity-repeatable-item entity-card';
      // MODIFICADO: mantener id_empresas_tutor en tutores existentes.
      card.innerHTML = `
        <div class="entity-section-header">
          <h4>${entityType === 'contactos' ? 'Persona de contacto' : 'Tutor de empresa'} #${entityIndex + 1}</h4>
          <button type="button" class="ghost-button" data-remove-item>Eliminar</button>
        </div>
        <div class="entity-grid">
          <label>
            Nombre
            <input type="text" name="${entityType}[${entityIndex}][nombre]">
          </label>
          <label>
            Apellido 1
            <input type="text" name="${entityType}[${entityIndex}][apellido1]">
          </label>
          <label>
            Apellido 2
            <input type="text" name="${entityType}[${entityIndex}][apellido2]">
          </label>
          <label>
            ${extraFieldLabel}
            <input type="text" name="${entityType}[${entityIndex}][${extraFieldName}]">
          </label>
          ${entityType === 'tutores' ? `<input type="hidden" name="tutores[${entityIndex}][id_empresas_tutor]">` : ''}
        </div>
        <div class="grid empresa-medios-grid">
          <section class="entity-nested-section">
            <div class="entity-section-header">
              <h4>Teléfonos</h4>
              <button type="button" class="edit-toggle nested-add-phone">Añadir teléfono</button>
            </div>
            <div class="entity-stack nested-phone-list"></div>
          </section>
          <section class="entity-nested-section">
            <div class="entity-section-header">
              <h4>Correos</h4>
              <button type="button" class="edit-toggle nested-add-email">Añadir correo</button>
            </div>
            <div class="entity-stack nested-email-list"></div>
          </section>
        </div>
      `;
      return card;
    };

    const addContacto = () => {
      const list = document.getElementById('contactosList');
      const entityIndex = Number(list.dataset.nextIndex || 0);
      const card = createEntityCard('contactos', entityIndex, 'Cargo', 'cargo');
      list.appendChild(card);
      list.dataset.nextIndex = String(entityIndex + 1);
      attachNestedControls(card, 'contactos', entityIndex);
      return card;
    };

    const addTutor = () => {
      const list = document.getElementById('tutoresList');
      const entityIndex = Number(list.dataset.nextIndex || 0);
      const card = createEntityCard('tutores', entityIndex, 'DNI', 'dni');
      list.appendChild(card);
      list.dataset.nextIndex = String(entityIndex + 1);
      attachNestedControls(card, 'tutores', entityIndex);
      return card;
    };

    const setInputValue = (scope, selector, value) => {
      const input = scope.querySelector(selector);
      if (input) {
        input.value = value || '';
      }
    };

    const createAndFillNested = (card, listSelector, addButtonSelector, fieldName, items) => {
      const list = card.querySelector(listSelector);
      const addButton = card.querySelector(addButtonSelector);
      if (!list || !addButton || !Array.isArray(items)) {
        return;
      }

      items.forEach((item) => {
        addButton.click();
        const nestedItem = list.lastElementChild;
        if (!nestedItem) {
          return;
        }

        setInputValue(nestedItem, `input[name$="[${fieldName}]"]`, item?.[fieldName] || '');
        setInputValue(nestedItem, 'select[name$="[etiqueta]"]', item?.etiqueta || '');
      });
    };

    const populateInitialData = () => {
      const telefonosEmpresa = Array.isArray(initialFormValues.telefonos_empresa) ? initialFormValues.telefonos_empresa : [];
      const correosEmpresa = Array.isArray(initialFormValues.correos_empresa) ? initialFormValues.correos_empresa : [];
      const direcciones = Array.isArray(initialFormValues.direcciones) ? initialFormValues.direcciones : [];
      const contactos = Array.isArray(initialFormValues.contactos) ? initialFormValues.contactos : [];
      const tutores = Array.isArray(initialFormValues.tutores) ? initialFormValues.tutores : [];

      telefonosEmpresa.forEach((telefono) => {
        const item = addItemFromTemplate('#telefonosEmpresaList', '#telefonoEmpresaTemplate');
        if (!item) {
          return;
        }

        setInputValue(item, 'input[name$="[telefono]"]', telefono?.telefono || '');
        setInputValue(item, 'select[name$="[etiqueta]"]', telefono?.etiqueta || '');
      });

      correosEmpresa.forEach((correo) => {
        const item = addItemFromTemplate('#correosEmpresaList', '#correoEmpresaTemplate');
        if (!item) {
          return;
        }

        setInputValue(item, 'input[name$="[correo]"]', correo?.correo || '');
        setInputValue(item, 'select[name$="[etiqueta]"]', correo?.etiqueta || '');
      });

      direcciones.forEach((direccion) => {
        const item = addItemFromTemplate('#direccionesList', '#direccionTemplate');
        if (!item) {
          return;
        }

        setInputValue(item, 'select[name$="[etiqueta]"]', direccion?.etiqueta || '');
        setInputValue(item, 'select[name$="[id_via]"]', direccion?.id_via || '');
        setInputValue(item, 'input[name$="[nombre_via]"]', direccion?.nombre_via || '');
        setInputValue(item, 'input[name$="[numero]"]', direccion?.numero || '');
        setInputValue(item, 'input[name$="[bloque]"]', direccion?.bloque || '');
        setInputValue(item, 'input[name$="[escalera]"]', direccion?.escalera || '');
        setInputValue(item, 'input[name$="[planta]"]', direccion?.planta || '');
        setInputValue(item, 'input[name$="[puerta]"]', direccion?.puerta || '');
        setInputValue(item, 'select[name$="[id_pais]"]', direccion?.id_pais || '');
        setInputValue(item, 'input[name$="[cp]"]', direccion?.cp || '');
        setInputValue(item, 'select[name$="[id_provincia]"]', direccion?.id_provincia || '');
        setInputValue(item, 'input[name$="[localidad]"]', direccion?.localidad || '');
        setInputValue(item, 'input[name$="[id_localidad]"]', direccion?.id_localidad || '');
        setInputValue(item, 'input[name$="[otros]"]', direccion?.otros || '');
      });

      contactos.forEach((contacto) => {
        const card = addContacto();
        if (!card) {
          return;
        }

        setInputValue(card, 'input[name$="[nombre]"]', contacto?.nombre || '');
        setInputValue(card, 'input[name$="[apellido1]"]', contacto?.apellido1 || '');
        setInputValue(card, 'input[name$="[apellido2]"]', contacto?.apellido2 || '');
        setInputValue(card, 'input[name$="[cargo]"]', contacto?.cargo || '');
        createAndFillNested(card, '.nested-phone-list', '.nested-add-phone', 'telefono', contacto?.telefonos || []);
        createAndFillNested(card, '.nested-email-list', '.nested-add-email', 'correo', contacto?.correos || []);
      });

      tutores.forEach((tutor) => {
        const card = addTutor();
        if (!card) {
          return;
        }

        setInputValue(card, 'input[name$="[id_empresas_tutor]"]', tutor?.id_empresas_tutor || ''); // MODIFICADO
        setInputValue(card, 'input[name$="[nombre]"]', tutor?.nombre || '');
        setInputValue(card, 'input[name$="[apellido1]"]', tutor?.apellido1 || '');
        setInputValue(card, 'input[name$="[apellido2]"]', tutor?.apellido2 || '');
        setInputValue(card, 'input[name$="[dni]"]', tutor?.dni || '');
        createAndFillNested(card, '.nested-phone-list', '.nested-add-phone', 'telefono', tutor?.telefonos || []);
        createAndFillNested(card, '.nested-email-list', '.nested-add-email', 'correo', tutor?.correos || []);
      });
    };

    document.getElementById('addContactoButton').addEventListener('click', addContacto);
    document.getElementById('addTutorButton').addEventListener('click', addTutor);

    populateInitialData();
    })();
  </script>
</body>
</html>
