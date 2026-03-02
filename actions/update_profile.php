<?php
// actions/update_profile.php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /pages/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получаем и чистим данные
    $fullName = trim($_POST['full_name'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $city     = trim($_POST['city'] ?? '');
    $address  = trim($_POST['address'] ?? '');
    
    // Получаем статус согласия (если чекбокс не нажат, в POST его не будет)
    $consent = isset($_POST['data_consent']) ? 1 : 0;

    try {
        // Обновляем данные пользователя, включая флаг согласия
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, city = ?, address = ?, data_consent = ? WHERE id = ?");
        $stmt->execute([$fullName, $phone, $city, $address, $consent, $_SESSION['user_id']]);
        
        // Обновляем статус в сессии, чтобы Neural_Hub сразу отреагировал на изменения
        $_SESSION['data_consent'] = $consent;
        
        $_SESSION['flash'] = "Данные профиля обновлены!";
        $_SESSION['flash_type'] = "success";
    } catch (PDOException $e) {
        $_SESSION['flash'] = "Ошибка обновления: " . $e->getMessage();
        $_SESSION['flash_type'] = "danger";
    }

    header("Location: /pages/profile.php");
    exit;
}
