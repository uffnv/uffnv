<?php
// actions/signin.php
session_start();
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /pages/login.php");
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

$errors = [];
if (empty($email)) {
    $errors['email'] = 'Введите email';
}
if (empty($password)) {
    $errors['password'] = 'Введите пароль';
}

if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    $_SESSION['old'] = ['email' => $email];
    header("Location: /pages/login.php");
    exit;
}

try {
    // Использование обратных кавычек для `role`
    $stmt = $pdo->prepare("SELECT id, `role`, password_hash FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        unset($_SESSION['errors']);
        unset($_SESSION['old']);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role']; 

        $checkRole = strtolower(trim($user['role']));

        if ($checkRole === 'admin' || $checkRole === 'super_admin') {
            header("Location: /admin/index.php");
        } else {
            header("Location: /pages/profile.php");
        }
        exit;

    } else {
        $_SESSION['errors']['auth_failed'] = "Неверный email или пароль";
        $_SESSION['old'] = ['email' => $email];
        header("Location: /pages/login.php");
        exit;
    }

} catch (PDOException $e) {
    $_SESSION['errors']['auth_failed'] = "Ошибка сервера. Попробуйте позже.";
    header("Location: /pages/login.php");
    exit;
}

