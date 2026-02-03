<?php
// actions/create_category.php
session_start();
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    header("Location: /pages/signin.php");
    exit;
}

$title = trim($_POST['title']);
$desc = trim($_POST['description']);
$userId = $_SESSION['user_id'];

if (empty($title)) {
    die("Ошибка: Название раздела обязательно.");
}

try {
    // 1. Проверяем роль текущего пользователя
    $stmtUser = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmtUser->execute([$userId]);
    $user = $stmtUser->fetch();

    // Если Админ/Супер-Админ -> Одобряем сразу (1), иначе -> На модерацию (0)
    $isApproved = ($user['role'] === 'admin' || $user['role'] === 'super_admin') ? 1 : 0;

    // 2. Вставляем категорию
    $sql = "INSERT INTO categories (title, description, is_approved, user_id) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$title, $desc, $isApproved, $userId]);

    // 3. Редирект
    if ($isApproved) {
        // Если админ создал - сразу на форум, смотреть результат
        header("Location: /pages/forum.php");
    } else {
        // Если юзер - в профиль с уведомлением
        header("Location: /pages/profile.php?msg=category_pending");
    }
    exit;

} catch (PDOException $e) {
    die("Ошибка БД: " . $e->getMessage());
}
