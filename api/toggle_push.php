<?php
// api/toggle_push.php
require_once __DIR__ . '/../includes/functions.php';
initAppSession();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

requireCsrfOrRedirect('/pages/profile.php', true);

$enabled = !empty($_POST['enabled']) ? 1 : 0;

try {
    $stmt = $pdo->prepare("
        INSERT INTO user_settings (user_id, push_enabled)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE push_enabled = ?
    ");
    $stmt->execute([(int)$_SESSION['user_id'], $enabled, $enabled]);

    echo json_encode(['success' => true, 'push_enabled' => (bool)$enabled], JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    error_log("toggle_push error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error'], JSON_UNESCAPED_UNICODE);
}
