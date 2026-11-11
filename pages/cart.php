<?php
// pages/cart.php
require_once __DIR__ . '/../includes/functions.php';
initAppSession();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

$sessId = session_id();

// Получаем товары
$sql = "SELECT c.id as cart_id, c.quantity, p.id as product_id, p.title, p.price, p.image, p.old_price
        FROM cart c
        JOIN products p ON c.product_id = p.id AND p.is_active = 1
        WHERE c.session_id = ?
        ORDER BY c.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$sessId]);
$cartItems = $stmt->fetchAll();

// Итог
$total = 0;
foreach ($cartItems as $item) {
    $total += (float)$item['price'] * (int)$item['quantity'];
}
?>

<style>
    /* === ШРИФТЫ И ЦВЕТА (ОБЩИЙ СТИЛЬ) === */
    body {
        font-family: 'Arial', sans-serif;
        background: #120024;
        color: #fff;
    }

    /* === КАРТОЧКА ТОВАРА === */
    .cart-card {
        background: #fff;
        color: #000;
        border: 3px solid #000;
        box-shadow: 8px 8px 0 #000;
        transition: box-shadow 0.3s ease;
        overflow: hidden;
    }
    .cart-card:hover {
        box-shadow: 10px 10px 0 #bc13fe; /* Неоновая тень */
    }

    /* Анимация картинки */
    .cart-img-wrapper { overflow: hidden; height: 100%; display: block; }
    .cart-img-wrapper img {
        transition: transform 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        transform-origin: center center;
    }
    .cart-card:hover .cart-img-wrapper img { transform: scale(1.1) rotate(2deg); }

    /* Кнопка удаления */
    .delete-btn i { display: inline-block; transition: transform 0.3s ease, color 0.3s; }
    .delete-btn:hover i { transform: rotate(90deg) scale(1.2); color: #dc3545; }

    /* Ссылки на товары */
    .small-title-link {
        transition: all 0.2s ease;
        background: #000; color: #fff;
        font-family: 'Arial', sans-serif;
        font-weight: 900;
        text-transform: uppercase;
    }
    .small-title-link:hover {
        background: #FCE300 !important; color: #000 !important;
        border-color: #000 !important; padding-left: 10px !important; 
    }

    /* === ЧЕК (SIDEBAR) === */
.checkout-card {
        background: rgba(18, 18, 18, 0.95);
        backdrop-filter: blur(10px);
        border: 3px solid #FCE300;
        box-shadow: 0 0 20px rgba(252, 227, 0, 0.15);
        color: #fff;
    }

    /* Кнопка оформления (ИСПРАВЛЕННЫЙ HOVER) */
    .btn-checkout {
        background: #FCE300; 
        color: #000;
        border: 3px solid #000; /* Черная рамка в спокойном состоянии */
        font-family: 'Arial Black', 'Impact', sans-serif;
        transition: all 0.2s ease;
        position: relative;
    }

    .btn-checkout:hover {
        background: #000;           /* Черный фон */
        color: #FCE300;             /* Желтый текст */
        border-color: #FCE300;      /* Желтая рамка */
        transform: translate(-4px, -4px);
        box-shadow: 8px 8px 0 #FCE300; /* Желтая жесткая тень */
    }
    
    .btn-checkout i { display: inline-block; transition: transform 0.3s ease; }
    .btn-checkout:hover i { transform: translateX(5px) scale(1.1); }
</style>

<!-- ФОН -->
<div class="bg-main-anim"></div>

<div class="container py-5" style="position: relative; z-index: 2; min-height: 80vh;">
    
    <div class="d-flex align-items-center mb-5 border-bottom border-4 border-light pb-3">
        <h1 class="display-4 street-font m-0 text-white" style="text-shadow: 4px 4px 0 #000;">
            Твоя <span class="text-street-yellow">корзина</span>
        </h1>
        <span class="badge bg-warning text-dark rounded-0 ms-3 fs-4 border-2 border-dark street-font"><?= count($cartItems) ?></span>
    </div>

    <?php if(empty($cartItems)): ?>
        <div class="text-center py-5 bg-white border-4 border-dark shadow-lg cart-card p-5" style="max-width: 600px; margin: 0 auto; transform: rotate(-1deg);">
            <i class="bi bi-cart-x display-1 text-muted mb-3 d-block"></i>
            <h3 class="street-font text-dark mb-3">Здесь пусто</h3>
            <p class="text-dark fw-bold mb-4 fs-5">Ты еще не выбрал ни одной шмотки.</p>
            <a href="/pages/catalog.php" class="btn btn-warning rounded-0 border-3 border-dark fw-black px-5 py-3 text-uppercase shadow-sm street-font fs-5">
                Вернуться в шоп
            </a>
        </div>
    <?php else: ?>
        <div class="row g-5">
            
            <!-- СПИСОК ТОВАРОВ -->
            <div class="col-lg-8">
                <?php foreach($cartItems as $item): ?>
                    <div class="card mb-4 rounded-0 cart-card border-0">
                        <div class="row g-0">
                            
                            <!-- ФОТО -->
                            <div class="col-4 col-md-3 border-end border-3 border-dark position-relative" style="max-width: 140px; height: 140px;">
                                <a href="/pages/product.php?id=<?= (int)$item['product_id'] ?>" class="cart-img-wrapper h-100">
                                    <img src="<?= e(safeUrl($item['image'] ?? '', '')) ?>"
                                         class="w-100 h-100 object-fit-cover"
                                         alt="<?= e($item['title']) ?>">
                                </a>
                            </div>

                            <!-- ИНФО -->
                            <div class="col-8 col-md-9">
                                <div class="card-body h-100 d-flex flex-column justify-content-between py-3 px-3 px-md-4">

                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="me-3 overflow-hidden w-100">
                                            <a href="/pages/product.php?id=<?= (int)$item['product_id'] ?>"
                                               class="d-inline-block text-decoration-none px-2 py-1 mb-1 small-title-link text-truncate border border-black"
                                               style="max-width: 100%;">
                                                <?= e($item['title']) ?>
                                            </a>
                                            <?php if ($item['old_price'] > 0): ?>
                                                <div class="text-muted text-decoration-line-through small fw-bold mt-1">
                                                    <?= number_format((float)$item['old_price'], 0, '', ' ') ?> ₽
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Кнопка удаления (POST + CSRF) -->
                                        <form action="/actions/cart.php" method="POST" class="ms-2">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="cart_id" value="<?= (int)$item['cart_id'] ?>">
                                            <button type="submit"
                                                    class="btn btn-link p-0 text-danger fs-3 lh-1 delete-btn border-0"
                                                    title="Удалить"
                                                    aria-label="Удалить <?= e($item['title']) ?>">
                                                <i class="bi bi-x-square-fill"></i>
                                            </button>
                                        </form>
                                    </div>

                                    <div class="d-flex align-items-end justify-content-between pt-2 mt-auto">

                                        <!-- Кол-во -->
                                        <form action="/actions/cart.php" method="POST" class="d-flex align-items-center">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="cart_id" value="<?= (int)$item['cart_id'] ?>">

                                            <div class="input-group input-group-sm" style="width: 110px;">
                                                <button type="submit" name="qty" value="<?= (int)$item['quantity'] - 1 ?>"
                                                        class="btn btn-outline-dark rounded-0 border-2 fw-bold px-2"
                                                        aria-label="Уменьшить количество"
                                                        <?= ($item['quantity'] <= 1) ? 'disabled style="opacity: 0.3;"' : '' ?>>
                                                    <i class="bi bi-dash-lg"></i>
                                                </button>

                                                <input type="text" readonly
                                                       aria-label="Количество товара"
                                                       class="form-control rounded-0 border-2 border-dark text-center fw-black bg-white fs-6 p-0 street-font"
                                                       value="<?= (int)$item['quantity'] ?>">

                                                <button type="submit" name="qty" value="<?= (int)$item['quantity'] + 1 ?>"
                                                        class="btn btn-outline-dark rounded-0 border-2 fw-bold px-2"
                                                        aria-label="Увеличить количество">
                                                    <i class="bi bi-plus-lg"></i>
                                                </button>
                                            </div>
                                        </form>

                                        <div class="text-end">
                                            <div class="street-font fs-5 lh-1 text-nowrap">
                                                <?= number_format((float)$item['price'] * (int)$item['quantity'], 0, '', ' ') ?> ₽
                                            </div>
                                            <small class="text-muted fw-bold d-none d-sm-block mt-1" style="font-size: 0.7rem;">
                                                <?= number_format((float)$item['price'], 0, '', ' ') ?> ₽/шт
                                            </small>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- ИТОГ (САЙДБАР) -->
            <div class="col-lg-4">
                <div class="card rounded-0 p-4 sticky-top checkout-card" style="top: 100px;">
                    <h3 class="street-font mb-4 text-street-yellow border-bottom border-secondary pb-3">
                        <i class="bi bi-receipt me-2"></i>Чек
                    </h3>
                    
                    <div class="d-flex justify-content-between mb-2 fs-5 text-white">
                        <span class="text-white-50 fw-bold">Позиций:</span>
                        <span class="fw-bold street-font"><?= count($cartItems) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-4 fs-5 text-white">
                        <span class="text-white-50 fw-bold">Доставка:</span>
                        <span class="fw-bold text-street-yellow street-font">FREE</span>
                    </div>
                    
                    <div class="d-flex justify-content-between border-top border-secondary pt-3 mb-4 text-white">
                        <span class="h4 street-font">ВСЕГО:</span>
                        <span class="h3 street-font text-street-yellow m-0"><?= number_format($total, 0, '', ' ') ?> ₽</span>
                    </div>

                    <a href="/pages/checkout.php" class="btn btn-checkout w-100 rounded-0 py-3 fs-5 shadow-sm">
                        ОФОРМИТЬ <i class="bi bi-arrow-right-short ms-1 fw-bold"></i>
                    </a>
                    
                    <div class="mt-4 pt-3 border-top border-secondary text-center small text-white-50 fw-bold text-uppercase">
                        <i class="bi bi-shield-lock-fill text-street-yellow"></i> Безопасная оплата
                    </div>
                </div>
            </div>

        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
