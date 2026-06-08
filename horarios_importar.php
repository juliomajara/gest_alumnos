<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pdo = db();

$page_title = 'Importar horarios | Gestor de Alumnos';
$active_page = 'horarios';

$messages = [];
$errors = [];
$result = [
  'insertados' => 0,
  'eliminados' => 0,
  'filas_ignoradas' => 0,
  'grupo_detectado' => '',
  'curso_usado' => '',
  'modulos_no_encontrados' => [],
  'tramos_no_encontrados' => [],
  'errores' => [],
  'avisos' => [],
  'modulos_horas_descuadradas' => [],
];

function normalize_csv_text(string $value): string {
  $value = str_replace("\xEF\xBB\xBF", '', $value);
  $value = str_replace("\xc2\xa0", ' ', $value);
  $value = trim($value);

  if ($value === '') {
    return '';
  }

  if (!mb_check_encoding($value, 'UTF-8')) {
    $converted = @mb_convert_encoding($value, 'UTF-8', 'Windows-1252,ISO-8859-1,UTF-8');
    if (is_string($converted) && $converted !== '') {
      $value = $converted;
    }
  }

  return trim($value);
}

function normalize_key(string $value): string {
  $value = normalize_csv_text($value);
  if ($value === '') {
    return '';
  }

  $value = mb_strtolower($value, 'UTF-8');
  $translit = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
  if ($translit !== false) {
    $value = mb_strtolower($translit, 'UTF-8');
  }

  $value = preg_replace('/[^a-z0-9]+/u', '', $value) ?? $value;
  return trim($value);
}

function parse_time_range(string $raw): ?array {
  if (preg_match('/(\d{1,2})[:\.](\d{2})\s*[-–]\s*(\d{1,2})[:\.](\d{2})/', $raw, $m)) {
    return [sprintf('%02d:%02d:00', (int) $m[1], (int) $m[2]), sprintf('%02d:%02d:00', (int) $m[3], (int) $m[4])];
  }
  return null;
}

function split_tramo_tokens(string $raw): array {
  $raw = trim($raw);
  if ($raw === '') {
    return [];
  }

  $parts = preg_split('/\s*[\/,;]+\s*/u', $raw) ?: [];
  $tokens = [];
  foreach ($parts as $part) {
    $part = trim((string) $part);
    if ($part !== '') {
      $tokens[] = $part;
    }
  }

  if ($tokens === []) {
    return [$raw];
  }

  return $tokens;
}

function resolve_tramo_ids_from_csv(string $tramoRaw, array $tramosRows, array $tramosByRange, array $tramosByToken): array {
  $resolved = [];
  $tokens = split_tramo_tokens($tramoRaw);
  if ($tokens === []) {
    $tokens = [$tramoRaw];
  }

  foreach ($tokens as $token) {
    $range = parse_time_range($token);
    if ($range !== null) {
      $rangeKey = $range[0] . '-' . $range[1];
      if (isset($tramosByRange[$rangeKey])) {
        $resolved[] = (int) $tramosByRange[$rangeKey];
        continue;
      }

      $matches = [];
      foreach ($tramosRows as $tramo) {
        $horaInicio = (string) ($tramo['hora_inicio'] ?? '');
        $horaFin = (string) ($tramo['hora_fin'] ?? '');
        if ($horaInicio >= $range[0] && $horaFin <= $range[1]) {
          $matches[] = (int) $tramo['id_horario_tramo'];
        }
      }

      if ($matches !== []) {
        foreach ($matches as $id) {
          $resolved[] = $id;
        }
        continue;
      }
    }

    $tramoId = null;
    $tk = normalize_key($token);
    if (isset($tramosByToken[$tk])) {
      $tramoId = (int) $tramosByToken[$tk];
    }

    if ($tramoId === null && preg_match('/\d+/', $token, $nm)) {
      $numKey = normalize_key($nm[0]);
      if ($numKey !== '' && isset($tramosByToken[$numKey])) {
        $tramoId = (int) $tramosByToken[$numKey];
      }
    }

    if ($tramoId !== null) {
      $resolved[] = $tramoId;
    }
  }

  $resolved = array_values(array_unique($resolved));
  sort($resolved);
  return $resolved;
}

$cursos_escolares = $pdo->query('SELECT id_curso_escolar, curso_escolar, activo FROM cursos_escolares ORDER BY activo DESC, id_curso_escolar DESC')->fetchAll(PDO::FETCH_ASSOC);
$grupos = $pdo->query('SELECT id_grupo, grupo FROM grupos ORDER BY grupo')->fetchAll(PDO::FETCH_ASSOC);

$defaultCurso = 0;
foreach ($cursos_escolares as $cursoItem) {
  if ((int) ($cursoItem['activo'] ?? 0) === 1) {
    $defaultCurso = (int) $cursoItem['id_curso_escolar'];
    break;
  }
}
if ($defaultCurso <= 0 && isset($cursos_escolares[0]['id_curso_escolar'])) {
  $defaultCurso = (int) $cursos_escolares[0]['id_curso_escolar'];
}

$default_group_id = (int) ($pdo->query("SELECT valor FROM `config` WHERE `clave` = 'grupo_por_defecto' LIMIT 1")->fetchColumn() ?: 0);
$selected = [
  'id_curso_escolar' => (int) ($_POST['id_curso_escolar'] ?? ($_GET['id_curso_escolar'] ?? $defaultCurso)),
  'id_grupo' => (int) ($_POST['id_grupo'] ?? ($_GET['id_grupo'] ?? $default_group_id)),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if ($selected['id_curso_escolar'] <= 0 || $selected['id_grupo'] <= 0) {
    $errors[] = 'Debes seleccionar curso escolar y grupo.';
  }

  if (!isset($_FILES['csv_horarios']) || !is_uploaded_file($_FILES['csv_horarios']['tmp_name'])) {
    $errors[] = 'Debes seleccionar un archivo CSV válido.';
  }

  $cursoStmt = $pdo->prepare('SELECT curso_escolar FROM cursos_escolares WHERE id_curso_escolar = :id LIMIT 1');
  $cursoStmt->execute(['id' => $selected['id_curso_escolar']]);
  $cursoRow = $cursoStmt->fetch(PDO::FETCH_ASSOC);
  if (!$cursoRow) {
    $errors[] = 'El curso escolar seleccionado no es válido.';
  } else {
    $result['curso_usado'] = (string) $cursoRow['curso_escolar'];
  }

  $grupoStmt = $pdo->prepare('SELECT grupo FROM grupos WHERE id_grupo = :id LIMIT 1');
  $grupoStmt->execute(['id' => $selected['id_grupo']]);
  $grupoRow = $grupoStmt->fetch(PDO::FETCH_ASSOC);
  if (!$grupoRow) {
    $errors[] = 'El grupo seleccionado no es válido.';
  }

  if ($errors === []) {
    $targetGroup = (string) ($grupoRow['grupo'] ?? '');
    $targetGroupKey = normalize_key($targetGroup);

    $modulesRows = $pdo->query('SELECT id_modulo, codigo, abreviatura, materia_general, materia_propia, horas_semanales FROM modulos')->fetchAll(PDO::FETCH_ASSOC);
    $moduleMap = [];
    $moduleInfo = [];
    foreach ($modulesRows as $m) {
      $idModulo = (int) $m['id_modulo'];
      $horasSemanales = null;
      if (isset($m['horas_semanales']) && $m['horas_semanales'] !== null) {
        $horasSemanales = (int) $m['horas_semanales'];
      }
      $moduleInfo[$idModulo] = [
        'codigo' => (string) ($m['codigo'] ?? ''),
        'abreviatura' => (string) ($m['abreviatura'] ?? ''),
        'materia_general' => (string) ($m['materia_general'] ?? ''),
        'materia_propia' => (string) ($m['materia_propia'] ?? ''),
        'horas_semanales' => $horasSemanales,
      ];
      $candidates = [
        (string) ($m['codigo'] ?? ''),
        (string) ($m['abreviatura'] ?? ''),
        (string) ($m['materia_general'] ?? ''),
        (string) ($m['materia_propia'] ?? ''),
      ];
      foreach ($candidates as $candidate) {
        $k = normalize_key($candidate);
        if ($k !== '' && !isset($moduleMap[$k])) {
          $moduleMap[$k] = $idModulo;
        }
      }
    }

    $tramosRows = $pdo->query('SELECT id_horario_tramo, numero_tramo, nombre, hora_inicio, hora_fin FROM horarios_tramos ORDER BY numero_tramo')->fetchAll(PDO::FETCH_ASSOC);
    $tramosByRange = [];
    $tramosByToken = [];
    $tramosNumero = [];
    foreach ($tramosRows as $t) {
      $idTramo = (int) $t['id_horario_tramo'];
      $tramosNumero[$idTramo] = (int) ($t['numero_tramo'] ?? 0);
      $rangeKey = (string) $t['hora_inicio'] . '-' . (string) $t['hora_fin'];
      $tramosByRange[$rangeKey] = $idTramo;

      $tokens = [
        (string) ($t['numero_tramo'] ?? ''),
        'tramo ' . (string) ($t['numero_tramo'] ?? ''),
        (string) ($t['nombre'] ?? ''),
      ];
      foreach ($tokens as $token) {
        $k = normalize_key($token);
        if ($k !== '' && !isset($tramosByToken[$k])) {
          $tramosByToken[$k] = $idTramo;
        }
      }
    }

    $columnsStmt = $pdo->query('SHOW COLUMNS FROM horarios_grupos');
    $hasProfesorColumn = false;
    $allowNullProfesor = true;
    foreach ($columnsStmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
      if ((string) $col['Field'] === 'id_profesor') {
        $hasProfesorColumn = true;
        $allowNullProfesor = strtoupper((string) ($col['Null'] ?? 'YES')) === 'YES';
      }
    }

    $handle = fopen($_FILES['csv_horarios']['tmp_name'], 'rb');
    if (!$handle) {
      $errors[] = 'No se pudo abrir el archivo CSV.';
    } else {
      $header = fgetcsv($handle, 0, ',', '"', '\\');
      if (!is_array($header) || count($header) < 2) {
        $errors[] = 'La cabecera del CSV no es válida.';
      } else {
        $header = array_map(static fn($v): string => normalize_csv_text((string) $v), $header);
        $headerMap = [];
        foreach ($header as $idx => $name) {
          $headerMap[normalize_key($name)] = $idx;
        }

        $idxUnidad = $headerMap['unidad'] ?? null;
        $idxMateriaGeneral = $headerMap['materiageneral'] ?? null;
        $idxDMateria = $headerMap['dmateria'] ?? null;
        $idxXMateriaOmg = $headerMap['xmateriaomg'] ?? null;

        $dayCols = [
          1 => $headerMap['tramol'] ?? null,
          2 => $headerMap['tramom'] ?? null,
          3 => $headerMap['tramox'] ?? null,
          4 => $headerMap['tramoj'] ?? null,
          5 => $headerMap['tramov'] ?? null,
        ];

        if ($idxUnidad === null) {
          $errors[] = 'No se ha encontrado la columna UNIDAD en el CSV.';
        }

        $hasAnyDay = false;
        foreach ($dayCols as $idx) {
          if ($idx !== null) {
            $hasAnyDay = true;
            break;
          }
        }
        if (!$hasAnyDay) {
          $errors[] = 'No se ha encontrado ninguna columna de tramos (TRAMO_L...TRAMO_V).';
        }

        $slots = [];
        $line = 1;
        while ($errors === [] && ($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
          $line++;
          $unidadCsv = normalize_csv_text((string) ($row[(int) $idxUnidad] ?? ''));
          if ($unidadCsv === '') {
            $result['filas_ignoradas']++;
            continue;
          }

          if (normalize_key($unidadCsv) !== $targetGroupKey) {
            $result['filas_ignoradas']++;
            continue;
          }

          $result['grupo_detectado'] = $unidadCsv;

          $moduleRaw = '';
          foreach ([$idxXMateriaOmg, $idxDMateria, $idxMateriaGeneral] as $modIdx) {
            if ($modIdx !== null) {
              $candidate = normalize_csv_text((string) ($row[(int) $modIdx] ?? ''));
              if ($candidate !== '') {
                $moduleRaw = $candidate;
                break;
              }
            }
          }

          if ($moduleRaw === '') {
            $result['filas_ignoradas']++;
            continue;
          }

          $moduleId = $moduleMap[normalize_key($moduleRaw)] ?? null;
          if ($moduleId === null) {
            $result['modulos_no_encontrados'][] = $moduleRaw;
            $result['filas_ignoradas']++;
            continue;
          }

          foreach ($dayCols as $dia => $colIdx) {
            if ($colIdx === null) {
              continue;
            }

            $tramoRaw = normalize_csv_text((string) ($row[(int) $colIdx] ?? ''));
            if ($tramoRaw === '') {
              continue;
            }

            $tramoIds = resolve_tramo_ids_from_csv($tramoRaw, $tramosRows, $tramosByRange, $tramosByToken);

            if ($tramoIds === []) {
              $result['tramos_no_encontrados'][] = 'Línea ' . $line . ' [' . $tramoRaw . ']';
              continue;
            }

            $hasValidHours = isset($moduleInfo[$moduleId]['horas_semanales']) && (int) $moduleInfo[$moduleId]['horas_semanales'] > 0;
            if (!$hasValidHours) {
              $result['avisos'][] = 'Módulo sin horas semanales válidas (no se usará para inferencias): ' . $moduleRaw;
            }

            foreach ($tramoIds as $tramoId) {
              $slotKey = $dia . '-' . $tramoId;
              if (isset($slots[$slotKey]) && (int) $slots[$slotKey]['id_modulo'] !== (int) $moduleId) {
                $result['errores'][] = 'Conflicto slot en línea ' . $line . ': día ' . $dia . ', tramo ' . $tramoId . ', módulo anterior ' . $slots[$slotKey]['id_modulo'] . ', módulo nuevo ' . $moduleId;
                continue;
              }
              $slots[$slotKey] = [
                'dia_semana' => $dia,
                'id_horario_tramo' => $tramoId,
                'id_modulo' => $moduleId,
              ];
            }
          }
        }

        if ($errors === [] && $slots !== []) {
          $moduleCount = [];
          foreach ($slots as $slot) {
            $mid = (int) $slot['id_modulo'];
            $moduleCount[$mid] = ($moduleCount[$mid] ?? 0) + 1;
          }

          $tramosOrdenados = $tramosRows;
          usort($tramosOrdenados, static function (array $a, array $b): int {
            $na = (int) ($a['numero_tramo'] ?? 0);
            $nb = (int) ($b['numero_tramo'] ?? 0);
            return $na <=> $nb;
          });

          for ($dia = 1; $dia <= 5; $dia++) {
            $lastModuloId = null;

            foreach ($tramosOrdenados as $tramo) {
              $tramoId = (int) $tramo['id_horario_tramo'];
              $numeroTramo = (int) ($tramo['numero_tramo'] ?? 0);
              $slotKey = $dia . '-' . $tramoId;

              if (isset($slots[$slotKey])) {
                $lastModuloId = (int) $slots[$slotKey]['id_modulo'];
                continue;
              }

              if ($numeroTramo > 6) {
                continue;
              }

              if ($lastModuloId === null) {
                continue;
              }

              $horasEsperadas = (int) ($moduleInfo[$lastModuloId]['horas_semanales'] ?? 0);
              if ($horasEsperadas <= 0) {
                continue;
              }

              $actuales = (int) ($moduleCount[$lastModuloId] ?? 0);
              if ($actuales >= $horasEsperadas) {
                continue;
              }

              $slots[$slotKey] = [
                'dia_semana' => $dia,
                'id_horario_tramo' => $tramoId,
                'id_modulo' => $lastModuloId,
              ];
              $moduleCount[$lastModuloId] = $actuales + 1;
            }
          }
        }

        if ($errors === [] && $slots === []) {
          $errors[] = 'No se han detectado slots válidos para importar en el grupo seleccionado.';
        }

        if ($errors === [] && $slots !== []) {
          $moduleCountFinal = [];
          foreach ($slots as $slot) {
            $mid = (int) $slot['id_modulo'];
            $moduleCountFinal[$mid] = ($moduleCountFinal[$mid] ?? 0) + 1;
          }
          foreach ($moduleCountFinal as $mid => $detectadas) {
            $esperadas = (int) ($moduleInfo[(int) $mid]['horas_semanales'] ?? 0);
            if ($esperadas <= 0) {
              continue;
            }
            if ($detectadas !== $esperadas) {
              $nombre = (string) ($moduleInfo[(int) $mid]['materia_general'] ?? '');
              if ($nombre === '') {
                $nombre = (string) ($moduleInfo[(int) $mid]['materia_propia'] ?? '');
              }
              $codigoAbrev = (string) ($moduleInfo[(int) $mid]['codigo'] ?? '');
              if ($codigoAbrev === '') {
                $codigoAbrev = (string) ($moduleInfo[(int) $mid]['abreviatura'] ?? '');
              }
              $etiqueta = $nombre !== '' ? $nombre : ('Módulo ID ' . $mid);
              if ($codigoAbrev !== '') {
                $etiqueta .= ' (' . $codigoAbrev . ')';
              }
              $result['modulos_horas_descuadradas'][] = $etiqueta . ': esperadas ' . $esperadas . ' horas, detectadas ' . $detectadas . ' horas. Diferencia: ' . ($detectadas - $esperadas) . '.';
            }
          }
          if ($result['modulos_horas_descuadradas'] !== []) {
            $result['avisos'][] = 'Advertencia: el horario podría no ser correcto porque no coinciden las horas semanales de algunos módulos.';
          }
        }

        if ($errors === []) {
          $deleteStmt = $pdo->prepare('DELETE FROM horarios_grupos WHERE id_curso_escolar = :c AND id_grupo = :g');

          $insertSql = $hasProfesorColumn
            ? 'INSERT INTO horarios_grupos (id_curso_escolar, id_grupo, dia_semana, id_horario_tramo, id_modulo, id_profesor) VALUES (:c,:g,:d,:t,:m,:p)'
            : 'INSERT INTO horarios_grupos (id_curso_escolar, id_grupo, dia_semana, id_horario_tramo, id_modulo) VALUES (:c,:g,:d,:t,:m)';
          $insertStmt = $pdo->prepare($insertSql);

          try {
            $pdo->beginTransaction();
            $deleteStmt->execute(['c' => $selected['id_curso_escolar'], 'g' => $selected['id_grupo']]);
            $result['eliminados'] = $deleteStmt->rowCount();

            foreach ($slots as $slot) {
              if ($hasProfesorColumn) {
                $insertStmt->execute([
                  'c' => $selected['id_curso_escolar'],
                  'g' => $selected['id_grupo'],
                  'd' => $slot['dia_semana'],
                  't' => $slot['id_horario_tramo'],
                  'm' => $slot['id_modulo'],
                  'p' => $allowNullProfesor ? null : 0,
                ]);
              } else {
                $insertStmt->execute([
                  'c' => $selected['id_curso_escolar'],
                  'g' => $selected['id_grupo'],
                  'd' => $slot['dia_semana'],
                  't' => $slot['id_horario_tramo'],
                  'm' => $slot['id_modulo'],
                ]);
              }
              $result['insertados']++;
            }

            $pdo->commit();
            $messages[] = 'Importación de horario finalizada.';
          } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
              $pdo->rollBack();
            }
            $errors[] = 'Error durante la importación: ' . $e->getMessage();
          }
        }

        $result['modulos_no_encontrados'] = array_values(array_unique($result['modulos_no_encontrados']));
        $result['tramos_no_encontrados'] = array_values(array_unique($result['tramos_no_encontrados']));
        $result['avisos'] = array_values(array_unique($result['avisos']));
        $result['modulos_horas_descuadradas'] = array_values(array_unique($result['modulos_horas_descuadradas']));
      }

      fclose($handle);
    }
  }
}
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
          <h1>Importar horarios</h1>
          <p class="subheading">Selecciona curso escolar, grupo y carga el CSV del horario.</p>
        </div>
        <div class="header-actions">
          <a class="primary-button" href="horarios.php?id_curso_escolar=<?php echo (int) $selected['id_curso_escolar']; ?>&id_grupo=<?php echo (int) $selected['id_grupo']; ?>">Volver a horarios</a>
        </div>
      </header>

      <section class="panel">
        <div class="panel-header">
          <h3>Instrucciones</h3>
        </div>
        <div class="panel-grid">
          <p>Usa un CSV procedente de Raíces o exportación equivalente.</p>
          <p>Solo se extraerán tramos horarios del grupo seleccionado.</p>
          <p>Los módulos deben existir previamente en la base de datos para poder guardar los slots.</p>
        </div>
      </section>

      <?php if ($errors !== []): ?>
        <section class="panel">
          <div class="panel-header"><h3>Errores</h3></div>
          <ul class="form-errors">
            <?php foreach ($errors as $error): ?>
              <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
            <?php endforeach; ?>
          </ul>
        </section>
      <?php endif; ?>

      <?php if ($messages !== []): ?>
        <section class="panel">
          <div class="panel-header"><h3>Resultado</h3></div>
          <div class="panel-grid">
            <?php foreach ($messages as $message): ?>
              <p><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endforeach; ?>
            <p>Curso usado: <?php echo htmlspecialchars($result['curso_usado'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p>Grupo detectado en CSV: <?php echo htmlspecialchars($result['grupo_detectado'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p>Registros anteriores eliminados: <?php echo (int) $result['eliminados']; ?></p>
            <p>Slots insertados/actualizados: <?php echo (int) $result['insertados']; ?></p>
            <p>Filas ignoradas: <?php echo (int) $result['filas_ignoradas']; ?></p>
            <p>Módulos no encontrados: <?php echo count($result['modulos_no_encontrados']); ?></p>
            <p>Tramos no encontrados: <?php echo count($result['tramos_no_encontrados']); ?></p>
            <p>Errores detectados: <?php echo count($result['errores']); ?></p>
            <p>Avisos: <?php echo count($result['avisos']); ?></p>
            <p>Módulos con horas descuadradas: <?php echo count($result['modulos_horas_descuadradas']); ?></p>
          </div>

          <?php if ($result['modulos_no_encontrados'] !== []): ?>
            <div class="panel-header"><h3>Módulos no encontrados</h3></div>
            <ul class="form-errors">
              <?php foreach ($result['modulos_no_encontrados'] as $item): ?>
                <li><?php echo htmlspecialchars($item, ENT_QUOTES, 'UTF-8'); ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>

          <?php if ($result['tramos_no_encontrados'] !== []): ?>
            <div class="panel-header"><h3>Tramos no encontrados</h3></div>
            <ul class="form-errors">
              <?php foreach ($result['tramos_no_encontrados'] as $item): ?>
                <li><?php echo htmlspecialchars($item, ENT_QUOTES, 'UTF-8'); ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>

          <?php if ($result['errores'] !== []): ?>
            <div class="panel-header"><h3>Errores concretos</h3></div>
            <ul class="form-errors">
              <?php foreach ($result['errores'] as $item): ?>
                <li><?php echo htmlspecialchars($item, ENT_QUOTES, 'UTF-8'); ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>

          <?php if ($result['avisos'] !== []): ?>
            <div class="panel-header"><h3>Avisos</h3></div>
            <ul class="form-errors">
              <?php foreach ($result['avisos'] as $item): ?>
                <li><?php echo htmlspecialchars($item, ENT_QUOTES, 'UTF-8'); ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>

          <?php if ($result['modulos_horas_descuadradas'] !== []): ?>
            <div class="panel-header"><h3>Módulos con horas semanales descuadradas</h3></div>
            <ul class="form-errors">
              <?php foreach ($result['modulos_horas_descuadradas'] as $item): ?>
                <li><?php echo htmlspecialchars($item, ENT_QUOTES, 'UTF-8'); ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </section>
      <?php endif; ?>

      <form method="post" enctype="multipart/form-data" class="panel entity-form">
        <section class="entity-section">
          <div class="panel-header">
            <h3>Contexto académico</h3>
            <p>Selecciona curso escolar y grupo para esta importación.</p>
          </div>

          <div class="entity-grid entity-grid--4">
            <label>
              Curso escolar
              <select name="id_curso_escolar" required>
                <option value="">Selecciona curso escolar</option>
                <?php foreach ($cursos_escolares as $item): ?>
                  <option value="<?php echo (int) $item['id_curso_escolar']; ?>" <?php echo (int) $item['id_curso_escolar'] === $selected['id_curso_escolar'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars((string) $item['curso_escolar'], ENT_QUOTES, 'UTF-8'); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </label>

            <label>
              Grupo
              <select name="id_grupo" required>
                <option value="">Selecciona grupo</option>
                <?php foreach ($grupos as $item): ?>
                  <option value="<?php echo (int) $item['id_grupo']; ?>" <?php echo (int) $item['id_grupo'] === $selected['id_grupo'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars((string) $item['grupo'], ENT_QUOTES, 'UTF-8'); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </label>
          </div>

          <div class="entity-grid">
            <div class="file-field">
              <span class="file-field-label">Archivo CSV</span>
              <div class="file-field-control">
                <label class="file-field-btn" for="csvHorariosInput">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                  Seleccionar archivo
                </label>
                <span class="file-field-name" id="csvHorariosName">Ningún archivo seleccionado</span>
                <input type="file" id="csvHorariosInput" name="csv_horarios" accept=".csv,text/csv" required class="file-field-input">
              </div>
            </div>
          </div>
          <script>
            (function () {
              var inp = document.getElementById('csvHorariosInput');
              var nm = document.getElementById('csvHorariosName');
              if (inp && nm) {
                inp.addEventListener('change', function () {
                  nm.textContent = this.files[0] ? this.files[0].name : 'Ningún archivo seleccionado';
                });
              }
            })();
          </script>

          <div class="form-actions">
            <button type="submit" class="primary-button">Importar horario</button>
          </div>
        </section>
      </form>
    </main>
  </div>
</body>
</html>
