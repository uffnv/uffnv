<?php
// actions/add_reply.php
session_start();
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    header("Location: /pages/forum.php");
    exit;
}

$userId = $_SESSION['user_id'];
$topicId = (int)$_POST['topic_id'];
$content = trim($_POST['content']);

// Валидация
if (empty($content) || $topicId <= 0) {
    header("Location: /pages/topic.php?id=$topicId");
    exit;
}

// Проверка бана
$role = strtolower(trim($_SESSION['role'] ?? 'user'));
if ($role === 'banned') {
    die("Вы забанены.");
}

try {
    // 1. Сохраняем пост
    $stmt = $pdo->prepare("INSERT INTO posts (content, user_id, topic_id, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$content, $userId, $topicId]);
    $postId = $pdo->lastInsertId(); // ID нового поста (для ссылки-якоря)

    // --- УВЕДОМЛЕНИЯ ---
    
    // А. Уведомление автору темы (если это не он сам отвечает)
    $stmt = $pdo->prepare("SELECT user_id, title FROM topics WHERE id = ?");
    $stmt->execute([$topicId]);
    $topic = $stmt->fetch();

    if ($topic && $topic['user_id'] != $userId) {
        $msg = "Новый ответ в вашей теме: " . mb_substr($topic['title'], 0, 20) . "...";
        $link = "/pages/topic.php?id=$topicId#post-$postId";
        
        $pdo->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'reply', ?, ?)")
            ->execute([$topic['user_id'], $msg, $link]);
    }

    // Б. Уведомление упомянутым пользователям (@username)
    // Ищем все вхождения @word
    preg_match_all('/@(\w+)/u', $content, $matches);
    
    if (!empty($matches[1])) {
        // Убираем дубликаты (если упомянул одного дважды)
        $mentionedNames = array_unique($matches[1]);
        
        foreach ($mentionedNames as $name) {
            // Ищем юзера по нику
            $uStmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $uStmt->execute([$name]);
            $targetUser = $uStmt->fetch();

            // Если юзер существует и это не мы сами (самому себе слать странно)
            if ($targetUser && $targetUser['id'] != $userId) {
                // Чтобы не спамить автору темы дважды (если мы ответили ему и упомянули его), можно проверить
                if ($topic && $targetUser['id'] == $topic['user_id']) {
                    continue; // Он уже получил уведомление выше (пункт А)
                }

                $msg = "Вас упомянули в теме: " . mb_substr($topic['title'], 0, 20) . "...";
                $link = "/pages/topic.php?id=$topicId#post-$postId";
                
                $pdo->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'mention', ?, ?)")
                    ->execute([$targetUser['id'], $msg, $link]);
            }
        }
    }

    // Редирект обратно к посту
    header("Location: /pages/topic.php?id=$topicId#post-$postId");
    exit;

} catch (Exception $e) {
    die("Ошибка: " . $e->getMessage());
}
