<?php
// actions/delete_avatar.php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /pages/signin.php");
    exit;
}

// Получаем текущий аватар
$stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if ($user && !empty($user['avatar'])) {
    $filePath = __DIR__ . '/../' . $user['avatar'];
    
    // Удаляем физически
    if (file_exists($filePath)) {
        unlink($filePath);
    }

    // Удаляем из БД
    $update = $pdo->prepare("UPDATE users SET avatar = NULL WHERE id = ?");
    $update->execute([$_SESSION['user_id']]);
    
    // Удаляем из сессии
    unset($_SESSION['avatar']);

    $_SESSION['flash'] = "Аватар удален.";
    $_SESSION['flash_type'] = "warning";
}

header("Location: /pages/profile.php");
exit;
