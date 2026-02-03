<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    header("Location: /pages/create_topic.php");
    exit;
}

$userId = $_SESSION['user_id'];
$role = strtolower(trim($_SESSION['role'] ?? 'user'));

if ($role === 'banned') die("Бан.");

$title = trim($_POST['title']);
$catId = (int)$_POST['category_id'];
$content = trim($_POST['content']);

if (empty($title) || empty($content)) {
    $_SESSION['error'] = "Заполните поля";
    header("Location: /pages/create_topic.php");
    exit;
}

try {
    $pdo->beginTransaction();

    // СТАТУС: Админ -> 1, Юзер -> 0
    $isApproved = ($role === 'admin' || $role === 'super_admin') ? 1 : 0;

    // 1. Создаем тему (она есть, но скрыта если 0)
    $stmt = $pdo->prepare("INSERT INTO topics (title, user_id, category_id, is_approved, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$title, $userId, $catId, $isApproved]);
    $topicId = $pdo->lastInsertId();

    // 2. Создаем пост
    $stmt = $pdo->prepare("INSERT INTO posts (content, user_id, topic_id, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$content, $userId, $topicId]);

    // 3. Уведомление Админам (если скрыта)
    if ($isApproved === 0) {
        $admins = $pdo->query("SELECT id FROM users WHERE role IN ('admin', 'super_admin')")->fetchAll(PDO::FETCH_COLUMN);
        $notifSql = "INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'system', ?, ?)";
        $msg = "Новая тема на проверку: " . mb_substr($title, 0, 20) . "...";
        $link = "/pages/profile.php"; // Админ идет в профиль одобрять
        
        $nStmt = $pdo->prepare($notifSql);
        foreach ($admins as $aid) {
            if ($aid != $userId) $nStmt->execute([$aid, $msg, $link]);
        }
    }

    $pdo->commit();

    if ($isApproved) {
        header("Location: /pages/topic.php?id=$topicId");
    } else {
        // Юзеру говорим: жди одобрения
        header("Location: /pages/profile.php?msg=topic_pending");
    }
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    die($e->getMessage());
}
