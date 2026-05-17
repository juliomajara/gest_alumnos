<?php
declare(strict_types=1);

// Compatibilidad: mantenemos esta ruta antigua redirigiendo al generador específico de prácticas.
$query = $_SERVER['QUERY_STRING'] ?? '';
$target = 'generar_calendario_practica.php' . ($query !== '' ? '?' . $query : '');
header('Location: ' . $target, true, 302);
exit;
