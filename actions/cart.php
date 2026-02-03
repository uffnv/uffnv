<?php
// actions/cart.php
session_start();
require_once __DIR__ . '/../config/db.php';

// Получаем ID сессии (уникальный для каждого посетителя)
$sessId = session_id(); 
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// 1. ДОБАВИТЬ В КОРЗИНУ
if ($action == 'add') {
    $prodId = (int)$_POST['product_id'];
    
    // Проверяем, есть ли уже товар в корзине у этого юзера
    $stmt = $pdo->prepare("SELECT id, quantity FROM cart WHERE session_id = ? AND product_id = ?");
    $stmt->execute([$sessId, $prodId]);
    $item = $stmt->fetch();

    if ($item) {
        // Если есть - увеличиваем кол-во
        $pdo->prepare("UPDATE cart SET quantity = quantity + 1 WHERE id = ?")->execute([$item['id']]);
    } else {
        // Если нет - добавляем
        $pdo->prepare("INSERT INTO cart (session_id, product_id, quantity) VALUES (?, ?, 1)")->execute([$sessId, $prodId]);
    }
    
    // Возвращаем туда, откуда пришли
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}

// 2. УДАЛИТЬ ИЗ КОРЗИНЫ
if ($action == 'delete') {
    $cartId = (int)$_GET['id'];
    $pdo->prepare("DELETE FROM cart WHERE id = ? AND session_id = ?")->execute([$cartId, $sessId]);
    header("Location: /pages/cart.php");
    exit;
}

// 3. ОБНОВИТЬ КОЛИЧЕСТВО (Плюс/Минус)
if ($action == 'update') {
    $cartId = (int)$_POST['cart_id'];
    $qty = (int)$_POST['qty'];
    
    if ($qty > 0) {
        $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND session_id = ?")->execute([$qty, $cartId, $sessId]);
    } else {
        // Если 0, то удаляем
        $pdo->prepare("DELETE FROM cart WHERE id = ? AND session_id = ?")->execute([$cartId, $sessId]);
    }
    header("Location: /pages/cart.php");
    exit;
}
