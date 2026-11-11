<?php
// pages/terms.php
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

<div class="container py-5 my-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            
            <div class="info-card">
                <div class="d-flex align-items-center gap-3 mb-4 border-bottom border-dark border-3 pb-3">
                    <span class="badge bg-black text-warning rounded-0 px-3 py-2 street-font fs-6">ЗоЗПП РФ</span>
                    <h1 class="street-font fs-2 m-0 text-dark">Публичная оферта и Пользовательское соглашение</h1>
                </div>

                <div class="text-secondary small lh-lg" style="font-size: 0.95rem;">
                    <p class="text-dark fw-bold">Редакция от <?= date('d.m.Y') ?> г. Интернет-магазин UFFNV STORE (uffnv.ru)</p>

                    <h4 class="street-font text-dark mt-4 border-bottom border-2 border-warning pb-1">1. Общие положения</h4>
                    <p>
                        Настоящий документ является публичной офертой в соответствии со ст. 435 и ч. 2 ст. 437 Гражданского кодекса РФ.
                        Оформление заказа на сайте uffnv.ru либо регистрация учетной записи является полным и безоговорочным принятием (акцептом) настоящей Оферты.
                    </p>

                    <h4 class="street-font text-dark mt-4 border-bottom border-2 border-warning pb-1">2. Предмет соглашения</h4>
                    <p>
                        Продавец обязуется передать в собственность Покупателя товары (одежда, аксессуары, авторский мерч и арт-продукция), представленные в каталоге интернет-магазина, а Покупатель обязуется оплатить и принять заказ на условиях настоящего Договора.
                    </p>

                    <h4 class="street-font text-dark mt-4 border-bottom border-2 border-warning pb-1">3. Оформление заказа и оплата</h4>
                    <p>
                        Заказ формируется Покупателем самостоятельно через сайт uffnv.ru. Все цены указаны в рублях Российской Федерации. 
                        Оплата производится онлайн банковской картой или при получении в пункте выдачи.
                        В соответствии с требованиями Федерального закона № 54-ФЗ электронный чек направляется на email, указанный Покупателем при оформлении заказа.
                    </p>

                    <h4 class="street-font text-dark mt-4 border-bottom border-2 border-warning pb-1">4. Доставка</h4>
                    <p>
                        Доставка осуществляется курьерскими службами и транспортными компаниями по всей территории России. Сроки и стоимость доставки рассчитываются при подтверждении заказа.
                    </p>

                    <h4 class="street-font text-dark mt-4 border-bottom border-2 border-warning pb-1">5. Возврат товара и отказ от заказа (ст. 26.1 ЗоЗПП РФ)</h4>
                    <p>
                        <strong>Право на возврат:</strong> В соответствии со ст. 26.1 Закона РФ «О защите прав потребителей» (дистанционный способ продажи товара), Покупатель вправе отказаться от товара надлежащего качества в любое время до его передачи, а после передачи — <strong>в течение 7 (семи) календарных дней</strong>.
                    </p>
                    <p>
                        Возврат товара надлежащего качества возможен, если сохранены его товарный вид, фабричные ярлыки, бирки, потребительские свойства и документ, подтверждающий факт покупки.
                    </p>
                    <p>
                        В случае обнаружения брака (ненадлежащего качества) возврат или замена осуществляются в соответствии со статьями 18–24 ЗоЗПП РФ.
                    </p>

                    <h4 class="street-font text-dark mt-4 border-bottom border-2 border-warning pb-1">6. Реквизиты продавца</h4>
                    <div class="p-3 bg-light border border-2 border-dark mt-3">
                        <div><strong>Проект:</strong> UFFNV STORE / Стрит-арт платформа</div>
                        <div><strong>Сайт:</strong> uffnv.ru</div>
                        <div><strong>Электронная почта для обращений:</strong> admin@uffnv.ru</div>
                        <div><strong>Служба клиентской поддержки:</strong> support@uffnv.ru</div>
                        <div><strong>Территориальная юрисдикция:</strong> Российская Федерация</div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
