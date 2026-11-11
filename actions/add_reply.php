<?php
// actions/add_reply.php
require_once __DIR__ . '/../includes/functions.php';
initAppSession();
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['user_id'])) {
    header("Location: /pages/topics_list.php");
    exit;
}

$userId  = (int)$_SESSION['user_id'];
$topicId = (int)($_POST['topic_id'] ?? 0);
$content = trim($_POST['content'] ?? '');
$backUrl = $topicId > 0 ? '/pages/topic.php?id=' . $topicId : '/pages/topics_list.php';

requireCsrfOrRedirect($backUrl);

if ($content === '' || $topicId <= 0) {
    redirectWithFlash($backUrl, 'Введите текст сообщения.', 'warning');
}

// Проверка бана: учитывается и общий бан форума, и локальный бан в конкретной теме (topic_id)
$ban = getActiveForumBan($pdo, $userId, $topicId);
if ($ban) {
    $scope = !empty($ban['topic_id']) ? 'в этой теме' : 'на форуме';
    $until = !empty($ban['expires_at']) ? ' до ' . date('d.m.Y H:i', strtotime($ban['expires_at'])) : ' бессрочно';
    $reason = !empty($ban['reason']) ? ' (' . $ban['reason'] . ')' : '';
    redirectWithFlash($backUrl, 'Вам ограничен доступ к ответам ' . $scope . $until . $reason . '.', 'danger');
}

try {
    $stmt = $pdo->prepare("SELECT id, user_id, title, is_approved FROM topics WHERE id = ? LIMIT 1");
    $stmt->execute([$topicId]);
    $topic = $stmt->fetch();

    if (!$topic || (!$topic['is_approved'] && !isAdmin())) {
        redirectWithFlash('/pages/topics_list.php', 'Тема не найдена или ещё не одобрена.', 'danger');
    }

    $stmt = $pdo->prepare("INSERT INTO posts (content, user_id, topic_id, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$content, $userId, $topicId]);
    $postId = (int)$pdo->lastInsertId();

    $topicShort = mb_substr($topic['title'], 0, 20, 'UTF-8');
    $link = "/pages/topic.php?id=$topicId#post-$postId";

    // Уведомление автору темы
    if ((int)$topic['user_id'] !== $userId) {
        $msg = "Новый ответ в вашей теме: " . $topicShort . "...";
        $pdo->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'reply', ?, ?)")
            ->execute([$topic['user_id'], $msg, $link]);
    }

    // Уведомление упомянутым пользователям (@username)
    preg_match_all('/@([a-zA-Zа-яА-ЯёЁ0-9_.-]+)/u', $content, $matches);
    if (!empty($matches[1])) {
        $mentionedNames = array_unique($matches[1]);
        $uStmt = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
        $nStmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'mention', ?, ?)");

        foreach ($mentionedNames as $name) {
            $uStmt->execute([$name]);
            $targetUser = $uStmt->fetch();
            if ($targetUser && (int)$targetUser['id'] !== $userId && (int)$targetUser['id'] !== (int)$topic['user_id']) {
                $msg = "Вас упомянули в теме: " . $topicShort . "...";
                $nStmt->execute([$targetUser['id'], $msg, $link]);
            }
        }
    }

    header("Location: " . $link);
    exit;
} catch (\Throwable $e) {
    error_log("Add reply error: " . $e->getMessage());
    redirectWithFlash($backUrl, 'Ошибка сохранения ответа. Попробуйте позже.', 'danger');
}
