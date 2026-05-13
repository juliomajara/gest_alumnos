<?php
declare(strict_types=1);

use Mpdf\Mpdf;

require_once __DIR__ . '/../vendor/autoload.php';

function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
function image_file_to_data_uri_fsp(string $path): string {
  $mime = function_exists('mime_content_type') ? (string)mime_content_type($path) : 'image/png';
  $data = file_get_contents($path);
  if ($data === false) throw new RuntimeException('No se pudo leer imagen local.');
  return 'data:' . $mime . ';base64,' . base64_encode($data);
}
function embed_local_images(string $html, string $docsDir): string {
  return preg_replace_callback('~(<img\b[^>]*\bsrc=["\'])([^"\']+)(["\'][^>]*>)~iu', static function(array $m) use ($docsDir): string {
    $src = trim($m[2]); if ($src === '' || str_starts_with($src,'data:')) return $m[0];
    $path = $docsDir . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $src), DIRECTORY_SEPARATOR);
    if (!is_file($path) || !is_readable($path)) return $m[0];
    return $m[1] . image_file_to_data_uri_fsp($path) . $m[3];
  }, $html) ?? $html;
}

function render_estado_checkbox(bool $checked): string {
  $mark = $checked ? 'X' : '';
  return '<table role="presentation" cellspacing="0" cellpadding="0" style="margin:0 auto;border-collapse:collapse;">'
    . '<tr><td style="width:10px;height:10px;border:1px solid #000;text-align:center;vertical-align:middle;font-size:8px;line-height:8px;font-weight:bold;">' . $mark . '</td></tr>'
    . '</table>';
}
function normalize_estado_seguimiento(string $estado): string {
  $estado = trim($estado);
  $estado = mb_strtolower($estado, 'UTF-8');
  $estado = str_replace(['-', ' '], '_', $estado);
  $estado = preg_replace('/_+/', '_', $estado) ?? $estado;
  return $estado;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Método no permitido.'); }

$projectRoot = realpath(__DIR__ . '/..');
$docsDir = $projectRoot . DIRECTORY_SEPARATOR . 'docs';
$templatePath = $docsDir . DIRECTORY_SEPARATOR . 'practicas_ficha_seguimiento_periodico.html';
$mpdfTempDir = $docsDir . DIRECTORY_SEPARATOR . 'tmp_mpdf';
if (!is_file($templatePath) || !is_readable($templatePath)) { http_response_code(400); exit('No se encontró la plantilla HTML.'); }
if (!is_dir($mpdfTempDir) && !mkdir($mpdfTempDir, 0775, true) && !is_dir($mpdfTempDir)) { http_response_code(500); exit('No se pudo crear tmp_mpdf.'); }

$filas = $_POST['filas'] ?? [];
if (!is_array($filas)) { http_response_code(400); exit('Formato de filas no válido.'); }

$html = (string) file_get_contents($templatePath);
$html = embed_local_images($html, $docsDir);
$html .= '<style>.activity-col{width:46%;}.module-col{width:8%;}.ra-col{width:4%;}</style>';

$repl = [
  '{{CURSO_ACADEMICO}}'=>e((string)($_POST['curso_academico'] ?? '')),
  '{{NUM_CONVENIO}}'=>e((string)($_POST['num_convenio'] ?? '')),
  '{{NUM_ANEXO_RELACION}}'=>e((string)($_POST['num_anexo_relacion'] ?? '')),
  '{{ALUMNO_APELLIDOS}}'=>e((string)($_POST['alumno_apellidos'] ?? '')),
  '{{ALUMNO_NOMBRE}}'=>e((string)($_POST['alumno_nombre'] ?? '')),
  '{{ALUMNO_EMAIL}}'=>e((string)($_POST['alumno_email'] ?? '')),
  '{{CENTRO_TRABAJO_DENOMINACION}}'=>e((string)($_POST['centro_trabajo_denominacion'] ?? '')),
  '{{TUTOR_EMPRESA_APELLIDOS}}'=>e((string)($_POST['tutor_empresa_apellidos'] ?? '')),
  '{{TUTOR_EMPRESA_NOMBRE}}'=>e((string)($_POST['tutor_empresa_nombre'] ?? '')),
  '{{TUTOR_EMPRESA_EMAIL}}'=>e((string)($_POST['tutor_empresa_email'] ?? '')),
  '{{FECHA_INICIO}}'=>e((string)($_POST['fecha_inicio'] ?? '')),
  '{{FECHA_FIN}}'=>e((string)($_POST['fecha_fin'] ?? '')),
  '{{FECHA_FIRMA}}'=>e((string)($_POST['fecha_firma'] ?? '')),
];
$dynamicRowsHtml = '';
foreach ($filas as $row) {
  if (!is_array($row)) continue;
  $estado = normalize_estado_seguimiento((string)($row['estado'] ?? 'en_proceso'));
  if (!in_array($estado, ['no_superado', 'en_proceso', 'superado'], true)) $estado = 'en_proceso';
  $dynamicRowsHtml .= '<tr class="row-fill">'
    . '<td><span class="value-text">' . e((string)($row['actividad'] ?? '')) . '</span></td>'
    . '<td class="center"><span class="value-text">' . e((string)($row['codigo_modulo'] ?? '')) . '</span></td>'
    . '<td class="center"><span class="value-text">' . e((string)($row['ra'] ?? '')) . '</span></td>'
    . '<td class="center">' . render_estado_checkbox($estado==='no_superado') . '</td>'
    . '<td class="center">' . render_estado_checkbox($estado==='en_proceso') . '</td>'
    . '<td class="center">' . render_estado_checkbox($estado==='superado') . '</td>'
    . '<td><span class="value-text">' . e((string)($row['observaciones'] ?? '')) . '</span></td>'
    . '</tr>';
}
if ($dynamicRowsHtml === '') {
  $dynamicRowsHtml = '<tr class="row-fill"><td></td><td class="center"></td><td class="center"></td><td class="center">' . render_estado_checkbox(false) . '</td><td class="center">' . render_estado_checkbox(true) . '</td><td class="center">' . render_estado_checkbox(false) . '</td><td></td></tr>';
}
$html = preg_replace('/<tr class="row-fill">.*?\{\{FILA_8_OBSERVACIONES\}\}<\/span><\/td>\s*<\/tr>/su', $dynamicRowsHtml, $html, 1) ?? $html;
$html = strtr($html, $repl);
$html = preg_replace('/\{\{[^}]+\}\}/', '', $html) ?? $html;

$tmpPdf = $docsDir . DIRECTORY_SEPARATOR . 'tmp_ficha_' . uniqid('', true) . '.pdf';
try {
  $mpdf = new Mpdf([
    'tempDir' => $mpdfTempDir,
    'mode' => 'utf-8',
    'format' => 'A4-L',
    'default_font' => 'dejavusans',
    'allow_output_buffering' => true,
    'margin_left' => 15,
    'margin_right' => 15,
    'margin_top' => 8,
    'margin_bottom' => 6,
    'margin_header' => 0,
    'margin_footer' => 0,
  ]);
  $mpdf->shrink_tables_to_fit = 1;
  $mpdf->SetBasePath($docsDir . DIRECTORY_SEPARATOR);
  $mpdf->WriteHTML($html);
  $mpdf->Output($tmpPdf, \Mpdf\Output\Destination::FILE);
  if (!is_file($tmpPdf) || filesize($tmpPdf) < 1024) throw new RuntimeException('PDF generado vacío o demasiado pequeño.');
  $head = file_get_contents($tmpPdf, false, null, 0, 5);
  if ($head !== '%PDF-') throw new RuntimeException('PDF corrupto: cabecera inválida.');

  $base = trim((string)($_POST['alumno_apellidos'] ?? '')) . '_' . trim((string)($_POST['alumno_nombre'] ?? '')) . '_' . date('Ymd');
  $base = preg_replace('/\s+/u', '_', $base) ?? '';
  $base = preg_replace('/[\/\\:*?"<>|\x00-\x1F]/u', '', $base) ?? 'Ficha_seguimiento';
  $filename = 'Ficha_seguimiento_' . trim($base, '._-') . '.pdf';

  while (ob_get_level() > 0) { ob_end_clean(); }
  header('Content-Type: application/pdf');
  header('Content-Length: ' . (string) filesize($tmpPdf));
  header("Content-Disposition: attachment; filename*=UTF-8''" . rawurlencode($filename));
  readfile($tmpPdf);
} catch (Throwable $e) {
  http_response_code(500);
  echo 'Error al generar el PDF: ' . e($e->getMessage());
} finally {
  if (is_file($tmpPdf)) @unlink($tmpPdf);
}
