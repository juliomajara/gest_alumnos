<?php
declare(strict_types=1);

use Mpdf\Mpdf;

require_once __DIR__ . '/../vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Método no permitido.'); }
if (!class_exists('ZipArchive')) { http_response_code(500); exit('ZipArchive no disponible en este servidor.'); }

$input = (string)file_get_contents('php://input');
$fichas = json_decode($input, true);
if (!is_array($fichas) || empty($fichas)) { http_response_code(400); exit('Datos inválidos.'); }

$projectRoot = (string)realpath(__DIR__ . '/..');
$docsDir     = $projectRoot . DIRECTORY_SEPARATOR . 'docs';
$templatePath = $docsDir . DIRECTORY_SEPARATOR . 'practicas_ficha_seguimiento_periodico.html';
$mpdfTempDir  = $docsDir . DIRECTORY_SEPARATOR . 'tmp_mpdf';

if (!is_file($templatePath) || !is_readable($templatePath)) { http_response_code(400); exit('No se encontró la plantilla HTML.'); }
if (!is_dir($mpdfTempDir) && !mkdir($mpdfTempDir, 0775, true) && !is_dir($mpdfTempDir)) { http_response_code(500); exit('No se pudo crear tmp_mpdf.'); }

function eg_zip(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
function norm_estado_z(string $estado): string {
  $estado = trim(mb_strtolower($estado, 'UTF-8'));
  $estado = str_replace(['á','é','í','ó','ú','Á','É','Í','Ó','Ú'],['a','e','i','o','u','a','e','i','o','u'], $estado);
  $estado = str_replace(['-',' '], '_', $estado);
  return preg_replace('/_+/', '_', $estado) ?? $estado;
}

// Embed local images once, shared across all fichas
$baseHtml = (string)file_get_contents($templatePath);
$baseHtml = (string)(preg_replace_callback('~(<img\b[^>]*\bsrc=["\'])([^"\']+)(["\'][^>]*>)~iu', static function(array $m) use ($docsDir): string {
  $src = trim($m[2]); if ($src === '' || str_starts_with($src, 'data:')) return $m[0];
  $path = $docsDir . DIRECTORY_SEPARATOR . ltrim(str_replace(['/','\\'],[DIRECTORY_SEPARATOR,DIRECTORY_SEPARATOR],$src), DIRECTORY_SEPARATOR);
  if (!is_file($path) || !is_readable($path)) return $m[0];
  $mime = function_exists('mime_content_type') ? (string)mime_content_type($path) : 'image/png';
  $data = file_get_contents($path); if ($data === false) return $m[0];
  return $m[1] . 'data:' . $mime . ';base64,' . base64_encode($data) . $m[3];
}, $baseHtml) ?? $baseHtml);
$baseHtml .= '<style>.activity-col{width:50.5%;}.module-col{width:8%;}.ra-col{width:4%;}.status-col{width:5.5%;}</style>';

$zipPath = $docsDir . DIRECTORY_SEPARATOR . 'tmp_fichas_zip_' . uniqid('', true) . '.zip';
$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) { http_response_code(500); exit('No se pudo crear el ZIP.'); }

$tmpPdfs = []; $usedNames = []; $genOk = 0;

foreach ($fichas as $idx => $ficha) {
  if (!is_array($ficha)) continue;
  $tmpPdf = $docsDir . DIRECTORY_SEPARATOR . 'tmp_zip_' . uniqid('', true) . '.pdf';
  try {
    $filas = isset($ficha['filas']) && is_array($ficha['filas']) ? $ficha['filas'] : [];
    $repl = [
      '{{CURSO_ACADEMICO}}'          => eg_zip((string)($ficha['curso_academico']          ?? '')),
      '{{NUM_CONVENIO}}'             => eg_zip((string)($ficha['num_convenio']             ?? '')),
      '{{NUM_ANEXO_RELACION}}'       => eg_zip((string)($ficha['num_anexo_relacion']       ?? '')),
      '{{ALUMNO_APELLIDOS}}'         => eg_zip((string)($ficha['alumno_apellidos']         ?? '')),
      '{{ALUMNO_NOMBRE}}'            => eg_zip((string)($ficha['alumno_nombre']            ?? '')),
      '{{ALUMNO_EMAIL}}'             => eg_zip((string)($ficha['alumno_email']             ?? '')),
      '{{CENTRO_TRABAJO_DENOMINACION}}'=>eg_zip((string)($ficha['centro_trabajo_denominacion']??'')),
      '{{TUTOR_EMPRESA_APELLIDOS}}'  => eg_zip((string)($ficha['tutor_empresa_apellidos']  ?? '')),
      '{{TUTOR_EMPRESA_NOMBRE}}'     => eg_zip((string)($ficha['tutor_empresa_nombre']     ?? '')),
      '{{TUTOR_EMPRESA_EMAIL}}'      => eg_zip((string)($ficha['tutor_empresa_email']      ?? '')),
      '{{FECHA_INICIO}}'             => eg_zip((string)($ficha['fecha_inicio']             ?? '')),
      '{{FECHA_FIN}}'                => eg_zip((string)($ficha['fecha_fin']                ?? '')),
      '{{FECHA_FIRMA}}'              => eg_zip((string)($ficha['fecha_fin']                ?? '')),
    ];
    $rowsHtml = '';
    foreach ($filas as $row) {
      if (!is_array($row)) continue;
      $act   = trim((string)($row['actividad'] ?? ''));
      $est   = norm_estado_z((string)($row['estado'] ?? ''));
      if ($act !== '') { $est = 'superado'; }
      elseif (!in_array($est, ['no_superado','en_proceso','superado'], true)) { $est = ''; }
      $x = static fn(bool $c): string => $c ? '<span style="font-weight:700;">X</span>' : '';
      $rowsHtml .= '<tr class="row-fill">'
        . '<td><span class="value-text">'    . eg_zip($act)                            . '</span></td>'
        . '<td class="center"><span class="value-text">' . eg_zip((string)($row['codigo_modulo']??'')) . '</span></td>'
        . '<td class="center"><span class="value-text">' . eg_zip((string)($row['ra']??''))            . '</span></td>'
        . '<td class="center">' . $x($est==='no_superado') . '</td>'
        . '<td class="center">' . $x($est==='en_proceso')  . '</td>'
        . '<td class="center">' . $x($est==='superado')    . '</td>'
        . '<td><span class="value-text">'   . eg_zip((string)($row['observaciones']??'')) . '</span></td>'
        . '</tr>';
    }
    if ($rowsHtml === '') {
      $rowsHtml = '<tr class="row-fill"><td></td><td class="center"></td><td class="center"></td><td class="center"></td><td class="center"></td><td class="center"></td><td></td></tr>';
    }
    $html = $baseHtml;
    $html = preg_replace('/<tr class="row-fill">.*?\{\{FILA_8_OBSERVACIONES\}\}<\/span><\/td>\s*<\/tr>/su', $rowsHtml, $html, 1) ?? $html;
    $html = strtr($html, $repl);
    $html = preg_replace('/\{\{[^}]+\}\}/', '', $html) ?? $html;

    $mpdf = new Mpdf([
      'tempDir'=>$mpdfTempDir, 'mode'=>'utf-8', 'format'=>'A4-L',
      'default_font'=>'dejavusans', 'allow_output_buffering'=>true,
      'margin_left'=>15, 'margin_right'=>15, 'margin_top'=>8,
      'margin_bottom'=>6, 'margin_header'=>0, 'margin_footer'=>0,
    ]);
    $mpdf->shrink_tables_to_fit = 1;
    $mpdf->SetBasePath($docsDir . DIRECTORY_SEPARATOR);
    $mpdf->WriteHTML($html);
    $mpdf->Output($tmpPdf, \Mpdf\Output\Destination::FILE);

    if (!is_file($tmpPdf) || filesize($tmpPdf) < 1024) throw new RuntimeException('PDF generado vacío.');

    $zipName = trim((string)($ficha['_filename'] ?? ''));
    if ($zipName === '') $zipName = 'ficha_' . ($idx + 1) . '.pdf';
    $finalName = $zipName; $cnt = 1;
    while (isset($usedNames[$finalName])) {
      $finalName = pathinfo($zipName, PATHINFO_FILENAME) . '_' . (++$cnt) . '.pdf';
    }
    $usedNames[$finalName] = true;

    $zip->addFile($tmpPdf, $finalName);
    $tmpPdfs[] = $tmpPdf;
    $genOk++;
  } catch (Throwable $e) {
    if (is_file($tmpPdf)) @unlink($tmpPdf);
  }
}

$zip->close();
// ZipArchive ha leído todos los PDF; ya se pueden eliminar
foreach ($tmpPdfs as $f) { @unlink($f); }

if ($genOk === 0) {
  @unlink($zipPath);
  http_response_code(500);
  exit('No se pudo generar ningún PDF.');
}

$zipSize = filesize($zipPath) ?: 0;
while (ob_get_level() > 0) { ob_end_clean(); }
header('Content-Type: application/zip');
header('Content-Length: ' . (string)$zipSize);
header("Content-Disposition: attachment; filename=\"fichas_seguimiento.zip\"");
readfile($zipPath);
@unlink($zipPath);
