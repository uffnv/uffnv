<?php require_once 'layout_header.php'; ?>

<?php
if (!isset($_GET['id'])) { echo "<script>window.location='orders.php';</script>"; exit; }
$id = (int)$_GET['id'];

// Смена статуса
if (isset($_POST['change_status'])) {
    $newSt = $_POST['status'];
    $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$newSt, $id]);
    // Обновим страницу, чтобы увидеть изменения
    echo "<script>window.location='order_view.php?id=$id';</script>";
}

// Данные заказа
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$id]);
$order = $stmt->fetch();

// Товары в заказе
$stmtItems = $pdo->prepare("SELECT oi.*, p.title, p.image FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$stmtItems->execute([$id]);
$items = $stmtItems->fetchAll();

$amount = isset($order['total_amount']) ? $order['total_amount'] : ($order['total_price'] ?? 0);
?>

<div class="d-flex align-items-center mb-4">
    <a href="orders.php" class="btn btn-outline-dark rounded-0 me-3"><i class="bi bi-arrow-left"></i></a>
    <h2 class="fw-black text-uppercase m-0">Заказ #<?= $order['id'] ?></h2>
    <span class="badge bg-dark rounded-0 ms-3 fs-6"><?= $order['status'] ?></span>
</div>

<div class="row g-4">
    <!-- ИНФО О КЛИЕНТЕ И СТАТУС -->
    <div class="col-lg-4 order-lg-2">
        <div class="card admin-card p-4 mb-4">
            <h5 class="fw-bold mb-3 border-bottom pb-2">Управление</h5>
            <form method="POST">
                <label class="small fw-bold text-muted mb-1">СМЕНИТЬ СТАТУС</label>
                <div class="input-group">
                    <select name="status" class="form-select">
                        <option value="new" <?= $order['status']=='new'?'selected':'' ?>>В обработке (New)</option>
                        <option value="processing" <?= $order['status']=='processing'?'selected':'' ?>>Собирается</option>
                        <option value="shipped" <?= $order['status']=='shipped'?'selected':'' ?>>Отправлен</option>
                        <option value="completed" <?= $order['status']=='completed'?'selected':'' ?>>Выполнен</option>
                        <option value="cancelled" <?= $order['status']=='cancelled'?'selected':'' ?>>Отменен</option>
                    </select>
                    <button type="submit" name="change_status" class="btn btn-warning btn-admin">OK</button>
                </div>
            </form>
        </div>

        <div class="card admin-card p-4">
            <h5 class="fw-bold mb-3 border-bottom pb-2">Клиент</h5>
            <div class="mb-2"><strong>Имя:</strong> <?= htmlspecialchars($order['name']) ?></div>
            <div class="mb-2"><strong>Телефон:</strong> <?= htmlspecialchars($order['phone']) ?></div>
            <div class="mb-2"><strong>Email:</strong> <?= htmlspecialchars($order['email']) ?></div>
            <div class="mb-4"><strong>Адрес:</strong> <br><?= htmlspecialchars($order['address']) ?></div>
            
            <?php if(!empty($order['comment'])): ?>
                <div class="alert alert-warning border-2 border-dark rounded-0 fw-bold">
                    <small class="text-uppercase text-muted d-block">Комментарий:</small>
                    <?= htmlspecialchars($order['comment']) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- СОСТАВ ЗАКАЗА -->
    <div class="col-lg-8 order-lg-1">
        <div class="card admin-card p-0">
            <div class="card-header bg-white fw-bold py-3 px-4 border-bottom border-dark">
                СОСТАВ ЗАКАЗА
            </div>
            <table class="table table-hover mb-0 align-middle">
                <tbody>
                    <?php foreach($items as $item): ?>
                        <tr>
                            <td class="ps-4" style="width: 70px;">
                                <img src="<?= $item['image'] ?>" style="width: 50px; height: 50px; object-fit: cover;" class="border border-dark">
                            </td>
                            <td>
                                <a href="/pages/product.php?id=<?= $item['product_id'] ?>" target="_blank" class="fw-bold text-dark text-decoration-none">
                                    <?= htmlspecialchars($item['title']) ?>
                                </a>
                            </td>
                            <td><?= $item['quantity'] ?> шт.</td>
                            <td class="text-end fw-bold pe-4">
                                <?= number_format($item['price'] * $item['quantity'], 0, '', ' ') ?> ₽
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-light fw-black fs-5">
                    <tr>
                        <td colspan="3" class="text-end py-3">ИТОГО:</td>
                        <td class="text-end pe-4 py-3"><?= number_format($amount, 0, '', ' ') ?> ₽</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php require_once 'layout_footer.php'; ?>
