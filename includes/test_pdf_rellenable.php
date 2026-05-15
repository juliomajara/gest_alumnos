<?php
declare(strict_types=1);

use Mpdf\Mpdf;
use Mpdf\Output\Destination;

require_once __DIR__ . '/../vendor/autoload.php';

$projectRoot = realpath(__DIR__ . '/..');

if ($projectRoot === false) {
    http_response_code(500);
    exit('No se pudo resolver la raíz del proyecto.');
}

$tmpDir = $projectRoot . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'tmp_mpdf';

if (!is_dir($tmpDir) && !mkdir($tmpDir, 0775, true) && !is_dir($tmpDir)) {
    http_response_code(500);
    exit('No se pudo crear la carpeta temporal de mPDF.');
}

$mpdf = new Mpdf([
    'tempDir' => $tmpDir,
    'mode' => 'utf-8',
    'format' => 'A4',
    'default_font' => 'dejavusans',
    'margin_left' => 12,
    'margin_right' => 12,
    'margin_top' => 12,
    'margin_bottom' => 12,
    'allow_output_buffering' => true,
]);

$mpdf->useActiveForms = true;

$html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">

<style>
    body {
        font-family: dejavusans;
        font-size: 10pt;
        color: #000;
    }

    h1 {
        font-size: 14pt;
        text-align: center;
        margin-bottom: 8mm;
    }

    h2 {
        font-size: 11pt;
        margin-top: 7mm;
        margin-bottom: 2mm;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 5mm;
    }

    td,
    th {
        border: 0.2mm solid #000;
        padding: 2mm;
        vertical-align: top;
    }

    .textarea {
        font-family: dejavusans;
        font-size: 9pt;
        color: #000000;
        background-color: #ffffff;
        border: 0.2mm solid #777777;
        width: 100%;
    }

    .textarea-grande {
        height: 28mm;
    }

    .textarea-media {
        height: 22mm;
    }

    .center {
        text-align: center;
    }

    .checkbox-grande {
        font-size: 20pt;
    }
</style>
</head>

<body>

<h1>Test de campos multilínea con mPDF</h1>

<form>

<h2>1. Textarea con texto inicial largo</h2>

<p><strong>Intento A</strong>: textarea con texto inicial largo, rows, cols y wrap="virtual".</p>

<textarea name="campoA" rows="5" cols="80" wrap="virtual" class="textarea textarea-grande">Texto de prueba inicial para ver si el campo se renderiza como multilínea correctamente en mPDF. Esta línea debería poder editarse dentro del PDF y debería comportarse como un campo de varias líneas, no como una sola línea.</textarea>

<h2>2. Textarea con un espacio inicial</h2>

<p><strong>Intento B</strong>: textarea aparentemente vacío, pero con un espacio dentro.</p>

<textarea name="campoB" rows="5" cols="80" wrap="virtual" class="textarea textarea-grande"> </textarea>

<h2>3. Textarea con salto de línea inicial</h2>

<p><strong>Intento C</strong>: textarea con saltos de línea dentro.</p>

<textarea name="campoC" rows="5" cols="80" wrap="virtual" class="textarea textarea-grande">
Línea 1 de prueba.
Línea 2 de prueba.
Línea 3 de prueba.
</textarea>

<h2>4. Textarea dentro de tabla</h2>

<table>
    <tr>
        <td style="width:30%;">
            <strong>Áreas y/o puestos</strong>
        </td>
        <td style="width:70%;">
            <textarea name="areas_puestos_valoracion" rows="5" cols="70" wrap="virtual" class="textarea textarea-media">Texto inicial de prueba para áreas y puestos.</textarea>
        </td>
    </tr>
    <tr>
        <td>
            <strong>Competencias transversales</strong>
        </td>
        <td>
            <textarea name="valoracion_competencias_transversales" rows="5" cols="70" wrap="virtual" class="textarea textarea-media">Texto inicial de prueba para competencias transversales.</textarea>
        </td>
    </tr>
    <tr>
        <td>
            <strong>Observaciones del tutor</strong>
        </td>
        <td>
            <textarea name="observaciones_tutor_empresa" rows="5" cols="70" wrap="virtual" class="textarea textarea-media">Texto inicial de prueba para observaciones del tutor.</textarea>
        </td>
    </tr>
</table>

<h2>5. Motivos de no superación</h2>

<textarea name="motivos_no_superacion" rows="5" cols="80" wrap="virtual" class="textarea textarea-grande">Texto inicial de prueba para motivos de no superación.</textarea>

<h2>6. Checkboxes grandes de control</h2>

<table>
    <tr>
        <th>Módulo</th>
        <th>Resultado de aprendizaje</th>
        <th>Superado</th>
        <th>No superado</th>
    </tr>
    <tr>
        <td class="center">0224</td>
        <td>Instala sistemas operativos en red describiendo sus características.</td>
        <td class="center">
            <input type="checkbox" name="ra_1_superado" value="ON" class="checkbox-grande">
        </td>
        <td class="center">
            <input type="checkbox" name="ra_1_no_superado" value="ON" class="checkbox-grande">
        </td>
    </tr>
</table>

</form>

</body>
</html>
HTML;

$mpdf->WriteHTML($html);

while (ob_get_level() > 0) {
    ob_end_clean();
}

$mpdf->Output('test_pdf_multilinea.pdf', Destination::INLINE);