<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/practicas_pdfs.php';

function format_student_name(array $row): string
{
  $apellido1 = trim((string) ($row['alumno_apellido1'] ?? ''));
  $apellido2 = trim((string) ($row['alumno_apellido2'] ?? ''));
  $nombre = trim((string) ($row['alumno_nombre'] ?? ''));

  $apellidos = trim(implode(' ', array_filter([
    $apellido1,
    $apellido2,
  ], static fn (string $value): bool => $value !== '')));

  if ($apellidos === '' && $nombre === '') {
    return 'No disponible';
  }

  if ($apellidos === '') {
    return $nombre;
  }

  if ($nombre === '') {
    return $apellidos;
  }

  return $apellidos . ', ' . $nombre;
}

function format_date_es(?string $value): string
{
  $value = trim((string) $value);
  if ($value === '') {
    return 'No disponible';
  }

  $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);
  return $date ? $date->format('d/m/Y') : $value;
}

function calculate_practice_status(array $practice): string
{
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

function build_order_url(string $order): string
{
  $params = $_GET;
  $params['orden'] = $order;

  $query = http_build_query($params);

  return 'practicas_documentacion.php' . ($query !== '' ? '?' . $query : '');
}

function run_generator_script(string $scriptName, int $practiceId, ?string &$commandOutput = null): bool
{
  $scriptPath = __DIR__ . '/' . $scriptName;
  if (!is_file($scriptPath)) {
    return false;
  }

  $bootstrapCode = sprintf(
    '$_GET["id_practica"]=%d;$_SERVER["HTTP_HOST"]="localhost";$_SERVER["HTTP_REFERER"]="practicas_documentacion.php";require %s;',
    $practiceId,
    var_export($scriptPath, true)
  );

  $command = escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($bootstrapCode);
  $output = [];
  $exitCode = 1;
  exec($command . ' 2>&1', $output, $exitCode);
  $commandOutput = trim(implode("\n", $output));

  return $exitCode === 0;
}

$page_title = 'Documentación de prácticas | Gestor de Alumnos';
$active_page = 'utilidades';

$load_error = null;
$active_course_id = null;
$practices = [];
$generated_documents = [];
$generation_errors = [];
$generation_summary = null;
$allowed_orders = ['alumno', 'empresa', 'anexo', 'fecha_inicio', 'fecha_fin', 'estado'];
$order_param = (string) ($_GET['orden'] ?? 'alumno');
$current_order = in_array($order_param, $allowed_orders, true) ? $order_param : 'alumno';

try {
  $pdo = db();
  $active_course_id = $pdo->query('SELECT id_curso_escolar FROM cursos_escolares WHERE activo = 1 LIMIT 1')->fetchColumn();

  if ($_SERVER['REQUEST_METHOD'] === 'POST' && $active_course_id !== false && $active_course_id !== null) {
    $selected_programa = $_POST['generar_programa'] ?? [];
    $selected_calendario = $_POST['generar_calendario'] ?? [];

    if (!is_array($selected_programa)) {
      $selected_programa = [];
    }
    if (!is_array($selected_calendario)) {
      $selected_calendario = [];
    }

    $selected_ids = array_unique(array_map(
      static fn (string $id): int => (int) $id,
      array_merge(array_keys($selected_programa), array_keys($selected_calendario))
    ));
    $selected_ids = array_values(array_filter($selected_ids, static fn (int $id): bool => $id > 0));

    if ($selected_ids !== []) {
      $placeholders = implode(', ', array_fill(0, count($selected_ids), '?'));
      $selected_stmt = $pdo->prepare(
        'SELECT DISTINCT
          p.id_practica,
          p.anexo,
          p.fecha_inicio,
          p.fecha_fin,
          p.fecha_fin_extra,
          a.nombre AS alumno_nombre,
          a.apellido1 AS alumno_apellido1,
          a.apellido2 AS alumno_apellido2,
          e.convenio AS empresa_convenio,
          e.nombre AS empresa_nombre,
          e.nombre_comercial AS empresa_nombre_comercial
        FROM practicas p
        INNER JOIN alumnos a ON a.id_alumno = p.id_alumno
        INNER JOIN empresas e ON e.id_empresa = p.id_empresa
        INNER JOIN alumno_curso ac ON ac.id_alumno = p.id_alumno
        WHERE ac.id_curso_escolar = ?
          AND p.id_practica IN (' . $placeholders . ')'
      );

      $selected_stmt->execute(array_merge([(int) $active_course_id], $selected_ids));
      $selected_practices = $selected_stmt->fetchAll();
      $selected_practices_by_id = [];
      foreach ($selected_practices as $practice) {
        $selected_practices_by_id[(int) ($practice['id_practica'] ?? 0)] = $practice;
      }

      foreach ($selected_ids as $id_practica) {
        if (!isset($selected_practices_by_id[$id_practica])) {
          $generation_errors[] = 'No se pudo recuperar la información necesaria para la práctica #' . $id_practica . '.';
          continue;
        }

        $practice = $selected_practices_by_id[$id_practica];
        $paths = practicas_get_document_paths($practice);
        $practice_for_calendar_paths = $practice;
        unset($practice_for_calendar_paths['empresa_nombre_comercial']);
        $calendar_paths = practicas_get_document_paths($practice_for_calendar_paths);

        if (isset($selected_programa[(string) $id_practica])) {
          $missing_plan_fields = [];
          $required_plan_fields = [
            'alumno_nombre' => 'nombre del alumno',
            'alumno_apellido1' => 'primer apellido del alumno',
            'empresa_nombre' => 'nombre de la empresa',
            'empresa_convenio' => 'número de convenio',
            'anexo' => 'número de anexo',
            'fecha_inicio' => 'fecha de inicio',
          ];
          foreach ($required_plan_fields as $field => $label) {
            if (trim((string) ($practice[$field] ?? '')) === '') {
              $missing_plan_fields[] = $label;
            }
          }
          $end_date = trim((string) ($practice['fecha_fin_extra'] ?? ''));
          if ($end_date === '') {
            $end_date = trim((string) ($practice['fecha_fin'] ?? ''));
          }
          if ($end_date === '') {
            $missing_plan_fields[] = 'fecha de fin';
          }

          if ($missing_plan_fields !== []) {
            $generation_errors[] = 'No se pudo generar el Plan Formación para la práctica #' . $id_practica . '.';
            continue;
          }

          $before_mtime = is_file($paths['plan_file_path']) ? (int) filemtime($paths['plan_file_path']) : 0;
          $script_output = null;
          $executed = run_generator_script('generar_plan_formacion.php', $id_practica, $script_output);
          clearstatcache(true, $paths['plan_file_path']);
          $after_mtime = is_file($paths['plan_file_path']) ? (int) filemtime($paths['plan_file_path']) : 0;

          if ($executed && is_file($paths['plan_file_path']) && ($before_mtime === 0 || $after_mtime >= $before_mtime)) {
            $generated_documents[] = 'Plan Formación - ' . $paths['plan_file_name'];
          } else {
            $generation_errors[] = 'No se pudo generar el Plan Formación para la práctica #' . $id_practica . '.';
          }
        }

        if (isset($selected_calendario[(string) $id_practica])) {
          $start_date = trim((string) ($practice['fecha_inicio'] ?? ''));
          $end_date = trim((string) ($practice['fecha_fin_extra'] ?? ''));
          if ($end_date === '') {
            $end_date = trim((string) ($practice['fecha_fin'] ?? ''));
          }

          if ($start_date === '' || $end_date === '') {
            $generation_errors[] = 'No se pudo generar el Calendario para la práctica #' . $id_practica . '.';
            continue;
          }

          $before_mtime = is_file($calendar_paths['calendar_file_path']) ? (int) filemtime($calendar_paths['calendar_file_path']) : 0;
          $script_output = null;
          $executed = run_generator_script('generar_calendario.php', $id_practica, $script_output);
          clearstatcache(true, $calendar_paths['calendar_file_path']);
          $after_mtime = is_file($calendar_paths['calendar_file_path']) ? (int) filemtime($calendar_paths['calendar_file_path']) : 0;

          if ($executed && is_file($calendar_paths['calendar_file_path']) && ($before_mtime === 0 || $after_mtime >= $before_mtime)) {
            $generated_documents[] = 'Calendario - ' . $calendar_paths['calendar_file_name'];
          } else {
            $generation_errors[] = 'No se pudo generar el Calendario para la práctica #' . $id_practica . '.';
          }
        }
      }

      if ($generated_documents !== []) {
        $generation_summary = 'La documentación se ha generado correctamente.';
      } elseif ($generation_errors === []) {
        $generation_errors[] = 'No se ha generado ningún documento.';
      }
    } else {
      $generation_errors[] = 'Selecciona al menos una opción de documentación antes de generar.';
    }
  }

  if ($active_course_id !== false && $active_course_id !== null) {
    $order_clause = match ($current_order) {
      'empresa' => 'ORDER BY e.nombre ASC, p.id_practica ASC',
      'anexo' => 'ORDER BY CAST(p.anexo AS UNSIGNED) ASC, p.id_practica ASC',
      'fecha_inicio' => 'ORDER BY p.fecha_inicio ASC, p.id_practica ASC',
      'fecha_fin' => 'ORDER BY p.fecha_fin ASC, p.id_practica ASC',
      'estado' => "ORDER BY CASE
        WHEN p.cancelada = 1 THEN 1
        WHEN p.fecha_inicio IS NULL OR p.fecha_inicio = '' OR p.fecha_fin_extra IS NULL OR p.fecha_fin_extra = '' THEN 2
        WHEN CURDATE() < p.fecha_inicio THEN 3
        WHEN CURDATE() <= p.fecha_fin_extra THEN 4
        ELSE 5
      END ASC, p.id_practica ASC",
      default => 'ORDER BY a.apellido1 ASC, a.apellido2 ASC, a.nombre ASC, p.id_practica ASC',
    };

    $practices_stmt = $pdo->prepare(
      'SELECT DISTINCT
        p.id_practica,
        p.anexo,
        p.fecha_inicio,
        p.fecha_fin,
        p.fecha_fin_extra,
        p.cancelada,
        a.nombre AS alumno_nombre,
        a.apellido1 AS alumno_apellido1,
        a.apellido2 AS alumno_apellido2,
        e.nombre AS empresa_nombre,
        e.apellido1 AS empresa_apellido1,
        e.apellido2 AS empresa_apellido2,
        e.nombre_comercial AS empresa_nombre_comercial
      FROM practicas p
      INNER JOIN alumnos a ON a.id_alumno = p.id_alumno
      INNER JOIN empresas e ON e.id_empresa = p.id_empresa
      INNER JOIN alumno_curso ac ON ac.id_alumno = p.id_alumno
      WHERE ac.id_curso_escolar = :active_course_id
      ' . $order_clause
    );

    $practices_stmt->execute(['active_course_id' => $active_course_id]);
    $practices = $practices_stmt->fetchAll();
  }
} catch (Throwable $error) {
  $load_error = 'No se ha podido cargar la documentación de prácticas en este momento.';
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
          <h1>Documentación de prácticas</h1>
          <p class="subheading">Genera el Plan Formación y/o el Calendario para las prácticas del curso actual.</p>
        </div>
      </header>

      <section class="panel">
        <div class="panel-header">
          <h3>Listado del curso actual</h3>
          <p>Selecciona los documentos que quieras generar para cada práctica.</p>
        </div>

        <div class="panel-grid">
          <?php if ($generation_summary !== null): ?>
            <p><?php echo htmlspecialchars($generation_summary, ENT_QUOTES, 'UTF-8'); ?></p>
          <?php endif; ?>

          <?php if ($generated_documents !== []): ?>
            <ul>
              <?php foreach ($generated_documents as $generated_document): ?>
                <li><?php echo htmlspecialchars($generated_document, ENT_QUOTES, 'UTF-8'); ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>

          <?php if ($generation_errors !== []): ?>
            <ul>
              <?php foreach ($generation_errors as $generation_error): ?>
                <li><?php echo htmlspecialchars($generation_error, ENT_QUOTES, 'UTF-8'); ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>

          <form method="post" action="practicas_documentacion.php">
            <table>
              <thead>
                <tr>
                  <th><a class="practice-link" href="<?php echo htmlspecialchars(build_order_url('alumno'), ENT_QUOTES, 'UTF-8'); ?>">Alumno</a></th>
                  <th><a class="practice-link" href="<?php echo htmlspecialchars(build_order_url('empresa'), ENT_QUOTES, 'UTF-8'); ?>">Empresa</a></th>
                  <th><a class="practice-link" href="<?php echo htmlspecialchars(build_order_url('anexo'), ENT_QUOTES, 'UTF-8'); ?>">Anexo</a></th>
                  <th><a class="practice-link" href="<?php echo htmlspecialchars(build_order_url('fecha_inicio'), ENT_QUOTES, 'UTF-8'); ?>">Fecha inicio</a></th>
                  <th><a class="practice-link" href="<?php echo htmlspecialchars(build_order_url('fecha_fin'), ENT_QUOTES, 'UTF-8'); ?>">Fecha fin</a></th>
                  <th><a class="practice-link" href="<?php echo htmlspecialchars(build_order_url('estado'), ENT_QUOTES, 'UTF-8'); ?>">Estado</a></th>
                  <th>
                    Plan Formación
                    <input type="checkbox" id="select-all-programa" aria-label="Seleccionar o deseleccionar todos los Plan Formación">
                  </th>
                  <th>
                    Calendario
                    <input type="checkbox" id="select-all-calendario" aria-label="Seleccionar o deseleccionar todos los Calendarios">
                  </th>
                </tr>
              </thead>
              <tbody>
                <?php if ($load_error !== null): ?>
                  <tr>
                    <td colspan="8"><?php echo htmlspecialchars($load_error, ENT_QUOTES, 'UTF-8'); ?></td>
                  </tr>
                <?php elseif ($active_course_id === false || $active_course_id === null): ?>
                  <tr>
                    <td colspan="8">No hay un curso activo configurado.</td>
                  </tr>
                <?php elseif ($practices === []): ?>
                  <tr>
                    <td colspan="8">No hay prácticas registradas para el curso actual.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($practices as $practice): ?>
                    <?php
                      $practice_id = (int) $practice['id_practica'];
                      $nombreCompleto = trim(implode(' ', array_filter([
                        $practice['empresa_nombre'] ?? '',
                        $practice['empresa_apellido1'] ?? '',
                        $practice['empresa_apellido2'] ?? ''
                      ], static fn ($value) => trim((string) $value) !== '')));
                      $nombreComercial = trim((string) ($practice['empresa_nombre_comercial'] ?? ''));
                      $nombreApellido1 = trim(implode(' ', array_filter([
                        $practice['empresa_nombre'] ?? '',
                        $practice['empresa_apellido1'] ?? ''
                      ], static fn ($value) => trim((string) $value) !== '')));
                      $empresaNombreMostrado = $nombreCompleto !== '' ? $nombreCompleto : 'No disponible';
                      if ($nombreComercial !== '' && $nombreComercial !== $nombreCompleto) {
                        $empresaNombreMostrado = $nombreComercial;
                        if ($nombreApellido1 !== '') {
                          $empresaNombreMostrado .= ' (' . $nombreApellido1 . ')';
                        }
                      }
                    ?>
                    <tr>
                      <td>
                        <a class="practice-link" href="practica_detalle.php?id_practica=<?php echo urlencode((string) $practice_id); ?>">
                          <?php echo htmlspecialchars(format_student_name($practice), ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                      </td>
                      <td><?php echo htmlspecialchars($empresaNombreMostrado, ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars((string) ($practice['anexo'] ?? 'No disponible'), ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars(format_date_es($practice['fecha_inicio'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars(format_date_es($practice['fecha_fin'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars(calculate_practice_status($practice), ENT_QUOTES, 'UTF-8'); ?></td>
                      <td>
                        <input type="checkbox" name="generar_programa[<?php echo $practice_id; ?>]" value="1">
                      </td>
                      <td>
                        <input type="checkbox" name="generar_calendario[<?php echo $practice_id; ?>]" value="1">
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>

            <div class="form-actions">
              <button type="submit" class="primary-button">Generar Documentación</button>
            </div>
          </form>
        </div>
      </section>
    </main>
  </div>

  <script>
    const selectAllPrograma = document.getElementById('select-all-programa');
    const selectAllCalendario = document.getElementById('select-all-calendario');

    if (selectAllPrograma) {
      selectAllPrograma.addEventListener('change', () => {
        document.querySelectorAll('input[name^="generar_programa["]').forEach((checkbox) => {
          checkbox.checked = selectAllPrograma.checked;
        });
      });
    }

    if (selectAllCalendario) {
      selectAllCalendario.addEventListener('change', () => {
        document.querySelectorAll('input[name^="generar_calendario["]').forEach((checkbox) => {
          checkbox.checked = selectAllCalendario.checked;
        });
      });
    }
  </script>
</body>
</html>
