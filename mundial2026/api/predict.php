<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['ok' => false, 'error' => 'Método no permitido']); exit;
}

start_session();
$user = current_user();
if (!$user) { echo json_encode(['ok' => false, 'error' => 'Sesión expirada']); exit; }

$match_id = trim($_POST['match_id'] ?? '');
$g1 = $_POST['g1'] ?? '';
$g2 = $_POST['g2'] ?? '';

if (!$match_id) { echo json_encode(['ok' => false, 'error' => 'Partido no válido']); exit; }
if (!is_numeric($g1) || !is_numeric($g2) || (int)$g1 < 0 || (int)$g2 < 0 || (int)$g1 > 30 || (int)$g2 > 30) {
    echo json_encode(['ok' => false, 'error' => 'Resultado no válido']); exit;
}

// Find match and check lock
$matches = get_matches();
$match = null;
foreach ($matches as $m) {
    if ($m['id'] === $match_id) { $match = $m; break; }
}
if (!$match) { echo json_encode(['ok' => false, 'error' => 'Partido no encontrado']); exit; }
if (is_locked($match)) { echo json_encode(['ok' => false, 'error' => 'El partido ya ha comenzado, no se pueden cambiar predicciones']); exit; }

save_prediction($user['id'], $user['name'], $match_id, (int)$g1, (int)$g2);
echo json_encode(['ok' => true]);
