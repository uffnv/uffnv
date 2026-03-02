<?php
// actions/signup.php
session_start();
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /pages/register.php");
    exit;
}

// 1. Получаем и чистим данные
$username = trim($_POST['username'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm  = $_POST['password_confirm'] ?? '';
$consent  = isset($_POST['data_consent']) ? 1 : 0; // Флаг согласия для Neural_Hub

// 2. Валидация
$errors = [];

if (mb_strlen($username) < 3) {
    $errors['username'] = "Имя должно быть не менее 3 символов";
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = "Некорректный Email";
}
if (mb_strlen($password) < 6) {
    $errors['password'] = "Пароль должен быть не менее 6 символов";
}
if ($password !== $confirm) {
    $errors['password_confirm'] = "Пароли не совпадают";
}

// 3. Проверка на дубликаты (Email или Username)
if (empty($errors)) {
    // Используем подготовленные выражения для защиты от SQL-инъекций
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
    $stmt->execute([$email, $username]);
    if ($stmt->fetch()) {
        $errors['username'] = "Пользователь с таким именем или email уже существует";
    }
}

// Если есть ошибки — возвращаем назад
if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    $_SESSION['old'] = ['username' => $username, 'email' => $email];
    header("Location: /pages/register.php");
    exit;
}

// 4. Регистрация
try {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $role = 'user';

    // Вставляем данные, включая флаг согласия data_consent
    $sql = "INSERT INTO users (username, email, password_hash, `role`, data_consent, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$username, $email, $hash, $role, $consent]);

    // 5. Автоматический вход
    $_SESSION['user_id'] = $pdo->lastInsertId();
    $_SESSION['role'] = $role;
    $_SESSION['data_consent'] = $consent; // Сохраняем статус для работы Neural_Hub

    header("Location: /pages/profile.php");
    exit;

} catch (PDOException $e) {
    $_SESSION['errors']['auth_failed'] = "Ошибка базы данных. Попробуйте позже.";
    $_SESSION['old'] = ['username' => $username, 'email' => $email];
    header("Location: /pages/register.php");
    exit;
}
