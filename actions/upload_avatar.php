<?php
// actions/upload_avatar.php
session_start();
require_once __DIR__ . '/../config/db.php';

// Если не авторизован - выгоняем
if (!isset($_SESSION['user_id'])) {
    header("Location: /pages/signin.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $file = $_FILES['avatar'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 2 * 1024 * 1024; // 2 MB
    $uploadDir = __DIR__ . '/../uploads/avatars/';
    
    // Проверка ошибок загрузки
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['flash'] = "Ошибка при загрузке файла.";
        $_SESSION['flash_type'] = "danger";
        header("Location: /pages/profile.php");
        exit;
    }

    // Проверка типа файла (MIME)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowedTypes)) {
        $_SESSION['flash'] = "Можно загружать только картинки (JPG, PNG, GIF, WEBP).";
        $_SESSION['flash_type'] = "danger";
        header("Location: /pages/profile.php");
        exit;
    }

    // Проверка размера
    if ($file['size'] > $maxSize) {
        $_SESSION['flash'] = "Файл слишком большой (макс. 2МБ).";
        $_SESSION['flash_type'] = "danger";
        header("Location: /pages/profile.php");
        exit;
    }

    // Генерируем уникальное имя
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newName = 'user_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
    $destPath = $uploadDir . $newName;

    // Перемещаем файл
    if (move_uploaded_file($file['tmp_name'], $destPath)) {
        // Успех! Обновляем путь в БД
        // Сохраняем путь относительно корня сайта: uploads/avatars/filename.jpg
        $dbPath = 'uploads/avatars/' . $newName;
        
        $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
        $stmt->execute([$dbPath, $_SESSION['user_id']]);
        
        // Удаляем старый аватар (если он был не дефолтным), чтобы не засорять сервер (опционально)
        // Но пока пропустим для простоты.

        $_SESSION['flash'] = "Аватарка обновлена!";
        $_SESSION['flash_type'] = "success";
    } else {
        $_SESSION['flash'] = "Не удалось сохранить файл.";
        $_SESSION['flash_type'] = "danger";
    }

    header("Location: /pages/profile.php");
    exit;
}
// actions/upload_avatar.php
// ... после успешного move_uploaded_file и выполнения SQL UPDATE ...

if (move_uploaded_file($file['tmp_name'], $destPath)) {
    $dbPath = 'uploads/avatars/' . $newName;
    
    $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
    $stmt->execute([$dbPath, $_SESSION['user_id']]);
    
    // ДОБАВЛЯЕМ ЭТУ СТРОКУ: Обновляем аватар в текущей сессии
    $_SESSION['avatar'] = $dbPath; 

    $_SESSION['flash'] = "Аватарка обновлена!";
    $_SESSION['flash_type'] = "success";
}
// ...
