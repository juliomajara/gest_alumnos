<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pdo = db();

$page_title = 'Importar alumnos | Gestor de Alumnos';
$active_page = 'configuracion';

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
              "SELECT id_correo FROM correos WHERE entidad_tipo = 'alumno' AND id_entidad = :id_entidad AND direccion_correo = :direccion_correo LIMIT 1"
            );
            $insert_email = $pdo->prepare(
              "INSERT INTO correos (entidad_tipo, id_entidad, direccion_correo, etiqueta) VALUES ('alumno', :id_entidad, :direccion_correo, 'Personal')"
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
              $correo_tutor1 = get_csv_value($row, $header_map, ['CORREO_TUT1']);
              $correo_tutor2 = get_csv_value($row, $header_map, ['CORREO_TUT2']);
              $telefono = get_csv_value($row, $header_map, ['TELEFONO']);
              $correo_alumno = get_csv_value($row, $header_map, ['CORREO_AL']);

              $anio_curso = get_csv_value($row, $header_map, ['C_ANNO']);
              $nivel_texto = get_csv_value($row, $header_map, ['NIVEL']);
              $etapa = get_csv_value($row, $header_map, ['ETAPA']);
              $modalidad = get_csv_value($row, $header_map, ['D_MODALIDAD']);
              $unidad = get_csv_value($row, $header_map, ['UNIDAD']);
              $estado = get_csv_value($row, $header_map, ['ESTADO']);
              $materia_general = get_csv_value($row, $header_map, ['MATERIA_GENERAL']);
              $materia_propia = get_csv_value($row, $header_map, ['MATERIA_PROPIA_CENTRO']);

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

              if ($correo_alumno !== '') {
                $select_email->execute(['id_entidad' => $id_alumno, 'direccion_correo' => $correo_alumno]);
                if (!$select_email->fetchColumn()) {
                  $insert_email->execute(['id_entidad' => $id_alumno, 'direccion_correo' => $correo_alumno]);
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
                if (!array_key_exists($modalidad, $ciclo_cache)) {
                  $stmt = $pdo->prepare('SELECT id_ciclo FROM ciclos WHERE LOWER(ciclo) = LOWER(:ciclo) LIMIT 1');
                  $stmt->execute(['ciclo' => $modalidad]);
                  $ciclo_cache[$modalidad] = $stmt->fetchColumn();
                }
                $id_ciclo = $ciclo_cache[$modalidad] ? (int) $ciclo_cache[$modalidad] : null;
                if ($id_ciclo === null) {
                  $warnings[] = 'No se encontró el ciclo: ' . $modalidad . '.';
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

              if ($id_ciclo !== null && $materia_general !== '' && normalize_estado($estado) === 'matriculada') {
                $materia_propia = $materia_propia !== '' ? $materia_propia : '';
                $modulo_key = $id_ciclo . '|' . mb_strtolower($materia_general, 'UTF-8') . '|' . mb_strtolower($materia_propia, 'UTF-8');
                if (!array_key_exists($modulo_key, $modulo_cache)) {
                  $stmt = $pdo->prepare(
                    'SELECT id_modulo FROM modulos
                     WHERE id_ciclo = :id_ciclo
                     AND LOWER(materia_general) = LOWER(:materia_general)
                     AND LOWER(materia_propia) = LOWER(:materia_propia)
                     LIMIT 1'
                  );
                  $stmt->execute([
                    'id_ciclo' => $id_ciclo,
                    'materia_general' => $materia_general,
                    'materia_propia' => $materia_propia,
                  ]);
                  $modulo_cache[$modulo_key] = $stmt->fetchColumn();
                }

                $id_modulo = $modulo_cache[$modulo_key] ? (int) $modulo_cache[$modulo_key] : null;
                if ($id_modulo !== null) {
                  $select_alumno_modulo->execute(['id_alumno' => $id_alumno, 'id_modulo' => $id_modulo]);
                  if (!$select_alumno_modulo->fetchColumn()) {
                    $insert_alumno_modulo->execute(['id_alumno' => $id_alumno, 'id_modulo' => $id_modulo]);
                    $results['modules_linked']++;
                  }
                } else {
                  $warnings[] = 'No se encontró módulo para ' . $materia_general . ' / ' . $materia_propia . '.';
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
          <h3>CSV de alumnos</h3>
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
