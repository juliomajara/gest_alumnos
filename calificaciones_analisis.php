<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pdo = db();

$page_title = 'Análisis de calificaciones | Gestor de Alumnos';
$active_page = 'calificaciones';

function grade_display(?string $original, $nota): string
{
  $calificacion_original = trim((string) ($original ?? ''));
  if ($calificacion_original !== '') {
    return $calificacion_original;
  }

  if ($nota !== null && $nota !== '') {
    return rtrim(rtrim(number_format((float) $nota, 2, '.', ''), '0'), '.');
  }

  return '—';
}

function grade_numeric(?string $original, $nota): ?float
{
  $calificacion_original = trim((string) ($original ?? ''));
  if ($calificacion_original !== '' && preg_match('/^(TC|CV)\b/i', $calificacion_original)) {
    return null;
  }

  if ($nota !== null && $nota !== '') {
    return (float) $nota;
  }

  if ($calificacion_original === '') {
    return null;
  }

  if (preg_match('/^([A-Za-z]{1,10})-([0-9]+(?:[\.,][0-9]+)?)$/', $calificacion_original, $match)) {
    return (float) str_replace(',', '.', $match[2]);
  }

  if (preg_match('/^[0-9]+(?:[\.,][0-9]+)?$/', $calificacion_original)) {
    return (float) str_replace(',', '.', $calificacion_original);
  }

  return null;
}

function classify_student(int $suspensos): string
{
  if ($suspensos <= 0) {
    return 'Todo aprobado';
  }

  if ($suspensos === 1) {
    return '1 suspensa';
  }

  if ($suspensos === 2) {
    return '2 suspensas';
  }

  return '3 o más suspensas';
}

function fmt(?float $value, int $decimals = 2): string
{
  if ($value === null) {
    return '—';
  }

  return number_format($value, $decimals, ',', '.');
}

function fmt_count_pct(?int $count, int $total): string
{
  if ($count === null || $total <= 0) {
    return '—';
  }

  return $count . ' / ' . fmt($count * 100 / $total) . '%';
}

function median(array $values): ?float
{
  if ($values === []) {
    return null;
  }

  sort($values, SORT_NUMERIC);
  $count = count($values);
  $mid = intdiv($count, 2);

  if ($count % 2 === 1) {
    return (float) $values[$mid];
  }

  return ((float) $values[$mid - 1] + (float) $values[$mid]) / 2;
}

function deviation(array $values): ?float
{
  if ($values === []) {
    return null;
  }

  $n = count($values);
  $mean = array_sum($values) / $n;
  $sum_sq = 0.0;
  foreach ($values as $value) {
    $sum_sq += ($value - $mean) ** 2;
  }

  return sqrt($sum_sq / $n);
}

function render_bar_chart(string $title, array $labels, array $values, array $tooltips = [], ?float $threshold = null): string
{
  $max = 0.0;
  foreach ($values as $value) {
    $max = max($max, (float) $value);
  }

  $n = count($values);
  $bar_width = max(44, min(80, (int) floor((540 - 60) / max(1, $n)) - 12));
  $gap = 12;
  $chart_height = 240;
  $base_y = 182;
  $plot_height = 130;
  $svg_width = max(340, $n * ($bar_width + $gap) + 70);

  ob_start();
  ?>
  <section class="panel">
    <div class="panel-header">
      <h3><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h3>
    </div>
    <div class="ca-chart-wrap">
      <svg viewBox="0 0 <?php echo $svg_width; ?> <?php echo $chart_height; ?>" width="100%" style="min-width:<?php echo min($svg_width, 280); ?>px;display:block;" role="img" aria-label="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>">
        <?php for ($ca_g = 1; $ca_g <= 4; $ca_g++): ?>
          <?php $ca_gy = $base_y - (int) round($ca_g * $plot_height / 4); ?>
          <line x1="44" y1="<?php echo $ca_gy; ?>" x2="<?php echo $svg_width - 10; ?>" y2="<?php echo $ca_gy; ?>" stroke="#eef0f7" stroke-width="1" stroke-dasharray="4 3"></line>
          <text x="40" y="<?php echo $ca_gy + 4; ?>" text-anchor="end" font-size="9" fill="#9ca3af"><?php echo round($max * $ca_g / 4, 1); ?></text>
        <?php endfor; ?>
        <line x1="44" y1="<?php echo $base_y; ?>" x2="<?php echo $svg_width - 10; ?>" y2="<?php echo $base_y; ?>" stroke="#d1d5db" stroke-width="1.5"></line>
        <?php foreach ($values as $ca_i => $value): ?>
          <?php
            $ca_x   = 52 + $ca_i * ($bar_width + $gap);
            $ca_h   = $max > 0 ? (int) round(((float) $value / $max) * $plot_height) : 0;
            $ca_h   = max($ca_h, (float) $value > 0 ? 2 : 0);
            $ca_by  = $base_y - $ca_h;
            $ca_cx  = $ca_x + intdiv($bar_width, 2);
            $ca_lbl = (string) ($labels[$ca_i] ?? '');
            $ca_gid = 'bg' . $ca_i . '_' . abs(crc32($title));
            $ca_tip = htmlspecialchars((string) ($tooltips[$ca_i] ?? ''), ENT_QUOTES, 'UTF-8');
            if ($threshold !== null) {
              [$ca_ct, $ca_cb] = (float) $value >= $threshold ? ['#34d399', '#10b981'] : ['#f87171', '#ef4444'];
            } else {
              [$ca_ct, $ca_cb] = ['#818cf8', '#4f46e5'];
            }
          ?>
          <defs>
            <linearGradient id="<?php echo $ca_gid; ?>" x1="0" y1="0" x2="0" y2="1">
              <stop offset="0%" stop-color="<?php echo $ca_ct; ?>"></stop>
              <stop offset="100%" stop-color="<?php echo $ca_cb; ?>"></stop>
            </linearGradient>
          </defs>
          <?php if ($ca_tip !== ''): ?>
          <g class="ca-bar-group" data-name="<?php echo $ca_tip; ?>">
          <?php endif; ?>
          <rect x="<?php echo $ca_x; ?>" y="<?php echo $ca_by; ?>" width="<?php echo $bar_width; ?>" height="<?php echo $ca_h; ?>" rx="5" ry="5" fill="url(#<?php echo $ca_gid; ?>)"></rect>
          <text x="<?php echo $ca_cx; ?>" y="<?php echo $ca_by - 5; ?>" text-anchor="middle" font-size="10" font-weight="600" fill="#374151"><?php echo round((float) $value, 1); ?></text>
          <text x="<?php echo $ca_cx; ?>" y="<?php echo $base_y + 14; ?>" text-anchor="middle" font-size="10" fill="#6d7a99"><?php echo htmlspecialchars($ca_lbl, ENT_QUOTES, 'UTF-8'); ?></text>
          <?php if ($ca_tip !== ''): ?>
          </g>
          <?php endif; ?>
        <?php endforeach; ?>
      </svg>
    </div>
  </section>
  <?php

  return (string) ob_get_clean();
}

function render_pass_rate_chart(string $title, array $labels, array $values, array $tooltips = []): string
{
  $n = count($values);
  $bar_width = max(44, min(80, (int) floor((540 - 60) / max(1, $n)) - 12));
  $gap = 12;
  $chart_height = 250;
  $base_y = 182;
  $plot_height = 130;
  $svg_width = max(340, $n * ($bar_width + $gap) + 70);

  ob_start();
  ?>
  <section class="panel">
    <div class="panel-header">
      <h3><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h3>
    </div>
    <div class="ca-chart-wrap">
      <svg viewBox="0 0 <?php echo $svg_width; ?> <?php echo $chart_height; ?>" width="100%" style="min-width:<?php echo min($svg_width, 280); ?>px;display:block;" role="img" aria-label="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>">
        <?php foreach ([25, 50, 75, 100] as $ca_pct): ?>
          <?php $ca_gy = $base_y - (int) round($ca_pct / 100 * $plot_height); ?>
          <?php $ca_is_ref = $ca_pct === 50 || $ca_pct === 75; ?>
          <line x1="44" y1="<?php echo $ca_gy; ?>" x2="<?php echo $svg_width - 10; ?>" y2="<?php echo $ca_gy; ?>"
            stroke="<?php echo $ca_pct === 50 ? '#f59e0b' : ($ca_pct === 75 ? '#10b981' : '#eef0f7'); ?>"
            stroke-width="<?php echo $ca_is_ref ? '1.5' : '1'; ?>"
            stroke-dasharray="<?php echo $ca_is_ref ? '6 3' : '4 3'; ?>"></line>
          <text x="40" y="<?php echo $ca_gy + 4; ?>" text-anchor="end" font-size="9" fill="<?php echo $ca_pct === 50 ? '#f59e0b' : ($ca_pct === 75 ? '#10b981' : '#9ca3af'); ?>"><?php echo $ca_pct; ?>%</text>
        <?php endforeach; ?>
        <line x1="44" y1="<?php echo $base_y; ?>" x2="<?php echo $svg_width - 10; ?>" y2="<?php echo $base_y; ?>" stroke="#d1d5db" stroke-width="1.5"></line>
        <!-- Legend -->
        <?php $ca_leg_x = max(100, $svg_width - 140); ?>
        <rect x="<?php echo $ca_leg_x; ?>" y="10" width="9" height="9" rx="2" fill="#10b981"></rect>
        <text x="<?php echo $ca_leg_x + 13; ?>" y="18" font-size="9" fill="#6d7a99">&#8805;75%</text>
        <rect x="<?php echo $ca_leg_x + 40; ?>" y="10" width="9" height="9" rx="2" fill="#f59e0b"></rect>
        <text x="<?php echo $ca_leg_x + 53; ?>" y="18" font-size="9" fill="#6d7a99">50-75%</text>
        <rect x="<?php echo $ca_leg_x + 92; ?>" y="10" width="9" height="9" rx="2" fill="#ef4444"></rect>
        <text x="<?php echo $ca_leg_x + 105; ?>" y="18" font-size="9" fill="#6d7a99">&lt;50%</text>
        <?php foreach ($values as $ca_i => $value): ?>
          <?php
            $ca_pv  = (float) $value;
            $ca_x   = 52 + $ca_i * ($bar_width + $gap);
            $ca_h   = (int) round($ca_pv / 100 * $plot_height);
            $ca_h   = max($ca_h, $ca_pv > 0 ? 2 : 0);
            $ca_by  = $base_y - $ca_h;
            $ca_cx  = $ca_x + intdiv($bar_width, 2);
            $ca_lbl = (string) ($labels[$ca_i] ?? '');
            $ca_gid = 'pr' . $ca_i . '_' . abs(crc32($title));
            $ca_tip = htmlspecialchars((string) ($tooltips[$ca_i] ?? ''), ENT_QUOTES, 'UTF-8');
            if ($ca_pv >= 75) {
              [$ca_ct, $ca_cb] = ['#34d399', '#10b981'];
            } elseif ($ca_pv >= 50) {
              [$ca_ct, $ca_cb] = ['#fbbf24', '#f59e0b'];
            } else {
              [$ca_ct, $ca_cb] = ['#f87171', '#ef4444'];
            }
          ?>
          <defs>
            <linearGradient id="<?php echo $ca_gid; ?>" x1="0" y1="0" x2="0" y2="1">
              <stop offset="0%" stop-color="<?php echo $ca_ct; ?>"></stop>
              <stop offset="100%" stop-color="<?php echo $ca_cb; ?>"></stop>
            </linearGradient>
          </defs>
          <?php if ($ca_tip !== ''): ?>
          <g class="ca-bar-group" data-name="<?php echo $ca_tip; ?>">
          <?php endif; ?>
          <rect x="<?php echo $ca_x; ?>" y="<?php echo $ca_by; ?>" width="<?php echo $bar_width; ?>" height="<?php echo $ca_h; ?>" rx="5" ry="5" fill="url(#<?php echo $ca_gid; ?>)"></rect>
          <text x="<?php echo $ca_cx; ?>" y="<?php echo $ca_by - 5; ?>" text-anchor="middle" font-size="10" font-weight="600" fill="#374151"><?php echo round($ca_pv, 1); ?>%</text>
          <text x="<?php echo $ca_cx; ?>" y="<?php echo $base_y + 14; ?>" text-anchor="middle" font-size="10" fill="#6d7a99"><?php echo htmlspecialchars($ca_lbl, ENT_QUOTES, 'UTF-8'); ?></text>
          <?php if ($ca_tip !== ''): ?>
          </g>
          <?php endif; ?>
        <?php endforeach; ?>
      </svg>
    </div>
  </section>
  <?php
  return (string) ob_get_clean();
}

function render_grade_histogram(string $title, array $histogram): string
{
  $max_count = max(array_merge([1], $histogram));
  $bar_width = 46;
  $gap = 8;
  $chart_height = 235;
  $base_y = 178;
  $plot_height = 120;
  $svg_width = 10 * ($bar_width + $gap) + 70;
  $ref_x = 44 + 5 * ($bar_width + $gap) - (int) round($gap / 2);

  ob_start();
  ?>
  <section class="panel">
    <div class="panel-header">
      <h3><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h3>
    </div>
    <div class="ca-chart-wrap">
      <svg viewBox="0 0 <?php echo $svg_width; ?> <?php echo $chart_height; ?>" width="100%" style="min-width:<?php echo min($svg_width, 280); ?>px;display:block;" role="img" aria-label="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>">
        <?php for ($ca_g = 1; $ca_g <= 4; $ca_g++): ?>
          <?php $ca_gy = $base_y - (int) round($ca_g * $plot_height / 4); ?>
          <line x1="44" y1="<?php echo $ca_gy; ?>" x2="<?php echo $svg_width - 10; ?>" y2="<?php echo $ca_gy; ?>" stroke="#eef0f7" stroke-width="1" stroke-dasharray="4 3"></line>
          <text x="40" y="<?php echo $ca_gy + 4; ?>" text-anchor="end" font-size="9" fill="#9ca3af"><?php echo (int) round($max_count * $ca_g / 4); ?></text>
        <?php endfor; ?>
        <line x1="44" y1="<?php echo $base_y; ?>" x2="<?php echo $svg_width - 10; ?>" y2="<?php echo $base_y; ?>" stroke="#d1d5db" stroke-width="1.5"></line>
        <!-- Separator suspenso / aprobado -->
        <line x1="<?php echo $ref_x; ?>" y1="22" x2="<?php echo $ref_x; ?>" y2="<?php echo $base_y; ?>" stroke="#6b7280" stroke-width="1.5" stroke-dasharray="5 3"></line>
        <text x="<?php echo $ref_x - 4; ?>" y="18" text-anchor="end" font-size="9" font-weight="600" fill="#ef4444">Suspenso</text>
        <text x="<?php echo $ref_x + 4; ?>" y="18" text-anchor="start" font-size="9" font-weight="600" fill="#10b981">Aprobado</text>
        <?php $ca_bin_labels = ['0-1','1-2','2-3','3-4','4-5','5-6','6-7','7-8','8-9','9-10']; ?>
        <?php foreach ($histogram as $ca_bin => $ca_count): ?>
          <?php
            $ca_x   = 52 + $ca_bin * ($bar_width + $gap);
            $ca_h   = $max_count > 0 ? (int) round($ca_count / $max_count * $plot_height) : 0;
            $ca_h   = max($ca_h, $ca_count > 0 ? 2 : 0);
            $ca_by  = $base_y - $ca_h;
            $ca_cx  = $ca_x + intdiv($bar_width, 2);
            $ca_gid = 'gh' . $ca_bin . '_' . abs(crc32($title));
            [$ca_ct, $ca_cb] = $ca_bin >= 5 ? ['#34d399', '#10b981'] : ['#f87171', '#ef4444'];
          ?>
          <defs>
            <linearGradient id="<?php echo $ca_gid; ?>" x1="0" y1="0" x2="0" y2="1">
              <stop offset="0%" stop-color="<?php echo $ca_ct; ?>"></stop>
              <stop offset="100%" stop-color="<?php echo $ca_cb; ?>"></stop>
            </linearGradient>
          </defs>
          <rect x="<?php echo $ca_x; ?>" y="<?php echo $ca_by; ?>" width="<?php echo $bar_width; ?>" height="<?php echo $ca_h; ?>" rx="4" ry="4" fill="url(#<?php echo $ca_gid; ?>)"></rect>
          <?php if ($ca_count > 0): ?>
            <text x="<?php echo $ca_cx; ?>" y="<?php echo $ca_by - 5; ?>" text-anchor="middle" font-size="10" font-weight="600" fill="#374151"><?php echo $ca_count; ?></text>
          <?php endif; ?>
          <text x="<?php echo $ca_cx; ?>" y="<?php echo $base_y + 14; ?>" text-anchor="middle" font-size="9" fill="#6d7a99"><?php echo $ca_bin_labels[$ca_bin]; ?></text>
        <?php endforeach; ?>
      </svg>
    </div>
  </section>
  <?php
  return (string) ob_get_clean();
}

function render_donut_chart(string $title, array $segments): string
{
  $total = array_sum(array_column($segments, 'value'));
  if ($total <= 0) {
    return '';
  }

  $cx = 90; $cy = 90;
  $r_outer = 70; $r_inner = 34;

  $paths = [];
  $start_angle = -90.0;
  foreach ($segments as $seg) {
    if ((int) $seg['value'] <= 0) {
      continue;
    }
    $sweep = ($seg['value'] / $total) * 360;
    if ($sweep < 0.5) {
      continue;
    }
    $end_angle  = $start_angle + $sweep;
    $large      = $sweep > 180 ? 1 : 0;
    $mid_angle  = $start_angle + $sweep / 2;
    $mid_r      = ($r_outer + $r_inner) / 2;
    $x1  = round($cx + $r_outer * cos(deg2rad($start_angle)), 3);
    $y1  = round($cy + $r_outer * sin(deg2rad($start_angle)), 3);
    $x2  = round($cx + $r_outer * cos(deg2rad($end_angle)), 3);
    $y2  = round($cy + $r_outer * sin(deg2rad($end_angle)), 3);
    $ix1 = round($cx + $r_inner * cos(deg2rad($start_angle)), 3);
    $iy1 = round($cy + $r_inner * sin(deg2rad($start_angle)), 3);
    $ix2 = round($cx + $r_inner * cos(deg2rad($end_angle)), 3);
    $iy2 = round($cy + $r_inner * sin(deg2rad($end_angle)), 3);
    $paths[] = [
      'd'     => "M {$x1} {$y1} A {$r_outer} {$r_outer} 0 {$large} 1 {$x2} {$y2} L {$ix2} {$iy2} A {$r_inner} {$r_inner} 0 {$large} 0 {$ix1} {$iy1} Z",
      'color' => (string) $seg['color'],
      'label' => (string) $seg['label'],
      'value' => (int) $seg['value'],
      'pct'   => round($seg['value'] * 100 / $total, 1),
      'sweep' => $sweep,
      'mid_x' => round($cx + $mid_r * cos(deg2rad($mid_angle)), 1),
      'mid_y' => round($cy + $mid_r * sin(deg2rad($mid_angle)), 1),
    ];
    $start_angle = $end_angle;
  }

  if ($paths === []) {
    return '';
  }

  ob_start();
  ?>
  <section class="panel">
    <div class="panel-header">
      <h3><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h3>
    </div>
    <div class="ca-donut-wrap">
      <svg viewBox="0 0 180 180" width="180" height="180" role="img" aria-label="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>">
        <?php foreach ($paths as $path): ?>
          <path d="<?php echo $path['d']; ?>" fill="<?php echo htmlspecialchars($path['color'], ENT_QUOTES, 'UTF-8'); ?>" stroke="#fff" stroke-width="2"></path>
        <?php endforeach; ?>
        <?php foreach ($paths as $path): ?>
          <?php if ($path['sweep'] >= 18): ?>
            <text
              x="<?php echo $path['mid_x']; ?>"
              y="<?php echo round($path['mid_y'] - 4, 1); ?>"
              text-anchor="middle"
              font-size="<?php echo $path['sweep'] >= 40 ? '11' : '9'; ?>"
              font-weight="700"
              fill="#fff"><?php echo $path['pct']; ?>%</text>
            <?php if ($path['sweep'] >= 40): ?>
            <text
              x="<?php echo $path['mid_x']; ?>"
              y="<?php echo round($path['mid_y'] + 8, 1); ?>"
              text-anchor="middle"
              font-size="9"
              fill="rgba(255,255,255,0.85)"><?php echo htmlspecialchars($path['value'] . ' al.', ENT_QUOTES, 'UTF-8'); ?></text>
            <?php endif; ?>
          <?php endif; ?>
        <?php endforeach; ?>
        <text x="<?php echo $cx; ?>" y="<?php echo $cy - 7; ?>" text-anchor="middle" font-size="24" font-weight="700" fill="#1f2a44"><?php echo $total; ?></text>
        <text x="<?php echo $cx; ?>" y="<?php echo $cy + 12; ?>" text-anchor="middle" font-size="10" fill="#6d7a99">alumnos</text>
      </svg>
      <ul class="ca-donut-legend">
        <?php foreach ($paths as $path): ?>
          <li>
            <span class="ca-donut-dot" style="background:<?php echo htmlspecialchars($path['color'], ENT_QUOTES, 'UTF-8'); ?>"></span>
            <span class="ca-donut-label"><?php echo htmlspecialchars($path['label'], ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="ca-donut-count"><?php echo $path['value']; ?> <small>(<?php echo $path['pct']; ?>%)</small></span>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </section>
  <?php
  return (string) ob_get_clean();
}

$courses = $pdo->query(
  'SELECT id_curso_escolar, curso_escolar, activo
   FROM cursos_escolares
   ORDER BY activo DESC, id_curso_escolar DESC'
)->fetchAll();

$active_course_id = $pdo->query('SELECT id_curso_escolar FROM cursos_escolares WHERE activo = 1 ORDER BY id_curso_escolar DESC LIMIT 1')->fetchColumn();
if (!$active_course_id && $courses !== []) {
  $active_course_id = (int) $courses[0]['id_curso_escolar'];
}
$active_course_id = (int) $active_course_id;

$selected_course_id = isset($_GET['id_curso_escolar']) && ctype_digit((string) $_GET['id_curso_escolar'])
  ? (int) $_GET['id_curso_escolar']
  : $active_course_id;

$default_group_id = (string) ($pdo->query("SELECT valor FROM `config` WHERE `clave` = 'grupo_por_defecto' LIMIT 1")->fetchColumn() ?: '');
$selected_group = (string) ($_GET['id_grupo'] ?? $default_group_id);
$selected_group = ctype_digit($selected_group) ? $selected_group : '';

$selected_evaluation = isset($_GET['id_evaluacion']) && ctype_digit((string) $_GET['id_evaluacion'])
  ? (int) $_GET['id_evaluacion']
  : 0;

$search_term = trim((string) ($_GET['q'] ?? ''));

$evaluations = $pdo->query('SELECT id_evaluacion, nombre FROM evaluaciones ORDER BY id_evaluacion')->fetchAll();
if ($selected_evaluation <= 0 && $evaluations !== []) {
  $selected_evaluation = (int) $evaluations[count($evaluations) - 1]['id_evaluacion'];
}

$evaluation_name_by_id = [];
foreach ($evaluations as $evaluation) {
  $evaluation_name_by_id[(int) $evaluation['id_evaluacion']] = (string) $evaluation['nombre'];
}

$groups_stmt = $pdo->prepare(
  'SELECT DISTINCT g.id_grupo, g.grupo
   FROM alumno_curso ac
   INNER JOIN grupos g ON g.id_grupo = ac.id_grupo
   WHERE ac.id_curso_escolar = :id_curso_escolar
   ORDER BY g.grupo'
);
$groups_stmt->execute([
  'id_curso_escolar' => $selected_course_id,
]);
$groups = $groups_stmt->fetchAll();
$groups_by_id = [];
foreach ($groups as $group) {
  $groups_by_id[(int) $group['id_grupo']] = (string) $group['grupo'];
}

$show_results = $selected_group !== '' && $selected_evaluation > 0;

$students = [];
$modules = [];
$grades_current = [];
$grades_previous = [];
$student_rows = [];
$module_rows = [];
$summary_rows = [];
$group_stats = [];
$conclusions = [];
$previous_evaluation_id = 0;
$group_mean_history = [];
$total_recoveries = 0;
$total_falls = 0;
$selected_group_course_id = 0;
$students_with_computable_grades = [];

if ($show_results) {
  $students_stmt = $pdo->prepare(
    'SELECT
       a.id_alumno,
       a.apellido1,
       a.apellido2,
       a.nombre,
       g.grupo
     FROM alumno_curso ac
     INNER JOIN alumnos a ON a.id_alumno = ac.id_alumno
     LEFT JOIN grupos g ON g.id_grupo = ac.id_grupo
     WHERE ac.id_curso_escolar = :id_curso_escolar
       AND ac.id_grupo = :id_grupo
       AND (
         :search_term = ""
         OR a.nombre LIKE :search_term_like_nombre
         OR a.apellido1 LIKE :search_term_like_apellido1
         OR a.apellido2 LIKE :search_term_like_apellido2
       )
     ORDER BY g.grupo, a.apellido1, a.apellido2, a.nombre'
  );
  $search_like = '%' . $search_term . '%';
  $students_stmt->execute([
    'id_curso_escolar' => $selected_course_id,
    'id_grupo' => (int) $selected_group,
    'search_term' => $search_term,
    'search_term_like_nombre' => $search_like,
    'search_term_like_apellido1' => $search_like,
    'search_term_like_apellido2' => $search_like,
  ]);
  $students = $students_stmt->fetchAll();

  $selected_group_course_stmt = $pdo->prepare(
    'SELECT ac.id_curso
     FROM alumno_curso ac
     WHERE ac.id_curso_escolar = :id_curso_escolar
       AND ac.id_grupo = :id_grupo
     GROUP BY ac.id_curso
     ORDER BY ac.id_curso
     LIMIT 1'
  );
  $selected_group_course_stmt->execute([
    'id_curso_escolar' => $selected_course_id,
    'id_grupo' => (int) $selected_group,
  ]);
  $selected_group_course_id = (int) $selected_group_course_stmt->fetchColumn();

  $modules_stmt = $pdo->prepare(
    'SELECT DISTINCT
       m.id_modulo,
       m.id_ciclo,
       m.id_curso,
       m.codigo,
       m.abreviatura,
       m.materia_general,
       m.materia_propia
     FROM modulos m
     INNER JOIN alumno_curso ac
       ON ac.id_ciclo = m.id_ciclo
      AND ac.id_curso = m.id_curso
     WHERE ac.id_curso_escolar = :id_curso_escolar
       AND ac.id_grupo = :id_grupo
       AND m.id_curso = :id_curso
       AND COALESCE(m.tipo, "") <> "FFE"
     ORDER BY m.id_ciclo, m.id_curso, m.codigo, m.id_modulo'
  );
  $modules_stmt->execute([
    'id_curso_escolar' => $selected_course_id,
    'id_grupo' => (int) $selected_group,
    'id_curso' => $selected_group_course_id,
  ]);
  $modules = $modules_stmt->fetchAll();

  $evaluation_ids_in_context_stmt = $pdo->prepare(
    'SELECT DISTINCT c.id_evaluacion
     FROM calificaciones c
     WHERE c.id_curso_escolar = :id_curso_escolar
       AND c.id_grupo = :id_grupo
     ORDER BY c.id_evaluacion'
  );
  $evaluation_ids_in_context_stmt->execute([
    'id_curso_escolar' => $selected_course_id,
    'id_grupo' => (int) $selected_group,
  ]);
  $evaluation_ids_in_context = array_map(static fn (array $row): int => (int) $row['id_evaluacion'], $evaluation_ids_in_context_stmt->fetchAll());

  foreach ($evaluation_ids_in_context as $evaluation_id) {
    if ($evaluation_id < $selected_evaluation) {
      $previous_evaluation_id = $evaluation_id;
    }
  }

  $grades_stmt = $pdo->prepare(
    'SELECT id_alumno, id_modulo, id_evaluacion, calificacion_original, nota
     FROM calificaciones
     WHERE id_curso_escolar = :id_curso_escolar
       AND id_grupo = :id_grupo
       AND id_evaluacion IN (:current_evaluation, :previous_evaluation)'
  );
  $grades_stmt->bindValue(':id_curso_escolar', $selected_course_id, PDO::PARAM_INT);
  $grades_stmt->bindValue(':id_grupo', (int) $selected_group, PDO::PARAM_INT);
  $grades_stmt->bindValue(':current_evaluation', $selected_evaluation, PDO::PARAM_INT);
  $grades_stmt->bindValue(':previous_evaluation', $previous_evaluation_id > 0 ? $previous_evaluation_id : -1, PDO::PARAM_INT);
  $grades_stmt->execute();
  $grades = $grades_stmt->fetchAll();

  foreach ($grades as $grade_row) {
    $id_alumno = (int) $grade_row['id_alumno'];
    $id_modulo = (int) $grade_row['id_modulo'];
    $id_evaluacion = (int) $grade_row['id_evaluacion'];
    $numeric = grade_numeric((string) ($grade_row['calificacion_original'] ?? ''), $grade_row['nota']);
    $display = grade_display((string) ($grade_row['calificacion_original'] ?? ''), $grade_row['nota']);

    if ($id_evaluacion === $selected_evaluation) {
      $grades_current[$id_alumno][$id_modulo] = ['numeric' => $numeric, 'display' => $display];
    }
    if ($previous_evaluation_id > 0 && $id_evaluacion === $previous_evaluation_id) {
      $grades_previous[$id_alumno][$id_modulo] = ['numeric' => $numeric, 'display' => $display];
    }
  }

  foreach ($students as $student) {
    $id_alumno = (int) $student['id_alumno'];
    foreach ($modules as $module) {
      $module_id = (int) $module['id_modulo'];
      $current_numeric = $grades_current[$id_alumno][$module_id]['numeric'] ?? null;
      if ($current_numeric !== null) {
        $students_with_computable_grades[$id_alumno] = true;
        break;
      }
    }
  }

  if ($evaluation_ids_in_context !== [] && $modules !== [] && $students_with_computable_grades !== []) {
    $student_ids = array_keys($students_with_computable_grades);
    $student_placeholders = [];
    foreach ($student_ids as $index => $student_id) {
      $student_placeholders[] = ':id_alumno_' . $index;
    }
    $history_stmt = $pdo->prepare(
      'SELECT c.id_evaluacion, AVG(c.nota) AS media
       FROM calificaciones c
       INNER JOIN modulos m ON m.id_modulo = c.id_modulo
       WHERE c.id_curso_escolar = :id_curso_escolar
         AND c.id_grupo = :id_grupo
         AND c.id_alumno IN (' . implode(', ', $student_placeholders) . ')
         AND c.nota IS NOT NULL
         AND c.id_modulo IN (
           SELECT m2.id_modulo
           FROM modulos m2
           WHERE m2.id_curso = :id_curso
             AND COALESCE(m2.tipo, "") <> "FFE"
         )
         AND UPPER(TRIM(COALESCE(c.calificacion_original, ""))) NOT LIKE "TC%"
         AND UPPER(TRIM(COALESCE(c.calificacion_original, ""))) NOT LIKE "CV%"
         AND COALESCE(m.tipo, "") <> "FFE"
       GROUP BY c.id_evaluacion
       ORDER BY c.id_evaluacion'
    );
    $history_params = [
      'id_curso_escolar' => $selected_course_id,
      'id_grupo' => (int) $selected_group,
      'id_curso' => $selected_group_course_id,
    ];
    foreach ($student_ids as $index => $student_id) {
      $history_params['id_alumno_' . $index] = (int) $student_id;
    }
    $history_stmt->execute($history_params);
    $history_rows = $history_stmt->fetchAll();
    foreach ($history_rows as $history_row) {
      $evaluation_id = (int) $history_row['id_evaluacion'];
      $group_mean_history[] = [
        'label' => (string) ($evaluation_name_by_id[$evaluation_id] ?? ('Evaluación ' . $evaluation_id)),
        'value' => $history_row['media'] !== null ? (float) $history_row['media'] : 0.0,
      ];
    }
  }

  $module_stats = [];
  foreach ($modules as $module) {
    $module_id = (int) $module['id_modulo'];
    $module_code = trim((string) $module['codigo']);
    if ($module_code === '') {
      $module_code = trim((string) ($module['abreviatura'] ?? 'Módulo'));
    }
    $module_name = trim((string) ($module['materia_general'] ?? ''));
    if ($module_name === '') {
      $module_name = trim((string) ($module['materia_propia'] ?? ''));
    }

    $module_stats[$module_id] = [
      'codigo' => $module_code,
      'nombre' => $module_name,
      'aprobados' => 0,
      'suspensos' => 0,
      'no_evaluados' => 0,
      'numeric' => [],
      'prev_numeric' => [],
      'recuperan' => 0,
      'caen' => 0,
    ];
  }

  $classification_counts = [
    'Todo aprobado' => 0,
    '1 suspensa' => 0,
    '2 suspensas' => 0,
    '3 o más suspensas' => 0,
  ];
  $classification_counts_previous = [
    'Todo aprobado' => 0,
    '1 suspensa' => 0,
    '2 suspensas' => 0,
    '3 o más suspensas' => 0,
  ];
  $group_means_current = [];
  $group_means_previous = [];
  $improve_count = 0;
  $worsen_count = 0;
  $same_count = 0;
  $group_pass_current = 0;
  $group_pass_previous = 0;
  $group_total_valid_grades_current = 0;
  $group_total_valid_grades_previous = 0;
  $students_with_comparison = 0;

  foreach ($students as $student) {
    $id_alumno = (int) $student['id_alumno'];
    $full_name = trim((string) $student['apellido1'])
      . (trim((string) ($student['apellido2'] ?? '')) !== '' ? ' ' . trim((string) $student['apellido2']) : '')
      . ', '
      . trim((string) $student['nombre']);

    $aprobados = 0;
    $suspensos = 0;
    $no_evaluados = 0;
    $notes = [];
    $prev_notes = [];
    $best_module = '—';
    $worst_module = '—';
    $best_grade = null;
    $worst_grade = null;
    $suben = 0;
    $bajan = 0;
    $recupera = 0;
    $cae = 0;
    $comparables = 0;
    $student_has_computable_grades = isset($students_with_computable_grades[$id_alumno]);

    foreach ($modules as $module) {
      $module_id = (int) $module['id_modulo'];
      $current_numeric = $grades_current[$id_alumno][$module_id]['numeric'] ?? null;
      $previous_numeric = $grades_previous[$id_alumno][$module_id]['numeric'] ?? null;

      if ($current_numeric === null) {
        $no_evaluados++;
        if ($student_has_computable_grades) {
          $module_stats[$module_id]['no_evaluados']++;
        }
      } else {
        $notes[] = $current_numeric;
        if ($student_has_computable_grades) {
          $group_total_valid_grades_current++;
          $module_stats[$module_id]['numeric'][] = $current_numeric;
          if ($current_numeric >= 5) {
            $aprobados++;
            $module_stats[$module_id]['aprobados']++;
          } else {
            $suspensos++;
            $module_stats[$module_id]['suspensos']++;
          }
        }

        if ($best_grade === null || $current_numeric > $best_grade) {
          $best_grade = $current_numeric;
          $best_module = (string) $module_stats[$module_id]['codigo'];
        }
        if ($worst_grade === null || $current_numeric < $worst_grade) {
          $worst_grade = $current_numeric;
          $worst_module = (string) $module_stats[$module_id]['codigo'];
        }
      }

      if ($previous_numeric !== null) {
        $prev_notes[] = $previous_numeric;
        if ($student_has_computable_grades) {
          $group_total_valid_grades_previous++;
          $module_stats[$module_id]['prev_numeric'][] = $previous_numeric;
        }
      }

      if ($current_numeric !== null && $previous_numeric !== null) {
        $comparables++;
        if ($current_numeric > $previous_numeric) {
          $suben++;
        } elseif ($current_numeric < $previous_numeric) {
          $bajan++;
        }

        if ($previous_numeric < 5 && $current_numeric >= 5) {
          $recupera++;
          if ($student_has_computable_grades) {
            $module_stats[$module_id]['recuperan']++;
            $total_recoveries++;
          }
        }
        if ($previous_numeric >= 5 && $current_numeric < 5) {
          $cae++;
          if ($student_has_computable_grades) {
            $module_stats[$module_id]['caen']++;
            $total_falls++;
          }
        }
      }
    }

    $media = $notes !== [] ? array_sum($notes) / count($notes) : null;
    $media_prev = $prev_notes !== [] ? array_sum($prev_notes) / count($prev_notes) : null;
    $classification = $student_has_computable_grades ? classify_student($suspensos) : 'Sin módulos computables';
    if ($student_has_computable_grades) {
      $classification_counts[$classification]++;
    }

    if ($student_has_computable_grades && $suspensos === 0) {
      $group_pass_current++;
    }

    if ($student_has_computable_grades && $media !== null) {
      $group_means_current[] = $media;
    }
    if ($student_has_computable_grades && $media_prev !== null) {
      $group_means_previous[] = $media_prev;
    }

    $evolution = 'Sin evaluación anterior';
    $diff_media = null;
    if ($student_has_computable_grades && $media !== null && $media_prev !== null) {
      $students_with_comparison++;
      $diff_media = $media - $media_prev;
      if ($diff_media > 0.01) {
        $evolution = 'Mejora';
        $improve_count++;
      } elseif ($diff_media < -0.01) {
        $evolution = 'Empeora';
        $worsen_count++;
      } else {
        $evolution = 'Se mantiene';
        $same_count++;
      }
    }

    if ($previous_evaluation_id > 0) {
      $prev_suspensos = 0;
      $prev_notes_count = 0;
      foreach ($modules as $module) {
        $module_id = (int) $module['id_modulo'];
        $previous_numeric = $grades_previous[$id_alumno][$module_id]['numeric'] ?? null;
        if ($previous_numeric !== null) {
          $prev_notes_count++;
          if ($previous_numeric < 5) {
            $prev_suspensos++;
          }
        }
      }
      if ($student_has_computable_grades && $prev_suspensos === 0) {
        $group_pass_previous++;
      }
      if ($student_has_computable_grades && $prev_notes_count > 0) {
        $classification_counts_previous[classify_student($prev_suspensos)]++;
      }
    }

    if ($student_has_computable_grades) {
      $student_rows[] = [
        'id_alumno' => $id_alumno,
        'nombre' => $full_name,
        'aprobados' => $aprobados,
        'suspensos' => $suspensos,
        'no_evaluados' => $no_evaluados,
        'media' => $media,
        'clasificacion' => $classification,
        'mejor_modulo' => $best_module,
        'peor_modulo' => $worst_module,
        'evolucion' => $evolution,
        'diff_media' => $diff_media,
        'suben' => $suben,
        'bajan' => $bajan,
        'recupera' => $recupera,
        'cae' => $cae,
        'modulos_computables' => count($notes),
        'modulos_computables_prev' => count($prev_notes),
        'modulos_comparables' => $comparables,
        'incluido_en_calculos' => $student_has_computable_grades,
      ];
    }
  }

  foreach ($module_stats as $module_id => $module_stat) {
    $evaluados = $module_stat['aprobados'] + $module_stat['suspensos'];
    if ($evaluados === 0) {
      continue;
    }
    $pct_aprobados = $evaluados > 0 ? ($module_stat['aprobados'] * 100 / $evaluados) : null;
    $media_modulo = $module_stat['numeric'] !== [] ? array_sum($module_stat['numeric']) / count($module_stat['numeric']) : null;
    $max_modulo = $module_stat['numeric'] !== [] ? max($module_stat['numeric']) : null;
    $min_modulo = $module_stat['numeric'] !== [] ? min($module_stat['numeric']) : null;

    $prev_media = $module_stat['prev_numeric'] !== [] ? array_sum($module_stat['prev_numeric']) / count($module_stat['prev_numeric']) : null;
    $prev_aprobados = 0;
    foreach ($module_stat['prev_numeric'] as $prev_note) {
      if ($prev_note >= 5) {
        $prev_aprobados++;
      }
    }
    $prev_pct = $module_stat['prev_numeric'] !== [] ? ($prev_aprobados * 100 / count($module_stat['prev_numeric'])) : null;

    $module_rows[] = [
      'id_modulo' => $module_id,
      'codigo' => $module_stat['codigo'],
      'nombre' => $module_stat['nombre'],
      'aprobados' => $module_stat['aprobados'],
      'suspensos' => $module_stat['suspensos'],
      'pct_aprobados' => $pct_aprobados,
      'media' => $media_modulo,
      'max' => $max_modulo,
      'min' => $min_modulo,
      'cambio_media' => ($media_modulo !== null && $prev_media !== null) ? ($media_modulo - $prev_media) : null,
      'cambio_pct' => ($pct_aprobados !== null && $prev_pct !== null) ? ($pct_aprobados - $prev_pct) : null,
      'recuperan' => $module_stat['recuperan'],
      'caen' => $module_stat['caen'],
      'evaluados' => $evaluados,
      'evaluados_prev' => count($module_stat['prev_numeric']),
    ];
  }

  usort($module_rows, static fn (array $a, array $b): int => ($a['pct_aprobados'] ?? -1) <=> ($b['pct_aprobados'] ?? -1));

  $hardest_module = $module_rows !== [] ? $module_rows[0] : null;
  $easiest_module = $module_rows !== [] ? $module_rows[count($module_rows) - 1] : null;

  $total_students = count($students_with_computable_grades);
  $group_mean = $group_means_current !== [] ? array_sum($group_means_current) / count($group_means_current) : null;
  $group_prev_mean = $group_means_previous !== [] ? array_sum($group_means_previous) / count($group_means_previous) : null;

  $group_stats = [
    'total' => $total_students,
    'todo_aprobado' => $classification_counts['Todo aprobado'],
    'una' => $classification_counts['1 suspensa'],
    'dos' => $classification_counts['2 suspensas'],
    'tres_o_mas' => $classification_counts['3 o más suspensas'],
    'pct_todo_aprobado' => $total_students > 0 ? ($classification_counts['Todo aprobado'] * 100 / $total_students) : null,
    'pct_riesgo' => $total_students > 0 ? ($classification_counts['3 o más suspensas'] * 100 / $total_students) : null,
    'media' => $group_mean,
    'mediana' => median($group_means_current),
    'max' => $group_means_current !== [] ? max($group_means_current) : null,
    'min' => $group_means_current !== [] ? min($group_means_current) : null,
    'desviacion' => deviation($group_means_current),
    'pct_mejoran' => $students_with_comparison > 0 ? ($improve_count * 100 / $students_with_comparison) : null,
    'pct_empeoran' => $students_with_comparison > 0 ? ($worsen_count * 100 / $students_with_comparison) : null,
    'pct_mantienen' => $students_with_comparison > 0 ? ($same_count * 100 / $students_with_comparison) : null,
    'var_media' => ($group_mean !== null && $group_prev_mean !== null) ? ($group_mean - $group_prev_mean) : null,
    'var_pct_aprobados' => $total_students > 0 && $previous_evaluation_id > 0
      ? (($group_pass_current * 100 / $total_students) - ($group_pass_previous * 100 / $total_students))
      : null,
    'notas_computables_actual' => $group_total_valid_grades_current,
    'notas_computables_anterior' => $group_total_valid_grades_previous,
    'alumnos_con_comparativa' => $students_with_comparison,
  ];

  $summary_rows = [
    ['Métrica', 'Valor', 'Variación con la anterior'],
    [
      'Todo aprobado',
      fmt_count_pct((int) $group_stats['todo_aprobado'], (int) $group_stats['total']),
      $previous_evaluation_id > 0
        ? fmt_count_pct((int) $group_stats['todo_aprobado'] - (int) $classification_counts_previous['Todo aprobado'], (int) $group_stats['total'])
        : '—',
    ],
    [
      '1 suspensa',
      fmt_count_pct((int) $group_stats['una'], (int) $group_stats['total']),
      $previous_evaluation_id > 0
        ? fmt_count_pct((int) $group_stats['una'] - (int) $classification_counts_previous['1 suspensa'], (int) $group_stats['total'])
        : '—',
    ],
    [
      '2 suspensas',
      fmt_count_pct((int) $group_stats['dos'], (int) $group_stats['total']),
      $previous_evaluation_id > 0
        ? fmt_count_pct((int) $group_stats['dos'] - (int) $classification_counts_previous['2 suspensas'], (int) $group_stats['total'])
        : '—',
    ],
    [
      '3 o más suspensas',
      fmt_count_pct((int) $group_stats['tres_o_mas'], (int) $group_stats['total']),
      $previous_evaluation_id > 0
        ? fmt_count_pct((int) $group_stats['tres_o_mas'] - (int) $classification_counts_previous['3 o más suspensas'], (int) $group_stats['total'])
        : '—',
    ],
    ['% todo aprobado', fmt($group_stats['pct_todo_aprobado']) . '%', '—'],
    ['% alumnado en riesgo (3 o más)', fmt($group_stats['pct_riesgo']) . '%', '—'],
    ['Media del grupo', fmt($group_stats['media']) . ' (calculada sobre ' . $group_stats['notas_computables_actual'] . ' módulos computables)', '—'],
    ['Mediana del grupo', fmt($group_stats['mediana']) . ' (calculada sobre ' . $group_stats['total'] . ' alumnos)', '—'],
    ['Máximo del grupo', fmt($group_stats['max']) . ' (sobre ' . $group_stats['total'] . ' alumnos)', '—'],
    ['Mínimo del grupo', fmt($group_stats['min']) . ' (sobre ' . $group_stats['total'] . ' alumnos)', '—'],
    ['Desviación básica', fmt($group_stats['desviacion']), '—'],
    ['% mejora', fmt($group_stats['pct_mejoran']) . '% (sobre ' . $group_stats['alumnos_con_comparativa'] . ' alumnos con comparativa)', '—'],
    ['% empeora', fmt($group_stats['pct_empeoran']) . '% (sobre ' . $group_stats['alumnos_con_comparativa'] . ' alumnos con comparativa)', '—'],
    ['% se mantiene', fmt($group_stats['pct_mantienen']) . '% (sobre ' . $group_stats['alumnos_con_comparativa'] . ' alumnos con comparativa)', '—'],
    ['Variación media vs anterior', fmt($group_stats['var_media']) . ' (actual: ' . $group_stats['notas_computables_actual'] . ' módulos computables; anterior: ' . $group_stats['notas_computables_anterior'] . ')', '—'],
    ['Variación % todo aprobado vs anterior', fmt($group_stats['var_pct_aprobados']) . '% (sobre ' . $group_stats['total'] . ' alumnos)', '—'],
  ];

  if ($group_stats['total'] > 0) {
    $conclusions[] = 'El ' . fmt($group_stats['pct_todo_aprobado']) . '% del grupo tiene todo aprobado.';
    if ($hardest_module !== null) {
      $conclusions[] = 'El módulo con mayor porcentaje de suspensos es ' . $hardest_module['nombre'] . ' (' . $hardest_module['codigo'] . ') (' . fmt(100 - (float) $hardest_module['pct_aprobados']) . '% de suspensos sobre ' . $hardest_module['evaluados'] . ' módulos computables).';
    }
    if ($easiest_module !== null) {
      $conclusions[] = 'El módulo con mayor porcentaje de aprobados es ' . $easiest_module['nombre'] . ' (' . $easiest_module['codigo'] . ') (' . fmt($easiest_module['pct_aprobados']) . '% de aprobados sobre ' . $easiest_module['evaluados'] . ' módulos computables).';
    }
    $conclusions[] = 'Hay ' . $group_stats['tres_o_mas'] . ' alumnos en riesgo (3 o más suspensas).';
    $conclusions[] = 'Se observan ' . $total_recoveries . ' recuperaciones de módulo y ' . $worsen_count . ' alumnos que empeoran su media.';
    if ($previous_evaluation_id > 0 && $group_stats['var_media'] !== null) {
      $trend = $group_stats['var_media'] > 0.01 ? 'mejora' : ($group_stats['var_media'] < -0.01 ? 'empeora' : 'se mantiene');
      $conclusions[] = 'Respecto a la evaluación anterior, la media del grupo ' . $trend . ' (' . fmt($group_stats['var_media']) . ', sobre ' . $group_stats['notas_computables_actual'] . ' módulos computables en la evaluación actual).';
    }
  }

  $grade_histogram = array_fill(0, 10, 0);
  foreach ($grades_current as $id_alumno => $module_grades) {
    if (!isset($students_with_computable_grades[$id_alumno])) {
      continue;
    }
    foreach ($module_grades as $grade_data) {
      $n_val = $grade_data['numeric'];
      if ($n_val === null) {
        continue;
      }
      $bin = min(9, (int) floor($n_val));
      if ($bin < 0) {
        $bin = 0;
      }
      $grade_histogram[$bin]++;
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
          <h1>Análisis de calificaciones</h1>
          <p class="subheading">Métricas individuales, globales y por módulo a partir de la evaluación seleccionada.</p>
        </div>
        <div class="header-actions">
          <?php if ($show_results): ?>
            <a class="primary-button" href="includes/analisis_exportar.php?<?php echo htmlspecialchars(http_build_query([
              'id_curso_escolar' => $selected_course_id,
              'id_grupo'         => $selected_group,
              'id_evaluacion'    => $selected_evaluation,
              'formato'          => 'excel',
            ]), ENT_QUOTES, 'UTF-8'); ?>">Exportar Excel</a>
            <a class="primary-button" href="includes/analisis_exportar.php?<?php echo htmlspecialchars(http_build_query([
              'id_curso_escolar' => $selected_course_id,
              'id_grupo'         => $selected_group,
              'id_evaluacion'    => $selected_evaluation,
              'formato'          => 'pdf',
            ]), ENT_QUOTES, 'UTF-8'); ?>">Exportar PDF</a>
          <?php endif; ?>
          <a class="ghost-button" href="calificaciones.php?<?php echo htmlspecialchars(http_build_query([
            'id_grupo' => $selected_group,
            'id_evaluacion' => $selected_evaluation,
          ]), ENT_QUOTES, 'UTF-8'); ?>">Volver a calificaciones</a>
        </div>
      </header>

      <form class="topbar" method="get">
        <div class="topbar-actions entity-grid entity-grid--4">
          <label class="calendar-select">
            <select name="id_curso_escolar" onchange="this.form.submit()" aria-label="Curso escolar">
              <option value="">Selecciona curso escolar</option>
              <?php foreach ($courses as $course): ?>
                <option value="<?php echo (int) $course['id_curso_escolar']; ?>" <?php echo (int) $course['id_curso_escolar'] === $selected_course_id ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($course['curso_escolar'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </label>

          <label class="calendar-select">
            <select name="id_grupo" onchange="this.form.submit()" aria-label="Grupo">
              <option value="">Selecciona grupo</option>
              <?php foreach ($groups as $group): ?>
                <option value="<?php echo (int) $group['id_grupo']; ?>" <?php echo (string) $group['id_grupo'] === $selected_group ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($group['grupo'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </label>

          <label class="calendar-select">
            <select name="id_evaluacion" onchange="this.form.submit()" aria-label="Evaluación">
              <?php foreach ($evaluations as $evaluation): ?>
                <option value="<?php echo (int) $evaluation['id_evaluacion']; ?>" <?php echo (int) $evaluation['id_evaluacion'] === $selected_evaluation ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($evaluation['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </label>

          <div class="topbar-search">
            <span class="search-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="7"></circle>
                <line x1="16.65" y1="16.65" x2="21" y2="21"></line>
              </svg>
            </span>
            <input type="search" name="q" placeholder="Buscar por nombre o apellidos" aria-label="Buscar por nombre o apellidos" value="<?php echo htmlspecialchars($search_term, ENT_QUOTES, 'UTF-8'); ?>">
          </div>
        </div>
      </form>

      <?php if (!$show_results): ?>
        <section class="panel">
          <div class="panel-header">
            <h3>Selecciona curso, grupo y evaluación</h3>
            <p>El análisis se mostrará cuando el contexto mínimo esté definido.</p>
          </div>
        </section>
      <?php else: ?>
        <?php
          $selected_group_name = trim((string) ($groups_by_id[(int) $selected_group] ?? ''));
          if ($selected_group_name === '') {
            $selected_group_name = 'Sin grupo';
          }
          $selected_evaluation_name = trim((string) ($evaluation_name_by_id[$selected_evaluation] ?? ''));
          $previous_evaluation_name = $previous_evaluation_id > 0
            ? trim((string) ($evaluation_name_by_id[$previous_evaluation_id] ?? ('Evaluación ' . $previous_evaluation_id)))
            : 'No disponible';
        ?>

        <section class="panel">
          <div class="panel-header">
            <h3>Contexto del análisis</h3>
            <p>Grupo <?php echo htmlspecialchars($selected_group_name, ENT_QUOTES, 'UTF-8'); ?> · Evaluación base: <?php echo htmlspecialchars($selected_evaluation_name, ENT_QUOTES, 'UTF-8'); ?> · Evaluación anterior: <?php echo htmlspecialchars($previous_evaluation_name, ENT_QUOTES, 'UTF-8'); ?></p>
          </div>
        </section>

        <section class="db-kpis">
          <?php
            $ca_pct_aprobado = (float) ($group_stats['pct_todo_aprobado'] ?? 0);
            $ca_kpi_aprobado = $ca_pct_aprobado >= 75 ? 'green' : ($ca_pct_aprobado >= 50 ? 'amber' : 'orange');
            $ca_var = $group_stats['var_media'] ?? null;
            $ca_kpi_var = ($ca_var !== null && $ca_var > 0.01) ? 'green' : (($ca_var !== null && $ca_var < -0.01) ? 'orange' : 'teal');
            $ca_var_arrow = ($ca_var !== null && $ca_var > 0.01) ? '&#8679;' : (($ca_var !== null && $ca_var < -0.01) ? '&#8681;' : '&#8596;');
            $ca_riesgo = (int) ($group_stats['tres_o_mas'] ?? 0);
          ?>
          <article class="db-kpi db-kpi--<?php echo $ca_kpi_aprobado; ?>">
            <div class="db-kpi-icon">&#9989;</div>
            <div class="db-kpi-body">
              <div class="db-kpi-value"><?php echo fmt($group_stats['pct_todo_aprobado']); ?>%</div>
              <div class="db-kpi-label">Todo aprobado</div>
            </div>
          </article>
          <article class="db-kpi db-kpi--blue">
            <div class="db-kpi-icon">&#128202;</div>
            <div class="db-kpi-body">
              <div class="db-kpi-value"><?php echo fmt($group_stats['media']); ?></div>
              <div class="db-kpi-label">Media del grupo</div>
            </div>
          </article>
          <article class="db-kpi db-kpi--indigo">
            <div class="db-kpi-icon">&#128101;</div>
            <div class="db-kpi-body">
              <div class="db-kpi-value"><?php echo (int) ($group_stats['total'] ?? 0); ?></div>
              <div class="db-kpi-label">Alumnos evaluados</div>
            </div>
          </article>
          <article class="db-kpi db-kpi--<?php echo $ca_riesgo > 0 ? 'pink' : 'green'; ?>">
            <div class="db-kpi-icon">&#9888;&#65039;</div>
            <div class="db-kpi-body">
              <div class="db-kpi-value"><?php echo $ca_riesgo; ?></div>
              <div class="db-kpi-label">En riesgo (3+ suspensos)</div>
            </div>
          </article>
          <article class="db-kpi db-kpi--<?php echo $ca_kpi_var; ?>">
            <div class="db-kpi-icon"><?php echo $ca_var_arrow; ?></div>
            <div class="db-kpi-body">
              <div class="db-kpi-value"><?php echo fmt($group_stats['var_media']); ?></div>
              <div class="db-kpi-label">Variación media vs anterior</div>
            </div>
          </article>
          <article class="db-kpi db-kpi--teal">
            <div class="db-kpi-icon">&#127919;</div>
            <div class="db-kpi-body">
              <div class="db-kpi-value"><?php echo fmt($group_stats['mediana']); ?></div>
              <div class="db-kpi-label">Mediana del grupo</div>
            </div>
          </article>
          <article class="db-kpi db-kpi--amber">
            <div class="db-kpi-icon">&#128200;</div>
            <div class="db-kpi-body">
              <div class="db-kpi-value"><?php echo fmt($group_stats['pct_mejoran']); ?>%</div>
              <div class="db-kpi-label">Alumnos que mejoran</div>
            </div>
          </article>
          <article class="db-kpi db-kpi--purple">
            <div class="db-kpi-icon">&#128201;</div>
            <div class="db-kpi-body">
              <div class="db-kpi-value"><?php echo fmt($group_stats['desviacion']); ?></div>
              <div class="db-kpi-label">Desviación típica</div>
            </div>
          </article>
        </section>

        <section class="panel">
          <div class="panel-header">
            <h3>Conclusiones automáticas</h3>
          </div>
          <div class="panel-grid">
            <table>
              <tbody>
                <?php if ($conclusions === []): ?>
                  <tr><td>No hay datos suficientes para generar conclusiones.</td></tr>
                <?php else: ?>
                  <?php foreach ($conclusions as $conclusion): ?>
                    <tr><td><?php echo htmlspecialchars($conclusion, ENT_QUOTES, 'UTF-8'); ?></td></tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>

        <section class="panel">
          <div class="panel-header">
            <h3>Métricas globales del grupo</h3>
          </div>
          <div class="panel-grid">
            <table>
              <tbody>
                <?php foreach ($summary_rows as $index => $summary_row): ?>
                  <?php if ($index === 0): ?>
                    <tr>
                      <th><?php echo htmlspecialchars((string) $summary_row[0], ENT_QUOTES, 'UTF-8'); ?></th>
                      <th><?php echo htmlspecialchars((string) $summary_row[1], ENT_QUOTES, 'UTF-8'); ?></th>
                      <th><?php echo htmlspecialchars((string) $summary_row[2], ENT_QUOTES, 'UTF-8'); ?></th>
                    </tr>
                  <?php else: ?>
                    <tr>
                      <td><?php echo htmlspecialchars((string) $summary_row[0], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars((string) $summary_row[1], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars((string) $summary_row[2], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                  <?php endif; ?>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </section>

        <section class="panel">
          <div class="panel-header">
            <h3>Métricas individuales por alumno</h3>
          </div>
          <div class="panel-grid">
            <table>
              <thead>
                <tr>
                  <th>Alumno</th>
                  <th>Aprobados</th>
                  <th>Suspensos</th>
                  <th>Media</th>
                  <th>Clasificación</th>
                  <th>Mejor módulo</th>
                  <th>Peor módulo</th>
                  <th>Evolución</th>
                  <th>Dif. media</th>
                  <th>Suben</th>
                  <th>Bajan</th>
                  <th>Recuperan</th>
                  <th>Caen</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($student_rows === []): ?>
                  <tr>
                    <td colspan="13">No hay alumnado para el contexto seleccionado.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($student_rows as $student_row): ?>
                    <tr>
                      <td>
                        <a class="practice-link" href="alumno_detalle.php?id_alumno=<?php echo (int) $student_row['id_alumno']; ?>"><?php echo htmlspecialchars((string) $student_row['nombre'], ENT_QUOTES, 'UTF-8'); ?></a>
                        <div class="brand-subtitle"><?php echo 'sobre ' . (int) $student_row['modulos_computables'] . ' módulos'; ?></div>
                      </td>
                      <td><?php echo (int) $student_row['aprobados']; ?></td>
                      <td><?php echo (int) $student_row['suspensos']; ?></td>
                      <td><?php echo htmlspecialchars(fmt($student_row['media']), ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars((string) $student_row['clasificacion'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars((string) $student_row['mejor_modulo'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars((string) $student_row['peor_modulo'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars((string) $student_row['evolucion'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars(fmt($student_row['diff_media']), ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo (int) $student_row['suben']; ?></td>
                      <td><?php echo (int) $student_row['bajan']; ?></td>
                      <td><?php echo (int) $student_row['recupera']; ?></td>
                      <td><?php echo (int) $student_row['cae']; ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>

        <section class="panel">
          <div class="panel-header">
            <h3>Métricas por módulo</h3>
          </div>
          <div class="panel-grid">
            <table>
              <thead>
                <tr>
                  <th>Módulo</th>
                  <th>Aprobados</th>
                  <th>Suspensos</th>
                  <th>% Aprobados</th>
                  <th>Media</th>
                  <th>Máx</th>
                  <th>Mín</th>
                  <th>Cambio media</th>
                  <th>Cambio % aprobados</th>
                  <th>Recuperan</th>
                  <th>Caen</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($module_rows === []): ?>
                  <tr>
                    <td colspan="11">No hay módulos para el contexto seleccionado.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($module_rows as $module_row): ?>
                    <tr>
                      <td>
                        <?php echo htmlspecialchars((string) $module_row['codigo'], ENT_QUOTES, 'UTF-8'); ?><?php echo $module_row['nombre'] !== '' ? ' · ' . htmlspecialchars((string) $module_row['nombre'], ENT_QUOTES, 'UTF-8') : ''; ?>
                        <div class="brand-subtitle"><?php echo 'sobre ' . (int) $module_row['evaluados'] . ' alumnos'; ?></div>
                      </td>
                      <td><?php echo (int) $module_row['aprobados']; ?></td>
                      <td><?php echo (int) $module_row['suspensos']; ?></td>
                      <td><?php echo htmlspecialchars(fmt($module_row['pct_aprobados']) . '%', ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars(fmt($module_row['media']), ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars(fmt($module_row['max']), ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars(fmt($module_row['min']), ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars(fmt($module_row['cambio_media']), ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars(fmt($module_row['cambio_pct']) . '%', ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo (int) $module_row['recuperan']; ?></td>
                      <td><?php echo (int) $module_row['caen']; ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>

        <div class="ca-charts-grid">
        <?php
          echo render_donut_chart('Distribución del alumnado por suspensos acumulados', [
            ['label' => 'Todo aprobado', 'value' => (int) ($group_stats['todo_aprobado'] ?? 0), 'color' => '#10b981'],
            ['label' => '1 suspensa',    'value' => (int) ($group_stats['una'] ?? 0),            'color' => '#f59e0b'],
            ['label' => '2 suspensas',   'value' => (int) ($group_stats['dos'] ?? 0),            'color' => '#f97316'],
            ['label' => '3 o más',       'value' => (int) ($group_stats['tres_o_mas'] ?? 0),     'color' => '#ef4444'],
          ]);

          if (array_sum($grade_histogram) > 0) {
            echo render_grade_histogram('Distribución de notas individuales (por tramos)', $grade_histogram);
          }

          $module_labels   = [];
          $module_values   = [];
          $module_tooltips = [];
          foreach ($module_rows as $module_row) {
            $module_labels[]   = (string) $module_row['codigo'];
            $module_values[]   = (float) ($module_row['pct_aprobados'] ?? 0.0);
            $module_tooltips[] = (string) $module_row['nombre'];
          }
          if ($module_labels !== []) {
            echo render_pass_rate_chart('% de aprobados por módulo', $module_labels, $module_values, $module_tooltips);
          }

          $module_media_tmp = [];
          foreach ($module_rows as $module_row) {
            if ($module_row['media'] !== null) {
              $module_media_tmp[] = [
                'label'   => (string) $module_row['codigo'],
                'value'   => (float) $module_row['media'],
                'tooltip' => (string) $module_row['nombre'],
              ];
            }
          }
          usort($module_media_tmp, static fn (array $a, array $b): int => $a['value'] <=> $b['value']);
          $module_media_labels   = array_column($module_media_tmp, 'label');
          $module_media_values   = array_column($module_media_tmp, 'value');
          $module_media_tooltips = array_column($module_media_tmp, 'tooltip');
          if ($module_media_labels !== []) {
            echo render_bar_chart('Media por módulo', $module_media_labels, $module_media_values, $module_media_tooltips, 5.0);
          }

          if (count($group_mean_history) >= 2) {
            $history_labels = [];
            $history_values = [];
            foreach ($group_mean_history as $point) {
              $history_labels[] = (string) $point['label'];
              $history_values[] = (float) $point['value'];
            }
            echo render_bar_chart('Evolución de la media del grupo por evaluación', $history_labels, $history_values);
          }
        ?>
        </div>
      <?php endif; ?>
    </main>
  </div>

  <script>
    (function () {
      const form = document.querySelector('.topbar');
      const searchInput = form ? form.querySelector('input[name="q"]') : null;
      let searchDebounceTimer = null;

      if (!form || !searchInput) {
        return;
      }

      searchInput.addEventListener('input', () => {
        if (searchDebounceTimer) {
          window.clearTimeout(searchDebounceTimer);
        }

        searchDebounceTimer = window.setTimeout(() => {
          form.submit();
        }, 250);
      });
    })();

    (function () {
      var tip = document.createElement('div');
      tip.className = 'ca-bar-tooltip';
      document.body.appendChild(tip);

      function showTip(name, x, y) {
        tip.textContent = name;
        tip.style.left = (x + 14) + 'px';
        tip.style.top  = (y - 10) + 'px';
        tip.classList.add('ca-bar-tooltip--visible');
      }

      function hideTip() {
        tip.classList.remove('ca-bar-tooltip--visible');
      }

      document.querySelectorAll('.ca-bar-group').forEach(function (g) {
        var name = g.dataset.name;
        if (!name) { return; }

        g.addEventListener('mouseenter', function (e) {
          showTip(name, e.clientX, e.clientY);
        });

        g.addEventListener('mousemove', function (e) {
          tip.style.left = (e.clientX + 14) + 'px';
          tip.style.top  = (e.clientY - 10) + 'px';
        });

        g.addEventListener('mouseleave', hideTip);

        g.addEventListener('click', function (e) {
          showTip(name, e.clientX, e.clientY);
        });
      });
    })();
  </script>
</body>
</html>
