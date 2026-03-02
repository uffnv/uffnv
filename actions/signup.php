<?php
// actions/signup.php
session_start();
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /pages/register.php");
    exit;
}

$username = trim($_POST['username'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm  = $_POST['password_confirm'] ?? '';

$errors = [];

// Проверка длины строк (требует расширения mbstring на сервере)
if (function_exists('mb_strlen')) {
    if (mb_strlen($username) < 3) {
        $errors['username'] = "Имя должно быть не менее 3 символов";
    }
    if (mb_strlen($password) < 6) {
        $errors['password'] = "Пароль должен быть не менее 6 символов";
    }
} else {
    if (strlen($username) < 3) {
        $errors['username'] = "Имя должно быть не менее 3 символов";
    }
    if (strlen($password) < 6) {
        $errors['password'] = "Пароль должен быть не менее 6 символов";
    }
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = "Некорректный Email";
}
if ($password !== $confirm) {
    $errors['password_confirm'] = "Пароли не совпадают";
}

if (empty($errors)) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
    $stmt->execute([$email, $username]);
    if ($stmt->fetch()) {
        $errors['username'] = "Пользователь с таким именем или email уже существует";
    }
}

if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    $_SESSION['old'] = ['username' => $username, 'email' => $email];
    header("Location: /pages/register.php");
    exit;
}

try {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $role = 'user';

    // Экранирование колонки `role`
    $sql = "INSERT INTO users (username, email, password_hash, `role`, created_at) VALUES (?, ?, ?, ?, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$username, $email, $hash, $role]);

    $_SESSION['user_id'] = $pdo->lastInsertId();
    $_SESSION['role'] = $role;

    header("Location: /pages/profile.php");
    exit;

} catch (PDOException $e) {
    $_SESSION['errors']['auth_failed'] = "Ошибка базы данных. Попробуйте позже.";
    $_SESSION['old'] = ['username' => $username, 'email' => $email];
    header("Location: /pages/register.php");
    exit;
}
