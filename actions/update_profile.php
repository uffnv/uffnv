<?php
// actions/update_profile.php
require_once __DIR__ . '/../includes/functions.php';
initAppSession();
require_once __DIR__ . '/../config/db.php';

checkAuth('/pages/login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /pages/profile.php");
    exit;
}

requireCsrfOrRedirect('/pages/profile.php');

$fullName        = trim($_POST['full_name'] ?? '');
$phone           = trim($_POST['phone'] ?? '');
$city            = trim($_POST['city'] ?? '');
$address         = trim($_POST['address'] ?? '');
$consent         = isset($_POST['data_consent']) ? 1 : 0;
$passwordConfirm = $_POST['password_confirm'] ?? '';

if (mb_strlen($fullName, 'UTF-8') > 120 || mb_strlen($city, 'UTF-8') > 100 || mb_strlen($address, 'UTF-8') > 255) {
    redirectWithFlash('/pages/profile.php', 'Превышена допустимая длина ФИО, города или адреса.', 'danger');
}

if ($phone !== '') {
    $phoneCheck = normalizeAndValidatePhone($phone);
    if (!$phoneCheck['valid']) {
        redirectWithFlash('/pages/profile.php', $phoneCheck['error'], 'danger');
    }
    $phone = $phoneCheck['value'];
}

if ($passwordConfirm === '') {
    redirectWithFlash('/pages/profile.php', 'Введите текущий пароль для подтверждения изменений.', 'danger');
}

try {
    $uStmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ? LIMIT 1");
    $uStmt->execute([$_SESSION['user_id']]);
    $row = $uStmt->fetch();

    if (!$row || !password_verify($passwordConfirm, $row['password_hash'])) {
        redirectWithFlash('/pages/profile.php', 'Неверный текущий пароль! Изменения не сохранены.', 'danger');
    }

    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, city = ?, address = ?, data_consent = ? WHERE id = ?");
    $stmt->execute([$fullName, $phone, $city, $address, $consent, $_SESSION['user_id']]);

    $_SESSION['data_consent'] = $consent;
    redirectWithFlash('/pages/profile.php', 'Данные профиля обновлены!', 'success');
} catch (\PDOException $e) {
    error_log("Update profile error: " . $e->getMessage());
    redirectWithFlash('/pages/profile.php', 'Ошибка обновления профиля. Попробуйте позже.', 'danger');
}
