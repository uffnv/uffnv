<?php
// actions/delete_profile.php
require_once __DIR__ . '/../includes/functions.php';
initAppSession();
require_once __DIR__ . '/../config/db.php';

checkAuth('/pages/login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /pages/profile.php");
    exit;
}

$currentUserId = (int)$_SESSION['user_id'];
$currentUserRole = $_SESSION['role'] ?? 'user';

$targetId = 0;
$isSelfDelete = false;

if (isset($_POST['action']) && $_POST['action'] === 'self_delete') {
    $targetId = $currentUserId;
    $isSelfDelete = true;
    requireCsrfOrRedirect('/pages/profile.php');
} elseif (isset($_POST['user_id'])) {
    $targetId = (int)$_POST['user_id'];
    requireCsrfOrRedirect('/admin/users.php');
} else {
    redirectWithFlash('/pages/profile.php', 'Неверный запрос.', 'danger');
}

if ($isSelfDelete) {
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare("SELECT password_hash, role, avatar FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$currentUserId]);
    $targetUser = $stmt->fetch();

    if (!$targetUser) {
        redirectWithFlash('/pages/login.php', 'Пользователь не найден.', 'danger');
    }
    if ($targetUser['role'] === 'super_admin') {
        redirectWithFlash('/pages/profile.php', 'Супер-админ не может удалить свой аккаунт таким способом.', 'danger');
    }
    if (!password_verify($password, $targetUser['password_hash'])) {
        redirectWithFlash('/pages/profile.php', 'Неверный пароль! Аккаунт не удален.', 'danger');
    }
} else {
    if (!isAdmin()) {
        redirectWithFlash('/', 'У вас нет прав администратора.', 'danger');
    }
    if ($targetId === $currentUserId) {
        redirectWithFlash('/admin/users.php', 'Используйте удаление профиля в личном кабинете.', 'warning');
    }

    $stmt = $pdo->prepare("SELECT role, avatar FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$targetId]);
    $targetUser = $stmt->fetch();

    if (!$targetUser) {
        redirectWithFlash('/admin/users.php', 'Пользователь не найден.', 'danger');
    }
    if (!canManageTargetUser($currentUserRole, $targetUser['role'])) {
        redirectWithFlash('/admin/users.php', 'Недостаточно прав для удаления этого пользователя.', 'danger');
    }
}

try {
    if (!empty($targetUser['avatar'])) {
        safeUnlinkUpload($targetUser['avatar']);
    }

    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$targetId]);

    if ($isSelfDelete) {
        $_SESSION = [];
        session_destroy();
        header("Location: /");
        exit;
    }

    redirectWithFlash('/admin/users.php', 'Пользователь успешно удален.', 'success');
} catch (\PDOException $e) {
    error_log("Delete profile error: " . $e->getMessage());
    $back = $isSelfDelete ? '/pages/profile.php' : '/admin/users.php';
    redirectWithFlash($back, 'Ошибка базы данных при удалении профиля.', 'danger');
}
