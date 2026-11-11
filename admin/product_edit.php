<?php
// admin/product_edit.php
require_once __DIR__ . '/admin_auth.php';

$cats = $pdo->query("SELECT * FROM shop_categories ORDER BY title ASC")->fetchAll();

$product = null;
$isEdit = false;
$gallery = [];
$prodId = (int)($_GET['id'] ?? 0);

if ($prodId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
    $stmt->execute([$prodId]);
    $product = $stmt->fetch();

    if ($product) {
        $isEdit = true;
        $gStmt = $pdo->prepare("SELECT * FROM product_gallery WHERE product_id = ?");
        $gStmt->execute([$prodId]);
        $gallery = $gStmt->fetchAll();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $redirectSelf = $isEdit ? '/admin/product_edit.php?id=' . $prodId : '/admin/product_edit.php';
    requireCsrfOrRedirect($redirectSelf);

    // 1. Удаление главного фото (POST)
    if (isset($_POST['del_main_img']) && $isEdit) {
        if (!empty($product['image'])) {
            safeUnlinkUpload($product['image']);
        }
        $pdo->prepare("UPDATE products SET image = '' WHERE id = ?")->execute([$prodId]);
        redirectWithFlash('/admin/product_edit.php?id=' . $prodId, 'Главное фото удалено.', 'info');
    }

    // 2. Удаление фото из галереи (POST)
    if (isset($_POST['del_img_id']) && $isEdit) {
        $imgId = (int)$_POST['del_img_id'];
        $fStmt = $pdo->prepare("SELECT image_path FROM product_gallery WHERE id = ? AND product_id = ? LIMIT 1");
        $fStmt->execute([$imgId, $prodId]);
        $fileData = $fStmt->fetch();

        if ($fileData) {
            safeUnlinkUpload($fileData['image_path']);
            $pdo->prepare("DELETE FROM product_gallery WHERE id = ? AND product_id = ?")->execute([$imgId, $prodId]);
        }
        redirectWithFlash('/admin/product_edit.php?id=' . $prodId, 'Фото удалено из галереи.', 'info');
    }

    // 3. Сохранение/создание товара
    $title     = trim($_POST['title'] ?? '');
    $price     = (int)round((float)($_POST['price'] ?? 0));
    $old_price = (!empty($_POST['old_price']) && (float)$_POST['old_price'] > 0)
        ? (int)round((float)$_POST['old_price'])
        : 0;
    $desc      = trim($_POST['description'] ?? '');
    $cat_id    = (int)($_POST['category_id'] ?? 0);
    $is_sale   = isset($_POST['is_sale']) ? 1 : 0;
    $is_new    = isset($_POST['is_new']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (mb_strlen($title, 'UTF-8') < 2 || mb_strlen($title, 'UTF-8') > 200) {
        redirectWithFlash($redirectSelf, 'Название товара должно содержать от 2 до 200 символов.', 'danger');
    }
    if ($price < 1 || $price > 9999999) {
        redirectWithFlash($redirectSelf, 'Цена товара должна быть от 1 до 9 999 999 ₽.', 'danger');
    }
    if ($old_price > 0 && ($old_price <= $price || $old_price > 9999999)) {
        redirectWithFlash($redirectSelf, 'Старая цена (зачёркнутая) должна быть больше текущей цены и не превышать 9 999 999 ₽.', 'danger');
    }
    if ($cat_id > 0) {
        $catExists = $pdo->prepare("SELECT id FROM shop_categories WHERE id = ? LIMIT 1");
        $catExists->execute([$cat_id]);
        if (!$catExists->fetch()) {
            redirectWithFlash($redirectSelf, 'Выбранная категория магазина не существует.', 'danger');
        }
    }

    // Главное фото (через безопасный anti-RCE загрузчик)
    $mainImagePath = $isEdit ? ($product['image'] ?? '') : '';
    if (!empty($_FILES['image']['name'])) {
        $imgRes = uploadSafeImage($_FILES['image'], 'assets/uploads', 'main_', 5 * 1024 * 1024);
        if (!$imgRes['ok']) {
            redirectWithFlash($redirectSelf, 'Главное фото: ' . $imgRes['error'], 'danger');
        }
        if ($isEdit && !empty($product['image'])) {
            safeUnlinkUpload($product['image']);
        }
        $mainImagePath = '/' . $imgRes['path'];
    }

    if ($isEdit) {
        $sql = "UPDATE products SET title = ?, description = ?, price = ?, old_price = ?, image = ?, category_id = ?, is_sale = ?, is_new = ?, is_active = ? WHERE id = ?";
        $pdo->prepare($sql)->execute([$title, $desc, $price, $old_price, $mainImagePath, $cat_id, $is_sale, $is_new, $is_active, $prodId]);
        $currentId = $prodId;
    } else {
        $sql = "INSERT INTO products (title, description, price, old_price, image, category_id, is_sale, is_new, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $pdo->prepare($sql)->execute([$title, $desc, $price, $old_price, $mainImagePath, $cat_id, $is_sale, $is_new, $is_active]);
        $currentId = (int)$pdo->lastInsertId();
    }

    // Загрузка галереи
    $galleryErrors = [];
    if (!empty($_FILES['gallery']['name'][0])) {
        $total = count($_FILES['gallery']['name']);
        $gInsert = $pdo->prepare("INSERT INTO product_gallery (product_id, image_path) VALUES (?, ?)");

        for ($i = 0; $i < $total; $i++) {
            if ($_FILES['gallery']['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $singleFile = [
                'name'     => $_FILES['gallery']['name'][$i],
                'type'     => $_FILES['gallery']['type'][$i],
                'tmp_name' => $_FILES['gallery']['tmp_name'][$i],
                'error'    => $_FILES['gallery']['error'][$i],
                'size'     => $_FILES['gallery']['size'][$i],
            ];
            $gRes = uploadSafeImage($singleFile, 'assets/uploads', "g{$i}_", 5 * 1024 * 1024);
            if ($gRes['ok']) {
                $gInsert->execute([$currentId, '/' . $gRes['path']]);
            } else {
                $galleryErrors[] = $gRes['error'];
            }
        }
    }

    if (!empty($galleryErrors)) {
        redirectWithFlash('/admin/product_edit.php?id=' . $currentId, 'Товар сохранён, но часть галереи отклонена: ' . $galleryErrors[0], 'warning');
    }
    redirectWithFlash('/admin/products.php', 'Товар сохранён!', 'success');
}

require_once __DIR__ . '/layout_header.php';
?>

<div class="d-flex align-items-center mb-4">
    <a href="/admin/products.php" class="btn btn-outline-dark rounded-0 me-3" aria-label="Назад к товарам"><i class="bi bi-arrow-left" aria-hidden="true"></i></a>
    <h2 class="fw-black text-uppercase m-0"><?= $isEdit ? 'Редактировать товар' : 'Новый товар' ?></h2>
</div>

<form method="POST" enctype="multipart/form-data" data-validate-form data-prevent-double-submit="1" novalidate>
    <?= csrfField() ?>
    <div class="row g-4">

        <div class="col-lg-8">
            <div class="card admin-card p-4">
                <div class="mb-3">
                    <label for="prodTitle" class="fw-bold small text-muted mb-1">НАЗВАНИЕ</label>
                    <input id="prodTitle" type="text" name="title" class="form-control" value="<?= $isEdit ? e($product['title']) : '' ?>" required maxlength="200" data-rule="required-text" data-min="2" data-max="200">
                    <div class="auth-field-error"></div>
                </div>

                <div class="mb-3">
                    <label for="prodDesc" class="fw-bold small text-muted mb-1">ОПИСАНИЕ</label>
                    <textarea id="prodDesc" name="description" class="form-control" rows="6"><?= $isEdit ? e($product['description']) : '' ?></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="prodPrice" class="fw-bold small text-muted mb-1">ЦЕНА (₽, целое)</label>
                        <input id="prodPrice" type="number" name="price" min="1" max="9999999" step="1" class="form-control" value="<?= $isEdit ? (int)$product['price'] : '' ?>" required data-rule="price" data-min="1" data-max="9999999">
                        <div class="auth-field-error"></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="prodOldPrice" class="fw-bold small text-muted mb-1">СТАРАЯ ЦЕНА</label>
                        <input id="prodOldPrice" type="number" name="old_price" min="0" max="9999999" step="1" class="form-control" value="<?= ($isEdit && (int)$product['old_price'] > 0) ? (int)$product['old_price'] : '' ?>" data-rule="price" data-optional="1" data-min="1" data-max="9999999" data-gt-field="price">
                        <div class="auth-field-error"></div>
                    </div>
                </div>
            </div>

            <div class="card admin-card p-4 mt-4 bg-light">
                <h5 class="fw-bold mb-3 border-bottom border-dark pb-2">Галерея изображений</h5>

                <div class="mb-3">
                    <label for="prodGallery" class="fw-bold small text-muted mb-1">ДОБАВИТЬ ФОТО (JPG, PNG, WEBP, до 5 МБ)</label>
                    <input id="prodGallery" type="file" name="gallery[]" class="form-control" multiple accept="image/jpeg,image/png,image/webp,image/gif">
                    <div class="form-text fw-bold">Зажмите Ctrl, чтобы выбрать несколько файлов.</div>
                </div>

                <?php if ($isEdit && !empty($gallery)): ?>
                    <div class="fw-bold small text-muted mb-2">ТЕКУЩИЕ ФОТО:</div>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($gallery as $img): ?>
                            <div class="position-relative border border-2 border-dark" style="width: 100px; height: 100px;">
                                <img src="<?= e($img['image_path']) ?>" alt="Фото галереи" class="w-100 h-100 object-fit-cover">
                                <button type="submit"
                                        form="delImgForm<?= (int)$img['id'] ?>"
                                        class="btn btn-sm btn-danger position-absolute top-0 end-0 p-0 rounded-0 d-flex align-items-center justify-content-center"
                                        style="width: 24px; height: 24px;"
                                        aria-label="Удалить фото"
                                        onclick="return confirm('Удалить это фото?');">
                                    <i class="bi bi-x" aria-hidden="true"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card admin-card p-4 mb-4">
                <div class="mb-4">
                    <label for="prodCat" class="fw-bold small text-muted mb-1">КАТЕГОРИЯ</label>
                    <select id="prodCat" name="category_id" class="form-select">
                        <option value="0">-- Без категории --</option>
                        <?php foreach ($cats as $c): ?>
                            <option value="<?= (int)$c['id'] ?>" <?= ($isEdit && (int)$product['category_id'] === (int)$c['id']) ? 'selected' : '' ?>>
                                <?= e($c['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" name="is_active" id="activeSwitch" <?= (!$isEdit || !empty($product['is_active'])) ? 'checked' : '' ?>>
                    <label class="form-check-label fw-bold" for="activeSwitch">✅ В наличии (показывать в каталоге)</label>
                </div>

                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" name="is_new" id="newSwitch" <?= ($isEdit && !empty($product['is_new'])) ? 'checked' : '' ?>>
                    <label class="form-check-label fw-bold" for="newSwitch">✨ НОВИНКА (NEW)</label>
                </div>

                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" type="checkbox" name="is_sale" id="saleSwitch" <?= ($isEdit && !empty($product['is_sale'])) ? 'checked' : '' ?>>
                    <label class="form-check-label fw-bold" for="saleSwitch">🔥 РАСПРОДАЖА (SALE)</label>
                </div>

                <div class="mb-3">
                    <label for="prodMainImg" class="fw-bold small text-muted mb-1">ГЛАВНОЕ ФОТО</label>
                    <input id="prodMainImg" type="file" name="image" class="form-control mb-2" accept="image/jpeg,image/png,image/webp,image/gif">
                    <?php if ($isEdit && !empty($product['image'])): ?>
                        <div class="position-relative border border-dark p-1">
                            <img src="<?= e($product['image']) ?>" alt="<?= e($product['title']) ?>" class="w-100">
                            <button type="submit"
                                    form="delMainImgForm"
                                    class="btn btn-sm btn-danger position-absolute top-0 end-0 rounded-0"
                                    title="Удалить главное фото"
                                    aria-label="Удалить главное фото"
                                    onclick="return confirm('Удалить главное фото товара?');">
                                <i class="bi bi-trash" aria-hidden="true"></i>
                            </button>
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

<?php if ($isEdit && !empty($product['image'])): ?>
    <form method="POST" id="delMainImgForm" class="d-none">
        <?= csrfField() ?>
        <input type="hidden" name="del_main_img" value="1">
    </form>
<?php endif; ?>

<?php if ($isEdit && !empty($gallery)): ?>
    <?php foreach ($gallery as $img): ?>
        <form method="POST" id="delImgForm<?= (int)$img['id'] ?>" class="d-none">
            <?= csrfField() ?>
            <input type="hidden" name="del_img_id" value="<?= (int)$img['id'] ?>">
        </form>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
