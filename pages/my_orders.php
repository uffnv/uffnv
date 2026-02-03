<?php
// pages/my_orders.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='/pages/login.php';</script>";
    exit;
}

// 1. Получаем заказы
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

// 2. Получаем ТОВАРЫ для этих заказов
$orderItems = [];
if (!empty($orders)) {
    $orderIds = array_column($orders, 'id');
    $inQuery = implode(',', array_fill(0, count($orderIds), '?'));
    
    $sqlItems = "SELECT oi.*, p.title, p.image, p.id as product_id 
                 FROM order_items oi 
                 JOIN products p ON oi.product_id = p.id 
                 WHERE oi.order_id IN ($inQuery)";
    
    $stmtItems = $pdo->prepare($sqlItems);
    $stmtItems->execute($orderIds);
    $allItems = $stmtItems->fetchAll();

    foreach ($allItems as $item) {
        $orderItems[$item['order_id']][] = $item;
    }
}
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

    /* === КАРТОЧКА ЗАКАЗА === */
    .order-card {
        background: #fff;
        color: #000;
        border: 3px solid #000;
        box-shadow: 8px 8px 0 #000;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .order-card:hover {
        transform: translateY(-5px);
        box-shadow: 10px 10px 0 #bc13fe;
    }
    
    .btn-black { 
        background: #000; 
        color: #FCE300; 
        border: 3px solid #000;
        transition: all 0.2s;
        font-family: 'Arial Black', sans-serif;
    }
    .btn-black:hover { 
        background: #FCE300; 
        color: #000; 
        box-shadow: 5px 5px 0 #fff;
        border-color: #000;
        transform: translate(-2px, -2px);
    }

    .btn-outline-order {
        background: transparent;
        color: #000;
        border: 3px solid #000;
        font-family: 'Arial Black', sans-serif;
        text-transform: uppercase;
        transition: all 0.2s;
    }
    .btn-outline-order:hover {
        background: #000;
        color: #fff;
        box-shadow: 5px 5px 0 #bc13fe;
        transform: translateY(-2px);
    }
    
    /* Модалка */
    .modal-content {
        border: 4px solid #000;
        box-shadow: 0 0 50px rgba(0,0,0,0.8);
    }
    .hover-bg-gray:hover { background-color: #f0f0f0 !important; }
    
    .link-hover-yellow:hover { color: #d8b100 !important; }
    /* Хак для кликабельности всей строки */
    .stretched-link::after { position: absolute; top: 0; right: 0; bottom: 0; left: 0; z-index: 1; content: ""; }
    .list-group-item { position: relative; }
    .object-fit-cover { object-fit: cover; }
</style>

<!-- ФОН -->
<div class="bg-main-anim"></div>

<div class="container py-5" style="position: relative; z-index: 2;">
    
    <!-- Заголовок -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-5 border-bottom border-4 border-light pb-3">
        <h1 class="display-4 street-font m-0 text-white" style="text-shadow: 4px 4px 0 #000;">
            МОИ <span class="text-street-yellow">ЗАКАЗЫ</span>
        </h1>
        <a href="/pages/profile.php" class="btn btn-outline-light rounded-0 fw-bold text-uppercase border-2 mt-3 mt-md-0 street-font">
            <i class="bi bi-arrow-left me-2"></i> В профиль
        </a>
    </div>

    <?php if (empty($orders)): ?>
        <div class="alert alert-light border-4 border-dark rounded-0 text-center py-5 shadow-lg">
            <i class="bi bi-box-seam display-1 text-muted mb-3 d-block"></i>
            <h3 class="street-font text-dark">История пуста</h3>
            <a href="/pages/catalog.php" class="btn btn-black rounded-0 fw-black text-uppercase px-5 py-3 mt-3 fs-5">
                Перейти в каталог
            </a>
        </div>
    <?php else: ?>
        
        <div class="row g-4">
            <?php foreach ($orders as $ord): ?>
                <?php 
                    $amount = isset($ord['total_amount']) ? $ord['total_amount'] : ($ord['total_price'] ?? 0);
                    
                    // Статусы
                    $stClass = 'bg-secondary text-white';
                    $stLabel = $ord['status'];
                    if($ord['status'] == 'new') { $stClass = 'bg-warning text-dark'; $stLabel = 'В обработке'; }
                    if($ord['status'] == 'completed') { $stClass = 'bg-success text-white'; $stLabel = 'Выполнен'; }
                    if($ord['status'] == 'cancelled') { $stClass = 'bg-danger text-white'; $stLabel = 'Отменен'; }

                    // Товары этого заказа (для JS)
                    $currentItems = isset($orderItems[$ord['id']]) ? $orderItems[$ord['id']] : [];
                    $jsonItems = htmlspecialchars(json_encode($currentItems), ENT_QUOTES, 'UTF-8');
                ?>

                <div class="col-12">
                    <div class="card rounded-0 order-card">
                        <!-- Шапка -->
                        <div class="card-header bg-black text-white d-flex justify-content-between align-items-center py-3 border-bottom border-3 border-dark">
                            <div>
                                <span class="street-font text-street-yellow fs-5 me-2">ЗАКАЗ #<?= $ord['id'] ?></span>
                                <span class="small text-white-50 fw-bold ms-2 d-none d-sm-inline">
                                    от <?= date('d.m.Y', strtotime($ord['created_at'])) ?>
                                </span>
                            </div>
                            <span class="badge <?= $stClass ?> rounded-0 border border-white text-uppercase fw-bold px-3 py-2">
                                <?= $stLabel ?>
                            </span>
                        </div>

                        <div class="card-body p-4 bg-white">
                            <div class="row g-4 align-items-center">
                                
                                <!-- Адрес -->
                                <div class="col-md-5">
                                    <div class="d-flex align-items-start">
                                        <i class="bi bi-geo-alt-fill fs-3 me-3 text-secondary"></i>
                                        <div>
                                            <h6 class="street-font mb-1 text-dark small">Доставка</h6>
                                            <p class="mb-0 fw-bold small text-muted lh-sm"><?= htmlspecialchars($ord['address']) ?></p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Сумма -->
                                <div class="col-md-3">
                                    <div class="street-font fs-4 text-nowrap text-dark"><?= number_format($amount, 0, '', ' ') ?> ₽</div>
                                    <div class="small fw-bold text-muted text-uppercase"><?= $ord['payment_method'] ?></div>
                                </div>

                                <!-- Кнопка "Состав заказа" -->
                                <div class="col-md-4 text-md-end">
                                    <button class="btn btn-outline-order rounded-0 w-100 py-2 btn-view-items"
                                            data-id="<?= $ord['id'] ?>"
                                            data-items="<?= $jsonItems ?>">
                                        <i class="bi bi-eye me-2"></i> Что в заказе?
                                    </button>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>

</div>

<!-- МОДАЛЬНОЕ ОКНО -->
<div class="modal fade" id="orderItemsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content rounded-0">
            
            <div class="modal-header bg-black text-white border-bottom-0 py-3 border-bottom border-3 border-dark">
                <h5 class="modal-title street-font text-uppercase">
                    Состав заказа <span class="text-street-yellow" id="modalOrderId">#0</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-0 bg-white">
                <div id="modalItemsList" class="list-group list-group-flush">
                    <!-- JS Content -->
                </div>
            </div>

            <div class="modal-footer bg-light border-top-2 border-dark justify-content-center">
                <button type="button" class="btn btn-black rounded-0 fw-black text-uppercase px-4" data-bs-dismiss="modal">
                    Закрыть
                </button>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modalEl = document.getElementById('orderItemsModal');
    const modal = new bootstrap.Modal(modalEl);
    const modalTitleId = document.getElementById('modalOrderId');
    const modalList = document.getElementById('modalItemsList');

    document.querySelectorAll('.btn-view-items').forEach(btn => {
        btn.addEventListener('click', function() {
            const orderId = this.getAttribute('data-id');
            const items = JSON.parse(this.getAttribute('data-items'));

            modalTitleId.textContent = '#' + orderId;
            modalList.innerHTML = '';

            if (items.length === 0) {
                modalList.innerHTML = `
                    <div class="p-5 text-center text-muted">
                        <i class="bi bi-info-circle fs-1 mb-2 d-block"></i>
                        <span class="fw-bold">Список товаров не сохранен для этого заказа.</span>
                    </div>`;
            } else {
                items.forEach(item => {
                    const price = new Intl.NumberFormat('ru-RU').format(item.price);
                    const sum = new Intl.NumberFormat('ru-RU').format(item.price * item.quantity);
                    
                    const html = `
                        <div class="list-group-item p-4 border-bottom d-flex align-items-center bg-white hover-bg-gray">
                            <!-- Фото -->
                            <div class="me-4 border-2 border border-dark position-relative" style="width: 80px; height: 80px;">
                                <img src="${item.image}" class="w-100 h-100 object-fit-cover">
                            </div>
                            
                            <!-- Инфо -->
                            <div class="flex-grow-1">
                                <a href="/pages/product.php?id=${item.product_id}" target="_blank" class="fs-5 fw-bold text-dark text-decoration-none text-uppercase link-hover-yellow stretched-link street-font">
                                    ${item.title} <i class="bi bi-box-arrow-up-right small text-muted ms-1"></i>
                                </a>
                                <div class="text-muted fw-bold mt-1">
                                    ${item.quantity} шт. х ${price} ₽
                                </div>
                            </div>
                            
                            <!-- Сумма -->
                            <div class="street-font fs-4 ms-3 text-dark">
                                ${sum} ₽
                            </div>
                        </div>
                    `;
                    modalList.insertAdjacentHTML('beforeend', html);
                });
            }

            modal.show();
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
