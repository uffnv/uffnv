<?php
// admin/session_ping.php — Продление временной сессии администратора при активности
require_once __DIR__ . '/admin_auth.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$csrf = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!verifyCsrfToken((string)$csrf)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$_SESSION['admin_last_activity'] = time();

echo json_encode([
    'ok' => true,
    'remaining' => ADMIN_SESSION_IDLE_LIMIT,
]);
