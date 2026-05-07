<?php
declare(strict_types=1);

use Mpdf\Mpdf;

require_once __DIR__ . '/../vendor/autoload.php';

function image_file_to_data_uri_4a(string $path): string
{
    if (!is_file($path) || !is_readable($path)) {
        throw new RuntimeException('No se puede leer la imagen: ' . $path);
    }

    $mime = '';
    if (function_exists('mime_content_type')) {
        $mime = (string) mime_content_type($path);
    }

    if ($mime === '' || strpos($mime, 'image/') !== 0) {
        $ext = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
        $mimeMap = [
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'bmp' => 'image/bmp',
            'svg' => 'image/svg+xml',
        ];
        $mime = $mimeMap[$ext] ?? '';
    }

    if ($mime === '' || strpos($mime, 'image/') !== 0) {
        throw new RuntimeException('No se pudo determinar un MIME de imagen válido para: ' . $path);
    }

    $data = file_get_contents($path);
    if ($data === false) {
        throw new RuntimeException('No se pudo leer el contenido de la imagen: ' . $path);
    }

    return 'data:' . $mime . ';base64,' . base64_encode($data);
}

function embed_local_images_as_base64_4a(string $html, string $docsDir): string
{
    return preg_replace_callback('~(<img\b[^>]*\bsrc=["\'])([^"\']+)(["\'][^>]*>)~iu', static function (array $matches) use ($docsDir): string {
        $prefix = $matches[1];
        $src = trim(html_entity_decode($matches[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $suffix = $matches[3];

        if ($src === '' || preg_match('#^(data:|https?:|file:)#i', $src) === 1 || preg_match('#^//#', $src) === 1) {
            return $matches[0];
        }

        $relative = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $src), DIRECTORY_SEPARATOR);
        $path = $docsDir . DIRECTORY_SEPARATOR . $relative;

        if (!is_file($path) || !is_readable($path)) {
            return $matches[0];
        }

        try {
            $dataUri = image_file_to_data_uri_4a($path);
        } catch (RuntimeException $e) {
            return $matches[0];
        }

        return $prefix . $dataUri . $suffix;
    }, $html) ?? $html;
}

function sanitize_utf8_filename_base_4a(string $text, int $idAlumno): string
{
    $base = trim($text);
    $base = preg_replace('/\s+/u', '_', $base) ?? '';
    $base = preg_replace('/[\/\\:*?"<>|\x00-\x1F]/u', '', $base) ?? '';
    $base = trim($base, "._- \t\n\r\0\x0B");

    if ($base === '') {
        $base = 'alumno_' . $idAlumno;
    }

    return $base;
}

function generar_anexo_4a_descarga(PDO $pdo, int $id_alumno, int $id_curso_escolar, int $id_grupo, string $tipo = 'normal'): void
{
    if (!in_array($tipo, ['normal', 'firma'], true)) {
        throw new RuntimeException('El tipo de Anexo 4A no es válido.');
    }

    if ($id_alumno <= 0) {
        throw new RuntimeException('El identificador del alumno no es válido.');
    }

    $stmt = $pdo->prepare(
        'SELECT a.id_alumno, a.nombre, a.apellido1, a.apellido2, a.faltas_15_dia, a.faltas_15_cantidad,
                g.grupo, c.ciclo, ce.curso_escolar
         FROM alumnos a
         INNER JOIN alumno_curso ac ON ac.id_alumno = a.id_alumno
         LEFT JOIN grupos g ON g.id_grupo = ac.id_grupo
         LEFT JOIN ciclos c ON c.id_ciclo = ac.id_ciclo
         LEFT JOIN cursos_escolares ce ON ce.id_curso_escolar = ac.id_curso_escolar
         WHERE a.id_alumno = :id_alumno
           AND ac.id_curso_escolar = :id_curso_escolar
           AND ac.id_grupo = :id_grupo
         LIMIT 1'
    );
    $stmt->execute([
        'id_alumno' => $id_alumno,
        'id_curso_escolar' => $id_curso_escolar,
        'id_grupo' => $id_grupo,
    ]);
    $alumno = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$alumno) {
        throw new RuntimeException('No se encontró el alumno en el contexto de curso/grupo seleccionado.');
    }
    if (empty($alumno['faltas_15_dia']) || $alumno['faltas_15_cantidad'] === null || $alumno['faltas_15_cantidad'] === '') {
        throw new RuntimeException('No se puede generar el Anexo 4A: faltan datos de alcance del 15% (fecha o cantidad).');
    }

    $fecha15 = date_create((string) $alumno['faltas_15_dia']);
    if ($fecha15 === false) {
        throw new RuntimeException('La fecha de alcance del 15% no tiene un formato válido.');
    }

    $modulosStmt = $pdo->prepare(
        'SELECT DISTINCT m.id_modulo, COALESCE(m.horas_totales, 0) AS horas_totales,
                COALESCE(NULLIF(TRIM(m.materia_general), \'\'), NULLIF(TRIM(m.materia_propia), \'\'), CONCAT(\'Módulo \', m.id_modulo)) AS modulo_nombre,
                COALESCE(m.horas_semanales, 0) AS horas_semanales,
                LOWER(COALESCE(m.materia_general, \'\')) AS mg,
                LOWER(COALESCE(m.materia_propia, \'\')) AS mp
         FROM alumno_modulo am
         INNER JOIN modulos m ON m.id_modulo = am.id_modulo
         WHERE am.id_alumno = :id_alumno'
    );
    $modulosStmt->execute(['id_alumno' => $id_alumno]);
    $modulosRaw = $modulosStmt->fetchAll(PDO::FETCH_ASSOC);

    $modulos = [];
    $horasTotales = 0.0;
    foreach ($modulosRaw as $mod) {
        $esProyecto = str_contains((string) $mod['mg'], 'proyecto intermodular') || str_contains((string) $mod['mp'], 'proyecto intermodular');
        $incluir = ((float) $mod['horas_semanales'] > 0) || $esProyecto;
        if (!$incluir) {
            continue;
        }
        $horas = (float) $mod['horas_totales'];
        $modulos[] = [
            'nombre' => (string) $mod['modulo_nombre'],
            'horas' => $horas,
        ];
        $horasTotales += $horas;
    }

    $faltas15 = (float) $alumno['faltas_15_cantidad'];
    $porcentajeReal = $horasTotales > 0 ? ($faltas15 / $horasTotales) * 100 : 0.0;

    $projectRoot = realpath(__DIR__ . '/..');
    if ($projectRoot === false) {
        throw new RuntimeException('No se pudo resolver la raíz del proyecto.');
    }
    $docsDir = $projectRoot . DIRECTORY_SEPARATOR . 'docs';
    $templatePath = $docsDir . DIRECTORY_SEPARATOR . 'modelo_4A.html';
    $mpdfTempDir = $docsDir . DIRECTORY_SEPARATOR . 'tmp_mpdf';
    $tmpAnexosDir = $docsDir . DIRECTORY_SEPARATOR . 'tmp_anexos_4a';

    if (!is_dir($docsDir)) {
        throw new RuntimeException('No se encontró la carpeta docs: ' . $docsDir);
    }
    if (!is_file($templatePath)) {
        throw new RuntimeException('No se encontró la plantilla: ' . $templatePath);
    }
    if (!is_readable($templatePath)) {
        throw new RuntimeException('La plantilla no tiene permisos de lectura: ' . $templatePath);
    }
    $cabeceraPath = $docsDir . DIRECTORY_SEPARATOR . 'cabecera.png';
    if (!is_file($cabeceraPath)) {
        throw new RuntimeException('No se encontró cabecera.png en: ' . $cabeceraPath);
    }
    if (!extension_loaded('gd')) {
        throw new RuntimeException('La extensión GD de PHP no está activa. mPDF puede necesitar GD para procesar imágenes PNG/JPG. Activa extension=gd en php.ini y reinicia Apache.');
    }

    $html = (string) file_get_contents($templatePath);
    $html = embed_local_images_as_base64_4a($html, $docsDir);

    $nombreCompleto = trim($alumno['apellido1'] . ' ' . $alumno['apellido2'] . ', ' . $alumno['nombre']);
    $ciclo = trim((string) ($alumno['ciclo'] ?? ''));
    $cursoEscolar = trim((string) ($alumno['curso_escolar'] ?? ''));
    $modulosTexto = [];
    foreach ($modulos as $m) {
        $modulosTexto[] = $m['nombre'] . ' (' . rtrim(rtrim(number_format($m['horas'], 2, '.', ''), '0'), '.') . ' h)';
    }
    $listadoModulos = implode(', ', $modulosTexto);

    $parrafoCiclo = 'El total de horas del Ciclo Formativo';
    if ($ciclo !== '') {
        $parrafoCiclo .= ' ' . $ciclo;
    }
    if ($cursoEscolar !== '') {
        $parrafoCiclo .= ' en el curso ' . $cursoEscolar;
    }
    $parrafoCiclo .= ' es de ' . rtrim(rtrim(number_format($horasTotales, 2, '.', ''), '0'), '.') . ' horas distribuidas de la siguiente forma: ' . $listadoModulos . '.';

    $meses = [1=>'enero',2=>'febrero',3=>'marzo',4=>'abril',5=>'mayo',6=>'junio',7=>'julio',8=>'agosto',9=>'septiembre',10=>'octubre',11=>'noviembre',12=>'diciembre'];
    $fechaLarga = (int)$fecha15->format('j') . ' de ' . $meses[(int)$fecha15->format('n')] . ' de ' . $fecha15->format('Y');

    $html = preg_replace('/El total de horas del Ciclo Formativo[\s\S]*?<\/p>/', $parrafoCiclo . '</p>', $html, 1) ?? $html;
    $html = preg_replace('/<span class="bold">ALUMNO\/A:<\/span>[^<]+/i', '<span class="bold">ALUMNO/A:</span> ' . htmlspecialchars(mb_strtoupper($nombreCompleto, 'UTF-8'), ENT_QUOTES, 'UTF-8'), $html, 1) ?? $html;
    $html = preg_replace('/<span class="bold">CICLO FORMATIVO:<\/span>[^<]+/i', '<span class="bold">CICLO FORMATIVO:</span> ' . htmlspecialchars(mb_strtoupper($ciclo, 'UTF-8'), ENT_QUOTES, 'UTF-8'), $html, 1) ?? $html;
    $faltasTexto = rtrim(rtrim(number_format($faltas15, 2, '.', ''), '0'), '.');
    $porcentajeTexto = number_format($porcentajeReal, 2, ',', '');
    if (strpos($porcentajeTexto, ',') !== false) {
        $porcentajeTexto = rtrim(rtrim($porcentajeTexto, '0'), ',');
    }

    $html = preg_replace('/resolución asciende\s*<span class="bold">[^<]+<\/span>,\s*lo que equivale al\s*<span class="bold">[^<]+<\/span>/iu', 'resolución asciende <span class="bold">' . $faltasTexto . '</span>, lo que equivale al <span class="bold">' . $porcentajeTexto . ' %</span>', $html, 1) ?? $html;
    $html = preg_replace('/En Getafe, a [^<]+/i', 'En Getafe, a ' . $fechaLarga, $html, 1) ?? $html;

    $tmpDir = $tmpAnexosDir;
    $mpdfTmp = $mpdfTempDir;

    if (!is_dir($tmpDir) && !mkdir($tmpDir, 0775, true) && !is_dir($tmpDir)) {
        throw new RuntimeException('No se pudo crear la carpeta temporal de anexos: ' . $tmpDir);
    }
    if (!is_writable($tmpDir)) {
        throw new RuntimeException('La carpeta temporal de anexos no tiene permisos de escritura: ' . $tmpDir);
    }

    if (!is_dir($mpdfTmp) && !mkdir($mpdfTmp, 0775, true) && !is_dir($mpdfTmp)) {
        throw new RuntimeException('No se pudo crear la carpeta temporal de mPDF: ' . $mpdfTmp);
    }
    if (!is_writable($mpdfTmp)) {
        throw new RuntimeException('La carpeta temporal de mPDF no tiene permisos de escritura: ' . $mpdfTmp);
    }

    $base = sanitize_utf8_filename_base_4a(trim((($alumno['apellido1'] ?? '') . '_' . ($alumno['apellido2'] ?? '') . '_' . ($alumno['nombre'] ?? ''))), $id_alumno);

    $tmpBasePdf = $tmpDir . DIRECTORY_SEPARATOR . 'base_' . uniqid('', true) . '.pdf';
    $tmpFinalPdf = $tmpDir . DIRECTORY_SEPARATOR . 'final_' . uniqid('', true) . '.pdf';
    $pdfPath = $tmpBasePdf;

    if (preg_match('/<img\b[^>]*\bsrc=["\'](?:\./)?cabecera\.png["\']/i', $html) === 1) {
        throw new RuntimeException('La cabecera no se ha incrustado en base64. El HTML aún contiene src="cabecera.png".');
    }
    if (strpos($html, 'data:image/') === false) {
        throw new RuntimeException('El HTML no contiene ninguna imagen embebida en base64.');
    }

    $pdfUnico = new Mpdf(['tempDir' => $mpdfTmp, 'mode' => 'utf-8', 'format' => 'A4', 'default_font' => 'dejavusans', 'allow_output_buffering' => true]);
    $pdfUnico->SetBasePath($docsDir . DIRECTORY_SEPARATOR);
    $pdfUnico->WriteHTML($html);
    $pdfUnico->Output($tmpBasePdf, \Mpdf\Output\Destination::FILE);

    if (!is_file($tmpBasePdf) || filesize($tmpBasePdf) <= 0) {
        throw new RuntimeException('El PDF base del Anexo 4A no se ha generado correctamente o está vacío: ' . $tmpBasePdf);
    }

    if ($tipo === 'normal') {
        $cleanPdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'dejavusans',
            'tempDir' => $mpdfTmp,
            'allow_output_buffering' => true,
        ]);

        if (isset($cleanPdf->pages) && is_array($cleanPdf->pages)) {
            $cleanPdf->pages = [];
            $cleanPdf->page = 0;
        }

        $pageCount = $cleanPdf->SetSourceFile($tmpBasePdf);
        if ($pageCount < 1) {
            throw new RuntimeException('El PDF base del Anexo 4A no contiene páginas para generar la versión normal.');
        }

        $templateId = $cleanPdf->ImportPage(1);
        $size = $cleanPdf->GetTemplateSize($templateId);
        $cleanPdf->AddPage($size['orientation']);
        $cleanPdf->UseTemplate($templateId, 0, 0, $size['width'], $size['height']);

        if (is_file($tmpFinalPdf)) {
            unlink($tmpFinalPdf);
        }

        $cleanPdf->Output($tmpFinalPdf, \Mpdf\Output\Destination::FILE);

        if (!is_file($tmpFinalPdf) || filesize($tmpFinalPdf) <= 0) {
            throw new RuntimeException('El PDF final del Anexo 4A (normal) no se ha generado correctamente o está vacío: ' . $tmpFinalPdf);
        }

        $pdfPath = $tmpFinalPdf;
    }

    if ($tipo === 'firma') {
        $selloPath = $docsDir . DIRECTORY_SEPARATOR . 'sello_recibido.png';
        if (!is_file($selloPath) || !is_readable($selloPath)) {
            throw new RuntimeException('No se encontró o no se puede leer sello_recibido.png en: ' . $selloPath);
        }

        $stamper = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'dejavusans',
            'tempDir' => $mpdfTmp,
            'allow_output_buffering' => true,
        ]);

        if (isset($stamper->pages) && is_array($stamper->pages)) {
            $stamper->pages = [];
            $stamper->page = 0;
        }

        $pageCount = $stamper->SetSourceFile($tmpBasePdf);
        if ($pageCount < 1) {
            throw new RuntimeException('El PDF base del Anexo 4A no contiene páginas para estampar.');
        }

        $templateId = $stamper->ImportPage(1);
        $size = $stamper->GetTemplateSize($templateId);
        $stamper->AddPage($size['orientation']);
        $stamper->UseTemplate($templateId, 0, 0, $size['width'], $size['height']);

        if (method_exists($stamper, 'StartTransform') && method_exists($stamper, 'Rotate') && method_exists($stamper, 'StopTransform')) {
            $stamper->StartTransform();
            $stamper->Rotate(-5, 155, 222);
            $stamper->Image($selloPath, 120, 190, 72, 0, 'png');
            $stamper->StopTransform();
        } elseif (method_exists($stamper, 'Rotate')) {
            $stamper->Rotate(-5, 155, 222);
            $stamper->Image($selloPath, 120, 190, 72, 0, 'png');
            $stamper->Rotate(0);
        } else {
            $stamper->Image($selloPath, 120, 190, 72, 0, 'png');
        }

        if (is_file($tmpFinalPdf)) {
            unlink($tmpFinalPdf);
        }

        $stamper->Output($tmpFinalPdf, \Mpdf\Output\Destination::FILE);

        if (!is_file($tmpFinalPdf) || filesize($tmpFinalPdf) <= 0) {
            throw new RuntimeException('El PDF final del Anexo 4A (firma) no se ha generado correctamente o está vacío: ' . $tmpFinalPdf);
        }

        $pdfPath = $tmpFinalPdf;
    }

    register_shutdown_function(static function () use ($tmpBasePdf, $tmpFinalPdf): void {
        if (is_file($tmpBasePdf)) {
            @unlink($tmpBasePdf);
        }
        if (is_file($tmpFinalPdf)) {
            @unlink($tmpFinalPdf);
        }
    });

    if (!is_file($pdfPath) || filesize($pdfPath) <= 0) {
        throw new RuntimeException('El PDF del Anexo 4A no se ha generado correctamente o está vacío: ' . $pdfPath);
    }

    $downloadName = 'Anexo_4A_' . $base . ($tipo === 'firma' ? '_firma' : '') . '.pdf';
    $asciiFallback = preg_replace('/[^A-Za-z0-9_.-]/', '_', $downloadName) ?? 'Anexo_4A_alumno_' . $id_alumno . '.pdf';

    header('Content-Type: application/pdf');
    header("Content-Disposition: attachment; filename=\"" . $asciiFallback . "\"; filename*=UTF-8''" . rawurlencode($downloadName));
    header('Content-Length: ' . (string) filesize($pdfPath));
    readfile($pdfPath);
    exit;
}
