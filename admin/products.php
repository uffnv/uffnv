<?php
// admin/products.php
require_once __DIR__ . '/admin_auth.php';

$q      = trim($_GET['q'] ?? '');
$catId  = max(0, (int)($_GET['cat'] ?? 0));
$status = $_GET['status'] ?? 'all';
if (!in_array($status, ['all', 'active', 'hidden'], true)) {
    $status = 'all';
}

$queryParts = [];
if ($q !== '') $queryParts['q'] = $q;
if ($catId > 0) $queryParts['cat'] = $catId;
if ($status !== 'all') $queryParts['status'] = $status;
$backUrl = '/admin/products.php' . ($queryParts ? ('?' . http_build_query($queryParts)) : '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfOrRedirect($backUrl);
    $id = (int)($_POST['id'] ?? 0);

    if ($id > 0 && isset($_POST['toggle_active'])) {
        $curr = (int)($_POST['current_active'] ?? 0);
        $next = $curr ? 0 : 1;
        $pdo->prepare("UPDATE products SET is_active = ? WHERE id = ?")->execute([$next, $id]);
        redirectWithFlash($backUrl, $next ? 'Товар включён в каталоге.' : 'Товар скрыт из каталога.', 'info');
    }

    if ($id > 0 && isset($_POST['delete_product'])) {
        // Проверяем, участвовал ли товар в заказах (избегаем сноса order_items через CASCADE до миграции)
        $orderCntStmt = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE product_id = ?");
        $orderCntStmt->execute([$id]);
        $usedInOrders = (int)$orderCntStmt->fetchColumn() > 0;

        if ($usedInOrders) {
            $pdo->prepare("UPDATE products SET is_active = 0 WHERE id = ?")->execute([$id]);
            redirectWithFlash($backUrl, 'Товар скрыт (архивирован), так как присутствует в истории заказов.', 'warning');
        }

        $pStmt = $pdo->prepare("SELECT image FROM products WHERE id = ? LIMIT 1");
        $pStmt->execute([$id]);
        $prod = $pStmt->fetch();

        $gStmt = $pdo->prepare("SELECT image_path FROM product_gallery WHERE product_id = ?");
        $gStmt->execute([$id]);
        $gImgs = $gStmt->fetchAll();

        if ($prod && !empty($prod['image'])) {
            safeUnlinkUpload($prod['image']);
        }
        foreach ($gImgs as $gi) {
            safeUnlinkUpload($gi['image_path']);
        }

        $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
        redirectWithFlash($backUrl, 'Товар и его изображения удалены.', 'success');
    }
}

require_once __DIR__ . '/layout_header.php';

$shopCats = $pdo->query("SELECT id, title FROM shop_categories ORDER BY title ASC")->fetchAll();

$whereParts = [];
$params = [];
if ($q !== '') {
    $whereParts[] = '(p.title LIKE ? OR p.id = ?)';
    $params[] = '%' . $q . '%';
    $params[] = ctype_digit($q) ? (int)$q : 0;
}
if ($catId > 0) {
    $whereParts[] = 'p.category_id = ?';
    $params[] = $catId;
}
if ($status === 'active') {
    $whereParts[] = 'p.is_active = 1';
} elseif ($status === 'hidden') {
    $whereParts[] = 'p.is_active = 0';
}
$whereSql = $whereParts ? ('WHERE ' . implode(' AND ', $whereParts)) : '';

$stmt = $pdo->prepare("SELECT p.*, c.title as cat_title FROM products p LEFT JOIN shop_categories c ON p.category_id = c.id $whereSql ORDER BY p.id DESC");
$stmt->execute($params);
$prods = $stmt->fetchAll();
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h2 class="fw-black text-uppercase m-0">Товары (<?= count($prods) ?>)</h2>
    <a href="/admin/product_edit.php" class="btn btn-warning btn-admin">+ ДОБАВИТЬ ТОВАР</a>
</div>

<form method="GET" class="admin-card p-3 mb-4 bg-white">
    <div class="row g-2 align-items-end">
        <div class="col-md-4">
            <label for="prodSearch" class="fw-bold small">Поиск по названию или ID</label>
            <input id="prodSearch" type="text" name="q" class="form-control form-control-sm" value="<?= e($q) ?>" placeholder="Название или #ID...">
        </div>
        <div class="col-md-3">
            <label for="prodCatFilter" class="fw-bold small">Категория</label>
            <select id="prodCatFilter" name="cat" class="form-select form-select-sm">
                <option value="0">Все категории</option>
                <?php foreach ($shopCats as $sc): ?>
                    <option value="<?= (int)$sc['id'] ?>" <?= $catId === (int)$sc['id'] ? 'selected' : '' ?>><?= e($sc['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label for="prodStatusFilter" class="fw-bold small">Статус</label>
            <select id="prodStatusFilter" name="status" class="form-select form-select-sm">
                <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>Все</option>
                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>В наличии</option>
                <option value="hidden" <?= $status === 'hidden' ? 'selected' : '' ?>>Скрытые</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-1">
            <button type="submit" class="btn btn-sm btn-admin btn-admin-dark flex-grow-1">Фильтр</button>
            <?php if ($q !== '' || $catId > 0 || $status !== 'all'): ?>
                <a href="/admin/products.php" class="btn btn-sm btn-outline-dark rounded-0">Сброс</a>
            <?php endif; ?>
        </div>
    </div>
</form>

<div class="card admin-card p-0">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle fw-bold">
            <thead class="table-light">
                <tr>
                    <th class="ps-4">ID</th>
                    <th>Фото</th>
                    <th>Название</th>
                    <th>Категория</th>
                    <th>Цена</th>
                    <th>Метки</th>
                    <th class="text-end pe-4">Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($prods)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Товары не найдены.</td></tr>
                <?php else: foreach ($prods as $p): ?>
                    <tr class="<?= empty($p['is_active']) ? 'table-secondary opacity-75' : '' ?>">
                        <td class="ps-4">#<?= (int)$p['id'] ?></td>
                        <td>
                            <?php if (!empty($p['image'])): ?>
                                <img src="<?= e($p['image']) ?>" alt="<?= e($p['title']) ?>" style="width: 40px; height: 40px; object-fit: cover;" class="border border-dark">
                            <?php else: ?>
                                <div class="border border-dark bg-light d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;"><i class="bi bi-image" aria-hidden="true"></i></div>
                            <?php endif; ?>
                        </td>
                        <td><?= e($p['title']) ?></td>
                        <td><span class="badge bg-light text-dark border border-dark rounded-0"><?= e($p['cat_title'] ?? '-') ?></span></td>
                        <td><?= number_format((float)$p['price'], 0, '', ' ') ?> ₽</td>
                        <td>
                            <?php if (empty($p['is_active'])): ?>
                                <span class="badge bg-dark rounded-0">СКРЫТ</span>
                            <?php endif; ?>
                            <?php if (!empty($p['is_new'])): ?>
                                <span class="badge bg-warning text-dark rounded-0">NEW</span>
                            <?php endif; ?>
                            <?php if (!empty($p['is_sale'])): ?>
                                <span class="badge bg-danger rounded-0">SALE</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end pe-4">
                            <div class="d-inline-flex gap-1">
                                <a href="/admin/product_edit.php?id=<?= (int)$p['id'] ?>" class="btn btn-sm btn-outline-dark rounded-0" title="Редактировать" aria-label="Редактировать <?= e($p['title']) ?>"><i class="bi bi-pencil-fill" aria-hidden="true"></i></a>
                                <form method="POST" class="d-inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                    <input type="hidden" name="current_active" value="<?= (int)$p['is_active'] ?>">
                                    <button type="submit" name="toggle_active" value="1" class="btn btn-sm <?= !empty($p['is_active']) ? 'btn-secondary' : 'btn-success' ?> rounded-0" title="<?= !empty($p['is_active']) ? 'Скрыть товар' : 'Показать товар' ?>" aria-label="Переключить видимость <?= e($p['title']) ?>">
                                        <i class="bi <?= !empty($p['is_active']) ? 'bi-eye-slash' : 'bi-eye' ?>" aria-hidden="true"></i>
                                    </button>
                                </form>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Удалить/скрыть этот товар?');">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                    <button type="submit" name="delete_product" value="1" class="btn btn-sm btn-danger rounded-0" title="Удалить" aria-label="Удалить <?= e($p['title']) ?>"><i class="bi bi-trash-fill" aria-hidden="true"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/layout_footer.php'; ?>
