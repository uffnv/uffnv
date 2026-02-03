<!-- includes/footer.php -->

<?php
if (!isset($curPage)) {
    $curPage = basename($_SERVER['PHP_SELF']);
}
?>

<!-- Отступ, чтобы контент не прилипал -->
<div style="margin-top: 100px;"></div>

<style>
    /* === ПЕРЕМЕННЫЕ (ДУБЛИРУЕМ ДЛЯ НАДЕЖНОСТИ) === */
    :root { --street-yellow: #FCE300; --street-dark: #121212; }

    /* === АНИМАЦИЯ ПОЯВЛЕНИЯ СНИЗУ === */
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .fade-in-up { animation: fadeInUp 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) forwards; }

    /* === ОСНОВНОЙ КОНТЕЙНЕР ФУТЕРА === */
    .street-footer {
        background-color: var(--street-dark);
        color: #fff;
        border-top: 3px solid var(--street-yellow);
        padding-top: 70px;
        padding-bottom: 50px;
        font-family: 'Arial', sans-serif;
        position: relative;
        z-index: 10;
        box-shadow: 0 -10px 50px rgba(0,0,0,0.8);
        overflow: hidden;
    }

    /* ЗАГОЛОВКИ-СТИКЕРЫ */
    .footer-header {
        background: var(--street-yellow); 
        color: #000;
        font-weight: 900; 
        text-transform: uppercase;
        padding: 6px 14px; 
        display: inline-block;
        font-size: 1.1rem; 
        margin-bottom: 25px;
        transform: skew(-10deg);
        box-shadow: 4px 4px 0 rgba(255, 255, 255, 0.15);
        border: 2px solid #000;
    }

    /* ЛОГОТИП В ФУТЕРЕ */
    .footer-logo-box {
        background-color: #000; 
        color: #fff;
        display: inline-block; 
        padding: 10px 20px;
        margin-bottom: 25px;
        transform: rotate(-2deg);
        border: 2px solid var(--street-yellow);
        box-shadow: 0 0 15px rgba(252, 227, 0, 0.15);
        transition: transform 0.3s;
        text-decoration: none;
    }
    .footer-logo-box:hover { transform: rotate(0deg) scale(1.05); box-shadow: 0 0 25px rgba(252, 227, 0, 0.4); }

    .footer-logo-text {
        font-family: 'Impact', sans-serif; font-size: 2.5rem;
        line-height: 0.8; letter-spacing: 1px;
        text-transform: uppercase; margin: 0;
    }
    .footer-logo-text span { color: var(--street-yellow); }

    /* ССЫЛКИ НАВИГАЦИИ */
    .footer-link {
        text-decoration: none; 
        color: #e0e0e0; /* Светло-серый для контраста */
        font-weight: 700; 
        font-size: 0.95rem;
        display: block; 
        margin-bottom: 14px;
        transition: all 0.2s ease;
        border-left: 3px solid transparent; 
        padding-left: 0;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    /* Ховер и Активное состояние */
    .footer-link:hover, .footer-link.active {
        color: #000 !important; 
        background-color: var(--street-yellow);
        border-left-color: #fff; 
        padding-left: 12px;
        transform: translateX(5px);
        box-shadow: 5px 5px 0 #000;
    }
    
    /* Спец-стиль для SALE */
    .footer-link.text-danger { color: #ff6b6b; }
    .footer-link.text-danger:hover, .footer-link.text-danger.active {
        background-color: #dc3545; color: #fff !important;
    }

    /* СОЦСЕТИ */
    .social-btn {
        width: 48px; height: 48px;
        background: #000; 
        border: 2px solid #fff; 
        color: #fff;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 1.3rem; text-decoration: none; margin-right: 12px;
        transition: all 0.2s;
    }
    .social-btn:hover {
        background: var(--street-yellow); 
        color: #000; 
        border-color: var(--street-yellow);
        transform: translateY(-4px);
        box-shadow: 0 5px 15px rgba(252, 227, 0, 0.3);
    }

    /* ТЕКСТЫ И КОНТАКТЫ */
    .footer-text-block {
        color: #ccc;
        font-weight: 600;
        font-size: 0.9rem;
    }
    .footer-label {
        display: block;
        font-weight: 900;
        font-size: 0.75rem;
        color: #888;
        text-transform: uppercase;
        margin-bottom: 5px;
        letter-spacing: 1px;
    }
    .footer-value {
        font-size: 1.1rem;
        font-weight: 800;
        color: #fff;
        border-bottom: 2px solid #333;
        padding-bottom: 2px;
        display: inline-block;
    }
    .footer-phone {
        font-size: 1.8rem;
        font-weight: 900;
        color: #fff;
        text-decoration: none;
        letter-spacing: -0.5px;
        transition: color 0.2s;
    }
    .footer-phone:hover { color: var(--street-yellow); }

    /* НИЖНЯЯ ПОЛОСА */
    .footer-bottom {
        background-color: #000; 
        color: #666;
        padding: 25px 0; 
        text-align: center;
        font-weight: bold; 
        text-transform: uppercase;
        font-size: 0.8rem; 
        letter-spacing: 2px;
        border-top: 1px solid #222;
        position: relative; 
        z-index: 11;
    }
</style>

<footer class="street-footer fade-in-up">
    <div class="container">
        <div class="row gy-5">
            
            <!-- 1. БРЕНД -->
            <div class="col-lg-4 col-md-12">
                <a href="/" class="footer-logo-box">
                    <h2 class="footer-logo-text">UFFNV<span>.</span></h2>
                </a>
                
                <p class="footer-text-block pe-lg-5 mb-5 border-start border-4 border-warning ps-3 lh-sm">
                    УЛИЧНАЯ КУЛЬТУРА В КАЖДОМ ШВЕ.<br>
                    <span style="color: #fff;">ОДЕВАЙСЯ ТАК, КАК ЧУВСТВУЕШЬ.</span>
                </p>
                
                <div class="d-flex">
                    <a href="#" class="social-btn"><i class="bi bi-telegram"></i></a>
                    <a href="#" class="social-btn"><i class="bi bi-youtube"></i></a>
                    <a href="#" class="social-btn fw-black" style="font-size: 0.9rem; font-weight: 900;">VK</a>
                </div>
            </div>

            <!-- 2. КАТАЛОГ -->
            <div class="col-lg-2 col-md-4 col-6">
                <div class="footer-header">КАТАЛОГ</div>
                <nav class="d-flex flex-column">
                    <a href="/pages/catalog.php" class="footer-link <?= ($curPage == 'catalog.php' && !isset($_GET['sort']) && !isset($_GET['sale'])) ? 'active' : '' ?>">
                        Все товары
                    </a>
                    <a href="/pages/catalog.php?sort=new" class="footer-link <?= (isset($_GET['sort']) && $_GET['sort'] == 'new') ? 'active' : '' ?>">
                        Новинки
                    </a>
                    <a href="/pages/catalog.php?sale=1" class="footer-link text-danger <?= (isset($_GET['sale']) && $_GET['sale'] == '1') ? 'active' : '' ?>">
                        SALE %
                    </a>
                    <a href="/pages/cart.php" class="footer-link <?= ($curPage == 'cart.php') ? 'active' : '' ?>">
                        Корзина
                    </a>
                </nav>
            </div>

            <!-- 3. ИНФО -->
            <div class="col-lg-2 col-md-4 col-6">
                <div class="footer-header">ИНФО</div>
                <nav class="d-flex flex-column">
                    <a href="/pages/about.php" class="footer-link <?= ($curPage == 'about.php') ? 'active' : '' ?>">
                        О бренде
                    </a>
                    <a href="/pages/delivery.php" class="footer-link <?= ($curPage == 'delivery.php') ? 'active' : '' ?>">
                        Доставка
                    </a>
                    <a href="/pages/contacts.php" class="footer-link <?= ($curPage == 'contacts.php') ? 'active' : '' ?>">
                        Контакты
                    </a>
                    <a href="/pages/topics_list.php" class="footer-link <?= ($curPage == 'topics_list.php' || $curPage == 'forum.php' || $curPage == 'topic.php') ? 'active' : '' ?>">
                        Форум
                    </a>
                </nav>
            </div>

            <!-- 4. СВЯЗЬ -->
            <div class="col-lg-4 col-md-4">
                <div class="footer-header">СВЯЗЬ</div>
                
                <div class="mb-4">
                    <span class="footer-label">Где нас найти:</span>
                    <span class="footer-value">г. Москва, ул. Уличная 13</span>
                </div>

                <div class="mb-4">
                    <span class="footer-label">Звони:</span>
                    <a href="tel:+79990000000" class="footer-phone">
                        +7 (999) 000-00-00
                    </a>
                </div>

                <div>
                    <a href="mailto:support@uffn.ru" class="btn btn-warning w-100 w-md-auto rounded-0 fw-black px-4 py-3 border-2 border-white text-dark shadow-sm fw-bold text-uppercase">
                        <i class="bi bi-envelope-fill me-2"></i> НАПИСАТЬ НАМ
                    </a>
                </div>
            </div>

        </div>
    </div>
</footer>

<!-- НИЖНЯЯ ПОЛОСА -->
<div class="footer-bottom">
    <div class="container">
        &copy; 2024 - <?= date('Y') ?> UFFNV STORE. MADE ON THE STREETS.
    </div>
</div>

<!-- SCRIPTS (Оставляем как есть, для работы функционала) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })
</script>

</body>
</html>
