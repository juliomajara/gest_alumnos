<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/generar_anexo_3a.php';

$pdo = db();

$page_title = 'Asistencia mensual por alumno | Gestor de Alumnos';
$active_page = 'configuracion';

$errors = [];

$active_course_id = (int) ($pdo->query('SELECT id_curso_escolar FROM cursos_escolares WHERE activo = 1 ORDER BY id_curso_escolar DESC LIMIT 1')->fetchColumn() ?: 0);

$selected = [
  'id_curso_escolar' => (int) ($_GET['id_curso_escolar'] ?? $active_course_id),
  'id_ciclo' => 0,
  'id_curso' => 0,
  'id_grupo' => (int) ($_GET['id_grupo'] ?? 0),
];
$vista = (string) ($_GET['vista'] ?? ($_GET['tipo'] ?? 'injustificadas'));
if ($vista === 'faltas') {
  $vista = 'injustificadas';
}
if (!in_array($vista, ['injustificadas', 'justificadas', 'retrasos'], true)) {
  $vista = 'injustificadas';
}
$mostrar_retrasos = $vista === 'retrasos';
$mostrar_justificadas = $vista === 'justificadas';

if ((string) ($_GET['accion'] ?? '') === 'descargar_anexo_3a') {
  $id_alumno_descarga = filter_input(INPUT_GET, 'id_alumno', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
  $id_curso_descarga = filter_input(INPUT_GET, 'id_curso_escolar', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
  $id_grupo_descarga = filter_input(INPUT_GET, 'id_grupo', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
  $tipo_anexo_descarga = (string) ($_GET['tipo'] ?? 'normal');
  if (!in_array($tipo_anexo_descarga, ['normal', 'firma'], true)) {
    $tipo_anexo_descarga = 'normal';
  }

  try {
    generar_anexo_3a_descarga($pdo, (int) $id_alumno_descarga, (int) $id_curso_descarga, (int) $id_grupo_descarga, $tipo_anexo_descarga);
  } catch (Throwable $e) {
    $errors[] = $e->getMessage();
  }
}

$cursos_escolares = $pdo->query('SELECT id_curso_escolar, curso_escolar FROM cursos_escolares ORDER BY activo DESC, id_curso_escolar DESC')->fetchAll(PDO::FETCH_ASSOC);
$grupos = $pdo->query('SELECT id_grupo, id_ciclo, id_curso, grupo FROM grupos ORDER BY grupo')->fetchAll(PDO::FETCH_ASSOC);
$meses = $pdo->query('SELECT id_mes, mes, orden FROM meses ORDER BY orden, id_mes')->fetchAll(PDO::FETCH_ASSOC);

$students = [];
$attendance_rows = [];
$hours_by_student = [];

if ($selected['id_curso_escolar'] > 0 && $selected['id_grupo'] > 0) {
  $curso_valido_stmt = $pdo->prepare('SELECT 1 FROM cursos_escolares WHERE id_curso_escolar = :id_curso_escolar LIMIT 1');
  $curso_valido_stmt->execute([
    'id_curso_escolar' => $selected['id_curso_escolar'],
  ]);

  if (!$curso_valido_stmt->fetchColumn()) {
    $errors[] = 'El curso escolar seleccionado no es válido.';
  }

  $grupo_valido_stmt = $pdo->prepare('SELECT id_ciclo, id_curso FROM grupos WHERE id_grupo = :id_grupo LIMIT 1');
  $grupo_valido_stmt->execute([
    'id_grupo' => $selected['id_grupo'],
  ]);
  $grupo_contexto = $grupo_valido_stmt->fetch(PDO::FETCH_ASSOC);

  if (!$grupo_contexto) {
    $errors[] = 'El grupo seleccionado no es válido.';
  } else {
    $selected['id_ciclo'] = (int) $grupo_contexto['id_ciclo'];
    $selected['id_curso'] = (int) $grupo_contexto['id_curso'];
  }

  if ($errors === []) {
    $students_stmt = $pdo->prepare(
      'SELECT a.id_alumno, a.apellido1, a.apellido2, a.nombre,
              a.faltas_10_dia, a.faltas_10_cantidad, a.faltas_15_dia, a.faltas_15_cantidad
       FROM alumno_curso ac
       INNER JOIN alumnos a ON a.id_alumno = ac.id_alumno
       WHERE ac.id_curso_escolar = :id_curso_escolar
         AND ac.id_ciclo = :id_ciclo
         AND ac.id_curso = :id_curso
         AND ac.id_grupo = :id_grupo
       ORDER BY a.apellido1, a.apellido2, a.nombre'
    );
    $students_stmt->execute([
      'id_curso_escolar' => $selected['id_curso_escolar'],
      'id_ciclo' => $selected['id_ciclo'],
      'id_curso' => $selected['id_curso'],
      'id_grupo' => $selected['id_grupo'],
    ]);
    $students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($students !== []) {
      $hours_stmt = $pdo->prepare(
        'SELECT SUM(COALESCE(m.horas_totales, 0)) AS horas_totales_matriculadas
         FROM (
           SELECT DISTINCT id_modulo
           FROM alumno_modulo
           WHERE id_alumno = :id_alumno
         ) am
         INNER JOIN modulos m ON m.id_modulo = am.id_modulo
         WHERE (
           COALESCE(m.horas_semanales, 0) > 0
           OR LOWER(COALESCE(m.materia_general, \'\')) LIKE \'%proyecto intermodular%\'
           OR LOWER(COALESCE(m.materia_propia, \'\')) LIKE \'%proyecto intermodular%\'
         )'
      );

      foreach ($students as $student) {
        $id_alumno_horas = (int) $student['id_alumno'];
        $hours_stmt->execute([
          'id_alumno' => $id_alumno_horas,
        ]);
        $hours_by_student[$id_alumno_horas] = (float) ($hours_stmt->fetchColumn() ?: 0);
      }
    }

    $attendance_stmt = $pdo->prepare(
      'SELECT am.id_alumno,
              am.id_mes,
              SUM(am.faltas_justificadas) AS faltas_justificadas,
              SUM(am.faltas_injustificadas) AS faltas_injustificadas,
              SUM(am.retrasos) AS retrasos
       FROM asistencia_mensual am
       INNER JOIN alumno_curso ac ON ac.id_alumno = am.id_alumno
       WHERE am.id_curso_escolar = :id_curso_escolar
         AND ac.id_curso_escolar = :id_curso_escolar_ac
         AND ac.id_ciclo = :id_ciclo
         AND ac.id_curso = :id_curso
         AND ac.id_grupo = :id_grupo
       GROUP BY am.id_alumno, am.id_mes'
    );
    $attendance_stmt->execute([
      'id_curso_escolar' => $selected['id_curso_escolar'],
      'id_curso_escolar_ac' => $selected['id_curso_escolar'],
      'id_ciclo' => $selected['id_ciclo'],
      'id_curso' => $selected['id_curso'],
      'id_grupo' => $selected['id_grupo'],
    ]);
    $attendance_rows = $attendance_stmt->fetchAll(PDO::FETCH_ASSOC);
  }
}

$attendance_index = [];
foreach ($attendance_rows as $row) {
  $id_alumno = (int) $row['id_alumno'];
  $id_mes = (int) $row['id_mes'];

  if (!isset($attendance_index[$id_alumno])) {
    $attendance_index[$id_alumno] = [];
  }

  $attendance_index[$id_alumno][$id_mes] = [
    'faltas_justificadas' => (int) $row['faltas_justificadas'],
    'faltas_injustificadas' => (int) $row['faltas_injustificadas'],
    'retrasos' => (int) $row['retrasos'],
  ];
}

$meses_nombres = [
  'septiembre' => 'sep',
  'octubre' => 'oct',
  'noviembre' => 'nov',
  'diciembre' => 'dic',
  'enero' => 'ene',
  'febrero' => 'feb',
  'marzo' => 'mar',
  'abril' => 'abr',
  'mayo' => 'may',
  'junio' => 'jun',
];

$meses_index_nombre = [];
foreach ($meses as $mes) {
  $meses_index_nombre[strtolower(trim((string) $mes['mes']))] = (int) $mes['id_mes'];
}

$meses_curso = [];
foreach ($meses_nombres as $mes_nombre => $mes_abreviado) {
  $numero_mes = 0;
  if ($mes_nombre === 'septiembre') {
    $numero_mes = 9;
  } elseif ($mes_nombre === 'octubre') {
    $numero_mes = 10;
  } elseif ($mes_nombre === 'noviembre') {
    $numero_mes = 11;
  } elseif ($mes_nombre === 'diciembre') {
    $numero_mes = 12;
  } elseif ($mes_nombre === 'enero') {
    $numero_mes = 1;
  } elseif ($mes_nombre === 'febrero') {
    $numero_mes = 2;
  } elseif ($mes_nombre === 'marzo') {
    $numero_mes = 3;
  } elseif ($mes_nombre === 'abril') {
    $numero_mes = 4;
  } elseif ($mes_nombre === 'mayo') {
    $numero_mes = 5;
  } elseif ($mes_nombre === 'junio') {
    $numero_mes = 6;
  }
  $meses_curso[] = [
    'mes' => $mes_abreviado,
    'id_mes' => $meses_index_nombre[$mes_nombre] ?? 0,
    'numero_mes' => $numero_mes,
  ];
}

$total_columnas_asistencia = 4 + count($meses_curso) + 3;
$vista_titulo = 'Faltas Injustificadas';
if ($vista === 'justificadas') {
  $vista_titulo = 'Faltas Justificadas';
} elseif ($vista === 'retrasos') {
  $vista_titulo = 'Retrasos';
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
          <h1>Asistencia mensual por alumno</h1>
          <p class="subheading">Consulta faltas justificadas, injustificadas y retrasos por alumno y mes.</p>
        </div>
      </header>

      <?php if ($errors !== []): ?>
        <section class="panel">
          <div class="panel-header">
            <h3>Errores</h3>
          </div>
          <ul class="form-errors">
            <?php foreach ($errors as $error): ?>
              <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
            <?php endforeach; ?>
          </ul>
        </section>
      <?php endif; ?>

      <form method="get" class="panel entity-form">
        <section class="entity-section">
          <div class="panel-header">
            <h3>Filtros</h3>
            <p>Selecciona curso escolar y grupo para ver la asistencia agrupada por mes.</p>
          </div>

          <div class="entity-grid entity-grid--4">
            <label>
              <select name="id_curso_escolar" required>
                <option value="">Selecciona curso escolar</option>
                <?php foreach ($cursos_escolares as $item): ?>
                  <option value="<?php echo (int) $item['id_curso_escolar']; ?>" <?php echo (int) $item['id_curso_escolar'] === $selected['id_curso_escolar'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars((string) $item['curso_escolar'], ENT_QUOTES, 'UTF-8'); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </label>

            <label>
              <select name="id_grupo" required>
                <option value="">Selecciona grupo</option>
                <?php foreach ($grupos as $item): ?>
                  <option value="<?php echo (int) $item['id_grupo']; ?>" <?php echo (int) $item['id_grupo'] === $selected['id_grupo'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars((string) $item['grupo'], ENT_QUOTES, 'UTF-8'); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </label>

          </div>
        </section>
      </form>

      <section class="panel">
        <div class="panel-header panel-header-with-actions">
          <div>
            <h3>Listado de asistencia - <?php echo htmlspecialchars($vista_titulo, ENT_QUOTES, "UTF-8"); ?></h3>
            <p>Totales mensuales por alumno.</p>
          </div>
          <div class="panel-header-actions">
            <?php $query_base = $_GET; ?>
            <?php if ($vista !== 'injustificadas'): ?>
              <?php $query_injustificadas = $query_base; $query_injustificadas['vista'] = 'injustificadas'; $query_injustificadas['tipo'] = 'injustificadas'; ?>
              <a class="ghost-button" href="?<?php echo htmlspecialchars(http_build_query($query_injustificadas), ENT_QUOTES, 'UTF-8'); ?>">Injustificadas</a>
            <?php endif; ?>
            <?php if ($vista !== 'justificadas'): ?>
              <?php $query_justificadas = $query_base; $query_justificadas['vista'] = 'justificadas'; $query_justificadas['tipo'] = 'justificadas'; ?>
              <a class="ghost-button" href="?<?php echo htmlspecialchars(http_build_query($query_justificadas), ENT_QUOTES, 'UTF-8'); ?>">Justificadas</a>
            <?php endif; ?>
            <?php if ($vista !== 'retrasos'): ?>
              <?php $query_retrasos = $query_base; $query_retrasos['vista'] = 'retrasos'; $query_retrasos['tipo'] = 'retrasos'; ?>
              <a class="ghost-button" href="?<?php echo htmlspecialchars(http_build_query($query_retrasos), ENT_QUOTES, 'UTF-8'); ?>">Retrasos</a>
            <?php endif; ?>
          </div>
        </div>

        <div class="panel-grid">
          <table>
            <thead>
              <tr>
                <th rowspan="2">Alumno</th>
                <th rowspan="2">Horas matriculadas</th>
                <th rowspan="2">Descargar Anexo 3A</th>
                <th rowspan="2">Anexo 4A</th>
                <?php foreach ($meses_curso as $mes): ?>
                  <th colspan="1"><?php echo htmlspecialchars((string) $mes['mes'], ENT_QUOTES, 'UTF-8'); ?></th>
                <?php endforeach; ?>
                <th colspan="3">Totales</th>
              </tr>
              <tr>
                <?php foreach ($meses_curso as $mes): ?>
                  <?php if ($mostrar_retrasos): ?>
                    <th>R</th>
                  <?php elseif ($mostrar_justificadas): ?>
                    <th>J</th>
                  <?php else: ?>
                    <th>I</th>
                  <?php endif; ?>
                <?php endforeach; ?>
                <th>Total I</th>
                <th>Total J</th>
                <th>Total R</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($selected['id_curso_escolar'] <= 0 || $selected['id_grupo'] <= 0): ?>
                <tr>
                  <td colspan="<?php echo $total_columnas_asistencia; ?>">Selecciona curso escolar y grupo para consultar la asistencia.</td>
                </tr>
              <?php elseif ($errors !== []): ?>
                <tr>
                  <td colspan="<?php echo $total_columnas_asistencia; ?>">No se pudo cargar la asistencia por los errores indicados.</td>
                </tr>
              <?php elseif ($students === []): ?>
                <tr>
                  <td colspan="<?php echo $total_columnas_asistencia; ?>">No hay alumnos matriculados para el filtro seleccionado.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($students as $student): ?>
                  <?php
                    $student_name = trim(
                      (string) ($student['apellido1'] ?? '')
                      . ' '
                      . (string) ($student['apellido2'] ?? '')
                      . ', '
                      . (string) ($student['nombre'] ?? '')
                    );
                    $id_alumno = (int) $student['id_alumno'];
                    $total_j = 0;
                    $total_i = 0;
                    $total_r = 0;
                    $horas_totales_matriculadas = $hours_by_student[$id_alumno] ?? 0;
                    $faltas_10_mes = 0;
                    $faltas_15_mes = 0;
                    $injustificadas_title_10 = '';
                    $injustificadas_title_15 = '';
                    $fecha_10 = !empty($student['faltas_10_dia']) ? date_create((string) $student['faltas_10_dia']) : false;
                    $fecha_15 = !empty($student['faltas_15_dia']) ? date_create((string) $student['faltas_15_dia']) : false;
                    if ($fecha_10 !== false) {
                      $faltas_10_mes = (int) $fecha_10->format('n');
                      $tooltip_lines_10 = [];
                      $tooltip_lines_10[] = '10% alcanzado el: ' . $fecha_10->format('d/m/Y');
                      if ($student['faltas_10_cantidad'] !== null && $student['faltas_10_cantidad'] !== '') {
                        $tooltip_lines_10[] = 'Faltas injustificadas acumuladas: ' . (string) $student['faltas_10_cantidad'];
                      }
                      $injustificadas_title_10 = implode("\n", $tooltip_lines_10);
                    }
                    $mostrar_boton_anexo_3a = $fecha_10 !== false
                      && $student['faltas_10_cantidad'] !== null
                      && $student['faltas_10_cantidad'] !== ''
                      && $id_alumno > 0
                      && $selected['id_curso_escolar'] > 0
                      && $selected['id_grupo'] > 0;
                    $query_anexo_normal = [];
                    $query_anexo_firma = [];
                    if ($mostrar_boton_anexo_3a) {
                      $query_anexo_normal = $_GET;
                      $query_anexo_normal['accion'] = 'descargar_anexo_3a';
                      $query_anexo_normal['id_alumno'] = $id_alumno;
                      $query_anexo_normal['id_curso_escolar'] = $selected['id_curso_escolar'];
                      $query_anexo_normal['id_grupo'] = $selected['id_grupo'];
                      $query_anexo_normal['tipo'] = 'normal';

                      $query_anexo_firma = $query_anexo_normal;
                      $query_anexo_firma['tipo'] = 'firma';
                    }
                    if ($fecha_15 !== false) {
                      $faltas_15_mes = (int) $fecha_15->format('n');
                      $tooltip_lines_15 = [];
                      $tooltip_lines_15[] = '15% alcanzado el: ' . $fecha_15->format('d/m/Y');
                      if ($student['faltas_15_cantidad'] !== null && $student['faltas_15_cantidad'] !== '') {
                        $tooltip_lines_15[] = 'Faltas injustificadas acumuladas: ' . (string) $student['faltas_15_cantidad'];
                      }
                      $injustificadas_title_15 = implode("\n", $tooltip_lines_15);
                    }
                  ?>
                  <tr>
                    <td><?php echo htmlspecialchars($student_name, ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars(rtrim(rtrim(number_format((float) $horas_totales_matriculadas, 2, '.', ''), '0'), '.'), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>
                      <?php if ($mostrar_boton_anexo_3a): ?>
                        <div class="anexo-actions">
                          <a class="ghost-button btn-anexo-mini" href="?<?php echo htmlspecialchars(http_build_query($query_anexo_normal), ENT_QUOTES, 'UTF-8'); ?>">3A</a>
                          <a class="ghost-button btn-anexo-mini" href="?<?php echo htmlspecialchars(http_build_query($query_anexo_firma), ENT_QUOTES, 'UTF-8'); ?>">3AF</a>
                        </div>
                      <?php else: ?>
                        -
                      <?php endif; ?>
                    </td>
                    <td>-</td>
                    <?php foreach ($meses_curso as $mes): ?>
                      <?php
                        $id_mes = (int) $mes['id_mes'];
                        $totales = $attendance_index[$id_alumno][$id_mes] ?? [
                          'faltas_justificadas' => 0,
                          'faltas_injustificadas' => 0,
                          'retrasos' => 0,
                        ];
                        $total_j += (int) $totales['faltas_justificadas'];
                        $total_i += (int) $totales['faltas_injustificadas'];
                        $total_r += (int) $totales['retrasos'];
                      ?>
                      <?php if ($mostrar_retrasos): ?>
                                                <?php
                          $valor_mes = (int) $totales['retrasos'];
                          $celda_class = '';
                          $celda_style = '';
                          if ($valor_mes > 0) {
                            $celda_class = 'attendance-gradient-cell';
                            $celda_style = '--attendance-intensity: ' . min($valor_mes, 100) / 100 . ';';
                          }
                        ?>
                        <td class="<?php echo htmlspecialchars($celda_class, ENT_QUOTES, 'UTF-8'); ?>" style="<?php echo htmlspecialchars($celda_style, ENT_QUOTES, 'UTF-8'); ?>"><?php echo $valor_mes; ?></td>
                      <?php elseif ($mostrar_justificadas): ?>
                                                <?php
                          $valor_mes = (int) $totales['faltas_justificadas'];
                          $celda_class = '';
                          $celda_style = '';
                          if ($valor_mes > 0) {
                            $celda_class = 'attendance-gradient-cell';
                            $celda_style = '--attendance-intensity: ' . min($valor_mes, 100) / 100 . ';';
                          }
                        ?>
                        <td class="<?php echo htmlspecialchars($celda_class, ENT_QUOTES, 'UTF-8'); ?>" style="<?php echo htmlspecialchars($celda_style, ENT_QUOTES, 'UTF-8'); ?>"><?php echo $valor_mes; ?></td>
                      <?php else: ?>
                        <?php
                          $injustificadas_class = '';
                          $injustificadas_title = '';
                          $numero_mes = (int) ($mes['numero_mes'] ?? 0);
                          if ($faltas_15_mes > 0 && $numero_mes === $faltas_15_mes) {
                            $injustificadas_class = 'attendance-warning-15';
                            $injustificadas_title = $injustificadas_title_15;
                          } elseif ($faltas_10_mes > 0 && $numero_mes === $faltas_10_mes) {
                            $injustificadas_class = 'attendance-warning-10';
                            $injustificadas_title = $injustificadas_title_10;
                          }
                          $valor_mes = (int) $totales['faltas_injustificadas'];
                          $celda_style = '';
                          if ($valor_mes > 0 && $injustificadas_class === '') {
                            $injustificadas_class = 'attendance-gradient-cell';
                            $celda_style = '--attendance-intensity: ' . min($valor_mes, 100) / 100 . ';';
                          }
                        ?>
                        <td class="<?php echo htmlspecialchars($injustificadas_class, ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo htmlspecialchars($injustificadas_title, ENT_QUOTES, 'UTF-8'); ?>" style="<?php echo htmlspecialchars($celda_style, ENT_QUOTES, 'UTF-8'); ?>"><?php echo $valor_mes; ?></td>
                      <?php endif; ?>
                    <?php endforeach; ?>
                    <td><strong><?php echo $total_i; ?></strong></td>
                    <td><strong><?php echo $total_j; ?></strong></td>
                    <td><strong><?php echo $total_r; ?></strong></td>
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
<script>
  (function () {
    var form = document.querySelector('form.entity-form');
    if (!form) {
      return;
    }
    var groupSelect = form.querySelector('select[name="id_grupo"]');
    if (!groupSelect) {
      return;
    }
    groupSelect.addEventListener('change', function () {
      form.submit();
    });
  }());
</script>
</html>
