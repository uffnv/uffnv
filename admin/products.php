<?php require_once 'layout_header.php'; ?>
<?php
// Удаление товара
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Можно добавить удаление картинки с диска, но пока просто из БД
    $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
    echo "<script>window.location='products.php';</script>";
}

$prods = $pdo->query("SELECT p.*, c.title as cat_title FROM products p LEFT JOIN shop_categories c ON p.category_id = c.id ORDER BY p.id DESC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-black text-uppercase m-0">Товары</h2>
    <a href="product_edit.php" class="btn btn-warning btn-admin">+ ДОБАВИТЬ ТОВАР</a>
</div>

<div class="card admin-card p-0">
    <table class="table table-hover mb-0 align-middle fw-bold">
        <thead class="table-light">
            <tr>
                <th class="ps-4">ID</th>
                <th>Фото</th>
                <th>Название</th>
                <th>Категория</th>
                <th>Цена</th>
                <th>Распродажа</th>
                <th class="text-end pe-4">Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($prods as $p): ?>
                <tr>
                    <td class="ps-4">#<?= $p['id'] ?></td>
                    <td>
                        <img src="<?= htmlspecialchars($p['image']) ?>" style="width: 40px; height: 40px; object-fit: cover;" class="border border-dark">
                    </td>
                    <td><?= htmlspecialchars($p['title']) ?></td>
                    <td><span class="badge bg-light text-dark border border-dark rounded-0"><?= $p['cat_title'] ?? '-' ?></span></td>
                    <td><?= number_format($p['price'], 0, '', ' ') ?> ₽</td>
                    <td>
                        <?php if($p['is_sale']): ?>
                            <span class="badge bg-danger rounded-0">SALE</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end pe-4">
                        <a href="product_edit.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-dark rounded-0 me-1"><i class="bi bi-pencil-fill"></i></a>
                        <a href="?delete=<?= $p['id'] ?>" class="btn btn-sm btn-danger rounded-0" onclick="return confirm('Удалить товар?');"><i class="bi bi-trash-fill"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require_once 'layout_footer.php'; ?>
