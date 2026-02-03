<?php
// actions/add_to_cart.php
session_start();
require_once __DIR__ . '/../config/db.php';

// Проверяем, был ли POST запрос
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /pages/catalog.php");
    exit;
}

// Получаем данные
$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$sessionId = session_id(); // ID текущей сессии (работает для гостей)

if ($productId > 0) {
    // 1. Проверяем, есть ли уже этот товар в корзине у этого пользователя
    $stmt = $pdo->prepare("SELECT id, quantity FROM cart WHERE session_id = ? AND product_id = ?");
    $stmt->execute([$sessionId, $productId]);
    $existingItem = $stmt->fetch();

    if ($existingItem) {
        // 2. Если есть — увеличиваем количество (+1)
        $updateStmt = $pdo->prepare("UPDATE cart SET quantity = quantity + 1 WHERE id = ?");
        $updateStmt->execute([$existingItem['id']]);
    } else {
        // 3. Если нет — добавляем новую запись
        $insertStmt = $pdo->prepare("INSERT INTO cart (session_id, product_id, quantity) VALUES (?, ?, 1)");
        $insertStmt->execute([$sessionId, $productId]);
    }
}

// 4. Возвращаем пользователя туда, откуда он пришел (Каталог или страница товара)
$redirectUrl = $_SERVER['HTTP_REFERER'] ?? '/pages/catalog.php';
header("Location: $redirectUrl");
exit;
?>
