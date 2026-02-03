<?php
// actions/add_to_collection.php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /pages/signin.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['item_id'])) {
    $itemId = (int)$_POST['item_id'];
    $userId = $_SESSION['user_id'];

    // Проверяем, есть ли уже в коллекции
    $stmtCheck = $pdo->prepare("SELECT id FROM user_collections WHERE user_id = ? AND item_id = ?");
    $stmtCheck->execute([$userId, $itemId]);
    
    if ($stmtCheck->rowCount() == 0) {
        // Добавляем
        $stmtAdd = $pdo->prepare("INSERT INTO user_collections (user_id, item_id, status) VALUES (?, ?, 'owned')");
        $stmtAdd->execute([$userId, $itemId]);
        
        $_SESSION['flash'] = "Предмет добавлен в твою коллекцию!";
        $_SESSION['flash_type'] = "success";
    } else {
        $_SESSION['flash'] = "У тебя это уже есть!";
        $_SESSION['flash_type'] = "warning";
    }
}

header("Location: " . $_SERVER['HTTP_REFERER']); // Возврат назад
exit;
