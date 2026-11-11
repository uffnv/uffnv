<?php
// actions/signin.php
require_once __DIR__ . '/../includes/functions.php';
initAppSession();
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /pages/login.php");
    exit;
}

requireCsrfOrRedirect('/pages/login.php');

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    $_SESSION['error'] = "Введите email и пароль";
    $_SESSION['old'] = ['email' => $email];
    header("Location: /pages/login.php");
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, username, `role`, password_hash, data_consent FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        unset($_SESSION['error'], $_SESSION['old']);
        regenerateAppSession();

        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['data_consent'] = (int)$user['data_consent'];

        // Если старый глобальный бан на форуме истёк — getActiveForumBan автоматически обновит роль
        getActiveForumBan($pdo, (int)$user['id']);

        if (isAdmin()) {
            header("Location: /admin/index.php");
        } else {
            header("Location: /pages/profile.php");
        }
        exit;
    }

    $_SESSION['error'] = "Неверный email или пароль";
    $_SESSION['old'] = ['email' => $email];
    header("Location: /pages/login.php");
    exit;
} catch (\PDOException $e) {
    error_log("Signin error: " . $e->getMessage());
    $_SESSION['error'] = "Ошибка сервера. Попробуйте позже.";
    header("Location: /pages/login.php");
    exit;
}
