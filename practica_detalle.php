<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

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

function format_bool_value($value, string $fallback = 'No disponible'): string {
  if ($value === null || $value === '') {
    return $fallback;
  }

  return (int) $value === 1 ? 'Sí' : 'No';
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

$id_practica_raw = $_GET['id_practica'] ?? null;
$id_practica = filter_var($id_practica_raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

$load_error = null;
$practice = null;
$schedule_by_day = [];

if ($id_practica === false || $id_practica === null) {
  $load_error = 'No se ha indicado un identificador de práctica válido.';
} else {
  try {
    $pdo = db();

    $practice_stmt = $pdo->prepare(
      'SELECT
        p.*,
        pe.estado AS estado_practica,
        a.nia AS alumno_nia,
        a.dni AS alumno_dni,
        a.nombre AS alumno_nombre,
        a.apellido1 AS alumno_apellido1,
        a.apellido2 AS alumno_apellido2,
        e.cif AS empresa_cif,
        e.convenio AS empresa_convenio,
        e.nombre AS empresa_nombre,
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
        d.principal AS direccion_principal,
        v.via AS direccion_via_tipo,
        ld.nombre AS direccion_localidad,
        pd.nombre AS direccion_provincia,
        pa.pais AS direccion_pais,
        ac.id_curso_escolar,
        ce.curso_escolar,
        ac.id_grupo,
        g.grupo
      FROM practicas p
      INNER JOIN alumnos a ON a.id_alumno = p.id_alumno
      INNER JOIN empresas e ON e.id_empresa = p.id_empresa
      LEFT JOIN practicas_estados pe ON pe.id_practicas_estado = p.id_practicas_estado
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
      LEFT JOIN grupos g ON g.id_grupo = ac.id_grupo
      WHERE p.id_practica = :id_practica
      LIMIT 1'
    );

    $practice_stmt->execute(['id_practica' => $id_practica]);
    $practice = $practice_stmt->fetch();

    if ($practice) {
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
    }
  } catch (Throwable $error) {
    $load_error = 'No se ha podido cargar el detalle de la práctica en este momento.';
  }
}

$practice_found = is_array($practice);
$student_name = $practice_found ? full_name($practice, 'alumno') : 'Práctica no encontrada';
$company_name = $practice_found ? full_name($practice, 'empresa') : 'Práctica no encontrada';
$page_title = $practice_found
  ? 'Detalle de práctica #' . (int) $practice['id_practica'] . ' | Gestor de Alumnos'
  : 'Práctica no encontrada | Gestor de Alumnos';
$active_page = 'practicas';
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
          <p class="eyebrow">Detalle de práctica</p>
          <h1><?php echo htmlspecialchars($practice_found ? $student_name : 'Práctica no encontrada', ENT_QUOTES, 'UTF-8'); ?></h1>
          <p class="subheading">Consulta la información completa de la práctica y su horario asociado.</p>
        </div>
        <div class="header-actions">
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
        <div class="grid">
          <section class="panel">
            <div class="panel-header">
              <h3>Resumen</h3>
              <p>Estado general, alumno, empresa y datos principales de la práctica.</p>
            </div>
            <div class="panel-grid">
              <table class="panel-table-aligned">
                <tbody>
                  <tr><th>Estado</th><td><?php echo htmlspecialchars(format_value($practice['estado_practica']), ENT_QUOTES, 'UTF-8'); ?></td></tr>
                  <tr><th>Curso escolar</th><td><?php echo htmlspecialchars(format_value($practice['curso_escolar']), ENT_QUOTES, 'UTF-8'); ?></td></tr>
                  <tr><th>Grupo</th><td><?php echo htmlspecialchars(format_value($practice['grupo']), ENT_QUOTES, 'UTF-8'); ?></td></tr>
                  <tr><th>Alumno</th><td><?php echo htmlspecialchars($student_name, ENT_QUOTES, 'UTF-8'); ?></td></tr>
                  <tr><th>NIA / DNI alumno</th><td><?php echo htmlspecialchars(format_value($practice['alumno_nia']) . ' / ' . format_value($practice['alumno_dni']), ENT_QUOTES, 'UTF-8'); ?></td></tr>
                  <tr><th>Empresa</th><td><?php echo htmlspecialchars($company_name, ENT_QUOTES, 'UTF-8'); ?></td></tr>
                  <tr><th>CIF / Convenio</th><td><?php echo htmlspecialchars(format_value($practice['empresa_cif']) . ' / ' . format_value($practice['empresa_convenio']), ENT_QUOTES, 'UTF-8'); ?></td></tr>
                  <tr><th>Centro de trabajo</th><td><?php echo htmlspecialchars(build_address($practice), ENT_QUOTES, 'UTF-8'); ?></td></tr>
                  <tr><th>Etiqueta dirección</th><td><?php echo htmlspecialchars(format_value($practice['direccion_etiqueta']), ENT_QUOTES, 'UTF-8'); ?></td></tr>
                  <tr><th>Tutor de empresa</th><td><?php echo htmlspecialchars(full_name($practice, 'tutor'), ENT_QUOTES, 'UTF-8'); ?></td></tr>
                  <tr><th>DNI tutor</th><td><?php echo htmlspecialchars(format_value($practice['tutor_dni']), ENT_QUOTES, 'UTF-8'); ?></td></tr>
                  <tr><th>Fecha inicio</th><td><?php echo htmlspecialchars(format_date($practice['fecha_inicio']), ENT_QUOTES, 'UTF-8'); ?></td></tr>
                  <tr><th>Fecha fin</th><td><?php echo htmlspecialchars(format_date($practice['fecha_fin']), ENT_QUOTES, 'UTF-8'); ?></td></tr>
                  <tr><th>Horas totales</th><td><?php echo htmlspecialchars(format_value($practice['horas']), ENT_QUOTES, 'UTF-8'); ?></td></tr>
                </tbody>
              </table>
            </div>
          </section>

          <section class="panel">
            <div class="panel-header">
              <h3>Auditoría y metadatos</h3>
              <p>Identificadores técnicos y banderas de anexos de la práctica.</p>
            </div>
            <div class="panel-grid">
              <table class="panel-table-aligned">
                <tbody>
                  <tr><th>ID práctica</th><td><?php echo htmlspecialchars((string) (int) $practice['id_practica'], ENT_QUOTES, 'UTF-8'); ?></td></tr>
                  <tr><th>ID alumno</th><td><?php echo htmlspecialchars((string) (int) $practice['id_alumno'], ENT_QUOTES, 'UTF-8'); ?></td></tr>
                  <tr><th>ID empresa</th><td><?php echo htmlspecialchars((string) (int) $practice['id_empresa'], ENT_QUOTES, 'UTF-8'); ?></td></tr>
                  <tr><th>ID dirección</th><td><?php echo htmlspecialchars(format_value($practice['id_direccion']), ENT_QUOTES, 'UTF-8'); ?></td></tr>
                  <tr><th>ID tutor empresa</th><td><?php echo htmlspecialchars(format_value($practice['id_empresa_tutor']), ENT_QUOTES, 'UTF-8'); ?></td></tr>
                  <tr><th>ID estado práctica</th><td><?php echo htmlspecialchars(format_value($practice['id_practicas_estado']), ENT_QUOTES, 'UTF-8'); ?></td></tr>
                  <tr><th>ID curso escolar</th><td><?php echo htmlspecialchars(format_value($practice['id_curso_escolar']), ENT_QUOTES, 'UTF-8'); ?></td></tr>
                  <tr><th>ID grupo</th><td><?php echo htmlspecialchars(format_value($practice['id_grupo']), ENT_QUOTES, 'UTF-8'); ?></td></tr>
                  <tr><th>Anexo</th><td><?php echo htmlspecialchars(format_value($practice['anexo']), ENT_QUOTES, 'UTF-8'); ?></td></tr>
                  <tr>
                    <th>Circunstancias excepcionales</th>
                    <td>
                      <?php
                        $circ_excep = isset($practice['circ_excep']) ? (int) $practice['circ_excep'] : null;
                        $circ_excep_label = $circ_excep === 1
                          ? 'Requiere solicitud de autorización para la realización de la FFE bajo circunstancias de carácter excepcional.'
                          : 'No';
                        echo htmlspecialchars($circ_excep_label, ENT_QUOTES, 'UTF-8');
                      ?>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </section>
        </div>

        <section class="panel">
          <div class="panel-header">
            <h3>Horario semanal</h3>
            <p>Tramos por día y cómputo de horas diario.</p>
          </div>
          <div class="panel-grid">
            <table class="practica-horario-table practica-horario-detalle-table">
              <thead>
                <tr>
                  <th>Día</th>
                  <th>Entrada mañana</th>
                  <th>Salida mañana</th>
                  <th>Entrada tarde</th>
                  <th>Salida tarde</th>
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
                    <td><?php echo htmlspecialchars(format_time($morning['hora_entrada'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars(format_time($morning['hora_salida'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars(format_time($afternoon['hora_entrada'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars(format_time($afternoon['hora_salida'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($total_label, ENT_QUOTES, 'UTF-8'); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </section>

        <section class="panel">
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

        <?php
          $main_displayed_columns = [
            'id_practica', 'id_alumno', 'id_empresa', 'id_direccion', 'id_empresa_tutor',
            'anexo', 'id_practicas_estado', 'fecha_inicio', 'fecha_fin', 'horas',
            'requiere_anexo_5', 'requiere_anexo_6', 'observaciones',
          ];
          $additional_columns = [];
          foreach ($practice as $column => $value) {
            if (strpos($column, '_') === false) {
              continue;
            }
            if (!in_array($column, $main_displayed_columns, true) &&
                !str_starts_with($column, 'alumno_') &&
                !str_starts_with($column, 'empresa_') &&
                !str_starts_with($column, 'tutor_') &&
                !str_starts_with($column, 'direccion_') &&
                !in_array($column, ['estado_practica', 'curso_escolar', 'grupo', 'id_curso_escolar', 'id_grupo'], true)) {
              $additional_columns[$column] = $value;
            }
          }
        ?>
        <?php if ($additional_columns): ?>
          <section class="panel">
            <div class="panel-header">
              <h3>Campos adicionales</h3>
              <p>Datos en bruto no mostrados en los bloques principales.</p>
            </div>
            <div class="panel-grid">
              <table class="panel-table-aligned">
                <tbody>
                  <?php foreach ($additional_columns as $column => $value): ?>
                    <tr>
                      <th><?php echo htmlspecialchars($column, ENT_QUOTES, 'UTF-8'); ?></th>
                      <td><?php echo htmlspecialchars(format_value($value), ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </section>
        <?php endif; ?>
      <?php endif; ?>
    </main>
  </div>
</body>
</html>
