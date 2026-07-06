<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/puntuaciones.php';

header('Content-Type: application/json; charset=utf-8');

function responder_error(string $mensaje, int $codigo = 400): void
{
    http_response_code($codigo);
    echo json_encode(['ok' => false, 'error' => $mensaje]);
    exit;
}

$usuario = usuario_actual();
if ($usuario === null) {
    responder_error('No has iniciado sesión.', 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder_error('Método no permitido.', 405);
}

$datos = json_decode(file_get_contents('php://input'), true);
if (!is_array($datos)) {
    responder_error('Datos inválidos.');
}

$tipo = $datos['tipo'] ?? '';
$modo = $datos['modo'] ?? '';
$aciertos = filter_var($datos['aciertos'] ?? null, FILTER_VALIDATE_INT);
$total = filter_var($datos['total'] ?? null, FILTER_VALIDATE_INT);
$tiempoMs = filter_var($datos['tiempoMs'] ?? null, FILTER_VALIDATE_INT);

$totales_esperados = ['provincias' => 52, 'ccaa' => 19, 'rios' => 10];

if (!in_array($tipo, TIPOS_VALIDOS, true) || !in_array($modo, MODOS_VALIDOS, true)) {
    responder_error('Tipo o modo inválido.');
}
if ($total !== $totales_esperados[$tipo]) {
    responder_error('El total de preguntas no corresponde a este modo.');
}
if ($aciertos === false || $aciertos < 0 || $aciertos > $total) {
    responder_error('Número de aciertos inválido.');
}
if ($tiempoMs === false || $tiempoMs <= 0 || $tiempoMs > 3 * 60 * 60 * 1000) {
    responder_error('Tiempo inválido.');
}

guardar_puntuacion($usuario['id'], $tipo, $modo, $aciertos, $total, $tiempoMs);

$pct = $total > 0 ? round(($aciertos / $total) * 100, 2) : 0.0;
$posicion = posicion_en_ranking($tipo, $modo, $pct, $tiempoMs);
$totalJugadores = total_jugadores($tipo, $modo);

echo json_encode([
    'ok' => true,
    'pct' => $pct,
    'tiempoFormateado' => formatear_tiempo($tiempoMs),
    'posicion' => $posicion,
    'totalJugadores' => $totalJugadores,
]);
