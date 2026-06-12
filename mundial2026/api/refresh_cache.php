<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/helpers.php';

header('Content-Type: application/json');

start_session();
$user = current_user();
// Only admin can force refresh (or anyone, it's just a cache clear)
$cache = DATA_DIR . '/matches.json';
if (file_exists($cache)) unlink($cache);

$matches = get_matches();
echo json_encode(['ok' => true, 'count' => count($matches)]);
