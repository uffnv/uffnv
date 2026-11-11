<?php
// actions/read_all.php
require_once __DIR__ . '/../includes/functions.php';
initAppSession();
require_once __DIR__ . '/../config/db.php';

checkAuth('/pages/login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /pages/notifications.php");
    exit;
}

requireCsrfOrRedirect('/pages/notifications.php');

try {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    $stmt->execute([(int)$_SESSION['user_id']]);
    redirectWithFlash('/pages/notifications.php', 'Все уведомления отмечены как прочитанные.', 'success');
} catch (\PDOException $e) {
    error_log("Read all notifications error: " . $e->getMessage());
    redirectWithFlash('/pages/notifications.php', 'Не удалось обновить уведомления.', 'danger');
}
