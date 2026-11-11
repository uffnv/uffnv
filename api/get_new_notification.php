<?php
// api/get_new_notification.php
require_once __DIR__ . '/../includes/functions.php';
initAppSession();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized', 'notifications' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $settingStmt = $pdo->prepare("SELECT push_enabled FROM user_settings WHERE user_id = ? LIMIT 1");
    $settingStmt->execute([(int)$_SESSION['user_id']]);
    $setting = $settingStmt->fetch();

    if ($setting && !(int)$setting['push_enabled']) {
        echo json_encode(['notifications' => [], 'notification' => null, 'push_enabled' => false], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT id, message, link, created_at
        FROM notifications
        WHERE user_id = ? AND is_read = 0 AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $stmt->execute([(int)$_SESSION['user_id']]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formatted = [];
    foreach ($rows as $row) {
        $formatted[] = [
            'id'        => (int)$row['id'],
            'title'     => 'Новое уведомление',
            'message'   => $row['message'],
            'link'      => safeUrl($row['link'], '/pages/notifications.php'),
            'timestamp' => $row['created_at'],
        ];
    }

    echo json_encode([
        'notifications' => $formatted,
        'notification'  => $formatted[0] ?? null,
        'push_enabled'  => true,
    ], JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    error_log("get_new_notification error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error', 'notifications' => []], JSON_UNESCAPED_UNICODE);
}
