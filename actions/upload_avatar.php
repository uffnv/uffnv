<?php
// actions/upload_avatar.php
require_once __DIR__ . '/../includes/functions.php';
initAppSession();
require_once __DIR__ . '/../config/db.php';

checkAuth('/pages/login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['avatar'])) {
    header("Location: /pages/profile.php");
    exit;
}

requireCsrfOrRedirect('/pages/profile.php');

$userId = (int)$_SESSION['user_id'];
$uploadRes = uploadSafeImage($_FILES['avatar'], 'uploads/avatars', 'user_' . $userId . '_', 2 * 1024 * 1024);

if (!$uploadRes['ok']) {
    redirectWithFlash('/pages/profile.php', $uploadRes['error'], 'danger');
}

try {
    $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $currentUser = $stmt->fetch();

    $updateStmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
    $updateStmt->execute([$uploadRes['path'], $userId]);

    if ($currentUser && !empty($currentUser['avatar'])) {
        safeUnlinkUpload($currentUser['avatar']);
    }

    redirectWithFlash('/pages/profile.php', 'Аватар успешно обновлен!', 'success');
} catch (\PDOException $e) {
    safeUnlinkUpload($uploadRes['path']);
    error_log("Upload avatar error: " . $e->getMessage());
    redirectWithFlash('/pages/profile.php', 'Ошибка сохранения аватара.', 'danger');
}
