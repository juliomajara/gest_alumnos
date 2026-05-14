<?php
declare(strict_types=1);

use Mpdf\Mpdf;
use Mpdf\Output\Destination;

ob_start();

function esc(string $value): string {
  return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function value(array $row, string $key): string {
  return trim((string) ($row[$key] ?? ''));
}

function month_name_es(int $month): string {
  $months = [1=>'enero',2=>'febrero',3=>'marzo',4=>'abril',5=>'mayo',6=>'junio',7=>'julio',8=>'agosto',9=>'septiembre',10=>'octubre',11=>'noviembre',12=>'diciembre'];
  return $months[$month] ?? '';
}

function checkbox(bool $checked): string {
  return $checked ? '<span class="checkbox checked"></span>' : '<span class="checkbox"></span>';
}

$fase = 'inicio';
$id_practica = null;

try {
  $isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1'], true)
    || in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
  $debugHtml = $isLocal && (($_GET['debug_html'] ?? '0') === '1');
  $debugPdf = $isLocal && (($_GET['debug_pdf'] ?? '0') === '1');

  $fase = 'validando parámetro id_practica';
  $id_practica = filter_var($_GET['id_practica'] ?? ($_GET['id'] ?? null), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
  if ($id_practica === false || $id_practica === null) {
    throw new RuntimeException('No se recibió un id_practica válido.');
  }

  $fase = 'comprobando db.php';
  $dbPath = __DIR__ . '/../db.php';
  if (!is_file($dbPath)) {
    throw new RuntimeException('No se encontró db.php en: ' . $dbPath);
  }
  require_once $dbPath;

  $fase = 'comprobando vendor/autoload.php';
  $autoloadPath = __DIR__ . '/../vendor/autoload.php';
  if (!is_file($autoloadPath)) {
    throw new RuntimeException('No se encontró vendor/autoload.php en: ' . $autoloadPath);
  }
  require_once $autoloadPath;

  $fase = 'cargando mPDF';
  if (!class_exists(Mpdf::class)) {
    throw new RuntimeException('No se pudo cargar mPDF (clase Mpdf\\Mpdf no encontrada).');
  }

  $fase = 'obteniendo conexión a base de datos';
  $pdo = db();

  $fase = 'ejecutando consulta principal de práctica';
  $stmt = $pdo->prepare(
    'SELECT p.*, a.nombre AS alumno_nombre, a.apellido1 AS alumno_apellido1, a.apellido2 AS alumno_apellido2,
      e.nombre AS empresa_nombre, e.nombre_comercial AS empresa_nombre_comercial, e.convenio AS empresa_convenio,
      et.nombre AS tutor_nombre, et.apellido1 AS tutor_apellido1, et.apellido2 AS tutor_apellido2,
      ce.curso_escolar, ci.ciclo AS ciclo_nombre, ci.codigo AS ciclo_codigo, ci.id_ciclo,
      ac.id_curso_escolar AS ac_id_curso_escolar, ac.id_ciclo AS ac_id_ciclo, c.curso AS curso_ordinal,
      (SELECT direccion_correo FROM correos c1 WHERE c1.entidad_tipo = "alumno" AND c1.id_entidad = a.id_alumno ORDER BY c1.id_correo ASC LIMIT 1) AS alumno_email,
      (SELECT direccion_correo FROM correos c2 WHERE c2.entidad_tipo = "empresa_tutor" AND c2.id_entidad = et.id_empresas_tutor ORDER BY c2.id_correo ASC LIMIT 1) AS tutor_empresa_email
    FROM practicas p
    LEFT JOIN alumnos a ON a.id_alumno = p.id_alumno
    LEFT JOIN empresas e ON e.id_empresa = p.id_empresa
    LEFT JOIN empresas_tutores et ON et.id_empresas_tutor = p.id_empresa_tutor
    LEFT JOIN alumno_curso ac ON ac.id_alumno = p.id_alumno
      AND ac.id_curso_escolar = (
        SELECT MAX(ac2.id_curso_escolar)
        FROM alumno_curso ac2
        WHERE ac2.id_alumno = p.id_alumno
      )
    LEFT JOIN cursos_escolares ce ON ce.id_curso_escolar = ac.id_curso_escolar
    LEFT JOIN grupos gr ON gr.id_grupo = ac.id_grupo
    LEFT JOIN ciclos ci ON ci.id_ciclo = gr.id_ciclo
    LEFT JOIN cursos c ON c.id_curso = ac.id_curso
    WHERE p.id_practica = :id_practica
    LIMIT 1'
  );
  $stmt->execute(['id_practica' => $id_practica]);
  $practice = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$practice) {
    throw new RuntimeException('No existe la práctica con id ' . (int) $id_practica . '.');
  }

  $fase = 'cargando plantilla HTML';
  $templatePath = __DIR__ . '/../docs/practicas_informe_valoracion_final_tutor_empresa.html';
  if (!is_file($templatePath) || !is_readable($templatePath)) {
    throw new RuntimeException('No se encontró la plantilla HTML en: ' . $templatePath);
  }
  $html = (string) file_get_contents($templatePath);

  $fase = 'resolviendo imágenes de la plantilla';
  $docsDir = __DIR__ . '/../docs';
  $html = preg_replace_callback('~(<img\b[^>]*\bsrc=["\'])([^"\']+)(["\'][^>]*>)~iu', static function (array $m) use ($docsDir): string {
    $src = trim($m[2]);
    if ($src === '' || str_starts_with($src, 'data:')) {
      return $m[0];
    }
    $path = $docsDir . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $src), DIRECTORY_SEPARATOR);
    if (!is_file($path) || !is_readable($path)) {
      return $m[0];
    }
    $mime = function_exists('mime_content_type') ? (string) mime_content_type($path) : 'image/png';
    $data = file_get_contents($path);
    if ($data === false) {
      return $m[0];
    }
    return $m[1] . ('data:' . $mime . ';base64,' . base64_encode($data)) . $m[3];
  }, $html) ?? $html;

  $fase = 'consultando resultados de aprendizaje';
  $raRows = [];
  $courseId = (int) ($practice['ac_id_curso_escolar'] ?? 0);
  $cycleId = (int) ($practice['ac_id_ciclo'] ?? 0);
  if ($courseId > 0 && $cycleId > 0) {
    try {
      $raStmt = $pdo->prepare(
        'SELECT m.materia_general, m.materia_propia, ra.numero AS ra_numero
         FROM practicas_ras pr
         LEFT JOIN resultados_aprendizaje ra ON ra.id_ra = pr.id_ra
         LEFT JOIN modulos m ON m.id_modulo = COALESCE(pr.id_modulo, ra.id_modulo)
         WHERE pr.id_curso_escolar = :id_curso_escolar AND pr.id_ciclo = :id_ciclo
         ORDER BY m.codigo ASC, CAST(ra.numero AS UNSIGNED) ASC, ra.numero ASC, pr.id_practica_ra ASC'
      );
      $raStmt->execute(['id_curso_escolar' => $courseId, 'id_ciclo' => $cycleId]);
      $raRows = $raStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $raError) {
      error_log('[generar_informe_valoracion_tutor_empresa][RAs] id_practica=' . (string) $id_practica . ' :: ' . $raError->getMessage());
      $raRows = [];
    }
  }

  $fase = 'rellenando plantilla';
  $repl = [
    '{{CURSO_ACADEMICO}}' => esc(value($practice, 'curso_escolar')),
    '{{NUM_CONVENIO}}' => esc(value($practice, 'empresa_convenio')),
    '{{NUM_ANEXO_RELACION}}' => esc(value($practice, 'anexo')),
    '{{ALUMNO_APELLIDOS}}' => esc(trim(value($practice, 'alumno_apellido1') . ' ' . value($practice, 'alumno_apellido2'))),
    '{{ALUMNO_NOMBRE}}' => esc(value($practice, 'alumno_nombre')),
    '{{ALUMNO_EMAIL}}' => esc(value($practice, 'alumno_email')),
    '{{CENTRO_TRABAJO_DENOMINACION}}' => esc(value($practice, 'empresa_nombre_comercial') !== '' ? value($practice, 'empresa_nombre_comercial') : value($practice, 'empresa_nombre')),
    '{{TUTOR_EMPRESA_APELLIDOS}}' => esc(trim(value($practice, 'tutor_apellido1') . ' ' . value($practice, 'tutor_apellido2'))),
    '{{TUTOR_EMPRESA_NOMBRE}}' => esc(value($practice, 'tutor_nombre')),
    '{{TUTOR_EMPRESA_EMAIL}}' => esc(value($practice, 'tutor_empresa_email')),
    '{{CICLO_DENOMINACION}}' => esc(value($practice, 'ciclo_nombre')),
    '{{GRADO}}' => esc(value($practice, 'curso_ordinal')),
    '{{CODIGO_CICLO}}' => esc(value($practice, 'ciclo_codigo')),
    '{{HORAS_REALIZADAS}}' => esc(value($practice, 'horas')),
    '{{AREAS_PUESTOS_VALORACION}}' => esc(value($practice, 'areas_puestos_trabajo')),
    '{{VALORACION_COMPETENCIAS_TRANSVERSALES}}' => esc(value($practice, 'valoracion_competencias_transversales')),
    '{{OBSERVACIONES_TUTOR_EMPRESA}}' => esc(value($practice, 'observaciones_tutor_empresa')),
    '{{MOTIVOS_NO_SUPERACION}}' => esc(value($practice, 'motivos_no_superacion')),
    '{{LOCALIDAD_FIRMA}}' => esc(value($practice, 'localidad_firma')),
  ];

  $today = new DateTimeImmutable('today');
  $repl['{{DIA_FIRMA}}'] = esc($today->format('d'));
  $repl['{{MES_FIRMA}}'] = esc(month_name_es((int) $today->format('n')));
  $repl['{{ANIO_FIRMA}}'] = esc($today->format('Y'));

  for ($i = 1; $i <= 3; $i++) {
    $repl['{{PERIODO_' . $i . '}}'] = checkbox(((int) ($practice['periodo'] ?? 0)) === $i);
  }

  for ($i = 1; $i <= 6; $i++) {
    $row = $raRows[$i - 1] ?? [];
    $modulo = trim((string) (($row['materia_general'] ?? '') !== '' ? ($row['materia_general'] ?? '') : ($row['materia_propia'] ?? '')));
    $raNum = trim((string) ($row['ra_numero'] ?? ''));
    $repl['{{RA_' . $i . '_MODULO}}'] = esc($modulo);
    $repl['{{RA_' . $i . '_RESULTADO_APRENDIZAJE}}'] = esc($raNum !== '' ? ('RA ' . $raNum) : '');
    $repl['{{RA_' . $i . '_SUPERADO}}'] = '';
    $repl['{{RA_' . $i . '_NO_SUPERADO}}'] = '';
  }

  $html = strtr($html, $repl);
  $html = preg_replace('/\{\{[^}]+\}\}/', '', $html) ?? $html;

  $fase = 'creando instancia de mPDF';
  $pdfTempDir = sys_get_temp_dir() . '/mpdf_tmp';
  if (!is_dir($pdfTempDir) && !mkdir($pdfTempDir, 0755, true) && !is_dir($pdfTempDir)) {
    throw new RuntimeException('No se pudo preparar el directorio temporal para mPDF: ' . $pdfTempDir);
  }

  $mpdf = new Mpdf([
    'tempDir' => $pdfTempDir,
    'format' => 'A4',
    'orientation' => 'P',
    'mode' => 'utf-8',
    'margin_left' => 10,
    'margin_right' => 10,
    'margin_top' => 10,
    'margin_bottom' => 10,
    'default_font' => 'dejavusans',
  ]);
  $mpdf->shrink_tables_to_fit = 1;
  $mpdf->keep_table_proportions = false;
  $mpdf->simpleTables = true;

  if ($debugHtml) {
    if (ob_get_length()) {
      ob_end_clean();
    }
    header('Content-Type: text/html; charset=UTF-8');
    echo $html;
    exit;
  }

  $fase = 'escribiendo HTML en mPDF';
  $mpdf->SetBasePath(__DIR__ . '/../docs/');
  $mpdf->WriteHTML($html);
  if ($mpdf->page > 3) {
    throw new RuntimeException(
      'El informe se ha generado con ' . $mpdf->page . ' páginas. La maquetación HTML está rota. Revisa la plantilla del informe.'
    );
  }

  $fase = 'enviando PDF al navegador';
  if (ob_get_length()) {
    ob_end_clean();
  }
  $pdfDestination = $debugPdf ? Destination::INLINE : Destination::DOWNLOAD;
  $mpdf->Output('informe_valoracion_tutor_empresa_' . (int) $id_practica . '.pdf', $pdfDestination);
  exit;
} catch (Throwable $e) {
  $errorId = '[generar_informe_valoracion_tutor_empresa]';
  error_log($errorId . ' id_practica=' . (string) ($id_practica ?? 'null')
    . ' fase=' . $fase
    . ' message=' . $e->getMessage()
    . ' file=' . $e->getFile()
    . ' line=' . $e->getLine()
    . ' trace=' . str_replace(["\r", "\n"], ' | ', $e->getTraceAsString()));

  if (ob_get_length()) {
    ob_end_clean();
  }

  $isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1'], true)
    || in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);

  http_response_code(500);
  if ($isLocal) {
    echo '<h1>No se pudo generar el informe.</h1>';
    echo '<p><strong>Fase:</strong> ' . esc($fase) . '</p>';
    echo '<p><strong>ID práctica:</strong> ' . (int) ($id_practica ?? 0) . '</p>';
    echo '<p><strong>Error real:</strong> ' . esc($e->getMessage()) . '</p>';
    echo '<p><strong>Archivo:</strong> ' . esc($e->getFile()) . '</p>';
    echo '<p><strong>Línea:</strong> ' . (int) $e->getLine() . '</p>';
    exit;
  }

  exit('No se pudo generar el informe. Revisa el log de errores del servidor.');
}
