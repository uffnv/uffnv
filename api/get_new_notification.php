<?php
require_once __DIR__ . '/../config/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    // Получаем настройку push для этого юзера (создадим таблицу ниже)
    $settingStmt = $pdo->prepare("SELECT push_enabled FROM user_settings WHERE user_id = ?");
    $settingStmt->execute([$_SESSION['user_id']]);
    $setting = $settingStmt->fetch();
    
    // Если юзер отключил уведомления - возвращаем пустой массив
    if ($setting && !$setting['push_enabled']) {
        echo json_encode(['notifications' => [], 'push_enabled' => false]);
        exit;
    }
    
    // Получаем последнее непрочитанное уведомление (за последние 5 минут)
    $stmt = $pdo->prepare("
        SELECT id, message, link, created_at 
        FROM notifications 
        WHERE user_id = ? AND is_read = 0 AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $notif = $stmt->fetch();
    
    if ($notif) {
        echo json_encode([
            'notification' => [
                'id' => $notif['id'],
                'title' => 'Новое уведомление',
                'message' => $notif['message'],
                'link' => $notif['link'],
                'timestamp' => $notif['created_at']
            ],
            'push_enabled' => true
        ]);
    } else {
        echo json_encode(['notifications' => [], 'push_enabled' => true]);
    }
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
