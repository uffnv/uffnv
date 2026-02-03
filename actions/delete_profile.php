<?php
// actions/delete_profile.php
session_start();
require_once __DIR__ . '/../config/db.php';

// 1. Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: /pages/signin.php");
    exit;
}

$currentUserId = $_SESSION['user_id'];
$currentUserRole = $_SESSION['role'] ?? 'user';

// Определяем, кого и как удаляем
$targetId = 0;
$isSelfDelete = false;

if (isset($_POST['action']) && $_POST['action'] === 'self_delete') {
    // Пользователь удаляет сам себя
    $targetId = $currentUserId;
    $isSelfDelete = true;
} elseif (isset($_POST['user_id'])) {
    // Админ удаляет кого-то другого
    $targetId = (int)$_POST['user_id'];
} else {
    die("Неверный запрос.");
}

// 2. Логика прав и проверок
if ($isSelfDelete) {
    // --- УДАЛЕНИЕ СЕБЯ (Нужен пароль) ---
    $password = $_POST['password'] ?? '';
    
    // Получаем хеш пароля из БД
    $stmt = $pdo->prepare("SELECT password_hash, role FROM users WHERE id = ?");
    $stmt->execute([$currentUserId]);
    $user = $stmt->fetch();

    if (!$user) {
        die("Пользователь не найден.");
    }
    
    // Защита от удаления Супер-Админа (даже самим собой - от греха подальше)
    if ($user['role'] === 'super_admin') {
        echo "<script>alert('Супер-админ не может самоуничтожиться!'); window.location.href='/pages/profile.php';</script>";
        exit;
    }

    // Проверяем пароль
    if (!password_verify($password, $user['password_hash'])) {
        echo "<script>alert('Неверный пароль!'); window.location.href='/pages/profile.php';</script>";
        exit;
    }

} else {
    // --- УДАЛЕНИЕ АДМИНОМ (Пароль не нужен, нужны права) ---
    if ($currentUserRole !== 'admin' && $currentUserRole !== 'super_admin') {
        die("У вас нет прав администратора.");
    }

    // Проверяем цель
    $stmt = $pdo->prepare("SELECT role, avatar FROM users WHERE id = ?");
    $stmt->execute([$targetId]);
    $targetUser = $stmt->fetch();

    if (!$targetUser) {
        die("Цель не найдена.");
    }

    // Нельзя удалить Супер-Админа
    if ($targetUser['role'] === 'super_admin') {
        echo "<script>alert('Нельзя удалить Супер-Админа!'); window.location.href='/admin/users.php';</script>";
        exit;
    }
    
    // Админ не может удалить самого себя через эту кнопку (для этого есть self_delete)
    if ($targetId === $currentUserId) {
         echo "<script>alert('Используйте удаление профиля в личном кабинете.'); window.location.href='/admin/users.php';</script>";
         exit;
    }
}

// 3. ПРОЦЕСС УДАЛЕНИЯ
try {
    // Получаем данные для удаления аватарки (если мы еще не получили их выше)
    if (!isset($targetUser)) {
        $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
        $stmt->execute([$targetId]);
        $targetUser = $stmt->fetch();
    }

    // Удаляем файл аватарки
    if (!empty($targetUser['avatar']) && file_exists(__DIR__ . '/../' . $targetUser['avatar'])) {
        unlink(__DIR__ . '/../' . $targetUser['avatar']);
    }

    // Удаляем из БД
    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$targetId]);

    // 4. Финиш
    if ($isSelfDelete) {
        // Если удалил себя -> Выход
        session_destroy();
        header("Location: /");
    } else {
        // Если удалил админ -> Обратно в админку
        header("Location: /admin/users.php");
    }
    exit;

} catch (PDOException $e) {
    die("Ошибка БД: " . $e->getMessage());
}
