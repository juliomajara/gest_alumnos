<?php
$tmpDir = __DIR__ . '/docs/.mpdf_tmp';

echo '<h3>Diagnóstico</h3>';
echo '<p><strong>Ruta completa:</strong> ' . $tmpDir . '</p>';
echo '<p><strong>Existe:</strong> ' . (is_dir($tmpDir) ? 'Sí' : 'No') . '</p>';
echo '<p><strong>Escribible:</strong> ' . (is_writable($tmpDir) ? 'Sí' : 'No') . '</p>';
echo '<p><strong>Usuario Apache:</strong> ' . get_current_user() . '</p>';
echo '<p><strong>Proceso ID:</strong> ' . getmypid() . '</p>';

// Intentar crear la carpeta si no existe
if (!is_dir($tmpDir)) {
    $created = mkdir($tmpDir, 0755, true);
    echo '<p><strong>Intento de crear carpeta:</strong> ' . ($created ? 'OK' : 'FALLÓ') . '</p>';
    echo '<p><strong>Error:</strong> ' . error_get_last()['message'] ?? 'ninguno' . '</p>';
}

// Intentar escribir un archivo de prueba
$testFile = $tmpDir . '/test_write.txt';
$written = file_put_contents($testFile, 'test');
echo '<p><strong>Escritura de prueba:</strong> ' . ($written !== false ? 'OK' : 'FALLÓ') . '</p>';
if ($written !== false) {
    unlink($testFile);
}