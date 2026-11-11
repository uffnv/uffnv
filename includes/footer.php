<!-- includes/footer.php -->
<?php
if (!isset($curPage)) {
    $curPage = basename($_SERVER['PHP_SELF']);
}
?>

<footer class="street-footer">
    <div class="container">
        <div class="row gy-5">

            <!-- 1. БРЕНД -->
            <div class="col-lg-4 col-md-12">
                <a href="/" class="footer-logo-box" aria-label="UFFNV Главная">
                    <h2 class="footer-logo-text">UFFNV<span>.</span></h2>
                </a>
                <p class="text-white-50 fw-bold small pe-lg-4" style="line-height: 1.6;">
                    Стритвир бренд и комьюнити уличной культуры. <br>
                    Одежда, мерч, материалы для граффити и форум для художников.
                </p>

                <div class="d-flex mb-3">
                    <a href="https://t.me/uffn_v" target="_blank" rel="noopener noreferrer" class="social-btn" title="Telegram" aria-label="Telegram"><i class="bi bi-telegram" aria-hidden="true"></i></a>
                    <a href="/pages/contacts.php" class="social-btn" title="Контакты и каналы связи" aria-label="Контакты"><i class="bi bi-chat-dots-fill" aria-hidden="true"></i></a>
                </div>

                <div class="text-white-50 small mt-2" style="font-size: 0.75rem;">
                    <div><strong>Платформа:</strong> UFFNV STREET CULTURE</div>
                    <div><strong>Юрисдикция:</strong> Российская Федерация</div>
                </div>
            </div>

            <!-- 2. КАТАЛОГ -->
            <div class="col-lg-2 col-md-4 col-6">
                <div class="footer-header">КАТАЛОГ</div>
                <nav class="d-flex flex-column" aria-label="Навигация каталога">
                    <a href="/pages/catalog.php" class="footer-link <?= ($curPage === 'catalog.php' && !isset($_GET['sale']) && !isset($_GET['sort'])) ? 'active' : '' ?>">
                        Все товары
                    </a>
                    <a href="/pages/catalog.php?sort=new" class="footer-link <?= (isset($_GET['sort']) && $_GET['sort'] === 'new') ? 'active' : '' ?>">
                        Новинки
                    </a>
                    <a href="/pages/catalog.php?sale=1" class="footer-link text-danger <?= (isset($_GET['sale']) && $_GET['sale'] === '1') ? 'active' : '' ?>">
                        SALE %
                    </a>
                    <a href="/pages/cart.php" class="footer-link <?= ($curPage === 'cart.php') ? 'active' : '' ?>">
                        Корзина
                    </a>
                </nav>
            </div>

            <!-- 3. ИНФО & ПРАВОВЫЕ ДОКУМЕНТЫ -->
            <div class="col-lg-3 col-md-4 col-6">
                <div class="footer-header">ИНФО &amp; ЗАКОН</div>
                <nav class="d-flex flex-column" aria-label="Информация и документы">
                    <a href="/pages/about.php" class="footer-link <?= ($curPage === 'about.php') ? 'active' : '' ?>">
                        О бренде
                    </a>
                    <a href="/pages/delivery.php" class="footer-link <?= ($curPage === 'delivery.php') ? 'active' : '' ?>">
                        Доставка
                    </a>
                    <a href="/pages/privacy.php" class="footer-link <?= ($curPage === 'privacy.php') ? 'active' : '' ?>" style="color: var(--street-yellow);">
                        Политика 152-ФЗ
                    </a>
                    <a href="/pages/terms.php" class="footer-link <?= ($curPage === 'terms.php') ? 'active' : '' ?>">
                        Оферта и возврат
                    </a>
                    <a href="/pages/contacts.php" class="footer-link <?= ($curPage === 'contacts.php') ? 'active' : '' ?>">
                        Контакты
                    </a>
                    <a href="/pages/topics_list.php" class="footer-link <?= in_array($curPage, ['topics_list.php', 'topic.php', 'create_topic.php'], true) ? 'active' : '' ?>">
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
                    <a href="mailto:support@uffnv.ru" class="btn btn-warning w-100 rounded-0 fw-black px-3 py-2 border-2 border-white text-dark shadow-sm text-uppercase" style="font-size: 0.85rem;">
                        <i class="bi bi-envelope-fill me-1" aria-hidden="true"></i> Написать нам
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
            <a href="/pages/privacy.php" class="text-white-50 text-decoration-none me-3">152-ФЗ Конфиденциальность</a>
            <a href="/pages/terms.php" class="text-white-50 text-decoration-none">Публичная оферта</a>
        </div>
    </div>
</div>

<!-- 152-ФЗ: Баннер согласия на использование Cookie -->
<div id="streetCookieBanner" class="street-cookie-banner" role="dialog" aria-label="Уведомление о cookie">
    <div class="d-flex align-items-center gap-2 mb-2">
        <span class="badge bg-warning text-dark rounded-0 fw-black" style="font-size: 0.7rem;">152-ФЗ РФ</span>
        <span class="fw-bold text-uppercase small text-white">Файлы Cookie</span>
    </div>
    <p class="small text-white-50 mb-3" style="font-size: 0.8rem; line-height: 1.4;">
        Мы используем файлы cookie и локальные хранилища для корректной работы корзины, авторизации и аналитики сервиса в соответствии с
        <a href="/pages/privacy.php" target="_blank" rel="noopener noreferrer" class="text-warning text-decoration-underline fw-bold">Политикой конфиденциальности</a>.
    </p>
    <button id="acceptStreetCookieBtn" type="button" class="btn btn-warning w-100 rounded-0 fw-black text-dark text-uppercase py-2" style="font-size: 0.85rem;">
        Принять и продолжить
    </button>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

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
