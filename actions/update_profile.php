<?php
// actions/update_profile.php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /pages/signin.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получаем и чистим данные
    $fullName = trim($_POST['full_name'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $city     = trim($_POST['city'] ?? '');
    $address  = trim($_POST['address'] ?? '');

    try {
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, city = ?, address = ? WHERE id = ?");
        $stmt->execute([$fullName, $phone, $city, $address, $_SESSION['user_id']]);
        
        $_SESSION['flash'] = "Данные профиля обновлены!";
        $_SESSION['flash_type'] = "success";
    } catch (PDOException $e) {
        $_SESSION['flash'] = "Ошибка обновления: " . $e->getMessage();
        $_SESSION['flash_type'] = "danger";
    }

    header("Location: /pages/profile.php");
    exit;
}
