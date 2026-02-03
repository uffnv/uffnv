
<?php
// actions/approve_topic.php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /pages/signin.php");
    exit;
}

// Проверка прав
$currentUserRole = $_SESSION['role'] ?? 'user';
if ($currentUserRole !== 'admin' && $currentUserRole !== 'super_admin') {
    die("Нет прав.");
}

$topicId = (int)$_POST['topic_id'];
$action = $_POST['action'] ?? '';

if ($topicId > 0) {
    if ($action === 'approve') {
        // 1. Одобряем тему
        $pdo->prepare("UPDATE topics SET is_approved = 1 WHERE id = ?")->execute([$topicId]);

        // 2. Узнаем автора темы
        $stmt = $pdo->prepare("SELECT user_id, title FROM topics WHERE id = ?");
        $stmt->execute([$topicId]);
        $topic = $stmt->fetch();

        if ($topic) {
            // 3. Шлем уведомление автору
            $msg = "Ваша тема '{$topic['title']}' была одобрена и опубликована!";
            $link = "/pages/topic.php?id=" . $topicId;
            
            $notif = $pdo->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'topic_approved', ?, ?)");
            $notif->execute([$topic['user_id'], $msg, $link]);
        }

    } elseif ($action === 'delete') {
        // Удаляем тему
        $pdo->prepare("DELETE FROM topics WHERE id = ?")->execute([$topicId]);
        
        // (Опционально) Можно уведомить автора, что тема отклонена, если найти его ID перед удалением
    }
}

// Возвращаем обратно в профиль
header("Location: /pages/profile.php");
exit;
