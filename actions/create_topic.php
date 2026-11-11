<?php
// actions/create_topic.php
require_once __DIR__ . '/../includes/functions.php';
initAppSession();
require_once __DIR__ . '/../config/db.php';

checkAuth('/pages/login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /pages/create_topic.php");
    exit;
}

requireCsrfOrRedirect('/pages/create_topic.php');

$userId = (int)$_SESSION['user_id'];
$ban = getActiveForumBan($pdo, $userId, null);
if ($ban) {
    $until = !empty($ban['expires_at']) ? ' до ' . date('d.m.Y H:i', strtotime($ban['expires_at'])) : ' бессрочно';
    $reason = !empty($ban['reason']) ? ' (' . $ban['reason'] . ')' : '';
    redirectWithFlash('/pages/topics_list.php', 'Вам ограничен доступ к созданию тем на форуме' . $until . $reason . '.', 'danger');
}

$title      = trim($_POST['title'] ?? '');
$content    = trim($_POST['content'] ?? '');
$categoryId = (int)($_POST['category_id'] ?? 0);
$type       = in_array($_POST['type'] ?? 'standard', ['standard', 'general'], true) ? $_POST['type'] : 'standard';

if ($title === '' || $content === '' || $categoryId <= 0) {
    redirectWithFlash('/pages/create_topic.php', 'Заполните заголовок, категорию и текст темы.', 'danger');
}

try {
    $catCheck = $pdo->prepare("SELECT id FROM categories WHERE id = ? AND is_approved = 1 LIMIT 1");
    $catCheck->execute([$categoryId]);
    if (!$catCheck->fetch()) {
        redirectWithFlash('/pages/create_topic.php', 'Выбранная категория недоступна или не найдена.', 'danger');
    }

    $stmtUser = $pdo->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
    $stmtUser->execute([$userId]);
    $user = $stmtUser->fetch();

    $isApproved = ($user && in_array($user['role'], ['admin', 'super_admin'], true)) ? 1 : 0;

    $sql = "INSERT INTO topics (user_id, category_id, title, content, type, is_approved, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId, $categoryId, $title, $content, $type, $isApproved]);
    $newId = (int)$pdo->lastInsertId();

    if ($isApproved) {
        redirectWithFlash('/pages/topic.php?id=' . $newId, 'Тема опубликована!', 'success');
    }

    // Уведомляем админов о новой теме на модерации
    $admins = $pdo->query("SELECT id FROM users WHERE role IN ('admin', 'super_admin')")->fetchAll();
    if ($admins) {
        $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'topic_mod', ?, '/pages/profile.php')");
        $shortTitle = mb_substr($title, 0, 40, 'UTF-8');
        foreach ($admins as $adm) {
            $notifStmt->execute([$adm['id'], "Новая тема на модерацию: " . $shortTitle]);
        }
    }

    header("Location: /pages/profile.php?msg=topic_pending");
    exit;
} catch (\PDOException $e) {
    error_log("Create topic error: " . $e->getMessage());
    redirectWithFlash('/pages/create_topic.php', 'Ошибка сохранения темы. Попробуйте позже.', 'danger');
}
