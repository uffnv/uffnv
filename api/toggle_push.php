<?php
require_once __DIR__ . '/../config/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enabled = isset($_POST['enabled']) ? (int)$_POST['enabled'] : 1;
    
    try {
        // Создаем или обновляем настройку
        $stmt = $pdo->prepare("
            INSERT INTO user_settings (user_id, push_enabled) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE push_enabled = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $enabled, $enabled]);
        
        echo json_encode(['success' => true, 'push_enabled' => (bool)$enabled]);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}
