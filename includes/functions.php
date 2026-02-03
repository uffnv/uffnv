<?php
// includes/functions.php

/**
 * Очистка строковых данных от лишних пробелов и тегов
 */
function clean($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Проверка, авторизован ли пользователь
 */
function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php?page=login");
        exit;
    }
}

/**
 * Проверка, является ли пользователь админом
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Хелпер для редиректа с флеш-сообщением
 */
function redirectWithFlash($url, $message, $type = 'success') {
    $_SESSION['flash'] = $message;
    $_SESSION['flash_type'] = $type;
    header("Location: $url");
    exit;
}

// includes/functions.php

/**
 * Проверяет и возвращает правильный URL аватара.
 * Если аватара нет или файл не найден - возвращает false.
 */
function getAvatarUrl($dbPath) {
    if (empty($dbPath)) {
        return false;
    }

    // Абсолютный путь к файлу на сервере (для проверки file_exists)
    // __DIR__ указывает на includes, поэтому выходим на уровень вверх
    $physicalPath = __DIR__ . '/../' . $dbPath;

    if (file_exists($physicalPath)) {
        // Если файл есть, возвращаем веб-путь (со слешем в начале)
        return '/' . $dbPath;
    }

    return false;
}
?>
