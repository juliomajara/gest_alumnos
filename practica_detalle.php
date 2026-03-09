<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/practicas_pdfs.php';

function format_value($value, string $fallback = 'No disponible'): string {
  if ($value === null || $value === '') {
    return $fallback;
  }

  return (string) $value;
}

function format_date(?string $value, string $fallback = 'No disponible'): string {
  if ($value === null || $value === '') {
    return $fallback;
  }

  $date = DateTime::createFromFormat('Y-m-d', $value);
  if ($date && $date->format('Y-m-d') === $value) {
    return $date->format('d/m/Y');
  }

  return $value;
}

function format_time(?string $value, string $fallback = '—'): string {
  if ($value === null || $value === '') {
    return $fallback;
  }

  $time = DateTime::createFromFormat('H:i:s', $value);
  if ($time && $time->format('H:i:s') === $value) {
    return $time->format('H:i');
  }

  return $value;
}

function calculate_practice_status(array $practice): string {
  if ((int) ($practice['cancelada'] ?? 0) === 1) {
    return 'Cancelada';
  }

  $fecha_inicio = (string) ($practice['fecha_inicio'] ?? '');
  $fecha_fin_extra = (string) ($practice['fecha_fin_extra'] ?? '');

  if ($fecha_inicio === '' || $fecha_fin_extra === '') {
    return 'No disponible';
  }

  $today = (new DateTimeImmutable('today'))->format('Y-m-d');

  if ($today < $fecha_inicio) {
    return 'En espera';
  }

  if ($today <= $fecha_fin_extra) {
    return 'En curso';
  }

  return 'Finalizada';
}

function calculate_age(?string $birthDate): ?int {
  if ($birthDate === null || $birthDate === '') {
    return null;
  }

  $birth = DateTimeImmutable::createFromFormat('Y-m-d', $birthDate);
  if ($birth === false || $birth->format('Y-m-d') !== $birthDate) {
    return null;
  }

  return $birth->diff(new DateTimeImmutable('today'))->y;
}

function full_name(array $row, string $prefix): string {
  $parts = array_filter([
    trim((string) ($row[$prefix . '_apellido1'] ?? '')),
    trim((string) ($row[$prefix . '_apellido2'] ?? '')),
  ], static fn (string $value): bool => $value !== '');

  $name = trim((string) ($row[$prefix . '_nombre'] ?? ''));
  if ($parts === [] && $name === '') {
    return 'No disponible';
  }

  return trim(implode(' ', $parts) . ', ' . $name, ' ,');
}

function full_name_name_first(array $row, string $prefix): string {
  $parts = array_filter([
    trim((string) ($row[$prefix . '_nombre'] ?? '')),
    trim((string) ($row[$prefix . '_apellido1'] ?? '')),
    trim((string) ($row[$prefix . '_apellido2'] ?? '')),
  ], static fn (string $value): bool => $value !== '');

  return $parts !== [] ? implode(' ', $parts) : 'No disponible';
}

function build_address(array $practice): string {
  $parts = array_filter([
    trim((string) ($practice['direccion_via_tipo'] ?? '')),
    trim((string) ($practice['direccion_nombre_via'] ?? '')),
    trim((string) ($practice['direccion_numero'] ?? '')),
    ($practice['direccion_bloque'] ?? '') !== '' ? 'Bloque ' . $practice['direccion_bloque'] : '',
    ($practice['direccion_escalera'] ?? '') !== '' ? 'Esc. ' . $practice['direccion_escalera'] : '',
    ($practice['direccion_planta'] ?? '') !== '' ? 'Planta ' . $practice['direccion_planta'] : '',
    ($practice['direccion_puerta'] ?? '') !== '' ? 'Puerta ' . $practice['direccion_puerta'] : '',
    trim((string) ($practice['direccion_otros'] ?? '')),
  ], static fn (string $value): bool => $value !== '');

  $cpLocalidad = trim(implode(' ', array_filter([
    trim((string) ($practice['direccion_cp'] ?? '')),
    trim((string) ($practice['direccion_localidad'] ?? '')),
  ], static fn (string $value): bool => $value !== '')));

  if ($cpLocalidad !== '') {
    $parts[] = $cpLocalidad;
  }

  $provincia = trim((string) ($practice['direccion_provincia'] ?? ''));
  if ($provincia !== '') {
    $parts[] = $provincia;
  }

  $pais = trim((string) ($practice['direccion_pais'] ?? ''));
  if ($pais !== '') {
    $parts[] = $pais;
  }

  return $parts ? implode(', ', $parts) : 'No disponible';
}

$dias_semana = [
  1 => 'Lunes',
  2 => 'Martes',
  3 => 'Miércoles',
  4 => 'Jueves',
  5 => 'Viernes',
  6 => 'Sábado',
  7 => 'Domingo',
];

$id_practica_raw = $_GET['id_practica'] ?? ($_GET['id'] ?? null);
$id_practica = filter_var($id_practica_raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$action = isset($_GET['action']) ? (string) $_GET['action'] : '';
$post_action = isset($_POST['action']) ? (string) $_POST['action'] : '';

$load_error = null;
$practice = null;
$schedule_by_day = [];
$calendar_status = null;
$calendar_error = null;
$calendar_file_path = null;
$calendar_file_name = null;
$plan_status = null;
$plan_error = null;
$plan_file_path = null;
$plan_file_name = null;
$reactivate_status = null;
$reactivate_error = null;

$document_status_code = isset($_GET['doc_status']) ? (string) $_GET['doc_status'] : '';
if ($document_status_code === 'calendar_generated') {
  $calendar_status = 'Calendario generado correctamente.';
} elseif ($document_status_code === 'plan_generated') {
  $plan_status = 'Plan de formación generado correctamente.';
}

$document_error_message = isset($_GET['doc_error']) ? trim((string) $_GET['doc_error']) : '';
if ($document_error_message !== '') {
  if (str_starts_with($document_error_message, 'Calendario:')) {
    $calendar_error = trim(substr($document_error_message, strlen('Calendario:')));
  } elseif (str_starts_with($document_error_message, 'Plan:')) {
    $plan_error = trim(substr($document_error_message, strlen('Plan:')));
  }
}

if ($id_practica === false || $id_practica === null) {
  $load_error = 'No se ha indicado un identificador de práctica válido.';
} else {
  try {
    $pdo = db();

    $practice_stmt = $pdo->prepare(
      'SELECT
        p.*,
        p.fecha_fin,
        p.fecha_fin_extra,
        p.dias_extra,
        CASE
          WHEN COALESCE(p.cancelada, 0) = 1 THEN \'Cancelada\'
          ELSE NULL
        END AS estado_practica,
        COALESCE(p.cancelada, 0) AS id_practicas_estado,
        a.nia AS alumno_nia,
        a.dni AS alumno_dni,
        a.nombre AS alumno_nombre,
        a.apellido1 AS alumno_apellido1,
        a.apellido2 AS alumno_apellido2,
        a.fecha_nacimiento AS alumno_fecha_nacimiento,
        e.cif AS empresa_cif,
        e.convenio AS empresa_convenio,
        e.nombre AS empresa_nombre,
        e.nombre_comercial AS empresa_nombre_comercial,
        e.apellido1 AS empresa_apellido1,
        e.apellido2 AS empresa_apellido2,
        et.nombre AS tutor_nombre,
        et.apellido1 AS tutor_apellido1,
        et.apellido2 AS tutor_apellido2,
        et.dni AS tutor_dni,
        et.comentarios AS tutor_comentarios,
        d.etiqueta AS direccion_etiqueta,
        d.nombre_via AS direccion_nombre_via,
        d.numero AS direccion_numero,
        d.bloque AS direccion_bloque,
        d.escalera AS direccion_escalera,
        d.planta AS direccion_planta,
        d.puerta AS direccion_puerta,
        d.otros AS direccion_otros,
        d.cp AS direccion_cp,
        d.id_provincia AS direccion_id_provincia,
        d.principal AS direccion_principal,
        v.via AS direccion_via_tipo,
        ld.nombre AS direccion_localidad,
        ld.km_desde_getafe AS direccion_km_desde_getafe,
        ld.abono_desde_getafe AS direccion_abono_desde_getafe,
        pd.nombre AS direccion_provincia,
        pa.pais AS direccion_pais,
        ac.id_curso_escolar,
        ac.id_curso,
        ce.curso_escolar,
        c.curso AS curso_ordinal,
        ac.id_grupo,
        g.id_ciclo,
        g.grupo,
        ci.ciclo AS ciclo_nombre,
        ci.codigo AS ciclo_codigo,
        CONCAT_WS(\' \' , pc.apellido1, pc.apellido2, pc.nombre) AS tutor_centro,
        (
          SELECT c1.direccion_correo
          FROM correos c1
          WHERE c1.entidad_tipo = \'alumno\'
            AND c1.id_entidad = a.id_alumno
            AND TRIM(COALESCE(c1.etiqueta, \'\')) = \'Personal\'
          ORDER BY c1.id_correo ASC
          LIMIT 1
        ) AS alumno_email_personal,
        (
          SELECT c1.direccion_correo
          FROM correos c1
          WHERE c1.entidad_tipo = \'alumno\'
            AND c1.id_entidad = a.id_alumno
            AND TRIM(COALESCE(c1.etiqueta, \'\')) = \'EducaMadrid\'
          ORDER BY c1.id_correo ASC
          LIMIT 1
        ) AS alumno_email_educamadrid,
        (
          SELECT t1.telefono
          FROM telefonos t1
          WHERE t1.entidad_tipo = \'alumno\' AND t1.id_entidad = a.id_alumno
          ORDER BY t1.id_telefono ASC
          LIMIT 1
        ) AS alumno_telefono,
        (
          SELECT c2.direccion_correo
          FROM correos c2
          WHERE c2.entidad_tipo = \'empresa\' AND c2.id_entidad = e.id_empresa
          ORDER BY c2.id_correo ASC
          LIMIT 1
        ) AS empresa_email,
        (
          SELECT t2.telefono
          FROM telefonos t2
          WHERE t2.entidad_tipo = \'empresa\' AND t2.id_entidad = e.id_empresa
          ORDER BY t2.id_telefono ASC
          LIMIT 1
        ) AS empresa_telefono,
        (
          SELECT CONCAT_WS(\' \', ec.nombre, ec.apellido1, ec.apellido2)
          FROM empresas_contactos ec
          WHERE ec.id_empresa = e.id_empresa
          ORDER BY ec.id_empresa_contacto ASC
          LIMIT 1
        ) AS empresa_contacto_nombre,
        (
          SELECT cct.direccion_correo
          FROM correos cct
          WHERE cct.entidad_tipo = \'empresa_contacto\'
            AND cct.id_entidad = (
              SELECT ec1.id_empresa_contacto
              FROM empresas_contactos ec1
              WHERE ec1.id_empresa = e.id_empresa
              ORDER BY ec1.id_empresa_contacto ASC
              LIMIT 1
            )
          ORDER BY cct.id_correo ASC
          LIMIT 1
        ) AS empresa_contacto_email,
        (
          SELECT tct.telefono
          FROM telefonos tct
          WHERE tct.entidad_tipo = \'empresa_contacto\'
            AND tct.id_entidad = (
              SELECT ec2.id_empresa_contacto
              FROM empresas_contactos ec2
              WHERE ec2.id_empresa = e.id_empresa
              ORDER BY ec2.id_empresa_contacto ASC
              LIMIT 1
            )
          ORDER BY tct.id_telefono ASC
          LIMIT 1
        ) AS empresa_contacto_telefono,
        (
          SELECT c3.direccion_correo
          FROM correos c3
          WHERE c3.entidad_tipo = \'empresa_tutor\' AND c3.id_entidad = et.id_empresas_tutor
          ORDER BY c3.id_correo ASC
          LIMIT 1
        ) AS tutor_empresa_email,
        (
          SELECT t3.telefono
          FROM telefonos t3
          WHERE t3.entidad_tipo = \'empresa_tutor\' AND t3.id_entidad = et.id_empresas_tutor
          ORDER BY t3.id_telefono ASC
          LIMIT 1
        ) AS tutor_empresa_telefono,
        (
          SELECT c4.direccion_correo
          FROM correos c4
          WHERE c4.entidad_tipo = \'profesor\' AND c4.id_entidad = pc.id_profesor
          ORDER BY c4.id_correo ASC
          LIMIT 1
        ) AS tutor_centro_email,
        (
          SELECT t4.telefono
          FROM telefonos t4
          WHERE t4.entidad_tipo = \'profesor\' AND t4.id_entidad = pc.id_profesor
          ORDER BY t4.id_telefono ASC
          LIMIT 1
        ) AS tutor_centro_telefono
      FROM practicas p
      INNER JOIN alumnos a ON a.id_alumno = p.id_alumno
      INNER JOIN empresas e ON e.id_empresa = p.id_empresa
      LEFT JOIN empresas_tutores et ON et.id_empresas_tutor = p.id_empresa_tutor
      LEFT JOIN direcciones d ON d.id_direccion = p.id_direccion
      LEFT JOIN vias v ON v.id_via = d.id_via
      LEFT JOIN localidades ld ON ld.id_localidad = d.id_localidad
      LEFT JOIN provincias pd ON pd.id_provincia = d.id_provincia
      LEFT JOIN paises pa ON pa.id_pais = d.id_pais
      LEFT JOIN alumno_curso ac
        ON ac.id_alumno = p.id_alumno
       AND ac.id_curso_escolar = (
            SELECT MAX(ac2.id_curso_escolar)
            FROM alumno_curso ac2
            WHERE ac2.id_alumno = p.id_alumno
        )
      LEFT JOIN cursos_escolares ce ON ce.id_curso_escolar = ac.id_curso_escolar
      LEFT JOIN cursos c ON c.id_curso = ac.id_curso
      LEFT JOIN grupos g ON g.id_grupo = ac.id_grupo
      LEFT JOIN ciclos ci ON ci.id_ciclo = g.id_ciclo
      LEFT JOIN grupos_tutores gt ON gt.id_grupo = ac.id_grupo AND gt.id_curso_escolar = ac.id_curso_escolar
      LEFT JOIN profesores pc ON pc.id_profesor = gt.id_profesor
      WHERE p.id_practica = :id_practica
      LIMIT 1'
    );

    $practice_stmt->execute(['id_practica' => $id_practica]);
    $practice = $practice_stmt->fetch();

    if ($practice) {
      if ($post_action === 'reactivar_practica') {
        $id_alumno_practica = (int) ($practice['id_alumno'] ?? 0);

        if ($id_alumno_practica <= 0) {
          $reactivate_error = 'No se puede reactivar la práctica en este momento.';
        } elseif ((int) ($practice['cancelada'] ?? 0) === 0) {
          $reactivate_error = 'La práctica indicada ya está activa.';
        } else {
          $curso_actual_stmt = $pdo->prepare(
            'SELECT MAX(ac.id_curso_escolar)
             FROM alumno_curso ac
             WHERE ac.id_alumno = :id_alumno'
          );
          $curso_actual_stmt->execute(['id_alumno' => $id_alumno_practica]);
          $id_curso_actual = (int) ($curso_actual_stmt->fetchColumn() ?: 0);

          if ($id_curso_actual <= 0) {
            $reactivate_error = 'No se puede determinar el curso actual del alumno para reactivar la práctica.';
          } else {
            $active_practice_stmt = $pdo->prepare(
              'SELECT p.id_practica
               FROM practicas p
               INNER JOIN alumno_curso ac ON ac.id_alumno = p.id_alumno
               WHERE p.id_alumno = :id_alumno
                 AND ac.id_curso_escolar = :id_curso_escolar
                 AND COALESCE(p.cancelada, 0) = 0
                 AND p.id_practica <> :id_practica
               ORDER BY p.id_practica DESC
               LIMIT 1'
            );
            $active_practice_stmt->execute([
              'id_alumno' => $id_alumno_practica,
              'id_curso_escolar' => $id_curso_actual,
              'id_practica' => $id_practica,
            ]);
            $other_active_practice_id = (int) ($active_practice_stmt->fetchColumn() ?: 0);

            if ($other_active_practice_id > 0) {
              $reactivate_error = 'No se puede reactivar esta práctica porque el alumno ya tiene otra práctica activa en el curso actual.';
            } else {
              $latest_practice_stmt = $pdo->prepare(
                'SELECT p.id_practica
                 FROM practicas p
                 INNER JOIN alumno_curso ac ON ac.id_alumno = p.id_alumno
                 WHERE p.id_alumno = :id_alumno
                   AND ac.id_curso_escolar = :id_curso_escolar
                 ORDER BY
                   COALESCE(
                     NULLIF(p.fecha_fin_real, \'\'),
                     NULLIF(p.fecha_fin_extra, \'\'),
                     NULLIF(p.fecha_fin, \'\'),
                     NULLIF(p.fecha_inicio, \'\')
                   ) DESC,
                   NULLIF(p.fecha_inicio, \'\') DESC,
                   p.id_practica DESC
                 LIMIT 1'
              );
              $latest_practice_stmt->execute([
                'id_alumno' => $id_alumno_practica,
                'id_curso_escolar' => $id_curso_actual,
              ]);
              $latest_practice_id = (int) ($latest_practice_stmt->fetchColumn() ?: 0);

              if ($latest_practice_id !== (int) $id_practica) {
                $reactivate_error = 'No se puede activar esta práctica porque el alumno ha tenido una práctica posterior en el curso actual.';
              } else {
                $reactivate_stmt = $pdo->prepare(
                  'UPDATE practicas
                   SET cancelada = 0,
                       horas_hechas = NULL,
                       fecha_fin_real = NULL
                   WHERE id_practica = :id_practica'
                );
                $reactivate_stmt->execute(['id_practica' => $id_practica]);
                $reactivate_status = 'Práctica reactivada correctamente.';

                $practice_stmt->execute(['id_practica' => $id_practica]);
                $practice = $practice_stmt->fetch();
              }
            }
          }
        }
      }

      $schedule_stmt = $pdo->prepare(
        'SELECT id_practicas_horario, dia_semana, hora_entrada, hora_salida
         FROM practicas_horario
         WHERE id_practica = :id_practica
         ORDER BY dia_semana ASC, hora_entrada ASC'
      );
      $schedule_stmt->execute(['id_practica' => $id_practica]);

      foreach ($schedule_stmt->fetchAll() as $row) {
        $day = (int) $row['dia_semana'];
        if (!isset($schedule_by_day[$day])) {
          $schedule_by_day[$day] = [];
        }
        $schedule_by_day[$day][] = $row;
      }

      $document_paths = practicas_get_document_paths($practice);
      $calendarDirectory = $document_paths['calendar_directory'];
      $calendar_file_name = $document_paths['calendar_file_name'];
      $calendar_file_path = $document_paths['calendar_file_path'];
      $planDirectory = $document_paths['plan_directory'];
      $plan_file_name = $document_paths['plan_file_name'];
      $plan_file_path = $document_paths['plan_file_path'];

      if ($action === 'descargar_calendario') {
        $realBase = realpath($calendarDirectory);
        $realFile = $calendar_file_path !== null && file_exists($calendar_file_path) ? realpath($calendar_file_path) : false;

        if ($realBase !== false && $realFile !== false && str_starts_with($realFile, $realBase . DIRECTORY_SEPARATOR) && is_readable($realFile)) {
          header('Content-Type: application/pdf');
          header('Content-Disposition: attachment; filename="calendario_practicas.pdf"; filename*=UTF-8\'\'' . rawurlencode($calendar_file_name));
          header('Content-Length: ' . (string) filesize($realFile));
          header('X-Content-Type-Options: nosniff');
          readfile($realFile);
          exit;
        }

        $calendar_error = 'El calendario no existe o no está disponible para descarga.';
      }

      if ($action === 'generar_calendario') {
        header('Location: generar_calendario.php?id_practica=' . (int) $id_practica);
        exit;
      }

      if ($post_action === 'generar_plan_formacion') {
        header('Location: generar_plan_formacion.php?id_practica=' . (int) $id_practica);
        exit;
      }

      if ($action === 'descargar_plan_formacion') {
        $realBase = realpath($planDirectory);
        $realFile = $plan_file_path !== null && file_exists($plan_file_path) ? realpath($plan_file_path) : false;

        if ($realBase !== false && $realFile !== false && str_starts_with($realFile, $realBase . DIRECTORY_SEPARATOR) && is_readable($realFile)) {
          header('Content-Type: application/pdf');
          header('Content-Disposition: attachment; filename="plan_formacion.pdf"; filename*=UTF-8\'\'' . rawurlencode($plan_file_name));
          header('Content-Length: ' . (string) filesize($realFile));
          header('X-Content-Type-Options: nosniff');
          readfile($realFile);
          exit;
        }

        $plan_error = 'El plan de formación no existe o no está disponible para descarga.';
      }

    }
  } catch (Throwable $error) {
    $load_error = 'No se ha podido cargar el detalle de la práctica en este momento.';
  }
}

$practice_found = is_array($practice);
$student_name = $practice_found ? full_name($practice, 'alumno') : 'Práctica no encontrada';
$company_name = $practice_found ? format_value($practice['empresa_nombre'] ?? null) : 'Práctica no encontrada';
$company_commercial_name = $practice_found ? trim((string) ($practice['empresa_nombre_comercial'] ?? '')) : '';
$company_contact_name = $practice_found ? trim((string) ($practice['empresa_nombre'] ?? '')) : '';
$company_contact_lastname = $practice_found ? trim((string) ($practice['empresa_apellido1'] ?? '')) : '';
$company_summary_name = $company_commercial_name !== '' ? $company_commercial_name : $company_name;
if ($company_contact_name !== '' && $company_contact_lastname !== '') {
  $company_summary_name .= ' (' . $company_contact_name . ' ' . $company_contact_lastname . ')';
}
$page_title = $practice_found
  ? 'Detalle de práctica #' . (int) $practice['id_practica'] . ' | Gestor de Alumnos'
  : 'Práctica no encontrada | Gestor de Alumnos';
$active_page = 'practicas';
$calendar_exists = $practice_found && $calendar_file_path !== null && is_file($calendar_file_path);
$calendar_generated_at = $calendar_exists ? date('d/m/Y H:i', (int) filemtime($calendar_file_path)) : null;
$plan_exists = $practice_found && $plan_file_path !== null && is_file($plan_file_path);
$plan_generated_at = $plan_exists ? date('d/m/Y H:i', (int) filemtime($plan_file_path)) : null;
$practice_status = $practice_found ? calculate_practice_status($practice) : 'No disponible';
$practice_header_title = $practice_found
  ? $student_name . ' - (' . format_value($practice['empresa_convenio']) . ' / ' . format_value($practice['anexo']) . ') - ' . $practice_status
  : 'Práctica no encontrada';
$student_age = $practice_found ? calculate_age($practice['alumno_fecha_nacimiento'] ?? null) : null;
$student_birth_date = $practice_found
  ? format_date($practice['alumno_fecha_nacimiento'] ?? null) . ($student_age !== null ? ' (' . $student_age . ' años)' : '')
  : 'No disponible';
$seguimiento_rows = [];
$seguimiento_horas_hoy = '0,00';
$seguimiento_horas_hoy_valor = 0.0;
$seguimiento_horas_pendientes = '0,00';

if ($practice_found) {
  $fecha_inicio = DateTimeImmutable::createFromFormat('Y-m-d', (string) ($practice['fecha_inicio'] ?? ''));
  $fecha_fin_extra = DateTimeImmutable::createFromFormat('Y-m-d', (string) ($practice['fecha_fin_extra'] ?? ''));

  if ($fecha_inicio !== false && $fecha_fin_extra !== false && $fecha_inicio <= $fecha_fin_extra) {
    $segundos_por_dia_semana = [];
    foreach ($schedule_by_day as $day_number => $segments) {
      $total_segundos = 0;
      foreach ($segments as $segment) {
        $entrada = strtotime((string) $segment['hora_entrada']);
        $salida = strtotime((string) $segment['hora_salida']);
        if ($entrada !== false && $salida !== false && $salida > $entrada) {
          $total_segundos += ($salida - $entrada);
        }
      }

      if ($total_segundos > 0) {
        $segundos_por_dia_semana[(int) $day_number] = $total_segundos;
      }
    }

    $meses = [
      1 => 'Enero',
      2 => 'Febrero',
      3 => 'Marzo',
      4 => 'Abril',
      5 => 'Mayo',
      6 => 'Junio',
      7 => 'Julio',
      8 => 'Agosto',
      9 => 'Septiembre',
      10 => 'Octubre',
      11 => 'Noviembre',
      12 => 'Diciembre',
    ];

    $seguimiento_por_mes = [];
    $hoy = new DateTimeImmutable('today');
    $fecha_hasta_hoy = $hoy < $fecha_fin_extra ? $hoy : $fecha_fin_extra;
    $segundos_realizados = 0;

    for ($cursor = $fecha_inicio; $cursor <= $fecha_fin_extra; $cursor = $cursor->modify('+1 day')) {
      $dia_semana = (int) $cursor->format('N');
      if (!isset($segundos_por_dia_semana[$dia_semana])) {
        continue;
      }

      $clave_mes = $cursor->format('Y-m');
      if (!isset($seguimiento_por_mes[$clave_mes])) {
        $mes_numero = (int) $cursor->format('n');
        $seguimiento_por_mes[$clave_mes] = [
          'mes' => ($meses[$mes_numero] ?? $cursor->format('F')) . ' ' . $cursor->format('Y'),
          'dias' => 0,
        ];
      }

      $seguimiento_por_mes[$clave_mes]['dias']++;

      if ($cursor <= $fecha_hasta_hoy) {
        $segundos_realizados += $segundos_por_dia_semana[$dia_semana];
      }
    }

    foreach ($seguimiento_por_mes as $row) {
      $seguimiento_rows[] = [
        'mes' => $row['mes'],
        'dias' => (string) $row['dias'],
      ];
    }

    $seguimiento_horas_hoy_valor = $segundos_realizados / 3600;
    $seguimiento_horas_hoy = number_format($seguimiento_horas_hoy_valor, 2, ',', '.');
  }

  $horas_totales_practica = is_numeric((string) ($practice['horas'] ?? null)) ? (float) $practice['horas'] : 0.0;
  $horas_pendientes = $horas_totales_practica - $seguimiento_horas_hoy_valor;
  if ($horas_pendientes < 0) {
    $horas_pendientes = 0;
  }
  $seguimiento_horas_pendientes = number_format($horas_pendientes, 2, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
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
          <p class="eyebrow">Detalle de práctica</p>
          <h1><?php echo htmlspecialchars($practice_header_title, ENT_QUOTES, 'UTF-8'); ?></h1>
        </div>
        <div class="header-actions">
          <?php if ($practice_found && $practice_status !== 'Finalizada'): ?>
            <form method="post" action="practica_detalle.php?id_practica=<?php echo (int) $id_practica; ?>">
              <input type="hidden" name="action" value="reactivar_practica">
              <button type="submit" class="primary-button reactivate-button">Reactivar práctica</button>
            </form>
          <?php endif; ?>
          <a class="ghost-button" href="practicas.php">Volver a prácticas</a>
        </div>
      </header>

      <?php if ($load_error !== null): ?>
        <section class="panel">
          <div class="panel-header">
            <h3>Error al cargar la práctica</h3>
            <p><?php echo htmlspecialchars($load_error, ENT_QUOTES, 'UTF-8'); ?></p>
          </div>
        </section>
      <?php elseif (!$practice_found): ?>
        <section class="panel">
          <div class="panel-header">
            <h3>Práctica no encontrada</h3>
            <p>No existe ninguna práctica con el identificador indicado.</p>
          </div>
        </section>
      <?php else: ?>
        <?php if ($reactivate_status !== null || $reactivate_error !== null || $calendar_status !== null || $calendar_error !== null || $plan_status !== null || $plan_error !== null || $calendar_generated_at !== null || $plan_generated_at !== null): ?>
        <section class="panel">
          <div class="panel-header">
            <h3>Estado de documentos</h3>
              <?php if ($reactivate_status !== null): ?>
                <p><?php echo htmlspecialchars($reactivate_status, ENT_QUOTES, 'UTF-8'); ?></p>
              <?php endif; ?>
              <?php if ($reactivate_error !== null): ?>
                <p><?php echo htmlspecialchars($reactivate_error, ENT_QUOTES, 'UTF-8'); ?></p>
              <?php endif; ?>
              <?php if ($calendar_status !== null): ?>
                <p><?php echo htmlspecialchars($calendar_status, ENT_QUOTES, 'UTF-8'); ?></p>
              <?php endif; ?>
              <?php if ($calendar_error !== null): ?>
                <p><?php echo htmlspecialchars($calendar_error, ENT_QUOTES, 'UTF-8'); ?></p>
              <?php endif; ?>
              <?php if ($plan_status !== null): ?>
                <p><?php echo htmlspecialchars($plan_status, ENT_QUOTES, 'UTF-8'); ?></p>
              <?php endif; ?>
              <?php if ($plan_error !== null): ?>
                <p><?php echo htmlspecialchars($plan_error, ENT_QUOTES, 'UTF-8'); ?></p>
              <?php endif; ?>
              <?php if ($calendar_generated_at !== null): ?>
                <p>Calendario generado el <?php echo htmlspecialchars($calendar_generated_at, ENT_QUOTES, 'UTF-8'); ?>.</p>
              <?php endif; ?>
              <?php if ($plan_generated_at !== null): ?>
                <p>Plan de formación generado el <?php echo htmlspecialchars($plan_generated_at, ENT_QUOTES, 'UTF-8'); ?>.</p>
              <?php endif; ?>
            </div>
          </section>
        <?php endif; ?>
        <div class="practica-detalle-grid practica-detalle-grid--fila-1">
          <section class="panel practica-detalle-bloque practica-detalle-bloque--alumno">
            <div class="panel-header">
              <h3>Resumen del alumno</h3>
            </div>
            <div class="practica-detalle-campos">
              <div class="practica-detalle-campo"><span class="practica-detalle-campo-etiqueta">Nombre y apellidos</span><span class="practica-detalle-campo-valor"><a class="practice-link" href="alumno_detalle.php?id_alumno=<?php echo (int) $practice['id_alumno']; ?>"><?php echo htmlspecialchars($student_name, ENT_QUOTES, 'UTF-8'); ?></a></span></div>
              <div class="practica-detalle-campo"><span class="practica-detalle-campo-etiqueta">NIA</span><span class="practica-detalle-campo-valor"><?php echo htmlspecialchars(format_value($practice['alumno_nia']), ENT_QUOTES, 'UTF-8'); ?></span></div>
              <div class="practica-detalle-campo"><span class="practica-detalle-campo-etiqueta">DNI</span><span class="practica-detalle-campo-valor"><?php echo htmlspecialchars(format_value($practice['alumno_dni']), ENT_QUOTES, 'UTF-8'); ?></span></div>
              <div class="practica-detalle-campo"><span class="practica-detalle-campo-etiqueta">Email personal</span><span class="practica-detalle-campo-valor"><?php $alumno_email_personal = trim((string) ($practice['alumno_email_personal'] ?? '')); ?><?php if ($alumno_email_personal !== ''): ?><span data-copy="<?php echo htmlspecialchars($alumno_email_personal, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($alumno_email_personal, ENT_QUOTES, 'UTF-8'); ?></span><?php else: ?>No disponible<?php endif; ?></span></div>
              <div class="practica-detalle-campo"><span class="practica-detalle-campo-etiqueta">Email EducaMadrid</span><span class="practica-detalle-campo-valor"><?php $alumno_email_educamadrid = trim((string) ($practice['alumno_email_educamadrid'] ?? '')); ?><?php if ($alumno_email_educamadrid !== ''): ?><span data-copy="<?php echo htmlspecialchars($alumno_email_educamadrid, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($alumno_email_educamadrid, ENT_QUOTES, 'UTF-8'); ?></span><?php else: ?>No disponible<?php endif; ?></span></div>
              <div class="practica-detalle-campo"><span class="practica-detalle-campo-etiqueta">Teléfono</span><span class="practica-detalle-campo-valor"><?php echo htmlspecialchars(format_value($practice['alumno_telefono']), ENT_QUOTES, 'UTF-8'); ?></span></div>
              <div class="practica-detalle-campo"><span class="practica-detalle-campo-etiqueta">Fecha de nacimiento</span><span class="practica-detalle-campo-valor"><?php echo htmlspecialchars($student_birth_date, ENT_QUOTES, 'UTF-8'); ?></span></div>
            </div>
          </section>

          <section class="panel practica-detalle-bloque practica-detalle-bloque--empresa">
            <div class="panel-header">
              <h3>Resumen de la empresa</h3>
            </div>
            <div class="practica-detalle-campos">
              <div class="practica-detalle-campo"><span class="practica-detalle-campo-etiqueta">Nombre</span><span class="practica-detalle-campo-valor"><a class="practice-link" href="empresa_detalle.php?id_empresa=<?php echo (int) $practice['id_empresa']; ?>"><?php echo htmlspecialchars($company_summary_name, ENT_QUOTES, 'UTF-8'); ?></a></span></div>
              <div class="practica-detalle-campo"><span class="practica-detalle-campo-etiqueta">CIF</span><span class="practica-detalle-campo-valor"><?php echo htmlspecialchars(format_value($practice['empresa_cif']), ENT_QUOTES, 'UTF-8'); ?></span></div>
              <div class="practica-detalle-campo">
                <span class="practica-detalle-campo-etiqueta">Persona de contacto</span>
                <span class="practica-detalle-campo-valor">
                  <div><?php echo htmlspecialchars(format_value($practice['empresa_contacto_nombre']), ENT_QUOTES, 'UTF-8'); ?></div>
                  <?php $empresa_contacto_email = trim((string) ($practice['empresa_contacto_email'] ?? '')); ?>
                  <div><?php if ($empresa_contacto_email !== ''): ?><span data-copy="<?php echo htmlspecialchars($empresa_contacto_email, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($empresa_contacto_email, ENT_QUOTES, 'UTF-8'); ?></span><?php else: ?>No disponible<?php endif; ?></div>
                  <div><?php echo htmlspecialchars(format_value($practice['empresa_contacto_telefono']), ENT_QUOTES, 'UTF-8'); ?></div>
                </span>
              </div>
              <div class="practica-detalle-campo">
                <span class="practica-detalle-campo-etiqueta">Tutor en el Centro de Trabajo</span>
                <span class="practica-detalle-campo-valor">
                  <div><?php echo htmlspecialchars(full_name_name_first($practice, 'tutor'), ENT_QUOTES, 'UTF-8'); ?></div>
                  <?php $tutor_empresa_email = trim((string) ($practice['tutor_empresa_email'] ?? '')); ?>
                  <div><?php if ($tutor_empresa_email !== ''): ?><span data-copy="<?php echo htmlspecialchars($tutor_empresa_email, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($tutor_empresa_email, ENT_QUOTES, 'UTF-8'); ?></span><?php else: ?>No disponible<?php endif; ?></div>
                  <div><?php echo htmlspecialchars(format_value($practice['tutor_empresa_telefono']), ENT_QUOTES, 'UTF-8'); ?></div>
                </span>
              </div>
            </div>
          </section>
        </div>

        <div class="practica-detalle-grid practica-detalle-grid--fila-2">
          <section class="panel practica-detalle-bloque practica-detalle-bloque--practica">
            <div class="panel-header">
              <h3>Resumen de la práctica</h3>
            </div>
            <div class="practica-detalle-campos">
              <div class="practica-detalle-campo"><span class="practica-detalle-campo-etiqueta">NÂº Anexo</span><span class="practica-detalle-campo-valor"><?php echo htmlspecialchars(format_value($practice['anexo']), ENT_QUOTES, 'UTF-8'); ?></span></div>
              <div class="practica-detalle-campo"><span class="practica-detalle-campo-etiqueta">Fecha de inicio</span><span class="practica-detalle-campo-valor"><?php echo htmlspecialchars(format_date($practice['fecha_inicio']), ENT_QUOTES, 'UTF-8'); ?></span></div>
              <div class="practica-detalle-campo"><span class="practica-detalle-campo-etiqueta">Fecha de fin calculada</span><span class="practica-detalle-campo-valor"><?php echo htmlspecialchars(format_date($practice['fecha_fin']), ENT_QUOTES, 'UTF-8'); ?></span></div>
              <div class="practica-detalle-campo"><span class="practica-detalle-campo-etiqueta">Días extra</span><span class="practica-detalle-campo-valor"><?php echo htmlspecialchars((string) ((int) ($practice['dias_extra'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?></span></div>
              <div class="practica-detalle-campo"><span class="practica-detalle-campo-etiqueta">Fecha de fin</span><span class="practica-detalle-campo-valor"><?php echo htmlspecialchars(format_date($practice['fecha_fin_extra'] ?? null, '—'), ENT_QUOTES, 'UTF-8'); ?></span></div>
              <div class="practica-detalle-campo"><span class="practica-detalle-campo-etiqueta">Horas totales</span><span class="practica-detalle-campo-valor"><?php echo htmlspecialchars(format_value($practice['horas']), ENT_QUOTES, 'UTF-8'); ?></span></div>
              <div class="practica-detalle-campo"><span class="practica-detalle-campo-etiqueta">Kilómetros al CT</span><span class="practica-detalle-campo-valor"><?php echo htmlspecialchars(format_value($practice['direccion_km_desde_getafe']), ENT_QUOTES, 'UTF-8'); ?></span></div>
              <div class="practica-detalle-campo"><span class="practica-detalle-campo-etiqueta">Abono</span><span class="practica-detalle-campo-valor"><?php echo htmlspecialchars(format_value($practice['direccion_abono_desde_getafe']), ENT_QUOTES, 'UTF-8'); ?></span></div>
              <div class="practica-detalle-campo"><span class="practica-detalle-campo-etiqueta">Circunstancias excepcionales</span><span class="practica-detalle-campo-valor"><?php echo htmlspecialchars(((isset($practice['circ_excep']) ? (int) $practice['circ_excep'] : 0) === 1 ? 'Requiere solicitud de autorización para la realización de la FFE bajo circunstancias de carácter excepcional.' : 'No'), ENT_QUOTES, 'UTF-8'); ?></span></div>
            </div>
          </section>

          <section class="panel practica-detalle-bloque practica-detalle-bloque--horario">
            <div class="panel-header">
              <h3>Horario</h3>
            </div>
            <div class="panel-grid">
              <table class="practica-horario-detalle-table">
                <thead>
                  <tr>
                    <th>Día</th>
                    <th>Mañana</th>
                    <th>Tarde</th>
                    <th>Total</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($dias_semana as $day_number => $day_name): ?>
                    <?php
                      $segments = $schedule_by_day[$day_number] ?? [];
                      $morning = $segments[0] ?? null;
                      $afternoon = $segments[1] ?? null;
                      $total_seconds = 0;
                      foreach ($segments as $segment) {
                        $entrada = strtotime((string) $segment['hora_entrada']);
                        $salida = strtotime((string) $segment['hora_salida']);
                        if ($entrada !== false && $salida !== false && $salida > $entrada) {
                          $total_seconds += ($salida - $entrada);
                        }
                      }
                      $hours = intdiv($total_seconds, 3600);
                      $minutes = intdiv($total_seconds % 3600, 60);
                      $total_label = $total_seconds > 0 ? sprintf('%02d:%02d', $hours, $minutes) : '—';
                    ?>
                    <tr>
                      <td><?php echo htmlspecialchars($day_name, ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars(format_time($morning['hora_entrada'] ?? null), ENT_QUOTES, 'UTF-8'); ?> - <?php echo htmlspecialchars(format_time($morning['hora_salida'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars(format_time($afternoon['hora_entrada'] ?? null), ENT_QUOTES, 'UTF-8'); ?> - <?php echo htmlspecialchars(format_time($afternoon['hora_salida'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars($total_label, ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </section>

        </div>

        <div class="practica-detalle-grid practica-detalle-grid--fila-3">
          <section class="panel practica-detalle-bloque practica-detalle-bloque--observaciones">
            <div class="panel-header">
              <h3>Observaciones</h3>
            </div>
            <div class="panel-grid">
              <p class="practica-observaciones"><?php echo nl2br(htmlspecialchars(format_value($practice['observaciones']), ENT_QUOTES, 'UTF-8')); ?></p>
              <?php if (!empty($practice['tutor_comentarios'])): ?>
                <p class="practica-observaciones-meta"><strong>Comentarios tutor empresa:</strong> <?php echo nl2br(htmlspecialchars((string) $practice['tutor_comentarios'], ENT_QUOTES, 'UTF-8')); ?></p>
              <?php endif; ?>
            </div>
          </section>

          <section class="panel practica-detalle-bloque practica-detalle-bloque--instrucciones">
            <div class="panel-header">
              <h3>Instrucciones</h3>
            </div>
            <div class="panel-grid">
              <p class="practica-observaciones"><?php echo nl2br(htmlspecialchars(format_value($practice['instrucciones']), ENT_QUOTES, 'UTF-8')); ?></p>
            </div>
          </section>
        </div>

        <div class="practica-detalle-grid practica-detalle-grid--fila-4">
          <section class="panel practica-detalle-bloque practica-detalle-bloque--seguimiento">
            <div class="panel-header">
              <h3>Seguimiento</h3>
            </div>
            <div class="panel-grid">
              <table class="practica-horario-detalle-table">
                <thead>
                  <tr>
                    <th>Mes</th>
                    <th>Días de prácticas</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if ($seguimiento_rows !== []): ?>
                    <?php foreach ($seguimiento_rows as $seguimiento_row): ?>
                      <tr>
                        <td><?php echo htmlspecialchars((string) $seguimiento_row['mes'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string) $seguimiento_row['dias'], ENT_QUOTES, 'UTF-8'); ?></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="2">No hay datos de seguimiento disponibles.</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
              <p><strong>Horas realizadas a día de hoy:</strong> <?php echo htmlspecialchars($seguimiento_horas_hoy, ENT_QUOTES, 'UTF-8'); ?> (faltan <?php echo htmlspecialchars($seguimiento_horas_pendientes, ENT_QUOTES, 'UTF-8'); ?>)</p>
            </div>
          </section>

          <section class="panel practica-detalle-bloque practica-detalle-bloque--documentacion">
            <div class="panel-header">
              <h3>Documentación</h3>
              <p>Generación y descarga de calendario y plan de formación.</p>
            </div>
            <div class="panel-grid">
              <p><strong>Calendario</strong></p>
              <div class="header-actions">
                <a class="primary-button" href="generar_calendario.php?id_practica=<?php echo (int) $id_practica; ?>">Generar calendario</a>
                <?php if ($calendar_exists && $calendar_file_name !== null): ?>
                  <a class="ghost-button" href="practica_detalle.php?id_practica=<?php echo (int) $id_practica; ?>&action=descargar_calendario">Descargar calendario</a>
                <?php endif; ?>
              </div>

              <p><strong>Plan de formación</strong></p>
              <div class="header-actions">
                <a class="primary-button" href="generar_plan_formacion.php?id_practica=<?php echo (int) $id_practica; ?>">Generar Plan Formación</a>
                <?php if ($plan_exists && $plan_file_name !== null): ?>
                  <a class="ghost-button" href="practica_detalle.php?id_practica=<?php echo (int) $id_practica; ?>&action=descargar_plan_formacion">Descargar Plan Formación</a>
                <?php endif; ?>
              </div>
            </div>
          </section>
        </div>

      <?php endif; ?>
    </main>
  </div>
  <script>
    const copyUsingFallback = (text) => {
      const helper = document.createElement('textarea');
      helper.value = text;
      helper.setAttribute('readonly', 'readonly');
      helper.style.position = 'fixed';
      helper.style.opacity = '0';
      helper.style.pointerEvents = 'none';
      document.body.appendChild(helper);
      helper.select();
      helper.setSelectionRange(0, helper.value.length);
      document.execCommand('copy');
      helper.remove();
    };

    const copyText = (text) => {
      if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
        return navigator.clipboard.writeText(text).catch(() => {
          copyUsingFallback(text);
        });
      }

      copyUsingFallback(text);
      return Promise.resolve();
    };

    const showCopied = (element) => {
      const originalText = element.dataset.originalText ?? element.textContent;
      const textToCopy = element.dataset.copy ?? '';
      if (textToCopy === '') {
        return;
      }

      element.dataset.originalText = originalText;

      if (element.dataset.copyTimerId) {
        window.clearTimeout(Number(element.dataset.copyTimerId));
      }

      element.textContent = 'Copiado!';
      copyText(textToCopy);

      const timerId = window.setTimeout(() => {
        element.textContent = element.dataset.originalText ?? originalText;
        delete element.dataset.copyTimerId;
      }, 1000);

      element.dataset.copyTimerId = String(timerId);
    };

    document.addEventListener('click', (event) => {
      const target = event.target.closest('[data-copy]');
      if (!target) {
        return;
      }

      showCopied(target);
    });
  </script>
</body>
</html>
