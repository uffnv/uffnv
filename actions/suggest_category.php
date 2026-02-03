<?php
// actions/suggest_category.php
session_start();
require_once __DIR__ . '/../config/db.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash'] = "Войди в аккаунт, чтобы предлагать идеи!";
    $_SESSION['flash_type'] = "danger";
    header("Location: /pages/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($title)) {
        $_SESSION['flash'] = "Название района не может быть пустым.";
        $_SESSION['flash_type'] = "danger";
        header("Location: /pages/home.php");
        exit;
    }

    // Генерация slug
    // Если transliterator недоступен, используем простую замену
    if (function_exists('transliterator_transliterate')) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $title))));
    } else {
        // Запасной вариант, если расширение intl выключено на сервере
        $slug = 'cat-' . time() . '-' . rand(100,999); 
    }

    if (empty($slug)) {
        $slug = 'cat-' . time();
    }

    try {
        // Проверка на дубликат
        $stmtCheck = $pdo->prepare("SELECT id FROM categories WHERE slug = ?");
        $stmtCheck->execute([$slug]);
        if ($stmtCheck->rowCount() > 0) {
            $slug .= '-' . time();
        }

        // ВАЖНО: Убедитесь, что колонка status существует!
        $stmt = $pdo->prepare("INSERT INTO categories (title, slug, description, status) VALUES (?, ?, ?, 'pending')");
        $stmt->execute([$title, $slug, $description]);

        $_SESSION['flash'] = "Твоя идея отправлена на модерацию!";
        $_SESSION['flash_type'] = "success";

    } catch (PDOException $e) {
        $_SESSION['flash'] = "Ошибка: " . $e->getMessage();
        $_SESSION['flash_type'] = "danger";
    }

    header("Location: /pages/home.php");
    exit;
} else {
    header("Location: /pages/home.php");
    exit;
}
