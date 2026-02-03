<?php
// actions/read_notification.php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: /");
    exit;
}

$notifId = (int)$_GET['id'];
$userId = $_SESSION['user_id'];

try {
    // 1. Получаем ссылку и проверяем владельца
    $stmt = $pdo->prepare("SELECT link FROM notifications WHERE id = ? AND user_id = ?");
    $stmt->execute([$notifId, $userId]);
    $notif = $stmt->fetch();

    if ($notif) {
        // 2. Помечаем как прочитанное
        $update = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
        $update->execute([$notifId]);

        // 3. Переходим по ссылке
        if (!empty($notif['link'])) {
            header("Location: " . $notif['link']);
            exit;
        }
    }
} catch (Exception $e) {
    // Тихо падаем
}

// Фолбек
header("Location: /pages/home.php");
