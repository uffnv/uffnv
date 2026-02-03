<?php
// pages/order_success.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Получаем инфо о заказе
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    echo "<script>window.location='/pages/catalog.php';</script>";
    exit;
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

    /* === SUCCESS CARD === */
    .success-card {
        background: #fff;
        color: #000;
        border: 4px solid #000;
        box-shadow: 15px 15px 0 #bc13fe; /* Фиолетовая тень победы */
        position: relative;
    }

    /* Иконка галочки с пульсацией */
    .icon-wrapper {
        width: 120px; height: 120px;
        background: #000;
        color: #FCE300;
        border: 4px solid #000;
        display: flex; align-items: center; justify-content: center;
        border-radius: 50%;
        margin: 0 auto 30px;
        box-shadow: 0 0 0 0 rgba(188, 19, 254, 0.7); /* Фиолетовая пульсация */
        animation: pulse-purple 2s infinite;
    }
    @keyframes pulse-purple {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(188, 19, 254, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 20px rgba(188, 19, 254, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(188, 19, 254, 0); }
    }

    /* "Билет" с деталями */
    .ticket-details {
        background: #f8f9fa;
        border: 3px dashed #000;
        position: relative;
    }
    /* Эффект выреза по бокам */
    .ticket-details::before, .ticket-details::after {
        content: ""; position: absolute; top: 50%; width: 24px; height: 24px;
        background: #fff; border-radius: 50%; transform: translateY(-50%);
        border: 3px solid #000;
    }
    .ticket-details::before { left: -15px; border-right-color: transparent; border-bottom-color: transparent; transform: translateY(-50%) rotate(45deg); }
    .ticket-details::after { right: -15px; border-left-color: transparent; border-top-color: transparent; transform: translateY(-50%) rotate(45deg); }

    /* Кнопки */
    .btn-black { 
        background: #000; color: #FCE300; border: 3px solid #000; 
        transition: all 0.2s; font-family: 'Arial Black', sans-serif;
    }
    .btn-black:hover { 
        background: #FCE300; color: #000; box-shadow: 6px 6px 0 #bc13fe; transform: translate(-3px, -3px); 
    }
    
    .btn-outline-street {
        background: transparent; color: #000; border: 3px solid #000;
        transition: all 0.2s; font-family: 'Arial Black', sans-serif;
    }
    .btn-outline-street:hover {
        background: #000; color: #fff; transform: translate(-3px, -3px);
        box-shadow: 6px 6px 0 #bc13fe;
    }
</style>

<!-- ФОН -->
<div class="bg-main-anim"></div>

<div class="container py-5 text-center" style="position: relative; z-index: 2; min-height: 80vh; display: flex; align-items: center;">
    
    <div class="row justify-content-center w-100">
        <div class="col-lg-8">
            
            <!-- Карточка успеха -->
            <div class="card rounded-0 p-5 overflow-hidden success-card">
                
                <!-- Декоративный паттерн внутри -->
                <div class="position-absolute top-0 start-0 w-100 h-100" 
                     style="background: repeating-linear-gradient(45deg, #fff, #fff 10px, #f4f4f4 10px, #f4f4f4 20px); z-index: 0; opacity: 0.5;"></div>
                
                <div class="position-relative" style="z-index: 1;">
                    
                    <!-- Иконка -->
                    <div class="icon-wrapper">
                        <i class="bi bi-check-lg display-3"></i>
                    </div>

                    <!-- Заголовок -->
                    <h1 class="display-4 street-font mb-3 text-dark">
                        ЗАКАЗ <span class="text-street-yellow" style="text-shadow: 2px 2px 0 #000; text-decoration: underline;">#<?= $orderId ?></span> ПРИНЯТ
                    </h1>
                    
                    <p class="lead fw-bold text-muted mb-5 text-uppercase street-font" style="letter-spacing: 1px;">
                        Спасибо, бро! Мы уже собираем твой стаф.
                    </p>

                    <!-- Детали (Тикет) -->
                    <div class="ticket-details p-4 mb-5 mx-auto text-start" style="max-width: 500px;">
                        <div class="d-flex justify-content-between mb-2 border-bottom border-dark pb-2">
                            <span class="text-uppercase fw-bold text-muted small street-font">К оплате:</span>
                            <span class="street-font fs-4 text-dark"><?= number_format($order['total_amount'], 0, '', ' ') ?> ₽</span>
                        </div>
                        <div class="d-flex justify-content-between pt-2">
                            <span class="text-uppercase fw-bold text-muted small street-font">Телефон для связи:</span>
                            <span class="fw-bold fs-5 text-dark"><?= htmlspecialchars($order['phone']) ?></span>
                        </div>
                        
                        <div class="mt-4 pt-3 border-top border-dark text-center small fw-bold text-muted text-uppercase">
                            <i class="bi bi-info-circle-fill me-1 text-dark"></i> Наш менеджер свяжется с тобой в ближайшее время.
                        </div>
                    </div>

                    <!-- КНОПКИ ДЕЙСТВИЙ -->
                    <div class="d-flex flex-column flex-md-row justify-content-center gap-3">
                        
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <a href="/pages/profile.php" class="btn btn-black rounded-0 text-uppercase py-3 px-5 fs-5">
                                <i class="bi bi-person-fill me-2"></i> В ПРОФИЛЬ
                            </a>
                        <?php else: ?>
                            <a href="/pages/register.php" class="btn btn-black rounded-0 text-uppercase py-3 px-5 fs-5">
                                <i class="bi bi-person-plus-fill me-2"></i> СОЗДАТЬ АККАУНТ
                            </a>
                        <?php endif; ?>

                        <a href="/pages/catalog.php" class="btn btn-outline-street rounded-0 text-uppercase py-3 px-5 fs-5">
                            В МАГАЗИН
                        </a>
                    </div>

                </div>
            </div>

        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
