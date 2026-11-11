<?php
// admin/orders.php
require_once __DIR__ . '/admin_auth.php';

$allowedStatuses = ['new', 'processing', 'shipped', 'completed', 'cancelled'];

$q = trim($_GET['q'] ?? '');
$statusFilter = $_GET['status'] ?? 'all';
if ($statusFilter !== 'all' && !in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = 'all';
}

$queryParts = [];
if ($q !== '') $queryParts['q'] = $q;
if ($statusFilter !== 'all') $queryParts['status'] = $statusFilter;
$backUrl = '/admin/orders.php' . ($queryParts ? ('?' . http_build_query($queryParts)) : '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'], $_POST['id'])) {
    requireCsrfOrRedirect($backUrl);
    $newStatus = $_POST['status'];
    $orderId = (int)$_POST['id'];
    if ($orderId > 0 && in_array($newStatus, $allowedStatuses, true)) {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $orderId]);
        redirectWithFlash($backUrl, 'Статус заказа #' . $orderId . ' обновлен.', 'success');
    }
    redirectWithFlash($backUrl, 'Некорректные данные заказа.', 'danger');
}

require_once __DIR__ . '/layout_header.php';

$whereParts = [];
$params = [];
if ($statusFilter !== 'all') {
    $whereParts[] = 'o.status = ?';
    $params[] = $statusFilter;
}
if ($q !== '') {
    $qNum = ctype_digit(ltrim($q, '#')) ? (int)ltrim($q, '#') : 0;
    $like = '%' . $q . '%';
    $whereParts[] = '(o.id = ? OR o.name LIKE ? OR o.phone LIKE ? OR o.email LIKE ? OR u.username LIKE ?)';
    $params[] = $qNum;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
$whereSql = $whereParts ? ('WHERE ' . implode(' AND ', $whereParts)) : '';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 30;
$cntStmt = $pdo->prepare("SELECT COUNT(*) FROM orders o LEFT JOIN users u ON o.user_id = u.id $whereSql");
$cntStmt->execute($params);
$totalOrders = (int)$cntStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalOrders / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("
    SELECT o.*, u.username
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    $whereSql
    ORDER BY o.created_at DESC
    LIMIT " . (int)$perPage . " OFFSET " . (int)$offset . "
");
$stmt->execute($params);
$orders = $stmt->fetchAll();

$statusColors = [
    'new'        => 'bg-primary',
    'processing' => 'bg-info text-dark',
    'shipped'    => 'bg-warning text-dark',
    'completed'  => 'bg-success',
    'cancelled'  => 'bg-danger',
];
$statusNames = [
    'new'        => 'Новый',
    'processing' => 'В обработке',
    'shipped'    => 'Отправлен',
    'completed'  => 'Выполнен',
    'cancelled'  => 'Отменен',
];
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h2 class="street-font m-0">ЗАКАЗЫ (<?= $totalOrders ?>)</h2>
</div>

<form method="GET" class="admin-card p-3 mb-4 bg-white">
    <div class="row g-2 align-items-end">
        <div class="col-md-5">
            <label for="orderSearch" class="fw-bold small">Поиск (#ID, ФИО, телефон, email, ник)</label>
            <input id="orderSearch" type="text" name="q" class="form-control form-control-sm" value="<?= e($q) ?>" placeholder="#12, +7..., имя или email">
        </div>
        <div class="col-md-4">
            <label for="orderStatusFilter" class="fw-bold small">Статус заказа</label>
            <select id="orderStatusFilter" name="status" class="form-select form-select-sm">
                <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>Все статусы</option>
                <?php foreach ($statusNames as $stKey => $stLabel): ?>
                    <option value="<?= e($stKey) ?>" <?= $statusFilter === $stKey ? 'selected' : '' ?>><?= e($stLabel) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-1">
            <button type="submit" class="btn btn-sm btn-admin btn-admin-dark flex-grow-1">Найти</button>
            <?php if ($q !== '' || $statusFilter !== 'all'): ?>
                <a href="/admin/orders.php" class="btn btn-sm btn-outline-dark rounded-0">Сброс</a>
            <?php endif; ?>
        </div>
    </div>
</form>

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
                            <span class="badge <?= e($badgeColor) ?> rounded-0 text-uppercase">
                                <?= e($statusText) ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="/admin/order_view.php?id=<?= (int)$o['id'] ?>" class="btn btn-sm btn-admin btn-admin-dark">
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
                <a class="page-link rounded-0 border-dark fw-bold" href="?<?= http_build_query(array_merge($queryParts, ['page' => $p])) ?>"><?= $p ?></a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
