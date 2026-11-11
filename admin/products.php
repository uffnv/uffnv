<?php
// admin/products.php
require_once __DIR__ . '/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    requireCsrfOrRedirect('/admin/products.php');
    $id = (int)($_POST['id'] ?? 0);

    if ($id > 0) {
        // Проверяем, участвовал ли товар в заказах (избегаем сноса order_items через CASCADE до миграции)
        $orderCntStmt = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE product_id = ?");
        $orderCntStmt->execute([$id]);
        $usedInOrders = (int)$orderCntStmt->fetchColumn() > 0;

        if ($usedInOrders) {
            $pdo->prepare("UPDATE products SET is_active = 0 WHERE id = ?")->execute([$id]);
            redirectWithFlash('/admin/products.php', 'Товар скрыт (архивирован), так как присутствует в истории заказов.', 'warning');
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
        redirectWithFlash('/admin/products.php', 'Товар и его изображения удалены.', 'success');
    }
}

require_once __DIR__ . '/layout_header.php';

$prods = $pdo->query("SELECT p.*, c.title as cat_title FROM products p LEFT JOIN shop_categories c ON p.category_id = c.id ORDER BY p.id DESC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-black text-uppercase m-0">Товары (<?= count($prods) ?>)</h2>
    <a href="/admin/product_edit.php" class="btn btn-warning btn-admin">+ ДОБАВИТЬ ТОВАР</a>
</div>

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
                <?php foreach ($prods as $p): ?>
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
                            <a href="/admin/product_edit.php?id=<?= (int)$p['id'] ?>" class="btn btn-sm btn-outline-dark rounded-0 me-1" aria-label="Редактировать <?= e($p['title']) ?>"><i class="bi bi-pencil-fill" aria-hidden="true"></i></a>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Удалить/скрыть этот товар?');">
                                <?= csrfField() ?>
                                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                <button type="submit" name="delete_product" value="1" class="btn btn-sm btn-danger rounded-0" aria-label="Удалить <?= e($p['title']) ?>"><i class="bi bi-trash-fill" aria-hidden="true"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/layout_footer.php'; ?>
