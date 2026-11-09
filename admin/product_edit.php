<?php require_once 'layout_header.php'; ?>

<?php
// Категории
$cats = $pdo->query("SELECT * FROM shop_categories ORDER BY title ASC")->fetchAll();

$product = null;
$isEdit = false;
$gallery = [];

// === ЗАГРУЗКА ДАННЫХ ===
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    
    if ($product) {
        $isEdit = true;
        // Загружаем галерею из таблицы product_gallery
        $gStmt = $pdo->prepare("SELECT * FROM product_gallery WHERE product_id = ?");
        $gStmt->execute([$id]);
        $gallery = $gStmt->fetchAll();
    }
}

// === УДАЛЕНИЕ ФОТО ИЗ ГАЛЕРЕИ ===
if (isset($_GET['del_img'])) {
    $imgId = (int)$_GET['del_img'];
    $prodId = (int)$_GET['id']; 
    
    // Получаем путь файла
    $fStmt = $pdo->prepare("SELECT image_path FROM product_gallery WHERE id = ?");
    $fStmt->execute([$imgId]);
    $fileData = $fStmt->fetch();
    
    if ($fileData) {
        $fullPath = __DIR__ . '/..' . $fileData['image_path'];
        if (file_exists($fullPath)) unlink($fullPath);
        
        // Удаляем запись из product_gallery
        $pdo->prepare("DELETE FROM product_gallery WHERE id = ?")->execute([$imgId]);
    }
    
    echo "<script>window.location='product_edit.php?id=$prodId';</script>";
    exit;
}

// === СОХРАНЕНИЕ ТОВАРА ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $price = (float)$_POST['price'];
    $old_price = !empty($_POST['old_price']) ? (float)$_POST['old_price'] : null;
    $desc = trim($_POST['description']);
    $cat_id = (int)$_POST['category_id'];
    $is_sale = isset($_POST['is_sale']) ? 1 : 0;
    
    $uploadDir = __DIR__ . '/../assets/uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    // 1. ГЛАВНОЕ ФОТО
    $mainImagePath = $isEdit ? $product['image'] : '';
    if (!empty($_FILES['image']['name'])) {
        $fileName = time() . '_main_' . basename($_FILES['image']['name']);
        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $fileName)) {
            $mainImagePath = '/assets/uploads/' . $fileName;
        }
    }

    // 2. СОХРАНЯЕМ/ОБНОВЛЯЕМ ПРОДУКТ
    if ($isEdit) {
        $sql = "UPDATE products SET title=?, description=?, price=?, old_price=?, image=?, category_id=?, is_sale=? WHERE id=?";
        $pdo->prepare($sql)->execute([$title, $desc, $price, $old_price, $mainImagePath, $cat_id, $is_sale, $product['id']]);
        $currentId = $product['id'];
    } else {
        $sql = "INSERT INTO products (title, description, price, old_price, image, category_id, is_sale) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $pdo->prepare($sql)->execute([$title, $desc, $price, $old_price, $mainImagePath, $cat_id, $is_sale]);
        $currentId = $pdo->lastInsertId();
    }

    // 3. ЗАГРУЗКА ГАЛЕРЕИ (В product_gallery)
    if (!empty($_FILES['gallery']['name'][0])) {
        $total = count($_FILES['gallery']['name']);
        $gInsert = $pdo->prepare("INSERT INTO product_gallery (product_id, image_path) VALUES (?, ?)");
        
        for ($i = 0; $i < $total; $i++) {
            if ($_FILES['gallery']['error'][$i] === UPLOAD_ERR_OK) {
                $gName = time() . "_g{$i}_" . basename($_FILES['gallery']['name'][$i]);
                if (move_uploaded_file($_FILES['gallery']['tmp_name'][$i], $uploadDir . $gName)) {
                    $gPath = '/assets/uploads/' . $gName;
                    $gInsert->execute([$currentId, $gPath]);
                }
            }
        }
    }

    echo "<script>window.location='products.php';</script>";
}
?>

<div class="d-flex align-items-center mb-4">
    <a href="products.php" class="btn btn-outline-dark rounded-0 me-3"><i class="bi bi-arrow-left"></i></a>
    <h2 class="fw-black text-uppercase m-0"><?= $isEdit ? 'Редактировать товар' : 'Новый товар' ?></h2>
</div>

<form method="POST" enctype="multipart/form-data">
    <div class="row g-4">
        
        <!-- ЛЕВАЯ КОЛОНКА (Инфо) -->
        <div class="col-lg-8">
            <div class="card admin-card p-4">
                <div class="mb-3">
                    <label class="fw-bold small text-muted mb-1">НАЗВАНИЕ</label>
                    <input type="text" name="title" class="form-control" value="<?= $isEdit ? htmlspecialchars($product['title']) : '' ?>" required>
                </div>
                
                <div class="mb-3">
                    <label class="fw-bold small text-muted mb-1">ОПИСАНИЕ</label>
                    <textarea name="description" class="form-control" rows="6"><?= $isEdit ? htmlspecialchars($product['description']) : '' ?></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="fw-bold small text-muted mb-1">ЦЕНА (₽)</label>
                        <input type="number" name="price" class="form-control" value="<?= $isEdit ? $product['price'] : '' ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="fw-bold small text-muted mb-1">СТАРАЯ ЦЕНА</label>
                        <input type="number" name="old_price" class="form-control" value="<?= $isEdit ? $product['old_price'] : '' ?>">
                    </div>
                </div>
            </div>

            <!-- БЛОК ГАЛЕРЕИ -->
            <div class="card admin-card p-4 mt-4 bg-light">
                <h5 class="fw-bold mb-3 border-bottom border-dark pb-2">Галерея изображений</h5>
                
                <div class="mb-3">
                    <label class="fw-bold small text-muted mb-1">ДОБАВИТЬ ФОТО</label>
                    <input type="file" name="gallery[]" class="form-control" multiple accept="image/*">
                    <div class="form-text fw-bold">Зажмите Ctrl, чтобы выбрать несколько файлов.</div>
                </div>

                <?php if ($isEdit && !empty($gallery)): ?>
                    <label class="fw-bold small text-muted mb-2">ТЕКУЩИЕ ФОТО:</label>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach($gallery as $img): ?>
                            <div class="position-relative border border-2 border-dark" style="width: 100px; height: 100px;">
                                <img src="<?= $img['image_path'] ?>" class="w-100 h-100 object-fit-cover">
                                <!-- Кнопка удаления -->
                                <a href="?id=<?= $product['id'] ?>&del_img=<?= $img['id'] ?>" 
                                   class="btn btn-sm btn-danger position-absolute top-0 end-0 p-0 rounded-0 d-flex align-items-center justify-content-center" 
                                   style="width: 24px; height: 24px;"
                                   onclick="return confirm('Удалить это фото?');">
                                    <i class="bi bi-x"></i>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ПРАВАЯ КОЛОНКА (Настройки) -->
        <div class="col-lg-4">
            <div class="card admin-card p-4 mb-4">
                <div class="mb-4">
                    <label class="fw-bold small text-muted mb-1">КАТЕГОРИЯ</label>
                    <select name="category_id" class="form-select">
                        <?php foreach($cats as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= ($isEdit && $product['category_id'] == $c['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" type="checkbox" name="is_sale" id="saleSwitch" <?= ($isEdit && $product['is_sale']) ? 'checked' : '' ?>>
                    <label class="form-check-label fw-bold" for="saleSwitch">🔥 ЭТО РАСПРОДАЖА</label>
                </div>

                <div class="mb-3">
                    <label class="fw-bold small text-muted mb-1">ГЛАВНОЕ ФОТО</label>
                    <input type="file" name="image" class="form-control mb-2" accept="image/*">
                    <?php if($isEdit && !empty($product['image'])): ?>
                        <div class="border border-dark p-1">
                            <img src="<?= $product['image'] ?>" class="w-100">
                        </div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-warning btn-admin w-100 py-3 mt-2">
                    <?= $isEdit ? 'СОХРАНИТЬ' : 'СОЗДАТЬ' ?>
                </button>
            </div>
        </div>
    </div>
</form>

<?php require_once 'layout_footer.php'; ?>
