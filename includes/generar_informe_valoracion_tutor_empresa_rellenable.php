<?php
declare(strict_types=1);

use Mpdf\Mpdf;

require_once __DIR__ . '/../vendor/autoload.php';

function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
function image_file_to_data_uri(string $path): string {
  $mime = function_exists('mime_content_type') ? (string)mime_content_type($path) : 'image/png';
  $data = file_get_contents($path);
  if ($data === false) throw new RuntimeException('No se pudo leer imagen local.');
  return 'data:' . $mime . ';base64,' . base64_encode($data);
}
function embed_local_images(string $html, string $docsDir): string {
  return preg_replace_callback('~(<img\b[^>]*\bsrc=["\'])([^"\']+)(["\'][^>]*>)~iu', static function(array $m) use ($docsDir): string {
    $src = trim($m[2]);
    if ($src === '' || str_starts_with($src, 'data:') || preg_match('#^(https?:|file:|//)#i', $src) === 1) return $m[0];
    $path = $docsDir . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $src), DIRECTORY_SEPARATOR);
    if (!is_file($path) || !is_readable($path)) return $m[0];
    return $m[1] . image_file_to_data_uri($path) . $m[3];
  }, $html) ?? $html;
}
function value(array $row, string $key): string { return trim((string)($row[$key] ?? '')); }
function month_name_es(int $month): string {
  $m = [1=>'enero',2=>'febrero',3=>'marzo',4=>'abril',5=>'mayo',6=>'junio',7=>'julio',8=>'agosto',9=>'septiembre',10=>'octubre',11=>'noviembre',12=>'diciembre'];
  return $m[$month] ?? '';
}
function parse_iso_date(?string $value): ?DateTimeImmutable {
  $value = trim((string)$value);
  if ($value === '') return null;
  $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
  if ($date instanceof DateTimeImmutable) return $date;
  try {
    return new DateTimeImmutable($value);
  } catch (Throwable $e) {
    return null;
  }
}
function normalize_ra_for_display(string $raNumero): string {
  $raNumero = trim($raNumero);
  if ($raNumero === '') return '';
  if (stripos($raNumero, 'RA') === 0) return $raNumero;
  return 'RA ' . $raNumero;
}
function limpiarNombreArchivo(string $text): string {
  $text = trim($text);
  if ($text === '') return '';
  if (class_exists('Transliterator')) {
    $tr = Transliterator::create('NFD; [:Nonspacing Mark:] Remove; NFC');
    if ($tr instanceof Transliterator) {
      $text = $tr->transliterate($text);
    }
  } else {
    $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    if ($converted !== false) $text = $converted;
  }
  $text = preg_replace('/[^A-Za-z0-9]+/', '_', $text) ?? '';
  $text = trim($text, '_');
  return $text;
}

$fase = 'inicio';
$id_practica = 0;
$tmpPdf = '';
$templatePath = '';

try {
  $projectRoot = realpath(__DIR__ . '/..');
  if ($projectRoot === false) throw new RuntimeException('No se pudo resolver la raíz del proyecto.');

  require_once $projectRoot . DIRECTORY_SEPARATOR . 'db.php';

  $docsDir = $projectRoot . DIRECTORY_SEPARATOR . 'docs';
  $templatePath = $docsDir . DIRECTORY_SEPARATOR . 'practicas_informe_valoracion_final_tutor_empresa_mpdf_rellenable.html';
  $mpdfTempDir = $docsDir . DIRECTORY_SEPARATOR . 'tmp_mpdf';

  $isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1'], true)
    || in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);

  $fase = 'validando parámetro id_practica';
  $id_practica = filter_var($_GET['id_practica'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
  if ($id_practica <= 0) throw new RuntimeException('No se recibió un id_practica válido.');

  if (!is_dir($mpdfTempDir) && !mkdir($mpdfTempDir, 0775, true) && !is_dir($mpdfTempDir)) throw new RuntimeException('No se pudo crear tmp_mpdf.');

  $debugPlain = $isLocal && (($_GET['debug_plain'] ?? '0') === '1');
  $debugPlainWithMargins = $isLocal && (($_GET['debug_plain_with_margins'] ?? '0') === '1');
  $debugFakeData = $isLocal && (($_GET['debug_fake_data'] ?? '0') === '1');
  $debugHtml = $isLocal && (($_GET['debug_html'] ?? '0') === '1');
  $debugRas = $isLocal && (($_GET['debug_ras'] ?? '0') === '1');

  $html = '';
  $mpdfConfig = [
    'tempDir' => $mpdfTempDir,
    'mode' => 'utf-8',
    'format' => 'A4',
    'orientation' => 'P',
    'default_font' => 'dejavusans',
    'allow_output_buffering' => true,
  ];

  if ($debugPlain || $debugPlainWithMargins) {
    $html = '<h1>Prueba informe tutor</h1><p>Esto debe salir en una sola página.</p>';
    if ($debugPlainWithMargins) {
      $mpdfConfig['margin_left'] = 10;
      $mpdfConfig['margin_right'] = 10;
      $mpdfConfig['margin_top'] = 8;
      $mpdfConfig['margin_bottom'] = 7;
      $mpdfConfig['margin_header'] = 0;
      $mpdfConfig['margin_footer'] = 0;
    }
  } else {
    if (!is_file($templatePath) || !is_readable($templatePath)) throw new RuntimeException('No se encontró la plantilla HTML.');
    $htmlTemplate = (string) file_get_contents($templatePath);
    if ($htmlTemplate === '') throw new RuntimeException('Plantilla vacía.');
    $htmlTemplate = embed_local_images($htmlTemplate, $docsDir);

    if ($debugFakeData) {
      $repl = [
        '{{CURSO_ACADEMICO}}' => '2025/2026', '{{NUM_CONVENIO}}' => 'CONV-1', '{{NUM_ANEXO_RELACION}}' => 'ANEXO-1',
        '{{ALUMNO_APELLIDOS}}' => 'Pérez García', '{{ALUMNO_NOMBRE}}' => 'Ana', '{{ALUMNO_EMAIL}}' => 'ana@example.com',
        '{{CENTRO_TRABAJO_DENOMINACION}}' => 'Empresa de prueba', '{{TUTOR_EMPRESA_APELLIDOS}}' => 'López Martín',
        '{{TUTOR_EMPRESA_NOMBRE}}' => 'Carlos', '{{TUTOR_EMPRESA_EMAIL}}' => 'carlos@example.com',
        '{{CICLO_DENOMINACION}}' => 'Desarrollo de Aplicaciones Web', '{{GRADO}}' => 'Superior', '{{CODIGO_CICLO}}' => 'IFCS03',
        '{{HORAS_REALIZADAS}}' => '370', '{{PERIODO_1}}' => 'X', '{{PERIODO_2}}' => '', '{{PERIODO_3}}' => '',
        '{{AREAS_PUESTOS_VALORACION}}' => "\xc2\xa0",
        '{{VALORACION_COMPETENCIAS_TRANSVERSALES}}' => "\xc2\xa0",
        '{{OBSERVACIONES_TUTOR_EMPRESA}}' => "\xc2\xa0",
        '{{MOTIVOS_NO_SUPERACION}}' => "\xc2\xa0",
        '{{LOCALIDAD_FIRMA}}' => 'Getafe', '{{DIA_FIRMA}}' => '14', '{{MES_FIRMA}}' => 'mayo', '{{ANIO_FIRMA}}' => '2026',
      ];
      $repl['{{RAS_ROWS}}'] = '<tr><td class="modulo-cell">M1</td><td>RA 1</td><td class="check-cell"></td><td class="check-cell"></td></tr>';
      $repl['{{LEYENDA_MODULOS}}'] = '';
      $html = strtr($htmlTemplate, $repl);
    } else {
      $fase = 'consulta principal';
      $pdo = db();
      $stmt = $pdo->prepare('SELECT p.*, a.nombre AS alumno_nombre, a.apellido1 AS alumno_apellido1, a.apellido2 AS alumno_apellido2,
          e.nombre AS empresa_nombre, e.nombre_comercial AS empresa_nombre_comercial, e.convenio AS empresa_convenio,
          et.nombre AS tutor_nombre, et.apellido1 AS tutor_apellido1, et.apellido2 AS tutor_apellido2,
          ac.id_curso_escolar AS id_curso_escolar, gr.id_ciclo AS id_ciclo,
          ce.curso_escolar, ci.ciclo AS ciclo_nombre, ci.codigo AS ciclo_codigo, ci.grado AS ciclo_grado, c.curso AS curso_ordinal,
          (SELECT c1.direccion_correo
             FROM correos c1
            WHERE c1.entidad_tipo = "alumno"
              AND c1.id_entidad = a.id_alumno
              AND (LOWER(TRIM(COALESCE(c1.direccion_correo, ""))) LIKE "%@educa.madrid.org"
                OR TRIM(COALESCE(c1.etiqueta, "")) = "EducaMadrid")
            ORDER BY CASE
              WHEN LOWER(TRIM(COALESCE(c1.direccion_correo, ""))) LIKE "%@educa.madrid.org" THEN 0
              WHEN TRIM(COALESCE(c1.etiqueta, "")) = "EducaMadrid" THEN 1
              ELSE 2
            END, c1.id_correo DESC
            LIMIT 1) AS alumno_email,
          (SELECT direccion_correo FROM correos c2 WHERE c2.entidad_tipo = "empresa_tutor" AND c2.id_entidad = et.id_empresas_tutor ORDER BY c2.id_correo ASC LIMIT 1) AS tutor_empresa_email,
          ld.nombre AS direccion_localidad
        FROM practicas p
        LEFT JOIN alumnos a ON a.id_alumno = p.id_alumno
        LEFT JOIN empresas e ON e.id_empresa = p.id_empresa
        LEFT JOIN empresas_tutores et ON et.id_empresas_tutor = p.id_empresa_tutor
        LEFT JOIN direcciones d ON d.id_direccion = p.id_direccion
        LEFT JOIN localidades ld ON ld.id_localidad = d.id_localidad
        LEFT JOIN alumno_curso ac ON ac.id_alumno = p.id_alumno AND ac.id_curso_escolar = (SELECT MAX(ac2.id_curso_escolar) FROM alumno_curso ac2 WHERE ac2.id_alumno = p.id_alumno)
        LEFT JOIN cursos_escolares ce ON ce.id_curso_escolar = ac.id_curso_escolar
        LEFT JOIN grupos gr ON gr.id_grupo = ac.id_grupo
        LEFT JOIN ciclos ci ON ci.id_ciclo = gr.id_ciclo
        LEFT JOIN cursos c ON c.id_curso = ac.id_curso
        WHERE p.id_practica = :id_practica LIMIT 1');
      $stmt->execute(['id_practica' => $id_practica]);
      $practice = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$practice) throw new RuntimeException('No existe la práctica con id ' . $id_practica . '.');

      $repl = [
        '{{CURSO_ACADEMICO}}'=>e(value($practice,'curso_escolar')),
        '{{NUM_CONVENIO}}'=>e(value($practice,'empresa_convenio')),
        '{{NUM_ANEXO_RELACION}}'=>e(value($practice,'anexo')),
        '{{ALUMNO_APELLIDOS}}'=>e(trim(value($practice,'alumno_apellido1') . ' ' . value($practice,'alumno_apellido2'))),
        '{{ALUMNO_NOMBRE}}'=>e(value($practice,'alumno_nombre')),
        '{{ALUMNO_EMAIL}}'=>e(value($practice,'alumno_email')),
        '{{CENTRO_TRABAJO_DENOMINACION}}'=>e(value($practice,'empresa_nombre_comercial') !== '' ? value($practice,'empresa_nombre_comercial') : value($practice,'empresa_nombre')),
        '{{TUTOR_EMPRESA_APELLIDOS}}'=>e(trim(value($practice,'tutor_apellido1') . ' ' . value($practice,'tutor_apellido2'))),
        '{{TUTOR_EMPRESA_NOMBRE}}'=>e(value($practice,'tutor_nombre')),
        '{{TUTOR_EMPRESA_EMAIL}}'=>e(value($practice,'tutor_empresa_email')),
        '{{CICLO_DENOMINACION}}'=>e(value($practice,'ciclo_nombre')),
        '{{GRADO}}'=>e(value($practice,'ciclo_grado')),
        '{{CODIGO_CICLO}}'=>e(value($practice,'ciclo_codigo')),
        '{{HORAS_REALIZADAS}}'=>e(value($practice,'horas')),
        '{{AREAS_PUESTOS_VALORACION}}'=>(value($practice,'areas_puestos_trabajo')!==''?e(value($practice,'areas_puestos_trabajo')):"\xc2\xa0"),
        '{{VALORACION_COMPETENCIAS_TRANSVERSALES}}'=>(value($practice,'valoracion_competencias_transversales')!==''?e(value($practice,'valoracion_competencias_transversales')):"\xc2\xa0"),
        '{{OBSERVACIONES_TUTOR_EMPRESA}}'=>(value($practice,'observaciones_tutor_empresa')!==''?e(value($practice,'observaciones_tutor_empresa')):"\xc2\xa0"),
        '{{MOTIVOS_NO_SUPERACION}}'=>(value($practice,'motivos_no_superacion')!==''?e(value($practice,'motivos_no_superacion')):"\xc2\xa0"),
        '{{LOCALIDAD_FIRMA}}'=>e(value($practice,'direccion_localidad')),
      ];
      $fechaFirma = parse_iso_date((string)($practice['fecha_fin_extra'] ?? ''));
      if (!$fechaFirma instanceof DateTimeImmutable) {
        $fechaFirma = parse_iso_date((string)($practice['fecha_fin'] ?? ''));
      }
      $repl['{{DIA_FIRMA}}'] = $fechaFirma instanceof DateTimeImmutable ? e($fechaFirma->format('d')) : '';
      $repl['{{MES_FIRMA}}'] = $fechaFirma instanceof DateTimeImmutable ? e(month_name_es((int)$fechaFirma->format('n'))) : '';
      $repl['{{ANIO_FIRMA}}'] = $fechaFirma instanceof DateTimeImmutable ? e($fechaFirma->format('Y')) : '';
      $repl['{{PERIODO_1}}'] = 'X';
      $repl['{{PERIODO_2}}'] = '';
      $repl['{{PERIODO_3}}'] = '';
      $repl['{{RAS_ROWS}}'] = '';
      $repl['{{LEYENDA_MODULOS}}'] = '';

      $idCursoEscolar = (int)($practice['id_curso_escolar'] ?? 0);
      $idCiclo = (int)($practice['id_ciclo'] ?? 0);
      $cursoEscolarTexto = trim((string)($practice['curso_escolar'] ?? ''));
      $cicloTexto = trim((string)($practice['ciclo_nombre'] ?? ''));
      error_log('[informe_tutor_empresa] filtros RAs: id_practica=' . $id_practica . ', id_curso_escolar=' . ($practice['id_curso_escolar'] ?? 'null') . ', id_ciclo=' . ($practice['id_ciclo'] ?? 'null'));

      $rasSqlIds = 'SELECT
          pr.id_practica_ra,
          m.codigo AS codigo_modulo,
          COALESCE(m.materia_general, m.materia_propia) AS nombre_modulo,
          ra.numero AS ra_numero,
          ra.descripcion AS ra_texto
         FROM practicas_ras pr
         LEFT JOIN resultados_aprendizaje ra ON ra.id_ra = pr.id_ra
         LEFT JOIN modulos m ON m.id_modulo = COALESCE(pr.id_modulo, ra.id_modulo)
         WHERE pr.id_curso_escolar = :id_curso_escolar
           AND pr.id_ciclo = :id_ciclo
         ORDER BY m.codigo ASC, CAST(ra.numero AS UNSIGNED) ASC, ra.numero ASC, pr.id_practica_ra ASC';

      $rasSqlFallback = 'SELECT
          pr.id_practica_ra,
          m.codigo AS codigo_modulo,
          COALESCE(m.materia_general, m.materia_propia) AS nombre_modulo,
          ra.numero AS ra_numero,
          ra.descripcion AS ra_texto
         FROM practicas_ras pr
         LEFT JOIN resultados_aprendizaje ra ON ra.id_ra = pr.id_ra
         LEFT JOIN modulos m ON m.id_modulo = COALESCE(pr.id_modulo, ra.id_modulo)
         WHERE TRIM(COALESCE(pr.curso_escolar, "")) = :curso_escolar_texto
           AND TRIM(COALESCE(pr.ciclo, "")) = :ciclo_texto
         ORDER BY m.codigo ASC, CAST(ra.numero AS UNSIGNED) ASC, ra.numero ASC, pr.id_practica_ra ASC';

      $ras = [];
      $rasSqlUsed = '';
      $rasParams = [];
      $usedFallback = false;

      if ($idCursoEscolar > 0 && $idCiclo > 0) {
        $rasSqlUsed = $rasSqlIds;
        $rasParams = ['id_curso_escolar' => $idCursoEscolar, 'id_ciclo' => $idCiclo];
        $rasStmt = $pdo->prepare($rasSqlIds);
        $rasStmt->execute($rasParams);
        $ras = $rasStmt->fetchAll(PDO::FETCH_ASSOC);
      } else {
        error_log('[informe_tutor_empresa] No se pueden buscar RAs porque id_curso_escolar o id_ciclo son 0. id_practica=' . $id_practica . ', id_curso_escolar=' . $idCursoEscolar . ', id_ciclo=' . $idCiclo);
      }

      if (count($ras) === 0 && $cursoEscolarTexto !== '' && $cicloTexto !== '') {
        $usedFallback = true;
        $rasSqlUsed = $rasSqlFallback;
        $rasParams = ['curso_escolar_texto' => $cursoEscolarTexto, 'ciclo_texto' => $cicloTexto];
        $rasStmt = $pdo->prepare($rasSqlFallback);
        $rasStmt->execute($rasParams);
        $ras = $rasStmt->fetchAll(PDO::FETCH_ASSOC);
      }

      if ($debugRas) {
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Debug RAs</title><style>body{font-family:Arial,sans-serif;margin:20px}table{border-collapse:collapse;width:100%}th,td{border:1px solid #ccc;padding:6px;vertical-align:top}pre{background:#f5f5f5;padding:8px;white-space:pre-wrap}</style></head><body>';
        echo '<h1>Debug RAs informe tutor empresa</h1><ul>';
        echo '<li><strong>id_practica</strong>: ' . e((string)$id_practica) . '</li>';
        echo '<li><strong>id_alumno</strong>: ' . e((string)($practice['id_alumno'] ?? '')) . '</li>';
        echo '<li><strong>id_curso_escolar de la práctica</strong>: ' . e((string)($practice['id_curso_escolar'] ?? '')) . '</li>';
        echo '<li><strong>id_curso_escolar usado para buscar RAs</strong>: ' . e((string)$idCursoEscolar) . '</li>';
        echo '<li><strong>id_ciclo obtenido</strong>: ' . e((string)($practice['id_ciclo'] ?? '')) . '</li>';
        echo '<li><strong>id_ciclo usado para buscar RAs</strong>: ' . e((string)$idCiclo) . '</li>';
        echo '</ul><h2>Consulta SQL exacta usada para RAs</h2><pre>' . e($rasSqlUsed) . '</pre>';
        if ($usedFallback) echo '<p><strong>Se usó fallback por texto curso_escolar/ciclo.</strong></p>';
        if ($idCursoEscolar <= 0 || $idCiclo <= 0) echo '<p><strong>No se pueden buscar RAs porque id_curso_escolar o id_ciclo son 0.</strong></p>';
        echo '<h2>Parámetros usados</h2><pre>' . e((string)json_encode($rasParams, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>';
        echo '<h2>Número de filas devueltas</h2><p><strong>' . count($ras) . '</strong></p>';
        if (count($ras) === 0) echo '<p><strong>La consulta de RAs devuelve 0 filas.</strong></p>';
        echo '<table><thead><tr><th>#</th><th>id_practica_ra</th><th>codigo_modulo</th><th>nombre_modulo</th><th>ra_numero</th><th>ra_texto</th><th>RA_N_MODULO</th><th>RA_N_RESULTADO_APRENDIZAJE</th></tr></thead><tbody>';
        foreach ($ras as $idx => $raRow) {
          $raIndex++;
        $moduleName = trim((string)($raRow['nombre_modulo'] ?? ''));
          $raNumero = trim((string)($raRow['ra_numero'] ?? ''));
          $raTextoBase = trim((string)($raRow['ra_texto'] ?? ''));
          $raTextoFinal = $raTextoBase !== '' ? $raTextoBase : normalize_ra_for_display($raNumero);
          $raModuloFinal = trim((string)($raRow['codigo_modulo'] ?? ''));
          if ($raModuloFinal === '') $raModuloFinal = $moduleName;
          echo '<tr><td>' . ($idx + 1) . '</td><td>' . e((string)($raRow['id_practica_ra'] ?? '')) . '</td><td>' . e((string)($raRow['codigo_modulo'] ?? '')) . '</td><td>' . e($moduleName) . '</td><td>' . e($raNumero) . '</td><td>' . e($raTextoBase) . '</td><td>' . e($raModuloFinal) . '</td><td>' . e($raTextoFinal) . '</td></tr>';
        }
        echo '</tbody></table>';
        if (count($ras) === 0) echo '<p>Revisa si existen registros en practicas_ras para id_curso_escolar=' . e((string)$idCursoEscolar) . ' e id_ciclo=' . e((string)$idCiclo) . '.</p>';
        $diagSql = 'SELECT DISTINCT
            pr.id_curso_escolar,
            pr.id_ciclo,
            pr.curso_escolar,
            pr.ciclo,
            COUNT(*) AS total
           FROM practicas_ras pr
           GROUP BY pr.id_curso_escolar, pr.id_ciclo, pr.curso_escolar, pr.ciclo
           ORDER BY pr.id_curso_escolar, pr.id_ciclo';
        $diagRows = $pdo->query($diagSql)->fetchAll(PDO::FETCH_ASSOC);
        echo '<h2>Diagnóstico de datos en practicas_ras</h2><pre>' . e($diagSql) . '</pre>';
        echo '<table><thead><tr><th>id_curso_escolar</th><th>id_ciclo</th><th>curso_escolar</th><th>ciclo</th><th>total</th></tr></thead><tbody>';
        foreach ($diagRows as $diagRow) {
          echo '<tr><td>' . e((string)($diagRow['id_curso_escolar'] ?? '')) . '</td><td>' . e((string)($diagRow['id_ciclo'] ?? '')) . '</td><td>' . e((string)($diagRow['curso_escolar'] ?? '')) . '</td><td>' . e((string)($diagRow['ciclo'] ?? '')) . '</td><td>' . e((string)($diagRow['total'] ?? '')) . '</td></tr>';
        }
        echo '</tbody></table>';

        echo '</body></html>';
        exit;
      }

      $rasRowsHtml = '';
      $modulosLeyenda = [];
      $raIndex = 0;
      foreach ($ras as $raRow) {
        if (!is_array($raRow)) {
          continue;
        }

        $raIndex++;
        $moduleName = trim((string)($raRow['nombre_modulo'] ?? ''));
        $codigoModulo = trim((string)($raRow['codigo_modulo'] ?? ''));
        $raNumero = trim((string)($raRow['ra_numero'] ?? ''));
        $raTexto = trim((string)($raRow['ra_texto'] ?? ''));
        $raTextoFinal = $raTexto !== '' ? $raTexto : normalize_ra_for_display($raNumero);
        $raModuloFinal = $codigoModulo !== '' ? $codigoModulo : $moduleName;

        $claveLeyenda = $codigoModulo !== '' ? $codigoModulo : $moduleName;
        if ($claveLeyenda !== '' && !isset($modulosLeyenda[$claveLeyenda])) {
          if ($codigoModulo !== '' && $moduleName !== '') {
            $modulosLeyenda[$claveLeyenda] = $codigoModulo . ' - ' . $moduleName;
          } elseif ($codigoModulo !== '') {
            $modulosLeyenda[$claveLeyenda] = $codigoModulo;
          } else {
            $modulosLeyenda[$claveLeyenda] = $moduleName;
          }
        }

        $rasRowsHtml .= '<tr>';
        $rasRowsHtml .= '<td class="modulo-cell">' . e($raModuloFinal) . '</td>';
        $rasRowsHtml .= '<td>' . e($raTextoFinal) . '</td>';
        $rasRowsHtml .= '<td class="check-cell"><input type="checkbox" id="ra_' . $raIndex . '_superado" name="ra_' . $raIndex . '_superado" value="ON" style="font-size:20pt;" onchange="marcarExclusivo(this, \'ra_' . $raIndex . '_no_superado\');"></td>';
        $rasRowsHtml .= '<td class="check-cell"><input type="checkbox" id="ra_' . $raIndex . '_no_superado" name="ra_' . $raIndex . '_no_superado" value="ON" style="font-size:20pt;" onchange="marcarExclusivo(this, \'ra_' . $raIndex . '_superado\');"></td>';
        $rasRowsHtml .= '</tr>';
      }

      if ($rasRowsHtml === '') {
        $rasRowsHtml = '<tr><td></td><td></td><td class="check-cell"><input type="checkbox" id="ra_1_superado" name="ra_1_superado" value="ON" style="font-size:20pt;" onchange="marcarExclusivo(this, \'ra_1_no_superado\');"></td><td class="check-cell"><input type="checkbox" id="ra_1_no_superado" name="ra_1_no_superado" value="ON" style="font-size:20pt;" onchange="marcarExclusivo(this, \'ra_1_superado\');"></td></tr>';
      }
      $repl['{{RAS_ROWS}}'] = $rasRowsHtml;
      $leyendaModulos = '';
      if (!empty($modulosLeyenda)) {
        $leyendaModulos = 'Módulos: ' . implode('; ', array_values($modulosLeyenda)) . '.';
      }
      $repl['{{LEYENDA_MODULOS}}'] = e($leyendaModulos);

      if (count($ras) === 0) {
        error_log('[informe_tutor_empresa] No se encontraron RAs para id_practica=' . $id_practica . ', id_ciclo=' . $idCiclo . ', id_curso_escolar=' . $idCursoEscolar);
      }

      $html = strtr($htmlTemplate, $repl);

      if ($isLocal) {
        $firstRow = $ras[0] ?? [];
        $firstModule = trim((string)($firstRow['codigo_modulo'] ?? ''));
        if ($firstModule === '') {
          $firstModule = trim((string)($firstRow['nombre_modulo'] ?? ''));
        }
        $firstRa = trim((string)($firstRow['ra_texto'] ?? ''));
        if ($firstRa === '') $firstRa = normalize_ra_for_display(trim((string)($firstRow['ra_numero'] ?? '')));
        $html .= "\n<!-- Debug RAs informe tutor:\n"
          . 'id_practica=' . $id_practica . "\n"
          . 'id_ciclo=' . $idCiclo . "\n"
          . 'id_curso_escolar=' . $idCursoEscolar . "\n"
          . 'ras_encontrados=' . count($ras) . "\n"
          . 'primer_modulo=' . $firstModule . "\n"
          . 'primer_ra=' . $firstRa . "\n"
          . 'sql=' . preg_replace('/\s+/', ' ', trim($rasSqlUsed)) . "\n"
          . 'fallback=' . ($usedFallback ? '1' : '0') . "\n"
          . 'parametros=' . json_encode($rasParams, JSON_UNESCAPED_UNICODE) . "\n"
          . "-->\n";
      }
    }
    if (preg_match('/{{[^}]+}}/', $html, $m) === 1) {
      throw new RuntimeException('Quedan placeholders sin sustituir en la plantilla. Ejemplo: ' . $m[0]);
    }
    $mpdfConfig['margin_left'] = 10;
    $mpdfConfig['margin_right'] = 10;
    $mpdfConfig['margin_top'] = 8;
    $mpdfConfig['margin_bottom'] = 7;
    $mpdfConfig['margin_header'] = 0;
    $mpdfConfig['margin_footer'] = 0;
  }

  if ($debugHtml) {
    $debugHtmlPath = $docsDir . DIRECTORY_SEPARATOR . 'debug_informe_tutor_empresa.html';
    @file_put_contents($debugHtmlPath, $html);
    while (ob_get_level() > 0) ob_end_clean();
    header('Content-Type: text/html; charset=UTF-8');
    echo $html;
    exit;
  }

  $tmpPdf = $docsDir . DIRECTORY_SEPARATOR . 'tmp_informe_tutor_' . uniqid('', true) . '.pdf';
  $fase = 'generando PDF con mPDF';
  $mpdf = new Mpdf($mpdfConfig);
  $mpdf->SetBasePath($docsDir . DIRECTORY_SEPARATOR);
  $mpdf->shrink_tables_to_fit = 1;
  $mpdf->useActiveForms = true;

  $fase = 'escribiendo HTML en mPDF';
  $mpdf->WriteHTML($html);

  $fase = 'guardando PDF temporal';
  $mpdf->Output($tmpPdf, \Mpdf\Output\Destination::FILE);

  if (!is_file($tmpPdf) || filesize($tmpPdf) < 512) throw new RuntimeException('PDF generado vacío o demasiado pequeño.');
  $head = file_get_contents($tmpPdf, false, null, 0, 5);
  if ($head !== '%PDF-') throw new RuntimeException('PDF corrupto: cabecera inválida.');

  $apellido1 = limpiarNombreArchivo((string)($practice['alumno_apellido1'] ?? ''));
  $nombre = limpiarNombreArchivo((string)($practice['alumno_nombre'] ?? ''));
  if ($apellido1 !== '' && $nombre !== '') {
    $filename = 'informe_final_tutor_rellenable_' . $apellido1 . '_' . $nombre . '.pdf';
  } else {
    $filename = 'informe_final_tutor_rellenable_practica_' . $id_practica . '.pdf';
  }
  while (ob_get_level() > 0) ob_end_clean();
  header('Content-Type: application/pdf');
  header('Content-Length: ' . (string) filesize($tmpPdf));
  header("Content-Disposition: attachment; filename*=UTF-8''" . rawurlencode($filename));
  readfile($tmpPdf);
} catch (Throwable $e) {
  error_log('[generar_informe_valoracion_tutor_empresa_rellenable] id_practica=' . $id_practica . ' fase=' . $fase . ' message=' . $e->getMessage());
  while (ob_get_level() > 0) ob_end_clean();
  $isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1'], true)
    || in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
  http_response_code(500);
  if ($isLocal) {
    echo '<h1>No se pudo generar el informe.</h1><p><strong>Fase:</strong> ' . e($fase) . '</p><p><strong>ID práctica:</strong> ' . $id_practica . '</p><p><strong>Error real:</strong> ' . e($e->getMessage()) . '</p>';
    if ((($_GET['debug_plain'] ?? '0') === '1')) echo '<p>debug_plain falla: mPDF/configuración.</p>';
    if ((($_GET['debug_plain_with_margins'] ?? '0') === '1')) echo '<p>debug_plain_with_margins falla: márgenes.</p>';
    if ((($_GET['debug_fake_data'] ?? '0') === '1')) echo '<p>debug_fake_data falla: plantilla.</p>';
    exit;
  }
  echo 'No se pudo generar el informe. Revisa el log de errores del servidor.';
} finally {
  if ($tmpPdf !== '' && is_file($tmpPdf)) @unlink($tmpPdf);
}
