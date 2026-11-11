<?php
// admin/orders.php
require_once __DIR__ . '/admin_auth.php';

$allowedStatuses = ['new', 'processing', 'shipped', 'completed', 'cancelled'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'], $_POST['id'])) {
    requireCsrfOrRedirect('/admin/orders.php');
    $newStatus = $_POST['status'];
    $orderId = (int)$_POST['id'];
    if ($orderId > 0 && in_array($newStatus, $allowedStatuses, true)) {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $orderId]);
        redirectWithFlash('/admin/orders.php', 'Статус заказа #' . $orderId . ' обновлен.', 'success');
    }
    redirectWithFlash('/admin/orders.php', 'Некорректные данные заказа.', 'danger');
}

require_once __DIR__ . '/layout_header.php';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 30;
$totalOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalPages = max(1, (int)ceil($totalOrders / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("
    SELECT o.*, u.username
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->bindValue(1, $perPage, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$orders = $stmt->fetchAll();

$statusColors = [
    'new'        => 'primary',
    'processing' => 'info',
    'shipped'    => 'warning',
    'completed'  => 'success',
    'cancelled'  => 'danger',
];
$statusNames = [
    'new'        => 'Новый',
    'processing' => 'В обработке',
    'shipped'    => 'Отправлен',
    'completed'  => 'Выполнен',
    'cancelled'  => 'Отменен',
];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="street-font m-0">ЗАКАЗЫ (<?= $totalOrders ?>)</h2>
</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table table-admin table-hover m-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Покупатель</th>
                    <th>Сумма</th>
                    <th>Дата</th>
                    <th>Статус</th>
                    <th class="text-end">Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $o):
                    $st = $o['status'] ?? 'new';
                    $badgeColor = $statusColors[$st] ?? 'secondary';
                    $statusText = $statusNames[$st] ?? $st;
                    $amount = $o['total_amount'] ?? ($o['total_price'] ?? 0);
                    $buyerLabel = !empty($o['username']) ? $o['username'] : ($o['name'] . ' (гость)');
                ?>
                    <tr>
                        <td class="fw-bold">#<?= (int)$o['id'] ?></td>
                        <td>
                            <i class="bi bi-person-fill text-muted me-1" aria-hidden="true"></i>
                            <?= e($buyerLabel) ?>
                        </td>
                        <td class="fw-bold">
                            <?= number_format((float)$amount, 0, '', ' ') ?> ₽
                        </td>
                        <td class="small text-muted">
                            <?= date('d.m.Y H:i', strtotime($o['created_at'])) ?>
                        </td>
                        <td>
                            <span class="badge bg-<?= e($badgeColor) ?> rounded-0 text-uppercase">
                                <?= e($statusText) ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="order_view.php?id=<?= (int)$o['id'] ?>" class="btn btn-sm btn-admin btn-admin-dark">
                                <i class="bi bi-eye-fill me-1" aria-hidden="true"></i> Подробнее
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted fw-bold">Заказов пока нет</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPages > 1): ?>
<nav aria-label="Страницы заказов">
    <ul class="pagination">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                <a class="page-link rounded-0 border-dark fw-bold" href="?page=<?= $p ?>"><?= $p ?></a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
