<?php
// admin/order_view.php
require_once __DIR__ . '/admin_auth.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: orders.php");
    exit;
}

$allowedStatuses = ['new', 'processing', 'shipped', 'completed', 'cancelled'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_status'])) {
    requireCsrfOrRedirect('/admin/order_view.php?id=' . $id);
    $newSt = $_POST['status'] ?? '';
    if (in_array($newSt, $allowedStatuses, true)) {
        $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$newSt, $id]);
        redirectWithFlash('/admin/order_view.php?id=' . $id, 'Статус заказа обновлен.', 'success');
    }
    redirectWithFlash('/admin/order_view.php?id=' . $id, 'Недопустимый статус.', 'danger');
}

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order) {
    redirectWithFlash('/admin/orders.php', 'Заказ #' . $id . ' не найден.', 'warning');
}

require_once __DIR__ . '/layout_header.php';

$stmtItems = $pdo->prepare("SELECT oi.*, p.title, p.image FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$stmtItems->execute([$id]);
$items = $stmtItems->fetchAll();

$amount = $order['total_amount'] ?? ($order['total_price'] ?? 0);
?>

<div class="d-flex align-items-center mb-4">
    <a href="orders.php" class="btn btn-outline-dark rounded-0 me-3" aria-label="Назад к заказам"><i class="bi bi-arrow-left" aria-hidden="true"></i></a>
    <h2 class="fw-black text-uppercase m-0">Заказ #<?= (int)$order['id'] ?></h2>
    <span class="badge bg-dark rounded-0 ms-3 fs-6"><?= e($order['status']) ?></span>
</div>

<div class="row g-4">
    <div class="col-lg-4 order-lg-2">
        <div class="card admin-card p-4 mb-4">
            <h5 class="fw-bold mb-3 border-bottom pb-2">Управление</h5>
            <form method="POST">
                <?= csrfField() ?>
                <label for="orderStatusSelect" class="small fw-bold text-muted mb-1">СМЕНИТЬ СТАТУС</label>
                <div class="input-group">
                    <select id="orderStatusSelect" name="status" class="form-select">
                        <option value="new" <?= $order['status'] === 'new' ? 'selected' : '' ?>>Новый (New)</option>
                        <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>>В обработке</option>
                        <option value="shipped" <?= $order['status'] === 'shipped' ? 'selected' : '' ?>>Отправлен</option>
                        <option value="completed" <?= $order['status'] === 'completed' ? 'selected' : '' ?>>Выполнен</option>
                        <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Отменен</option>
                    </select>
                    <button type="submit" name="change_status" value="1" class="btn btn-warning btn-admin">OK</button>
                </div>
            </form>
        </div>

        <div class="card admin-card p-4">
            <h5 class="fw-bold mb-3 border-bottom pb-2">Клиент</h5>
            <div class="mb-2"><strong>Имя:</strong> <?= e($order['name']) ?></div>
            <div class="mb-2"><strong>Телефон:</strong> <?= e($order['phone']) ?></div>
            <div class="mb-2"><strong>Email:</strong> <?= e($order['email']) ?></div>
            <div class="mb-4"><strong>Адрес:</strong> <br><?= e($order['address']) ?></div>

            <?php if (!empty($order['comment'])): ?>
                <div class="alert alert-warning border-2 border-dark rounded-0 fw-bold">
                    <small class="text-uppercase text-muted d-block">Комментарий:</small>
                    <?= e($order['comment']) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-8 order-lg-1">
        <div class="card admin-card p-0">
            <div class="card-header bg-white fw-bold py-3 px-4 border-bottom border-dark">
                СОСТАВ ЗАКАЗА
            </div>
            <table class="table table-hover mb-0 align-middle">
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted fw-bold">Позиции заказа отсутствуют (возможно, товар был удалён в старой версии БД).</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $item):
                            $prodTitle = $item['title'] ?? ($item['product_title'] ?? ('Товар #' . (int)$item['product_id']));
                        ?>
                            <tr>
                                <td class="ps-4" style="width: 70px;">
                                    <?php if (!empty($item['image'])): ?>
                                        <img src="<?= e($item['image']) ?>" alt="<?= e($prodTitle) ?>" style="width: 50px; height: 50px; object-fit: cover;" class="border border-dark">
                                    <?php else: ?>
                                        <div class="border border-dark d-flex align-items-center justify-content-center bg-light" style="width: 50px; height: 50px;"><i class="bi bi-box" aria-hidden="true"></i></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($item['product_id'])): ?>
                                        <a href="/pages/product.php?id=<?= (int)$item['product_id'] ?>" target="_blank" rel="noopener noreferrer" class="fw-bold text-dark text-decoration-none">
                                            <?= e($prodTitle) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="fw-bold text-muted"><?= e($prodTitle) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= (int)$item['quantity'] ?> шт.</td>
                                <td class="text-end fw-bold pe-4">
                                    <?= number_format((float)$item['price'] * (int)$item['quantity'], 0, '', ' ') ?> ₽
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot class="table-light fw-black fs-5">
                    <tr>
                        <td colspan="3" class="text-end py-3">ИТОГО:</td>
                        <td class="text-end pe-4 py-3"><?= number_format((float)$amount, 0, '', ' ') ?> ₽</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
