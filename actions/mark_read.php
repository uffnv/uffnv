<?php
// actions/mark_read.php
require_once __DIR__ . '/../includes/functions.php';
initAppSession();
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SESSION['user_id']) && isset($_POST['id']) && verifyCsrfToken()) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([(int)$_POST['id'], (int)$_SESSION['user_id']]);
}
