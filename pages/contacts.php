<?php
// pages/contacts.php
session_start();
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

    /* === КАРТОЧКИ КОНТАКТОВ === */
    .contact-card {
        background: #fff;
        color: #000;
        border: 3px solid #000;
        box-shadow: 8px 8px 0 #000;
        transition: box-shadow 0.3s ease, transform 0.3s;
        height: 100%;
    }
    .contact-card:hover {
        box-shadow: 10px 10px 0 #bc13fe;
        transform: translateY(-5px);
    }
    
    /* Анимация иконки */
    .icon-box { transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
    .contact-card:hover .icon-box {
        transform: scale(1.2) rotate(-10deg);
        color: #bc13fe !important;
    }

    /* Соцсети */
    .social-btn {
        background: #000; color: #fff;
        border: 2px solid #000;
        transition: all 0.2s;
        font-family: 'Arial Black', sans-serif;
    }
    .social-btn:hover {
        background: #FCE300; color: #000;
        transform: translate(-3px, -3px);
        box-shadow: 5px 5px 0 #fff;
    }

    /* Карта */
    .map-container {
        background: #1a1a1a;
        border-right: 3px solid #000; /* Разделитель */
        height: 100%; min-height: 400px;
        position: relative; overflow: hidden;
        background-image: 
            linear-gradient(#222 2px, transparent 2px),
            linear-gradient(90deg, #222 2px, transparent 2px);
        background-size: 40px 40px;
    }
    /* Для мобильных убираем border-right */
    @media (max-width: 991px) { .map-container { border-right: none; border-bottom: 3px solid #000; } }

    .map-pin {
        position: absolute; top: 50%; left: 50%;
        transform: translate(-50%, -100%);
        font-size: 4rem; color: #FCE300;
        filter: drop-shadow(0 0 15px rgba(252, 227, 0, 0.6));
        animation: bouncePin 2s infinite;
    }
    @keyframes bouncePin {
        0%, 100% { transform: translate(-50%, -100%); }
        50% { transform: translate(-50%, -120%); }
    }

    /* Форма */
    .form-control {
        border: 3px solid #000;
        border-radius: 0;
        font-weight: bold;
        padding: 15px;
    }
    .form-control:focus {
        box-shadow: 5px 5px 0 #bc13fe;
        border-color: #000;
    }
    
    .btn-send {
        background: #FCE300; color: #000;
        border: 3px solid #000;
        box-shadow: 5px 5px 0 #000;
        transition: all 0.2s;
        font-family: 'Arial Black', sans-serif;
    }
    .btn-send:hover {
        background: #000; color: #FCE300; border-color: #FCE300;
        transform: translate(-3px, -3px);
        box-shadow: 8px 8px 0 #FCE300;
    }
</style>

<!-- ФОН -->
<div class="bg-main-anim"></div>

<div class="container py-5" style="position: relative; z-index: 2;">
    
    <div class="mb-5 border-bottom border-4 border-light pb-3 text-center text-md-start">
        <h1 class="display-3 street-font m-0 text-white" style="text-shadow: 4px 4px 0 #000;">
            СВЯЗЬ С <span class="text-street-yellow">БАЗОЙ</span>
        </h1>
        <p class="lead fw-bold text-white-50 text-uppercase letter-spacing-2 mt-2 street-font">
            UFFNV HEADQUARTERS
        </p>
    </div>

    <!-- ИНФО КАРТОЧКИ -->
    <div class="row g-4 mb-5">
        
        <!-- АДРЕС -->
        <div class="col-md-4">
            <div class="contact-card p-4 d-flex flex-column align-items-center text-center">
                <div class="icon-box mb-3 text-dark">
                    <i class="bi bi-geo-alt-fill display-3"></i>
                </div>
                <h4 class="street-font mb-3 border-bottom border-3 border-dark pb-2 w-100">Локация</h4>
                <p class="fs-5 fw-bold mb-1">Москва, Россия</p>
                <p class="text-muted fw-bold">ул. Уличная, д. 13 <br><span class="badge bg-black text-warning rounded-0 mt-1">Сектор Б</span></p>
                <div class="mt-auto pt-3">
                    <span class="badge bg-warning text-dark border border-dark rounded-0 fw-bold">М. Китай-Город</span>
                </div>
            </div>
        </div>

        <!-- КОНТАКТЫ -->
        <div class="col-md-4">
            <div class="contact-card p-4 d-flex flex-column align-items-center text-center">
                <div class="icon-box mb-3 text-dark">
                    <i class="bi bi-telephone-fill display-3"></i>
                </div>
                <h4 class="street-font mb-3 border-bottom border-3 border-dark pb-2 w-100">Связь</h4>
                <p class="fs-4 street-font mb-1 text-nowrap">+7 (999) 000-00-00</p>
                <p class="text-muted fw-bold mb-3">support@uffnv.ru</p>
                
                <div class="d-flex gap-2 mt-auto w-100">
                    <a href="#" class="social-btn p-2 rounded-0 text-decoration-none fw-bold flex-grow-1">TG</a>
                    <a href="#" class="social-btn p-2 rounded-0 text-decoration-none fw-bold flex-grow-1">VK</a>
                    <a href="#" class="social-btn p-2 rounded-0 text-decoration-none fw-bold flex-grow-1">WA</a>
                </div>
            </div>
        </div>

        <!-- РЕЖИМ РАБОТЫ -->
        <div class="col-md-4">
            <div class="contact-card p-4 d-flex flex-column align-items-center text-center">
                <div class="icon-box mb-3 text-dark">
                    <i class="bi bi-clock-fill display-3"></i>
                </div>
                <h4 class="street-font mb-3 border-bottom border-3 border-dark pb-2 w-100">Время</h4>
                <div class="w-100 text-start px-3">
                    <div class="d-flex justify-content-between border-bottom border-dark mb-2 pb-1">
                        <span class="fw-bold text-muted">Магазин:</span>
                        <span class="fw-black street-font">10:00 - 22:00</span>
                    </div>
                    <div class="d-flex justify-content-between border-bottom border-dark mb-2 pb-1">
                        <span class="fw-bold text-muted">Выходные:</span>
                        <span class="fw-black street-font">11:00 - 23:00</span>
                    </div>
                    <div class="d-flex justify-content-between mt-3">
                        <span class="fw-bold text-muted">Онлайн:</span>
                        <span class="badge bg-success rounded-0 border border-dark">24/7</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- КАРТА И ФОРМА -->
    <div class="row g-0 border-3 border-white shadow-lg" style="box-shadow: 10px 10px 0 #000;">
        
        <!-- КАРТА -->
        <div class="col-lg-6">
            <div class="map-container d-flex align-items-center justify-content-center">
                <i class="bi bi-geo-alt-fill map-pin"></i>
                <div class="text-center position-relative z-2 bg-black text-white p-3 border-2 border-warning border" style="transform: rotate(-3deg); box-shadow: 5px 5px 0 rgba(252, 227, 0, 0.3);">
                    <h4 class="street-font m-0">Мы здесь</h4>
                    <small class="text-warning fw-bold text-uppercase">Заходи в гости</small>
                </div>
            </div>
        </div>

        <!-- ФОРМА -->
        <div class="col-lg-6">
            <div class="bg-white p-5 h-100 d-flex flex-column justify-content-center border-3 border-dark border-start-0 mobile-border-fix">
                <h3 class="street-font mb-4 text-dark text-uppercase border-bottom border-4 border-dark pb-2 d-inline-block">Написать в штаб</h3>
                
                <form>
                    <div class="mb-3">
                        <label class="fw-black small text-uppercase mb-1 street-font text-dark">Твое имя</label>
                        <input type="text" class="form-control form-control-lg" placeholder="Как обращаться?">
                    </div>
                    <div class="mb-3">
                        <label class="fw-black small text-uppercase mb-1 street-font text-dark">Email / Telegram</label>
                        <input type="text" class="form-control form-control-lg" placeholder="Куда отвечать?">
                    </div>
                    <div class="mb-4">
                        <label class="fw-black small text-uppercase mb-1 street-font text-dark">Сообщение</label>
                        <textarea class="form-control" rows="3" placeholder="Твой вопрос..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-send w-100 py-3 text-uppercase fs-5 shadow-sm">
                        ОТПРАВИТЬ <i class="bi bi-send-fill ms-2"></i>
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
