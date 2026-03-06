<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$pdo = db();

$id_alumno = isset($_GET['id_alumno']) ? (int) $_GET['id_alumno'] : 0;

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

function calculate_age(?string $value): ?int {
  if ($value === null || $value === '') {
    return null;
  }

  $date = DateTime::createFromFormat('Y-m-d', $value);
  if (!$date || $date->format('Y-m-d') !== $value) {
    return null;
  }

  $today = new DateTime('today');
  return $date->diff($today)->y;
}

function format_bool(?int $value, string $fallback = 'No disponible'): string {
  if ($value === null) {
    return $fallback;
  }

  return $value === 1 ? 'Sí' : 'No';
}

function pick_contact_value(array $items, string $value_key, array $preferred_labels): ?string {
  foreach ($preferred_labels as $label) {
    foreach ($items as $item) {
      if (!isset($item['etiqueta'])) {
        continue;
      }
      if (strtolower((string) $item['etiqueta']) === strtolower($label)) {
        return $item[$value_key] ?? null;
      }
    }
  }

  if (!$items) {
    return null;
  }

  $first = $items[0];
  return $first[$value_key] ?? null;
}

$student = null;
$course_entries = [];
$modules = [];
$attendance = [];
$practicas = [];
$horarios = [];
$phones = [];
$emails = [];

if ($id_alumno > 0) {
  $student_stmt = $pdo->prepare(
    'SELECT a.*, p.nombre AS provincia, l.nombre AS localidad
     FROM alumnos a
     LEFT JOIN provincias p ON p.id_provincia = a.id_provincia
     LEFT JOIN localidades l ON l.id_localidad = a.id_localidad
     WHERE a.id_alumno = :id_alumno'
  );
  $student_stmt->execute(['id_alumno' => $id_alumno]);
  $student = $student_stmt->fetch();
}

if ($student) {
  $course_stmt = $pdo->prepare(
    'SELECT ac.id_curso_escolar,
            ce.curso_escolar,
            ce.activo,
            n.nivel,
            c.ciclo,
            c.abreviatura,
            cu.curso,
            g.grupo
     FROM alumno_curso ac
     LEFT JOIN cursos_escolares ce ON ce.id_curso_escolar = ac.id_curso_escolar
     LEFT JOIN niveles n ON n.id_nivel = ac.id_nivel
     LEFT JOIN ciclos c ON c.id_ciclo = ac.id_ciclo
     LEFT JOIN cursos cu ON cu.id_curso = ac.id_curso
     LEFT JOIN grupos g ON g.id_grupo = ac.id_grupo
     WHERE ac.id_alumno = :id_alumno
     ORDER BY ce.activo DESC, ce.curso_escolar DESC'
  );
  $course_stmt->execute(['id_alumno' => $id_alumno]);
  $course_entries = $course_stmt->fetchAll();

  $modules_stmt = $pdo->prepare(
    'SELECT m.id_modulo,
            m.codigo,
            m.abreviatura,
            m.tipo,
            m.materia_general,
            m.materia_propia,
            m.horas_semanales,
            m.horas_totales,
            n.nivel,
            c.ciclo,
            cu.curso
     FROM alumno_modulo am
     INNER JOIN modulos m ON m.id_modulo = am.id_modulo
     LEFT JOIN niveles n ON n.id_nivel = m.id_nivel
     LEFT JOIN ciclos c ON c.id_ciclo = m.id_ciclo
     LEFT JOIN cursos cu ON cu.id_curso = m.id_curso
     WHERE am.id_alumno = :id_alumno
     ORDER BY m.materia_general, m.materia_propia'
  );
  $modules_stmt->execute(['id_alumno' => $id_alumno]);
  $modules = $modules_stmt->fetchAll();

  $attendance_stmt = $pdo->prepare(
    'SELECT am.id_mes,
            me.mes,
            me.orden,
            am.id_curso_escolar,
            ce.curso_escolar,
            am.faltas_justificadas,
            am.faltas_injustificadas,
            am.retrasos
     FROM asistencia_mensual am
     LEFT JOIN meses me ON me.id_mes = am.id_mes
     LEFT JOIN cursos_escolares ce ON ce.id_curso_escolar = am.id_curso_escolar
     WHERE am.id_alumno = :id_alumno
     ORDER BY ce.curso_escolar DESC, me.orden ASC'
  );
  $attendance_stmt->execute(['id_alumno' => $id_alumno]);
  $attendance = $attendance_stmt->fetchAll();

  $practicas_stmt = $pdo->prepare(
    'SELECT pr.id_practica,
            pr.fecha_inicio,
            pr.fecha_fin,
            pr.fecha_fin_extra,
            pr.cancelada,
            pr.horas,
            pr.observaciones,
            e.nombre AS empresa,
            CASE
              WHEN pr.cancelada = 1 THEN "Cancelada"
              WHEN pr.fecha_inicio IS NULL OR pr.fecha_inicio = "" THEN ""
              WHEN COALESCE(NULLIF(pr.fecha_fin_extra, ""), pr.fecha_fin) IS NULL
                OR COALESCE(NULLIF(pr.fecha_fin_extra, ""), pr.fecha_fin) = "" THEN ""
              WHEN CURDATE() < pr.fecha_inicio THEN "En espera"
              WHEN CURDATE() <= COALESCE(NULLIF(pr.fecha_fin_extra, ""), pr.fecha_fin) THEN "En curso"
              ELSE "Finalizada"
            END AS practicas_estado,
            et.nombre AS tutor_nombre,
            et.apellido1 AS tutor_apellido1,
            et.apellido2 AS tutor_apellido2,
            et.dni AS tutor_dni,
            d.nombre_via,
            d.numero,
            d.bloque,
            d.escalera,
            d.planta,
            d.puerta,
            d.otros,
            d.cp AS direccion_cp,
            d.etiqueta AS direccion_etiqueta,
            v.via AS via_tipo,
            lp.nombre AS direccion_provincia,
            ll.nombre AS direccion_localidad,
            pa.pais AS direccion_pais
     FROM practicas pr
     LEFT JOIN empresas e ON e.id_empresa = pr.id_empresa
     LEFT JOIN empresas_tutores et ON et.id_empresas_tutor = pr.id_empresa_tutor
     LEFT JOIN direcciones d ON d.id_direccion = pr.id_direccion
     LEFT JOIN vias v ON v.id_via = d.id_via
     LEFT JOIN provincias lp ON lp.id_provincia = d.id_provincia
     LEFT JOIN localidades ll ON ll.id_localidad = d.id_localidad
     LEFT JOIN paises pa ON pa.id_pais = d.id_pais
     WHERE pr.id_alumno = :id_alumno
     ORDER BY pr.fecha_inicio DESC, pr.id_practica DESC'
  );
  $practicas_stmt->execute(['id_alumno' => $id_alumno]);
  $practicas = $practicas_stmt->fetchAll();

  $horario_stmt = $pdo->prepare(
    'SELECT ph.id_practica,
            ph.dia_semana,
            ph.hora_entrada,
            ph.hora_salida
     FROM practicas_horario ph
     INNER JOIN practicas pr ON pr.id_practica = ph.id_practica
     WHERE pr.id_alumno = :id_alumno
     ORDER BY pr.id_practica, ph.dia_semana, ph.hora_entrada'
  );
  $horario_stmt->execute(['id_alumno' => $id_alumno]);
  $horarios = $horario_stmt->fetchAll();

  $phones_stmt = $pdo->prepare(
    'SELECT telefono, etiqueta
     FROM telefonos
     WHERE entidad_tipo = :tipo AND id_entidad = :id_alumno
     ORDER BY etiqueta, telefono'
  );
  $phones_stmt->execute(['tipo' => 'alumno', 'id_alumno' => $id_alumno]);
  $phones = $phones_stmt->fetchAll();

  $emails_stmt = $pdo->prepare(
    'SELECT direccion_correo, etiqueta
     FROM correos
     WHERE entidad_tipo = :tipo AND id_entidad = :id_alumno
     ORDER BY etiqueta, direccion_correo'
  );
  $emails_stmt->execute(['tipo' => 'alumno', 'id_alumno' => $id_alumno]);
  $emails = $emails_stmt->fetchAll();
}

$telefono_principal = pick_contact_value($phones, 'telefono', ['principal', 'personal']);
$correo_educamadrid = null;
foreach ($emails as $email) {
  if (!isset($email['etiqueta'])) {
    continue;
  }
  if (strtolower((string) $email['etiqueta']) === 'educamadrid') {
    $correo_educamadrid = $email['direccion_correo'] ?? null;
    break;
  }
}
$correo_personal = pick_contact_value($emails, 'direccion_correo', ['personal']);
$grupo_actual = null;
if ($course_entries) {
  foreach ($course_entries as $course_entry) {
    if ($course_entry['activo']) {
      $grupo_actual = $course_entry['grupo'];
      break;
    }
  }
  if ($grupo_actual === null) {
    $grupo_actual = $course_entries[0]['grupo'];
  }
}

$apellido2 = $student && $student['apellido2'] ? ' ' . $student['apellido2'] : '';
$nombre_completo = $student
  ? sprintf('%s%s, %s', $student['apellido1'], $apellido2, $student['nombre'])
  : 'Alumno no encontrado';
$nombre_completo_con_grupo = $student && $grupo_actual
  ? $nombre_completo . ' - ' . $grupo_actual
  : $nombre_completo;

$page_title = $student
  ? sprintf('Ficha de %s | Gestor de Alumnos', $nombre_completo)
  : 'Alumno no encontrado | Gestor de Alumnos';
$active_page = 'alumnos';

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
          <p class="eyebrow">Ficha de alumno</p>
          <h1><?php echo htmlspecialchars($nombre_completo_con_grupo, ENT_QUOTES, 'UTF-8'); ?></h1>
          <p class="subheading">Consulta el detalle académico, personal y administrativo asociado al alumno.</p>
        </div>
        <div class="header-actions">
          <a class="primary-button" href="alumno_editar.php?id=<?php echo (int) $id_alumno; ?>">Editar alumno</a>
          <a class="ghost-button" href="alumnos.php">Volver al listado</a>
        </div>
      </header>

      <?php if ($student && isset($_GET['updated']) && $_GET['updated'] === '1'): ?>
        <p class="status">Los cambios del alumno se han guardado correctamente.</p>
      <?php endif; ?>

      <?php if (!$student): ?>
        <section class="panel">
          <div class="panel-header">
            <h3>Alumno no encontrado</h3>
            <p>No hemos podido localizar el alumno solicitado. Comprueba el enlace o vuelve al listado.</p>
          </div>
        </section>
      <?php else: ?>
        <div class="grid">
          <section class="panel">
            <div class="panel-header">
              <h3>Datos personales y contacto</h3>
              <p>Información básica de identificación y contacto del alumno.</p>
            </div>
            <div class="panel-grid">
              <table class="panel-table-aligned">
                <tbody>
                  <tr>
                    <th>NIA</th>
                    <td><?php echo htmlspecialchars(format_value($student['nia']), ENT_QUOTES, 'UTF-8'); ?></td>
                  </tr>
                  <tr>
                    <th>DNI</th>
                    <td><?php echo htmlspecialchars(format_value($student['dni']), ENT_QUOTES, 'UTF-8'); ?></td>
                  </tr>
                  <tr>
                    <th>Nº Seguridad Social</th>
                    <td><?php echo htmlspecialchars(format_value($student['seg_soc']), ENT_QUOTES, 'UTF-8'); ?></td>
                  </tr>
                  <tr>
                    <th>Fecha de nacimiento</th>
                    <td>
                      <?php
                        $fecha_nacimiento = format_date($student['fecha_nacimiento']);
                        $edad_alumno = calculate_age($student['fecha_nacimiento']);
                        $edad_texto = $edad_alumno !== null ? ' (' . $edad_alumno . ' años)' : '';
                      ?>
                      <?php echo htmlspecialchars($fecha_nacimiento . $edad_texto, ENT_QUOTES, 'UTF-8'); ?>
                    </td>
                  </tr>
                  <tr>
                    <th>Teléfono principal</th>
                    <td><?php echo htmlspecialchars(format_value($telefono_principal), ENT_QUOTES, 'UTF-8'); ?></td>
                  </tr>
                  <tr>
                    <th>Email EducaMadrid</th>
                    <td><?php echo htmlspecialchars(format_value($correo_educamadrid), ENT_QUOTES, 'UTF-8'); ?></td>
                  </tr>
                  <tr>
                    <th>Email personal</th>
                    <td><?php echo htmlspecialchars(format_value($correo_personal), ENT_QUOTES, 'UTF-8'); ?></td>
                  </tr>
                  <tr>
                    <th>Teléfonos registrados</th>
                    <td>
                      <?php
                        $telefono_items = [];
                        foreach ($phones as $phone) {
                          $etiqueta = format_value($phone['etiqueta'], '');
                          $telefono_label = $etiqueta ? $etiqueta . ': ' : '';
                          $telefono_items[] = $telefono_label . $phone['telefono'];
                        }
                      ?>
                      <?php echo htmlspecialchars($telefono_items ? implode(' · ', $telefono_items) : 'No disponible', ENT_QUOTES, 'UTF-8'); ?>
                    </td>
                  </tr>
                  <tr>
                    <th>Correos registrados</th>
                    <td>
                      <?php
                        $correo_items = [];
                        foreach ($emails as $email) {
                          $etiqueta = format_value($email['etiqueta'], '');
                          $correo_label = $etiqueta ? $etiqueta . ': ' : '';
                          $correo_items[] = $correo_label . $email['direccion_correo'];
                        }
                      ?>
                      <?php echo htmlspecialchars($correo_items ? implode(' · ', $correo_items) : 'No disponible', ENT_QUOTES, 'UTF-8'); ?>
                    </td>
                  </tr>
                  <tr>
                    <th>Horas FFE aprobadas</th>
                    <td><?php echo htmlspecialchars(format_value($student['horas_ffe_aprobadas']), ENT_QUOTES, 'UTF-8'); ?></td>
                  </tr>
                  <tr>
                    <th>Repite curso</th>
                    <td><?php echo htmlspecialchars(format_bool($student['repite_curso']), ENT_QUOTES, 'UTF-8'); ?></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </section>

          <section class="panel">
            <div class="panel-header">
              <h3>Tutores legales</h3>
              <p>Datos de contacto de las personas tutoras asociadas al alumno.</p>
            </div>
            <div class="panel-grid">
              <table class="panel-table-aligned">
                <tbody>
                  <tr>
                    <th>Tutor/a 1</th>
                    <td><?php echo htmlspecialchars(format_value($student['nombre_tutor1']), ENT_QUOTES, 'UTF-8'); ?></td>
                  </tr>
                  <tr>
                    <th>Teléfono tutor/a 1</th>
                    <td><?php echo htmlspecialchars(format_value($student['telefono_tutor1']), ENT_QUOTES, 'UTF-8'); ?></td>
                  </tr>
                  <tr>
                    <th>Correo tutor/a 1</th>
                    <td><?php echo htmlspecialchars(format_value($student['correo_tutor1']), ENT_QUOTES, 'UTF-8'); ?></td>
                  </tr>
                  <tr>
                    <th>Tutor/a 2</th>
                    <td><?php echo htmlspecialchars(format_value($student['nombre_tutor2']), ENT_QUOTES, 'UTF-8'); ?></td>
                  </tr>
                  <tr>
                    <th>Teléfono tutor/a 2</th>
                    <td><?php echo htmlspecialchars(format_value($student['telefono_tutor2']), ENT_QUOTES, 'UTF-8'); ?></td>
                  </tr>
                  <tr>
                    <th>Correo tutor/a 2</th>
                    <td><?php echo htmlspecialchars(format_value($student['correo_tutor2']), ENT_QUOTES, 'UTF-8'); ?></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </section>
        </div>

        <div class="grid">
          <section class="panel">
            <div class="panel-header">
              <h3>Dirección y localización</h3>
              <p>Provincia, localidad y código postal registrados para el alumno.</p>
            </div>
            <div class="panel-grid">
              <table class="panel-table-aligned">
                <tbody>
                  <tr>
                    <th>Provincia</th>
                    <td><?php echo htmlspecialchars(format_value($student['provincia']), ENT_QUOTES, 'UTF-8'); ?></td>
                  </tr>
                  <tr>
                    <th>Localidad</th>
                    <td><?php echo htmlspecialchars(format_value($student['localidad']), ENT_QUOTES, 'UTF-8'); ?></td>
                  </tr>
                  <tr>
                    <th>Código postal</th>
                    <td><?php echo htmlspecialchars(format_value($student['cp']), ENT_QUOTES, 'UTF-8'); ?></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </section>

          <section class="panel">
            <div class="panel-header">
              <h3>Faltas y observaciones</h3>
              <p>Resumen de faltas acumuladas y comentarios del equipo docente.</p>
            </div>
            <div class="panel-grid">
              <table class="panel-table-aligned">
                <tbody>
                  <tr>
                    <th>Faltas 10 días</th>
                    <td>
                      <?php echo htmlspecialchars(format_date($student['faltas_10_dia']), ENT_QUOTES, 'UTF-8'); ?>
                      (<?php echo htmlspecialchars(format_value($student['faltas_10_cantidad'], '0'), ENT_QUOTES, 'UTF-8'); ?>)
                    </td>
                  </tr>
                  <tr>
                    <th>Faltas 15 días</th>
                    <td>
                      <?php echo htmlspecialchars(format_date($student['faltas_15_dia']), ENT_QUOTES, 'UTF-8'); ?>
                      (<?php echo htmlspecialchars(format_value($student['faltas_15_cantidad'], '0'), ENT_QUOTES, 'UTF-8'); ?>)
                    </td>
                  </tr>
                  <tr>
                    <th>Comentarios</th>
                    <td><?php echo htmlspecialchars(format_value($student['comentarios']), ENT_QUOTES, 'UTF-8'); ?></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </section>
        </div>

        <section class="panel">
          <div class="panel-header">
            <h3>Matriculación</h3>
            <p>Histórico de cursos, niveles y grupos en los que está inscrito.</p>
          </div>
          <div class="panel-grid">
            <table>
              <thead>
                <tr>
                  <th>Curso escolar</th>
                  <th>Nivel</th>
                  <th>Ciclo</th>
                  <th>Curso</th>
                  <th>Grupo</th>
                  <th>Activo</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!$course_entries): ?>
                  <tr>
                    <td colspan="6">No hay matrículas registradas.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($course_entries as $course): ?>
                    <?php
                      $ciclo_label = $course['ciclo'] ?: 'No disponible';
                      if ($course['abreviatura']) {
                        $ciclo_label .= ' (' . $course['abreviatura'] . ')';
                      }
                      $activo = $course['activo'] ? 'Sí' : 'No';
                    ?>
                    <tr>
                      <td><?php echo htmlspecialchars(format_value($course['curso_escolar']), ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars(format_value($course['nivel']), ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars($ciclo_label, ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars(format_value($course['curso']), ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars(format_value($course['grupo']), ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars($activo, ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>

        <section class="panel">
          <div class="panel-header">
            <h3>Módulos asociados</h3>
            <p>Materias vinculadas a la matrícula del alumno.</p>
          </div>
          <div class="panel-grid">
            <table>
              <thead>
                <tr>
                  <th>Módulo</th>
                  <th>Código</th>
                  <th>Curso</th>
                  <th>Horas</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!$modules): ?>
                  <tr>
                    <td colspan="4">No hay módulos asociados.</td>
                  </tr>
                <?php else: ?>
                  <?php
                    $total_horas_semanales = 0;
                    $total_horas_totales = 0;
                  ?>
                  <?php foreach ($modules as $module): ?>
                    <?php
                      $nombre_modulo = trim($module['materia_general'] . ' ' . $module['materia_propia']);
                      $curso_numero = $module['curso'];
                      $curso_label = $curso_numero ? $curso_numero : 'No disponible';
                      $horas_semanales = is_numeric($module['horas_semanales']) ? (int) $module['horas_semanales'] : 0;
                      $horas_totales = is_numeric($module['horas_totales']) ? (int) $module['horas_totales'] : 0;
                      $total_horas_semanales += $horas_semanales;
                      $total_horas_totales += $horas_totales;
                      $horas = sprintf(
                        '%s semanales / %s totales',
                        format_value((string) $horas_semanales, '0'),
                        format_value((string) $horas_totales, '0')
                      );
                    ?>
                    <tr>
                      <td><?php echo htmlspecialchars($nombre_modulo ?: 'No disponible', ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars(format_value($module['codigo']), ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars($curso_label, ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars($horas, ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                  <?php endforeach; ?>
                  <tr>
                    <th colspan="3">Total horas matriculadas</th>
                    <td>
                      <?php
                        $total_horas = sprintf(
                          '%s semanales / %s totales',
                          format_value((string) $total_horas_semanales, '0'),
                          format_value((string) $total_horas_totales, '0')
                        );
                      ?>
                      <?php echo htmlspecialchars($total_horas, ENT_QUOTES, 'UTF-8'); ?>
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>

        <section class="panel">
          <div class="panel-header">
            <h3>Asistencia mensual</h3>
            <p>Faltas justificadas, injustificadas y retrasos por mes.</p>
          </div>
          <div class="panel-grid">
            <table>
              <thead>
                <tr>
                  <th>Curso escolar</th>
                  <th>Mes</th>
                  <th>Faltas justificadas</th>
                  <th>Faltas injustificadas</th>
                  <th>Retrasos</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!$attendance): ?>
                  <tr>
                    <td colspan="5">No hay datos de asistencia.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($attendance as $row): ?>
                    <tr>
                      <td><?php echo htmlspecialchars(format_value($row['curso_escolar']), ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars(format_value($row['mes']), ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars((string) $row['faltas_justificadas'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars((string) $row['faltas_injustificadas'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars((string) $row['retrasos'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>

        <section class="panel">
          <div class="panel-header">
            <h3>Prácticas</h3>
            <p>Empresas, fechas, estado y horario de las prácticas del alumno.</p>
          </div>
          <div class="panel-grid">
            <table>
              <thead>
                <tr>
                  <th>Empresa</th>
                  <th>Estado</th>
                  <th>Fechas</th>
                  <th>Horas</th>
                  <th>Tutor empresa</th>
                  <th>Dirección</th>
                  <th>Observaciones</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!$practicas): ?>
                  <tr>
                    <td colspan="7">No hay prácticas registradas.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($practicas as $practica): ?>
                    <?php
                      $fechas = trim(
                        sprintf(
                          '%s - %s',
                          format_date($practica['fecha_inicio'], 'Sin inicio'),
                          format_date($practica['fecha_fin'], 'Sin fin')
                        )
                      );
                      $tutor_nombre = trim(
                        sprintf(
                          '%s %s %s',
                          $practica['tutor_nombre'] ?? '',
                          $practica['tutor_apellido1'] ?? '',
                          $practica['tutor_apellido2'] ?? ''
                        )
                      );
                      $direccion_partes = array_filter([
                        $practica['via_tipo'] ? $practica['via_tipo'] : null,
                        $practica['nombre_via'] ? $practica['nombre_via'] : null,
                        $practica['numero'] ? 'Nº ' . $practica['numero'] : null,
                        $practica['bloque'] ? 'Bloque ' . $practica['bloque'] : null,
                        $practica['escalera'] ? 'Esc. ' . $practica['escalera'] : null,
                        $practica['planta'] ? 'Planta ' . $practica['planta'] : null,
                        $practica['puerta'] ? 'Puerta ' . $practica['puerta'] : null,
                        $practica['otros'] ? $practica['otros'] : null,
                        $practica['direccion_cp'] ? 'CP ' . $practica['direccion_cp'] : null,
                        $practica['direccion_localidad'] ? $practica['direccion_localidad'] : null,
                        $practica['direccion_provincia'] ? $practica['direccion_provincia'] : null,
                        $practica['direccion_pais'] ? $practica['direccion_pais'] : null,
                      ]);
                    ?>
                    <tr>
                      <td><?php echo htmlspecialchars(format_value($practica['empresa']), ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars(format_value($practica['practicas_estado']), ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars($fechas, ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars((string) $practica['horas'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td>
                        <?php echo htmlspecialchars($tutor_nombre ?: 'No disponible', ENT_QUOTES, 'UTF-8'); ?>
                        <?php if ($practica['tutor_dni']): ?>
                          (<?php echo htmlspecialchars($practica['tutor_dni'], ENT_QUOTES, 'UTF-8'); ?>)
                        <?php endif; ?>
                      </td>
                      <td><?php echo htmlspecialchars($direccion_partes ? implode(' · ', $direccion_partes) : 'No disponible', ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars(format_value($practica['observaciones']), ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
          <div class="panel-grid">
            <table>
              <thead>
                <tr>
                  <th>ID práctica</th>
                  <th>Día</th>
                  <th>Hora entrada</th>
                  <th>Hora salida</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!$horarios): ?>
                  <tr>
                    <td colspan="4">No hay horarios de prácticas registrados.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($horarios as $horario): ?>
                    <tr>
                      <td><?php echo htmlspecialchars((string) $horario['id_practica'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars($dias_semana[(int) $horario['dia_semana']] ?? 'No disponible', ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars($horario['hora_entrada'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars($horario['hora_salida'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>
      <?php endif; ?>
    </main>
  </div>
</body>
</html>
