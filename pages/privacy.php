<?php
// pages/privacy.php
require_once __DIR__ . '/../includes/functions.php';
initAppSession();
require_once __DIR__ . '/../includes/header.php';
?>

<style>
    body { font-family: 'Arial', sans-serif; background: #120024; color: #fff; }

    .info-card {
        background: #fff;
        color: #000;
        border: 4px solid #000;
        box-shadow: 10px 10px 0 #FCE300;
        padding: 30px;
    }
</style>

<div class="bg-main-anim"></div>

<div class="container py-4 py-md-5 position-relative" style="z-index: 2;">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            
            <div class="info-card">
                <div class="d-flex flex-wrap align-items-center gap-3 mb-4 border-bottom border-dark border-3 pb-3">
                    <span class="badge bg-black text-warning rounded-0 px-3 py-2 street-font fs-6">152-ФЗ РФ</span>
                    <h1 class="street-font fs-2 m-0 text-dark">Политика обработки персональных данных</h1>
                </div>

                <div class="text-secondary small lh-lg" style="font-size: 0.95rem;">
                    <p class="text-dark fw-bold">Редакция от <?= date('d.m.Y') ?> г. Сайт: uffnv.ru</p>

                    <h4 class="street-font text-dark mt-4 border-bottom border-2 border-warning pb-1">1. Общие положения</h4>
                    <p>
                        Настоящая Политика обработки персональных данных (далее — «Политика») составлена в соответствии с требованиями Федерального закона от 27.07.2006 № 152-ФЗ «О персональных данных» и определяет порядок сбора, хранения, обработки и защиты персональной информации пользователей интернет-магазина и комьюнити-платформы UFFNV (далее — «Оператор»).
                    </p>
                    <p>
                        Оператор ставит соблюдение прав и свобод граждан РФ приоритетным условием ведения деятельности, включая защиту неприкосновенности частной жизни, личной и семейной тайны.
                    </p>

                    <h4 class="street-font text-dark mt-4 border-bottom border-2 border-warning pb-1">2. Категории обрабатываемых данных</h4>
                    <p>Оператор обрабатывает следующие персональные данные Пользователей:</p>
                    <ul>
                        <li>Фамилия, имя, псевдоним (никнейм);</li>
                        <li>Адрес электронной почты (email);</li>
                        <li>Номер контактного телефона;</li>
                        <li>Адрес доставки заказов (город, улица, номер дома/квартиры);</li>
                        <li>Обезличенные технические данные, собираемые через файлы cookie, IP-адрес, тип браузера и устройства.</li>
                    </ul>

                    <h4 class="street-font text-dark mt-4 border-bottom border-2 border-warning pb-1">3. Цели обработки персональных данных</h4>
                    <ul>
                        <li>Регистрация и идентификация пользователя в личном кабинете и на форуме;</li>
                        <li>Оформление, комплектация и курьерская/почтовая доставка заказов интернет-магазина;</li>
                        <li>Обратная связь с клиентом, уведомление о статусах заказов и сообщений;</li>
                        <li>Информирование о новых товарах, дропах и акциях (только при наличии отдельного согласия в соответствии со ст. 18 закона № 38-ФЗ «О рекламе»);</li>
                        <li>Улучшение работы сервиса и аналитика пользовательского опыта (файлы cookie).</li>
                    </ul>

                    <h4 class="street-font text-dark mt-4 border-bottom border-2 border-warning pb-1">4. Правовые основания и локализация баз данных</h4>
                    <p>
                        Обработка данных осуществляется на основании согласия Пользователя, а также во исполнение договоров купли-продажи (ст. 6 152-ФЗ).
                    </p>
                    <p class="p-2 bg-light border-start border-4 border-dark fw-bold">
                        В соответствии с ч. 5 ст. 18 Федерального закона № 152-ФЗ сбор, запись, систематизация, накопление, хранение и извлечение персональных данных граждан РФ осуществляются с использованием баз данных, находящихся на территории Российской Федерации (хостинг Джино, дата-центр РФ).
                    </p>

                    <h4 class="street-font text-dark mt-4 border-bottom border-2 border-warning pb-1">5. Порядок и условия обработки</h4>
                    <p>
                        Обработка осуществляется с применением средств автоматизации и шифрования каналов связи (HTTPS/SSL). Передача данных третьим лицам не осуществляется, за исключением служб курьерской и почтовой доставки (Почта России, СДЭК и др.) исключительно в объемах, необходимых для доставки заказа.
                    </p>

                    <h4 class="street-font text-dark mt-4 border-bottom border-2 border-warning pb-1">6. Права Пользователя и отзыв согласия</h4>
                    <p>
                        Пользователь имеет право на получение сведений о своих данных, их уточнение, блокирование или уничтожение. Согласие может быть отозвано в любой момент путем направления письменного уведомления на электронный адрес: <strong>admin@uffnv.ru</strong> с пометкой «Отзыв согласия на обработку персональных данных».
                    </p>

                    <h4 class="street-font text-dark mt-4 border-bottom border-2 border-warning pb-1">7. Файлы Cookie</h4>
                    <p>
                        Сайт использует технические и аналитические cookies для корректной работы авторизации и корзины покупок. Пользователь может отключить cookie в настройках браузера, однако часть функций сайта может стать недоступной.
                    </p>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
