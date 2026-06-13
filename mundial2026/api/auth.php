<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['ok' => false, 'error' => 'Método no permitido']); exit;
}

$name = trim($_POST['name'] ?? '');
$pin  = trim($_POST['pin']  ?? '');

// Validate
if (mb_strlen($name) < 2 || mb_strlen($name) > 30) {
    echo json_encode(['ok' => false, 'error' => 'El nombre debe tener entre 2 y 30 caracteres']); exit;
}
if (!preg_match('/^\d{4}$/', $pin)) {
    echo json_encode(['ok' => false, 'error' => 'El PIN debe ser exactamente 4 dígitos']); exit;
}

start_session();

$existing = find_user($name);

if ($existing) {
    if (!password_verify($pin, $existing['pin_hash'])) {
        echo json_encode(['ok' => false, 'error' => 'PIN incorrecto para ese nombre']); exit;
    }
    $_SESSION['user'] = ['id' => $existing['id'], 'name' => $existing['name']];
    echo json_encode(['ok' => true, 'new' => false, 'name' => $existing['name']]); exit;
}

// New user — require explicit confirmation to avoid typos
if (empty($_POST['confirm_new'])) {
    echo json_encode(['ok' => false, 'is_new' => true, 'name' => $name]);
    exit;
}

$user = create_user($name, $pin);
$_SESSION['user'] = ['id' => $user['id'], 'name' => $user['name']];
echo json_encode(['ok' => true, 'new' => true, 'name' => $user['name']]);
