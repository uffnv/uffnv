<?php
// pages/product.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

// 1. Получаем ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 2. Запрос товара
$sql = "SELECT p.*, c.title as cat_title, c.id as cat_id
        FROM products p
        JOIN shop_categories c ON p.category_id = c.id
        WHERE p.id = ? AND p.is_active = 1";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    echo '<div class="container py-5 text-center text-white"><h1 class="display-1 street-font">404</h1><p class="fs-4 street-font">Артефакт не найден.</p></div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// 3. Галерея
$gallerySql = "SELECT image_path FROM product_gallery WHERE product_id = ?";
$gStmt = $pdo->prepare($gallerySql);
$gStmt->execute([$id]);
$gallery = $gStmt->fetchAll(PDO::FETCH_COLUMN);
array_unshift($gallery, $product['image']); // Главное фото первое

// 4. Похожие
$relSql = "SELECT * FROM products WHERE category_id = ? AND id != ? AND is_active = 1 ORDER BY RAND() LIMIT 4";
$relStmt = $pdo->prepare($relSql);
$relStmt->execute([$product['cat_id'], $id]);
$related = $relStmt->fetchAll();
?>

<style>
    /* === ОБЩИЙ СТИЛЬ === */
    body { font-family: 'Arial', sans-serif; background: #120024; color: #fff; }
    
    .street-font {
        font-family: 'Arial Black', 'Impact', sans-serif;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .text-street-yellow { color: #FCE300; text-shadow: 2px 2px 0 #000; }

    /* === ФОН NEON GRID === */
    .bg-main-anim {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background-image: 
            linear-gradient(rgba(188, 19, 254, 0.3) 1px, transparent 1px),
            linear-gradient(90deg, rgba(188, 19, 254, 0.3) 1px, transparent 1px);
        background-size: 80px 80px;
        perspective: 500px;
        transform-style: preserve-3d;
        animation: grid-move 6s linear infinite;
        box-shadow: inset 0 0 150px rgba(0,0,0,0.9); 
        z-index: -1;
    }
    @keyframes grid-move { 0% { background-position: 0 0; } 100% { background-position: 0 80px; } }
    .bg-main-anim::after {
        content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 100%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
        background-size: 40px 40px;
    }

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
    
    <!-- Хлебные крошки -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb fw-bold text-uppercase small">
            <li class="breadcrumb-item"><a href="/index.php">Главная</a></li>
            <li class="breadcrumb-item"><a href="/pages/catalog.php">Каталог</a></li>
            <li class="breadcrumb-item active text-break"><?= htmlspecialchars($product['title']) ?></li>
        </ol>
    </nav>

    <div class="row g-5 mb-5">
        
        <!-- === ЛЕВАЯ КОЛОНКА: ФОТО + ГАЛЕРЕЯ === -->
        <div class="col-lg-7">
            <div class="d-flex flex-column flex-md-row gap-3">
                
                <!-- Миниатюры (Слева на десктопе, снизу на мобилке, без слайдера - просто перенос) -->
                <!-- На десктопе (md) это колонка, на мобилке (по умолчанию) это ряд с переносом -->
                <div class="d-flex flex-row flex-md-column flex-wrap gap-2 order-2 order-md-1 justify-content-center justify-content-md-start">
                    <?php foreach($gallery as $index => $img): ?>
                        <div class="thumb-item <?= $index === 0 ? 'active-thumb' : '' ?>" 
                             onclick="changeImage(this, '<?= $img ?>')">
                            <img src="<?= $img ?>" class="w-100 h-100 object-fit-cover">
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Главное фото -->
                <div class="flex-grow-1 main-image-box order-1 order-md-2" id="mainImageContainer">
                    <div class="position-absolute top-0 start-0 p-3 pe-none" style="z-index: 10;">
                        <?php if($product['is_new']): ?>
                            <span class="badge bg-danger rounded-0 border border-dark text-uppercase d-table mb-2 street-font px-2 py-1">NEW</span>
                        <?php endif; ?>
                        <?php if($product['is_sale']): ?>
                            <span class="badge bg-warning text-dark rounded-0 border border-dark text-uppercase d-table street-font px-2 py-1">SALE</span>
                        <?php endif; ?>
                    </div>

                    <img src="<?= $product['image'] ?>" id="mainImage" alt="<?= htmlspecialchars($product['title']) ?>">
                </div>

            </div>
        </div>

        <!-- === ПРАВАЯ КОЛОНКА: ИНФО === -->
        <div class="col-lg-5">
            <div class="h-100 d-flex flex-column product-info-card">
                
                <!-- Заголовок с word-break -->
                <h1 class="display-5 street-font mb-2 text-break" style="line-height: 1.2; text-shadow: 2px 2px 0 #ccc;">
                    <?= htmlspecialchars($product['title']) ?>
                </h1>
                
                <div class="mb-4 pb-3 border-bottom border-3 border-dark">
                    <span class="badge bg-black text-white rounded-0 text-uppercase border border-dark street-font px-2">
                        <?= htmlspecialchars($product['cat_title']) ?>
                    </span>
                    <span class="ms-2 fw-bold text-muted small text-uppercase">ART: <?= $product['id'] ?></span>
                </div>

                <div class="mb-4 d-flex align-items-end flex-wrap gap-3">
                    <?php if($product['old_price'] > 0): ?>
                        <div>
                            <span class="text-decoration-line-through text-muted fw-bold fs-4">
                                <?= number_format($product['old_price'], 0, '', ' ') ?> ₽
                            </span>
                        </div>
                        <div class="text-danger street-font display-4 lh-1">
                            <?= number_format($product['price'], 0, '', ' ') ?> ₽
                        </div>
                    <?php else: ?>
                        <div class="street-font display-4 lh-1 text-dark">
                            <?= number_format($product['price'], 0, '', ' ') ?> ₽
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mb-4">
                    <h6 class="street-font mb-2 text-dark">ОПИСАНИЕ</h6>
                    <p class="fw-medium text-secondary text-break" style="line-height: 1.5;">
                        <?= $product['description'] ? nl2br(htmlspecialchars($product['description'])) : 'Описание отсутствует.' ?>
                    </p>
                </div>

                <div class="mt-auto pt-4 border-top border-3 border-dark">
                    <form action="/actions/add_to_cart.php" method="POST" class="d-grid gap-3">
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
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
                                <div><i class="bi bi-arrow-repeat fs-5 d-block mb-1"></i> Возврат 14 дней</div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- ПОХОЖИЕ ТОВАРЫ -->
    <?php if($related): ?>
        <div class="mt-5 pt-5 border-top border-4 border-white">
            <h3 class="street-font mb-4 text-white">
                <span class="text-street-yellow">См. также</span> на районе
            </h3>
            <div class="row g-4">
                <?php foreach($related as $p): ?>
                    <div class="col-6 col-md-3">
                        <a href="/pages/product.php?id=<?= $p['id'] ?>" class="text-decoration-none">
                            <div class="card h-100 rounded-0 related-card">
                                <div class="position-relative" style="padding-top: 100%; overflow: hidden;">
                                    <img src="<?= $p['image'] ?>" class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover">
                                </div>
                                <div class="card-body p-3">
                                    <div class="street-font small text-truncate text-dark mb-1"><?= $p['title'] ?></div>
                                    <div class="street-font fs-5 text-dark"><?= number_format($p['price'], 0, '', ' ') ?> ₽</div>
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
function changeImage(element, src) {
    document.getElementById('mainImage').src = src;
    document.querySelectorAll('.thumb-item').forEach(el => {
        el.classList.remove('active-thumb');
    });
    element.classList.add('active-thumb');
}

const container = document.getElementById('mainImageContainer');
const img = document.getElementById('mainImage');

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
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
