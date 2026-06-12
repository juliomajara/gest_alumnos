<?php
/**
 * Setup script – visita esta URL UNA VEZ después de subir los ficheros.
 * Se puede borrar después si quieres (no es obligatorio).
 */

$checks = [];

// 1. PHP version
$php_ok = version_compare(PHP_VERSION, '7.4', '>=');
$checks[] = ['PHP ' . PHP_VERSION, $php_ok, $php_ok ? 'OK' : 'Necesitas PHP 7.4+'];

// 2. Data directory
$data_dir = __DIR__ . '/data';
if (!is_dir($data_dir)) {
    @mkdir($data_dir, 0755, true);
}
$dir_exists  = is_dir($data_dir);
$dir_writable = $dir_exists && is_writable($data_dir);
$checks[] = ['Directorio data/', $dir_writable, $dir_writable ? 'OK — escritura permitida' : 'ERROR — no se puede escribir. En tu hosting, haz chmod 755 (o 777) en la carpeta data/'];

// 3. cURL
$curl_ok = function_exists('curl_init');
$checks[] = ['cURL', $curl_ok, $curl_ok ? 'Disponible' : 'No disponible — se usará file_get_contents'];

// 4. allow_url_fopen (fallback)
$fopen_ok = ini_get('allow_url_fopen');
$checks[] = ['allow_url_fopen', $curl_ok || $fopen_ok, ($curl_ok || $fopen_ok) ? 'OK (peticiones externas posibles)' : 'ERROR — ni cURL ni allow_url_fopen disponibles. Contacta con tu hosting.'];

// 5. JSON extension
$json_ok = function_exists('json_encode');
$checks[] = ['JSON extension', $json_ok, $json_ok ? 'OK' : 'No disponible'];

// 6. Sessions
$sess_ok = function_exists('session_start');
$checks[] = ['Sessions', $sess_ok, $sess_ok ? 'OK' : 'No disponible'];

// 7. Try to fetch matches
$fetch_ok = false;
$fetch_msg = '';
if ($curl_ok || $fopen_ok) {
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/helpers.php';
    $json = fetch_url(MATCHES_API_URL);
    $fetch_ok = $json !== null && json_decode($json, true) !== null;
    $fetch_msg = $fetch_ok ? 'Conexión correcta con el feed de partidos' : 'No se pudo conectar con el feed. Puede ser temporal.';
}
$checks[] = ['Feed de partidos (openfootball)', $fetch_ok, $fetch_msg];

// 8. Write test
$test_file = $data_dir . '/.write_test';
$write_ok  = @file_put_contents($test_file, 'ok') !== false;
if ($write_ok) @unlink($test_file);
$checks[] = ['Escritura de fichero', $write_ok, $write_ok ? 'OK' : 'ERROR — no se puede escribir en data/'];

$all_ok = $php_ok && $dir_writable && ($curl_ok || $fopen_ok) && $json_ok && $sess_ok && $write_ok;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Setup – Porras Mundial 2026</title>
<style>
  body { font-family: system-ui, sans-serif; background: #0a1628; color: #e0eaff; margin: 0; padding: 24px; }
  h1 { color: #ffd700; font-size: 1.3rem; }
  .card { background: #132035; border: 1px solid rgba(255,255,255,.1); border-radius: 12px; padding: 20px; max-width: 560px; margin: 0 auto; }
  .check { display: flex; align-items: flex-start; gap: 10px; padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,.07); font-size: .9rem; }
  .check:last-child { border-bottom: none; }
  .icon { font-size: 1.1rem; line-height: 1.3; flex-shrink: 0; }
  .label { font-weight: 600; min-width: 200px; }
  .msg { color: #7a93bc; font-size: .82rem; margin-top: 2px; }
  .ok   { color: #00e676; }
  .fail { color: #ff4f4f; }
  .warn { color: #ff9800; }
  .cta { margin-top: 20px; text-align: center; }
  .btn { display: inline-block; padding: 12px 28px; background: #ffd700; color: #000; font-weight: 800; border-radius: 10px; text-decoration: none; font-size: .95rem; }
</style>
</head>
<body>
<div class="card">
  <h1>⚙️ Setup – Porras Mundial 2026</h1>

  <?php foreach ($checks as [$label, $ok, $msg]): ?>
    <div class="check">
      <span class="icon <?= $ok ? 'ok' : 'fail' ?>"><?= $ok ? '✅' : '❌' ?></span>
      <div>
        <div class="label"><?= htmlspecialchars($label) ?></div>
        <div class="msg <?= $ok ? '' : 'fail' ?>"><?= htmlspecialchars($msg) ?></div>
      </div>
    </div>
  <?php endforeach; ?>

  <div class="cta">
    <?php if ($all_ok): ?>
      <p class="ok" style="margin-bottom:12px">✅ Todo correcto. La app está lista.</p>
      <a href="index.php" class="btn">⚽ Ir a la app</a>
    <?php else: ?>
      <p class="fail" style="margin-bottom:12px">Hay errores. Revisa los puntos marcados en rojo y recarga esta página.</p>
      <a href="setup.php" class="btn" style="background:#1a2d4a;color:#e0eaff">🔄 Recargar</a>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
