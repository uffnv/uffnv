<?php
// pages/my_orders.php
require_once __DIR__ . '/../includes/functions.php';
initAppSession();
require_once __DIR__ . '/../config/db.php';

checkAuth('/pages/login.php');

// 1. Получаем заказы
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([(int)$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

// 2. Получаем ТОВАРЫ для этих заказов (LEFT JOIN на случай удаления товара)
$orderItems = [];
if (!empty($orders)) {
    $orderIds = array_column($orders, 'id');
    $inQuery = implode(',', array_fill(0, count($orderIds), '?'));

    $sqlItems = "SELECT oi.order_id, oi.product_id, oi.quantity, oi.price,
                        COALESCE(p.title, 'Удаленный товар') AS title, p.image
                 FROM order_items oi
                 LEFT JOIN products p ON oi.product_id = p.id
                 WHERE oi.order_id IN ($inQuery)";

    $stmtItems = $pdo->prepare($sqlItems);
    $stmtItems->execute($orderIds);
    $allItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

    foreach ($allItems as $item) {
        $item['image'] = safeUrl($item['image'] ?? '', '');
        $orderItems[$item['order_id']][] = $item;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<style>
    /* === ОБЩИЙ СТИЛЬ === */
    body { font-family: 'Arial', sans-serif; background: #120024; color: #fff; }

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
                    $amount = (float)($ord['total_amount'] ?? 0);

                    // Статусы
                    $stClass = 'bg-secondary text-white';
                    $stLabel = $ord['status'];
                    if ($ord['status'] === 'new') { $stClass = 'bg-warning text-dark'; $stLabel = 'В обработке'; }
                    if ($ord['status'] === 'processing') { $stClass = 'bg-info text-dark'; $stLabel = 'Собирается'; }
                    if ($ord['status'] === 'shipped') { $stClass = 'bg-primary text-white'; $stLabel = 'В пути'; }
                    if ($ord['status'] === 'completed') { $stClass = 'bg-success text-white'; $stLabel = 'Выполнен'; }
                    if ($ord['status'] === 'cancelled') { $stClass = 'bg-danger text-white'; $stLabel = 'Отменен'; }

                    $currentItems = $orderItems[$ord['id']] ?? [];
                    $jsonItems = e(json_encode($currentItems, JSON_UNESCAPED_UNICODE));
                ?>

                <div class="col-12">
                    <div class="card rounded-0 order-card">
                        <!-- Шапка -->
                        <div class="card-header bg-black text-white d-flex justify-content-between align-items-center py-3 border-bottom border-3 border-dark">
                            <div>
                                <span class="street-font text-street-yellow fs-5 me-2">ЗАКАЗ #<?= (int)$ord['id'] ?></span>
                                <span class="small text-white-50 fw-bold ms-2 d-none d-sm-inline">
                                    от <?= e(date('d.m.Y', strtotime((string)$ord['created_at']))) ?>
                                </span>
                            </div>
                            <span class="badge <?= e($stClass) ?> rounded-0 border border-white text-uppercase fw-bold px-3 py-2">
                                <?= e($stLabel) ?>
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
                                            <p class="mb-0 fw-bold small text-muted lh-sm"><?= e($ord['address']) ?></p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Сумма -->
                                <div class="col-md-3">
                                    <div class="street-font fs-4 text-nowrap text-dark"><?= number_format($amount, 0, '', ' ') ?> ₽</div>
                                    <div class="small fw-bold text-muted text-uppercase"><?= e($ord['payment_method']) ?></div>
                                </div>

                                <!-- Кнопка "Состав заказа" -->
                                <div class="col-md-4 text-md-end">
                                    <button type="button" class="btn btn-outline-order rounded-0 w-100 py-2 btn-view-items"
                                            data-id="<?= (int)$ord['id'] ?>"
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
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>

            <div class="modal-body p-0 bg-white">
                <div id="modalItemsList" class="list-group list-group-flush"></div>
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
            const orderId = this.getAttribute('data-id') || '';
            let items = [];
            try {
                items = JSON.parse(this.getAttribute('data-items') || '[]');
            } catch (e) {
                items = [];
            }

            modalTitleId.textContent = '#' + orderId;
            modalList.replaceChildren();

            if (!Array.isArray(items) || items.length === 0) {
                const empty = document.createElement('div');
                empty.className = 'p-5 text-center text-muted';
                empty.innerHTML = '<i class="bi bi-info-circle fs-1 mb-2 d-block"></i>';
                const msg = document.createElement('span');
                msg.className = 'fw-bold';
                msg.textContent = 'Список товаров не сохранен для этого заказа.';
                empty.appendChild(msg);
                modalList.appendChild(empty);
            } else {
                items.forEach(item => {
                    const priceVal = Number(item.price || 0);
                    const qtyVal = Number(item.quantity || 1);
                    const price = new Intl.NumberFormat('ru-RU').format(priceVal);
                    const sum = new Intl.NumberFormat('ru-RU').format(priceVal * qtyVal);

                    const row = document.createElement('div');
                    row.className = 'list-group-item p-4 border-bottom d-flex align-items-center bg-white hover-bg-gray';

                    const thumbBox = document.createElement('div');
                    thumbBox.className = 'me-4 border-2 border border-dark position-relative flex-shrink-0';
                    thumbBox.style.width = '80px';
                    thumbBox.style.height = '80px';
                    if (item.image) {
                        const img = document.createElement('img');
                        img.src = String(item.image);
                        img.alt = String(item.title || '');
                        img.className = 'w-100 h-100 object-fit-cover';
                        thumbBox.appendChild(img);
                    }

                    const info = document.createElement('div');
                    info.className = 'flex-grow-1';
                    const titleEl = document.createElement(item.product_id ? 'a' : 'span');
                    if (item.product_id) {
                        titleEl.href = '/pages/product.php?id=' + encodeURIComponent(item.product_id);
                        titleEl.target = '_blank';
                        titleEl.rel = 'noopener noreferrer';
                    }
                    titleEl.className = 'fs-5 fw-bold text-dark text-decoration-none text-uppercase link-hover-yellow street-font';
                    titleEl.textContent = String(item.title || 'Товар');
                    info.appendChild(titleEl);

                    const meta = document.createElement('div');
                    meta.className = 'text-muted fw-bold mt-1';
                    meta.textContent = `${qtyVal} шт. x ${price} ₽`;
                    info.appendChild(meta);

                    const totalBox = document.createElement('div');
                    totalBox.className = 'street-font fs-4 ms-3 text-dark text-nowrap';
                    totalBox.textContent = `${sum} ₽`;

                    row.appendChild(thumbBox);
                    row.appendChild(info);
                    row.appendChild(totalBox);
                    modalList.appendChild(row);
                });
            }

            modal.show();
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
