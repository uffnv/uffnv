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

$loginCheck = validateLoginIdentifier($_POST['email'] ?? '');
$password   = (string)($_POST['password'] ?? '');

if (!$loginCheck['valid']) {
    $_SESSION['error'] = $loginCheck['error'];
    $_SESSION['old'] = ['email' => $loginCheck['value']];
    header("Location: /pages/login.php");
    exit;
}

if (strlen($password) < 1 || strlen($password) > 128) {
    $_SESSION['error'] = "Введите корректный пароль";
    $_SESSION['old'] = ['email' => $loginCheck['value']];
    header("Location: /pages/login.php");
    exit;
}

$loginVal = $loginCheck['value'];

try {
    if (strpos($loginVal, '@') !== false) {
        $stmt = $pdo->prepare("SELECT id, username, `role`, password_hash, data_consent FROM users WHERE email = ? LIMIT 1");
    } else {
        $stmt = $pdo->prepare("SELECT id, username, `role`, password_hash, data_consent FROM users WHERE username = ? LIMIT 1");
    }
    $stmt->execute([$loginVal]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        unset($_SESSION['error'], $_SESSION['old']);
        regenerateAppSession($pdo);

        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['data_consent'] = (int)$user['data_consent'];

        // Если старый глобальный бан на форуме истёк — getActiveForumBan автоматически обновит роль
        getActiveForumBan($pdo, (int)$user['id']);

        if (isAdmin()) {
            initAdminSessionMetadata();
            header("Location: /admin/index.php");
        } else {
            header("Location: /pages/profile.php");
        }
        exit;
    }

    $_SESSION['error'] = "Неверный логин/email или пароль";
    $_SESSION['old'] = ['email' => $loginVal];
    header("Location: /pages/login.php");
    exit;
} catch (\PDOException $e) {
    error_log("Signin error: " . $e->getMessage());
    $_SESSION['error'] = "Ошибка сервера. Попробуйте позже.";
    header("Location: /pages/login.php");
    exit;
}
