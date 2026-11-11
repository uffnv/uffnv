<?php
// actions/create_category.php
require_once __DIR__ . '/../includes/functions.php';
initAppSession();
require_once __DIR__ . '/../config/db.php';

checkAuth('/pages/login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /pages/topics_list.php");
    exit;
}

requireCsrfOrRedirect('/pages/topics_list.php');

$userId = (int)$_SESSION['user_id'];
$ban = getActiveForumBan($pdo, $userId, null);
if ($ban) {
    redirectWithFlash('/pages/topics_list.php', 'У вас ограничен доступ к созданию категорий на форуме.', 'danger');
}

$title = trim($_POST['title'] ?? '');
$desc  = trim($_POST['description'] ?? '');

if ($title === '') {
    redirectWithFlash('/pages/topics_list.php', 'Название раздела обязательно.', 'danger');
}

try {
    $stmtUser = $pdo->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
    $stmtUser->execute([$userId]);
    $user = $stmtUser->fetch();

    $isApproved = ($user && in_array($user['role'], ['admin', 'super_admin'], true)) ? 1 : 0;

    $sql = "INSERT INTO categories (title, description, is_approved, user_id) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$title, $desc, $isApproved, $userId]);

    if ($isApproved) {
        redirectWithFlash('/pages/topics_list.php', 'Категория создана!', 'success');
    }
    header("Location: /pages/profile.php?msg=category_pending");
    exit;
} catch (\PDOException $e) {
    error_log("Create category error: " . $e->getMessage());
    redirectWithFlash('/pages/topics_list.php', 'Ошибка создания категории.', 'danger');
}
