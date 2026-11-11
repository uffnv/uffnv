<?php
// pages/checkout.php
require_once __DIR__ . '/../includes/functions.php';
initAppSession();
require_once __DIR__ . '/../config/db.php';

$sessId = session_id();

// 1. Проверяем корзину до вывода header
$sql = "SELECT c.quantity, p.title, p.price, p.image
        FROM cart c
        JOIN products p ON c.product_id = p.id AND p.is_active = 1
        WHERE c.session_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$sessId]);
$cartItems = $stmt->fetchAll();

if (empty($cartItems)) {
    redirectWithFlash('/pages/catalog.php', 'Корзина пуста.', 'warning');
}

// Считаем итог
$total = 0;
foreach ($cartItems as $item) {
    $total += (float)$item['price'] * (int)$item['quantity'];
}

// 2. Получаем данные пользователя
$user = [];
if (isset($_SESSION['user_id'])) {
    $uStmt = $pdo->prepare("SELECT username, full_name, email, phone, city, address FROM users WHERE id = ?");
    $uStmt->execute([(int)$_SESSION['user_id']]);
    $user = $uStmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

$defaultName = !empty($user['full_name']) ? $user['full_name'] : ($user['username'] ?? '');

require_once __DIR__ . '/../includes/header.php';
?>

<style>
    /* === ОБЩИЙ СТИЛЬ === */
    body { font-family: 'Arial', sans-serif; background: #120024; color: #fff; }

    /* === СТИЛИ ФОРМЫ === */
    .text-street-yellow { color: #FCE300; text-shadow: 2px 2px 0 #000; }
    
    .checkout-card {
        background: #fff;
        border: 3px solid #000;
        box-shadow: 8px 8px 0 #000;
        color: #000;
        transition: box-shadow 0.3s;
    }
    .checkout-card:hover { box-shadow: 10px 10px 0 #bc13fe; }

    .checkout-header {
        background: #000;
        color: #FCE300;
        border-bottom: 3px solid #000;
        text-transform: uppercase;
        font-family: 'Arial Black', sans-serif;
    }
    
    /* Инпуты */
    .form-control {
        border: 2px solid #000;
        font-weight: bold;
        border-radius: 0;
        padding: 12px;
    }
    .form-control:focus {
        box-shadow: 5px 5px 0 #bc13fe;
        border-color: #000;
        background: #fff;
    }
    .form-label { font-family: 'Arial Black', sans-serif; color: #000 !important; }

    /* Радио-кнопки оплаты */
    .payment-option { cursor: pointer; display: block; width: 100%; }
    .card-box {
        background: #fff;
        border: 2px solid #000;
        transition: all 0.2s ease;
    }
    .peer:checked + .card-box {
        background-color: #FCE300 !important;
        border-color: #000 !important;
        box-shadow: 6px 6px 0 #000;
        transform: translate(-2px, -2px);
    }
    .peer:checked + .card-box .check-circle { border-color: #000 !important; background: #fff; }
    .peer:checked + .card-box .check-circle .dot { display: block !important; }
    
    .card-box:hover { border-color: #bc13fe; }

    /* Правая колонка (Чек) */
    .summary-card {
        background: rgba(18, 18, 18, 0.95);
        backdrop-filter: blur(10px);
        border: 3px solid #FCE300;
        box-shadow: 0 0 20px rgba(252, 227, 0, 0.15);
    }
    
    /* Скроллбар списка */
    .order-list::-webkit-scrollbar { width: 6px; }
    .order-list::-webkit-scrollbar-track { background: #222; }
    .order-list::-webkit-scrollbar-thumb { background: #555; }
    .order-list::-webkit-scrollbar-thumb:hover { background: #FCE300; }
    
    /* Кнопка подтверждения (ИНВЕРСИЯ HOVER) */
    .btn-confirm {
        background: #FCE300; 
        color: #000; 
        border: 3px solid #000;
        transition: all 0.2s;
        font-family: 'Arial Black', 'Impact', sans-serif;
    }
    .btn-confirm:hover {
        background: #000;
        color: #FCE300;
        border-color: #FCE300;
        transform: translate(-2px, -2px);
        box-shadow: 8px 8px 0 #FCE300;
    }
</style>

<!-- ФОН -->
<div class="bg-main-anim"></div>

<div class="container py-5" style="position: relative; z-index: 2;">
    
    <div class="mb-5 border-bottom border-4 border-light pb-3">
        <h1 class="display-4 street-font m-0 text-white" style="text-shadow: 4px 4px 0 #000;">
            ОФОРМЛЕНИЕ <span class="text-street-yellow">ЗАКАЗА</span>
        </h1>
    </div>

    <form action="/actions/place_order.php" method="POST" class="needs-validation" id="checkoutForm">
        <?= csrfField() ?>
        <div class="row g-5">

            <!-- ЛЕВАЯ КОЛОНКА: ФОРМА -->
            <div class="col-lg-7">

                <!-- 1. КОНТАКТЫ -->
                <div class="card rounded-0 checkout-card mb-5">
                    <div class="card-header checkout-header rounded-0 py-3">
                        <h4 class="m-0"><i class="bi bi-person-circle me-2"></i> Контакты</h4>
                    </div>
                    <div class="card-body p-4 bg-white">
                        <div class="row g-3">
                            <!-- ИМЯ -->
                            <div class="col-12">
                                <label for="checkoutName" class="form-label small">ФИО / Псевдоним</label>
                                <input type="text" id="checkoutName" name="name" class="form-control form-control-lg"
                                       value="<?= e($defaultName) ?>"
                                       required placeholder="Иван Иванов">
                            </div>

                            <!-- ТЕЛЕФОН -->
                            <div class="col-md-6">
                                <label for="phoneInput" class="form-label small">Телефон</label>
                                <input type="tel" name="phone" id="phoneInput" class="form-control form-control-lg"
                                       value="<?= e($user['phone'] ?? '') ?>"
                                       required placeholder="+7 (999) 000-00-00">
                            </div>

                            <!-- EMAIL -->
                            <div class="col-md-6">
                                <label for="checkoutEmail" class="form-label small">Email</label>
                                <input type="email" id="checkoutEmail" name="email" class="form-control form-control-lg"
                                       value="<?= e($user['email'] ?? '') ?>"
                                       required placeholder="mail@example.com">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. ДОСТАВКА -->
                <div class="card rounded-0 checkout-card mb-5">
                    <div class="card-header checkout-header rounded-0 py-3">
                        <h4 class="m-0"><i class="bi bi-truck me-2"></i> Доставка</h4>
                    </div>
                    <div class="card-body p-4 bg-white">
                        <div class="row g-3">
                            <!-- ГОРОД -->
                            <div class="col-12">
                                <label for="checkoutCity" class="form-label small">Город</label>
                                <input type="text" id="checkoutCity" name="city" class="form-control form-control-lg"
                                       value="<?= e($user['city'] ?? '') ?>"
                                       required placeholder="Москва">
                            </div>

                            <!-- АДРЕС -->
                            <div class="col-12">
                                <label for="checkoutAddress" class="form-label small">Адрес</label>
                                <input type="text" id="checkoutAddress" name="address" class="form-control form-control-lg"
                                       value="<?= e($user['address'] ?? '') ?>"
                                       required placeholder="Ул. Пушкина, д. 10, кв. 5">
                            </div>

                            <!-- КОММЕНТАРИЙ -->
                            <div class="col-12">
                                <label for="checkoutComment" class="form-label small">Комментарий</label>
                                <textarea id="checkoutComment" name="comment" class="form-control" rows="3" placeholder="Код домофона, этаж, пожелания..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. ОПЛАТА -->
                <div class="card rounded-0 checkout-card mb-5">
                    <div class="card-header checkout-header rounded-0 py-3">
                        <h4 class="m-0"><i class="bi bi-wallet2 me-2"></i> Оплата</h4>
                    </div>
                    <div class="card-body p-4 bg-white">
                        <div class="d-grid gap-3">

                            <label class="payment-option position-relative">
                                <input type="radio" name="payment_method" value="card_online" checked class="visually-hidden peer">
                                <div class="card-box p-3 d-flex align-items-center">
                                    <div class="check-circle me-3 rounded-circle border border-2 border-dark d-flex align-items-center justify-content-center bg-white" style="width: 24px; height: 24px;">
                                        <div class="dot bg-black rounded-circle" style="width: 12px; height: 12px; display: none;"></div>
                                    </div>
                                    <div>
                                        <div class="fw-black text-uppercase fs-5 text-dark street-font">Картой онлайн</div>
                                        <div class="small fw-bold text-muted">Visa, MC, Mir (Без комиссии)</div>
                                    </div>
                                    <i class="bi bi-credit-card-2-front ms-auto fs-2 text-dark"></i>
                                </div>
                            </label>

                            <label class="payment-option position-relative">
                                <input type="radio" name="payment_method" value="cash" class="visually-hidden peer">
                                <div class="card-box p-3 d-flex align-items-center">
                                    <div class="check-circle me-3 rounded-circle border border-2 border-dark d-flex align-items-center justify-content-center bg-white" style="width: 24px; height: 24px;">
                                        <div class="dot bg-black rounded-circle" style="width: 12px; height: 12px; display: none;"></div>
                                    </div>
                                    <div>
                                        <div class="fw-black text-uppercase fs-5 text-dark street-font">При получении</div>
                                        <div class="small fw-bold text-muted">Картой или наличными курьеру</div>
                                    </div>
                                    <i class="bi bi-cash-stack ms-auto fs-2 text-dark"></i>
                                </div>
                            </label>

                        </div>
                    </div>
                </div>

            </div>

            <!-- ПРАВАЯ КОЛОНКА: ИТОГ -->
            <div class="col-lg-5">
                <div class="card rounded-0 p-4 shadow-lg sticky-top summary-card" style="top: 100px;">
                    <h3 class="street-font mb-4 text-street-yellow border-bottom border-secondary pb-3">Ваш заказ</h3>

                    <!-- Список товаров -->
                    <div class="order-list mb-4 pe-2" style="max-height: 300px; overflow-y: auto;">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="d-flex align-items-center mb-3 pb-3 border-bottom border-secondary">
                                <img src="<?= e(safeUrl($item['image'] ?? '', '')) ?>" alt="<?= e($item['title']) ?>" class="rounded-0 border border-secondary me-3" width="60" height="60" style="object-fit: cover;">
                                <div class="flex-grow-1">
                                    <div class="street-font text-white small lh-sm mb-1">
                                        <?= e($item['title']) ?>
                                    </div>
                                    <div class="small text-white-50 fw-bold">
                                        <?= (int)$item['quantity'] ?> x <?= number_format((float)$item['price'], 0, '', ' ') ?> ₽
                                    </div>
                                </div>
                                <div class="street-font text-street-yellow">
                                    <?= number_format((float)$item['price'] * (int)$item['quantity'], 0, '', ' ') ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="d-flex justify-content-between mb-2 fs-5 text-white">
                        <span class="text-white-50 fw-bold">Товаров:</span>
                        <span class="fw-bold street-font"><?= number_format($total, 0, '', ' ') ?> ₽</span>
                    </div>
                    <div class="d-flex justify-content-between mb-4 fs-5 text-white">
                        <span class="text-white-50 fw-bold">Доставка:</span>
                        <span class="fw-bold text-street-yellow street-font">0 ₽</span>
                    </div>

                    <div class="d-flex justify-content-between border-top border-secondary pt-3 mb-4 text-white">
                        <span class="h4 street-font">ИТОГО:</span>
                        <span class="h3 street-font text-street-yellow m-0"><?= number_format($total, 0, '', ' ') ?> ₽</span>
                    </div>

                    <div class="mb-3 p-3 bg-dark border border-secondary text-start small">
                        <div class="form-check">
                            <input class="form-check-input rounded-0" type="checkbox" id="checkout_pd_consent" name="checkout_pd_consent" required style="accent-color: #FCE300;">
                            <label class="form-check-label text-white-50" for="checkout_pd_consent" style="font-size: 0.75rem;">
                                Я согласен на обработку персональных данных в соответствии с
                                <a href="/pages/privacy.php" target="_blank" class="text-street-yellow text-decoration-underline fw-bold">152-ФЗ</a>
                                и принимаю условия <a href="/pages/terms.php" target="_blank" class="text-street-yellow text-decoration-underline fw-bold">Публичной оферты</a>.
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-confirm w-100 rounded-0 text-uppercase py-4 fs-4 shadow-sm">
                        ПОДТВЕРДИТЬ ЗАКАЗ
                    </button>

                    <div class="mt-3 text-center small text-white-50 fw-bold lh-sm" style="font-size: 0.75rem;">
                        Оформляя заказ, вы подтверждаете согласие с <a href="/pages/terms.php" target="_blank" class="text-street-yellow">условиями продажи</a> и <a href="/pages/privacy.php" target="_blank" class="text-street-yellow">обработки данных</a> (152-ФЗ РФ).
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>

<!-- Маска телефона -->
<script src="https://unpkg.com/imask"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const phoneInput = document.getElementById('phoneInput');
        if (phoneInput && typeof IMask !== 'undefined') {
            IMask(phoneInput, {
                mask: '+{7} (000) 000-00-00'
            });
        }

        const form = document.getElementById('checkoutForm');
        if (form && phoneInput) {
            form.addEventListener('submit', function(e) {
                const digits = phoneInput.value.replace(/\D/g, '');
                if (digits.length < 11) {
                    e.preventDefault();
                    phoneInput.setCustomValidity('Введите полный номер телефона');
                    phoneInput.reportValidity();
                } else {
                    phoneInput.setCustomValidity('');
                }
            });
            phoneInput.addEventListener('input', function() {
                phoneInput.setCustomValidity('');
            });
        }
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
