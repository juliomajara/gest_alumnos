<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pdo = db();

$page_title = 'Importar alumnos | Gestor de Alumnos';
$active_page = 'alumnos';

$messages = [];
$warnings = [];
$results = [
  'rows_processed' => 0,
  'students_created' => 0,
  'students_existing' => 0,
  'modules_linked' => 0,
  'tutors_linked' => 0,
  'professors_linked' => 0,
];

$profesores_table_exists = (bool) $pdo->query("SHOW TABLES LIKE 'profesores'")->fetchColumn();
$grupos_tutores_table_exists = (bool) $pdo->query("SHOW TABLES LIKE 'grupos_tutores'")->fetchColumn();
$modulos_profesores_table_exists = (bool) $pdo->query("SHOW TABLES LIKE 'modulos_profesores'")->fetchColumn();

function normalize_header(string $header): string {
  return trim($header);
}

function get_csv_value(array $row, array $header_map, array $keys): string {
  foreach ($keys as $key) {
    if (array_key_exists($key, $header_map)) {
      $value = $row[$header_map[$key]] ?? '';
      return trim((string) $value);
    }
  }

  return '';
}

function parse_spanish_date(string $value): ?string {
  $value = trim($value);
  if ($value === '') {
    return null;
  }

  $date = DateTime::createFromFormat('d/m/Y', $value);
  if (!$date) {
    return null;
  }

  return $date->format('Y-m-d');
}

function normalize_estado(string $estado): string {
  return mb_strtolower(trim($estado), 'UTF-8');
}

function normalize_email(string $email): string {
  $email = trim($email);
  if ($email === '') {
    return '';
  }

  return mb_strtolower($email, 'UTF-8');
}

function norm(string $text): string {
  $text = str_replace("\xc2\xa0", ' ', $text);
  $text = mb_strtolower(trim($text), 'UTF-8');
  if ($text === '' || $text === '.') {
    return '';
  }

  $text = strtr($text, [
    'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a',
    'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
    'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
    'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o',
    'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
    'ñ' => 'n', 'ç' => 'c',
  ]);

  $text = str_replace(['|', '.', ',', ';', '·', '_'], ' ', $text);
  $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text) ?? $text;
  $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

  return trim($text);
}

function modulo_lookup_strategy(
  PDO $pdo,
  ?int $id_ciclo,
  string $materia_general_csv,
  string $materia_propia_csv,
  string $codigo_csv,
  string $abreviatura_csv
): array {
  static $meta = null;
  static $cache_by_ciclo = [];
  static $cache_all = null;

  if ($meta === null) {
    $columns = [];
    $stmt_columns = $pdo->query('SHOW COLUMNS FROM modulos');
    while (($row = $stmt_columns->fetch(PDO::FETCH_ASSOC)) !== false) {
      $columns[(string) ($row['Field'] ?? '')] = true;
    }
    $meta = [
      'has_codigo' => isset($columns['codigo']),
      'has_abreviatura' => isset($columns['abreviatura']),
      'has_tipo' => isset($columns['tipo']),
    ];
  }

  $load_rows = static function (?int $ciclo) use ($pdo, &$cache_by_ciclo, &$cache_all, $meta): array {
    $select_fields = 'id_modulo, id_ciclo, materia_general, materia_propia';
    if ($meta['has_codigo']) {
      $select_fields .= ', codigo';
    }
    if ($meta['has_abreviatura']) {
      $select_fields .= ', abreviatura';
    }
    if ($meta['has_tipo']) {
      $select_fields .= ', tipo';
    }

    if ($ciclo === null) {
      if ($cache_all === null) {
        $stmt = $pdo->query('SELECT ' . $select_fields . ' FROM modulos');
        $cache_all = $stmt->fetchAll(PDO::FETCH_ASSOC);
      }
      return $cache_all;
    }

    if (!array_key_exists($ciclo, $cache_by_ciclo)) {
      $stmt = $pdo->prepare('SELECT ' . $select_fields . ' FROM modulos WHERE id_ciclo = :id_ciclo');
      $stmt->execute(['id_ciclo' => $ciclo]);
      $cache_by_ciclo[$ciclo] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    return $cache_by_ciclo[$ciclo];
  };

  $norm_general_csv = norm($materia_general_csv);
  $norm_propia_csv = norm($materia_propia_csv);
  $norm_codigo_csv = norm($codigo_csv);
  $norm_abrev_csv = norm($abreviatura_csv);
  $is_optativo_general = str_starts_with($norm_general_csv, 'modulo profesional optativo');
  $is_fct_general = str_contains($norm_general_csv, 'formacion en centros de trabajo');

  $filter_general = static function (array $row) use ($norm_general_csv, $is_optativo_general): bool {
    if ($norm_general_csv === '') {
      return true;
    }

    $row_general = norm((string) ($row['materia_general'] ?? ''));
    if ($row_general === '') {
      return false;
    }

    if ($is_optativo_general) {
      return str_starts_with($row_general, 'modulo profesional optativo');
    }

    return $row_general === $norm_general_csv;
  };

  $find_exact_by_propia = static function (array $rows, bool $with_general_filter) use ($norm_propia_csv, $filter_general): array {
    if ($norm_propia_csv === '') {
      return [];
    }
    $matches = [];
    foreach ($rows as $row) {
      if (norm((string) ($row['materia_propia'] ?? '')) !== $norm_propia_csv) {
        continue;
      }
      if ($with_general_filter && !$filter_general($row)) {
        continue;
      }
      $matches[] = $row;
    }
    return $matches;
  };

  $rows_cycle = $load_rows($id_ciclo);
  $matches = $find_exact_by_propia($rows_cycle, true);
  if (count($matches) === 1) {
    return ['id_modulo' => (int) $matches[0]['id_modulo'], 'strategy' => 'A/B'];
  }

  // Reintento sin id_ciclo cuando no hay match exacto en ciclo esperado.
  $matches_global = $find_exact_by_propia($load_rows(null), true);
  if (count($matches_global) === 1) {
    return ['id_modulo' => (int) $matches_global[0]['id_modulo'], 'strategy' => 'A/B sin id_ciclo'];
  }

  // C) fallback por código o abreviatura (si se proporcionan en CSV).
  if ($norm_codigo_csv !== '' || $norm_abrev_csv !== '') {
    $candidates = [];
    foreach ($rows_cycle as $row) {
      if (!$filter_general($row)) {
        continue;
      }
      $ok = false;
      if ($norm_codigo_csv !== '' && isset($row['codigo']) && norm((string) $row['codigo']) === $norm_codigo_csv) {
        $ok = true;
      }
      if ($norm_abrev_csv !== '' && isset($row['abreviatura']) && norm((string) $row['abreviatura']) === $norm_abrev_csv) {
        $ok = true;
      }
      if ($ok) {
        $candidates[] = $row;
      }
    }
    if (count($candidates) === 1) {
      return ['id_modulo' => (int) $candidates[0]['id_modulo'], 'strategy' => 'C'];
    }
  }

  // Regla simple para FCT.
  if ($is_fct_general) {
    $fct_matches = [];
    foreach ($load_rows($id_ciclo) as $row) {
      $row_general = norm((string) ($row['materia_general'] ?? ''));
      $row_propia = norm((string) ($row['materia_propia'] ?? ''));
      $row_abrev = isset($row['abreviatura']) ? norm((string) $row['abreviatura']) : '';
      $row_codigo = isset($row['codigo']) ? norm((string) $row['codigo']) : '';
      $row_tipo = isset($row['tipo']) ? norm((string) $row['tipo']) : '';

      if (
        str_contains($row_general, 'formacion en centros de trabajo')
        || str_contains($row_propia, 'formacion en centros de trabajo')
        || $row_abrev === 'fct'
        || $row_codigo === 'fct'
        || str_contains($row_tipo, 'fct')
      ) {
        $fct_matches[] = $row;
      }
    }
    if (count($fct_matches) === 1) {
      return ['id_modulo' => (int) $fct_matches[0]['id_modulo'], 'strategy' => 'C/FCT'];
    }
  }

  // D) fallback suave: LIKE normalizado en candidatos razonables.
  if ($norm_propia_csv !== '') {
    $soft = [];
    foreach ($rows_cycle as $row) {
      if (!$filter_general($row)) {
        continue;
      }
      $propia = norm((string) ($row['materia_propia'] ?? ''));
      if ($propia === '') {
        continue;
      }
      if (str_contains($propia, $norm_propia_csv) || str_contains($norm_propia_csv, $propia)) {
        $soft[] = $row;
      }
    }
    if (count($soft) === 1) {
      return ['id_modulo' => (int) $soft[0]['id_modulo'], 'strategy' => 'D'];
    }
  }

  return ['id_modulo' => null, 'strategy' => 'A/B/C/D'];
}

$course_year_cache = [];
$nivel_cache = [];
$curso_cache = [];
$ciclo_cache = [];
$grupo_cache = [];
$modulo_cache = [];
$student_cache = [];
$profesor_cache = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!isset($_FILES['csv_file']) || !is_uploaded_file($_FILES['csv_file']['tmp_name'])) {
    $messages[] = 'No se ha subido ningún archivo válido.';
  } else {
    $tmp_name = $_FILES['csv_file']['tmp_name'];
    $handle = fopen($tmp_name, 'rb');
    if ($handle === false) {
      $messages[] = 'No se pudo leer el archivo CSV.';
    } else {
      $header = fgetcsv($handle, 0, ',', '"', '\\');
      if (!$header) {
        $messages[] = 'El CSV no contiene cabecera.';
      } else {
        $header_map = [];
        foreach ($header as $index => $column) {
          $header_map[normalize_header((string) $column)] = $index;
        }

        $required_columns = ['NIA', 'T_APELLIDO1', 'T_NOMBRE', 'C_NUMIDE', 'C_ANNO', 'NIVEL', 'ETAPA', 'D_MODALIDAD', 'UNIDAD'];
        $missing = [];
        foreach ($required_columns as $column) {
          if (!array_key_exists($column, $header_map)) {
            $missing[] = $column;
          }
        }

        if ($missing) {
          $messages[] = 'Faltan columnas obligatorias en el CSV: ' . implode(', ', $missing) . '.';
        } else {
          $pdo->beginTransaction();
          try {
            $select_student_by_nia = $pdo->prepare('SELECT id_alumno FROM alumnos WHERE nia = :nia LIMIT 1');
            $select_student_by_dni = $pdo->prepare('SELECT id_alumno FROM alumnos WHERE dni = :dni LIMIT 1');
            $insert_student = $pdo->prepare(
              'INSERT INTO alumnos (nia, apellido1, apellido2, nombre, dni, fecha_nacimiento, seg_soc, correo_tutor1, correo_tutor2)
               VALUES (:nia, :apellido1, :apellido2, :nombre, :dni, :fecha_nacimiento, :seg_soc, :correo_tutor1, :correo_tutor2)'
            );
            $select_phone = $pdo->prepare(
              "SELECT id_telefono FROM telefonos WHERE entidad_tipo = 'alumno' AND id_entidad = :id_entidad AND telefono = :telefono LIMIT 1"
            );
            $insert_phone = $pdo->prepare(
              "INSERT INTO telefonos (entidad_tipo, id_entidad, telefono, etiqueta) VALUES ('alumno', :id_entidad, :telefono, 'Personal')"
            );
            $select_email = $pdo->prepare(
              "SELECT id_correo, direccion_correo FROM correos WHERE entidad_tipo = 'alumno' AND id_entidad = :id_entidad AND direccion_correo = :direccion_correo LIMIT 1"
            );
            $insert_email = $pdo->prepare(
              "INSERT INTO correos (entidad_tipo, id_entidad, direccion_correo, etiqueta) VALUES ('alumno', :id_entidad, :direccion_correo, 'Personal')"
            );
            $update_email = $pdo->prepare(
              "UPDATE correos SET direccion_correo = :direccion_correo WHERE id_correo = :id_correo"
            );
            $update_student_emails = $pdo->prepare(
              'UPDATE alumnos
               SET correo_tutor1 = COALESCE(:correo_tutor1, correo_tutor1),
                   correo_tutor2 = COALESCE(:correo_tutor2, correo_tutor2)
               WHERE id_alumno = :id_alumno'
            );
            $select_alumno_curso = $pdo->prepare(
              'SELECT 1 FROM alumno_curso WHERE id_alumno = :id_alumno AND id_curso_escolar = :id_curso_escolar LIMIT 1'
            );
            $insert_alumno_curso = $pdo->prepare(
              'INSERT INTO alumno_curso (id_alumno, id_curso_escolar, id_nivel, id_ciclo, id_curso, id_grupo)
               VALUES (:id_alumno, :id_curso_escolar, :id_nivel, :id_ciclo, :id_curso, :id_grupo)'
            );
            $select_alumno_modulo = $pdo->prepare(
              'SELECT 1 FROM alumno_modulo WHERE id_alumno = :id_alumno AND id_modulo = :id_modulo LIMIT 1'
            );
            $insert_alumno_modulo = $pdo->prepare(
              'INSERT INTO alumno_modulo (id_alumno, id_modulo) VALUES (:id_alumno, :id_modulo)'
            );
            $select_profesor_by_dni = null;
            $select_profesor_by_name = null;
            $insert_profesor = null;
            $select_grupo_tutor = null;
            $insert_grupo_tutor = null;
            $select_modulo_profesor = null;
            $insert_modulo_profesor = null;

            if ($profesores_table_exists) {
              $select_profesor_by_dni = $pdo->prepare('SELECT id_profesor FROM profesores WHERE dni = :dni LIMIT 1');
              $select_profesor_by_name = $pdo->prepare(
                'SELECT id_profesor FROM profesores WHERE apellido1 = :apellido1 AND apellido2 = :apellido2 AND nombre = :nombre LIMIT 1'
              );
              $insert_profesor = $pdo->prepare(
                'INSERT INTO profesores (apellido1, apellido2, nombre, dni) VALUES (:apellido1, :apellido2, :nombre, :dni)'
              );
            }

            if ($grupos_tutores_table_exists) {
              $select_grupo_tutor = $pdo->prepare(
                'SELECT 1 FROM grupos_tutores WHERE id_grupo = :id_grupo AND id_profesor = :id_profesor AND id_curso_escolar = :id_curso_escolar LIMIT 1'
              );
              $insert_grupo_tutor = $pdo->prepare(
                'INSERT INTO grupos_tutores (id_grupo, id_profesor, id_curso_escolar) VALUES (:id_grupo, :id_profesor, :id_curso_escolar)'
              );
            }

            if ($modulos_profesores_table_exists) {
              $select_modulo_profesor = $pdo->prepare(
                'SELECT 1 FROM modulos_profesores WHERE id_modulo = :id_modulo AND id_profesor = :id_profesor AND id_curso_escolar = :id_curso_escolar LIMIT 1'
              );
              $insert_modulo_profesor = $pdo->prepare(
                'INSERT INTO modulos_profesores (id_modulo, id_profesor, id_curso_escolar) VALUES (:id_modulo, :id_profesor, :id_curso_escolar)'
              );
            }

            while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
              if ($row === [null] || $row === false) {
                continue;
              }

              $results['rows_processed']++;

              $nia = get_csv_value($row, $header_map, ['NIA']);
              $apellido1 = get_csv_value($row, $header_map, ['T_APELLIDO1']);
              $apellido2 = get_csv_value($row, $header_map, ['T_APELLIDO2']);
              $nombre = get_csv_value($row, $header_map, ['T_NOMBRE', 'NOMBRE']);
              $dni = get_csv_value($row, $header_map, ['C_NUMIDE']);
              $fecha_nacimiento = parse_spanish_date(get_csv_value($row, $header_map, ['F_NACIMIENTO']));
              $seg_soc = get_csv_value($row, $header_map, ['NUM_SS']);
              $correo_tutor1 = normalize_email(get_csv_value($row, $header_map, ['CORREO_TUT1']));
              $correo_tutor2 = normalize_email(get_csv_value($row, $header_map, ['CORREO_TUT2']));
              $telefono = get_csv_value($row, $header_map, ['TELEFONO']);
              $correo_alumno = normalize_email(get_csv_value($row, $header_map, ['CORREO_AL']));

              $anio_curso = get_csv_value($row, $header_map, ['C_ANNO']);
              $nivel_texto = get_csv_value($row, $header_map, ['NIVEL']);
              $etapa = get_csv_value($row, $header_map, ['ETAPA']);
              $modalidad = get_csv_value($row, $header_map, ['D_MODALIDAD']);
              $unidad = get_csv_value($row, $header_map, ['UNIDAD']);
              $estado = get_csv_value($row, $header_map, ['ESTADO']);
              $materia_general = get_csv_value($row, $header_map, ['MATERIA_GENERAL']);
              $materia_propia = get_csv_value($row, $header_map, ['MATERIA_PROPIA_CENTRO']);
              $materia_codigo = get_csv_value($row, $header_map, ['CODIGO_MODULO', 'COD_MODULO', 'CODIGO']);
              $materia_abreviatura = get_csv_value($row, $header_map, ['ABREVIATURA_MODULO', 'ABREVIATURA']);

              $tutor_dni = get_csv_value($row, $header_map, ['NIF_TUTOR']);
              $tutor_apellido1 = get_csv_value($row, $header_map, ['APE1_TUTOR']);
              $tutor_apellido2 = get_csv_value($row, $header_map, ['APE2_TUTOR']);
              $tutor_nombre = get_csv_value($row, $header_map, ['NOMBRE_TUTOR']);

              $profesor_dni = get_csv_value($row, $header_map, ['NIF_PROFESOR']);
              $profesor_apellido1 = get_csv_value($row, $header_map, ['APE1_PROFESOR']);
              $profesor_apellido2 = get_csv_value($row, $header_map, ['APE2_PROFESOR']);
              $profesor_nombre = get_csv_value($row, $header_map, ['NOMBRE_PROFESOR']);

              $student_key = $nia !== '' ? 'nia:' . $nia : ($dni !== '' ? 'dni:' . $dni : 'row:' . $results['rows_processed']);
              if (!array_key_exists($student_key, $student_cache)) {
                $id_alumno = null;
                if ($nia !== '') {
                  $select_student_by_nia->execute(['nia' => $nia]);
                  $id_alumno = $select_student_by_nia->fetchColumn();
                }
                if (!$id_alumno && $dni !== '') {
                  $select_student_by_dni->execute(['dni' => $dni]);
                  $id_alumno = $select_student_by_dni->fetchColumn();
                }

                if ($id_alumno) {
                  $results['students_existing']++;
                  $id_alumno = (int) $id_alumno;
                } else {
                  $insert_student->execute([
                    'nia' => $nia !== '' ? $nia : null,
                    'apellido1' => $apellido1,
                    'apellido2' => $apellido2 !== '' ? $apellido2 : null,
                    'nombre' => $nombre,
                    'dni' => $dni !== '' ? $dni : null,
                    'fecha_nacimiento' => $fecha_nacimiento,
                    'seg_soc' => $seg_soc !== '' ? $seg_soc : null,
                    'correo_tutor1' => $correo_tutor1 !== '' ? $correo_tutor1 : null,
                    'correo_tutor2' => $correo_tutor2 !== '' ? $correo_tutor2 : null,
                  ]);
                  $id_alumno = (int) $pdo->lastInsertId();
                  $results['students_created']++;
                }

                $student_cache[$student_key] = $id_alumno;
              } else {
                $id_alumno = $student_cache[$student_key];
              }

              if ($telefono !== '') {
                $select_phone->execute(['id_entidad' => $id_alumno, 'telefono' => $telefono]);
                if (!$select_phone->fetchColumn()) {
                  $insert_phone->execute(['id_entidad' => $id_alumno, 'telefono' => $telefono]);
                }
              }

              if ($correo_tutor1 !== '' || $correo_tutor2 !== '') {
                $update_student_emails->execute([
                  'id_alumno' => $id_alumno,
                  'correo_tutor1' => $correo_tutor1 !== '' ? $correo_tutor1 : null,
                  'correo_tutor2' => $correo_tutor2 !== '' ? $correo_tutor2 : null,
                ]);
              }

              if ($correo_alumno !== '') {
                $select_email->execute(['id_entidad' => $id_alumno, 'direccion_correo' => $correo_alumno]);
                $existing_email = $select_email->fetch(PDO::FETCH_ASSOC);
                if (!$existing_email) {
                  $insert_email->execute(['id_entidad' => $id_alumno, 'direccion_correo' => $correo_alumno]);
                } elseif ($existing_email['direccion_correo'] !== $correo_alumno) {
                  $update_email->execute([
                    'id_correo' => $existing_email['id_correo'],
                    'direccion_correo' => $correo_alumno,
                  ]);
                }
              }

              $id_curso_escolar = null;
              if ($anio_curso !== '') {
                if (!array_key_exists($anio_curso, $course_year_cache)) {
                  $label = $anio_curso . '/' . ((int) $anio_curso + 1);
                  $stmt = $pdo->prepare('SELECT id_curso_escolar FROM cursos_escolares WHERE curso_escolar = :label LIMIT 1');
                  $stmt->execute(['label' => $label]);
                  $course_year_cache[$anio_curso] = $stmt->fetchColumn();
                }
                $id_curso_escolar = $course_year_cache[$anio_curso] ? (int) $course_year_cache[$anio_curso] : null;
                if ($id_curso_escolar === null) {
                  $warnings[] = 'No se encontró el curso escolar para el año ' . $anio_curso . '.';
                }
              }

              $id_nivel = null;
              if ($nivel_texto !== '') {
                if (!array_key_exists($nivel_texto, $nivel_cache)) {
                  $stmt = $pdo->prepare('SELECT id_nivel FROM niveles WHERE nivel = :nivel LIMIT 1');
                  $stmt->execute(['nivel' => $nivel_texto]);
                  $nivel_cache[$nivel_texto] = $stmt->fetchColumn();
                }
                $id_nivel = $nivel_cache[$nivel_texto] ? (int) $nivel_cache[$nivel_texto] : null;
                if ($id_nivel === null) {
                  $warnings[] = 'No se encontró el nivel: ' . $nivel_texto . '.';
                }
              }

              $curso_label = '';
              if (preg_match('/([12])º/', $etapa, $matches)) {
                $curso_label = $matches[1] . 'º';
              }
              $id_curso = null;
              if ($curso_label !== '') {
                if (!array_key_exists($curso_label, $curso_cache)) {
                  $stmt = $pdo->prepare('SELECT id_curso FROM cursos WHERE curso = :curso LIMIT 1');
                  $stmt->execute(['curso' => $curso_label]);
                  $curso_cache[$curso_label] = $stmt->fetchColumn();
                }
                $id_curso = $curso_cache[$curso_label] ? (int) $curso_cache[$curso_label] : null;
                if ($id_curso === null) {
                  $warnings[] = 'No se encontró el curso para la etapa: ' . $etapa . '.';
                }
              }

              $id_ciclo = null;
              if ($modalidad !== '') {
                $modalidad_key = norm($modalidad);
                if (!array_key_exists($modalidad_key, $ciclo_cache)) {
                  $stmt = $pdo->prepare(
                    "SELECT id_ciclo
                     FROM ciclos
                     WHERE TRIM(ciclo) = TRIM(:m1)
                        OR TRIM(abreviatura) = TRIM(:m2)
                        OR :m3 LIKE CONCAT('%', TRIM(abreviatura), '%')
                     ORDER BY
                       CASE
                         WHEN TRIM(ciclo) = TRIM(:m4) THEN 1
                         WHEN TRIM(abreviatura) = TRIM(:m5) THEN 2
                         WHEN :m6 LIKE CONCAT('%', TRIM(abreviatura), '%') THEN 3
                         ELSE 4
                       END
                     LIMIT 1"
                  );
                  $modalidad_param = trim($modalidad);
                  $ciclo_params = [
                    'm1' => $modalidad_param,
                    'm2' => $modalidad_param,
                    'm3' => $modalidad_param,
                    'm4' => $modalidad_param,
                    'm5' => $modalidad_param,
                    'm6' => $modalidad_param,
                  ];
                  $stmt->execute($ciclo_params);
                  $ciclo_cache[$modalidad_key] = $stmt->fetchColumn();
                }
                $id_ciclo = $ciclo_cache[$modalidad_key] ? (int) $ciclo_cache[$modalidad_key] : null;
                if ($id_ciclo === null) {
                  $warnings[] = 'No se pudo resolver el ciclo desde CSV: ' . $modalidad . '.';
                }
              }

              $id_grupo = null;
              if ($unidad !== '') {
                if (!array_key_exists($unidad, $grupo_cache)) {
                  $stmt = $pdo->prepare('SELECT id_grupo FROM grupos WHERE grupo = :grupo LIMIT 1');
                  $stmt->execute(['grupo' => $unidad]);
                  $grupo_cache[$unidad] = $stmt->fetchColumn();
                }
                $id_grupo = $grupo_cache[$unidad] ? (int) $grupo_cache[$unidad] : null;
                if ($id_grupo === null) {
                  $warnings[] = 'No se encontró el grupo: ' . $unidad . '.';
                }
              }

              if ($id_curso_escolar !== null) {
                $select_alumno_curso->execute([
                  'id_alumno' => $id_alumno,
                  'id_curso_escolar' => $id_curso_escolar,
                ]);
                if (!$select_alumno_curso->fetchColumn()) {
                  $insert_alumno_curso->execute([
                    'id_alumno' => $id_alumno,
                    'id_curso_escolar' => $id_curso_escolar,
                    'id_nivel' => $id_nivel,
                    'id_ciclo' => $id_ciclo,
                    'id_curso' => $id_curso,
                    'id_grupo' => $id_grupo,
                  ]);
                }
              }

              if ($id_ciclo !== null && $materia_propia !== '' && normalize_estado($estado) === 'matriculada') {
                $modulo_key = implode('|', [
                  (string) $id_ciclo,
                  norm($materia_general),
                  norm($materia_propia),
                  norm($materia_codigo),
                  norm($materia_abreviatura),
                ]);

                if (!array_key_exists($modulo_key, $modulo_cache)) {
                  $lookup = modulo_lookup_strategy(
                    $pdo,
                    $id_ciclo,
                    $materia_general,
                    $materia_propia,
                    $materia_codigo,
                    $materia_abreviatura
                  );
                  $modulo_cache[$modulo_key] = $lookup;

                  if (($lookup['id_modulo'] ?? null) === null) {
                    $warnings[] = sprintf(
                      'No se encontró módulo (estrategias %s): materia_general_csv="%s", materia_propia_csv="%s", id_ciclo=%d',
                      (string) ($lookup['strategy'] ?? 'A/B/C/D'),
                      $materia_general,
                      $materia_propia,
                      $id_ciclo
                    );
                  }
                }

                $id_modulo = isset($modulo_cache[$modulo_key]['id_modulo']) && $modulo_cache[$modulo_key]['id_modulo'] !== null
                  ? (int) $modulo_cache[$modulo_key]['id_modulo']
                  : null;
                if ($id_modulo !== null) {
                  $select_alumno_modulo->execute(['id_alumno' => $id_alumno, 'id_modulo' => $id_modulo]);
                  if (!$select_alumno_modulo->fetchColumn()) {
                    $insert_alumno_modulo->execute(['id_alumno' => $id_alumno, 'id_modulo' => $id_modulo]);
                    $results['modules_linked']++;
                  }
                }

                if ($profesores_table_exists && $modulos_profesores_table_exists) {
                  $id_profesor = null;
                  $profesor_key = '';
                  if ($profesor_dni !== '' && $profesor_dni !== '*********') {
                    $profesor_key = 'dni:' . $profesor_dni;
                  } else {
                    $profesor_key = 'nombre:' . $profesor_apellido1 . '|' . $profesor_apellido2 . '|' . $profesor_nombre;
                  }

                  if (!array_key_exists($profesor_key, $profesor_cache)) {
                    if ($profesor_dni !== '' && $profesor_dni !== '*********') {
                      $select_profesor_by_dni->execute(['dni' => $profesor_dni]);
                      $id_profesor = $select_profesor_by_dni->fetchColumn();
                    }
                    if (!$id_profesor && $profesor_apellido1 !== '' && $profesor_nombre !== '') {
                      $select_profesor_by_name->execute([
                        'apellido1' => $profesor_apellido1,
                        'apellido2' => $profesor_apellido2 !== '' ? $profesor_apellido2 : null,
                        'nombre' => $profesor_nombre,
                      ]);
                      $id_profesor = $select_profesor_by_name->fetchColumn();
                    }

                    if (!$id_profesor && $profesor_apellido1 !== '' && $profesor_nombre !== '') {
                      $insert_profesor->execute([
                        'apellido1' => $profesor_apellido1,
                        'apellido2' => $profesor_apellido2 !== '' ? $profesor_apellido2 : null,
                        'nombre' => $profesor_nombre,
                        'dni' => $profesor_dni !== '' && $profesor_dni !== '*********' ? $profesor_dni : null,
                      ]);
                      $id_profesor = (int) $pdo->lastInsertId();
                    }

                    if ($id_profesor) {
                      $profesor_cache[$profesor_key] = (int) $id_profesor;
                    }
                  } else {
                    $id_profesor = $profesor_cache[$profesor_key];
                  }

                  if ($id_profesor && $id_modulo && $id_curso_escolar) {
                    $select_modulo_profesor->execute([
                      'id_modulo' => $id_modulo,
                      'id_profesor' => $id_profesor,
                      'id_curso_escolar' => $id_curso_escolar,
                    ]);
                    if (!$select_modulo_profesor->fetchColumn()) {
                      $insert_modulo_profesor->execute([
                        'id_modulo' => $id_modulo,
                        'id_profesor' => $id_profesor,
                        'id_curso_escolar' => $id_curso_escolar,
                      ]);
                      $results['professors_linked']++;
                    }
                  }
                }
              }

              if ($profesores_table_exists && $grupos_tutores_table_exists && $id_grupo !== null && $id_curso_escolar !== null) {
                $id_tutor = null;
                $tutor_key = '';
                if ($tutor_dni !== '' && $tutor_dni !== '*********') {
                  $tutor_key = 'dni:' . $tutor_dni;
                } else {
                  $tutor_key = 'nombre:' . $tutor_apellido1 . '|' . $tutor_apellido2 . '|' . $tutor_nombre;
                }

                if (!array_key_exists($tutor_key, $profesor_cache)) {
                  if ($tutor_dni !== '' && $tutor_dni !== '*********') {
                    $select_profesor_by_dni->execute(['dni' => $tutor_dni]);
                    $id_tutor = $select_profesor_by_dni->fetchColumn();
                  }
                  if (!$id_tutor && $tutor_apellido1 !== '' && $tutor_nombre !== '') {
                    $select_profesor_by_name->execute([
                      'apellido1' => $tutor_apellido1,
                      'apellido2' => $tutor_apellido2 !== '' ? $tutor_apellido2 : null,
                      'nombre' => $tutor_nombre,
                    ]);
                    $id_tutor = $select_profesor_by_name->fetchColumn();
                  }

                  if (!$id_tutor && $tutor_apellido1 !== '' && $tutor_nombre !== '') {
                    $insert_profesor->execute([
                      'apellido1' => $tutor_apellido1,
                      'apellido2' => $tutor_apellido2 !== '' ? $tutor_apellido2 : null,
                      'nombre' => $tutor_nombre,
                      'dni' => $tutor_dni !== '' && $tutor_dni !== '*********' ? $tutor_dni : null,
                    ]);
                    $id_tutor = (int) $pdo->lastInsertId();
                  }

                  if ($id_tutor) {
                    $profesor_cache[$tutor_key] = (int) $id_tutor;
                  }
                } else {
                  $id_tutor = $profesor_cache[$tutor_key];
                }

                if ($id_tutor) {
                  $select_grupo_tutor->execute([
                    'id_grupo' => $id_grupo,
                    'id_profesor' => $id_tutor,
                    'id_curso_escolar' => $id_curso_escolar,
                  ]);
                  if (!$select_grupo_tutor->fetchColumn()) {
                    $insert_grupo_tutor->execute([
                      'id_grupo' => $id_grupo,
                      'id_profesor' => $id_tutor,
                      'id_curso_escolar' => $id_curso_escolar,
                    ]);
                    $results['tutors_linked']++;
                  }
                }
              }
            }

            $pdo->commit();
            $messages[] = 'Importación finalizada correctamente.';
          } catch (Throwable $exception) {
            $pdo->rollBack();
            $messages[] = 'Se produjo un error al procesar el CSV: ' . $exception->getMessage();
          }
        }
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
          <p class="eyebrow">Importación</p>
          <h1>Importar alumnos</h1>
          <p class="subheading">Sube el CSV oficial para dar de alta alumnado, tutores, módulos y asignaciones.</p>
        </div>
      </header>

      <section class="panel">
        <div class="panel-header">
          <h3 class="panel-title-with-help">
            CSV de alumnos
            <span class="help-tooltip">
              <button
                type="button"
                class="help-tooltip-trigger"
                aria-label="Ayuda para exportar el CSV de alumnos"
                aria-describedby="csv-help"
              >
                ?
              </button>
              <span class="help-tooltip-content" id="csv-help" role="tooltip">
                <span class="help-tooltip-title">Cómo exportar el CSV desde Raíces</span>
                <ol>
                  <li>Entrar en Raíces.</li>
                  <li>Navegar a Utilidades &gt; Explotación de datos.</li>
                  <li>En el campo "Módulo" seleccionar "Matriculación".</li>
                  <li>En el campo "Consulta" seleccionar "Matriculados con materias y profesores".</li>
                  <li>En el campo "Curso académico" seleccionar el actual.</li>
                  <li>En el campo "Exportar los datos al formato" seleccionar "Fichero CSV".</li>
                </ol>
              </span>
            </span>
          </h3>
          <p>Selecciona el archivo CSV exportado desde el sistema académico y confirma la importación.</p>
        </div>

        <div class="panel-grid">
          <form method="post" enctype="multipart/form-data">
            <label>
              Archivo CSV
              <input type="file" name="csv_file" accept=".csv" required>
            </label>
            <button class="primary-button" type="submit">Procesar CSV</button>
          </form>
        </div>
      </section>

      <?php if ($messages): ?>
        <section class="panel">
          <div class="panel-header">
            <h3>Resultado de la importación</h3>
            <p>Resumen de la ejecución del proceso.</p>
          </div>
          <div class="panel-grid">
            <ul>
              <?php foreach ($messages as $message): ?>
                <li><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></li>
              <?php endforeach; ?>
            </ul>
            <ul>
              <li>Filas procesadas: <?php echo (int) $results['rows_processed']; ?></li>
              <li>Alumnos creados: <?php echo (int) $results['students_created']; ?></li>
              <li>Alumnos existentes: <?php echo (int) $results['students_existing']; ?></li>
              <li>Módulos asignados: <?php echo (int) $results['modules_linked']; ?></li>
              <li>Tutores asignados a grupos: <?php echo (int) $results['tutors_linked']; ?></li>
              <li>Profesores asignados a módulos: <?php echo (int) $results['professors_linked']; ?></li>
            </ul>
            <?php if ($warnings): ?>
              <div>
                <h4>Avisos</h4>
                <ul>
                  <?php foreach (array_unique($warnings) as $warning): ?>
                    <li><?php echo htmlspecialchars($warning, ENT_QUOTES, 'UTF-8'); ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>
          </div>
        </section>
      <?php endif; ?>
    </main>
  </div>
</body>
</html>
