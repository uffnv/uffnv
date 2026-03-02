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

// Валидация на пустоту
if (empty($email) || empty($password)) {
    $_SESSION['error'] = "Введите email и пароль";
    $_SESSION['old'] = ['email' => $email];
    header("Location: /pages/login.php");
    exit;
}

try {
    // Поиск пользователя и получение флага data_consent
    $stmt = $pdo->prepare("SELECT id, `role`, password_hash, data_consent FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Проверка пароля
    if ($user && password_verify($password, $user['password_hash'])) {
        
        unset($_SESSION['error'], $_SESSION['old']);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['data_consent'] = $user['data_consent']; // Передаем флаг в сессию

        $checkRole = strtolower(trim($user['role']));

        // Редирект в зависимости от роли
        if ($checkRole === 'admin' || $checkRole === 'super_admin') {
            header("Location: /admin/index.php");
        } else {
            header("Location: /pages/profile.php");
        }
        exit;

    } else {
        $_SESSION['error'] = "Неверный email или пароль";
        $_SESSION['old'] = ['email' => $email];
        header("Location: /pages/login.php");
        exit;
    }

} catch (PDOException $e) {
    $_SESSION['error'] = "Ошибка сервера. Попробуйте позже.";
    header("Location: /pages/login.php");
    exit;
}
