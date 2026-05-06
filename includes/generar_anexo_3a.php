<?php
declare(strict_types=1);

use Mpdf\Mpdf;

require_once __DIR__ . '/../vendor/autoload.php';

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

    $plantilla = __DIR__ . '/../docs/modelo_3A.html';
    if (!is_file($plantilla)) {
        throw new RuntimeException('No se encontró la plantilla docs/modelo_3A.html.');
    }
    $html = (string) file_get_contents($plantilla);

    $templateDir = realpath(__DIR__ . '/../docs');
    if ($templateDir === false) {
        throw new RuntimeException('No se encontró la carpeta docs para resolver recursos del Anexo 3A.');
    }
    $html = preg_replace_callback('/(<img\b[^>]*\bsrc=")([^"]+)(")/i', static function (array $matches) use ($templateDir): string {
        $src = trim($matches[2]);
        if ($src === '' || preg_match('#^(https?:)?//#i', $src) === 1 || str_starts_with($src, 'file://') || str_starts_with($src, 'data:')) {
            return $matches[0];
        }
        $path = realpath($templateDir . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $src), DIRECTORY_SEPARATOR));
        if ($path !== false && is_file($path)) {
            return $matches[1] . 'file://' . str_replace(DIRECTORY_SEPARATOR, '/', $path) . $matches[3];
        }
        return preg_replace('/<img\b[^>]*>/i', '', $matches[0]) ?? '';
    }, $html) ?? $html;

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

    $tmpDir = __DIR__ . '/../docs/tmp_anexos_3a';
    $mpdfTmp = __DIR__ . '/../docs/tmp_mpdf';

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

    $base = preg_replace('/[^A-Za-z0-9_\-]/', '_', trim(($alumno['apellido1'] ?? '') . '_' . ($alumno['apellido2'] ?? '') . '_' . ($alumno['nombre'] ?? 'alumno')));
    $base = trim((string)$base, '_');
    if ($base === '') { $base = 'alumno_' . $id_alumno; }

    $pdfPath = $tmpDir . '/Anexo_3A_' . $base . '_' . $tipo . '_' . bin2hex(random_bytes(4)) . '.pdf';

    if ($tipo === 'firma') {
        $html .= '<div style="margin-top:14mm; border:1px solid #000; padding:10mm 8mm; text-align:center; font-size:10pt;">'
            . '<strong>RECIBÍ (FIRMA DEL ALUMNO/A O REPRESENTANTE LEGAL)</strong><br><br><br><br>'
            . 'Fecha: ____ / ____ / ________'
            . '</div>';
    }
    $pdfUnico = new Mpdf(['tempDir' => $mpdfTmp, 'mode' => 'utf-8', 'default_font' => 'dejavusans']);
    $pdfUnico->WriteHTML($html);
    $pdfUnico->Output($pdfPath, \Mpdf\Output\Destination::FILE);

    register_shutdown_function(static function () use ($pdfPath): void {
        if (is_file($pdfPath)) {
            @unlink($pdfPath);
        }
    });

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="Anexo_3A_' . $base . ($tipo === 'firma' ? '_firma' : '') . '.pdf"');
    header('Content-Length: ' . (string) filesize($pdfPath));
    readfile($pdfPath);
    exit;
}
