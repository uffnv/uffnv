<?php
// actions/read_notification.php
require_once __DIR__ . '/../includes/functions.php';
initAppSession();
require_once __DIR__ . '/../config/db.php';

if (empty($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: /");
    exit;
}

$notifId = (int)$_GET['id'];
$userId  = (int)$_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT link FROM notifications WHERE id = ? AND user_id = ? LIMIT 1");
    $stmt->execute([$notifId, $userId]);
    $notif = $stmt->fetch();

    if ($notif) {
        $update = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $update->execute([$notifId, $userId]);

        if (!empty($notif['link'])) {
            header("Location: " . safeRedirectUrl($notif['link'], '/pages/notifications.php'));
            exit;
        }
    }
} catch (\Throwable $e) {
    error_log("Read notification error: " . $e->getMessage());
}

header("Location: /pages/notifications.php");
exit;
