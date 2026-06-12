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

$matches = get_matches();
if (is_qf_started($matches)) {
    echo json_encode(['ok' => false, 'error' => 'Los cuartos de final ya han comenzado. El plazo está cerrado.']); exit;
}

$pos = [];
foreach ([1, 2, 3, 4] as $p) {
    $val = trim($_POST["pos{$p}"] ?? '');
    if (!$val) { echo json_encode(['ok' => false, 'error' => "Falta la posición {$p}"]); exit; }
    $pos["pos{$p}"] = $val;
}
// Check no duplicates
if (count(array_unique(array_values($pos))) < 4) {
    echo json_encode(['ok' => false, 'error' => 'No puedes elegir el mismo equipo en varias posiciones']); exit;
}

save_top4($user['id'], $user['name'], $pos);
echo json_encode(['ok' => true]);
