<?php
// actions/create_topic.php
session_start();
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    header("Location: /pages/login.php");
    exit;
}

$title = trim($_POST['title']);
$content = trim($_POST['content']);
$categoryId = (int)$_POST['category_id'];
$type = isset($_POST['type']) ? $_POST['type'] : 'standard';

if (empty($title) || empty($content) || empty($categoryId)) {
    die("Ошибка: Заполните все поля.");
}

try {
    // 1. Проверяем роль
    $stmtUser = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmtUser->execute([$_SESSION['user_id']]);
    $user = $stmtUser->fetch();
    
    // Админ одобряет сразу
    $isApproved = ($user['role'] === 'admin' || $user['role'] === 'super_admin') ? 1 : 0;

    // 2. Вставляем тему
    // Убедитесь, что колонка 'content' теперь существует!
    $sql = "INSERT INTO topics (user_id, category_id, title, content, type, is_approved, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_SESSION['user_id'], $categoryId, $title, $content, $type, $isApproved]);

    // 3. Редирект
    if ($isApproved) {
        $newId = $pdo->lastInsertId();
        header("Location: /pages/topic.php?id=$newId");
    } else {
        header("Location: /pages/profile.php?msg=topic_pending");
    }
    exit;

} catch (PDOException $e) {
    die("Ошибка БД: " . $e->getMessage());
}
