<?php
declare(strict_types=1);

require_once __DIR__ . '/../db.php';

function ae_grade_display(?string $original, $nota): string
{
    $cal = trim((string) ($original ?? ''));
    if ($cal !== '') {
        return $cal;
    }
    if ($nota !== null && $nota !== '') {
        return rtrim(rtrim(number_format((float) $nota, 2, '.', ''), '0'), '.');
    }
    return '—';
}

function ae_grade_numeric(?string $original, $nota): ?float
{
    $cal = trim((string) ($original ?? ''));
    if ($cal !== '' && preg_match('/^(TC|CV)\b/i', $cal)) {
        return null;
    }
    if ($nota !== null && $nota !== '') {
        return (float) $nota;
    }
    if ($cal === '') {
        return null;
    }
    if (preg_match('/^([A-Za-z]{1,10})-([0-9]+(?:[\.,][0-9]+)?)$/', $cal, $m)) {
        return (float) str_replace(',', '.', $m[2]);
    }
    if (preg_match('/^[0-9]+(?:[\.,][0-9]+)?$/', $cal)) {
        return (float) str_replace(',', '.', $cal);
    }
    return null;
}

function ae_classify(int $suspensos): string
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

function ae_fmt(?float $v, int $decimals = 2): string
{
    if ($v === null) {
        return '—';
    }
    return number_format($v, $decimals, ',', '.');
}

function ae_h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

$pdo = db();
$formato = ($_GET['formato'] ?? 'excel') === 'pdf' ? 'pdf' : 'excel';

$active_course_id = (int) ($pdo->query(
    'SELECT id_curso_escolar FROM cursos_escolares WHERE activo = 1 ORDER BY id_curso_escolar DESC LIMIT 1'
)->fetchColumn() ?: 0);

$selected_course_id = isset($_GET['id_curso_escolar']) && ctype_digit((string) $_GET['id_curso_escolar'])
    ? (int) $_GET['id_curso_escolar']
    : $active_course_id;

$selected_group = isset($_GET['id_grupo']) && ctype_digit((string) $_GET['id_grupo'])
    ? (string) $_GET['id_grupo']
    : '';

$selected_evaluation = isset($_GET['id_evaluacion']) && ctype_digit((string) $_GET['id_evaluacion'])
    ? (int) $_GET['id_evaluacion']
    : 0;

if ($selected_group === '' || $selected_evaluation <= 0) {
    http_response_code(400);
    exit('Parámetros insuficientes.');
}

$group_stmt = $pdo->prepare('SELECT grupo FROM grupos WHERE id_grupo = :id');
$group_stmt->execute(['id' => (int) $selected_group]);
$group_name = (string) ($group_stmt->fetchColumn() ?: '');

$course_stmt = $pdo->prepare('SELECT curso_escolar FROM cursos_escolares WHERE id_curso_escolar = :id');
$course_stmt->execute(['id' => $selected_course_id]);
$course_name = (string) ($course_stmt->fetchColumn() ?: '');

$all_evals = $pdo->query('SELECT id_evaluacion, nombre FROM evaluaciones ORDER BY id_evaluacion')->fetchAll();
$export_evaluations = [];
foreach ($all_evals as $ev) {
    if ((int) $ev['id_evaluacion'] <= $selected_evaluation) {
        $export_evaluations[] = $ev;
    }
}
if ($export_evaluations === []) {
    http_response_code(400);
    exit('No hay evaluaciones para exportar.');
}

$export_eval_ids = array_map(static fn (array $e): int => (int) $e['id_evaluacion'], $export_evaluations);
$eval_name_by_id = [];
foreach ($export_evaluations as $ev) {
    $eval_name_by_id[(int) $ev['id_evaluacion']] = (string) $ev['nombre'];
}

$group_course_stmt = $pdo->prepare(
    'SELECT ac.id_curso FROM alumno_curso ac
     WHERE ac.id_curso_escolar = :ce AND ac.id_grupo = :g
     GROUP BY ac.id_curso ORDER BY ac.id_curso LIMIT 1'
);
$group_course_stmt->execute(['ce' => $selected_course_id, 'g' => (int) $selected_group]);
$selected_group_course_id = (int) ($group_course_stmt->fetchColumn() ?: 0);

$students_stmt = $pdo->prepare(
    'SELECT a.id_alumno, a.apellido1, a.apellido2, a.nombre
     FROM alumno_curso ac
     INNER JOIN alumnos a ON a.id_alumno = ac.id_alumno
     WHERE ac.id_curso_escolar = :ce AND ac.id_grupo = :g
     ORDER BY a.apellido1, a.apellido2, a.nombre'
);
$students_stmt->execute(['ce' => $selected_course_id, 'g' => (int) $selected_group]);
$students = $students_stmt->fetchAll();

$modules_stmt = $pdo->prepare(
    'SELECT DISTINCT m.id_modulo, m.id_ciclo, m.id_curso, m.codigo, m.abreviatura, m.materia_general, m.materia_propia
     FROM modulos m
     INNER JOIN alumno_curso ac ON ac.id_ciclo = m.id_ciclo AND ac.id_curso = m.id_curso
     WHERE ac.id_curso_escolar = :ce AND ac.id_grupo = :g
       AND m.id_curso = :ic AND COALESCE(m.tipo, "") <> "FFE"
     ORDER BY m.id_ciclo, m.id_curso, m.codigo, m.id_modulo'
);
$modules_stmt->execute([
    'ce' => $selected_course_id,
    'g'  => (int) $selected_group,
    'ic' => $selected_group_course_id,
]);
$modules = $modules_stmt->fetchAll();

$eval_placeholders = implode(', ', array_fill(0, count($export_eval_ids), '?'));
$grades_stmt = $pdo->prepare(
    'SELECT id_alumno, id_modulo, id_evaluacion, calificacion_original, nota
     FROM calificaciones
     WHERE id_curso_escolar = ? AND id_grupo = ?
       AND id_evaluacion IN (' . $eval_placeholders . ')'
);
$grades_stmt->execute(array_merge([$selected_course_id, (int) $selected_group], $export_eval_ids));
$all_grades = $grades_stmt->fetchAll();

$grades_by_eval = [];
foreach ($all_grades as $gr) {
    $eid = (int) $gr['id_evaluacion'];
    $aid = (int) $gr['id_alumno'];
    $mid = (int) $gr['id_modulo'];
    $grades_by_eval[$eid][$aid][$mid] = [
        'numeric' => ae_grade_numeric((string) ($gr['calificacion_original'] ?? ''), $gr['nota']),
        'display' => ae_grade_display((string) ($gr['calificacion_original'] ?? ''), $gr['nota']),
    ];
}

$eval_data = [];
foreach ($export_eval_ids as $eval_id) {
    $ev_grades = $grades_by_eval[$eval_id] ?? [];

    $students_with_grades = [];
    foreach ($students as $st) {
        $sid = (int) $st['id_alumno'];
        foreach ($modules as $m) {
            $mid = (int) $m['id_modulo'];
            if (($ev_grades[$sid][$mid]['numeric'] ?? null) !== null) {
                $students_with_grades[$sid] = true;
                break;
            }
        }
    }

    $module_stats = [];
    foreach ($modules as $m) {
        $mid  = (int) $m['id_modulo'];
        $code = trim((string) $m['codigo']);
        if ($code === '') {
            $code = trim((string) ($m['abreviatura'] ?? 'M'));
        }
        $name = trim((string) ($m['materia_general'] ?? ''));
        if ($name === '') {
            $name = trim((string) ($m['materia_propia'] ?? ''));
        }
        $module_stats[$mid] = [
            'codigo'    => $code,
            'nombre'    => $name,
            'aprobados' => 0,
            'suspensos' => 0,
            'numeric'   => [],
        ];
    }

    $cls_counts = [
        'Todo aprobado'     => 0,
        '1 suspensa'        => 0,
        '2 suspensas'       => 0,
        '3 o más suspensas' => 0,
    ];
    $group_means       = [];
    $student_rows_eval = [];

    foreach ($students as $st) {
        $sid  = (int) $st['id_alumno'];
        $has  = isset($students_with_grades[$sid]);
        $full = trim((string) $st['apellido1'])
            . (trim((string) ($st['apellido2'] ?? '')) !== '' ? ' ' . trim((string) $st['apellido2']) : '')
            . ', ' . trim((string) $st['nombre']);

        $aprobados = 0;
        $suspensos = 0;
        $notes     = [];

        foreach ($modules as $m) {
            $mid     = (int) $m['id_modulo'];
            $numeric = $ev_grades[$sid][$mid]['numeric'] ?? null;
            if ($numeric === null) {
                continue;
            }
            $notes[] = $numeric;
            if ($has) {
                $module_stats[$mid]['numeric'][] = $numeric;
                if ($numeric >= 5) {
                    $aprobados++;
                    $module_stats[$mid]['aprobados']++;
                } else {
                    $suspensos++;
                    $module_stats[$mid]['suspensos']++;
                }
            }
        }

        $media          = $notes !== [] ? array_sum($notes) / count($notes) : null;
        $classification = $has ? ae_classify($suspensos) : '—';
        if ($has) {
            $cls_counts[$classification]++;
            if ($media !== null) {
                $group_means[] = $media;
            }
            $student_rows_eval[] = [
                'id_alumno'    => $sid,
                'nombre'       => $full,
                'aprobados'    => $aprobados,
                'suspensos'    => $suspensos,
                'media'        => $media,
                'clasificacion' => $classification,
            ];
        }
    }

    $module_rows_eval = [];
    foreach ($module_stats as $mid => $ms) {
        $evaluados = $ms['aprobados'] + $ms['suspensos'];
        if ($evaluados === 0) {
            continue;
        }
        $pct    = $evaluados > 0 ? ($ms['aprobados'] * 100 / $evaluados) : null;
        $media_m = $ms['numeric'] !== [] ? array_sum($ms['numeric']) / count($ms['numeric']) : null;
        $module_rows_eval[] = [
            'codigo'        => $ms['codigo'],
            'nombre'        => $ms['nombre'],
            'evaluados'     => $evaluados,
            'aprobados'     => $ms['aprobados'],
            'suspensos'     => $ms['suspensos'],
            'pct_aprobados' => $pct,
            'media'         => $media_m,
        ];
    }

    $total      = count($students_with_grades);
    $todo_ap    = $cls_counts['Todo aprobado'];
    $group_mean = $group_means !== [] ? array_sum($group_means) / count($group_means) : null;

    $eval_data[$eval_id] = [
        'student_rows' => $student_rows_eval,
        'module_rows'  => $module_rows_eval,
        'group_stats'  => [
            'total'              => $total,
            'todo_aprobado'      => $todo_ap,
            'pct_todo_aprobado'  => $total > 0 ? ($todo_ap * 100 / $total) : null,
            'media'              => $group_mean,
            'una'                => $cls_counts['1 suspensa'],
            'dos'                => $cls_counts['2 suspensas'],
            'tres_o_mas'         => $cls_counts['3 o más suspensas'],
        ],
    ];
}

$safe_group = trim(preg_replace('/[^\w\-]/u', '_', $group_name) ?? 'grupo', '_');
$safe_eval  = trim(preg_replace('/[^\w\-]/u', '_', $eval_name_by_id[$selected_evaluation] ?? 'eval') ?? 'eval', '_');
$filename   = 'analisis_' . $safe_group . '_' . $safe_eval;

ob_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Análisis — <?php echo ae_h($group_name); ?> — <?php echo ae_h($eval_name_by_id[$selected_evaluation] ?? ''); ?></title>
<style>
body { font-family: Arial, sans-serif; font-size: 10px; color: #1f2a44; margin: 16px; }
h1 { font-size: 15px; margin: 0 0 4px; }
h2 { font-size: 12px; background: #1f2a44; color: #fff; padding: 5px 8px; margin: 18px 0 4px; }
h3 { font-size: 10px; font-weight: bold; margin: 8px 0 2px; color: #374151; }
p.meta { font-size: 9px; color: #6b7280; margin: 0 0 14px; }
table { border-collapse: collapse; margin-bottom: 10px; }
table.wide { width: 100%; }
th { background: #eef0f7; color: #374151; font-weight: bold; padding: 4px 6px; border: 1px solid #d1d5db; font-size: 9px; text-align: left; white-space: nowrap; }
td { padding: 3px 6px; border: 1px solid #e5e7eb; font-size: 9px; vertical-align: middle; }
tr:nth-child(even) td { background: #f9fafb; }
.ap { color: #059669; font-weight: bold; }
.su { color: #dc2626; font-weight: bold; }
.na { color: #9ca3af; }
.sep { height: 16px; }
</style>
</head>
<body>
<h1>Análisis de calificaciones</h1>
<p class="meta">
  Grupo: <?php echo ae_h($group_name); ?> &nbsp;&middot;&nbsp;
  Curso: <?php echo ae_h($course_name); ?> &nbsp;&middot;&nbsp;
  Hasta evaluación: <?php echo ae_h($eval_name_by_id[$selected_evaluation] ?? ''); ?> &nbsp;&middot;&nbsp;
  Generado el <?php echo date('d/m/Y H:i'); ?>
</p>

<?php if ($modules !== []): ?>
<h3>Leyenda de módulos</h3>
<table>
  <thead>
    <tr><th>Código</th><th>Nombre</th></tr>
  </thead>
  <tbody>
    <?php foreach ($modules as $m):
      $leg_code = trim((string) $m['codigo']);
      if ($leg_code === '') { $leg_code = trim((string) ($m['abreviatura'] ?? '')); }
      $leg_name = trim((string) ($m['materia_general'] ?? ''));
      if ($leg_name === '') { $leg_name = trim((string) ($m['materia_propia'] ?? '')); }
    ?>
    <tr>
      <td><?php echo ae_h($leg_code); ?></td>
      <td><?php echo ae_h($leg_name); ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>

<?php foreach ($export_evaluations as $ev):
  $eval_id   = (int) $ev['id_evaluacion'];
  $eval_name = (string) $ev['nombre'];
  $ed = $eval_data[$eval_id] ?? null;
  if ($ed === null) { continue; }
  $gs = $ed['group_stats'];
  $sr = $ed['student_rows'];
  $mr = $ed['module_rows'];
?>
<h2><?php echo ae_h($eval_name); ?></h2>

<h3>Resumen del grupo</h3>
<table>
  <thead>
    <tr>
      <th>Alumnos evaluados</th>
      <th>Todo aprobado</th>
      <th>1 suspensa</th>
      <th>2 suspensas</th>
      <th>3 o más suspensas</th>
      <th>% Todo aprobado</th>
      <th>Media del grupo</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><?php echo (int) $gs['total']; ?></td>
      <td class="ap"><?php echo (int) $gs['todo_aprobado']; ?></td>
      <td><?php echo (int) $gs['una']; ?></td>
      <td><?php echo (int) $gs['dos']; ?></td>
      <td class="<?php echo $gs['tres_o_mas'] > 0 ? 'su' : ''; ?>"><?php echo (int) $gs['tres_o_mas']; ?></td>
      <td><?php echo ae_h(ae_fmt($gs['pct_todo_aprobado'])); ?>%</td>
      <td><?php echo ae_h(ae_fmt($gs['media'])); ?></td>
    </tr>
  </tbody>
</table>

<?php if ($sr !== []): ?>
<h3>Calificaciones por alumno</h3>
<table class="wide">
  <thead>
    <tr>
      <th>Alumno</th>
      <?php foreach ($modules as $m):
        $col_code = trim((string) $m['codigo']);
        if ($col_code === '') { $col_code = trim((string) ($m['abreviatura'] ?? '')); }
      ?>
      <th><?php echo ae_h($col_code); ?></th>
      <?php endforeach; ?>
      <th>Media</th>
      <th>Aprobados</th>
      <th>Suspensos</th>
      <th>Clasificación</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($sr as $row):
      $sid = (int) $row['id_alumno'];
    ?>
    <tr>
      <td><?php echo ae_h((string) $row['nombre']); ?></td>
      <?php foreach ($modules as $m):
        $mid  = (int) $m['id_modulo'];
        $gd   = $grades_by_eval[$eval_id][$sid][$mid] ?? null;
        $num  = $gd['numeric'] ?? null;
        $disp = $gd !== null ? (string) $gd['display'] : '—';
        $cls  = $num !== null ? ($num >= 5 ? 'ap' : 'su') : 'na';
      ?>
      <td class="<?php echo $cls; ?>"><?php echo ae_h($disp); ?></td>
      <?php endforeach; ?>
      <td><?php echo ae_h(ae_fmt($row['media'])); ?></td>
      <td><?php echo (int) $row['aprobados']; ?></td>
      <td><?php echo (int) $row['suspensos']; ?></td>
      <td><?php echo ae_h((string) $row['clasificacion']); ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>

<?php if ($mr !== []): ?>
<h3>Métricas por módulo</h3>
<table>
  <thead>
    <tr>
      <th>Módulo</th>
      <th>Evaluados</th>
      <th>Aprobados</th>
      <th>Suspensos</th>
      <th>% Aprobados</th>
      <th>Media</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($mr as $mrow): ?>
    <tr>
      <td><?php echo ae_h((string) $mrow['codigo']); ?><?php echo $mrow['nombre'] !== '' ? ' — ' . ae_h((string) $mrow['nombre']) : ''; ?></td>
      <td><?php echo (int) $mrow['evaluados']; ?></td>
      <td class="ap"><?php echo (int) $mrow['aprobados']; ?></td>
      <td class="<?php echo $mrow['suspensos'] > 0 ? 'su' : ''; ?>"><?php echo (int) $mrow['suspensos']; ?></td>
      <td><?php echo ae_h(ae_fmt($mrow['pct_aprobados'])); ?>%</td>
      <td><?php echo ae_h(ae_fmt($mrow['media'])); ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>

<div class="sep"></div>
<?php endforeach; ?>

</body>
</html>
<?php
$html_content = (string) ob_get_clean();

if ($formato === 'pdf') {
    require_once __DIR__ . '/../vendor/autoload.php';
    $mpdf_temp = __DIR__ . '/../docs/tmp_mpdf';
    if (!is_dir($mpdf_temp) && !mkdir($mpdf_temp, 0775, true) && !is_dir($mpdf_temp)) {
        http_response_code(500);
        exit('No se pudo crear el directorio temporal para PDF.');
    }
    $mpdf = new \Mpdf\Mpdf([
        'mode'          => 'utf-8',
        'format'        => 'A4-L',
        'margin_left'   => 8,
        'margin_right'  => 8,
        'margin_top'    => 12,
        'margin_bottom' => 10,
        'tempDir'       => $mpdf_temp,
    ]);
    $mpdf->WriteHTML($html_content);
    $mpdf->Output($filename . '.pdf', 'D');
} else {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
    header('Cache-Control: max-age=0');
    echo "\xEF\xBB\xBF";
    echo $html_content;
}
exit;
