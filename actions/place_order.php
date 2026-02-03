<?php
// actions/place_order.php
session_start();
require_once __DIR__ . '/../config/db.php';

// Если скрипт вызван не через форму - выкидываем
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /catalog.php");
    exit;
}

// 1. ПОЛУЧЕНИЕ ДАННЫХ ИЗ ФОРМЫ (pages/checkout.php)
$name = trim($_POST['name']);
$phone = trim($_POST['phone']);
$email = trim($_POST['email']);
$city = trim($_POST['city']);       // Поле города
$address = trim($_POST['address']); // Поле улицы/дома
$comment = trim($_POST['comment']);
$payment = $_POST['payment_method'];

// Объединяем Город и Адрес в одну строку для базы данных
$fullAddress = $city . ', ' . $address;

$sessId = session_id();

// 2. ПОЛУЧАЕМ СОДЕРЖИМОЕ КОРЗИНЫ
// Нам нужны ID товара, цена и картинка (если понадобится)
$sql = "SELECT c.quantity, p.id as product_id, p.price, p.title 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.session_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$sessId]);
$cartItems = $stmt->fetchAll();

if (empty($cartItems)) {
    die("Ошибка: Ваша корзина пуста.");
}

// 3. РАСЧЕТ ОБЩЕЙ СУММЫ
$totalAmount = 0;
foreach($cartItems as $item) {
    $totalAmount += $item['price'] * $item['quantity'];
}

try {
    // Начинаем транзакцию (чтобы всё записалось или ничего)
    $pdo->beginTransaction();

    // 4. ОПРЕДЕЛЯЕМ ПОЛЬЗОВАТЕЛЯ
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

    // Если пользователь авторизован, можно обновить его телефон, если он пустой
    if ($userId) {
        $checkStmt = $pdo->prepare("SELECT phone FROM users WHERE id = ?");
        $checkStmt->execute([$userId]);
        $uData = $checkStmt->fetch();
        if (empty($uData['phone']) && !empty($phone)) {
            $pdo->prepare("UPDATE users SET phone = ? WHERE id = ?")->execute([$phone, $userId]);
        }
    }

    // 5. СОЗДАЕМ ЗАКАЗ (Таблица orders)
    // Вставляем объединенный адрес $fullAddress
    $orderSql = "INSERT INTO orders (user_id, name, phone, email, address, total_amount, payment_method, comment) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $pdo->prepare($orderSql)->execute([
        $userId, 
        $name, 
        $phone, 
        $email, 
        $fullAddress, // Сохраняем "Москва, ул. Пушкина..."
        $totalAmount, 
        $payment, 
        $comment
    ]);
    
    // Получаем ID только что созданного заказа
    $orderId = $pdo->lastInsertId();

    // 6. СОХРАНЯЕМ ПОЗИЦИИ ЗАКАЗА (Таблица order_items)
    // Это нужно для модального окна просмотра состава заказа
    $itemSql = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
    $itemStmt = $pdo->prepare($itemSql);

    foreach ($cartItems as $cItem) {
        $itemStmt->execute([
            $orderId, 
            $cItem['product_id'], 
            $cItem['quantity'], 
            $cItem['price'] // Фиксируем цену на момент покупки
        ]);
    }

    // 7. ОЧИЩАЕМ КОРЗИНУ ПОЛЬЗОВАТЕЛЯ
    $pdo->prepare("DELETE FROM cart WHERE session_id = ?")->execute([$sessId]);

    // Применяем все изменения
    $pdo->commit();

    // 8. ПЕРЕНАПРАВЛЕНИЕ НА УСПЕХ
    header("Location: /pages/order_success.php?id=$orderId");
    exit;

} catch (Exception $e) {
    // Если ошибка - отменяем всё
    $pdo->rollBack();
    die("Ошибка при оформлении заказа: " . $e->getMessage());
}
