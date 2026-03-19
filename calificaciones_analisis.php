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

function render_bar_chart(string $title, array $labels, array $values): string
{
  $max = 0.0;
  foreach ($values as $value) {
    $max = max($max, (float) $value);
  }

  $bar_width = 70;
  $gap = 22;
  $chart_height = 210;
  $base_y = 165;
  $svg_width = max(320, count($values) * ($bar_width + $gap) + 40);

  ob_start();
  ?>
  <section class="panel">
    <div class="panel-header">
      <h3><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h3>
    </div>
    <div class="panel-grid">
      <svg viewBox="0 0 <?php echo $svg_width; ?> <?php echo $chart_height; ?>" width="100%" height="<?php echo $chart_height; ?>" role="img" aria-label="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>">
        <line x1="20" y1="<?php echo $base_y; ?>" x2="<?php echo $svg_width - 20; ?>" y2="<?php echo $base_y; ?>" stroke="currentColor" stroke-width="1"></line>
        <?php foreach ($values as $index => $value): ?>
          <?php
            $x = 30 + $index * ($bar_width + $gap);
            $height = $max > 0 ? (int) round(((float) $value / $max) * 120) : 0;
            $y = $base_y - $height;
            $label = (string) ($labels[$index] ?? '');
          ?>
          <rect x="<?php echo $x; ?>" y="<?php echo $y; ?>" width="<?php echo $bar_width; ?>" height="<?php echo $height; ?>" rx="8" ry="8" fill="#4f46e5"></rect>
          <text x="<?php echo $x + intdiv($bar_width, 2); ?>" y="<?php echo $y - 8; ?>" text-anchor="middle" font-size="12" fill="currentColor"><?php echo htmlspecialchars((string) round((float) $value, 2), ENT_QUOTES, 'UTF-8'); ?></text>
          <text x="<?php echo $x + intdiv($bar_width, 2); ?>" y="188" text-anchor="middle" font-size="11" fill="currentColor"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></text>
        <?php endforeach; ?>
      </svg>
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

$selected_group = (string) ($_GET['id_grupo'] ?? '');
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

  if ($evaluation_ids_in_context !== [] && $modules !== []) {
    $history_stmt = $pdo->prepare(
      'SELECT c.id_evaluacion, AVG(c.nota) AS media
       FROM calificaciones c
       INNER JOIN modulos m ON m.id_modulo = c.id_modulo
       WHERE c.id_curso_escolar = :id_curso_escolar
         AND c.id_grupo = :id_grupo
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
    $history_stmt->execute([
      'id_curso_escolar' => $selected_course_id,
      'id_grupo' => (int) $selected_group,
      'id_curso' => $selected_group_course_id,
    ]);
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

    foreach ($modules as $module) {
      $module_id = (int) $module['id_modulo'];
      $current_numeric = $grades_current[$id_alumno][$module_id]['numeric'] ?? null;
      $previous_numeric = $grades_previous[$id_alumno][$module_id]['numeric'] ?? null;

      if ($current_numeric === null) {
        $no_evaluados++;
        $module_stats[$module_id]['no_evaluados']++;
      } else {
        $notes[] = $current_numeric;
        $group_total_valid_grades_current++;
        $module_stats[$module_id]['numeric'][] = $current_numeric;
        if ($current_numeric >= 5) {
          $aprobados++;
          $module_stats[$module_id]['aprobados']++;
        } else {
          $suspensos++;
          $module_stats[$module_id]['suspensos']++;
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
        $group_total_valid_grades_previous++;
        $module_stats[$module_id]['prev_numeric'][] = $previous_numeric;
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
          $module_stats[$module_id]['recuperan']++;
          $total_recoveries++;
        }
        if ($previous_numeric >= 5 && $current_numeric < 5) {
          $cae++;
          $module_stats[$module_id]['caen']++;
          $total_falls++;
        }
      }
    }

    $media = $notes !== [] ? array_sum($notes) / count($notes) : null;
    $media_prev = $prev_notes !== [] ? array_sum($prev_notes) / count($prev_notes) : null;
    $classification = classify_student($suspensos);
    $classification_counts[$classification]++;

    if ($suspensos === 0) {
      $group_pass_current++;
    }

    if ($media !== null) {
      $group_means_current[] = $media;
    }
    if ($media_prev !== null) {
      $group_means_previous[] = $media_prev;
    }

    $evolution = 'Sin evaluación anterior';
    $diff_media = null;
    if ($media !== null && $media_prev !== null) {
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
      foreach ($modules as $module) {
        $module_id = (int) $module['id_modulo'];
        $previous_numeric = $grades_previous[$id_alumno][$module_id]['numeric'] ?? null;
        if ($previous_numeric !== null && $previous_numeric < 5) {
          $prev_suspensos++;
        }
      }
      if ($prev_suspensos === 0) {
        $group_pass_previous++;
      }
    }

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
    ];
  }

  foreach ($module_stats as $module_id => $module_stat) {
    $evaluados = $module_stat['aprobados'] + $module_stat['suspensos'];
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

  $total_students = count($student_rows);
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
    ['Métrica', 'Valor'],
    ['Nº alumnos con todo aprobado', (string) $group_stats['todo_aprobado']],
    ['Nº alumnos con 1 suspensa', (string) $group_stats['una']],
    ['Nº alumnos con 2 suspensas', (string) $group_stats['dos']],
    ['Nº alumnos con 3 o más suspensas', (string) $group_stats['tres_o_mas']],
    ['% todo aprobado', fmt($group_stats['pct_todo_aprobado']) . '%'],
    ['% alumnado en riesgo (3 o más)', fmt($group_stats['pct_riesgo']) . '%'],
    ['Media del grupo', fmt($group_stats['media']) . ' (calculada sobre ' . $group_stats['notas_computables_actual'] . ' módulos computables)'],
    ['Mediana del grupo', fmt($group_stats['mediana']) . ' (calculada sobre ' . $group_stats['total'] . ' alumnos)'],
    ['Máximo del grupo', fmt($group_stats['max']) . ' (sobre ' . $group_stats['total'] . ' alumnos)'],
    ['Mínimo del grupo', fmt($group_stats['min']) . ' (sobre ' . $group_stats['total'] . ' alumnos)'],
    ['Desviación básica', fmt($group_stats['desviacion'])],
    ['% mejora', fmt($group_stats['pct_mejoran']) . '% (sobre ' . $group_stats['alumnos_con_comparativa'] . ' alumnos con comparativa)'],
    ['% empeora', fmt($group_stats['pct_empeoran']) . '% (sobre ' . $group_stats['alumnos_con_comparativa'] . ' alumnos con comparativa)'],
    ['% se mantiene', fmt($group_stats['pct_mantienen']) . '% (sobre ' . $group_stats['alumnos_con_comparativa'] . ' alumnos con comparativa)'],
    ['Variación media vs anterior', fmt($group_stats['var_media']) . ' (actual: ' . $group_stats['notas_computables_actual'] . ' módulos computables; anterior: ' . $group_stats['notas_computables_anterior'] . ')'],
    ['Variación % todo aprobado vs anterior', fmt($group_stats['var_pct_aprobados']) . '% (sobre ' . $group_stats['total'] . ' alumnos)'],
  ];

  if ($group_stats['total'] > 0) {
    $conclusions[] = 'El ' . fmt($group_stats['pct_todo_aprobado']) . '% del grupo tiene todo aprobado.';
    if ($hardest_module !== null) {
      $conclusions[] = 'El módulo más difícil es ' . $hardest_module['codigo'] . ' (' . fmt($hardest_module['pct_aprobados']) . '% de aprobados sobre ' . $hardest_module['evaluados'] . ' módulos computables).';
    }
    if ($easiest_module !== null) {
      $conclusions[] = 'El módulo más fácil es ' . $easiest_module['codigo'] . ' (' . fmt($easiest_module['pct_aprobados']) . '% de aprobados sobre ' . $easiest_module['evaluados'] . ' módulos computables).';
    }
    $conclusions[] = 'Hay ' . $group_stats['tres_o_mas'] . ' alumnos en riesgo (3 o más suspensas).';
    $conclusions[] = 'Se observan ' . $total_recoveries . ' recuperaciones de módulo y ' . $worsen_count . ' alumnos que empeoran su media.';
    if ($previous_evaluation_id > 0 && $group_stats['var_media'] !== null) {
      $trend = $group_stats['var_media'] > 0.01 ? 'mejora' : ($group_stats['var_media'] < -0.01 ? 'empeora' : 'se mantiene');
      $conclusions[] = 'Respecto a la evaluación anterior, la media del grupo ' . $trend . ' (' . fmt($group_stats['var_media']) . ', sobre ' . $group_stats['notas_computables_actual'] . ' módulos computables en la evaluación actual).';
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
      </header>

      <form class="topbar" method="get">
        <div class="topbar-actions entity-grid entity-grid--4">
          <label class="calendar-select">
            <select name="id_curso_escolar" onchange="this.form.submit()" aria-label="Curso escolar">
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
                    </tr>
                  <?php else: ?>
                    <tr>
                      <td><?php echo htmlspecialchars((string) $summary_row[0], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars((string) $summary_row[1], ENT_QUOTES, 'UTF-8'); ?></td>
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
                  <th>No evaluados</th>
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
                    <td colspan="14">No hay alumnado para el contexto seleccionado.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($student_rows as $student_row): ?>
                    <tr>
                      <td><a class="practice-link" href="alumno_detalle.php?id_alumno=<?php echo (int) $student_row['id_alumno']; ?>"><?php echo htmlspecialchars((string) $student_row['nombre'], ENT_QUOTES, 'UTF-8'); ?></a></td>
                      <td><?php echo (int) $student_row['aprobados']; ?></td>
                      <td><?php echo (int) $student_row['suspensos']; ?></td>
                      <td><?php echo (int) $student_row['no_evaluados']; ?></td>
                      <td><?php echo htmlspecialchars(fmt($student_row['media']) . ' (sobre ' . (int) $student_row['modulos_computables'] . ' módulos)', ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars((string) $student_row['clasificacion'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars((string) $student_row['mejor_modulo'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars((string) $student_row['peor_modulo'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars((string) $student_row['evolucion'] . ' (sobre ' . (int) $student_row['modulos_comparables'] . ' módulos)', ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars(fmt($student_row['diff_media']) . ' (sobre ' . (int) $student_row['modulos_comparables'] . ' módulos)', ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo (int) $student_row['suben']; ?><?php echo ' (sobre ' . (int) $student_row['modulos_comparables'] . ')'; ?></td>
                      <td><?php echo (int) $student_row['bajan']; ?><?php echo ' (sobre ' . (int) $student_row['modulos_comparables'] . ')'; ?></td>
                      <td><?php echo (int) $student_row['recupera']; ?><?php echo ' (sobre ' . (int) $student_row['modulos_comparables'] . ')'; ?></td>
                      <td><?php echo (int) $student_row['cae']; ?><?php echo ' (sobre ' . (int) $student_row['modulos_comparables'] . ')'; ?></td>
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
                      <td><?php echo htmlspecialchars((string) $module_row['codigo'], ENT_QUOTES, 'UTF-8'); ?><?php echo $module_row['nombre'] !== '' ? ' · ' . htmlspecialchars((string) $module_row['nombre'], ENT_QUOTES, 'UTF-8') : ''; ?></td>
                      <td><?php echo (int) $module_row['aprobados']; ?><?php echo ' (sobre ' . (int) $module_row['evaluados'] . ')'; ?></td>
                      <td><?php echo (int) $module_row['suspensos']; ?><?php echo ' (sobre ' . (int) $module_row['evaluados'] . ')'; ?></td>
                      <td><?php echo htmlspecialchars(fmt($module_row['pct_aprobados']) . '% (sobre ' . (int) $module_row['evaluados'] . ' módulos)', ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars(fmt($module_row['media']) . ' (sobre ' . (int) $module_row['evaluados'] . ' módulos)', ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars(fmt($module_row['max']), ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars(fmt($module_row['min']), ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars(fmt($module_row['cambio_media']) . ' (sobre ' . (int) min((int) $module_row['evaluados'], (int) $module_row['evaluados_prev']) . ' módulos)', ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars(fmt($module_row['cambio_pct']) . '% (sobre ' . (int) min((int) $module_row['evaluados'], (int) $module_row['evaluados_prev']) . ' módulos)', ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo (int) $module_row['recuperan']; ?><?php echo ' (sobre ' . (int) min((int) $module_row['evaluados'], (int) $module_row['evaluados_prev']) . ')'; ?></td>
                      <td><?php echo (int) $module_row['caen']; ?><?php echo ' (sobre ' . (int) min((int) $module_row['evaluados'], (int) $module_row['evaluados_prev']) . ')'; ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>

        <?php
          $distribution_labels = ['0', '1', '2', '3+'];
          $distribution_values = [
            (float) ($group_stats['todo_aprobado'] ?? 0),
            (float) ($group_stats['una'] ?? 0),
            (float) ($group_stats['dos'] ?? 0),
            (float) ($group_stats['tres_o_mas'] ?? 0),
          ];
          echo render_bar_chart('Distribución de suspensos por alumno', $distribution_labels, $distribution_values);

          $module_labels = [];
          $module_values = [];
          foreach ($module_rows as $module_row) {
            $module_labels[] = (string) $module_row['codigo'];
            $module_values[] = (float) ($module_row['pct_aprobados'] ?? 0.0);
          }
          if ($module_labels !== []) {
            echo render_bar_chart('% de aprobados por módulo', $module_labels, $module_values);
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
  </script>
</body>
</html>
