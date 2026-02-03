<?php
// actions/signup.php
session_start();
require_once __DIR__ . '/../config/db.php';

// Если пришли не POST-ом
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /pages/register.php");
    exit;
}

// 1. Получаем и чистим данные
$username = trim($_POST['username'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm  = $_POST['password_confirm'] ?? '';

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
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
    $stmt->execute([$email, $username]);
    if ($stmt->fetch()) {
        // Чтобы не палить, что именно занято, можно написать общее, 
        // но для удобства юзера лучше уточнить.
        // Для простоты скажем так:
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
    // Хешируем пароль
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Роль по умолчанию 'user'
    $role = 'user';

    // Вставляем в БД (Используем поле password_hash!)
    $sql = "INSERT INTO users (username, email, password_hash, role, created_at) VALUES (?, ?, ?, ?, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$username, $email, $hash, $role]);

    // 5. Автоматический вход после регистрации
    $_SESSION['user_id'] = $pdo->lastInsertId();
    $_SESSION['role'] = $role;

    // Редирект в профиль
    header("Location: /pages/profile.php");
    exit;

} catch (PDOException $e) {
    // Логгируем ошибку для админа (в файл), юзеру показываем общую
    $_SESSION['errors']['auth_failed'] = "Ошибка базы данных. Попробуйте позже.";
    // $_SESSION['errors']['debug'] = $e->getMessage(); // Раскомментировать для отладки
    $_SESSION['old'] = ['username' => $username, 'email' => $email];
    header("Location: /pages/register.php");
    exit;
}
?>
