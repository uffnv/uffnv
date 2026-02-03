<?php
// pages/delivery.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';
?>

<style>
    /* === ШРИФТЫ И ОБЩИЙ СТИЛЬ === */
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

    /* === КАРТОЧКИ === */
    .info-card {
        background: #fff;
        color: #000;
        border: 3px solid #000;
        box-shadow: 8px 8px 0 #000;
        transition: box-shadow 0.3s ease, transform 0.3s ease;
        height: 100%;
    }
    .info-card:hover {
        box-shadow: 10px 10px 0 #bc13fe;
        transform: translateY(-5px);
    }
    
    .info-card-header {
        background: #000;
        color: #fff;
        border-bottom: 3px solid #000;
    }

    /* Анимация иконки */
    .card-icon { transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
    .info-card:hover .card-icon { transform: scale(1.3) rotate(10deg); color: #FCE300 !important; }

    /* Блок "Процесс" */
    .process-step {
        background: #fff;
        color: #000;
        border: 2px solid #000;
        transition: all 0.2s;
        position: relative;
        z-index: 1;
    }
    .process-step:hover {
        background: #FCE300;
        transform: scale(1.05);
        z-index: 2;
        box-shadow: 0 0 20px rgba(252, 227, 0, 0.4);
        border-color: #000;
    }
    
    /* Блок "Оплата" */
    .payment-card {
        background: #000;
        color: #fff;
        border: 3px solid #FCE300;
        box-shadow: 10px 10px 0 #bc13fe;
        transition: transform 0.3s;
    }
    .payment-card:hover { transform: translateY(-3px); }
    
    /* Блок "Возврат" */
    .return-card {
        background: #fff;
        color: #000;
        border: 3px solid #000;
        box-shadow: 10px 10px 0 #000;
        transition: transform 0.3s;
    }
    .return-card:hover { transform: translateY(-3px); }

    /* Кнопка в возврате */
    .btn-outline-dark {
        border: 3px solid #000;
        font-family: 'Arial Black', sans-serif;
        color: #000;
    }
    .btn-outline-dark:hover {
        background: #000; color: #FCE300;
        box-shadow: 5px 5px 0 #bc13fe;
    }
</style>

<!-- ФОН -->
<div class="bg-main-anim"></div>

<div class="container py-5" style="position: relative; z-index: 2;">
    
    <!-- ЗАГОЛОВОК -->
    <div class="mb-5 border-bottom border-4 border-light pb-3">
        <h1 class="display-4 street-font m-0 text-white" style="text-shadow: 4px 4px 0 #000;">
            ДОСТАВКА <span class="text-street-yellow">&</span> ОПЛАТА
        </h1>
        <p class="lead fw-bold text-white-50 text-uppercase mt-2 street-font" style="letter-spacing: 2px;">
            Отправляем стаф по всему миру. Быстро. Чётко.
        </p>
    </div>

    <!-- 1. СПОСОБЫ ДОСТАВКИ -->
    <div class="row g-4 mb-5">
        
        <!-- Курьер -->
        <div class="col-md-4">
            <div class="info-card d-flex flex-column">
                <div class="info-card-header rounded-0 py-3 px-4">
                    <h4 class="street-font m-0">
                        <i class="bi bi-bicycle text-warning me-2 card-icon d-inline-block"></i> По Москве
                    </h4>
                </div>
                <div class="card-body p-4 d-flex flex-column flex-grow-1">
                    <p class="fw-bold fs-5">Курьерская доставка до двери.</p>
                    <ul class="list-unstyled fw-bold text-muted mb-4">
                        <li class="mb-2"><i class="bi bi-check-lg text-dark me-2"></i> Примерка перед покупкой</li>
                        <li class="mb-2"><i class="bi bi-check-lg text-dark me-2"></i> Оплата при получении</li>
                        <li class="mb-2"><i class="bi bi-clock text-dark me-2"></i> 1-2 дня</li>
                    </ul>
                    <div class="mt-auto pt-3 border-top border-2 border-dark">
                        <span class="street-font fs-3">500 ₽</span>
                        <span class="d-block small fw-bold text-muted text-uppercase">Бесплатно от 15 000 ₽</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- СДЭК -->
        <div class="col-md-4">
            <div class="info-card d-flex flex-column">
                <div class="info-card-header bg-warning text-dark rounded-0 py-3 px-4 border-bottom border-3 border-dark">
                    <h4 class="street-font m-0">
                        <i class="bi bi-box-seam-fill me-2 card-icon d-inline-block"></i> По РФ и СНГ
                    </h4>
                </div>
                <div class="card-body p-4 d-flex flex-column flex-grow-1">
                    <p class="fw-bold fs-5">СДЭК или Почта России.</p>
                    <ul class="list-unstyled fw-bold text-muted mb-4">
                        <li class="mb-2"><i class="bi bi-check-lg text-dark me-2"></i> Трек-номер для отслеживания</li>
                        <li class="mb-2"><i class="bi bi-check-lg text-dark me-2"></i> До пункта выдачи или двери</li>
                        <li class="mb-2"><i class="bi bi-clock text-dark me-2"></i> 3-7 дней</li>
                    </ul>
                    <div class="mt-auto pt-3 border-top border-2 border-dark">
                        <span class="street-font fs-3">от 350 ₽</span>
                        <span class="d-block small fw-bold text-muted text-uppercase">Рассчитывается в корзине</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Самовывоз -->
        <div class="col-md-4">
            <div class="info-card d-flex flex-column">
                <div class="info-card-header rounded-0 py-3 px-4">
                    <h4 class="street-font m-0">
                        <i class="bi bi-geo-alt-fill text-warning me-2 card-icon d-inline-block"></i> Самовывоз
                    </h4>
                </div>
                <div class="card-body p-4 d-flex flex-column flex-grow-1">
                    <p class="fw-bold fs-5">Забери сам из нашего шопа.</p>
                    <ul class="list-unstyled fw-bold text-muted mb-4">
                        <li class="mb-2"><i class="bi bi-check-lg text-dark me-2"></i> Г. Москва, ул. Уличная 13</li>
                        <li class="mb-2"><i class="bi bi-check-lg text-dark me-2"></i> Ежедневно с 11:00 до 21:00</li>
                        <li class="mb-2"><i class="bi bi-cup-hot text-dark me-2"></i> Угостим кофе</li>
                    </ul>
                    <div class="mt-auto pt-3 border-top border-2 border-dark">
                        <span class="street-font fs-3 text-success">Бесплатно</span>
                        <span class="d-block small fw-bold text-muted text-uppercase">Бронь держится 3 дня</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. ПРОЦЕСС -->
    <div class="mb-5">
        <h2 class="street-font mb-4 text-white">Процесс</h2>
        <div class="row g-0">
            
            <div class="col-md-3">
                <div class="process-step h-100 p-4 text-center border-end-md">
                    <i class="bi bi-cursor-fill display-4 mb-3 d-block text-warning" style="text-shadow: 2px 2px 0 #000;"></i>
                    <h5 class="street-font">1. Оформление</h5>
                    <p class="small fw-bold text-muted mb-0">Ты кидаешь заказ на сайте.</p>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="process-step h-100 p-4 text-center border-end-md">
                    <i class="bi bi-telephone-inbound-fill display-4 mb-3 d-block text-dark"></i>
                    <h5 class="street-font">2. Подтверждение</h5>
                    <p class="small fw-bold text-muted mb-0">Менеджер звонит уточнить детали.</p>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="process-step h-100 p-4 text-center border-end-md">
                    <i class="bi bi-box-fill display-4 mb-3 d-block text-warning" style="text-shadow: 2px 2px 0 #000;"></i>
                    <h5 class="street-font">3. Упаковка</h5>
                    <p class="small fw-bold text-muted mb-0">Пакуем, клеим стикеры, отправляем.</p>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="process-step h-100 p-4 text-center">
                    <i class="bi bi-emoji-sunglasses-fill display-4 mb-3 d-block text-dark"></i>
                    <h5 class="street-font">4. Флекс</h5>
                    <p class="small fw-bold text-muted mb-0">Ты получаешь стаф и кайфуешь.</p>
                </div>
            </div>

        </div>
    </div>

    <div class="row g-5">
        
        <!-- 3. ОПЛАТА -->
        <div class="col-lg-6">
            <div class="p-5 payment-card h-100 position-relative">
                <h3 class="street-font text-street-yellow mb-4 border-bottom border-light pb-2">
                    <i class="bi bi-credit-card-fill me-2"></i> Оплата
                </h3>
                
                <div class="d-flex align-items-center mb-4">
                    <i class="bi bi-check-square-fill text-success fs-3 me-3"></i>
                    <div>
                        <h5 class="street-font m-0 text-white">Банковской картой</h5>
                        <small class="text-white-50 fw-bold">Visa, MasterCard, MIR. Без комиссии.</small>
                    </div>
                </div>

                <div class="d-flex align-items-center mb-4">
                    <i class="bi bi-check-square-fill text-success fs-3 me-3"></i>
                    <div>
                        <h5 class="street-font m-0 text-white">При получении</h5>
                        <small class="text-white-50 fw-bold">Наличными или картой курьеру / в пункте выдачи.</small>
                    </div>
                </div>

                <div class="d-flex align-items-center">
                    <i class="bi bi-check-square-fill text-success fs-3 me-3"></i>
                    <div>
                        <h5 class="street-font m-0 text-white">Долями / Сплит</h5>
                        <small class="text-white-50 fw-bold">Разбей сумму на 4 платежа.</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. ВОЗВРАТ -->
        <div class="col-lg-6">
            <div class="p-5 return-card h-100 position-relative">
                <h3 class="street-font text-danger mb-4 border-bottom border-dark pb-2">
                    <i class="bi bi-arrow-repeat me-2"></i> Возврат
                </h3>
                
                <p class="fw-bold fs-5 mb-3">
                    Не подошел размер? Не вопрос.
                </p>
                <p class="fw-bold text-muted">
                    У тебя есть <span class="bg-black text-white px-1 street-font">14 дней</span> с момента получения заказа, чтобы вернуть или обменять товар.
                </p>

                <div class="alert alert-warning border-2 border-dark rounded-0 fw-bold d-flex align-items-center mt-4 text-dark shadow-sm">
                    <i class="bi bi-exclamation-triangle-fill fs-3 me-3"></i>
                    <div>
                        Главное — сохрани бирки, упаковку и товарный вид. Не стирай и не носи вещь на тусовки перед возвратом.
                    </div>
                </div>

                <a href="/pages/contacts.php" class="btn btn-outline-dark rounded-0 w-100 mt-2 py-3 fs-5 shadow-sm">
                    Связаться с поддержкой
                </a>
            </div>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
