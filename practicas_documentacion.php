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

function build_order_url(string $col, string $cur_col, string $cur_dir): string
{
  $params = $_GET;
  $params['orden'] = $col . '_' . (($col === $cur_col && $cur_dir === 'asc') ? 'desc' : 'asc');
  $query = http_build_query($params);
  return 'practicas_documentacion.php' . ($query !== '' ? '?' . $query : '');
}
function sort_ind_doc(string $col, string $cur_col, string $cur_dir): string
{
  if ($col !== $cur_col) return '';
  return $cur_dir === 'asc' ? ' ▲' : ' ▼';
}

function build_generator_url(string $scriptRelativePath): ?string
{
  $scheme = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') ? 'https' : 'http';
  $host = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
  if ($host === '') {
    return null;
  }

  $basePath = trim((string) dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '')), '/');
  $encodedPath = implode('/', array_map('rawurlencode', explode('/', str_replace('\\', '/', $scriptRelativePath))));

  return $scheme . '://' . $host . '/'
    . ($basePath !== '' ? $basePath . '/' : '')
    . $encodedPath;
}

function run_generator_script(string $scriptName, int $practiceId, ?string &$commandOutput = null): bool
{
  $scriptPath = __DIR__ . '/' . $scriptName;
  if (!is_file($scriptPath)) {
    return false;
  }

  $scheme = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') ? 'https' : 'http';
  $host = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
  $basePath = trim((string) dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '')), '/');

  if ($host !== '') {
    $encodedScriptPath = implode('/', array_map('rawurlencode', explode('/', str_replace('\\', '/', $scriptName))));
    $url = $scheme . '://' . $host . '/'
      . ($basePath !== '' ? $basePath . '/' : '')
      . $encodedScriptPath
      . '?id_practica=' . $practiceId;

    $headers = [];
    if (!empty($_COOKIE)) {
      $cookiePairs = [];
      foreach ($_COOKIE as $cookieName => $cookieValue) {
        $cookiePairs[] = rawurlencode((string) $cookieName) . '=' . rawurlencode((string) $cookieValue);
      }
      if ($cookiePairs !== []) {
        $headers[] = 'Cookie: ' . implode('; ', $cookiePairs);
      }
    }

    $context = stream_context_create([
      'http' => [
        'method' => 'GET',
        'header' => $headers,
        'ignore_errors' => true,
        'timeout' => 30,
      ],
    ]);

    $responseBody = @file_get_contents($url, false, $context);
    $responseHeaders = $http_response_header ?? [];
    $locationHeader = '';
    foreach ($responseHeaders as $headerLine) {
      if (stripos($headerLine, 'Location:') === 0) {
        $locationHeader = trim((string) substr($headerLine, 9));
      }
    }

    if ($locationHeader !== '') {
      $locationParts = parse_url($locationHeader);
      if ($locationParts !== false && isset($locationParts['query'])) {
        $locationQuery = [];
        parse_str($locationParts['query'], $locationQuery);
        if (isset($locationQuery['doc_status']) && trim((string) $locationQuery['doc_status']) !== '') {
          $commandOutput = $locationHeader;
          return true;
        }

        if (isset($locationQuery['doc_error'])) {
          $commandOutput = (string) $locationQuery['doc_error'];
        } else {
          $commandOutput = $locationHeader;
        }
      }
      // Si la respuesta HTTP no confirma estado de éxito,
      // intentamos también la ejecución por CLI como fallback.
    }

    if ($responseBody !== false) {
      $commandOutput = trim((string) $responseBody);
    }
  }

  $bootstrapCode = sprintf(
    '$_GET["id_practica"]=%d;$_REQUEST["id_practica"]=%d;$_SERVER["REQUEST_METHOD"]="GET";$_SERVER["HTTP_HOST"]="localhost";$_SERVER["HTTP_REFERER"]="http://localhost/practicas_documentacion.php";require %s;',
    $practiceId,
    $practiceId,
    var_export($scriptPath, true)
  );

  $command = escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($bootstrapCode);
  $output = [];
  $exitCode = 1;
  exec($command . ' 2>&1', $output, $exitCode);
  if ($commandOutput === null || $commandOutput === '') {
    $commandOutput = trim(implode("\n", $output));
  }

  return $exitCode === 0;
}

function fetch_generator_binary_response(string $scriptRelativePath, int $practiceId, ?string &$responseBody = null, array &$responseHeaders = [], ?string &$errorMessage = null): bool
{
  $url = build_generator_url($scriptRelativePath);
  if ($url === null) {
    $errorMessage = 'No se pudo determinar la URL interna del generador.';
    return false;
  }

  $url .= '?id_practica=' . $practiceId;

  $headers = [];
  if (!empty($_COOKIE)) {
    $cookiePairs = [];
    foreach ($_COOKIE as $cookieName => $cookieValue) {
      $cookiePairs[] = rawurlencode((string) $cookieName) . '=' . rawurlencode((string) $cookieValue);
    }
    if ($cookiePairs !== []) {
      $headers[] = 'Cookie: ' . implode('; ', $cookiePairs);
    }
  }

  $context = stream_context_create([
    'http' => [
      'method' => 'GET',
      'header' => $headers,
      'ignore_errors' => true,
      'timeout' => 60,
    ],
  ]);

  $body = @file_get_contents($url, false, $context);
  $capturedHeaders = $http_response_header ?? [];
  $responseHeaders = is_array($capturedHeaders) ? $capturedHeaders : [];
  $responseBody = $body !== false ? $body : null;

  $statusCode = 0;
  foreach ($responseHeaders as $headerLine) {
    if (preg_match('#^HTTP/\S+\s+(\d{3})#', (string) $headerLine, $matches) === 1) {
      $statusCode = (int) $matches[1];
      break;
    }
  }

  if ($statusCode < 200 || $statusCode >= 300) {
    $errorMessage = 'El generador devolvió un estado HTTP no válido.';
    return false;
  }

  if ($body === false || $body === '') {
    $errorMessage = 'El generador devolvió un contenido vacío.';
    return false;
  }

  $contentType = '';
  foreach ($responseHeaders as $headerLine) {
    if (stripos((string) $headerLine, 'Content-Type:') === 0) {
      $contentType = trim((string) substr((string) $headerLine, 13));
      break;
    }
  }

  if (stripos($contentType, 'application/pdf') === false) {
    $errorMessage = 'El generador no devolvió un PDF.';
    return false;
  }

  if (strncmp($body, '%PDF-', 5) !== 0) {
    $errorMessage = 'El PDF generado no es válido.';
    return false;
  }

  return true;
}

function extract_download_filename_from_headers(array $responseHeaders): ?string
{
  foreach ($responseHeaders as $headerLine) {
    if (stripos((string) $headerLine, 'Content-Disposition:') !== 0) {
      continue;
    }

    $headerValue = trim((string) substr((string) $headerLine, 20));
    if (preg_match("/filename\\*=UTF-8''([^;]+)/i", $headerValue, $matches) === 1) {
      return rawurldecode((string) $matches[1]);
    }

    if (preg_match('/filename="([^"]+)"/i', $headerValue, $matches) === 1) {
      return (string) $matches[1];
    }

    if (preg_match('/filename=([^;]+)/i', $headerValue, $matches) === 1) {
      return trim((string) $matches[1], " \t\n\r\0\x0B\"");
    }
  }

  return null;
}

function build_informe_valoracion_zip_entry_filename(array $practice): string
{
  $studentName = trim(implode(' ', array_filter([
    (string) ($practice['alumno_apellido1'] ?? ''),
    (string) ($practice['alumno_apellido2'] ?? ''),
    (string) ($practice['alumno_nombre'] ?? ''),
  ], static fn ($value) => trim((string) $value) !== '')));

  $companyName = trim((string) ($practice['empresa_nombre_comercial'] ?? ''));
  if ($companyName === '') {
    $companyName = trim(implode(' ', array_filter([
      (string) ($practice['empresa_nombre'] ?? ''),
      (string) ($practice['empresa_apellido1'] ?? ''),
      (string) ($practice['empresa_apellido2'] ?? ''),
    ], static fn ($value) => trim((string) $value) !== '')));
  }

  $parts = [
    'informe_final_tutor',
    practicas_sanitize_filename_component((string) ($practice['anexo'] ?? ''), 20),
    practicas_sanitize_filename_component($studentName, 80),
    practicas_sanitize_filename_component($companyName, 80),
  ];

  $baseName = implode('_', $parts);
  $baseName = preg_replace('/_+/u', '_', $baseName) ?? $baseName;
  $baseName = trim($baseName, '._-');
  if ($baseName === '') {
    $baseName = 'informe_final_tutor_practica_' . (int) ($practice['id_practica'] ?? 0);
  }

  return $baseName . '.pdf';
}

function ensure_unique_zip_entry_name(string $fileName, array &$usedNames): string
{
  $pathInfo = pathinfo($fileName);
  $baseName = (string) ($pathInfo['filename'] ?? 'documento');
  $extension = isset($pathInfo['extension']) && $pathInfo['extension'] !== ''
    ? '.' . (string) $pathInfo['extension']
    : '';

  $candidate = $fileName;
  $suffix = 2;
  while (isset($usedNames[$candidate])) {
    $candidate = $baseName . '_' . $suffix . $extension;
    $suffix++;
  }

  $usedNames[$candidate] = true;

  return $candidate;
}

function download_zip_file_and_exit(string $zipPath, string $downloadFileName): void
{
  while (ob_get_level() > 0) {
    ob_end_clean();
  }

  header('Content-Type: application/zip');
  header('Content-Length: ' . (string) filesize($zipPath));
  header("Content-Disposition: attachment; filename*=UTF-8''" . rawurlencode($downloadFileName));
  readfile($zipPath);
}

$page_title = 'Documentación de prácticas | Gestor de Alumnos';
$active_page = 'practicas';

$load_error = null;
$active_course_id = null;
$courses = [];
$selected_course_id = 0;
$search_term = trim((string) ($_GET['q'] ?? ''));
$practices = [];
$generated_documents = [];
$generation_errors = [];
$generation_summary = null;
$allowed_orders = ['alumno_asc', 'alumno_desc', 'empresa_asc', 'empresa_desc', 'anexo_asc', 'anexo_desc', 'fecha_inicio_asc', 'fecha_inicio_desc', 'fecha_fin_asc', 'fecha_fin_desc', 'estado_asc', 'estado_desc'];
$order_param = (string) ($_GET['orden'] ?? '');
$current_order = in_array($order_param, $allowed_orders, true) ? $order_param : 'alumno_asc';
$_last_us = strrpos($current_order, '_');
$sort_col = $_last_us !== false ? substr($current_order, 0, $_last_us) : 'alumno';
$sort_dir = $_last_us !== false ? substr($current_order, $_last_us + 1) : 'asc';

try {
  $pdo = db();
  $courses = $pdo->query('SELECT id_curso_escolar, curso_escolar FROM cursos_escolares ORDER BY activo DESC, id_curso_escolar DESC')->fetchAll();
  $active_course_id = 0;
  foreach ($courses as $_c) {
    if ((int) ($_c['activo'] ?? 0) === 1) { $active_course_id = (int) $_c['id_curso_escolar']; break; }
  }
  if ($active_course_id === 0 && $courses !== []) { $active_course_id = (int) $courses[0]['id_curso_escolar']; }
  $selected_course_id = isset($_GET['id_curso_escolar']) && ctype_digit((string) $_GET['id_curso_escolar'])
    ? (int) $_GET['id_curso_escolar']
    : $active_course_id;

  if ($_SERVER['REQUEST_METHOD'] === 'POST' && $selected_course_id > 0) {
    $selected_programa = $_POST['generar_programa'] ?? [];
    $selected_calendario = $_POST['generar_calendario'] ?? [];
    $selected_informe_valoracion = $_POST['generar_informe_valoracion'] ?? [];

    if (!is_array($selected_programa)) {
      $selected_programa = [];
    }
    if (!is_array($selected_calendario)) {
      $selected_calendario = [];
    }
    if (!is_array($selected_informe_valoracion)) {
      $selected_informe_valoracion = [];
    }

    $selected_ids = array_unique(array_map(
      static fn (string $id): int => (int) $id,
      array_merge(array_keys($selected_programa), array_keys($selected_calendario), array_keys($selected_informe_valoracion))
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
          p.cancelada,
          a.nombre AS alumno_nombre,
          a.apellido1 AS alumno_apellido1,
          a.apellido2 AS alumno_apellido2,
          e.convenio AS empresa_convenio,
          e.nombre AS empresa_nombre,
          e.apellido1 AS empresa_apellido1,
          e.apellido2 AS empresa_apellido2,
          e.nombre_comercial AS nombre_comercial,
          e.nombre_comercial AS empresa_nombre_comercial
        FROM practicas p
        INNER JOIN alumnos a ON a.id_alumno = p.id_alumno
        INNER JOIN empresas e ON e.id_empresa = p.id_empresa
        INNER JOIN alumno_curso ac ON ac.id_alumno = p.id_alumno
        WHERE ac.id_curso_escolar = ?
          AND p.id_practica IN (' . $placeholders . ')'
      );

      $selected_stmt->execute(array_merge([$selected_course_id], $selected_ids));
      $selected_practices = $selected_stmt->fetchAll();
      $selected_practices_by_id = [];
      foreach ($selected_practices as $practice) {
        $selected_practices_by_id[(int) ($practice['id_practica'] ?? 0)] = $practice;
      }

      $zipPath = null;
      $zip = null;
      $zipEntriesCount = 0;
      $zipEntryNames = [];
      $requestedInformeIds = array_values(array_filter(array_map(
        static fn (string $id): int => (int) $id,
        array_keys($selected_informe_valoracion)
      ), static fn (int $id): bool => $id > 0));

      if ($requestedInformeIds !== []) {
        if (!class_exists('ZipArchive')) {
          $generation_errors[] = 'No se puede generar el ZIP de informes porque ZipArchive no está disponible en PHP.';
        } else {
          $zipPath = tempnam(sys_get_temp_dir(), 'practicas_inf_');
          if ($zipPath === false) {
            $generation_errors[] = 'No se pudo preparar el archivo temporal para el ZIP de informes.';
            $zipPath = null;
          } else {
            $zip = new ZipArchive();
            $zipOpenResult = $zip->open($zipPath, ZipArchive::OVERWRITE);
            if ($zipOpenResult !== true) {
              $generation_errors[] = 'No se pudo crear el ZIP temporal de informes.';
              @unlink($zipPath);
              $zipPath = null;
              $zip = null;
            }
          }
        }
      }

      foreach ($selected_ids as $id_practica) {
        if (!isset($selected_practices_by_id[$id_practica])) {
          $generation_errors[] = 'No se pudo recuperar la información necesaria para la práctica #' . $id_practica . '.';
          continue;
        }

        $practice = $selected_practices_by_id[$id_practica];
        $paths = practicas_get_document_paths($practice);

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

          $alumnoNombreArchivoRaw = trim(implode(' ', array_filter([
            (string) ($practice['alumno_nombre'] ?? ''),
            (string) ($practice['alumno_apellido1'] ?? ''),
            (string) ($practice['alumno_apellido2'] ?? ''),
          ])));
          $empresaNombreComercialRaw = (string) ($practice['empresa_nombre_comercial'] ?? '');
          $alumnoNombreArchivo = practicas_sanitize_filename_component((string) preg_replace('/\s+/u', '', $alumnoNombreArchivoRaw), 40);
          $empresaNombreComercialArchivo = practicas_sanitize_filename_component((string) preg_replace('/\s+/u', '', $empresaNombreComercialRaw), 40);
          $planFileName = implode('_', [
            'PF',
            practicas_sanitize_filename_component((string) ($practice['anexo'] ?? ''), 20),
            practicas_sanitize_filename_component((string) ($practice['empresa_convenio'] ?? ''), 20),
            $alumnoNombreArchivo,
            $empresaNombreComercialArchivo,
          ]) . '.pdf';
          $planFilePath = $paths['plan_directory'] . '/' . $planFileName;

          $before_mtime = is_file($planFilePath) ? (int) filemtime($planFilePath) : 0;
          $script_output = null;
          $executed = run_generator_script('generar_plan_formacion.php', $id_practica, $script_output);
          clearstatcache(true, $planFilePath);
          $after_mtime = is_file($planFilePath) ? (int) filemtime($planFilePath) : 0;

          if ($executed && is_file($planFilePath) && ($before_mtime === 0 || $after_mtime >= $before_mtime)) {
            $generated_documents[] = 'Plan Formación - ' . $planFileName;
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

          $before_mtime = is_file($paths['calendar_file_path']) ? (int) filemtime($paths['calendar_file_path']) : 0;
          $script_output = null;
          $executed = run_generator_script('includes/generar_calendario_practica.php', $id_practica, $script_output);
          clearstatcache(true, $paths['calendar_file_path']);
          $after_mtime = is_file($paths['calendar_file_path']) ? (int) filemtime($paths['calendar_file_path']) : 0;

          if ($executed && is_file($paths['calendar_file_path']) && ($before_mtime === 0 || $after_mtime >= $before_mtime)) {
            $generated_documents[] = 'Calendario - ' . $paths['calendar_file_name'];
          } else {
            $generation_errors[] = 'No se pudo generar el Calendario para la práctica #' . $id_practica . '.';
          }
        }

        if (isset($selected_informe_valoracion[(string) $id_practica]) && $zip instanceof ZipArchive) {
          $pdfContent = null;
          $pdfHeaders = [];
          $fetchError = null;
          $fetched = fetch_generator_binary_response(
            'includes/generar_informe_valoracion_tutor_empresa.php',
            $id_practica,
            $pdfContent,
            $pdfHeaders,
            $fetchError
          );

          if (!$fetched || $pdfContent === null) {
            $generation_errors[] = 'No se pudo generar el Informe final de valoración para la práctica #' . $id_practica . '.';
            continue;
          }

          $pdfFileName = extract_download_filename_from_headers($pdfHeaders);
          if ($pdfFileName === null || trim($pdfFileName) === '') {
            $pdfFileName = build_informe_valoracion_zip_entry_filename($practice);
          }
          $pdfFileName = ensure_unique_zip_entry_name($pdfFileName, $zipEntryNames);

          if (!$zip->addFromString($pdfFileName, $pdfContent)) {
            $generation_errors[] = 'No se pudo añadir al ZIP el Informe final de valoración de la práctica #' . $id_practica . '.';
            continue;
          }

          $zipEntriesCount++;
          $generated_documents[] = 'Informe final tutor empresa - ' . $pdfFileName;

          $pdfRellenableContent = null;
          $pdfRellenableHeaders = [];
          $fetchRellenableError = null;
          $fetchedRellenable = fetch_generator_binary_response(
            'includes/generar_informe_valoracion_tutor_empresa_rellenable.php',
            $id_practica,
            $pdfRellenableContent,
            $pdfRellenableHeaders,
            $fetchRellenableError
          );

          if ($fetchedRellenable && $pdfRellenableContent !== null) {
            $pdfRellenableFileName = extract_download_filename_from_headers($pdfRellenableHeaders);
            if ($pdfRellenableFileName === null || trim($pdfRellenableFileName) === '') {
              $pdfRellenableFileName = pathinfo($pdfFileName, PATHINFO_FILENAME) . '_rellenable.pdf';
            }
            $pdfRellenableFileName = ensure_unique_zip_entry_name($pdfRellenableFileName, $zipEntryNames);
            if ($zip->addFromString($pdfRellenableFileName, $pdfRellenableContent)) {
              $zipEntriesCount++;
              $generated_documents[] = 'Informe final tutor empresa (rellenable) - ' . $pdfRellenableFileName;
            } else {
              $generation_errors[] = 'No se pudo añadir al ZIP el informe rellenable de la práctica #' . $id_practica . '.';
            }
          } else {
            $generation_errors[] = 'No se pudo generar el informe rellenable para la práctica #' . $id_practica . '.';
          }
        }
      }

      if ($zip instanceof ZipArchive) {
        $zip->close();

        if ($zipEntriesCount > 0 && $zipPath !== null && is_file($zipPath)) {
          try {
            download_zip_file_and_exit($zipPath, 'informes_finales_tutor_empresa_curso_actual.zip');
          } finally {
            @unlink($zipPath);
          }
          exit;
        }

        if ($zipPath !== null && is_file($zipPath)) {
          @unlink($zipPath);
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

  if ($selected_course_id > 0) {
    $dir = $sort_dir === 'desc' ? 'DESC' : 'ASC';
    $order_clause = match ($sort_col) {
      'empresa'      => "ORDER BY e.nombre $dir, p.id_practica ASC",
      'anexo'        => "ORDER BY CAST(p.anexo AS UNSIGNED) $dir, p.id_practica ASC",
      'fecha_inicio' => "ORDER BY p.fecha_inicio $dir, p.id_practica ASC",
      'fecha_fin'    => "ORDER BY p.fecha_fin $dir, p.id_practica ASC",
      'estado'       => "ORDER BY CASE WHEN p.cancelada = 1 THEN 1 WHEN p.fecha_inicio IS NULL OR p.fecha_fin_extra IS NULL THEN 2 WHEN CURDATE() < p.fecha_inicio THEN 3 WHEN CURDATE() <= p.fecha_fin_extra THEN 4 ELSE 5 END $dir, p.id_practica ASC",
      default        => "ORDER BY a.apellido1 $dir, a.apellido2 $dir, a.nombre $dir, p.id_practica ASC",
    };

    $search_where_doc = '';
    $search_params_doc = ['active_course_id' => $selected_course_id];
    if ($search_term !== '') {
      $search_like = '%' . $search_term . '%';
      $search_where_doc = ' AND (a.nombre LIKE :q1 OR a.apellido1 LIKE :q2 OR a.apellido2 LIKE :q3'
        . ' OR e.nombre LIKE :q4 OR e.apellido1 LIKE :q5 OR e.nombre_comercial LIKE :q6'
        . ' OR a.dni LIKE :q7 OR e.cif LIKE :q8)';
      $search_params_doc['q1'] = $search_like;
      $search_params_doc['q2'] = $search_like;
      $search_params_doc['q3'] = $search_like;
      $search_params_doc['q4'] = $search_like;
      $search_params_doc['q5'] = $search_like;
      $search_params_doc['q6'] = $search_like;
      $search_params_doc['q7'] = $search_like;
      $search_params_doc['q8'] = $search_like;
    }
    $practices_stmt = $pdo->prepare(
      'SELECT DISTINCT
        p.id_practica,
        p.anexo,
        p.fecha_inicio,
        p.fecha_fin,
        p.fecha_fin_extra,
        p.cancelada,
        a.id_alumno,
        a.nombre AS alumno_nombre,
        a.apellido1 AS alumno_apellido1,
        a.apellido2 AS alumno_apellido2,
        a.nia AS alumno_nia,
        a.dni AS alumno_dni,
        e.nombre AS empresa_nombre,
        e.apellido1 AS empresa_apellido1,
        e.apellido2 AS empresa_apellido2,
        e.nombre_comercial AS empresa_nombre_comercial,
        atal.telefono AS alumno_telefono,
        acor_educa.direccion_correo AS alumno_correo_educamadrid,
        acor_personal.direccion_correo AS alumno_correo_personal
      FROM practicas p
      INNER JOIN alumnos a ON a.id_alumno = p.id_alumno
      INNER JOIN empresas e ON e.id_empresa = p.id_empresa
      INNER JOIN alumno_curso ac ON ac.id_alumno = p.id_alumno
      LEFT JOIN (
        SELECT id_entidad, MIN(telefono) AS telefono
        FROM telefonos
        WHERE entidad_tipo = "alumno"
        GROUP BY id_entidad
      ) atal ON atal.id_entidad = a.id_alumno
      LEFT JOIN (
        SELECT id_entidad, MIN(direccion_correo) AS direccion_correo
        FROM correos
        WHERE entidad_tipo = "alumno" AND etiqueta = "educamadrid"
        GROUP BY id_entidad
      ) acor_educa ON acor_educa.id_entidad = a.id_alumno
      LEFT JOIN (
        SELECT id_entidad, MIN(direccion_correo) AS direccion_correo
        FROM correos
        WHERE entidad_tipo = "alumno" AND etiqueta = "personal"
        GROUP BY id_entidad
      ) acor_personal ON acor_personal.id_entidad = a.id_alumno
      WHERE ac.id_curso_escolar = :active_course_id' . $search_where_doc . '
      ' . $order_clause
    );

    $practices_stmt->execute($search_params_doc);
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
        <div class="header-actions">
          <a class="primary-button" href="practicas_ficha_seguimiento.php">Generar fichas de seguimiento periódico</a>
        </div>
      </header>

      <?php $_tab_qs = $selected_course_id > 0 ? '?id_curso_escolar=' . $selected_course_id : ''; ?>
      <nav class="tab-nav">
        <a class="tab-nav-link" href="practicas.php<?php echo htmlspecialchars($_tab_qs, ENT_QUOTES, 'UTF-8'); ?>">Prácticas</a>
        <a class="tab-nav-link" href="practicas_dias.php<?php echo htmlspecialchars($_tab_qs, ENT_QUOTES, 'UTF-8'); ?>">Días de prácticas</a>
        <a class="tab-nav-link active" href="practicas_documentacion.php<?php echo htmlspecialchars($_tab_qs, ENT_QUOTES, 'UTF-8'); ?>">Documentación</a>
        <a class="tab-nav-link" href="practicas_anexos.php<?php echo htmlspecialchars($_tab_qs, ENT_QUOTES, 'UTF-8'); ?>">Seguimiento de Anexos</a>
        <a class="tab-nav-link" href="practicas_listado.php<?php echo htmlspecialchars($_tab_qs, ENT_QUOTES, 'UTF-8'); ?>">Listado</a>
        <a class="tab-nav-link" href="practicas_contacto.php<?php echo htmlspecialchars($_tab_qs, ENT_QUOTES, 'UTF-8'); ?>">Correos</a>
      </nav>

      <form class="topbar" method="get">
        <div class="topbar-actions entity-grid entity-grid--4">
          <label class="calendar-select">
            <select name="id_curso_escolar" aria-label="Curso escolar">
              <option value="" <?php echo $selected_course_id <= 0 ? 'selected' : ''; ?>>Selecciona curso escolar</option>
              <?php foreach ($courses as $course): ?>
                <option value="<?php echo (int) $course['id_curso_escolar']; ?>" <?php echo (int) $course['id_curso_escolar'] === $selected_course_id ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars((string) $course['curso_escolar'], ENT_QUOTES, 'UTF-8'); ?>
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
            <input
              type="search"
              name="q"
              placeholder="Buscar por alumno, empresa, DNI, CIF o convenio"
              aria-label="Buscar por alumno, empresa, DNI, CIF o convenio"
              value="<?php echo htmlspecialchars($search_term, ENT_QUOTES, 'UTF-8'); ?>"
            >
            <input type="hidden" name="orden" value="<?php echo htmlspecialchars($current_order, ENT_QUOTES, 'UTF-8'); ?>">
          </div>
        </div>
      </form>

      <section class="panel">
        <div class="panel-header">
          <h3>Listado del curso actual</h3>
          <p>Selecciona los documentos que quieras generar para cada práctica.</p>
        </div>

        <div class="panel-grid">
          <?php if ($generation_summary !== null || $generated_documents !== []): ?>
            <div class="generation-feedback generation-feedback-success">
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
            </div>
          <?php endif; ?>

          <?php if ($generation_errors !== []): ?>
            <div class="generation-feedback generation-feedback-error">
              <ul class="form-errors">
                <?php foreach ($generation_errors as $generation_error): ?>
                  <li><?php echo htmlspecialchars($generation_error, ENT_QUOTES, 'UTF-8'); ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <form method="post" action="practicas_documentacion.php<?php echo $selected_course_id > 0 ? '?id_curso_escolar=' . $selected_course_id : ''; ?>">
            <table>
              <thead>
                <tr>
                  <th><a class="practice-link" href="<?php echo htmlspecialchars(build_order_url('alumno', $sort_col, $sort_dir), ENT_QUOTES, 'UTF-8'); ?>">Alumno<?php echo sort_ind_doc('alumno', $sort_col, $sort_dir); ?></a></th>
                  <th><a class="practice-link" href="<?php echo htmlspecialchars(build_order_url('empresa', $sort_col, $sort_dir), ENT_QUOTES, 'UTF-8'); ?>">Empresa<?php echo sort_ind_doc('empresa', $sort_col, $sort_dir); ?></a></th>
                  <th><a class="practice-link" href="<?php echo htmlspecialchars(build_order_url('anexo', $sort_col, $sort_dir), ENT_QUOTES, 'UTF-8'); ?>">Anexo<?php echo sort_ind_doc('anexo', $sort_col, $sort_dir); ?></a></th>
                  <th><a class="practice-link" href="<?php echo htmlspecialchars(build_order_url('fecha_inicio', $sort_col, $sort_dir), ENT_QUOTES, 'UTF-8'); ?>">Fecha inicio<?php echo sort_ind_doc('fecha_inicio', $sort_col, $sort_dir); ?></a></th>
                  <th><a class="practice-link" href="<?php echo htmlspecialchars(build_order_url('fecha_fin', $sort_col, $sort_dir), ENT_QUOTES, 'UTF-8'); ?>">Fecha fin<?php echo sort_ind_doc('fecha_fin', $sort_col, $sort_dir); ?></a></th>
                  <th><a class="practice-link" href="<?php echo htmlspecialchars(build_order_url('estado', $sort_col, $sort_dir), ENT_QUOTES, 'UTF-8'); ?>">Estado<?php echo sort_ind_doc('estado', $sort_col, $sort_dir); ?></a></th>
                  <th>
                    Plan Formación
                    <input type="checkbox" id="select-all-programa" aria-label="Seleccionar o deseleccionar todos los Plan Formación">
                  </th>
                  <th>
                    Calendario
                    <input type="checkbox" id="select-all-calendario" aria-label="Seleccionar o deseleccionar todos los Calendarios">
                  </th>
                  <th>
                    Informe final
                    <input type="checkbox" id="select-all-informe-valoracion" aria-label="Seleccionar o deseleccionar todos los informes finales del tutor de empresa">
                  </th>
                </tr>
              </thead>
              <tbody>
                <?php if ($load_error !== null): ?>
                  <tr>
                    <td colspan="9"><?php echo htmlspecialchars($load_error, ENT_QUOTES, 'UTF-8'); ?></td>
                  </tr>
                <?php elseif ($selected_course_id <= 0): ?>
                  <tr>
                    <td colspan="9">No hay un curso activo configurado.</td>
                  </tr>
                <?php elseif ($practices === []): ?>
                  <tr>
                    <td colspan="9">No hay prácticas registradas para el curso actual.</td>
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
                        <span
                          class="alumno-name-trigger"
                          role="button"
                          tabindex="0"
                          aria-haspopup="dialog"
                          aria-expanded="false"
                          data-alumno-id="<?php echo (int) ($practice['id_alumno'] ?? 0); ?>"
                          data-alumno-nombre="<?php echo htmlspecialchars(format_student_name($practice), ENT_QUOTES, 'UTF-8'); ?>"
                          data-alumno-nia="<?php echo htmlspecialchars(trim((string) ($practice['alumno_nia'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>"
                          data-alumno-dni="<?php echo htmlspecialchars(trim((string) ($practice['alumno_dni'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>"
                          data-alumno-telefono="<?php echo htmlspecialchars(trim((string) ($practice['alumno_telefono'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>"
                          data-alumno-correo-educamadrid="<?php echo htmlspecialchars(trim((string) ($practice['alumno_correo_educamadrid'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>"
                          data-alumno-correo-personal="<?php echo htmlspecialchars(trim((string) ($practice['alumno_correo_personal'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>"
                          data-practica-id="<?php echo $practice_id; ?>"
                        ><?php echo htmlspecialchars(format_student_name($practice), ENT_QUOTES, 'UTF-8'); ?></span>
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
                      <td>
                        <input type="checkbox" name="generar_informe_valoracion[<?php echo $practice_id; ?>]" value="1">
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

  <div class="practicas-ras-popover-layer" id="alumno-detail-layer" hidden>
    <button type="button" class="practicas-ras-popover-backdrop" data-popover-close tabindex="-1" aria-hidden="true"></button>
    <div class="practicas-ras-popover practicas-ras-popover--modulo practicas-ras-popover--empresa" id="alumno-detail-popover" role="dialog" aria-modal="false" aria-labelledby="alumno-detail-title" hidden>
      <div class="practicas-ras-popover__header">
        <span class="practicas-ras-popover__eyebrow">Alumno</span>
        <span id="alumno-detail-title" class="practicas-ras-popover__title"></span>
        <button type="button" class="practicas-ras-popover__close" data-popover-close aria-label="Cerrar detalle del alumno">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14" aria-hidden="true">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>
      <ul class="practicas-ras-popover__criteria" id="alumno-detail-data"></ul>
      <div class="practicas-ras-popover__footer">
        <a id="alumno-detail-link" class="practicas-ras-popover__link" href="#">Ver detalles de alumno →</a>
      </div>
    </div>
  </div>

  <script>
    const tableBody = document.querySelector('tbody');
    const alumnoLayer = document.getElementById('alumno-detail-layer');
    const alumnoPopover = document.getElementById('alumno-detail-popover');
    const alumnoTitle = document.getElementById('alumno-detail-title');
    const alumnoDetailList = document.getElementById('alumno-detail-data');
    const alumnoDetailLink = document.getElementById('alumno-detail-link');
    let activeAlumnoTrigger = null;

    if (alumnoLayer && alumnoPopover && alumnoTitle && alumnoDetailList) {
      const setAlumnoPopoverPosition = (trigger) => {
        const triggerRect = trigger.getBoundingClientRect();
        const popoverRect = alumnoPopover.getBoundingClientRect();
        const gutter = 12;
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;

        let top = triggerRect.top;
        let left = triggerRect.right + gutter;

        if (left + popoverRect.width > viewportWidth - gutter) {
          left = triggerRect.left - popoverRect.width - gutter;
        }

        if (left < gutter) {
          left = Math.min(viewportWidth - popoverRect.width - gutter, Math.max(gutter, triggerRect.left));
          top = triggerRect.bottom + gutter;
        }

        if (top + popoverRect.height > viewportHeight - gutter) {
          top = Math.max(gutter, viewportHeight - popoverRect.height - gutter);
        }

        alumnoPopover.style.top = `${Math.max(gutter, top)}px`;
        alumnoPopover.style.left = `${Math.max(gutter, left)}px`;
      };

      const closeAlumnoPopover = () => {
        alumnoPopover.hidden = true;
        alumnoLayer.hidden = true;
        if (activeAlumnoTrigger) {
          activeAlumnoTrigger.setAttribute('aria-expanded', 'false');
        }
        activeAlumnoTrigger = null;
      };

      const getAlumnoValueOrFallback = (value) => {
        const normalized = (value || '').trim();
        return normalized !== '' ? normalized : 'No disponible';
      };

      const addAlumnoInfoItem = (label, value) => {
        const item = document.createElement('li');
        const strong = document.createElement('strong');
        strong.textContent = label;
        item.appendChild(strong);
        const valueSpan = document.createElement('span');
        valueSpan.textContent = value;
        item.appendChild(valueSpan);
        alumnoDetailList.appendChild(item);
      };

      const addAlumnoCopyItem = (label, value) => {
        const item = document.createElement('li');
        const strong = document.createElement('strong');
        strong.textContent = label;
        item.appendChild(strong);
        const valueSpan = document.createElement('span');
        if (value !== '') {
          const trigger = document.createElement('span');
          trigger.className = 'copy-trigger';
          trigger.dataset.copy = value;
          trigger.textContent = value;
          valueSpan.appendChild(trigger);
        } else {
          valueSpan.textContent = 'No disponible';
        }
        item.appendChild(valueSpan);
        alumnoDetailList.appendChild(item);
      };

      const openAlumnoPopover = (trigger) => {
        if (activeAlumnoTrigger && activeAlumnoTrigger !== trigger) {
          activeAlumnoTrigger.setAttribute('aria-expanded', 'false');
        }

        alumnoTitle.textContent = trigger.dataset.alumnoNombre || 'Alumno';

        if (alumnoDetailLink) {
          const alumnoId = (trigger.dataset.alumnoId || '').trim();
          alumnoDetailLink.setAttribute('href', alumnoId !== '' ? `alumno_detalle.php?id_alumno=${encodeURIComponent(alumnoId)}` : '#');
        }

        alumnoDetailList.innerHTML = '';
        addAlumnoInfoItem('NIA', getAlumnoValueOrFallback(trigger.dataset.alumnoNia));
        addAlumnoInfoItem('DNI', getAlumnoValueOrFallback(trigger.dataset.alumnoDni));
        addAlumnoCopyItem('EducaMadrid', (trigger.dataset.alumnoCorreoEducamadrid || '').trim());
        addAlumnoCopyItem('Correo personal', (trigger.dataset.alumnoCorreoPersonal || '').trim());
        addAlumnoCopyItem('Teléfono', (trigger.dataset.alumnoTelefono || '').trim());

        activeAlumnoTrigger = trigger;
        trigger.setAttribute('aria-expanded', 'true');
        alumnoLayer.hidden = false;
        alumnoPopover.hidden = false;
        setAlumnoPopoverPosition(trigger);
      };

      tableBody.addEventListener('click', (event) => {
        const trigger = event.target.closest('.alumno-name-trigger');
        if (trigger && tableBody.contains(trigger)) {
          if (activeAlumnoTrigger === trigger && !alumnoPopover.hidden) {
            closeAlumnoPopover();
            return;
          }
          openAlumnoPopover(trigger);
          return;
        }
      });

      tableBody.addEventListener('keydown', (event) => {
        const trigger = event.target.closest('.alumno-name-trigger');
        if (trigger && tableBody.contains(trigger) && (event.key === 'Enter' || event.key === ' ')) {
          event.preventDefault();
          trigger.click();
        }
      });

      alumnoLayer.querySelectorAll('[data-popover-close]').forEach((element) => {
        element.addEventListener('click', closeAlumnoPopover);
      });

      window.addEventListener('resize', () => {
        if (activeAlumnoTrigger && !alumnoPopover.hidden) {
          setAlumnoPopoverPosition(activeAlumnoTrigger);
        }
      });

      window.addEventListener('scroll', () => {
        if (activeAlumnoTrigger && !alumnoPopover.hidden) {
          setAlumnoPopoverPosition(activeAlumnoTrigger);
        }
      }, true);

      document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !alumnoPopover.hidden) {
          closeAlumnoPopover();
        }
      });
    }
  </script>
  <script src="assets/copy.js"></script>
  <script>
    (function () {
      var topbarFormDoc = document.querySelector('form.topbar');
      if (topbarFormDoc) {
        var topbarCourseDoc = topbarFormDoc.querySelector('select[name="id_curso_escolar"]');
        var topbarSearchDoc = topbarFormDoc.querySelector('input[name="q"]');
        var searchDebounceDoc = null;
        if (topbarCourseDoc) {
          topbarCourseDoc.addEventListener('change', function () { topbarFormDoc.submit(); });
        }
        if (topbarSearchDoc) {
          topbarSearchDoc.addEventListener('input', function () {
            window.clearTimeout(searchDebounceDoc);
            searchDebounceDoc = window.setTimeout(function () { topbarFormDoc.submit(); }, 250);
          });
        }
      }
    })();
  </script>
  <script>
    const selectAllPrograma = document.getElementById('select-all-programa');
    const selectAllCalendario = document.getElementById('select-all-calendario');
    const selectAllInformeValoracion = document.getElementById('select-all-informe-valoracion');

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

    if (selectAllInformeValoracion) {
      selectAllInformeValoracion.addEventListener('change', () => {
        document.querySelectorAll('input[name^="generar_informe_valoracion["]').forEach((checkbox) => {
          checkbox.checked = selectAllInformeValoracion.checked;
        });
      });
    }
  </script>
</body>
</html>
