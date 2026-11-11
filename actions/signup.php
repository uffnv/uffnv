<?php
// actions/signup.php
require_once __DIR__ . '/../includes/functions.php';
initAppSession();
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /pages/register.php");
    exit;
}

requireCsrfOrRedirect('/pages/register.php');

$username = trim($_POST['username'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm  = $_POST['password_confirm'] ?? '';
$consent  = isset($_POST['data_consent']) ? 1 : 0;

$errors = [];

if (mb_strlen($username, 'UTF-8') < 3 || mb_strlen($username, 'UTF-8') > 30) {
    $errors['username'] = "Никнейм должен быть от 3 до 30 символов";
} elseif (!preg_match('/^[a-zA-Zа-яА-ЯёЁ0-9_.-]+$/u', $username)) {
    $errors['username'] = "Никнейм может содержать только буквы, цифры, _, - и .";
}

$emailCheck = validateEmailStrict($email);
if (!$emailCheck['valid']) {
    $errors['email'] = $emailCheck['error'];
} else {
    $email = $emailCheck['value'];
}
if (mb_strlen($password, 'UTF-8') < 6 || mb_strlen($password, 'UTF-8') > 64) {
    $errors['password'] = "Пароль должен быть от 6 до 64 символов";
} elseif (!preg_match('/^[\x21-\x7E]+$/', $password) || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
    $errors['password'] = "Пароль: латинские буквы и минимум 1 цифра, без пробелов и кириллицы";
}
if ($password !== $confirm) {
    $errors['password_confirm'] = "Пароли не совпадают";
}
if ($consent !== 1) {
    $errors['data_consent'] = "Необходимо согласие на обработку персональных данных (152-ФЗ)";
}

if (empty($errors)) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1");
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

    $sql = "INSERT INTO users (username, email, password_hash, `role`, data_consent, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$username, $email, $hash, $role, $consent]);

    regenerateAppSession($pdo);
    $_SESSION['user_id'] = (int)$pdo->lastInsertId();
    $_SESSION['username'] = $username;
    $_SESSION['role'] = $role;
    $_SESSION['data_consent'] = $consent;

    header("Location: /pages/profile.php");
    exit;
} catch (\PDOException $e) {
    error_log("Signup error: " . $e->getMessage());
    $_SESSION['errors']['general'] = "Ошибка базы данных. Попробуйте позже.";
    $_SESSION['old'] = ['username' => $username, 'email' => $email];
    header("Location: /pages/register.php");
    exit;
}
