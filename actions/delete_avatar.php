<?php
// actions/delete_avatar.php
require_once __DIR__ . '/../includes/functions.php';
initAppSession();
require_once __DIR__ . '/../config/db.php';

checkAuth('/pages/login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /pages/profile.php");
    exit;
}

requireCsrfOrRedirect('/pages/profile.php');

try {
    $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if ($user && !empty($user['avatar'])) {
        safeUnlinkUpload($user['avatar']);

        $update = $pdo->prepare("UPDATE users SET avatar = NULL WHERE id = ?");
        $update->execute([$_SESSION['user_id']]);
        unset($_SESSION['avatar']);

        redirectWithFlash('/pages/profile.php', 'Аватар удален.', 'warning');
    }
} catch (\PDOException $e) {
    error_log("Delete avatar error: " . $e->getMessage());
}

header("Location: /pages/profile.php");
exit;
