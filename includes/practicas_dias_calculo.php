<?php

foreach ($practices as $practice) {
  $student_id = (int) $practice['id_alumno'];
  if (!isset($student_rows[$student_id])) {
    $student_rows[$student_id] = [
      'id_practica' => (int) $practice['id_practica'],
      'name' => format_student_name($practice, 'alumno'),
      'apellido1' => mb_strtolower(trim((string) ($practice['alumno_apellido1'] ?? ''))),
      'months' => array_fill_keys(array_keys($months), 0),
      'month_practice_ids' => array_fill_keys(array_keys($months), []),
      'seconds' => 0,
      'current_month_seconds' => 0,
      'had_current_month_in_course' => false,
      'status' => calculate_practice_status($practice),
    ];
  }

  if (
    $student_rows[$student_id]['status'] === 'Cancelada'
    && (int) ($practice['cancelada'] ?? 0) === 0
  ) {
    $student_rows[$student_id]['status'] = calculate_practice_status($practice);
  }

  $fecha_inicio = DateTimeImmutable::createFromFormat('Y-m-d', (string) ($practice['fecha_inicio'] ?? ''));
  $fecha_fin_extra = DateTimeImmutable::createFromFormat('Y-m-d', (string) ($practice['fecha_fin_extra'] ?? ''));
  $fecha_fin_real = DateTimeImmutable::createFromFormat('Y-m-d', (string) ($practice['fecha_fin_real'] ?? ''));

  if ($fecha_inicio === false || $fecha_fin_extra === false || $fecha_inicio > $fecha_fin_extra) {
    continue;
  }

  $fecha_fin_practica = $fecha_fin_extra;
  if ((int) ($practice['cancelada'] ?? 0) === 1 && $fecha_fin_real !== false && $fecha_fin_real >= $fecha_inicio) {
    $fecha_fin_practica = $fecha_fin_real;
  }

  $schedule_by_day = $schedule_by_practice[(int) $practice['id_practica']] ?? [];

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

  $fecha_hasta_hoy = $hoy < $fecha_fin_practica ? $hoy : $fecha_fin_practica;

  for ($cursor = $fecha_inicio; $cursor <= $fecha_fin_practica; $cursor = $cursor->modify('+1 day')) {
    $fecha_cursor = $cursor->format('Y-m-d');
    if (isset($non_teaching_days_lookup[$fecha_cursor])) {
      continue;
    }

    $dia_semana = (int) $cursor->format('N');
    if (!isset($segundos_por_dia_semana[$dia_semana])) {
      continue;
    }

    $mes_numero = (int) $cursor->format('n');
    if (isset($student_rows[$student_id]['months'][$mes_numero])) {
      $student_rows[$student_id]['months'][$mes_numero]++;
      $student_rows[$student_id]['month_practice_ids'][$mes_numero][(int) $practice['id_practica']] = true;
    }

    if ((int) $cursor->format('n') === $current_month && (int) $cursor->format('Y') === $current_year) {
      $student_rows[$student_id]['had_current_month_in_course'] = true;
    }

    if ($cursor <= $fecha_hasta_hoy) {
      $student_rows[$student_id]['seconds'] += $segundos_por_dia_semana[$dia_semana];

      if ((int) $cursor->format('n') === $current_month && (int) $cursor->format('Y') === $current_year) {
        $student_rows[$student_id]['current_month_seconds'] += $segundos_por_dia_semana[$dia_semana];
      }
    }
  }
}
