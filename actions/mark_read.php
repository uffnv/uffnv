<?php
// actions/mark_read.php
session_start();
require_once __DIR__ . '/../config/db.php';
if (isset($_SESSION['user_id']) && isset($_POST['id'])) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([(int)$_POST['id'], $_SESSION['user_id']]);
}
