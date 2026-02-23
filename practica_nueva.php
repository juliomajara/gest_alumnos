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

function valid_time(?string $value): bool {
  if ($value === null) {
    return false;
  }

  if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value)) {
    return false;
  }

  return true;
}

function time_to_minutes(string $value): int {
  [$hours, $minutes] = array_map('intval', explode(':', $value));
  return ($hours * 60) + $minutes;
}

/**
 * @return array{errors: array<int, string>, day_totals: array<int, float>, weekly_total: float, has_weekend_schedule: bool, has_weekday_over_8: bool}
 */
function analyze_schedule(array $horario): array {
  $errors = [];
  $day_totals = [];
  $weekly_total = 0.0;
  $has_weekend_schedule = false;
  $has_weekday_over_8 = false;
  $day_labels = [
    1 => 'lunes',
    2 => 'martes',
    3 => 'miércoles',
    4 => 'jueves',
    5 => 'viernes',
    6 => 'sábado',
    7 => 'domingo',
  ];

  for ($day = 1; $day <= 7; $day++) {
    $day_data = is_array($horario[$day] ?? null) ? $horario[$day] : [];
    $segments = [
      'mañana' => [
        'entrada' => normalize_text($day_data['manana_entrada'] ?? null),
        'salida' => normalize_text($day_data['manana_salida'] ?? null),
      ],
      'tarde' => [
        'entrada' => normalize_text($day_data['tarde_entrada'] ?? null),
        'salida' => normalize_text($day_data['tarde_salida'] ?? null),
      ],
    ];

    $day_total = 0.0;
    $has_any_segment = false;

    foreach ($segments as $segment_name => $segment) {
      $entrada = $segment['entrada'];
      $salida = $segment['salida'];

      if ($entrada === null && $salida === null) {
        continue;
      }

      if ($entrada === null || $salida === null) {
        $errors[] = sprintf('El tramo de %s del %s debe tener hora de entrada y salida.', $segment_name, $day_labels[$day]);
        continue;
      }

      if (!valid_time($entrada) || !valid_time($salida)) {
        $errors[] = sprintf('El tramo de %s del %s tiene un formato de hora no válido.', $segment_name, $day_labels[$day]);
        continue;
      }

      $entrada_minutes = time_to_minutes($entrada);
      $salida_minutes = time_to_minutes($salida);
      if ($salida_minutes <= $entrada_minutes) {
        $errors[] = sprintf('La salida debe ser posterior a la entrada en el tramo de %s del %s.', $segment_name, $day_labels[$day]);
        continue;
      }

      $has_any_segment = true;
      $day_total += ($salida_minutes - $entrada_minutes) / 60;
    }

    $day_totals[$day] = $day_total;
    $weekly_total += $day_total;

    if ($day <= 5 && $day_total > 8) {
      $has_weekday_over_8 = true;
    }
    if ($day >= 6 && $has_any_segment) {
      $has_weekend_schedule = true;
    }
  }

  return [
    'errors' => $errors,
    'day_totals' => $day_totals,
    'weekly_total' => $weekly_total,
    'has_weekend_schedule' => $has_weekend_schedule,
    'has_weekday_over_8' => $has_weekday_over_8,
  ];
}

function resolve_address_province_id(PDO $pdo, int $direccion_id, int $empresa_id): ?int {
  if ($direccion_id > 0) {
    $stmt = $pdo->prepare('SELECT id_provincia FROM direcciones WHERE id_direccion = :id_direccion AND id_empresa = :id_empresa LIMIT 1');
    $stmt->execute([
      'id_direccion' => $direccion_id,
      'id_empresa' => $empresa_id,
    ]);
    $value = $stmt->fetchColumn();
    return $value !== false && $value !== null ? (int) $value : null;
  }

  return null;
}

/**
 * @return array{dates: array<string, bool>, configured: bool}
 */
function load_non_teaching_days(PDO $pdo): array {
  try {
    $rows = $pdo->query('SELECT fecha FROM no_lectivos')->fetchAll(PDO::FETCH_COLUMN);
  } catch (Throwable $error) {
    return [
      'dates' => [],
      'configured' => false,
    ];
  }

  $dates = [];
  foreach ($rows as $date) {
    $date_value = is_string($date) ? trim($date) : '';
    if (valid_date($date_value)) {
      $dates[$date_value] = true;
    }
  }

  return [
    'dates' => $dates,
    'configured' => true,
  ];
}

function has_schedule_for_day(array $horario, int $day): bool {
  $day_data = is_array($horario[$day] ?? null) ? $horario[$day] : [];
  $segments = [
    ['entrada' => normalize_text($day_data['manana_entrada'] ?? null), 'salida' => normalize_text($day_data['manana_salida'] ?? null)],
    ['entrada' => normalize_text($day_data['tarde_entrada'] ?? null), 'salida' => normalize_text($day_data['tarde_salida'] ?? null)],
  ];

  foreach ($segments as $segment) {
    if ($segment['entrada'] === null || $segment['salida'] === null) {
      continue;
    }
    if (!valid_time($segment['entrada']) || !valid_time($segment['salida'])) {
      continue;
    }
    if (time_to_minutes($segment['salida']) <= time_to_minutes($segment['entrada'])) {
      continue;
    }
    return true;
  }

  return false;
}

function calculate_real_end_date(string $fecha_fin, int $dias_extra, array $non_teaching_lookup, bool $allow_saturday, bool $allow_sunday): string {
  if ($dias_extra <= 0) {
    return $fecha_fin;
  }

  $current = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha_fin, new DateTimeZone('UTC'));
  if (!$current) {
    return $fecha_fin;
  }

  $counted_days = 0;
  $guard = 0;

  while ($counted_days < $dias_extra && $guard < 4000) {
    $guard += 1;
    $current = $current->modify('+1 day');
    $date_key = $current->format('Y-m-d');

    if (isset($non_teaching_lookup[$date_key])) {
      continue;
    }

    $day_of_week = (int) $current->format('N');
    if ($day_of_week === 6 && !$allow_saturday) {
      continue;
    }
    if ($day_of_week === 7 && !$allow_sunday) {
      continue;
    }

    $counted_days += 1;
  }

  return $current->format('Y-m-d');
}

function add_years_to_date(string $value, int $years): ?string {
  $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, new DateTimeZone('UTC'));
  if (!$date) {
    return null;
  }

  return $date->modify('+' . $years . ' years')->format('Y-m-d');
}

function compare_iso_dates(string $left, string $right): int {
  return strcmp($left, $right);
}

function inclusive_days_between(string $start, string $end): ?int {
  $start_date = DateTimeImmutable::createFromFormat('!Y-m-d', $start, new DateTimeZone('UTC'));
  $end_date = DateTimeImmutable::createFromFormat('!Y-m-d', $end, new DateTimeZone('UTC'));
  if (!$start_date || !$end_date || $end_date < $start_date) {
    return null;
  }

  return $start_date->diff($end_date)->days + 1;
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

function practice_address_label(array $address): string {
  $prefix = !empty($address['principal']) ? '[Principal]' : '[CT]';

  $street = trim(implode(' ', array_filter([
    trim((string) ($address['via'] ?? '')),
    trim((string) ($address['nombre_via'] ?? '')),
    trim((string) ($address['numero'] ?? '')),
  ], static fn (string $value): bool => $value !== '')));

  $parts = [];
  if ($street !== '') {
    $parts[] = $street;
  }

  $bloque = trim((string) ($address['bloque'] ?? ''));
  if ($bloque !== '') {
    $parts[] = 'Bloque ' . $bloque;
  }

  $escalera = trim((string) ($address['escalera'] ?? ''));
  if ($escalera !== '') {
    $parts[] = 'Esc. ' . $escalera;
  }

  $planta = trim((string) ($address['planta'] ?? ''));
  if ($planta !== '') {
    $parts[] = 'Planta ' . $planta;
  }

  $puerta = trim((string) ($address['puerta'] ?? ''));
  if ($puerta !== '') {
    $parts[] = $puerta;
  }

  $cpLocalidad = trim(implode(' ', array_filter([
    trim((string) ($address['cp'] ?? '')),
    trim((string) ($address['localidad'] ?? '')),
  ], static fn (string $value): bool => $value !== '')));
  if ($cpLocalidad !== '') {
    $parts[] = $cpLocalidad;
  }

  $direccionCompleta = $parts ? implode(', ', $parts) : ('Dirección #' . (int) ($address['id_direccion'] ?? 0));
  $provincia = trim((string) ($address['provincia'] ?? ''));
  if ($provincia !== '') {
    $direccionCompleta .= ' (' . $provincia . ')';
  }

  return $prefix . ' ' . $direccionCompleta;
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
              d.principal,
              d.nombre_via,
              d.numero,
              d.bloque,
              d.escalera,
              d.planta,
              d.puerta,
              d.cp,
              v.via,
              l.nombre AS localidad,
              p.nombre AS provincia
       FROM direcciones d
       LEFT JOIN vias v ON v.id_via = d.id_via
       LEFT JOIN localidades l ON l.id_localidad = d.id_localidad
       LEFT JOIN provincias p ON p.id_provincia = d.id_provincia
       WHERE d.id_empresa = :id_empresa
       ORDER BY d.principal DESC, d.id_direccion'
    );
    $address_stmt->execute(['id_empresa' => $company_id]);
    $direcciones = [];
    foreach ($address_stmt->fetchAll() as $address) {
      $direcciones[] = [
        'id_direccion' => (int) $address['id_direccion'],
        'label' => practice_address_label($address),
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
$anexo4_warning_message = null;
$anexo4_warning_reasons = [];
$pending_circ_excep_confirmation = false;
$minor_age_warning_message = null;
$minor_age_warning_details = [];
$pending_minor_age_confirmation = false;
$save_error_detail = null;
$form_values = $_POST;
$non_teaching_data = load_non_teaching_days($pdo);
$non_teaching_lookup = $non_teaching_data['dates'];
$non_teaching_configured = $non_teaching_data['configured'];
$non_teaching_warning = !$non_teaching_configured;

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
  $dias_extra_raw = normalize_text($_POST['dias_extra'] ?? null);
  $dias_extra = 0;
  $observaciones = normalize_text($_POST['observaciones'] ?? null);
  $instrucciones = normalize_text($_POST['instrucciones'] ?? null);
  $horario = is_array($_POST['horario'] ?? null) ? $_POST['horario'] : [];
  $confirm_circ_excep = ($_POST['confirm_circ_excep'] ?? '') === '1';
  $confirm_minor_age = ($_POST['confirm_minor_age'] ?? '') === '1';

  if ($dias_extra_raw !== null) {
    if (!preg_match('/^-?\d+$/', $dias_extra_raw)) {
      $errors[] = 'Los días extra deben ser un número entero.';
    } else {
      $dias_extra = (int) $dias_extra_raw;
    }
  }

  $dias_extra = max(0, min(20, $dias_extra));
  $form_values['dias_extra'] = (string) $dias_extra;

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
  $schedule_analysis = analyze_schedule($horario);
  foreach ($schedule_analysis['errors'] as $schedule_error) {
    $errors[] = $schedule_error;
  }

  if ($errors) {
    $save_error_detail = implode(' ', $errors);
  }

  $province_id = null;
  if ($empresa_id > 0) {
    $province_id = resolve_address_province_id($pdo, $direccion_id, $empresa_id);
  }

  $anexo4_reasons = [];
  if ($schedule_analysis['has_weekday_over_8']) {
    $anexo4_reasons[] = 'Hay al menos un día laborable con más de 8 horas.';
  }
  if ($schedule_analysis['has_weekend_schedule']) {
    $anexo4_reasons[] = 'Hay horario informado en sábado y/o domingo.';
  }
  if ($schedule_analysis['weekly_total'] > 40) {
    $anexo4_reasons[] = 'La suma de horas semanales es mayor de 40.';
  }
  if ($province_id !== null && $province_id !== 28) {
    $anexo4_reasons[] = 'El centro de trabajo está fuera de la provincia de Madrid.';
  }

  $unknown_province_by_policy = false;
  if ($empresa_id > 0 && $province_id === null) {
    $unknown_province_by_policy = true;
    $anexo4_reasons[] = 'No se pudo resolver la provincia del centro de trabajo; por seguridad se requiere Anexo 4.';
  }

  $requires_circ_excep = $anexo4_reasons !== [];

  $allow_saturday = has_schedule_for_day($horario, 6);
  $allow_sunday = has_schedule_for_day($horario, 7);
  $fecha_fin_real = null;
  if ($fecha_fin !== null && valid_date($fecha_fin)) {
    $fecha_fin_real = calculate_real_end_date($fecha_fin, $dias_extra, $non_teaching_lookup, $allow_saturday, $allow_sunday);
    $form_values['fecha_fin_real'] = $fecha_fin_real;
  }

  $mayor_edad = 1;
  $requires_minor_age_confirmation = false;
  if ($alumno_id > 0 && $fecha_inicio !== null && valid_date($fecha_inicio)) {
    $birth_stmt = $pdo->prepare('SELECT fecha_nacimiento FROM alumnos WHERE id_alumno = :id_alumno LIMIT 1');
    $birth_stmt->execute(['id_alumno' => $alumno_id]);
    $fecha_nacimiento = $birth_stmt->fetchColumn();
    $fecha_nacimiento = is_string($fecha_nacimiento) ? trim($fecha_nacimiento) : null;

    if ($fecha_nacimiento === null || !valid_date($fecha_nacimiento)) {
      $errors[] = 'No se pudo calcular la mayoría de edad porque la fecha de nacimiento del alumno no es válida.';
    } else {
      $fecha_cumple_18 = add_years_to_date($fecha_nacimiento, 18);
      if ($fecha_cumple_18 === null) {
        $errors[] = 'No se pudo calcular la mayoría de edad del alumno.';
      } else {
        $mayor_edad = compare_iso_dates($fecha_inicio, $fecha_cumple_18) >= 0 ? 1 : 0;

        if ($mayor_edad === 0) {
          $requires_minor_age_confirmation = true;
          $period_end = null;
          if ($fecha_fin_real !== null && valid_date($fecha_fin_real)) {
            $period_end = $fecha_fin_real;
          } elseif ($fecha_fin !== null && valid_date($fecha_fin)) {
            $period_end = $fecha_fin;
          }

          if ($period_end === null || compare_iso_dates($period_end, $fecha_inicio) < 0) {
            $minor_age_warning_message = 'El alumno/a será menor de edad al iniciar las prácticas.';
            $minor_age_warning_details[] = 'No se han podido calcular los porcentajes por falta de fechas válidas del periodo.';
          } elseif (compare_iso_dates($fecha_cumple_18, $period_end) > 0) {
            $minor_age_warning_message = 'El alumno/a será menor de edad durante todo el periodo de prácticas.';
          } else {
            $minor_days_end = (new DateTimeImmutable($fecha_cumple_18, new DateTimeZone('UTC')))->modify('-1 day')->format('Y-m-d');
            $minor_days = 0;
            if (compare_iso_dates($minor_days_end, $fecha_inicio) >= 0) {
              $minor_days = inclusive_days_between($fecha_inicio, $minor_days_end) ?? 0;
            }
            $total_days = inclusive_days_between($fecha_inicio, $period_end);

            if ($total_days === null || $total_days <= 0) {
              $minor_age_warning_message = 'El alumno/a será menor de edad al iniciar las prácticas y cumplirá 18 años durante las mismas.';
              $minor_age_warning_details[] = 'No se han podido calcular los porcentajes por falta de fechas válidas del periodo.';
            } else {
              $minor_percentage = round(($minor_days / $total_days) * 100, 1);
              $adult_percentage = round(100 - $minor_percentage, 1);
              $minor_age_warning_message = sprintf(
                'El alumno/a será menor de edad al iniciar las prácticas y cumplirá 18 años en el transcurso de las mismas (hará un %.1f%% de días siendo menor de edad y un %.1f%% de días siendo mayor de edad).',
                $minor_percentage,
                $adult_percentage
              );
            }
          }
        }
      }
    }
  }

  if ($minor_age_warning_message === null && $requires_minor_age_confirmation) {
    $minor_age_warning_message = 'El alumno/a será menor de edad al iniciar las prácticas.';
  }

  if (!$errors) {
    if ($requires_circ_excep && !$confirm_circ_excep) {
      $pending_circ_excep_confirmation = true;
      $anexo4_warning_message = 'Atención: esta práctica requiere rellenar la Solicitud de autorización para la realización de la FFE bajo circunstancias de carácter excepcional.';
      $anexo4_warning_reasons = $anexo4_reasons;
      if ($unknown_province_by_policy) {
        $anexo4_warning_reasons[] = 'Política aplicada: al no poder verificar provincia se marca circ_excep=1 para evitar incumplimientos.';
      }
    }

    if ($requires_minor_age_confirmation && !$confirm_minor_age) {
      $pending_minor_age_confirmation = true;
    }

    if (!$pending_circ_excep_confirmation && !$pending_minor_age_confirmation) {
      try {
        $pdo->beginTransaction();

        $insert_practice_stmt = $pdo->prepare(
          'INSERT INTO practicas (
            id_alumno, id_empresa, id_direccion, id_empresa_tutor, anexo, cancelada,
            fecha_inicio, fecha_fin, fecha_fin_real, horas, dias_extra, observaciones, instrucciones, circ_excep, mayor_edad
          ) VALUES (
            :id_alumno, :id_empresa, :id_direccion, :id_empresa_tutor, :anexo, :cancelada,
            :fecha_inicio, :fecha_fin, :fecha_fin_real, :horas, :dias_extra, :observaciones, :instrucciones, :circ_excep, :mayor_edad
          )'
        );

        $practice_inserted = $insert_practice_stmt->execute([
          'id_alumno' => $alumno_id,
          'id_empresa' => $empresa_id,
          'id_direccion' => $direccion_id > 0 ? $direccion_id : null,
          'id_empresa_tutor' => $tutor_id > 0 ? $tutor_id : null,
          'anexo' => $anexo !== null && ctype_digit($anexo) ? (int) $anexo : null,
          'cancelada' => 0,
          'fecha_inicio' => $fecha_inicio,
          'fecha_fin' => $fecha_fin,
          'fecha_fin_real' => $fecha_fin_real,
          'horas' => $horas,
          'dias_extra' => $dias_extra,
          'observaciones' => $observaciones,
          'instrucciones' => $instrucciones,
          'circ_excep' => $requires_circ_excep ? 1 : 0,
          'mayor_edad' => $mayor_edad,
        ]);

        if ($practice_inserted === false) {
          $error_info = $insert_practice_stmt->errorInfo();
          throw new RuntimeException(
            sprintf(
              'Error PDO en INSERT práctica. SQLSTATE: %s; Código: %s; Mensaje: %s.',
              (string) ($error_info[0] ?? 'N/A'),
              (string) ($error_info[1] ?? 'N/A'),
              (string) ($error_info[2] ?? 'N/A')
            )
          );
        }

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

            $schedule_inserted = $insert_schedule_stmt->execute([
              'id_practica' => $practice_id,
              'dia_semana' => $day,
              'hora_entrada' => $segment['entrada'] . ':00',
              'hora_salida' => $segment['salida'] . ':00',
            ]);

            if ($schedule_inserted === false) {
              $error_info = $insert_schedule_stmt->errorInfo();
              throw new RuntimeException(
                sprintf(
                  'Error PDO en INSERT horario. SQLSTATE: %s; Código: %s; Mensaje: %s.',
                  (string) ($error_info[0] ?? 'N/A'),
                  (string) ($error_info[1] ?? 'N/A'),
                  (string) ($error_info[2] ?? 'N/A')
                )
              );
            }
          }
        }

        $pdo->commit();
        header('Location: practica_detalle.php?id_practica=' . $practice_id);
        exit;
      } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
          $pdo->rollBack();
        }
        $errors[] = 'No se pudo guardar la práctica.';

        if ($error instanceof PDOException) {
          $error_info = $error->errorInfo;
          $save_error_detail = sprintf(
            'SQLSTATE: %s; Código PDO: %s; Mensaje: %s.',
            (string) ($error_info[0] ?? 'N/A'),
            (string) $error->getCode(),
            $error->getMessage()
          );
        } else {
          $save_error_detail = $error->getMessage();
        }
      }
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

$default_tutor_group = 0;
if ($active_course_id > 0) {
  $default_group_stmt = $pdo->prepare(
    'SELECT gt.id_grupo
     FROM grupos_tutores gt
     INNER JOIN profesores p ON p.id_profesor = gt.id_profesor
     WHERE gt.id_curso_escolar = :id_curso_escolar
       AND p.nombre = :nombre
       AND p.apellido1 = :apellido1
       AND p.apellido2 = :apellido2
     ORDER BY gt.id_grupo
     LIMIT 1'
  );
  $default_group_stmt->execute([
    'id_curso_escolar' => $active_course_id,
    'nombre' => 'Julio',
    'apellido1' => 'Sánchez',
    'apellido2' => 'Fernández',
  ]);
  $default_tutor_group = (int) ($default_group_stmt->fetchColumn() ?: 0);
}

$companies = $pdo->query('SELECT id_empresa, nombre, apellido1, apellido2 FROM empresas ORDER BY nombre, apellido1, apellido2')->fetchAll();
$no_lectivos = array_keys($non_teaching_lookup);

$selected_group = isset($form_values['id_grupo']) ? (int) $form_values['id_grupo'] : 0;
if ($selected_group <= 0 && $default_tutor_group > 0) {
  $selected_group = $default_tutor_group;
  $form_values['id_grupo'] = (string) $default_tutor_group;
}
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
            d.principal,
            d.nombre_via,
            d.numero,
            d.bloque,
            d.escalera,
            d.planta,
            d.puerta,
            d.cp,
            v.via,
            l.nombre AS localidad,
            p.nombre AS provincia
     FROM direcciones d
     LEFT JOIN vias v ON v.id_via = d.id_via
     LEFT JOIN localidades l ON l.id_localidad = d.id_localidad
     LEFT JOIN provincias p ON p.id_provincia = d.id_provincia
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
            <?php if ($save_error_detail !== null): ?>
              <p>Motivo: <?php echo htmlspecialchars($save_error_detail, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>
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

      <?php if ($pending_circ_excep_confirmation): ?>
        <section class="panel">
          <div class="panel-header">
            <h3><?php echo htmlspecialchars($anexo4_warning_message ?? '', ENT_QUOTES, 'UTF-8'); ?></h3>
            <p>Antes de guardar, confirma que deseas continuar con esta práctica.</p>
          </div>
          <?php if ($anexo4_warning_reasons): ?>
            <ul class="form-errors">
              <?php foreach ($anexo4_warning_reasons as $reason): ?>
                <li><?php echo htmlspecialchars($reason, ENT_QUOTES, 'UTF-8'); ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </section>
      <?php endif; ?>

      <?php if ($pending_minor_age_confirmation): ?>
        <section class="panel">
          <div class="panel-header">
            <h3><?php echo htmlspecialchars($minor_age_warning_message ?? '', ENT_QUOTES, 'UTF-8'); ?></h3>
            <p>Antes de guardar, confirma que deseas continuar con esta práctica.</p>
          </div>
          <?php if ($minor_age_warning_details): ?>
            <ul class="form-errors">
              <?php foreach ($minor_age_warning_details as $detail): ?>
                <li><?php echo htmlspecialchars($detail, ENT_QUOTES, 'UTF-8'); ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </section>
      <?php endif; ?>

      <form method="post" class="panel entity-form practica-nueva-form">
        <input type="hidden" name="id_curso_escolar" value="<?php echo $active_course_id; ?>">
        <?php if ($pending_circ_excep_confirmation): ?>
          <input type="hidden" name="confirm_circ_excep" value="1">
        <?php endif; ?>
        <?php if ($pending_minor_age_confirmation): ?>
          <input type="hidden" name="confirm_minor_age" value="1">
        <?php endif; ?>

        <section class="entity-section">
          <div class="panel-header">
            <h3>Datos generales</h3>
            <p>Selecciona curso, alumno y empresa.</p>
          </div>
          <div class="entity-grid">
            <label class="practica-curso">
              Curso escolar
              <input type="text" value="<?php echo htmlspecialchars((string) ($active_course['curso_escolar'] ?? 'No disponible'), ENT_QUOTES, 'UTF-8'); ?>" readonly>
            </label>
            <label class="practica-anexo">
              Anexo
              <input type="number" name="anexo" min="0" step="1" value="<?php echo htmlspecialchars((string) ($form_values['anexo'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            </label>
            <label class="practica-grupo">
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
            <label class="practica-alumno">
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
              Horas
              <input type="number" name="horas" id="horas" min="0" step="1" value="<?php echo htmlspecialchars((string) ($form_values['horas'] ?? '500'), ENT_QUOTES, 'UTF-8'); ?>" required>
            </label>
          </div>
          <div class="entity-grid entity-grid--3">
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
                  <option value="<?php echo (int) $address['id_direccion']; ?>" <?php echo (int) $address['id_direccion'] === $selected_address ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars(practice_address_label($address), ENT_QUOTES, 'UTF-8'); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </label>
            <label>
              Tutor
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

          <?php if ($non_teaching_warning): ?>
            <p class="subheading">Aviso: no lectivos no están configurados; los días extra se calcularán solo con restricción de sábado/domingo según horario.</p>
          <?php endif; ?>

          <div class="practica-nueva-layout">
            <div class="practica-nueva-block practica-nueva-block--horario">
              <div class="panel-header">
                <h3>Horario</h3>
                <p>Introduce tramos de mañana y tarde para cada día.</p>
              </div>
              <div class="panel-grid practica-horario" data-schedule-container>
                <table class="practica-horario-table">
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
            </div>

            <div class="practica-nueva-block practica-nueva-block--planificacion">
              <div class="panel-header">
                <h3>Planificación</h3>
                <p>Define fechas y horas.</p>
              </div>
              <div class="entity-grid practica-nueva-planificacion-grid">
                <label>
                  Fecha de inicio
                  <input type="date" name="fecha_inicio" id="fecha_inicio" value="<?php echo htmlspecialchars((string) ($form_values['fecha_inicio'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
                </label>
                <label>
                  Días extra
                  <input type="number" name="dias_extra" id="dias_extra" min="0" max="20" step="1" value="<?php echo htmlspecialchars((string) ($form_values['dias_extra'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </label>
                <label>
                  Fecha fin calculada
                  <input type="date" name="fecha_fin" id="fecha_fin" value="<?php echo htmlspecialchars((string) ($form_values['fecha_fin'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" readonly required>
                </label>
                <label>
                  Fecha fin real
                  <input type="date" name="fecha_fin_real" id="fecha_fin_real" value="<?php echo htmlspecialchars((string) ($form_values['fecha_fin_real'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" readonly>
                </label>
                <label>
                  Días
                  <input type="text" id="dias_computados" value="" readonly>
                </label>
                <label>
                  Horas último día
                  <input type="text" id="horas_ultimo_dia" value="" readonly>
                </label>
              </div>
            </div>
          </div>

          <div class="practica-nueva-block practica-nueva-block--observaciones">
            <div class="entity-grid entity-grid--full">
              <label>
                Observaciones
                <textarea name="observaciones" id="observaciones"><?php echo htmlspecialchars((string) ($form_values['observaciones'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
              </label>
              <label>
                Instrucciones
                <textarea name="instrucciones" id="instrucciones"><?php echo htmlspecialchars((string) ($form_values['instrucciones'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
              </label>
            </div>
          </div>
        </section>

        <div class="form-actions">
          <?php if ($pending_circ_excep_confirmation || $pending_minor_age_confirmation): ?>
            <button type="submit" class="edit-toggle">Guardar igualmente</button>
            <a href="practica_nueva.php" class="ghost-button">Volver y revisar</a>
          <?php else: ?>
            <button type="submit" class="edit-toggle">Guardar práctica</button>
            <a href="practicas.php" class="ghost-button">Cancelar</a>
          <?php endif; ?>
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
    const realEndDateInput = document.getElementById('fecha_fin_real');
    const extraDaysInput = document.getElementById('dias_extra');
    const computedDaysInput = document.getElementById('dias_computados');
    const lastDayHoursInput = document.getElementById('horas_ultimo_dia');
    const scheduleContainer = document.querySelector('[data-schedule-container]');
    const nonTeachingDays = new Set(<?php echo json_encode(array_values($no_lectivos), JSON_UNESCAPED_UNICODE); ?>);
    const nonTeachingConfigured = <?php echo $non_teaching_configured ? 'true' : 'false'; ?>;


    const copyMondayFieldToWeekdays = (sourceInput) => {
      if (!sourceInput || !scheduleContainer) {
        return;
      }

      const sourceDay = Number(sourceInput.dataset.day || 0);
      const sourceSegment = sourceInput.dataset.segment || '';
      if (sourceDay !== 1 || sourceSegment === '') {
        return;
      }

      for (let day = 2; day <= 5; day += 1) {
        const target = scheduleContainer.querySelector(`input[type="time"][data-day="${day}"][data-segment="${sourceSegment}"]`);
        if (!target) {
          continue;
        }
        target.value = sourceInput.value;
      }
    };

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

    const parseIsoDateUTC = (value) => {
      if (!/^\d{4}-\d{2}-\d{2}$/.test(value || '')) {
        return null;
      }
      const [year, month, day] = value.split('-').map(Number);
      const date = new Date(Date.UTC(year, month - 1, day));
      if (
        date.getUTCFullYear() !== year
        || (date.getUTCMonth() + 1) !== month
        || date.getUTCDate() !== day
      ) {
        return null;
      }
      return date;
    };

    const toIsoDate = (date) => {
      const y = date.getUTCFullYear();
      const m = String(date.getUTCMonth() + 1).padStart(2, '0');
      const d = String(date.getUTCDate()).padStart(2, '0');
      return `${y}-${m}-${d}`;
    };

    const hasScheduleOnDay = (day) => {
      const segments = ['manana', 'tarde'];
      for (const segment of segments) {
        const inValue = document.querySelector(`input[data-day="${day}"][data-segment="${segment}_entrada"]`)?.value || '';
        const outValue = document.querySelector(`input[data-day="${day}"][data-segment="${segment}_salida"]`)?.value || '';
        if (segmentHours(inValue, outValue) > 0) {
          return true;
        }
      }
      return false;
    };

    const updateRealEndDate = () => {
      if (!realEndDateInput) {
        return;
      }

      const calculatedEndValue = endDateInput.value;
      const calculatedEndDate = parseIsoDateUTC(calculatedEndValue);
      if (!calculatedEndDate) {
        realEndDateInput.value = '';
        return;
      }

      const parsedExtraDays = Number.parseInt(extraDaysInput?.value || '0', 10);
      const safeExtraDays = Number.isNaN(parsedExtraDays) ? 0 : Math.max(0, Math.min(20, parsedExtraDays));
      if (safeExtraDays === 0) {
        realEndDateInput.value = toIsoDate(calculatedEndDate);
        return;
      }

      const allowSaturday = hasScheduleOnDay(6);
      const allowSunday = hasScheduleOnDay(7);
      let current = new Date(calculatedEndDate);
      let counted = 0;
      let guard = 0;

      while (counted < safeExtraDays && guard < 4000) {
        guard += 1;
        current.setUTCDate(current.getUTCDate() + 1);
        const currentIso = toIsoDate(current);
        const dayOfWeek = current.getUTCDay();

        if (nonTeachingConfigured && nonTeachingDays.has(currentIso)) {
          continue;
        }
        if (dayOfWeek === 6 && !allowSaturday) {
          continue;
        }
        if (dayOfWeek === 0 && !allowSunday) {
          continue;
        }

        counted += 1;
      }

      realEndDateInput.value = toIsoDate(current);
    };

    const getWeeklyTeachingHours = () => {
      const result = {};
      for (let day = 1; day <= 7; day += 1) {
        result[day] = day <= 5 ? dayHours(day) : 0;
      }
      return result;
    };

    const formatHours = (value) => {
      if (!Number.isFinite(value)) {
        return '';
      }
      return Number.isInteger(value) ? String(value) : value.toFixed(2);
    };

    const calculatePlanning = () => {
      const startValue = startDateInput.value;
      const targetHours = Math.max(0, Number(hoursInput.value || 0));
      const weeklyHours = getWeeklyTeachingHours();
      const weeklyTotal = Object.values(weeklyHours).reduce((sum, value) => sum + value, 0);

      const resetPlanning = () => {
        endDateInput.value = '';
        computedDaysInput.value = '';
        lastDayHoursInput.value = '';
        updateRealEndDate();
      };

      if (!startValue || targetHours <= 0 || weeklyTotal <= 0) {
        resetPlanning();
        return;
      }

      const startDate = parseIsoDateUTC(startValue);
      if (!startDate) {
        resetPlanning();
        return;
      }

      let current = new Date(startDate);
      let accumulated = 0;
      let computedDays = 0;
      let lastDayHours = 0;
      let guard = 0;

      while (guard < 4000) {
        guard += 1;
        const dayOfWeek = current.getUTCDay();
        const mappedDay = dayOfWeek === 0 ? 7 : dayOfWeek;
        const dateKey = toIsoDate(current);

        if (mappedDay <= 5 && !nonTeachingDays.has(dateKey)) {
          const todayHours = weeklyHours[mappedDay] || 0;
          if (todayHours > 0) {
            computedDays += 1;
            const remainingHours = Math.max(0, targetHours - accumulated);
            lastDayHours = Math.min(todayHours, remainingHours);
            accumulated += lastDayHours;
          }

          if (accumulated >= targetHours) {
            endDateInput.value = dateKey;
            computedDaysInput.value = String(computedDays);
            lastDayHoursInput.value = formatHours(lastDayHours);
            updateRealEndDate();
            return;
          }
        }

        current.setUTCDate(current.getUTCDate() + 1);
      }

      resetPlanning();
    };

    const updateHoursByStudent = async () => {
      const studentId = Number(studentSelect.value || 0);
      if (studentId <= 0) {
        hoursInput.value = '500';
        calculatePlanning();
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

      calculatePlanning();
    };

    groupSelect.addEventListener('change', async () => {
      const groupId = Number(groupSelect.value || 0);
      resetSelect(studentSelect, groupId > 0 ? 'Cargando alumnos…' : 'Selecciona primero un grupo');
      studentSelect.disabled = groupId <= 0;

      if (groupId <= 0) {
        hoursInput.value = '500';
        calculatePlanning();
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

        const direcciones = Array.isArray(data?.direcciones) ? data.direcciones : [];
        const tutores = Array.isArray(data?.tutores) ? data.tutores : [];

        resetSelect(addressSelect, 'Selecciona una dirección');
        direcciones.forEach((address) => {
          const option = document.createElement('option');
          option.value = String(address.id_direccion);
          option.textContent = address.label;
          addressSelect.appendChild(option);
        });
        if (direcciones.length === 1) {
          addressSelect.value = String(direcciones[0].id_direccion);
        }

        resetSelect(tutorSelect, 'Selecciona un tutor');
        tutores.forEach((tutor) => {
          const option = document.createElement('option');
          option.value = String(tutor.id_empresas_tutor);
          option.textContent = tutor.nombre;
          tutorSelect.appendChild(option);
        });
        if (tutores.length === 1) {
          tutorSelect.value = String(tutores[0].id_empresas_tutor);
        }
      } catch (error) {
        resetSelect(addressSelect, 'No se pudieron cargar direcciones');
        resetSelect(tutorSelect, 'No se pudieron cargar tutores');
      }

      addressSelect.disabled = false;
      tutorSelect.disabled = false;
    });

    if (scheduleContainer) {
      scheduleContainer.addEventListener('input', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLInputElement) || target.type !== 'time' || !target.dataset.day) {
          return;
        }

        updateDayTotals();
        calculatePlanning();
      });

      scheduleContainer.addEventListener('change', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLInputElement) || target.type !== 'time' || !target.dataset.day) {
          return;
        }

        copyMondayFieldToWeekdays(target);
        updateDayTotals();
        calculatePlanning();
      });
    }

    startDateInput.addEventListener('change', calculatePlanning);
    hoursInput.addEventListener('input', calculatePlanning);
    extraDaysInput?.addEventListener('input', () => {
      const parsedValue = Number.parseInt(extraDaysInput.value || '0', 10);
      if (!Number.isNaN(parsedValue)) {
        extraDaysInput.value = String(Math.max(0, Math.min(20, parsedValue)));
      }
      updateRealEndDate();
    });

    updateDayTotals();
    calculatePlanning();
    updateRealEndDate();
  </script>
</body>
</html>
