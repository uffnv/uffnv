<?php
// actions/cart.php
require_once __DIR__ . '/../includes/functions.php';
initAppSession();
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /pages/cart.php");
    exit;
}

$sessId = session_id();
$action = $_POST['action'] ?? '';
$referer = safeRedirectUrl($_SERVER['HTTP_REFERER'] ?? '/pages/catalog.php', '/pages/catalog.php');

// 1. ДОБАВИТЬ В КОРЗИНУ
if ($action === 'add') {
    requireCsrfOrRedirect($referer);
    $prodId = (int)($_POST['product_id'] ?? 0);
    if ($prodId <= 0) {
        redirectWithFlash($referer, 'Товар не указан.', 'danger');
    }

    $prodStmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND is_active = 1 LIMIT 1");
    $prodStmt->execute([$prodId]);
    if (!$prodStmt->fetch()) {
        redirectWithFlash($referer, 'Товар недоступен или удален.', 'warning');
    }

    $stmt = $pdo->prepare("SELECT id, quantity FROM cart WHERE session_id = ? AND product_id = ? LIMIT 1");
    $stmt->execute([$sessId, $prodId]);
    $item = $stmt->fetch();

    if ($item) {
        $newQty = min(99, (int)$item['quantity'] + 1);
        $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?")->execute([$newQty, $item['id']]);
    } else {
        $pdo->prepare("INSERT INTO cart (session_id, product_id, quantity) VALUES (?, ?, 1)")->execute([$sessId, $prodId]);
    }

    redirectWithFlash($referer, 'Товар добавлен в корзину!', 'success');
}

// 2. УДАЛИТЬ ИЗ КОРЗИНЫ
if ($action === 'delete') {
    requireCsrfOrRedirect('/pages/cart.php');
    $cartId = (int)($_POST['id'] ?? $_POST['cart_id'] ?? 0);
    if ($cartId > 0) {
        $pdo->prepare("DELETE FROM cart WHERE id = ? AND session_id = ?")->execute([$cartId, $sessId]);
    }
    header("Location: /pages/cart.php");
    exit;
}

// 3. ОБНОВИТЬ КОЛИЧЕСТВО
if ($action === 'update') {
    requireCsrfOrRedirect('/pages/cart.php');
    $cartId = (int)($_POST['cart_id'] ?? 0);
    $qty = (int)($_POST['qty'] ?? 0);

    if ($cartId > 0) {
        if ($qty > 0) {
            $qty = min(99, $qty);
            $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND session_id = ?")->execute([$qty, $cartId, $sessId]);
        } else {
            $pdo->prepare("DELETE FROM cart WHERE id = ? AND session_id = ?")->execute([$cartId, $sessId]);
        }
    }
    header("Location: /pages/cart.php");
    exit;
}

header("Location: /pages/cart.php");
exit;
