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

function valid_date(?string $value): bool {
  if ($value === null) {
    return false;
  }

  $date = DateTime::createFromFormat('Y-m-d', $value);
  return $date !== false && $date->format('Y-m-d') === $value;
}

function practice_full_name(array $row, string $nameKey = 'nombre'): string {
  $parts = array_filter([
    trim((string) ($row['apellido1'] ?? '')),
    trim((string) ($row['apellido2'] ?? '')),
  ], static fn (string $value): bool => $value !== '');

  $apellidos = implode(' ', $parts);
  $nombre = trim((string) ($row[$nameKey] ?? ''));

  if ($apellidos === '' && $nombre === '') {
    return 'No disponible';
  }

  return trim($apellidos . ', ' . $nombre, ' ,');
}

$active_course_stmt = $pdo->query('SELECT id_curso_escolar, curso_escolar FROM cursos_escolares WHERE activo = 1 ORDER BY id_curso_escolar DESC LIMIT 1');
$active_course = $active_course_stmt->fetch();
if (!$active_course) {
  $fallback_course_stmt = $pdo->query('SELECT id_curso_escolar, curso_escolar FROM cursos_escolares ORDER BY id_curso_escolar DESC LIMIT 1');
  $active_course = $fallback_course_stmt->fetch() ?: null;
}

$active_course_id = $active_course ? (int) $active_course['id_curso_escolar'] : 0;

if (($_GET['action'] ?? '') !== '') {
  header('Content-Type: application/json; charset=UTF-8');
  $action = (string) $_GET['action'];

  if ($action === 'students') {
    $group_id = isset($_GET['group_id']) ? (int) $_GET['group_id'] : 0;

    if ($group_id <= 0 || $active_course_id <= 0) {
      echo json_encode(['ok' => true, 'students' => []]);
      exit;
    }

    $students_stmt = $pdo->prepare(
      'SELECT DISTINCT a.id_alumno, a.nombre, a.apellido1, a.apellido2
       FROM alumno_curso ac
       INNER JOIN alumnos a ON a.id_alumno = ac.id_alumno
       WHERE ac.id_grupo = :id_grupo AND ac.id_curso_escolar = :id_curso_escolar
       ORDER BY a.apellido1, a.apellido2, a.nombre'
    );
    $students_stmt->execute([
      'id_grupo' => $group_id,
      'id_curso_escolar' => $active_course_id,
    ]);

    $students = array_map(static fn (array $student): array => [
      'id_alumno' => (int) $student['id_alumno'],
      'nombre' => practice_full_name($student),
    ], $students_stmt->fetchAll());

    echo json_encode(['ok' => true, 'students' => $students]);
    exit;
  }

  if ($action === 'student_hours') {
    $student_id = isset($_GET['id_alumno']) ? (int) $_GET['id_alumno'] : 0;

    if ($student_id <= 0) {
      echo json_encode(['ok' => true, 'horas_ffe_aprobadas' => null]);
      exit;
    }

    $hours_stmt = $pdo->prepare('SELECT horas_ffe_aprobadas FROM alumnos WHERE id_alumno = :id_alumno');
    $hours_stmt->execute(['id_alumno' => $student_id]);
    $hours = $hours_stmt->fetchColumn();

    echo json_encode(['ok' => true, 'horas_ffe_aprobadas' => $hours !== false ? (int) $hours : null]);
    exit;
  }

  if ($action === 'company_data') {
    $company_id = isset($_GET['id_empresa']) ? (int) $_GET['id_empresa'] : 0;

    if ($company_id <= 0) {
      echo json_encode(['ok' => true, 'direcciones' => [], 'tutores' => []]);
      exit;
    }

    $address_stmt = $pdo->prepare(
      'SELECT d.id_direccion,
              d.etiqueta,
              d.nombre_via,
              d.numero,
              d.cp,
              v.via,
              l.nombre AS localidad
       FROM direcciones d
       LEFT JOIN vias v ON v.id_via = d.id_via
       LEFT JOIN localidades l ON l.id_localidad = d.id_localidad
       WHERE d.id_empresa = :id_empresa
       ORDER BY d.principal DESC, d.id_direccion'
    );
    $address_stmt->execute(['id_empresa' => $company_id]);
    $direcciones = [];
    foreach ($address_stmt->fetchAll() as $address) {
      $fragments = [];
      if (!empty($address['etiqueta'])) {
        $fragments[] = (string) $address['etiqueta'];
      }

      $street = trim(implode(' ', array_filter([
        (string) ($address['via'] ?? ''),
        (string) ($address['nombre_via'] ?? ''),
        (string) ($address['numero'] ?? ''),
      ], static fn (string $value): bool => trim($value) !== '')));
      if ($street !== '') {
        $fragments[] = $street;
      }

      $cp_localidad = trim(implode(' ', array_filter([
        (string) ($address['cp'] ?? ''),
        (string) ($address['localidad'] ?? ''),
      ], static fn (string $value): bool => trim($value) !== '')));
      if ($cp_localidad !== '') {
        $fragments[] = $cp_localidad;
      }

      $label = $fragments ? implode(' · ', $fragments) : 'Dirección #' . (int) $address['id_direccion'];
      $direcciones[] = [
        'id_direccion' => (int) $address['id_direccion'],
        'label' => $label,
      ];
    }

    $tutor_stmt = $pdo->prepare(
      'SELECT id_empresas_tutor, nombre, apellido1, apellido2
       FROM empresas_tutores
       WHERE id_empresa = :id_empresa
       ORDER BY apellido1, apellido2, nombre'
    );
    $tutor_stmt->execute(['id_empresa' => $company_id]);
    $tutores = array_map(static fn (array $tutor): array => [
      'id_empresas_tutor' => (int) $tutor['id_empresas_tutor'],
      'nombre' => practice_full_name($tutor),
    ], $tutor_stmt->fetchAll());

    echo json_encode(['ok' => true, 'direcciones' => $direcciones, 'tutores' => $tutores]);
    exit;
  }

  echo json_encode(['ok' => false, 'message' => 'Acción no válida.']);
  exit;
}

$errors = [];
$success_message = null;
$form_values = $_POST;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $curso_escolar_id = isset($_POST['id_curso_escolar']) ? (int) $_POST['id_curso_escolar'] : 0;
  $grupo_id = isset($_POST['id_grupo']) ? (int) $_POST['id_grupo'] : 0;
  $alumno_id = isset($_POST['id_alumno']) ? (int) $_POST['id_alumno'] : 0;
  $empresa_id = isset($_POST['id_empresa']) ? (int) $_POST['id_empresa'] : 0;
  $direccion_id = isset($_POST['id_direccion']) ? (int) $_POST['id_direccion'] : 0;
  $tutor_id = isset($_POST['id_empresa_tutor']) ? (int) $_POST['id_empresa_tutor'] : 0;
  $anexo = normalize_text($_POST['anexo'] ?? null);
  $fecha_inicio = normalize_text($_POST['fecha_inicio'] ?? null);
  $fecha_fin = normalize_text($_POST['fecha_fin'] ?? null);
  $horas = isset($_POST['horas']) ? (int) $_POST['horas'] : 0;
  $observaciones = normalize_text($_POST['observaciones'] ?? null);
  $horario = is_array($_POST['horario'] ?? null) ? $_POST['horario'] : [];

  if ($curso_escolar_id <= 0) {
    $errors[] = 'No hay un curso escolar activo disponible.';
  }
  if ($grupo_id <= 0) {
    $errors[] = 'El grupo es obligatorio.';
  }
  if ($alumno_id <= 0) {
    $errors[] = 'El alumno es obligatorio.';
  }
  if ($empresa_id <= 0) {
    $errors[] = 'La empresa es obligatoria.';
  }
  if ($fecha_inicio === null || !valid_date($fecha_inicio)) {
    $errors[] = 'La fecha de inicio es obligatoria y debe ser válida.';
  }
  if ($fecha_fin === null || !valid_date($fecha_fin)) {
    $errors[] = 'La fecha de fin calculada es obligatoria y debe ser válida.';
  }
  if ($horas < 0) {
    $errors[] = 'Las horas a realizar no pueden ser negativas.';
  }

  if (!$errors) {
    try {
      $pdo->beginTransaction();

      $insert_practice_stmt = $pdo->prepare(
        'INSERT INTO practicas (
          id_alumno, id_empresa, id_direccion, id_empresa_tutor, anexo, id_practicas_estado,
          fecha_inicio, fecha_fin, horas, observaciones
        ) VALUES (
          :id_alumno, :id_empresa, :id_direccion, :id_empresa_tutor, :anexo, :id_practicas_estado,
          :fecha_inicio, :fecha_fin, :horas, :observaciones
        )'
      );

      $insert_practice_stmt->execute([
        'id_alumno' => $alumno_id,
        'id_empresa' => $empresa_id,
        'id_direccion' => $direccion_id > 0 ? $direccion_id : null,
        'id_empresa_tutor' => $tutor_id > 0 ? $tutor_id : null,
        'anexo' => $anexo !== null && ctype_digit($anexo) ? (int) $anexo : null,
        'id_practicas_estado' => 1,
        'fecha_inicio' => $fecha_inicio,
        'fecha_fin' => $fecha_fin,
        'horas' => $horas,
        'observaciones' => $observaciones,
      ]);

      $practice_id = (int) $pdo->lastInsertId();
      $insert_schedule_stmt = $pdo->prepare(
        'INSERT INTO practicas_horario (id_practica, dia_semana, hora_entrada, hora_salida)
         VALUES (:id_practica, :dia_semana, :hora_entrada, :hora_salida)'
      );

      for ($day = 1; $day <= 7; $day++) {
        $day_data = is_array($horario[$day] ?? null) ? $horario[$day] : [];
        $segments = [
          [
            'entrada' => normalize_text($day_data['manana_entrada'] ?? null),
            'salida' => normalize_text($day_data['manana_salida'] ?? null),
          ],
          [
            'entrada' => normalize_text($day_data['tarde_entrada'] ?? null),
            'salida' => normalize_text($day_data['tarde_salida'] ?? null),
          ],
        ];

        foreach ($segments as $segment) {
          if ($segment['entrada'] === null || $segment['salida'] === null) {
            continue;
          }
          if ($segment['salida'] <= $segment['entrada']) {
            continue;
          }

          $insert_schedule_stmt->execute([
            'id_practica' => $practice_id,
            'dia_semana' => $day,
            'hora_entrada' => $segment['entrada'] . ':00',
            'hora_salida' => $segment['salida'] . ':00',
          ]);
        }
      }

      $pdo->commit();
      $success_message = 'Práctica creada correctamente.';
      $form_values = [];
    } catch (Throwable $error) {
      if ($pdo->inTransaction()) {
        $pdo->rollBack();
      }
      $errors[] = 'No se pudo guardar la práctica.';
    }
  }
}

$groups = [];
if ($active_course_id > 0) {
  $groups_stmt = $pdo->prepare(
    'SELECT DISTINCT g.id_grupo, g.grupo
     FROM alumno_curso ac
     INNER JOIN grupos g ON g.id_grupo = ac.id_grupo
     WHERE ac.id_curso_escolar = :id_curso_escolar
     ORDER BY g.grupo'
  );
  $groups_stmt->execute(['id_curso_escolar' => $active_course_id]);
  $groups = $groups_stmt->fetchAll();
}

if (!$groups) {
  $groups = $pdo->query('SELECT id_grupo, grupo FROM grupos ORDER BY grupo')->fetchAll();
}

$companies = $pdo->query('SELECT id_empresa, nombre, apellido1, apellido2 FROM empresas ORDER BY nombre, apellido1, apellido2')->fetchAll();
$no_lectivos = $pdo->query('SELECT fecha FROM no_lectivos')->fetchAll(PDO::FETCH_COLUMN);

$selected_group = isset($form_values['id_grupo']) ? (int) $form_values['id_grupo'] : 0;
$selected_student = isset($form_values['id_alumno']) ? (int) $form_values['id_alumno'] : 0;
$selected_company = isset($form_values['id_empresa']) ? (int) $form_values['id_empresa'] : 0;
$selected_address = isset($form_values['id_direccion']) ? (int) $form_values['id_direccion'] : 0;
$selected_tutor = isset($form_values['id_empresa_tutor']) ? (int) $form_values['id_empresa_tutor'] : 0;

$students = [];
if ($selected_group > 0 && $active_course_id > 0) {
  $students_stmt = $pdo->prepare(
    'SELECT DISTINCT a.id_alumno, a.nombre, a.apellido1, a.apellido2
     FROM alumno_curso ac
     INNER JOIN alumnos a ON a.id_alumno = ac.id_alumno
     WHERE ac.id_grupo = :id_grupo AND ac.id_curso_escolar = :id_curso_escolar
     ORDER BY a.apellido1, a.apellido2, a.nombre'
  );
  $students_stmt->execute([
    'id_grupo' => $selected_group,
    'id_curso_escolar' => $active_course_id,
  ]);
  $students = $students_stmt->fetchAll();
}

$company_addresses = [];
$company_tutors = [];
if ($selected_company > 0) {
  $address_stmt = $pdo->prepare(
    'SELECT d.id_direccion,
            d.etiqueta,
            d.nombre_via,
            d.numero,
            d.cp,
            v.via,
            l.nombre AS localidad
     FROM direcciones d
     LEFT JOIN vias v ON v.id_via = d.id_via
     LEFT JOIN localidades l ON l.id_localidad = d.id_localidad
     WHERE d.id_empresa = :id_empresa
     ORDER BY d.principal DESC, d.id_direccion'
  );
  $address_stmt->execute(['id_empresa' => $selected_company]);
  $company_addresses = $address_stmt->fetchAll();

  $tutor_stmt = $pdo->prepare(
    'SELECT id_empresas_tutor, nombre, apellido1, apellido2
     FROM empresas_tutores
     WHERE id_empresa = :id_empresa
     ORDER BY apellido1, apellido2, nombre'
  );
  $tutor_stmt->execute(['id_empresa' => $selected_company]);
  $company_tutors = $tutor_stmt->fetchAll();
}

$page_title = 'Nueva práctica | Gestor de Alumnos';
$active_page = 'practicas';
$dias_semana = [
  1 => 'Lunes',
  2 => 'Martes',
  3 => 'Miércoles',
  4 => 'Jueves',
  5 => 'Viernes',
  6 => 'Sábado',
  7 => 'Domingo',
];
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
          <h1>Nueva práctica</h1>
          <p class="subheading">Registra una práctica y define el horario semanal del alumno.</p>
        </div>
        <div class="header-actions">
          <a class="edit-toggle" href="practicas.php">Volver a prácticas</a>
        </div>
      </header>

      <?php if ($errors): ?>
        <section class="panel">
          <div class="panel-header">
            <h3>Revisa el formulario</h3>
            <p>Hay datos pendientes antes de guardar la práctica.</p>
          </div>
          <ul class="form-errors">
            <?php foreach ($errors as $error): ?>
              <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
            <?php endforeach; ?>
          </ul>
        </section>
      <?php endif; ?>

      <?php if ($success_message !== null): ?>
        <section class="panel">
          <div class="panel-header">
            <h3><?php echo htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8'); ?></h3>
          </div>
        </section>
      <?php endif; ?>

      <form method="post" class="panel entity-form">
        <input type="hidden" name="id_curso_escolar" value="<?php echo $active_course_id; ?>">

        <section class="entity-section">
          <div class="panel-header">
            <h3>Datos generales</h3>
            <p>Selecciona curso, alumno y empresa.</p>
          </div>
          <div class="entity-grid">
            <label>
              Curso escolar
              <input type="text" value="<?php echo htmlspecialchars((string) ($active_course['curso_escolar'] ?? 'No disponible'), ENT_QUOTES, 'UTF-8'); ?>" readonly>
            </label>
            <label>
              Grupo
              <select name="id_grupo" id="id_grupo" required>
                <option value="">Selecciona un grupo</option>
                <?php foreach ($groups as $group): ?>
                  <option value="<?php echo (int) $group['id_grupo']; ?>" <?php echo (int) $group['id_grupo'] === $selected_group ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars((string) $group['grupo'], ENT_QUOTES, 'UTF-8'); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </label>
            <label>
              Alumno
              <select name="id_alumno" id="id_alumno" <?php echo $selected_group > 0 ? '' : 'disabled'; ?> required>
                <option value=""><?php echo $selected_group > 0 ? 'Selecciona un alumno' : 'Selecciona primero un grupo'; ?></option>
                <?php foreach ($students as $student): ?>
                  <option value="<?php echo (int) $student['id_alumno']; ?>" <?php echo (int) $student['id_alumno'] === $selected_student ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars(practice_full_name($student), ENT_QUOTES, 'UTF-8'); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </label>
            <label>
              Empresa
              <select name="id_empresa" id="id_empresa" required>
                <option value="">Selecciona una empresa</option>
                <?php foreach ($companies as $company): ?>
                  <option value="<?php echo (int) $company['id_empresa']; ?>" <?php echo (int) $company['id_empresa'] === $selected_company ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars(practice_full_name($company), ENT_QUOTES, 'UTF-8'); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </label>
            <label>
              Dirección del centro de trabajo
              <select name="id_direccion" id="id_direccion" <?php echo $selected_company > 0 ? '' : 'disabled'; ?>>
                <option value=""><?php echo $selected_company > 0 ? 'Selecciona una dirección' : 'Selecciona primero una empresa'; ?></option>
                <?php foreach ($company_addresses as $address): ?>
                  <?php
                    $label_parts = array_filter([
                      (string) ($address['etiqueta'] ?? ''),
                      trim(implode(' ', array_filter([
                        (string) ($address['via'] ?? ''),
                        (string) ($address['nombre_via'] ?? ''),
                        (string) ($address['numero'] ?? ''),
                      ], static fn (string $value): bool => trim($value) !== ''))),
                      trim(implode(' ', array_filter([
                        (string) ($address['cp'] ?? ''),
                        (string) ($address['localidad'] ?? ''),
                      ], static fn (string $value): bool => trim($value) !== ''))),
                    ], static fn (string $value): bool => trim($value) !== '');
                  ?>
                  <option value="<?php echo (int) $address['id_direccion']; ?>" <?php echo (int) $address['id_direccion'] === $selected_address ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($label_parts ? implode(' · ', $label_parts) : ('Dirección #' . (int) $address['id_direccion']), ENT_QUOTES, 'UTF-8'); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </label>
            <label>
              Tutor en la empresa
              <select name="id_empresa_tutor" id="id_empresa_tutor" <?php echo $selected_company > 0 ? '' : 'disabled'; ?>>
                <option value=""><?php echo $selected_company > 0 ? 'Selecciona un tutor' : 'Selecciona primero una empresa'; ?></option>
                <?php foreach ($company_tutors as $tutor): ?>
                  <option value="<?php echo (int) $tutor['id_empresas_tutor']; ?>" <?php echo (int) $tutor['id_empresas_tutor'] === $selected_tutor ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars(practice_full_name($tutor), ENT_QUOTES, 'UTF-8'); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </label>
          </div>
        </section>

        <section class="entity-section">
          <div class="panel-header">
            <h3>Planificación</h3>
            <p>Define fechas, horas y observaciones.</p>
          </div>
          <div class="entity-grid">
            <label>
              Anexo
              <input type="number" name="anexo" min="0" step="1" value="<?php echo htmlspecialchars((string) ($form_values['anexo'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </label>
            <label>
              Fecha de inicio
              <input type="date" name="fecha_inicio" id="fecha_inicio" value="<?php echo htmlspecialchars((string) ($form_values['fecha_inicio'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
            </label>
            <label>
              Horas a realizar
              <input type="number" name="horas" id="horas" min="0" step="1" value="<?php echo htmlspecialchars((string) ($form_values['horas'] ?? '500'), ENT_QUOTES, 'UTF-8'); ?>" required>
            </label>
            <label>
              Fecha de fin
              <input type="date" name="fecha_fin" id="fecha_fin" value="<?php echo htmlspecialchars((string) ($form_values['fecha_fin'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" readonly required>
            </label>
          </div>
          <label>
            Observaciones
            <textarea name="observaciones" id="observaciones"><?php echo htmlspecialchars((string) ($form_values['observaciones'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
          </label>
        </section>

        <section class="entity-section">
          <div class="panel-header">
            <h3>Horario</h3>
            <p>Introduce tramos de mañana y tarde para cada día.</p>
          </div>
          <div class="panel-grid">
            <table>
              <thead>
                <tr>
                  <th rowspan="2">Día</th>
                  <th colspan="2">Mañana</th>
                  <th colspan="2">Tarde</th>
                  <th rowspan="2">Total</th>
                </tr>
                <tr>
                  <th>Entrada</th>
                  <th>Salida</th>
                  <th>Entrada</th>
                  <th>Salida</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($dias_semana as $day_number => $day_name): ?>
                  <?php $day_values = is_array($form_values['horario'][$day_number] ?? null) ? $form_values['horario'][$day_number] : []; ?>
                  <tr>
                    <td><?php echo htmlspecialchars($day_name, ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><input type="time" data-day="<?php echo $day_number; ?>" data-segment="manana_entrada" name="horario[<?php echo $day_number; ?>][manana_entrada]" value="<?php echo htmlspecialchars((string) ($day_values['manana_entrada'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></td>
                    <td><input type="time" data-day="<?php echo $day_number; ?>" data-segment="manana_salida" name="horario[<?php echo $day_number; ?>][manana_salida]" value="<?php echo htmlspecialchars((string) ($day_values['manana_salida'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></td>
                    <td><input type="time" data-day="<?php echo $day_number; ?>" data-segment="tarde_entrada" name="horario[<?php echo $day_number; ?>][tarde_entrada]" value="<?php echo htmlspecialchars((string) ($day_values['tarde_entrada'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></td>
                    <td><input type="time" data-day="<?php echo $day_number; ?>" data-segment="tarde_salida" name="horario[<?php echo $day_number; ?>][tarde_salida]" value="<?php echo htmlspecialchars((string) ($day_values['tarde_salida'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></td>
                    <td><output data-day-total="<?php echo $day_number; ?>">0</output></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </section>

        <div class="form-actions">
          <button type="submit" class="edit-toggle">Guardar práctica</button>
          <a href="practicas.php" class="ghost-button">Cancelar</a>
        </div>
      </form>
    </main>
  </div>

  <script>
    const groupSelect = document.getElementById('id_grupo');
    const studentSelect = document.getElementById('id_alumno');
    const companySelect = document.getElementById('id_empresa');
    const addressSelect = document.getElementById('id_direccion');
    const tutorSelect = document.getElementById('id_empresa_tutor');
    const hoursInput = document.getElementById('horas');
    const startDateInput = document.getElementById('fecha_inicio');
    const endDateInput = document.getElementById('fecha_fin');
    const timeInputs = document.querySelectorAll('input[type="time"][data-day]');
    const nonTeachingDays = new Set(<?php echo json_encode(array_values($no_lectivos), JSON_UNESCAPED_UNICODE); ?>);

    const resetSelect = (select, placeholder) => {
      select.innerHTML = '';
      const option = document.createElement('option');
      option.value = '';
      option.textContent = placeholder;
      select.appendChild(option);
    };

    const parseTimeToMinutes = (value) => {
      if (!value || !/^\d{2}:\d{2}$/.test(value)) {
        return null;
      }
      const [hours, minutes] = value.split(':').map(Number);
      return hours * 60 + minutes;
    };

    const segmentHours = (startValue, endValue) => {
      const start = parseTimeToMinutes(startValue);
      const end = parseTimeToMinutes(endValue);
      if (start === null || end === null || end <= start) {
        return 0;
      }
      return (end - start) / 60;
    };

    const dayHours = (day) => {
      const mIn = document.querySelector(`input[data-day="${day}"][data-segment="manana_entrada"]`)?.value || '';
      const mOut = document.querySelector(`input[data-day="${day}"][data-segment="manana_salida"]`)?.value || '';
      const tIn = document.querySelector(`input[data-day="${day}"][data-segment="tarde_entrada"]`)?.value || '';
      const tOut = document.querySelector(`input[data-day="${day}"][data-segment="tarde_salida"]`)?.value || '';
      return segmentHours(mIn, mOut) + segmentHours(tIn, tOut);
    };

    const updateDayTotals = () => {
      for (let day = 1; day <= 7; day += 1) {
        const total = dayHours(day);
        const output = document.querySelector(`output[data-day-total="${day}"]`);
        if (!output) continue;
        output.textContent = Number.isInteger(total) ? String(total) : total.toFixed(2);
      }
    };

    const toIsoDate = (date) => {
      const y = date.getFullYear();
      const m = String(date.getMonth() + 1).padStart(2, '0');
      const d = String(date.getDate()).padStart(2, '0');
      return `${y}-${m}-${d}`;
    };

    const getWeeklyTeachingHours = () => {
      const result = {};
      for (let day = 1; day <= 7; day += 1) {
        result[day] = day <= 5 ? dayHours(day) : 0;
      }
      return result;
    };

    const calculateEndDate = () => {
      const startValue = startDateInput.value;
      const targetHours = Math.max(0, Number(hoursInput.value || 0));
      const weeklyHours = getWeeklyTeachingHours();
      const weeklyTotal = Object.values(weeklyHours).reduce((sum, value) => sum + value, 0);

      if (!startValue || targetHours <= 0 || weeklyTotal <= 0) {
        endDateInput.value = '';
        return;
      }

      const startDate = new Date(`${startValue}T00:00:00`);
      if (Number.isNaN(startDate.getTime())) {
        endDateInput.value = '';
        return;
      }

      let current = new Date(startDate);
      let accumulated = 0;
      let guard = 0;

      while (guard < 4000) {
        guard += 1;
        const dayOfWeek = current.getDay();
        const mappedDay = dayOfWeek === 0 ? 7 : dayOfWeek;
        const dateKey = toIsoDate(current);

        if (mappedDay <= 5 && !nonTeachingDays.has(dateKey)) {
          accumulated += weeklyHours[mappedDay] || 0;
          if (accumulated >= targetHours) {
            endDateInput.value = dateKey;
            return;
          }
        }

        current.setDate(current.getDate() + 1);
      }

      endDateInput.value = '';
    };

    const updateHoursByStudent = async () => {
      const studentId = Number(studentSelect.value || 0);
      if (studentId <= 0) {
        hoursInput.value = '500';
        calculateEndDate();
        return;
      }

      try {
        const response = await fetch(`practica_nueva.php?action=student_hours&id_alumno=${studentId}`);
        const data = await response.json();
        const approved = data?.horas_ffe_aprobadas;
        const result = approved === null || approved === undefined ? 500 : Math.max(0, 500 - Number(approved));
        hoursInput.value = String(Number.isFinite(result) ? result : 500);
      } catch (error) {
        hoursInput.value = '500';
      }

      calculateEndDate();
    };

    groupSelect.addEventListener('change', async () => {
      const groupId = Number(groupSelect.value || 0);
      resetSelect(studentSelect, groupId > 0 ? 'Cargando alumnos…' : 'Selecciona primero un grupo');
      studentSelect.disabled = groupId <= 0;

      if (groupId <= 0) {
        hoursInput.value = '500';
        calculateEndDate();
        return;
      }

      try {
        const response = await fetch(`practica_nueva.php?action=students&group_id=${groupId}`);
        const data = await response.json();
        resetSelect(studentSelect, 'Selecciona un alumno');
        (data.students || []).forEach((student) => {
          const option = document.createElement('option');
          option.value = String(student.id_alumno);
          option.textContent = student.nombre;
          studentSelect.appendChild(option);
        });
      } catch (error) {
        resetSelect(studentSelect, 'No se pudieron cargar alumnos');
      }

      studentSelect.disabled = false;
    });

    studentSelect.addEventListener('change', updateHoursByStudent);

    companySelect.addEventListener('change', async () => {
      const companyId = Number(companySelect.value || 0);
      resetSelect(addressSelect, companyId > 0 ? 'Cargando direcciones…' : 'Selecciona primero una empresa');
      resetSelect(tutorSelect, companyId > 0 ? 'Cargando tutores…' : 'Selecciona primero una empresa');
      addressSelect.disabled = companyId <= 0;
      tutorSelect.disabled = companyId <= 0;

      if (companyId <= 0) {
        return;
      }

      try {
        const response = await fetch(`practica_nueva.php?action=company_data&id_empresa=${companyId}`);
        const data = await response.json();

        resetSelect(addressSelect, 'Selecciona una dirección');
        (data.direcciones || []).forEach((address) => {
          const option = document.createElement('option');
          option.value = String(address.id_direccion);
          option.textContent = address.label;
          addressSelect.appendChild(option);
        });

        resetSelect(tutorSelect, 'Selecciona un tutor');
        (data.tutores || []).forEach((tutor) => {
          const option = document.createElement('option');
          option.value = String(tutor.id_empresas_tutor);
          option.textContent = tutor.nombre;
          tutorSelect.appendChild(option);
        });
      } catch (error) {
        resetSelect(addressSelect, 'No se pudieron cargar direcciones');
        resetSelect(tutorSelect, 'No se pudieron cargar tutores');
      }

      addressSelect.disabled = false;
      tutorSelect.disabled = false;
    });

    timeInputs.forEach((input) => {
      input.addEventListener('input', () => {
        updateDayTotals();
        calculateEndDate();
      });
    });

    startDateInput.addEventListener('change', calculateEndDate);
    hoursInput.addEventListener('input', calculateEndDate);

    updateDayTotals();
    calculateEndDate();
  </script>
</body>
</html>
