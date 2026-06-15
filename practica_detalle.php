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
  $calle = trim(trim((string) ($practice['direccion_via_tipo'] ?? '')) . ' ' . trim((string) ($practice['direccion_nombre_via'] ?? '')));
  $parts = [];
  if ($calle !== '') {
    $parts[] = $calle;
  }

  $numero = trim((string) ($practice['direccion_numero'] ?? ''));
  if ($numero !== '') {
    $parts[] = $numero;
  }

  if (($practice['direccion_bloque'] ?? '') !== '') { $parts[] = 'Bloque ' . $practice['direccion_bloque']; }
  if (($practice['direccion_escalera'] ?? '') !== '') { $parts[] = 'Esc. ' . $practice['direccion_escalera']; }
  if (($practice['direccion_planta'] ?? '') !== '') { $parts[] = 'Planta ' . $practice['direccion_planta']; }
  if (($practice['direccion_puerta'] ?? '') !== '') { $parts[] = 'Puerta ' . $practice['direccion_puerta']; }

  $otros = trim((string) ($practice['direccion_otros'] ?? ''));
  if ($otros !== '') {
    $parts[] = $otros;
  }

  $cp = trim((string) ($practice['direccion_cp'] ?? ''));
  if ($cp !== '') {
    $parts[] = $cp;
  }

  $localidad = trim((string) ($practice['direccion_localidad'] ?? ''));
  $provincia = trim((string) ($practice['direccion_provincia'] ?? ''));
  if ($localidad !== '' && $provincia !== '') {
    $parts[] = $localidad . ' (' . $provincia . ')';
  } elseif ($localidad !== '') {
    $parts[] = $localidad;
  } elseif ($provincia !== '') {
    $parts[] = '(' . $provincia . ')';
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

$id_practica_raw = $_GET['id_practica'] ?? ($_GET['id'] ?? ($_POST['id_practica'] ?? ($_POST['id'] ?? null)));
$id_practica = filter_var($id_practica_raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$action = isset($_GET['action']) ? (string) $_GET['action'] : '';
$post_action = isset($_POST['action']) ? (string) $_POST['action'] : '';
$is_ajax_request = isset($_REQUEST['ajax']) && (string) $_REQUEST['ajax'] === '1';
$anexo_7_id = null;

if ($is_ajax_request) {
  header('Content-Type: application/json; charset=UTF-8');

  if ($id_practica === false || $id_practica === null) {
    echo json_encode([
      'ok' => false,
      'message' => 'Práctica no válida.',
    ]);
    exit;
  }

  try {
    $pdo = db();
    $ajax_action = isset($_REQUEST['action']) ? (string) $_REQUEST['action'] : '';

    $anexos_stmt_ajax = $pdo->query(
      'SELECT id_practicas_anexo, anexo
       FROM practicas_anexos
       ORDER BY id_practicas_anexo ASC'
    );
    $anexos_catalog_ajax = $anexos_stmt_ajax->fetchAll();
    $anexos_validos_ajax = [];
    foreach ($anexos_catalog_ajax as $anexo_row_ajax) {
      $id_anexo_ajax = (int) $anexo_row_ajax['id_practicas_anexo'];
      $anexos_validos_ajax[$id_anexo_ajax] = true;
      $anexo_codigo_ajax = trim((string) ($anexo_row_ajax['anexo'] ?? ''));
      if ($anexo_7_id === null && preg_match('/(^|\D)7(\D|$)/', $anexo_codigo_ajax) === 1) {
        $anexo_7_id = $id_anexo_ajax;
      }
    }

    $fases_stmt_ajax = $pdo->query(
      'SELECT id_practicas_anexo_estado, estado
       FROM practicas_anexos_estados
       ORDER BY id_practicas_anexo_estado ASC'
    );
    $fases_catalog_ajax = $fases_stmt_ajax->fetchAll();
    $fases_validas_ajax = [];
    foreach ($fases_catalog_ajax as $fase_row_ajax) {
      $fases_validas_ajax[(int) $fase_row_ajax['id_practicas_anexo_estado']] = true;
    }

    if ($ajax_action === 'ajax_guardar_anexo_fase' || $ajax_action === 'ajax_guardar_anexo_7_fase') {
      $id_practicas_anexo_ajax = filter_var($_REQUEST['id_practicas_anexo'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
      $id_practicas_anexo_estado_ajax = filter_var($_REQUEST['id_practicas_anexo_estado'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
      $checked_ajax = isset($_REQUEST['checked']) && (string) $_REQUEST['checked'] === '1' ? 1 : 0;
      $numero_seguimiento_ajax = 1;

      if ($id_practicas_anexo_ajax === false || $id_practicas_anexo_ajax === null || !isset($anexos_validos_ajax[(int) $id_practicas_anexo_ajax])) {
        echo json_encode(['ok' => false, 'message' => 'Anexo no válido.']);
        exit;
      }
      if ($id_practicas_anexo_estado_ajax === false || $id_practicas_anexo_estado_ajax === null || !isset($fases_validas_ajax[(int) $id_practicas_anexo_estado_ajax])) {
        echo json_encode(['ok' => false, 'message' => 'Fase no válida.']);
        exit;
      }

      if ($ajax_action === 'ajax_guardar_anexo_fase') {
        if ((int) $id_practicas_anexo_ajax === (int) $anexo_7_id) {
          echo json_encode(['ok' => false, 'message' => 'Acción no válida para el anexo 7.']);
          exit;
        }
      } else {
        if ((int) $id_practicas_anexo_ajax !== (int) $anexo_7_id) {
          echo json_encode(['ok' => false, 'message' => 'Acción válida solo para el anexo 7.']);
          exit;
        }
        $numero_seguimiento_ajax = filter_var($_REQUEST['numero_seguimiento'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($numero_seguimiento_ajax === false || $numero_seguimiento_ajax === null) {
          echo json_encode(['ok' => false, 'message' => 'Número de seguimiento no válido.']);
          exit;
        }
      }

      if ($checked_ajax === 1) {
        $exists_stmt_ajax = $pdo->prepare(
          'SELECT id_practica_anexo_seguimiento
           FROM practicas_anexos_seguimiento
           WHERE id_practica = :id_practica
             AND id_practicas_anexo = :id_practicas_anexo
             AND id_practicas_anexo_estado = :id_practicas_anexo_estado
             AND numero_seguimiento = :numero_seguimiento
           LIMIT 1'
        );
        $exists_stmt_ajax->execute([
          'id_practica' => $id_practica,
          'id_practicas_anexo' => $id_practicas_anexo_ajax,
          'id_practicas_anexo_estado' => $id_practicas_anexo_estado_ajax,
          'numero_seguimiento' => $numero_seguimiento_ajax,
        ]);
        $exists_id_ajax = $exists_stmt_ajax->fetchColumn();

        if ($exists_id_ajax === false) {
          $insert_stmt_ajax = $pdo->prepare(
            'INSERT INTO practicas_anexos_seguimiento
              (id_practica, id_practicas_anexo, id_practicas_anexo_estado, numero_seguimiento, fecha_creacion, fecha_actualizacion)
             VALUES
              (:id_practica, :id_practicas_anexo, :id_practicas_anexo_estado, :numero_seguimiento, NOW(), NOW())'
          );
          $insert_stmt_ajax->execute([
            'id_practica' => $id_practica,
            'id_practicas_anexo' => $id_practicas_anexo_ajax,
            'id_practicas_anexo_estado' => $id_practicas_anexo_estado_ajax,
            'numero_seguimiento' => $numero_seguimiento_ajax,
          ]);
        }
      } else {
        $delete_stmt_ajax = $pdo->prepare(
          'DELETE FROM practicas_anexos_seguimiento
           WHERE id_practica = :id_practica
             AND id_practicas_anexo = :id_practicas_anexo
             AND id_practicas_anexo_estado = :id_practicas_anexo_estado
             AND numero_seguimiento = :numero_seguimiento'
        );
        $delete_stmt_ajax->execute([
          'id_practica' => $id_practica,
          'id_practicas_anexo' => $id_practicas_anexo_ajax,
          'id_practicas_anexo_estado' => $id_practicas_anexo_estado_ajax,
          'numero_seguimiento' => $numero_seguimiento_ajax,
        ]);
      }

      $fechas_stmt_ajax = $pdo->prepare(
        'SELECT MIN(fecha_creacion) AS fecha_creacion, MAX(fecha_actualizacion) AS fecha_actualizacion
         FROM practicas_anexos_seguimiento
         WHERE id_practica = :id_practica
           AND id_practicas_anexo = :id_practicas_anexo
           AND numero_seguimiento = :numero_seguimiento'
      );
      $fechas_stmt_ajax->execute([
        'id_practica' => $id_practica,
        'id_practicas_anexo' => $id_practicas_anexo_ajax,
        'numero_seguimiento' => $numero_seguimiento_ajax,
      ]);
      $fechas_row_ajax = $fechas_stmt_ajax->fetch();

      echo json_encode([
        'ok' => true,
        'message' => 'Seguimiento guardado correctamente.',
        'fecha_creacion' => isset($fechas_row_ajax['fecha_creacion']) && $fechas_row_ajax['fecha_creacion'] !== null ? (string) $fechas_row_ajax['fecha_creacion'] : '',
        'fecha_actualizacion' => isset($fechas_row_ajax['fecha_actualizacion']) && $fechas_row_ajax['fecha_actualizacion'] !== null ? (string) $fechas_row_ajax['fecha_actualizacion'] : '',
        'id_practicas_anexo' => (int) $id_practicas_anexo_ajax,
        'id_practicas_anexo_estado' => (int) $id_practicas_anexo_estado_ajax,
        'numero_seguimiento' => (int) $numero_seguimiento_ajax,
      ]);
      exit;
    }

    if ($ajax_action === 'ajax_crear_anexo_7_seguimiento') {
      $id_practicas_anexo_ajax = filter_var($_REQUEST['id_practicas_anexo'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
      $numero_seguimiento_base_ajax = filter_var($_REQUEST['numero_seguimiento_base'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
      if ($id_practicas_anexo_ajax === false || $id_practicas_anexo_ajax === null || !isset($anexos_validos_ajax[(int) $id_practicas_anexo_ajax])) {
        echo json_encode(['ok' => false, 'message' => 'Anexo no válido.']);
        exit;
      }
      if ((int) $id_practicas_anexo_ajax !== (int) $anexo_7_id) {
        echo json_encode(['ok' => false, 'message' => 'Acción válida solo para el anexo 7.']);
        exit;
      }

      $max_stmt_ajax = $pdo->prepare(
        'SELECT MAX(numero_seguimiento)
         FROM practicas_anexos_seguimiento
         WHERE id_practica = :id_practica
           AND id_practicas_anexo = :id_practicas_anexo'
      );
      $max_stmt_ajax->execute([
        'id_practica' => $id_practica,
        'id_practicas_anexo' => $id_practicas_anexo_ajax,
      ]);
      $max_numero_db_ajax = (int) ($max_stmt_ajax->fetchColumn() ?: 0);
      $max_base_ajax = 0;
      if ($numero_seguimiento_base_ajax !== false && $numero_seguimiento_base_ajax !== null) {
        $max_base_ajax = (int) $numero_seguimiento_base_ajax;
      }
      $siguiente_numero_seguimiento_ajax = max($max_numero_db_ajax, $max_base_ajax) + 1;

      echo json_encode([
        'ok' => true,
        'message' => 'Nuevo seguimiento creado correctamente.',
        'numero_seguimiento' => (int) $siguiente_numero_seguimiento_ajax,
        'id_practicas_anexo' => (int) $id_practicas_anexo_ajax,
      ]);
      exit;
    }

    echo json_encode([
      'ok' => false,
      'message' => 'Acción AJAX no válida.',
    ]);
    exit;
  } catch (Throwable $e) {
    echo json_encode([
      'ok' => false,
      'message' => 'Error técnico.',
    ]);
    exit;
  }
}

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
$anexos_status = null;
$anexos_error = null;
$anexos_catalog = [];
$fases_catalog = [];
$anexos_seguimiento_rows = [];
$anexos_fases_marcadas = [];
$anexo7_seguimientos = [];

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

      $anexos_stmt = $pdo->query(
        'SELECT id_practicas_anexo, anexo, descripcion
         FROM practicas_anexos
         ORDER BY id_practicas_anexo ASC'
      );
      $anexos_catalog = $anexos_stmt->fetchAll();

      foreach ($anexos_catalog as $anexo_row) {
        $anexo_codigo = trim((string) ($anexo_row['anexo'] ?? ''));
        if ($anexo_7_id === null && preg_match('/(^|\D)7(\D|$)/', $anexo_codigo) === 1) {
          $anexo_7_id = (int) $anexo_row['id_practicas_anexo'];
        }
      }

      $fases_stmt = $pdo->query(
        'SELECT id_practicas_anexo_estado, estado
         FROM practicas_anexos_estados
         ORDER BY id_practicas_anexo_estado ASC'
      );
      $fases_catalog = $fases_stmt->fetchAll();

      $anexos_validos = [];
      foreach ($anexos_catalog as $anexo_row) {
        $anexos_validos[(int) $anexo_row['id_practicas_anexo']] = true;
      }

      $fases_validas = [];
      foreach ($fases_catalog as $fase_row) {
        $fases_validas[(int) $fase_row['id_practicas_anexo_estado']] = true;
      }

      if ($post_action === 'guardar_anexo_fases' || $post_action === 'guardar_anexo_7_fases' || $post_action === 'crear_anexo_7_fases') {
        $id_practicas_anexo_post = filter_var($_POST['id_practicas_anexo'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $fases_post_raw = $_POST['fases'] ?? [];
        $fases_post_ids = [];

        if (is_array($fases_post_raw)) {
          foreach ($fases_post_raw as $fase_post_raw) {
            $fase_post_id = filter_var($fase_post_raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($fase_post_id !== false && $fase_post_id !== null) {
              $fases_post_ids[(int) $fase_post_id] = true;
            }
          }
        }

        $fases_post = array_keys($fases_post_ids);
        $fases_invalidas = [];
        foreach ($fases_post as $fase_post_id) {
          if (!isset($fases_validas[(int) $fase_post_id])) {
            $fases_invalidas[] = (int) $fase_post_id;
          }
        }

        if ($id_practicas_anexo_post === false || $id_practicas_anexo_post === null || !isset($anexos_validos[(int) $id_practicas_anexo_post])) {
          $anexos_error = 'Anexo no válido.';
        } elseif ($fases_invalidas !== []) {
          $anexos_error = 'Fases no válidas.';
        } elseif ($post_action === 'guardar_anexo_fases') {
          if ((int) $id_practicas_anexo_post === (int) $anexo_7_id) {
            $anexos_error = 'Acción no válida para el anexo 7.';
          } else {
            $delete_stmt = $pdo->prepare(
              'DELETE FROM practicas_anexos_seguimiento
               WHERE id_practica = :id_practica
                 AND id_practicas_anexo = :id_practicas_anexo
                 AND numero_seguimiento = 1'
            );
            $delete_stmt->execute([
              'id_practica' => $id_practica,
              'id_practicas_anexo' => $id_practicas_anexo_post,
            ]);

            if ($fases_post !== []) {
              $insert_stmt = $pdo->prepare(
                'INSERT INTO practicas_anexos_seguimiento
                  (id_practica, id_practicas_anexo, id_practicas_anexo_estado, numero_seguimiento, fecha_creacion, fecha_actualizacion)
                 VALUES
                  (:id_practica, :id_practicas_anexo, :id_practicas_anexo_estado, 1, NOW(), NOW())'
              );

              foreach ($fases_post as $fase_post_id) {
                $insert_stmt->execute([
                  'id_practica' => $id_practica,
                  'id_practicas_anexo' => $id_practicas_anexo_post,
                  'id_practicas_anexo_estado' => (int) $fase_post_id,
                ]);
              }
            }

            $anexos_status = 'Seguimiento guardado correctamente.';
          }
        } elseif ($post_action === 'guardar_anexo_7_fases') {
          $numero_seguimiento_post = filter_var($_POST['numero_seguimiento'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
          if ((int) $id_practicas_anexo_post !== (int) $anexo_7_id) {
            $anexos_error = 'Acción válida solo para el anexo 7.';
          } elseif ($numero_seguimiento_post === false || $numero_seguimiento_post === null) {
            $anexos_error = 'Número de seguimiento no válido.';
          } else {
            $delete_stmt = $pdo->prepare(
              'DELETE FROM practicas_anexos_seguimiento
               WHERE id_practica = :id_practica
                 AND id_practicas_anexo = :id_practicas_anexo
                 AND numero_seguimiento = :numero_seguimiento'
            );
            $delete_stmt->execute([
              'id_practica' => $id_practica,
              'id_practicas_anexo' => $id_practicas_anexo_post,
              'numero_seguimiento' => $numero_seguimiento_post,
            ]);

            if ($fases_post !== []) {
              $insert_stmt = $pdo->prepare(
                'INSERT INTO practicas_anexos_seguimiento
                  (id_practica, id_practicas_anexo, id_practicas_anexo_estado, numero_seguimiento, fecha_creacion, fecha_actualizacion)
                 VALUES
                  (:id_practica, :id_practicas_anexo, :id_practicas_anexo_estado, :numero_seguimiento, NOW(), NOW())'
              );

              foreach ($fases_post as $fase_post_id) {
                $insert_stmt->execute([
                  'id_practica' => $id_practica,
                  'id_practicas_anexo' => $id_practicas_anexo_post,
                  'id_practicas_anexo_estado' => (int) $fase_post_id,
                  'numero_seguimiento' => (int) $numero_seguimiento_post,
                ]);
              }
            }

            $anexos_status = 'Seguimiento guardado correctamente.';
          }
        } elseif ($post_action === 'crear_anexo_7_fases') {
          if ((int) $id_practicas_anexo_post !== (int) $anexo_7_id) {
            $anexos_error = 'Acción válida solo para el anexo 7.';
          } elseif ($fases_post === []) {
            $anexos_error = 'Debes seleccionar al menos una fase para crear el seguimiento.';
          } else {
            $max_stmt = $pdo->prepare(
              'SELECT MAX(numero_seguimiento)
               FROM practicas_anexos_seguimiento
               WHERE id_practica = :id_practica
                 AND id_practicas_anexo = :id_practicas_anexo'
            );
            $max_stmt->execute([
              'id_practica' => $id_practica,
              'id_practicas_anexo' => $id_practicas_anexo_post,
            ]);
            $siguiente_numero_seguimiento = (int) ($max_stmt->fetchColumn() ?: 0) + 1;

            $insert_stmt = $pdo->prepare(
              'INSERT INTO practicas_anexos_seguimiento
                (id_practica, id_practicas_anexo, id_practicas_anexo_estado, numero_seguimiento, fecha_creacion, fecha_actualizacion)
               VALUES
                (:id_practica, :id_practicas_anexo, :id_practicas_anexo_estado, :numero_seguimiento, NOW(), NOW())'
            );

            foreach ($fases_post as $fase_post_id) {
              $insert_stmt->execute([
                'id_practica' => $id_practica,
                'id_practicas_anexo' => $id_practicas_anexo_post,
                'id_practicas_anexo_estado' => (int) $fase_post_id,
                'numero_seguimiento' => $siguiente_numero_seguimiento,
              ]);
            }

            $anexos_status = 'Nuevo seguimiento creado correctamente.';
          }
        }
      }

      $seguimiento_anexos_stmt = $pdo->prepare(
        'SELECT id_practica_anexo_seguimiento, id_practica, id_practicas_anexo, id_practicas_anexo_estado, numero_seguimiento, fecha_creacion, fecha_actualizacion
         FROM practicas_anexos_seguimiento
         WHERE id_practica = :id_practica
         ORDER BY id_practicas_anexo ASC, numero_seguimiento ASC, id_practicas_anexo_estado ASC'
      );
      $seguimiento_anexos_stmt->execute(['id_practica' => $id_practica]);
      $anexos_seguimiento_rows = $seguimiento_anexos_stmt->fetchAll();

      foreach ($anexos_seguimiento_rows as $seguimiento_anexo_row) {
        $id_practicas_anexo_row = (int) $seguimiento_anexo_row['id_practicas_anexo'];
        $numero_seguimiento_row = (int) $seguimiento_anexo_row['numero_seguimiento'];
        $id_fase_row = (int) $seguimiento_anexo_row['id_practicas_anexo_estado'];
        $fecha_creacion_row = isset($seguimiento_anexo_row['fecha_creacion']) ? (string) $seguimiento_anexo_row['fecha_creacion'] : '';
        $fecha_actualizacion_row = isset($seguimiento_anexo_row['fecha_actualizacion']) ? (string) $seguimiento_anexo_row['fecha_actualizacion'] : '';

        if (!isset($anexos_fases_marcadas[$id_practicas_anexo_row])) {
          $anexos_fases_marcadas[$id_practicas_anexo_row] = [];
        }
        if (!isset($anexos_fases_marcadas[$id_practicas_anexo_row][$numero_seguimiento_row])) {
          $anexos_fases_marcadas[$id_practicas_anexo_row][$numero_seguimiento_row] = [
            'fases' => [],
            'fecha_creacion_min' => null,
            'fecha_actualizacion_max' => null,
          ];
        }

        $anexos_fases_marcadas[$id_practicas_anexo_row][$numero_seguimiento_row]['fases'][$id_fase_row] = true;

        if ($fecha_creacion_row !== '') {
          if ($anexos_fases_marcadas[$id_practicas_anexo_row][$numero_seguimiento_row]['fecha_creacion_min'] === null
            || $fecha_creacion_row < $anexos_fases_marcadas[$id_practicas_anexo_row][$numero_seguimiento_row]['fecha_creacion_min']) {
            $anexos_fases_marcadas[$id_practicas_anexo_row][$numero_seguimiento_row]['fecha_creacion_min'] = $fecha_creacion_row;
          }
        }

        if ($fecha_actualizacion_row !== '') {
          if ($anexos_fases_marcadas[$id_practicas_anexo_row][$numero_seguimiento_row]['fecha_actualizacion_max'] === null
            || $fecha_actualizacion_row > $anexos_fases_marcadas[$id_practicas_anexo_row][$numero_seguimiento_row]['fecha_actualizacion_max']) {
            $anexos_fases_marcadas[$id_practicas_anexo_row][$numero_seguimiento_row]['fecha_actualizacion_max'] = $fecha_actualizacion_row;
          }
        }
      }

      if (isset($anexos_fases_marcadas[(int) $anexo_7_id]) && is_array($anexos_fases_marcadas[(int) $anexo_7_id])) {
        $anexo7_seguimientos = $anexos_fases_marcadas[(int) $anexo_7_id];
        ksort($anexo7_seguimientos);
      }

      $anexos_estados_permitidos = [
        '2.1' => [
          'datos solicitados',
          'enviado a firmar por la empresa',
          'firmado por la empresa',
          'enviado a firmar por el director',
          'firmado por el director',
          'devuelto a la empresa',
        ],
        '2.2' => [
          'enviado a firmar por el director',
          'firmado por el director',
        ],
        '3' => [
          'enviado a firmar por la empresa',
          'firmado por la empresa',
          'devuelto a la empresa',
        ],
        '4' => [
          'enviado a la dat',
          'autorizado',
        ],
        '7' => [
          'enviado a firmar por la empresa',
          'firmado por la empresa',
        ],
        '8' => [
          'enviado a firmar por la empresa',
          'firmado por la empresa',
        ],
      ];
      $fases_catalog_por_estado_clave = [];
      $fases_catalog_por_id = [];
      foreach ($fases_catalog as $fase_row) {
        $id_fase_row = (int) $fase_row['id_practicas_anexo_estado'];
        $fases_catalog_por_id[$id_fase_row] = (string) ($fase_row['estado'] ?? '');
        $fase_estado_row = trim((string) ($fase_row['estado'] ?? ''));
        if ($fase_estado_row === '') {
          continue;
        }
        $fase_estado_normalizada = preg_replace('/\s+/u', ' ', $fase_estado_row);
        $fase_estado_normalizada = $fase_estado_normalizada !== null ? $fase_estado_normalizada : $fase_estado_row;
        $fase_estado_normalizada = function_exists('mb_strtolower') ? mb_strtolower($fase_estado_normalizada, 'UTF-8') : strtolower($fase_estado_normalizada);
        $fase_estado_clave = strtr($fase_estado_normalizada, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
        $fases_catalog_por_estado_clave[$fase_estado_clave] = (int) $fase_row['id_practicas_anexo_estado'];
      }
      $anexos_estados_permitidos_por_id = [];
      $anexos_fases_ordenadas_por_id = [];
      foreach ($anexos_catalog as $anexo_row) {
        $id_anexo_row = (int) $anexo_row['id_practicas_anexo'];
        $anexo_codigo_row = trim((string) ($anexo_row['anexo'] ?? ''));
        $anexo_codigo_clave = '';
        if (preg_match('/(^|\D)(2\.1|2\.2|3|4|7|8)(\D|$)/', $anexo_codigo_row, $anexo_codigo_matches) === 1) {
          $anexo_codigo_clave = (string) $anexo_codigo_matches[2];
        }
        if ($anexo_codigo_clave === '' || !isset($anexos_estados_permitidos[$anexo_codigo_clave])) {
          continue;
        }
        $anexos_estados_permitidos_por_id[$id_anexo_row] = [];
        $anexos_fases_ordenadas_por_id[$id_anexo_row] = [];
        foreach ($anexos_estados_permitidos[$anexo_codigo_clave] as $estado_permitido_clave) {
          if (isset($fases_catalog_por_estado_clave[$estado_permitido_clave])) {
            $id_fase_permitida = (int) $fases_catalog_por_estado_clave[$estado_permitido_clave];
            $anexos_estados_permitidos_por_id[$id_anexo_row][$id_fase_permitida] = true;
            $anexos_fases_ordenadas_por_id[$id_anexo_row][] = [
              'id' => $id_fase_permitida,
              'estado' => $fases_catalog_por_id[$id_fase_permitida] ?? '',
            ];
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

      if ($plan_file_path !== null && !is_file($plan_file_path)) {
        $alumnoNombreArchivoRaw = trim(implode(' ', array_filter([
          (string) ($practice['alumno_nombre'] ?? ''),
          (string) ($practice['alumno_apellido1'] ?? ''),
          (string) ($practice['alumno_apellido2'] ?? ''),
        ])));
        $empresaNombreComercialRaw = (string) ($practice['empresa_nombre_comercial'] ?? '');
        $alumnoNombreArchivo = practicas_sanitize_filename_component((string) preg_replace('/\s+/u', '', $alumnoNombreArchivoRaw), 40);
        $empresaNombreComercialArchivo = practicas_sanitize_filename_component((string) preg_replace('/\s+/u', '', $empresaNombreComercialRaw), 40);
        $planFileNameAlternativo = implode('_', [
          'PF',
          practicas_sanitize_filename_component((string) ($practice['anexo'] ?? ''), 20),
          practicas_sanitize_filename_component((string) ($practice['empresa_convenio'] ?? ''), 20),
          $alumnoNombreArchivo,
          $empresaNombreComercialArchivo,
        ]) . '.pdf';
        $planFilePathAlternativo = $planDirectory . '/' . $planFileNameAlternativo;

        if (is_file($planFilePathAlternativo)) {
          $plan_file_name = $planFileNameAlternativo;
          $plan_file_path = $planFilePathAlternativo;
        }
      }

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
        header('Location: includes/generar_calendario_practica.php?id_practica=' . (int) $id_practica);
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
  $months = [
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
  $practices = [$practice];
  $student_rows = [
    (int) $practice['id_alumno'] => [
      'id_practica' => (int) $practice['id_practica'],
      'name' => $student_name,
      'apellido1' => mb_strtolower(trim((string) ($practice['alumno_apellido1'] ?? ''))),
      'months' => array_fill_keys(array_keys($months), 0),
      'month_practice_ids' => array_fill_keys(array_keys($months), []),
      'seconds' => 0,
      'current_month_seconds' => 0,
      'had_current_month_in_course' => false,
      'status' => calculate_practice_status($practice),
    ],
  ];
  $hoy = new DateTimeImmutable('today');
  $current_month = (int) $hoy->format('n');
  $current_year = (int) $hoy->format('Y');
  $schedule_by_practice = [
    (int) $practice['id_practica'] => $schedule_by_day,
  ];
  $non_teaching_days = $pdo->query('SELECT fecha FROM no_lectivos')->fetchAll(PDO::FETCH_COLUMN);
  $non_teaching_days_lookup = array_fill_keys($non_teaching_days, true);

  require __DIR__ . '/includes/practicas_dias_calculo.php';

  $student_row = $student_rows[(int) $practice['id_alumno']] ?? null;
  if (is_array($student_row)) {
    foreach ($student_row['months'] as $month_number => $days_count) {
      if ((int) $days_count <= 0) {
        continue;
      }

      $seguimiento_rows[] = [
        'mes' => ($months[(int) $month_number] ?? ''),
        'dias' => (string) $days_count,
      ];
    }

    $seguimiento_horas_hoy_valor = ((float) ($student_row['seconds'] ?? 0)) / 3600;
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
          <?php if ($practice_found && $practice_status === 'Cancelada'): ?>
            <form method="post" action="practica_detalle.php?id_practica=<?php echo (int) $id_practica; ?>">
              <input type="hidden" name="action" value="reactivar_practica">
              <button type="submit" class="primary-button reactivate-button">Reactivar práctica</button>
            </form>
          <?php endif; ?>
          <?php if ($practice_found): ?>
            <a class="primary-button" href="practica_editar.php?id_practica=<?php echo (int) $id_practica; ?>">Editar práctica</a>
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
        <?php if ($reactivate_status !== null || $reactivate_error !== null || $calendar_status !== null || $calendar_error !== null || $plan_status !== null || $plan_error !== null || $anexos_status !== null || $anexos_error !== null || $calendar_generated_at !== null || $plan_generated_at !== null): ?>
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
              <?php if ($anexos_status !== null): ?>
                <p><?php echo htmlspecialchars($anexos_status, ENT_QUOTES, 'UTF-8'); ?></p>
              <?php endif; ?>
              <?php if ($anexos_error !== null): ?>
                <p><?php echo htmlspecialchars($anexos_error, ENT_QUOTES, 'UTF-8'); ?></p>
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
              <div class="practica-detalle-campo"><span class="practica-detalle-campo-etiqueta">Email personal</span><span class="practica-detalle-campo-valor"><?php $alumno_email_personal = trim((string) ($practice['alumno_email_personal'] ?? '')); ?><?php if ($alumno_email_personal !== ''): ?><span class="copy-trigger" data-copy="<?php echo htmlspecialchars($alumno_email_personal, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($alumno_email_personal, ENT_QUOTES, 'UTF-8'); ?></span><?php else: ?>No disponible<?php endif; ?></span></div>
              <div class="practica-detalle-campo"><span class="practica-detalle-campo-etiqueta">Email EducaMadrid</span><span class="practica-detalle-campo-valor"><?php $alumno_email_educamadrid = trim((string) ($practice['alumno_email_educamadrid'] ?? '')); ?><?php if ($alumno_email_educamadrid !== ''): ?><span class="copy-trigger" data-copy="<?php echo htmlspecialchars($alumno_email_educamadrid, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($alumno_email_educamadrid, ENT_QUOTES, 'UTF-8'); ?></span><?php else: ?>No disponible<?php endif; ?></span></div>
              <div class="practica-detalle-campo"><span class="practica-detalle-campo-etiqueta">Teléfono</span><span class="practica-detalle-campo-valor"><?php $val = trim((string) ($practice['alumno_telefono'] ?? '')); if ($val !== '') { $v = htmlspecialchars($val, ENT_QUOTES, 'UTF-8'); echo '<span class="copy-trigger" data-copy="' . $v . '">' . $v . '</span>'; } else { echo 'No disponible'; } ?></span></div>
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
                  <div><?php if ($empresa_contacto_email !== ''): ?><span class="copy-trigger" data-copy="<?php echo htmlspecialchars($empresa_contacto_email, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($empresa_contacto_email, ENT_QUOTES, 'UTF-8'); ?></span><?php else: ?>No disponible<?php endif; ?></div>
                  <div><?php $val = trim((string) ($practice['empresa_contacto_telefono'] ?? '')); if ($val !== '') { $v = htmlspecialchars($val, ENT_QUOTES, 'UTF-8'); echo '<span class="copy-trigger" data-copy="' . $v . '">' . $v . '</span>'; } else { echo htmlspecialchars(format_value($practice['empresa_contacto_telefono']), ENT_QUOTES, 'UTF-8'); } ?></div>
                </span>
              </div>
              <div class="practica-detalle-campo">
                <span class="practica-detalle-campo-etiqueta">Tutor en el Centro de Trabajo</span>
                <span class="practica-detalle-campo-valor">
                  <div><?php echo htmlspecialchars(full_name_name_first($practice, 'tutor'), ENT_QUOTES, 'UTF-8'); ?></div>
                  <?php $tutor_empresa_email = trim((string) ($practice['tutor_empresa_email'] ?? '')); ?>
                  <div><?php if ($tutor_empresa_email !== ''): ?><span class="copy-trigger" data-copy="<?php echo htmlspecialchars($tutor_empresa_email, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($tutor_empresa_email, ENT_QUOTES, 'UTF-8'); ?></span><?php else: ?>No disponible<?php endif; ?></div>
                  <div><?php $val = trim((string) ($practice['tutor_empresa_telefono'] ?? '')); if ($val !== '') { $v = htmlspecialchars($val, ENT_QUOTES, 'UTF-8'); echo '<span class="copy-trigger" data-copy="' . $v . '">' . $v . '</span>'; } else { echo htmlspecialchars(format_value($practice['tutor_empresa_telefono']), ENT_QUOTES, 'UTF-8'); } ?></div>
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
                <a class="primary-button" href="includes/generar_calendario_practica.php?id_practica=<?php echo (int) $id_practica; ?>">Generar calendario</a>
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

              <p><strong>Informe tutor</strong></p>
              <div class="header-actions">
                <a class="primary-button" href="includes/generar_informe_valoracion_tutor_empresa.php?id_practica=<?php echo (int) $id_practica; ?>">Generar Informe Tutor</a>
                <a class="ghost-button" href="includes/generar_informe_valoracion_tutor_empresa_rellenable.php?id_practica=<?php echo (int) $id_practica; ?>">Generar Informe Tutor Rellenable</a>
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
      if (!target || target.classList.contains('copy-trigger')) {
        return;
      }

      showCopied(target);
    });

    const anexosFasesCatalog = [
      <?php
        $anexo_7_catalog_base = $anexos_fases_ordenadas_por_id[(int) $anexo_7_id] ?? [];
        $anexo_7_catalog_por_nombre = [];
        foreach ($anexo_7_catalog_base as $fase_item_catalog) {
          $anexo_7_catalog_por_nombre[trim((string) ($fase_item_catalog['estado'] ?? ''))] = $fase_item_catalog;
        }
        $anexo_7_catalog_orden = ['Enviado a firmar por la empresa', 'Firmado por la empresa'];
        $anexo_7_catalog = [];
        foreach ($anexo_7_catalog_orden as $estado_catalog) {
          if (isset($anexo_7_catalog_por_nombre[$estado_catalog])) {
            $anexo_7_catalog[] = $anexo_7_catalog_por_nombre[$estado_catalog];
          }
        }
        if ($anexo_7_catalog === []) {
          $anexo_7_catalog = $anexo_7_catalog_base;
        }
      ?>
      <?php foreach ($anexo_7_catalog as $fase_item): ?>
        {
          id: <?php echo (int) ($fase_item['id'] ?? 0); ?>,
          estado: <?php echo json_encode((string) ($fase_item['estado'] ?? ''), JSON_UNESCAPED_UNICODE); ?>
        },
      <?php endforeach; ?>
    ];

    const actualizarEstadoVisualAnexo = (checkbox) => {
      const wrap = checkbox.closest('[data-hito-wrap]');
      if (wrap) {
        wrap.classList.toggle('is-checked', checkbox.checked);
      }
      const hito = wrap ? wrap.querySelector('.anexos-estado-hito') : null;
      if (hito) {
        hito.textContent = checkbox.checked ? '✓' : '';
      }

      const row = checkbox.closest('[data-anexo-status-row]');
      if (!row) {
        return;
      }
      const checkboxes = row.querySelectorAll('input[type="checkbox"][data-anexo-ajax="1"]');
      const total = checkboxes.length;
      let marcadas = 0;
      checkboxes.forEach((item) => {
        if (item.checked) {
          marcadas += 1;
        }
      });
      const porcentaje = total > 0 ? Math.round((marcadas / total) * 100) : 0;
      const valor = row.querySelector('[data-progreso-valor]');
      if (valor) {
        valor.textContent = `${porcentaje}%`;
      }
      const fill = row.querySelector('[data-progreso-fill]');
      if (fill) {
        fill.style.width = `${porcentaje}%`;
      }
    };

    const guardarFaseAnexoAjax = (checkbox) => {
      const idPractica = checkbox.dataset.idPractica;
      const idAnexo = checkbox.dataset.idPracticasAnexo;
      const idFase = checkbox.dataset.idPracticasAnexoEstado;
      const numeroSeguimiento = checkbox.dataset.numeroSeguimiento;
      const action = checkbox.dataset.ajaxAction;
      const checked = checkbox.checked ? '1' : '0';

      const params = new URLSearchParams();
      params.append('ajax', '1');
      params.append('action', action);
      params.append('id_practica', idPractica);
      params.append('id_practicas_anexo', idAnexo);
      params.append('id_practicas_anexo_estado', idFase);
      params.append('checked', checked);
      if (action === 'ajax_guardar_anexo_7_fase') {
        params.append('numero_seguimiento', numeroSeguimiento);
      }

      fetch('practica_detalle.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        },
        body: params.toString(),
      })
        .then((response) => response.json())
        .then((data) => {
          if (!data || data.ok !== true) {
            checkbox.checked = !checkbox.checked;
            actualizarEstadoVisualAnexo(checkbox);
            return;
          }
          actualizarEstadoVisualAnexo(checkbox);
        })
        .catch(() => {
          checkbox.checked = !checkbox.checked;
          actualizarEstadoVisualAnexo(checkbox);
        });
    };

    document.addEventListener('change', (event) => {
      const checkbox = event.target.closest('input[type="checkbox"][data-anexo-ajax="1"]');
      if (!checkbox) {
        return;
      }

      guardarFaseAnexoAjax(checkbox);
    });

    const crearBloqueSeguimientoAnexo7 = (idPractica, idAnexo, numeroSeguimiento) => {
      const panel = document.createElement('div');
      panel.className = 'anexos-estado-row anexos-estado-row--seguimiento anexos-estado-row--compacta';
      panel.setAttribute('data-anexo-7-seguimiento-item', '1');
      panel.dataset.numeroSeguimiento = String(numeroSeguimiento);
      panel.setAttribute('data-anexo-status-row', '1');

      const colAnexo = document.createElement('div');
      colAnexo.className = 'anexos-estado-col anexo-col';
      const title = document.createElement('p');
      title.className = 'anexos-estado-anexo-titulo';
      title.textContent = `Seguimiento ${numeroSeguimiento}`;
      colAnexo.appendChild(title);
      panel.appendChild(colAnexo);

      const steps = document.createElement('div');
      steps.className = 'anexos-estado-col pasos-col';
      steps.setAttribute('data-fases-container', '1');

      anexosFasesCatalog.forEach((fase) => {
        const label = document.createElement('label');
        label.className = 'anexos-estado-hito-wrap';
        label.setAttribute('data-hito-wrap', '1');
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.className = 'anexos-estado-input';
        checkbox.name = 'fases[]';
        checkbox.value = String(fase.id);
        checkbox.dataset.anexoAjax = '1';
        checkbox.dataset.ajaxAction = 'ajax_guardar_anexo_7_fase';
        checkbox.dataset.idPractica = idPractica;
        checkbox.dataset.idPracticasAnexo = idAnexo;
        checkbox.dataset.idPracticasAnexoEstado = String(fase.id);
        checkbox.dataset.numeroSeguimiento = String(numeroSeguimiento);
        label.appendChild(checkbox);
        const hito = document.createElement('span');
        hito.className = 'anexos-estado-hito';
        hito.title = fase.estado;
        label.appendChild(hito);
        const sr = document.createElement('span');
        sr.className = 'sr-only';
        sr.textContent = fase.estado;
        label.appendChild(sr);
        steps.appendChild(label);
      });
      panel.appendChild(steps);

      const progreso = document.createElement('div');
      progreso.className = 'anexos-estado-col progreso-col';
      const progresoValor = document.createElement('span');
      progresoValor.className = 'anexos-estado-progreso-valor';
      progresoValor.setAttribute('data-progreso-valor', '1');
      progresoValor.textContent = '0%';
      progreso.appendChild(progresoValor);
      const progresoBarra = document.createElement('div');
      progresoBarra.className = 'anexos-estado-progreso-barra';
      const progresoFill = document.createElement('span');
      progresoFill.className = 'anexos-estado-progreso-barra-fill';
      progresoFill.setAttribute('data-progreso-fill', '1');
      progresoFill.style.width = '0%';
      progresoBarra.appendChild(progresoFill);
      progreso.appendChild(progresoBarra);
      panel.appendChild(progreso);

      return panel;
    };

    document.addEventListener('click', (event) => {
      const button = event.target.closest('[data-anexo-7-add-button]');
      if (!button) {
        return;
      }

      const idPractica = button.dataset.idPractica;
      const idAnexo = button.dataset.idPracticasAnexo;
      const params = new URLSearchParams();
      params.append('ajax', '1');
      params.append('action', 'ajax_crear_anexo_7_seguimiento');
      params.append('id_practica', idPractica);
      params.append('id_practicas_anexo', idAnexo);
      const panel = button.closest('.panel');
      if (!panel) {
        return;
      }
      const list = panel.querySelector('[data-anexo-7-seguimientos-list]');
      if (!list) {
        return;
      }
      let numeroSeguimientoBase = 0;
      list.querySelectorAll('[data-anexo-7-seguimiento-item]').forEach((item) => {
        const value = Number(item.dataset.numeroSeguimiento || '0');
        if (value > numeroSeguimientoBase) {
          numeroSeguimientoBase = value;
        }
      });
      params.append('numero_seguimiento_base', String(numeroSeguimientoBase));

      fetch('practica_detalle.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        },
        body: params.toString(),
      })
        .then((response) => response.json())
        .then((data) => {
          if (!data || data.ok !== true) {
            return;
          }
          const bloque = crearBloqueSeguimientoAnexo7(idPractica, idAnexo, data.numero_seguimiento);
          list.appendChild(bloque);
        })
        .catch(() => {
        });
    });
  </script>
  <script src="assets/copy.js"></script>
</body>
</html>
