<?php
// pages/product.php
require_once __DIR__ . '/../includes/functions.php';
initAppSession();
require_once __DIR__ . '/../config/db.php';

// 1. Получаем ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 2. Запрос товара
$sql = "SELECT p.*, COALESCE(c.title, 'Каталог') as cat_title, c.id as cat_id
        FROM products p
        LEFT JOIN shop_categories c ON p.category_id = c.id
        WHERE p.id = ? AND p.is_active = 1";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    require_once __DIR__ . '/../includes/header.php';
    echo '<div class="bg-main-anim"></div><div class="container py-5 text-center text-white" style="position:relative;z-index:2;"><h1 class="display-1 street-font text-street-yellow">404</h1><p class="fs-4 street-font">Артефакт не найден.</p><a href="/pages/catalog.php" class="btn btn-warning text-dark rounded-0 border-3 border-dark street-font mt-3">В каталог</a></div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

require_once __DIR__ . '/../includes/header.php';

// 3. Галерея
$gallerySql = "SELECT image_path FROM product_gallery WHERE product_id = ?";
$gStmt = $pdo->prepare($gallerySql);
$gStmt->execute([$id]);
$gallery = $gStmt->fetchAll(PDO::FETCH_COLUMN);
if (!empty($product['image'])) {
    array_unshift($gallery, $product['image']);
}

// 4. Похожие
$related = [];
if (!empty($product['cat_id'])) {
    $relSql = "SELECT * FROM products WHERE category_id = ? AND id != ? AND is_active = 1 ORDER BY RAND() LIMIT 4";
    $relStmt = $pdo->prepare($relSql);
    $relStmt->execute([$product['cat_id'], $id]);
    $related = $relStmt->fetchAll();
}
?>

<style>
    /* === ОБЩИЙ СТИЛЬ === */
    body { font-family: 'Arial', sans-serif; background: #120024; color: #fff; }

    /* Хлебные крошки */
    .breadcrumb-item a { color: #fff; text-decoration: none; border-bottom: 2px solid transparent; font-family: 'Arial Black', sans-serif; }
    .breadcrumb-item a:hover { color: #FCE300; border-color: #FCE300; }
    .breadcrumb-item.active { color: #FCE300; font-family: 'Arial Black', sans-serif; }
    .breadcrumb-item + .breadcrumb-item::before { color: #555; content: "/"; font-weight: 900; }

    /* === ЛЕВАЯ КОЛОНКА (ФОТО) === */
    .main-image-box {
        border: 4px solid #000;
        background: #fff;
        box-shadow: 10px 10px 0 #000;
        min-height: 400px;
        height: auto;
        position: relative;
        overflow: hidden; 
        cursor: crosshair;
    }
    
    #mainImage {
        width: 100%; height: 100%;
        object-fit: cover;
        transform-origin: center center;
        transition: transform 0.1s ease-out;
    }

    /* Миниатюры */
    .thumb-item {
        border: 3px solid #000;
        width: 80px; height: 100px;
        cursor: pointer;
        opacity: 0.7;
        transition: all 0.2s;
        background: #fff;
        flex-shrink: 0; /* Чтобы не сжимались */
    }
    .thumb-item:hover { opacity: 1; transform: translateY(-3px); border-color: #bc13fe; }
    .thumb-item.active-thumb {
        border-color: #FCE300;
        opacity: 1;
        box-shadow: 3px 3px 0 #000;
        transform: translateY(-3px);
    }
    
    /* === ПРАВАЯ КОЛОНКА (ИНФО) === */
    .product-info-card {
        background: #fff;
        color: #000;
        border: 4px solid #000;
        box-shadow: 10px 10px 0 #bc13fe;
        padding: 2rem;
    }

    .btn-buy {
        background: #FCE300;
        color: #000;
        border: 3px solid #000;
        box-shadow: 6px 6px 0 #000;
        transition: all 0.2s;
        font-family: 'Arial Black', 'Impact', sans-serif;
    }
    .btn-buy:hover {
        background: #000;
        color: #FCE300;
        border-color: #FCE300;
        transform: translate(-3px, -3px);
        box-shadow: 8px 8px 0 #FCE300;
    }

    .related-card {
        background: #fff;
        border: 3px solid #000;
        transition: transform 0.2s;
        color: #000;
    }
    .related-card:hover {
        transform: translateY(-5px);
        box-shadow: 8px 8px 0 #FCE300;
        border-color: #000;
    }
</style>

<!-- ФОН -->
<div class="bg-main-anim"></div>

<div class="container py-5" style="position: relative; z-index: 2;">
    
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb fw-bold text-uppercase small">
            <li class="breadcrumb-item"><a href="/index.php">Главная</a></li>
            <li class="breadcrumb-item"><a href="/pages/catalog.php">Каталог</a></li>
            <li class="breadcrumb-item active text-break"><?= e($product['title']) ?></li>
        </ol>
    </nav>

    <div class="row g-5 mb-5">

        <!-- === ЛЕВАЯ КОЛОНКА: ФОТО + ГАЛЕРЕЯ === -->
        <div class="col-lg-7">
            <div class="d-flex flex-column flex-md-row gap-3">

                <div class="d-flex flex-row flex-md-column flex-wrap gap-2 order-2 order-md-1 justify-content-center justify-content-md-start">
                    <?php foreach ($gallery as $index => $img): ?>
                        <?php $thumbSrc = safeUrl($img, ''); if ($thumbSrc === '') continue; ?>
                        <button type="button"
                                class="thumb-item p-0 <?= $index === 0 ? 'active-thumb' : '' ?>"
                                data-src="<?= e($thumbSrc) ?>"
                                aria-label="Показать фото <?= (int)$index + 1 ?>">
                            <img src="<?= e($thumbSrc) ?>" alt="<?= e($product['title']) ?>" class="w-100 h-100 object-fit-cover">
                        </button>
                    <?php endforeach; ?>
                </div>

                <!-- Главное фото -->
                <div class="flex-grow-1 main-image-box order-1 order-md-2" id="mainImageContainer">
                    <div class="position-absolute top-0 start-0 p-3 pe-none" style="z-index: 10;">
                        <?php if ($product['is_new']): ?>
                            <span class="badge bg-danger rounded-0 border border-dark text-uppercase d-table mb-2 street-font px-2 py-1">NEW</span>
                        <?php endif; ?>
                        <?php if ($product['is_sale']): ?>
                            <span class="badge bg-warning text-dark rounded-0 border border-dark text-uppercase d-table street-font px-2 py-1">SALE</span>
                        <?php endif; ?>
                    </div>

                    <img src="<?= e(safeUrl($product['image'] ?? '', '')) ?>" id="mainImage" alt="<?= e($product['title']) ?>">
                </div>

            </div>
        </div>

        <!-- === ПРАВАЯ КОЛОНКА: ИНФО === -->
        <div class="col-lg-5">
            <div class="h-100 d-flex flex-column product-info-card">

                <h1 class="display-5 street-font mb-2 text-break" style="line-height: 1.2; text-shadow: 2px 2px 0 #ccc;">
                    <?= e($product['title']) ?>
                </h1>

                <div class="mb-4 pb-3 border-bottom border-3 border-dark">
                    <span class="badge bg-black text-white rounded-0 text-uppercase border border-dark street-font px-2">
                        <?= e($product['cat_title']) ?>
                    </span>
                    <span class="ms-2 fw-bold text-muted small text-uppercase">ART: <?= (int)$product['id'] ?></span>
                </div>

                <div class="mb-4 d-flex align-items-end flex-wrap gap-3">
                    <?php if ($product['old_price'] > 0): ?>
                        <div>
                            <span class="text-decoration-line-through text-muted fw-bold fs-4">
                                <?= number_format((float)$product['old_price'], 0, '', ' ') ?> ₽
                            </span>
                        </div>
                        <div class="text-danger street-font display-4 lh-1">
                            <?= number_format((float)$product['price'], 0, '', ' ') ?> ₽
                        </div>
                    <?php else: ?>
                        <div class="street-font display-4 lh-1 text-dark">
                            <?= number_format((float)$product['price'], 0, '', ' ') ?> ₽
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mb-4">
                    <h6 class="street-font mb-2 text-dark">ОПИСАНИЕ</h6>
                    <p class="fw-medium text-secondary text-break" style="line-height: 1.5;">
                        <?= $product['description'] ? nl2br(e($product['description'])) : 'Описание отсутствует.' ?>
                    </p>
                </div>

                <div class="mt-auto pt-4 border-top border-3 border-dark">
                    <form action="/actions/cart.php" method="POST" class="d-grid gap-3">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                        <button type="submit" class="btn btn-buy btn-lg rounded-0 text-uppercase py-3 fs-4">
                            В КОРЗИНУ
                        </button>
                    </form>

                    <div class="row mt-4 g-2 text-center small text-uppercase fw-bold street-font text-dark">
                        <div class="col-6">
                            <div class="border border-2 border-dark p-2 bg-light h-100 d-flex align-items-center justify-content-center">
                                <div><i class="bi bi-truck fs-5 d-block mb-1"></i> Доставка 2-5 дней</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="border border-2 border-dark p-2 bg-light h-100 d-flex align-items-center justify-content-center">
                                <div><i class="bi bi-arrow-repeat fs-5 d-block mb-1"></i> Возврат по условиям</div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- ПОХОЖИЕ ТОВАРЫ -->
    <?php if ($related): ?>
        <div class="mt-5 pt-5 border-top border-4 border-white">
            <h3 class="street-font mb-4 text-white">
                <span class="text-street-yellow">См. также</span> на районе
            </h3>
            <div class="row g-4">
                <?php foreach ($related as $p): ?>
                    <div class="col-6 col-md-3">
                        <a href="/pages/product.php?id=<?= (int)$p['id'] ?>" class="text-decoration-none">
                            <div class="card h-100 rounded-0 related-card">
                                <div class="position-relative" style="padding-top: 100%; overflow: hidden;">
                                    <img src="<?= e(safeUrl($p['image'] ?? '', '')) ?>" alt="<?= e($p['title']) ?>" class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover">
                                </div>
                                <div class="card-body p-3">
                                    <div class="street-font small text-truncate text-dark mb-1"><?= e($p['title']) ?></div>
                                    <div class="street-font fs-5 text-dark"><?= number_format((float)$p['price'], 0, '', ' ') ?> ₽</div>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

</div>

<script>
document.querySelectorAll('.thumb-item').forEach(btn => {
    btn.addEventListener('click', function() {
        const src = this.dataset.src;
        if (!src) return;
        document.getElementById('mainImage').src = src;
        document.querySelectorAll('.thumb-item').forEach(el => el.classList.remove('active-thumb'));
        this.classList.add('active-thumb');
    });
});

const container = document.getElementById('mainImageContainer');
const img = document.getElementById('mainImage');

if (container && img && window.matchMedia('(hover: hover)').matches) {
    container.addEventListener('mousemove', function(e) {
        const { left, top, width, height } = container.getBoundingClientRect();
        const x = (e.clientX - left) / width * 100;
        const y = (e.clientY - top) / height * 100;
        img.style.transformOrigin = `${x}% ${y}%`;
        img.style.transform = 'scale(2)';
    });

    container.addEventListener('mouseleave', function() {
        img.style.transformOrigin = 'center center';
        img.style.transform = 'scale(1)';
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
