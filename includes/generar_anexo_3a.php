<?php
declare(strict_types=1);

use Mpdf\Mpdf;

require_once __DIR__ . '/../vendor/autoload.php';

function image_file_to_data_uri(string $path): string
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

function embed_local_images_as_base64(string $html, string $docsDir): string
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
            $dataUri = image_file_to_data_uri($path);
        } catch (RuntimeException $e) {
            return $matches[0];
        }

        return $prefix . $dataUri . $suffix;
    }, $html) ?? $html;
}

function sanitize_utf8_filename_base(string $text, int $idAlumno): string
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

function generar_anexo_3a_descarga(PDO $pdo, int $id_alumno, int $id_curso_escolar, int $id_grupo, string $tipo = 'normal'): void
{
    if (!in_array($tipo, ['normal', 'firma'], true)) {
        throw new RuntimeException('El tipo de Anexo 3A no es válido.');
    }

    if ($id_alumno <= 0) {
        throw new RuntimeException('El identificador del alumno no es válido.');
    }

    $stmt = $pdo->prepare(
        'SELECT a.id_alumno, a.nombre, a.apellido1, a.apellido2, a.faltas_10_dia, a.faltas_10_cantidad,
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
    if (empty($alumno['faltas_10_dia']) || $alumno['faltas_10_cantidad'] === null || $alumno['faltas_10_cantidad'] === '') {
        throw new RuntimeException('No se puede generar el Anexo 3A: faltan datos de alcance del 10% (fecha o cantidad).');
    }

    $fecha10 = date_create((string) $alumno['faltas_10_dia']);
    if ($fecha10 === false) {
        throw new RuntimeException('La fecha de alcance del 10% no tiene un formato válido.');
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

    $faltas10 = (float) $alumno['faltas_10_cantidad'];
    $maximo15 = (float) floor(($horasTotales * 0.15) * 100) / 100;
    $restantes = max(0.0, $maximo15 - $faltas10);

    $projectRoot = realpath(__DIR__ . '/..');
    if ($projectRoot === false) {
        throw new RuntimeException('No se pudo resolver la raíz del proyecto.');
    }
    $docsDir = $projectRoot . DIRECTORY_SEPARATOR . 'docs';
    $templatePath = $docsDir . DIRECTORY_SEPARATOR . 'modelo_3A.html';
    $mpdfTempDir = $docsDir . DIRECTORY_SEPARATOR . 'tmp_mpdf';
    $tmpAnexosDir = $docsDir . DIRECTORY_SEPARATOR . 'tmp_anexos_3a';

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
    $html = embed_local_images_as_base64($html, $docsDir);

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
    $fechaLarga = (int)$fecha10->format('j') . ' de ' . $meses[(int)$fecha10->format('n')] . ' de ' . $fecha10->format('Y');

    $html = preg_replace('/El total de horas del Ciclo Formativo[\s\S]*?<\/p>/', $parrafoCiclo . '</p>', $html, 1) ?? $html;
    $html = preg_replace('/<span class="bold">ALUMNO\/A:<\/span>[^<]+/i', '<span class="bold">ALUMNO/A:</span> ' . htmlspecialchars(mb_strtoupper($nombreCompleto, 'UTF-8'), ENT_QUOTES, 'UTF-8'), $html, 1) ?? $html;
    $html = preg_replace('/<span class="bold">CICLO FORMATIVO:<\/span>[^<]+/i', '<span class="bold">CICLO FORMATIVO:</span> ' . htmlspecialchars(mb_strtoupper($ciclo, 'UTF-8'), ENT_QUOTES, 'UTF-8'), $html, 1) ?? $html;
    $html = preg_replace('/ha faltado de manera injustificada a <span class="bold">[^<]+<\/span>/', 'ha faltado de manera injustificada a <span class="bold">' . rtrim(rtrim(number_format($faltas10, 2, '.', ''), '0'), '.') . ' horas</span>', $html, 1) ?? $html;
    $html = preg_replace('/anulación de su matrícula es de <span class="bold">[^<]+<\/span> y que, en consecuencia,\s*sólo le quedan <span class="bold">[^<]+<\/span>/', 'anulación de su matrícula es de <span class="bold">' . rtrim(rtrim(number_format($maximo15, 2, '.', ''), '0'), '.') . ' horas</span> y que, en consecuencia, sólo le quedan <span class="bold">' . rtrim(rtrim(number_format($restantes, 2, '.', ''), '0'), '.') . '</span>', $html, 1) ?? $html;
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

    $base = sanitize_utf8_filename_base(trim((($alumno['apellido1'] ?? '') . '_' . ($alumno['apellido2'] ?? '') . '_' . ($alumno['nombre'] ?? ''))), $id_alumno);

    $pdfPath = $tmpDir . '/Anexo_3A_' . $base . '_' . $tipo . '_' . bin2hex(random_bytes(4)) . '.pdf';

    if (strpos($html, 'cabecera.png') !== false) {
        throw new RuntimeException('La cabecera no se ha incrustado en base64. El HTML aún contiene cabecera.png.');
    }
    if (strpos($html, 'data:image/') === false) {
        throw new RuntimeException('El HTML no contiene ninguna imagen embebida en base64.');
    }

    $pdfUnico = new Mpdf(['tempDir' => $mpdfTmp, 'mode' => 'utf-8', 'format' => 'A4', 'default_font' => 'dejavusans', 'allow_output_buffering' => true]);
    $pdfUnico->SetBasePath($docsDir . DIRECTORY_SEPARATOR);
    $pdfUnico->WriteHTML($html);
    if ($tipo === 'firma') {
        $selloPath = $docsDir . DIRECTORY_SEPARATOR . 'sello_recibido.png';
        if (!is_file($selloPath) || !is_readable($selloPath)) {
            throw new RuntimeException('No se encontró o no se puede leer sello_recibido.png en: ' . $selloPath);
        }

        $pdfUnico->page = 1;
        if (method_exists($pdfUnico, 'SetPage')) {
            $pdfUnico->SetPage(1);
        }

        if (method_exists($pdfUnico, 'StartTransform') && method_exists($pdfUnico, 'Rotate') && method_exists($pdfUnico, 'StopTransform')) {
            $pdfUnico->StartTransform();
            $pdfUnico->Rotate(-5, 155, 222);
            $pdfUnico->Image($selloPath, 120, 190, 72, 0, 'png');
            $pdfUnico->StopTransform();
        } elseif (method_exists($pdfUnico, 'Rotate')) {
            $pdfUnico->Rotate(-5, 155, 222);
            $pdfUnico->Image($selloPath, 120, 190, 72, 0, 'png');
            $pdfUnico->Rotate(0);
        } else {
            $pdfUnico->Image($selloPath, 120, 190, 72, 0, 'png');
        }
    }
    $pdfUnico->Output($pdfPath, \Mpdf\Output\Destination::FILE);

    register_shutdown_function(static function () use ($pdfPath): void {
        if (is_file($pdfPath)) {
            @unlink($pdfPath);
        }
    });

    $downloadName = 'Anexo_3A_' . $base . ($tipo === 'firma' ? '_firma' : '') . '.pdf';
    $asciiFallback = preg_replace('/[^A-Za-z0-9_.-]/', '_', $downloadName) ?? 'Anexo_3A_alumno_' . $id_alumno . '.pdf';

    header('Content-Type: application/pdf');
    header("Content-Disposition: attachment; filename=\"" . $asciiFallback . "\"; filename*=UTF-8''" . rawurlencode($downloadName));
    header('Content-Length: ' . (string) filesize($pdfPath));
    readfile($pdfPath);
    exit;
}
