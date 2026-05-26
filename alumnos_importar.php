<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pdo = db();

$page_title = 'Importar alumnos | Gestor de Alumnos';
$active_page = 'alumnos';

$messages = [];
$warnings = [];
$warning_keys = [];
$module_diagnostic_mode = true;
$results = [
  'rows_processed' => 0,
  'matriculated_rows' => 0,
  'unique_module_combinations' => 0,
  'students_created' => 0,
  'students_existing' => 0,
  'modules_linked' => 0,
  'module_exact_no_match_warnings' => 0,
  'tutors_linked' => 0,
  'professors_linked' => 0,
];

$profesores_table_exists = (bool) $pdo->query("SHOW TABLES LIKE 'profesores'")->fetchColumn();
$grupos_tutores_table_exists = (bool) $pdo->query("SHOW TABLES LIKE 'grupos_tutores'")->fetchColumn();
$modulos_profesores_table_exists = (bool) $pdo->query("SHOW TABLES LIKE 'modulos_profesores'")->fetchColumn();

function add_warning(array &$warnings, array &$warning_keys, string $key, string $message): void {
  if (isset($warning_keys[$key])) {
    return;
  }
  $warning_keys[$key] = true;
  $warnings[] = $message;
}

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

function is_matriculated_estado(string $estado): bool {
  return in_array(normalize_estado($estado), ['matriculada', 'matriculado'], true);
}

function normalize_email(string $email): string {
  $email = trim($email);
  if ($email === '') {
    return '';
  }

  return mb_strtolower($email, 'UTF-8');
}

function norm_persona(string $text): string {
  $text = str_replace("\xc2\xa0", ' ', $text);
  $text = mb_strtolower(trim($text), 'UTF-8');
  $text = str_replace(["\t", '.', ',', ';'], ' ', $text);
  $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

  return trim($text);
}

function normalize_module_text(string $s): string {
  $s = str_replace(["\xc2\xa0", "\t", "\r", "\n"], ' ', $s);
  $s = preg_replace('/\s+/u', ' ', trim($s)) ?? trim($s);
  if ($s === '' || $s === '.') {
    return '';
  }

  do {
    $previous = $s;
    $s = preg_replace('/\s*[-–—]{2,}\s*/u', ' ', $s) ?? $s;
    $s = preg_replace('/[\s\.,;:·]+$/u', '', $s) ?? $s;
    $s = preg_replace('/\s+/u', ' ', trim($s)) ?? trim($s);
  } while ($s !== '' && $previous !== $s);

  $s = preg_replace('/\s+\d+\s*(?:h|H|horas?)\.?$/u', '', $s) ?? $s;
  $s = preg_replace('/\s*\(?\d+\s*(?:h|H|horas?)\)?\s*$/u', '', $s) ?? $s;
  $s = preg_replace('/\s+curso\s*$/ui', '', $s) ?? $s;

  return trim($s);
}

function is_module_metadata_chunk(string $chunk): bool {
  $chunk = mb_strtolower(trim($chunk), 'UTF-8');
  $chunk = preg_replace('/[\.,;:]+$/u', '', $chunk) ?? $chunk;
  if ($chunk === '') {
    return false;
  }

  if (preg_match('/^\d+\s*(?:h|horas?)$/u', $chunk)) {
    return true;
  }
  if (preg_match('/^(?:1º|2º|primer\s+curso|segundo\s+curso)$/u', $chunk)) {
    return true;
  }
  if (preg_match('/^mp\s*\d{3,5}$/u', $chunk)) {
    return true;
  }

  return in_array($chunk, ['horas', 'loe', 'logse', 'dual', 'presencial'], true);
}

function strip_module_metadata_brackets(string $s): string {
  $s = normalize_module_text($s);
  if ($s === '') {
    return '';
  }

  do {
    $previous = $s;

    if (preg_match('/^\s*\(([^()\[\]]{1,80})\)\s*(.*)$/u', $s, $m) && is_module_metadata_chunk($m[1])) {
      $s = trim((string) $m[2]);
    }
    if (preg_match('/^\s*\[([^\[\]()]{1,80})\]\s*(.*)$/u', $s, $m) && is_module_metadata_chunk($m[1])) {
      $s = trim((string) $m[2]);
    }
    if (preg_match('/^(.*)\s*\(([^()\[\]]{1,80})\)\s*$/u', $s, $m) && is_module_metadata_chunk($m[2])) {
      $s = trim((string) $m[1]);
    }
    if (preg_match('/^(.*)\s*\[([^\[\]()]{1,80})\]\s*$/u', $s, $m) && is_module_metadata_chunk($m[2])) {
      $s = trim((string) $m[1]);
    }

    $s = normalize_module_text($s);
  } while ($s !== '' && $previous !== $s);

  return $s;
}

function clean_module_text(string $s, bool $strip_metadata_brackets = false): string {
  if ($strip_metadata_brackets) {
    return strip_module_metadata_brackets($s);
  }

  return normalize_module_text($s);
}

function module_key(?int $id_ciclo, string $general_clean, string $propia_clean): string {
  return ($id_ciclo !== null ? (string) $id_ciclo : 'null') . '|' . $general_clean . '|' . $propia_clean;
}

function normalize_for_exact_match(string $text): string {
  $text = str_replace(["\xc2\xa0", "\t", "\r", "\n"], ' ', $text);
  $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
  return trim($text);
}

function diagnose_module_no_match(
  PDO $pdo,
  ?int $id_ciclo,
  string $general,
  string $propia,
  string $general_norm,
  string $propia_norm,
  bool $swap_used
): string {
  $exact_stmt = $pdo->prepare(
    'SELECT id_modulo, id_ciclo, materia_general, materia_propia
     FROM modulos
     WHERE materia_general = :general AND materia_propia = :propia'
  );
  $exact_stmt->execute(['general' => $general, 'propia' => $propia]);
  $exact_rows = $exact_stmt->fetchAll(PDO::FETCH_ASSOC);

  if ($exact_rows !== []) {
    $cycles = [];
    foreach ($exact_rows as $row) {
      $cycles[] = (int) $row['id_ciclo'];
    }
    $cycles = array_values(array_unique($cycles));
    sort($cycles);
    if ($id_ciclo !== null && !in_array($id_ciclo, $cycles, true)) {
      return 'Existe fila exacta en modulos, pero con id_ciclo=' . implode(',', $cycles) . ' (esperado=' . $id_ciclo . ').';
    }
    return 'Existe fila exacta en modulos, pero hay ambigüedad (' . count($exact_rows) . ' filas).';
  }

  $swap_stmt = $pdo->prepare(
    'SELECT id_modulo, id_ciclo
     FROM modulos
     WHERE materia_general = :propia AND materia_propia = :general'
  );
  $swap_stmt->execute(['general' => $general, 'propia' => $propia]);
  $swap_rows = $swap_stmt->fetchAll(PDO::FETCH_ASSOC);
  if ($swap_rows !== []) {
    $cycles = [];
    foreach ($swap_rows as $row) {
      $cycles[] = (int) $row['id_ciclo'];
    }
    $cycles = array_values(array_unique($cycles));
    sort($cycles);
    return $swap_used
      ? 'Match por swap aplicado correctamente.'
      : 'El texto existe en columnas invertidas (swap) con id_ciclo=' . implode(',', $cycles) . '.';
  }

  $norm_stmt = $pdo->prepare(
    "SELECT id_modulo, id_ciclo,
            CONCAT('[', materia_general, ']') AS mg,
            CHAR_LENGTH(materia_general) AS mg_len,
            CONCAT('[', materia_propia, ']') AS mp,
            CHAR_LENGTH(materia_propia) AS mp_len,
            HEX(materia_propia) AS mp_hex
     FROM modulos
     WHERE TRIM(REGEXP_REPLACE(REPLACE(REPLACE(materia_general, CONVERT(0xC2A0 USING utf8mb4), ' '), CHAR(9), ' '), '[[:space:]]+', ' ')) = :general_norm
       AND TRIM(REGEXP_REPLACE(REPLACE(REPLACE(materia_propia, CONVERT(0xC2A0 USING utf8mb4), ' '), CHAR(9), ' '), '[[:space:]]+', ' ')) = :propia_norm
     LIMIT 1"
  );
  $norm_stmt->execute(['general_norm' => $general_norm, 'propia_norm' => $propia_norm]);
  $norm_row = $norm_stmt->fetch(PDO::FETCH_ASSOC);
  if ($norm_row !== false) {
    return 'No existe igualdad binaria exacta; en BD hay espacios invisibles/NBSP (mp_hex=' . $norm_row['mp_hex'] . ').';
  }

  $token = trim((string) (preg_split('/\s+/u', $propia_norm)[0] ?? ''));
  if ($token === '') {
    return 'No existe fila exacta en modulos con esos valores.';
  }

  $like_stmt = $pdo->prepare(
    "SELECT id_modulo, id_ciclo,
            CONCAT('[', materia_general, ']') AS mg,
            CHAR_LENGTH(materia_general) AS mg_len,
            CONCAT('[', materia_propia, ']') AS mp,
            CHAR_LENGTH(materia_propia) AS mp_len,
            HEX(materia_propia) AS mp_hex
     FROM modulos
     WHERE materia_propia LIKE :token OR materia_general LIKE :token
     ORDER BY id_modulo
     LIMIT 1"
  );
  $like_stmt->execute(['token' => '%' . $token . '%']);
  $like_row = $like_stmt->fetch(PDO::FETCH_ASSOC);
  if ($like_row !== false) {
    return sprintf(
      'No existe fila exacta en modulos con esos valores. Candidato cercano: id_modulo=%d,id_ciclo=%d,mg=%s,mg_len=%d,mp=%s,mp_len=%d,mp_hex=%s.',
      (int) $like_row['id_modulo'],
      (int) $like_row['id_ciclo'],
      (string) $like_row['mg'],
      (int) $like_row['mg_len'],
      (string) $like_row['mp'],
      (int) $like_row['mp_len'],
      (string) $like_row['mp_hex']
    );
  }

  return 'No existe fila exacta en modulos con esos valores.';
}

function valid_csv_dni(string $dni): bool {
  $dni = trim($dni);
  return $dni !== '' && $dni !== '*********';
}

function resolve_or_create_profesor(
  PDO $pdo,
  array &$profesores_index,
  array &$profesor_cache,
  ?PDOStatement $insert_profesor,
  ?PDOStatement $update_profesor_dni,
  string $apellido1,
  string $apellido2,
  string $nombre,
  string $dni
): ?int {
  if ($insert_profesor === null) {
    return null;
  }

  $apellido1_clean = trim($apellido1);
  $apellido2_clean = trim($apellido2);
  $nombre_clean = trim($nombre);
  $dni_clean = trim($dni);

  $a1 = norm_persona($apellido1_clean);
  $a2 = norm_persona($apellido2_clean);
  $nom = norm_persona($nombre_clean);

  if ($a1 === '' || $nom === '') {
    return null;
  }

  $cache_key = valid_csv_dni($dni_clean)
    ? 'dni:' . $dni_clean
    : 'nombre:' . $a1 . '|' . $a2 . '|' . $nom;

  if (isset($profesor_cache[$cache_key])) {
    return $profesor_cache[$cache_key];
  }

  $id_profesor = null;
  if (valid_csv_dni($dni_clean) && isset($profesores_index['dni'][$dni_clean])) {
    $id_profesor = $profesores_index['dni'][$dni_clean];
  }

  if ($id_profesor === null) {
    $full = $a1 . '|' . $a2 . '|' . $nom;
    $short = $a1 . '|' . $nom;

    if (isset($profesores_index['name_full'][$full])) {
      $id_profesor = $profesores_index['name_full'][$full];
    } elseif ($a2 === '' && isset($profesores_index['name_short'][$short])) {
      $id_profesor = $profesores_index['name_short'][$short];
    }
  }

  if ($id_profesor !== null) {
    $current_dni = $profesores_index['current_dni'][$id_profesor] ?? null;
    if (valid_csv_dni($dni_clean) && ($current_dni === null || $current_dni === '' || $current_dni === '*********')) {
      if ($update_profesor_dni !== null) {
        $update_profesor_dni->execute(['dni' => $dni_clean, 'id_profesor' => $id_profesor]);
      }
      $profesores_index['current_dni'][$id_profesor] = $dni_clean;
      $profesores_index['dni'][$dni_clean] = $id_profesor;
    }

    $profesor_cache[$cache_key] = $id_profesor;
    return $id_profesor;
  }

  $insert_profesor->execute([
    'apellido1' => $apellido1_clean,
    'apellido2' => $apellido2_clean !== '' ? $apellido2_clean : null,
    'nombre' => $nombre_clean,
    'dni' => valid_csv_dni($dni_clean) ? $dni_clean : null,
  ]);

  $id_profesor = (int) $pdo->lastInsertId();
  $full = $a1 . '|' . $a2 . '|' . $nom;
  $short = $a1 . '|' . $nom;
  $profesores_index['name_full'][$full] = $id_profesor;
  if (!isset($profesores_index['name_short'][$short])) {
    $profesores_index['name_short'][$short] = $id_profesor;
  }
  if (valid_csv_dni($dni_clean)) {
    $profesores_index['dni'][$dni_clean] = $id_profesor;
    $profesores_index['current_dni'][$id_profesor] = $dni_clean;
  } else {
    $profesores_index['current_dni'][$id_profesor] = null;
  }

  $profesor_cache[$cache_key] = $id_profesor;
  return $id_profesor;
}

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
            $rows = [];
            $matriculated_unique = [];

            while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
              if ($row === [null] || $row === false) {
                continue;
              }

              $raw = [
                'nia' => get_csv_value($row, $header_map, ['NIA']),
                'apellido1' => get_csv_value($row, $header_map, ['T_APELLIDO1']),
                'apellido2' => get_csv_value($row, $header_map, ['T_APELLIDO2']),
                'nombre' => get_csv_value($row, $header_map, ['T_NOMBRE', 'NOMBRE']),
                'dni' => get_csv_value($row, $header_map, ['C_NUMIDE']),
                'fecha_nacimiento' => parse_spanish_date(get_csv_value($row, $header_map, ['F_NACIMIENTO'])),
                'seg_soc' => get_csv_value($row, $header_map, ['NUM_SS']),
                'correo_tutor1' => normalize_email(get_csv_value($row, $header_map, ['CORREO_TUT1'])),
                'correo_tutor2' => normalize_email(get_csv_value($row, $header_map, ['CORREO_TUT2'])),
                'telefono' => get_csv_value($row, $header_map, ['TELEFONO']),
                'correo_alumno' => normalize_email(get_csv_value($row, $header_map, ['CORREO_AL'])),
                'anio_curso' => get_csv_value($row, $header_map, ['C_ANNO']),
                'nivel_texto' => get_csv_value($row, $header_map, ['NIVEL']),
                'etapa' => get_csv_value($row, $header_map, ['ETAPA']),
                'modalidad' => get_csv_value($row, $header_map, ['D_MODALIDAD']),
                'unidad' => get_csv_value($row, $header_map, ['UNIDAD']),
                'estado' => get_csv_value($row, $header_map, ['ESTADO']),
                'materia_general' => get_csv_value($row, $header_map, ['MATERIA_GENERAL']),
                'materia_propia' => get_csv_value($row, $header_map, ['MATERIA_PROPIA_CENTRO']),
                'tutor_dni' => get_csv_value($row, $header_map, ['NIF_TUTOR']),
                'tutor_apellido1' => get_csv_value($row, $header_map, ['APE1_TUTOR']),
                'tutor_apellido2' => get_csv_value($row, $header_map, ['APE2_TUTOR']),
                'tutor_nombre' => get_csv_value($row, $header_map, ['NOMBRE_TUTOR']),
                'profesor_dni' => get_csv_value($row, $header_map, ['NIF_PROFESOR']),
                'profesor_apellido1' => get_csv_value($row, $header_map, ['APE1_PROFESOR']),
                'profesor_apellido2' => get_csv_value($row, $header_map, ['APE2_PROFESOR']),
                'profesor_nombre' => get_csv_value($row, $header_map, ['NOMBRE_PROFESOR']),
              ];

              $rows[] = [
                'raw' => $raw,
                'clean' => [
                  'general' => normalize_for_exact_match(clean_module_text($raw['materia_general'])),
                  'propia' => normalize_for_exact_match(clean_module_text($raw['materia_propia'])),
                  'general_metadata' => normalize_for_exact_match(clean_module_text($raw['materia_general'], true)),
                  'propia_metadata' => normalize_for_exact_match(clean_module_text($raw['materia_propia'], true)),
                ],
                'ids' => [
                  'id_alumno' => null,
                  'id_profesor' => null,
                  'id_tutor' => null,
                  'id_curso_escolar' => null,
                  'id_nivel' => null,
                  'id_curso' => null,
                  'id_ciclo' => null,
                  'id_grupo' => null,
                  'id_modulo' => null,
                ],
              ];
            }

            $results['rows_processed'] = count($rows);

            $course_year_cache = [];
            $nivel_cache = [];
            $curso_cache = [];
            $ciclo_cache = [];
            $grupo_cache = [];
            $student_cache = [];
            $profesor_cache = [];

            $select_course_year = $pdo->prepare('SELECT id_curso_escolar FROM cursos_escolares WHERE curso_escolar = :label LIMIT 1');
            $select_nivel = $pdo->prepare('SELECT id_nivel FROM niveles WHERE nivel = :nivel LIMIT 1');
            $select_curso = $pdo->prepare('SELECT id_curso FROM cursos WHERE curso = :curso LIMIT 1');
            $select_ciclo = $pdo->prepare(
              "SELECT id_ciclo
               FROM ciclos
               WHERE LOWER(TRIM(ciclo)) = LOWER(TRIM(:m1))
                  OR LOWER(TRIM(abreviatura)) = LOWER(TRIM(:m2))
                  OR LOWER(:m3) LIKE CONCAT('%', LOWER(TRIM(abreviatura)), '%')
               ORDER BY
                 CASE
                   WHEN LOWER(TRIM(ciclo)) = LOWER(TRIM(:m4)) THEN 1
                   WHEN LOWER(TRIM(abreviatura)) = LOWER(TRIM(:m5)) THEN 2
                   WHEN LOWER(:m6) LIKE CONCAT('%', LOWER(TRIM(abreviatura)), '%') THEN 3
                   ELSE 4
                 END
               LIMIT 1"
            );
            $select_grupo = $pdo->prepare('SELECT id_grupo FROM grupos WHERE grupo = :grupo LIMIT 1');

            foreach ($rows as $index => &$row_data) {
              $raw = $row_data['raw'];

              if ($raw['anio_curso'] !== '') {
                if (!array_key_exists($raw['anio_curso'], $course_year_cache)) {
                  $label = $raw['anio_curso'] . '/' . ((int) $raw['anio_curso'] + 1);
                  $select_course_year->execute(['label' => $label]);
                  $course_year_cache[$raw['anio_curso']] = $select_course_year->fetchColumn();
                }
                $row_data['ids']['id_curso_escolar'] = $course_year_cache[$raw['anio_curso']] !== false ? (int) $course_year_cache[$raw['anio_curso']] : null;
              }

              if ($raw['nivel_texto'] !== '') {
                if (!array_key_exists($raw['nivel_texto'], $nivel_cache)) {
                  $select_nivel->execute(['nivel' => $raw['nivel_texto']]);
                  $nivel_cache[$raw['nivel_texto']] = $select_nivel->fetchColumn();
                }
                $row_data['ids']['id_nivel'] = $nivel_cache[$raw['nivel_texto']] !== false ? (int) $nivel_cache[$raw['nivel_texto']] : null;
              }

              if (preg_match('/([12])º/', $raw['etapa'], $m)) {
                $curso_label = $m[1] . 'º';
                if (!array_key_exists($curso_label, $curso_cache)) {
                  $select_curso->execute(['curso' => $curso_label]);
                  $curso_cache[$curso_label] = $select_curso->fetchColumn();
                }
                $row_data['ids']['id_curso'] = $curso_cache[$curso_label] !== false ? (int) $curso_cache[$curso_label] : null;
              }

              if ($raw['modalidad'] !== '') {
                $modalidad_key = trim($raw['modalidad']);
                if (!array_key_exists($modalidad_key, $ciclo_cache)) {
                  $select_ciclo->execute([
                    'm1' => $modalidad_key,
                    'm2' => $modalidad_key,
                    'm3' => $modalidad_key,
                    'm4' => $modalidad_key,
                    'm5' => $modalidad_key,
                    'm6' => $modalidad_key,
                  ]);
                  $ciclo_cache[$modalidad_key] = $select_ciclo->fetchColumn();
                }
                $row_data['ids']['id_ciclo'] = $ciclo_cache[$modalidad_key] !== false ? (int) $ciclo_cache[$modalidad_key] : null;
                if ($row_data['ids']['id_ciclo'] === null) {
                  $modalidad_norm = mb_strtolower(trim($modalidad_key), 'UTF-8');
                  $fallback_map = [
                    'desarrollo de aplicaciones multiplataforma' => 'DAM',
                    'desarrollo de aplicaciones web' => 'DAW',
                    'administración de sistemas informáticos en red' => 'ASIR',
                    'administracion de sistemas informaticos en red' => 'ASIR',
                    'sistemas microinformáticos y redes' => 'SMR',
                    'sistemas microinformaticos y redes' => 'SMR',
                  ];
                  $fallback_abbr = $fallback_map[$modalidad_norm] ?? null;
                  if ($fallback_abbr !== null) {
                    $select_ciclo->execute([
                      'm1' => $fallback_abbr,
                      'm2' => $fallback_abbr,
                      'm3' => $fallback_abbr,
                      'm4' => $fallback_abbr,
                      'm5' => $fallback_abbr,
                      'm6' => $fallback_abbr,
                    ]);
                    $fallback_ciclo = $select_ciclo->fetchColumn();
                    $row_data['ids']['id_ciclo'] = $fallback_ciclo !== false ? (int) $fallback_ciclo : null;
                    if ($row_data['ids']['id_ciclo'] !== null) {
                      $ciclo_cache[$modalidad_key] = $row_data['ids']['id_ciclo'];
                    }
                  }
                }
              }

              if ($raw['unidad'] !== '') {
                if (!array_key_exists($raw['unidad'], $grupo_cache)) {
                  $select_grupo->execute(['grupo' => $raw['unidad']]);
                  $grupo_cache[$raw['unidad']] = $select_grupo->fetchColumn();
                }
                $row_data['ids']['id_grupo'] = $grupo_cache[$raw['unidad']] !== false ? (int) $grupo_cache[$raw['unidad']] : null;
              }

              if (is_matriculated_estado($raw['estado'])) {
                $results['matriculated_rows']++;
                $combo_key = module_key($row_data['ids']['id_ciclo'], $row_data['clean']['general'], $row_data['clean']['propia']);
                $matriculated_unique[$combo_key] = true;
              }

              if ($row_data['ids']['id_curso_escolar'] === null && $raw['anio_curso'] !== '') {
                add_warning($warnings, $warning_keys, 'curso_escolar:' . $raw['anio_curso'], 'No se encontró el curso escolar para el año ' . $raw['anio_curso'] . '.');
              }
              if ($row_data['ids']['id_nivel'] === null && $raw['nivel_texto'] !== '') {
                add_warning($warnings, $warning_keys, 'nivel:' . $raw['nivel_texto'], 'No se encontró el nivel: ' . $raw['nivel_texto'] . '.');
              }
              if ($row_data['ids']['id_ciclo'] === null && $raw['modalidad'] !== '') {
                add_warning($warnings, $warning_keys, 'ciclo:' . $raw['modalidad'], 'No se pudo resolver el ciclo desde CSV: ' . $raw['modalidad'] . '.');
              }
              if ($row_data['ids']['id_grupo'] === null && $raw['unidad'] !== '') {
                add_warning($warnings, $warning_keys, 'grupo:' . $raw['unidad'], 'No se encontró el grupo: ' . $raw['unidad'] . '.');
              }
            }
            unset($row_data);

            $results['unique_module_combinations'] = count($matriculated_unique);

            $module_index = [];
            $module_collision_ids = [];
            $module_index_metadata = [];
            $module_collision_ids_metadata = [];
            $module_index_swap = [];
            $module_collision_ids_swap = [];
            $module_index_by_propia = [];
            $module_collision_ids_by_propia = [];
            $module_general_by_id = [];
            $stmt_modulos = $pdo->query('SELECT id_modulo, id_ciclo, materia_general, materia_propia FROM modulos');
            foreach ($stmt_modulos->fetchAll(PDO::FETCH_ASSOC) as $modulo_row) {
              $id_ciclo = isset($modulo_row['id_ciclo']) ? (int) $modulo_row['id_ciclo'] : null;
              $general_clean = normalize_for_exact_match(clean_module_text((string) ($modulo_row['materia_general'] ?? '')));
              $propia_clean = normalize_for_exact_match(clean_module_text((string) ($modulo_row['materia_propia'] ?? '')));
              $key = module_key($id_ciclo, $general_clean, $propia_clean);
              $swap_key = module_key($id_ciclo, $propia_clean, $general_clean);
              $general_clean_metadata = normalize_for_exact_match(clean_module_text((string) ($modulo_row['materia_general'] ?? ''), true));
              $propia_clean_metadata = normalize_for_exact_match(clean_module_text((string) ($modulo_row['materia_propia'] ?? ''), true));
              $metadata_key = module_key($id_ciclo, $general_clean_metadata, $propia_clean_metadata);
              $id_modulo = (int) $modulo_row['id_modulo'];

              if (!isset($module_index[$key])) {
                $module_index[$key] = [$id_modulo];
              } else {
                $module_index[$key][] = $id_modulo;
              }

              if (!isset($module_index_swap[$swap_key])) {
                $module_index_swap[$swap_key] = [$id_modulo];
              } else {
                $module_index_swap[$swap_key][] = $id_modulo;
              }

              if (!isset($module_index_metadata[$metadata_key])) {
                $module_index_metadata[$metadata_key] = [$id_modulo];
              } else {
                $module_index_metadata[$metadata_key][] = $id_modulo;
              }

              $propia_only_key = ($id_ciclo !== null ? (string) $id_ciclo : 'null') . '|' . $propia_clean;
              if (!isset($module_index_by_propia[$propia_only_key])) {
                $module_index_by_propia[$propia_only_key] = [$id_modulo];
              } else {
                $module_index_by_propia[$propia_only_key][] = $id_modulo;
              }
              $module_general_by_id[$id_modulo] = $general_clean;
            }

            foreach ($module_index as $key => $ids) {
              if (count($ids) > 1) {
                $module_collision_ids[$key] = $ids;
                add_warning(
                  $warnings,
                  $warning_keys,
                  'module_collision:' . $key,
                  'Colisión en índice de módulos para la clave exacta ' . $key . ' con id_modulo=[' . implode(', ', $ids) . ']. No se asignará automáticamente en casos ambiguos.'
                );
              }
            }

            foreach ($module_index_metadata as $key => $ids) {
              if (count($ids) > 1) {
                $module_collision_ids_metadata[$key] = $ids;
              }
            }

            foreach ($module_index_swap as $key => $ids) {
              if (count($ids) > 1) {
                $module_collision_ids_swap[$key] = $ids;
              }
            }

            foreach ($module_index_by_propia as $key => $ids) {
              if (count($ids) > 1) {
                $module_collision_ids_by_propia[$key] = $ids;
              }
            }

            $profesores_index = ['dni' => [], 'name_full' => [], 'name_short' => [], 'current_dni' => []];
            $insert_profesor = null;
            $update_profesor_dni = null;
            $select_modulo_profesor = null;
            $insert_modulo_profesor = null;
            $select_grupo_tutor = null;
            $insert_grupo_tutor = null;
            $active_course_id_for_module_prof = null;

            if ($profesores_table_exists) {
              $insert_profesor = $pdo->prepare('INSERT INTO profesores (apellido1, apellido2, nombre, dni) VALUES (:apellido1, :apellido2, :nombre, :dni)');
              $update_profesor_dni = $pdo->prepare('UPDATE profesores SET dni = :dni WHERE id_profesor = :id_profesor');

              $rows_profes = $pdo->query('SELECT id_profesor, apellido1, apellido2, nombre, dni FROM profesores')->fetchAll(PDO::FETCH_ASSOC);
              foreach ($rows_profes as $prof_row) {
                $id_prof = (int) $prof_row['id_profesor'];
                $dni = trim((string) ($prof_row['dni'] ?? ''));
                $a1 = norm_persona((string) ($prof_row['apellido1'] ?? ''));
                $a2 = norm_persona((string) ($prof_row['apellido2'] ?? ''));
                $nom = norm_persona((string) ($prof_row['nombre'] ?? ''));

                if (valid_csv_dni($dni)) {
                  $profesores_index['dni'][$dni] = $id_prof;
                }
                $profesores_index['current_dni'][$id_prof] = $dni !== '' ? $dni : null;
                if ($a1 !== '' && $nom !== '') {
                  $full = $a1 . '|' . $a2 . '|' . $nom;
                  if (!isset($profesores_index['name_full'][$full])) {
                    $profesores_index['name_full'][$full] = $id_prof;
                  }
                  $short = $a1 . '|' . $nom;
                  if (!isset($profesores_index['name_short'][$short])) {
                    $profesores_index['name_short'][$short] = $id_prof;
                  }
                }
              }

              $active_course_id_for_module_prof = $pdo->query('SELECT id_curso_escolar FROM cursos_escolares WHERE activo = 1 ORDER BY id_curso_escolar DESC LIMIT 1')->fetchColumn();
              $active_course_id_for_module_prof = $active_course_id_for_module_prof !== false ? (int) $active_course_id_for_module_prof : null;
              if ($active_course_id_for_module_prof === null && $modulos_profesores_table_exists) {
                add_warning($warnings, $warning_keys, 'curso_activo_profesores', 'No hay curso escolar activo para asignar profesores a módulos cuando no viene informado en CSV.');
              }
            }

            if ($modulos_profesores_table_exists) {
              $select_modulo_profesor = $pdo->prepare('SELECT 1 FROM modulos_profesores WHERE id_modulo = :id_modulo AND id_profesor = :id_profesor AND id_curso_escolar = :id_curso_escolar LIMIT 1');
              $insert_modulo_profesor = $pdo->prepare('INSERT INTO modulos_profesores (id_modulo, id_profesor, id_curso_escolar) VALUES (:id_modulo, :id_profesor, :id_curso_escolar)');
            }

            if ($grupos_tutores_table_exists) {
              $select_grupo_tutor = $pdo->prepare('SELECT 1 FROM grupos_tutores WHERE id_grupo = :id_grupo AND id_profesor = :id_profesor AND id_curso_escolar = :id_curso_escolar LIMIT 1');
              $insert_grupo_tutor = $pdo->prepare('INSERT INTO grupos_tutores (id_grupo, id_profesor, id_curso_escolar) VALUES (:id_grupo, :id_profesor, :id_curso_escolar)');
            }

            // Paso 2: profesores y tutores.
            if ($profesores_table_exists) {
              foreach ($rows as &$row_data) {
                $raw = $row_data['raw'];
                $row_data['ids']['id_profesor'] = resolve_or_create_profesor(
                  $pdo,
                  $profesores_index,
                  $profesor_cache,
                  $insert_profesor,
                  $update_profesor_dni,
                  $raw['profesor_apellido1'],
                  $raw['profesor_apellido2'],
                  $raw['profesor_nombre'],
                  $raw['profesor_dni']
                );
                $row_data['ids']['id_tutor'] = resolve_or_create_profesor(
                  $pdo,
                  $profesores_index,
                  $profesor_cache,
                  $insert_profesor,
                  $update_profesor_dni,
                  $raw['tutor_apellido1'],
                  $raw['tutor_apellido2'],
                  $raw['tutor_nombre'],
                  $raw['tutor_dni']
                );
              }
              unset($row_data);
            }

            // Paso 3: alumnos.
            $select_student_by_nia = $pdo->prepare('SELECT id_alumno FROM alumnos WHERE nia = :nia LIMIT 1');
            $select_student_by_dni = $pdo->prepare('SELECT id_alumno FROM alumnos WHERE dni = :dni LIMIT 1');
            $insert_student = $pdo->prepare(
              'INSERT INTO alumnos (nia, apellido1, apellido2, nombre, dni, fecha_nacimiento, seg_soc, correo_tutor1, correo_tutor2)
               VALUES (:nia, :apellido1, :apellido2, :nombre, :dni, :fecha_nacimiento, :seg_soc, :correo_tutor1, :correo_tutor2)'
            );
            $select_phone = $pdo->prepare("SELECT 1 FROM telefonos WHERE entidad_tipo = 'alumno' AND id_entidad = :id_entidad AND telefono = :telefono LIMIT 1");
            $insert_phone = $pdo->prepare("INSERT INTO telefonos (entidad_tipo, id_entidad, telefono, etiqueta) VALUES ('alumno', :id_entidad, :telefono, 'Personal')");
            $select_email = $pdo->prepare("SELECT 1 FROM correos WHERE entidad_tipo = 'alumno' AND id_entidad = :id_entidad AND direccion_correo = :direccion_correo LIMIT 1");
            $insert_email = $pdo->prepare("INSERT INTO correos (entidad_tipo, id_entidad, direccion_correo, etiqueta) VALUES ('alumno', :id_entidad, :direccion_correo, 'Personal')");
            $update_student_emails = $pdo->prepare(
              'UPDATE alumnos
               SET correo_tutor1 = COALESCE(:correo_tutor1, correo_tutor1),
                   correo_tutor2 = COALESCE(:correo_tutor2, correo_tutor2)
               WHERE id_alumno = :id_alumno'
            );

            foreach ($rows as &$row_data) {
              $raw = $row_data['raw'];
              $student_key = $raw['nia'] !== '' ? 'nia:' . $raw['nia'] : ($raw['dni'] !== '' ? 'dni:' . $raw['dni'] : 'row:' . spl_object_id((object) $raw));

              if (!isset($student_cache[$student_key])) {
                $id_alumno = null;
                if ($raw['nia'] !== '') {
                  $select_student_by_nia->execute(['nia' => $raw['nia']]);
                  $found = $select_student_by_nia->fetchColumn();
                  $id_alumno = $found !== false ? (int) $found : null;
                }
                if ($id_alumno === null && $raw['dni'] !== '') {
                  $select_student_by_dni->execute(['dni' => $raw['dni']]);
                  $found = $select_student_by_dni->fetchColumn();
                  $id_alumno = $found !== false ? (int) $found : null;
                }

                if ($id_alumno !== null) {
                  $results['students_existing']++;
                } else {
                  $insert_student->execute([
                    'nia' => $raw['nia'] !== '' ? $raw['nia'] : null,
                    'apellido1' => $raw['apellido1'],
                    'apellido2' => $raw['apellido2'] !== '' ? $raw['apellido2'] : null,
                    'nombre' => $raw['nombre'],
                    'dni' => $raw['dni'] !== '' ? $raw['dni'] : null,
                    'fecha_nacimiento' => $raw['fecha_nacimiento'],
                    'seg_soc' => $raw['seg_soc'] !== '' ? $raw['seg_soc'] : null,
                    'correo_tutor1' => $raw['correo_tutor1'] !== '' ? $raw['correo_tutor1'] : null,
                    'correo_tutor2' => $raw['correo_tutor2'] !== '' ? $raw['correo_tutor2'] : null,
                  ]);
                  $id_alumno = (int) $pdo->lastInsertId();
                  $results['students_created']++;
                }

                $student_cache[$student_key] = $id_alumno;
              }

              $id_alumno = $student_cache[$student_key];
              $row_data['ids']['id_alumno'] = $id_alumno;

              if ($raw['telefono'] !== '') {
                $select_phone->execute(['id_entidad' => $id_alumno, 'telefono' => $raw['telefono']]);
                if (!$select_phone->fetchColumn()) {
                  $insert_phone->execute(['id_entidad' => $id_alumno, 'telefono' => $raw['telefono']]);
                }
              }

              if ($raw['correo_alumno'] !== '') {
                $select_email->execute(['id_entidad' => $id_alumno, 'direccion_correo' => $raw['correo_alumno']]);
                if (!$select_email->fetchColumn()) {
                  $insert_email->execute(['id_entidad' => $id_alumno, 'direccion_correo' => $raw['correo_alumno']]);
                }
              }

              if ($raw['correo_tutor1'] !== '' || $raw['correo_tutor2'] !== '') {
                $update_student_emails->execute([
                  'id_alumno' => $id_alumno,
                  'correo_tutor1' => $raw['correo_tutor1'] !== '' ? $raw['correo_tutor1'] : null,
                  'correo_tutor2' => $raw['correo_tutor2'] !== '' ? $raw['correo_tutor2'] : null,
                ]);
              }
            }
            unset($row_data);

            // Paso 5: alumno_curso.
            $select_alumno_curso = $pdo->prepare('SELECT 1 FROM alumno_curso WHERE id_alumno = :id_alumno AND id_curso_escolar = :id_curso_escolar LIMIT 1');
            $insert_alumno_curso = $pdo->prepare('INSERT INTO alumno_curso (id_alumno, id_curso_escolar, id_nivel, id_ciclo, id_curso, id_grupo) VALUES (:id_alumno, :id_curso_escolar, :id_nivel, :id_ciclo, :id_curso, :id_grupo)');

            foreach ($rows as $row_data) {
              if ($row_data['ids']['id_alumno'] === null || $row_data['ids']['id_curso_escolar'] === null) {
                continue;
              }

              $select_alumno_curso->execute([
                'id_alumno' => $row_data['ids']['id_alumno'],
                'id_curso_escolar' => $row_data['ids']['id_curso_escolar'],
              ]);
              if (!$select_alumno_curso->fetchColumn()) {
                $insert_alumno_curso->execute([
                  'id_alumno' => $row_data['ids']['id_alumno'],
                  'id_curso_escolar' => $row_data['ids']['id_curso_escolar'],
                  'id_nivel' => $row_data['ids']['id_nivel'],
                  'id_ciclo' => $row_data['ids']['id_ciclo'],
                  'id_curso' => $row_data['ids']['id_curso'],
                  'id_grupo' => $row_data['ids']['id_grupo'],
                ]);
              }
            }

            // Paso 6: módulos a alumnos con match exacto.
            $select_alumno_modulo = $pdo->prepare('SELECT 1 FROM alumno_modulo WHERE id_alumno = :id_alumno AND id_modulo = :id_modulo LIMIT 1');
            $insert_alumno_modulo = $pdo->prepare('INSERT INTO alumno_modulo (id_alumno, id_modulo) VALUES (:id_alumno, :id_modulo)');

            foreach ($rows as &$row_data) {
              $raw = $row_data['raw'];
              if (!is_matriculated_estado($raw['estado']) || $row_data['ids']['id_alumno'] === null) {
                continue;
              }

              $id_ciclo = $row_data['ids']['id_ciclo'];
              $general_clean = $row_data['clean']['general'];
              $propia_clean = $row_data['clean']['propia'];
              $general_clean_metadata = $row_data['clean']['general_metadata'];
              $propia_clean_metadata = $row_data['clean']['propia_metadata'];
              $key = module_key($id_ciclo, $general_clean, $propia_clean);
              $metadata_key = module_key($id_ciclo, $general_clean_metadata, $propia_clean_metadata);

              $candidates = $module_index[$key] ?? [];
              if (isset($module_collision_ids[$key])) {
                $candidates = $module_collision_ids[$key];
              }

              $candidate_count = count($candidates);
              $metadata_candidates = [];
              $metadata_candidate_count = 0;
              $swap_candidates = [];
              $swap_candidate_count = 0;
              $swap_used = false;

              if ($candidate_count === 0) {
                $metadata_candidates = $module_index_metadata[$metadata_key] ?? [];
                if (isset($module_collision_ids_metadata[$metadata_key])) {
                  $metadata_candidates = $module_collision_ids_metadata[$metadata_key];
                }
                $metadata_candidate_count = count($metadata_candidates);
                if ($metadata_candidate_count === 1) {
                  $candidates = $metadata_candidates;
                  $candidate_count = 1;
                }
              }

              if ($candidate_count === 0) {
                $swap_candidates = $module_index_swap[$key] ?? [];
                if (isset($module_collision_ids_swap[$key])) {
                  $swap_candidates = $module_collision_ids_swap[$key];
                }
                $swap_candidate_count = count($swap_candidates);
                if ($swap_candidate_count === 1) {
                  $candidates = $swap_candidates;
                  $candidate_count = 1;
                  $swap_used = true;
                }
              }

              if ($candidate_count === 0) {
                $propia_only_key = ($id_ciclo !== null ? (string) $id_ciclo : 'null') . '|' . $propia_clean;
                $propia_only_candidates = $module_index_by_propia[$propia_only_key] ?? [];
                if (isset($module_collision_ids_by_propia[$propia_only_key])) {
                  $propia_only_candidates = $module_collision_ids_by_propia[$propia_only_key];
                }
                if (count($propia_only_candidates) === 1) {
                  $candidate_id = (int) $propia_only_candidates[0];
                  $bd_general = $module_general_by_id[$candidate_id] ?? '';
                  if ($bd_general !== '' && ($bd_general === $general_clean || str_starts_with($bd_general, $general_clean) || str_starts_with($general_clean, $bd_general))) {
                    $candidates = [$candidate_id];
                    $candidate_count = 1;
                  }
                }
              }

              if ($candidate_count === 1) {
                $id_modulo = (int) $candidates[0];
                $row_data['ids']['id_modulo'] = $id_modulo;

                $select_alumno_modulo->execute(['id_alumno' => $row_data['ids']['id_alumno'], 'id_modulo' => $id_modulo]);
                if (!$select_alumno_modulo->fetchColumn()) {
                  $insert_alumno_modulo->execute(['id_alumno' => $row_data['ids']['id_alumno'], 'id_modulo' => $id_modulo]);
                  $results['modules_linked']++;
                }
              } else {
                $warn_key = 'exact_module:' . $key . '|csv:' . md5($raw['materia_general'] . '|' . $raw['materia_propia'] . '|' . (string) $id_ciclo);
                if ($candidate_count === 0 && $metadata_candidate_count > 0) {
                  $warn_key .= '|meta:' . $metadata_key;
                }
                $diagnostic_text = '';
                if ($module_diagnostic_mode) {
                  $diagnostic_text = diagnose_module_no_match(
                    $pdo,
                    $id_ciclo,
                    $raw['materia_general'],
                    $raw['materia_propia'],
                    $general_clean,
                    $propia_clean,
                    $swap_used
                  );
                }

                add_warning(
                  $warnings,
                  $warning_keys,
                  $warn_key,
                  sprintf(
                    'No match exacto de módulo: csv_general="%s", csv_propia="%s", limpio_general="%s", limpio_propia="%s", limpio_general_meta="%s", limpio_propia_meta="%s", id_ciclo=%s, candidatos_exactos=%d, candidatos_meta=%d, candidatos_swap=%d. %s',
                    $raw['materia_general'],
                    $raw['materia_propia'],
                    $general_clean,
                    $propia_clean,
                    $general_clean_metadata,
                    $propia_clean_metadata,
                    $id_ciclo !== null ? (string) $id_ciclo : 'null',
                    $candidate_count,
                    $metadata_candidate_count,
                    $swap_candidate_count,
                    $diagnostic_text
                  )
                );
                $results['module_exact_no_match_warnings']++;
              }
            }
            unset($row_data);

            // Paso 7: módulos a profesores.
            if ($profesores_table_exists && $modulos_profesores_table_exists && $select_modulo_profesor !== null && $insert_modulo_profesor !== null) {
              foreach ($rows as $row_data) {
                $id_modulo = $row_data['ids']['id_modulo'];
                $id_profesor = $row_data['ids']['id_profesor'];
                $id_curso_escolar = $row_data['ids']['id_curso_escolar'] ?? $active_course_id_for_module_prof;

                if ($id_modulo === null || $id_profesor === null || $id_curso_escolar === null) {
                  continue;
                }

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

            // Paso 8: tutorías.
            if ($profesores_table_exists && $grupos_tutores_table_exists && $select_grupo_tutor !== null && $insert_grupo_tutor !== null) {
              foreach ($rows as $row_data) {
                $id_grupo = $row_data['ids']['id_grupo'];
                $id_tutor = $row_data['ids']['id_tutor'];
                $id_curso_escolar = $row_data['ids']['id_curso_escolar'];

                if ($id_grupo === null || $id_tutor === null || $id_curso_escolar === null) {
                  continue;
                }

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

      <form method="post" enctype="multipart/form-data" class="panel entity-form">
        <section class="entity-section">
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

          <div class="entity-grid">
            <div class="file-field">
              <span class="file-field-label">Archivo CSV</span>
              <div class="file-field-control">
                <label class="file-field-btn" for="csvAlumnosInput">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                  Seleccionar archivo
                </label>
                <span class="file-field-name" id="csvAlumnosName">Ningún archivo seleccionado</span>
                <input type="file" id="csvAlumnosInput" name="csv_file" accept=".csv" required class="file-field-input">
              </div>
            </div>
          </div>
          <script>
            (function () {
              var inp = document.getElementById('csvAlumnosInput');
              var nm = document.getElementById('csvAlumnosName');
              if (inp && nm) {
                inp.addEventListener('change', function () {
                  nm.textContent = this.files[0] ? this.files[0].name : 'Ningún archivo seleccionado';
                });
              }
            })();
          </script>

          <div class="form-actions">
            <button class="primary-button" type="submit">Procesar CSV</button>
          </div>
        </section>
      </form>

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
              <li>Filas matriculadas: <?php echo (int) $results['matriculated_rows']; ?></li>
              <li>Combinaciones únicas (id_ciclo+materias limpias): <?php echo (int) $results['unique_module_combinations']; ?></li>
              <li>Alumnos creados: <?php echo (int) $results['students_created']; ?></li>
              <li>Alumnos existentes: <?php echo (int) $results['students_existing']; ?></li>
              <li>Módulos asignados: <?php echo (int) $results['modules_linked']; ?></li>
              <li>Warnings por no match exacto de módulo: <?php echo (int) $results['module_exact_no_match_warnings']; ?></li>
              <li>Tutores asignados a grupos: <?php echo (int) $results['tutors_linked']; ?></li>
              <li>Profesores asignados a módulos: <?php echo (int) $results['professors_linked']; ?></li>
            </ul>
            <?php if ($warnings): ?>
              <div>
                <h4>Avisos</h4>
                <ul>
                  <?php foreach ($warnings as $warning): ?>
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
