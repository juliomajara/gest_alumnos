<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function full_name_name_first(array $row, string $prefix): string
{
  $parts = array_filter([
    trim((string) ($row[$prefix . '_nombre'] ?? '')),
    trim((string) ($row[$prefix . '_apellido1'] ?? '')),
    trim((string) ($row[$prefix . '_apellido2'] ?? '')),
  ], static fn (string $value): bool => $value !== '');

  return $parts !== [] ? implode(' ', $parts) : 'No disponible';
}

$page_title = 'Días de prácticas | Gestor de Alumnos';
$active_page = 'utilidades';

$months = [
  9 => 'Septiembre',
  10 => 'Octubre',
  11 => 'Noviembre',
  12 => 'Diciembre',
  1 => 'Enero',
  2 => 'Febrero',
  3 => 'Marzo',
  4 => 'Abril',
  5 => 'Mayo',
  6 => 'Junio',
];

$student_rows = [];
$load_error = null;

try {
  $pdo = db();
  $active_course_id = $pdo->query('SELECT id_curso_escolar FROM cursos_escolares WHERE activo = 1 LIMIT 1')->fetchColumn();

  if ($active_course_id !== false) {
    $practices_stmt = $pdo->prepare(
      'SELECT DISTINCT
        p.id_practica,
        p.id_alumno,
        p.fecha_inicio,
        p.fecha_fin_real,
        a.nombre AS alumno_nombre,
        a.apellido1 AS alumno_apellido1,
        a.apellido2 AS alumno_apellido2
      FROM practicas p
      INNER JOIN alumnos a ON a.id_alumno = p.id_alumno
      INNER JOIN alumno_curso ac ON ac.id_alumno = p.id_alumno
      WHERE ac.id_curso_escolar = :active_course_id
      ORDER BY a.apellido1 ASC, a.apellido2 ASC, a.nombre ASC, p.id_practica ASC'
    );
    $practices_stmt->execute(['active_course_id' => $active_course_id]);
    $practices = $practices_stmt->fetchAll();

    if ($practices !== []) {
      $practice_ids = array_map(static fn (array $practice): int => (int) $practice['id_practica'], $practices);
      $placeholders = implode(', ', array_fill(0, count($practice_ids), '?'));

      $schedule_stmt = $pdo->prepare(
        'SELECT id_practica, dia_semana, hora_entrada, hora_salida
         FROM practicas_horario
         WHERE id_practica IN (' . $placeholders . ')
         ORDER BY id_practica ASC, dia_semana ASC, hora_entrada ASC'
      );
      $schedule_stmt->execute($practice_ids);
      $schedule_rows = $schedule_stmt->fetchAll();

      $schedule_by_practice = [];
      foreach ($schedule_rows as $row) {
        $practice_id = (int) $row['id_practica'];
        $day = (int) $row['dia_semana'];

        if (!isset($schedule_by_practice[$practice_id])) {
          $schedule_by_practice[$practice_id] = [];
        }
        if (!isset($schedule_by_practice[$practice_id][$day])) {
          $schedule_by_practice[$practice_id][$day] = [];
        }

        $schedule_by_practice[$practice_id][$day][] = $row;
      }

      $hoy = new DateTimeImmutable('today');

      foreach ($practices as $practice) {
        $student_id = (int) $practice['id_alumno'];
        if (!isset($student_rows[$student_id])) {
          $student_rows[$student_id] = [
            'name' => full_name_name_first($practice, 'alumno'),
            'months' => array_fill_keys(array_keys($months), 0),
            'seconds' => 0,
          ];
        }

        $fecha_inicio = DateTimeImmutable::createFromFormat('Y-m-d', (string) ($practice['fecha_inicio'] ?? ''));
        $fecha_fin_real = DateTimeImmutable::createFromFormat('Y-m-d', (string) ($practice['fecha_fin_real'] ?? ''));

        if ($fecha_inicio === false || $fecha_fin_real === false || $fecha_inicio > $fecha_fin_real) {
          continue;
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

        $fecha_hasta_hoy = $hoy < $fecha_fin_real ? $hoy : $fecha_fin_real;

        for ($cursor = $fecha_inicio; $cursor <= $fecha_fin_real; $cursor = $cursor->modify('+1 day')) {
          $dia_semana = (int) $cursor->format('N');
          if (!isset($segundos_por_dia_semana[$dia_semana])) {
            continue;
          }

          $mes_numero = (int) $cursor->format('n');
          if (isset($student_rows[$student_id]['months'][$mes_numero])) {
            $student_rows[$student_id]['months'][$mes_numero]++;
          }

          if ($cursor <= $fecha_hasta_hoy) {
            $student_rows[$student_id]['seconds'] += $segundos_por_dia_semana[$dia_semana];
          }
        }
      }
    }
  }
} catch (Throwable $error) {
  $load_error = 'No se ha podido cargar el resumen de días de prácticas en este momento.';
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
          <h1>Días de prácticas por alumno</h1>
          <p class="subheading">Consulta los días de prácticas por mes y las horas realizadas a día de hoy.</p>
        </div>
      </header>

      <section class="panel">
        <div class="panel-header">
          <h3>Resumen mensual del curso actual</h3>
          <p>Alumnado con prácticas registradas, días por mes y horas acumuladas.</p>
        </div>

        <div class="panel-grid">
          <table>
            <thead>
              <tr>
                <th>Alumno</th>
                <?php foreach ($months as $month_name): ?>
                  <th><?php echo htmlspecialchars($month_name, ENT_QUOTES, 'UTF-8'); ?></th>
                <?php endforeach; ?>
                <th>Horas realizadas</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($load_error !== null): ?>
                <tr>
                  <td colspan="12"><?php echo htmlspecialchars($load_error, ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
              <?php elseif ($student_rows === []): ?>
                <tr>
                  <td colspan="12">No hay prácticas registradas para el curso actual.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($student_rows as $student_row): ?>
                  <tr>
                    <td><?php echo htmlspecialchars((string) $student_row['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <?php foreach (array_keys($months) as $month_number): ?>
                      <td><?php echo htmlspecialchars((string) $student_row['months'][$month_number], ENT_QUOTES, 'UTF-8'); ?></td>
                    <?php endforeach; ?>
                    <td><?php echo htmlspecialchars(number_format(((float) $student_row['seconds']) / 3600, 2, ',', '.'), ENT_QUOTES, 'UTF-8'); ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>
    </main>
  </div>
</body>
</html>
