<!-- includes/footer.php -->

<?php
if (!isset($curPage)) {
    $curPage = basename($_SERVER['PHP_SELF']);
}
?>

<!-- Отступ, чтобы контент не прилипал -->
<div style="margin-top: 100px;"></div>

<style>
    /* ОБЩИЙ ФУТЕР */
    .street-footer {
        background-color: #0d0d0d;
        color: #fff;
        border-top: 5px solid var(--street-yellow);
        padding-top: 60px;
        padding-bottom: 40px;
        position: relative;
        z-index: 10;
    }

    /* БЛОК С ЛОГОТИПОМ */
    .footer-logo-box {
        display: inline-block;
        background: #000;
        border: 3px solid var(--street-yellow);
        padding: 5px 15px;
        box-shadow: 4px 4px 0 var(--street-yellow);
        margin-bottom: 20px;
        text-decoration: none;
    }
    .footer-logo-text {
        font-family: 'Impact', sans-serif;
        font-size: 2.2rem;
        color: #fff;
        margin: 0;
        letter-spacing: 1px;
    }
    .footer-logo-text span { color: var(--street-yellow); }

    /* ЗАГОЛОВКИ КОЛОНОК */
    .footer-header {
        font-family: 'Impact', sans-serif;
        font-size: 1.3rem;
        color: #fff;
        letter-spacing: 1px;
        margin-bottom: 25px;
        position: relative;
        display: inline-block;
    }
    .footer-header::after {
        content: '';
        position: absolute;
        bottom: -5px;
        left: 0;
        width: 30px;
        height: 3px;
        background-color: var(--street-yellow);
    }

    /* СОЦСЕТИ */
    .social-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 45px;
        height: 45px;
        background: #1a1a1a;
        color: #fff;
        border: 2px solid #333;
        font-size: 1.2rem;
        margin-right: 10px;
        transition: all 0.2s;
        text-decoration: none;
    }
    .social-btn:hover {
        background: var(--street-yellow);
        color: #000;
        border-color: #000;
        transform: translateY(-3px);
        box-shadow: 3px 3px 0 #fff;
    }

    /* ССЫЛКИ НАВИГАЦИИ */
    .footer-link {
        text-decoration: none; 
        color: #e0e0e0; 
        font-weight: 700; 
        font-size: 0.95rem;
        display: block; 
        margin-bottom: 12px;
        transition: all 0.2s;
        position: relative;
        width: fit-content;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    /* Ховер и Активное состояние */
    .footer-link:hover, .footer-link.active {
        color: #000 !important; 
        background-color: var(--street-yellow);
        padding: 2px 8px;
        margin-left: -8px; 
        transform: translateX(8px);
        box-shadow: 5px 5px 0 #000;
    }
    
    /* Спец-стиль для SALE */
    .footer-link.text-danger { color: #ff6b6b; }
    .footer-link.text-danger:hover, .footer-link.text-danger.active {
        background-color: #dc3545; color: #fff !important;
        box-shadow: 5px 5px 0 #000;
    }

    /* БЛОК СВЯЗИ */
    .footer-label {
        font-size: 0.8rem;
        text-transform: uppercase;
        color: #888;
        font-weight: 900;
        display: block;
        margin-bottom: 2px;
    }
    .footer-value {
        font-weight: 700;
        font-size: 1.1rem;
        color: #fff;
    }
    .footer-phone {
        font-family: 'Impact', sans-serif;
        font-size: 1.6rem;
        color: #fff;
        text-decoration: none;
        letter-spacing: -0.5px;
        transition: color 0.2s;
    }
    .footer-phone:hover { color: var(--street-yellow); }

    /* НИЖНЯЯ ПЛАШКА */
    .footer-bottom {
        background-color: #000;
        border-top: 1px solid #222;
        padding: 20px 0;
        font-size: 0.8rem;
        font-weight: 700;
        color: #666;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    /* 152-ФЗ Cookie Banner */
    .street-cookie-banner {
        position: fixed;
        bottom: 20px;
        right: 20px;
        max-width: 420px;
        background: #000;
        color: #fff;
        border: 3px solid var(--street-yellow);
        box-shadow: 8px 8px 0 rgba(0,0,0,0.8);
        padding: 20px;
        z-index: 10050;
        display: none;
    }
</style>

<footer class="street-footer">
    <div class="container">
        <div class="row gy-5">
            
            <!-- 1. БРЕНД -->
            <div class="col-lg-4 col-md-12">
                <a href="/" class="footer-logo-box">
                    <h2 class="footer-logo-text">UFFNV<span>.</span></h2>
                </a>
                <p class="text-white-50 fw-bold small pe-lg-4" style="line-height: 1.6;">
                    Стритвир бренд и комьюнити уличной культуры. <br>
                    Одежда, мерч, холсты и закрытый форум для художников.
                </p>
                
                <div class="d-flex mb-3">
                    <a href="https://t.me/uffn_v" target="_blank" class="social-btn" title="Telegram"><i class="bi bi-telegram"></i></a>
                    <a href="#" class="social-btn" title="YouTube"><i class="bi bi-youtube"></i></a>
                    <a href="#" class="social-btn fw-black" style="font-size: 0.9rem; font-weight: 900;" title="VK">VK</a>
                </div>

                <div class="text-white-50 small mt-2" style="font-size: 0.75rem;">
                    <div><strong>Платформа:</strong> UFFNV STREET CULTURE</div>
                    <div><strong>Юрисдикция:</strong> Российская Федерация</div>
                </div>
            </div>

            <!-- 2. КАТАЛОГ -->
            <div class="col-lg-2 col-md-4 col-6">
                <div class="footer-header">КАТАЛОГ</div>
                <nav class="d-flex flex-column">
                    <a href="/pages/catalog.php" class="footer-link <?= ($curPage == 'catalog.php' && !isset($_GET['sale']) && !isset($_GET['sort'])) ? 'active' : '' ?>">
                        Все вещи
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

            <!-- 3. ИНФО & ПРАВОВЫЕ ДОКУМЕНТЫ -->
            <div class="col-lg-3 col-md-4 col-6">
                <div class="footer-header">ИНФО & ЗАКОН</div>
                <nav class="d-flex flex-column">
                    <a href="/pages/about.php" class="footer-link <?= ($curPage == 'about.php') ? 'active' : '' ?>">
                        О бренде
                    </a>
                    <a href="/pages/delivery.php" class="footer-link <?= ($curPage == 'delivery.php') ? 'active' : '' ?>">
                        Доставка
                    </a>
                    <a href="/pages/privacy.php" class="footer-link <?= ($curPage == 'privacy.php') ? 'active' : '' ?>" style="color: var(--street-yellow);">
                        Политика 152-ФЗ
                    </a>
                    <a href="/pages/terms.php" class="footer-link <?= ($curPage == 'terms.php') ? 'active' : '' ?>">
                        Оферта и возврат
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
            <div class="col-lg-3 col-md-4">
                <div class="footer-header">СВЯЗЬ</div>
                
                <div class="mb-3">
                    <span class="footer-label">Адрес:</span>
                    <span class="footer-value fs-6">г. Москва, ул. Уличная, д. 13</span>
                </div>

                <div class="mb-3">
                    <span class="footer-label">Email:</span>
                    <a href="mailto:admin@uffnv.ru" class="text-white text-decoration-none fw-bold small">
                        admin@uffnv.ru
                    </a>
                </div>

                <div class="mb-3">
                    <span class="footer-label">Звони:</span>
                    <a href="tel:+79990000000" class="footer-phone fs-5">
                        +7 (999) 000-00-00
                    </a>
                </div>

                <div>
                    <a href="mailto:support@uffn.ru" class="btn btn-warning w-100 rounded-0 fw-black px-3 py-2 border-2 border-white text-dark shadow-sm fw-bold text-uppercase" style="font-size: 0.85rem;">
                        <i class="bi bi-envelope-fill me-1"></i> Написать нам
                    </a>
                </div>
            </div>

        </div>
    </div>
</footer>

<!-- НИЖНЯЯ ПОЛОСА -->
<div class="footer-bottom">
    <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
        <div>
            &copy; 2024 - <?= date('Y') ?> UFFNV STORE. MADE ON THE STREETS.
        </div>
        <div class="small text-white-50">
            <a href="/pages/privacy.php" class="text-white-50 text-decoration-none me-3 hover-warning">152-ФЗ Конфиденциальность</a>
            <a href="/pages/terms.php" class="text-white-50 text-decoration-none hover-warning">Публичная оферта</a>
        </div>
    </div>
</div>

<!-- 152-ФЗ: Баннер согласия на использование Cookie -->
<div id="streetCookieBanner" class="street-cookie-banner">
    <div class="d-flex align-items-center gap-2 mb-2">
        <span class="badge bg-warning text-dark rounded-0 fw-black" style="font-size: 0.7rem;">152-ФЗ РФ</span>
        <span class="fw-bold text-uppercase small text-white">Файлы Cookie</span>
    </div>
    <p class="small text-white-50 mb-3" style="font-size: 0.8rem; line-height: 1.4;">
        Мы используем файлы cookie и локальные хранилища для корректной работы корзины, авторизации и аналитики сервиса в соответствии с 
        <a href="/pages/privacy.php" target="_blank" class="text-warning text-decoration-underline fw-bold">Политикой конфиденциальности</a>.
    </p>
    <button id="acceptStreetCookieBtn" type="button" class="btn btn-warning w-100 rounded-0 fw-black text-dark text-uppercase py-2" style="font-size: 0.85rem;">
        Принять и продолжить
    </button>
</div>

<!-- SCRIPTS (Оставляем как есть, для работы функционала) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Bootstrap tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // 152-ФЗ Cookie согласие
        if (!localStorage.getItem('cookie_consent_uffnv')) {
            var banner = document.getElementById('streetCookieBanner');
            if (banner) banner.style.display = 'block';
        }
        var btn = document.getElementById('acceptStreetCookieBtn');
        if (btn) {
            btn.addEventListener('click', function () {
                localStorage.setItem('cookie_consent_uffnv', 'true');
                var banner = document.getElementById('streetCookieBanner');
                if (banner) banner.style.display = 'none';
            });
        }
    });
</script>

</body>
</html>
