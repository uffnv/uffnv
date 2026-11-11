<?php
// actions/approve_topic.php
require_once __DIR__ . '/../includes/functions.php';
initAppSession();
require_once __DIR__ . '/../config/db.php';

checkAuth('/pages/login.php');

if (!isAdmin()) {
    redirectWithFlash('/', 'Нет прав для модерации.', 'danger');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /pages/profile.php");
    exit;
}

requireCsrfOrRedirect('/pages/profile.php');

$topicId = (int)($_POST['topic_id'] ?? 0);
$action  = $_POST['action'] ?? '';

if ($topicId > 0) {
    if ($action === 'approve') {
        $stmt = $pdo->prepare("SELECT user_id, title FROM topics WHERE id = ? LIMIT 1");
        $stmt->execute([$topicId]);
        $topic = $stmt->fetch();

        if ($topic) {
            $pdo->prepare("UPDATE topics SET is_approved = 1 WHERE id = ?")->execute([$topicId]);
            $msg = "Ваша тема '" . mb_substr($topic['title'], 0, 50, 'UTF-8') . "' была одобрена и опубликована!";
            $link = "/pages/topic.php?id=" . $topicId;
            $pdo->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'topic_approved', ?, ?)")
                ->execute([$topic['user_id'], $msg, $link]);
        }
    } elseif ($action === 'delete') {
        $pdo->prepare("DELETE FROM topics WHERE id = ?")->execute([$topicId]);
    }
}

header("Location: /pages/profile.php");
exit;
