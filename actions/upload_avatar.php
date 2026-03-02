<?php
// actions/upload_avatar.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: /pages/login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// 1. Проверяем, передан ли файл
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $file = $_FILES['avatar'];

    // Простая валидация (тип и размер)
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        redirectWithFlash('/pages/profile.php', 'Недопустимый формат файла (только JPG, PNG, WEBP)', 'danger');
    }

    if ($file['size'] > 2 * 1024 * 1024) { // 2MB
        redirectWithFlash('/pages/profile.php', 'Файл слишком большой (макс. 2МБ)', 'danger');
    }

    // 2. Получаем текущий аватар, чтобы удалить его
    $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $currentUser = $stmt->fetch();

    if ($currentUser && !empty($currentUser['avatar'])) {
        $oldFilePath = __DIR__ . '/../' . $currentUser['avatar'];
        if (file_exists($oldFilePath)) {
            unlink($oldFilePath); // Удаляем старый файл
        }
    }

    // 3. Подготовка папки
    $uploadDir = 'uploads/avatars/';
    if (!is_dir(__DIR__ . '/../' . $uploadDir)) {
        mkdir(__DIR__ . '/../' . $uploadDir, 0777, true);
    }

    // 4. Генерируем уникальное имя (чтобы избежать проблем с кэшем браузера)
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = 'user_' . $userId . '_' . time() . '.' . $extension;
    $dbPath = $uploadDir . $fileName;
    $absolutePath = __DIR__ . '/../' . $dbPath;

    // 5. Сохраняем файл и обновляем БД
    if (move_uploaded_file($file['tmp_name'], $absolutePath)) {
        $updateStmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
        $updateStmt->execute([$dbPath, $userId]);
        
        redirectWithFlash('/pages/profile.php', 'Аватар успешно обновлен!', 'success');
    } else {
        redirectWithFlash('/pages/profile.php', 'Ошибка при сохранении файла', 'danger');
    }
} else {
    header("Location: /pages/profile.php");
    exit;
}
