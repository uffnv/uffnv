<?php
// actions/place_order.php
require_once __DIR__ . '/../includes/functions.php';
initAppSession();
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /pages/catalog.php");
    exit;
}

requireCsrfOrRedirect('/pages/checkout.php');

$name    = trim($_POST['name'] ?? '');
$phone   = trim($_POST['phone'] ?? '');
$email   = trim($_POST['email'] ?? '');
$city    = trim($_POST['city'] ?? '');
$address = trim($_POST['address'] ?? '');
$comment = trim($_POST['comment'] ?? '');
$payment = $_POST['payment_method'] ?? '';
$consent = isset($_POST['checkout_pd_consent']) ? 1 : 0;

$oldCheckout = [
    'name'           => $name,
    'phone'          => $phone,
    'email'          => $email,
    'city'           => $city,
    'address'        => $address,
    'comment'        => $comment,
    'payment_method' => $payment,
];

if ($consent !== 1) {
    $_SESSION['old'] = $oldCheckout;
    redirectWithFlash('/pages/checkout.php', 'Необходимо подтвердить согласие на обработку персональных данных.', 'danger');
}

if (mb_strlen($name, 'UTF-8') < 2 || mb_strlen($name, 'UTF-8') > 120) {
    $_SESSION['old'] = $oldCheckout;
    redirectWithFlash('/pages/checkout.php', 'Укажите корректное ФИО или псевдоним (от 2 до 120 символов).', 'danger');
}

$phoneCheck = normalizeAndValidatePhone($phone);
if (!$phoneCheck['valid']) {
    $_SESSION['old'] = $oldCheckout;
    redirectWithFlash('/pages/checkout.php', $phoneCheck['error'], 'danger');
}
$phone = $phoneCheck['value'];

$emailCheck = validateEmailStrict($email);
if (!$emailCheck['valid']) {
    $_SESSION['old'] = $oldCheckout;
    redirectWithFlash('/pages/checkout.php', $emailCheck['error'], 'danger');
}
$email = $emailCheck['value'];

if (mb_strlen($city, 'UTF-8') < 2 || mb_strlen($city, 'UTF-8') > 100 || mb_strlen($address, 'UTF-8') < 5 || mb_strlen($address, 'UTF-8') > 255) {
    $_SESSION['old'] = $oldCheckout;
    redirectWithFlash('/pages/checkout.php', 'Укажите корректный город и адрес доставки.', 'danger');
}

if (mb_strlen($comment, 'UTF-8') > 1000) {
    $_SESSION['old'] = $oldCheckout;
    redirectWithFlash('/pages/checkout.php', 'Комментарий к заказу слишком длинный (максимум 1000 символов).', 'danger');
}

if (!in_array($payment, ['card_online', 'card', 'cash'], true)) {
    $_SESSION['old'] = $oldCheckout;
    redirectWithFlash('/pages/checkout.php', 'Выберите корректный способ оплаты.', 'danger');
}
unset($_SESSION['old']);

$fullAddress = $city . ', ' . $address;
$sessId = session_id();

$sql = "SELECT c.quantity, p.id as product_id, p.price, p.title
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.session_id = ? AND p.is_active = 1";
$stmt = $pdo->prepare($sql);
$stmt->execute([$sessId]);
$cartItems = $stmt->fetchAll();

if (empty($cartItems)) {
    redirectWithFlash('/pages/cart.php', 'Ваша корзина пуста или товары сняты с продажи.', 'warning');
}

$totalAmount = 0;
foreach ($cartItems as $item) {
    $qty = max(1, (int)$item['quantity']);
    $totalAmount += ((float)$item['price']) * $qty;
}

try {
    $pdo->beginTransaction();

    $userId = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

    if ($userId) {
        $checkStmt = $pdo->prepare("SELECT phone FROM users WHERE id = ? LIMIT 1");
        $checkStmt->execute([$userId]);
        $uData = $checkStmt->fetch();
        if ($uData && empty($uData['phone']) && $phone !== '') {
            $pdo->prepare("UPDATE users SET phone = ? WHERE id = ?")->execute([$phone, $userId]);
        }
    }

    $orderSql = "INSERT INTO orders (user_id, name, phone, email, address, total_amount, payment_method, comment)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $pdo->prepare($orderSql)->execute([
        $userId,
        $name,
        $phone,
        $email,
        $fullAddress,
        $totalAmount,
        $payment,
        $comment,
    ]);

    $orderId = (int)$pdo->lastInsertId();

    $itemSql = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
    $itemStmt = $pdo->prepare($itemSql);
    foreach ($cartItems as $cItem) {
        $itemStmt->execute([
            $orderId,
            $cItem['product_id'],
            max(1, (int)$cItem['quantity']),
            $cItem['price'],
        ]);
    }

    $pdo->prepare("DELETE FROM cart WHERE session_id = ?")->execute([$sessId]);
    $pdo->commit();

    // Сохраняем в сессию список недавно оформленных заказов (защита от IDOR на order_success.php для гостей)
    if (!isset($_SESSION['allowed_order_ids']) || !is_array($_SESSION['allowed_order_ids'])) {
        $_SESSION['allowed_order_ids'] = [];
    }
    $_SESSION['allowed_order_ids'][] = $orderId;

    header("Location: /pages/order_success.php?id=" . $orderId);
    exit;
} catch (\Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Place order error: " . $e->getMessage());
    redirectWithFlash('/pages/checkout.php', 'Ошибка при оформлении заказа. Попробуйте позже.', 'danger');
}
