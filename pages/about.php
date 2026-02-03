<?php
// pages/about.php
session_start();
require_once __DIR__ . '/../includes/header.php';
?>

<style>
    /* === ШРИФТЫ ИЗ INDEX.PHP === */
    body { 
        font-family: 'Arial', sans-serif; 
        background: #120024; 
        color: #fff; 
    }

    .street-font {
        font-family: 'Arial Black', 'Impact', sans-serif;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    /* === ФОН NEON GRID (Фиксированный) === */
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

    /* === СТИЛЬ "СЕКРЕТНОГО ДОСЬЕ" === */
    .about-card {
        background: #fff;
        border: 4px solid #000;
        box-shadow: 10px 10px 0 #000;
        transform: rotate(-1deg);
        position: relative;
        max-width: 800px;
        margin: 0 auto;
        color: #000;
        transition: all 0.3s ease;
    }

    .about-card:hover {
        transform: rotate(0deg) scale(1.01);
        box-shadow: 15px 15px 0 #bc13fe; /* Неоновая тень при наведении */
    }

    /* Декоративный "Скотч" */
    .tape-strip {
        position: absolute; top: -20px; left: 50%; transform: translateX(-50%) rotate(2deg);
        width: 160px; height: 45px;
        background: rgba(255, 255, 255, 0.4);
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        z-index: 10;
        border-left: 2px dotted rgba(0,0,0,0.2);
        border-right: 2px dotted rgba(0,0,0,0.2);
        background-color: #fce300;
        opacity: 0.9;
    }

    .marker-highlight {
        background: transparent;
        transition: background 0.3s ease;
        padding: 0 5px;
        font-weight: 900;
    }
    .about-card:hover .marker-highlight {
        background: linear-gradient(120deg, #FCE300 0%, #FCE300 100%);
        background-repeat: no-repeat;
        background-size: 100% 40%;
        background-position: 0 88%;
    }

    /* Блоки статистики */
    .stat-box {
        border: 3px solid #000;
        padding: 20px;
        text-align: center;
        background: #fff;
        color: #000;
        transition: transform 0.2s, background 0.2s, color 0.2s;
        height: 100%;
        display: flex; flex-direction: column; justify-content: center;
    }
    
    .stat-box:hover {
        transform: translateY(-5px);
        background: #000;
        color: #FCE300;
        box-shadow: 6px 6px 0 #bc13fe;
        border-color: #000;
    }
    
    .stat-box small {
        display: block;
        font-weight: 900;
        text-transform: uppercase;
        font-family: 'Arial', sans-serif;
        margin-top: 5px;
        font-size: 0.9rem;
    }

    /* Анимация появления */
    .fade-in-up { animation: fadeInUp 0.8s ease-out; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px) rotate(-1deg); } to { opacity: 1; transform: translateY(0) rotate(-1deg); } }
    
    /* Кнопка в стиле Street */
    .btn-black { 
        background: #000; 
        color: #fff; 
        border: 3px solid #000;
        transition: all 0.2s; 
        font-family: 'Arial Black', 'Impact', sans-serif;
        text-transform: uppercase;
    }
    .btn-black:hover { 
        transform: translate(-4px, -4px); 
        box-shadow: 8px 8px 0 #bc13fe !important; 
        background: #fce300; 
        color: #000;
    }
</style>

<!-- ФОН -->
<div class="bg-main-anim"></div>

<div class="container py-5" style="min-height: 85vh; display: flex; align-items: center;">
    
    <div class="about-card p-4 p-md-5 fade-in-up">
        <!-- Декоративный скотч -->
        <div class="tape-strip"></div>

        <div class="text-center mb-5 pt-3">
            <h1 class="display-3 street-font mb-0" style="letter-spacing: -1px; text-shadow: 3px 3px 0 #ccc;">
                UFFNV <span style="color: #000;">CLOTHING</span>
            </h1>
            <p class="lead street-font text-muted mt-2 border-bottom border-4 border-dark d-inline-block pb-1" style="font-size: 1.2rem;">
                EST. 2024 • UNDERGROUND CULTURE
            </p>
        </div>

        <div class="row align-items-center g-5">
            <div class="col-lg-12">
                <div class="fs-5 fw-bold lh-base text-center">
                    <p class="mb-4">
                        <span class="bg-black text-white px-2 street-font">UFFNV</span> — это больше, чем просто одежда. 
                        Это манифест свободы. Это <span class="marker-highlight">ПУЛЬС ГОРОДА</span>, застывший в ткани.
                    </p>
                    <p class="mb-4 text-secondary">
                        Мы не следуем трендам. Мы создаем униформу для тех, кто рисует на стенах, катает на досках и не боится быть громким в мире тишины.
                    </p>
                    <p class="mb-0 text-dark fw-black">
                        КАЖДЫЙ ШОВ — ЭТО ИСТОРИЯ. КАЖДЫЙ ПРИНТ — ЭТО ВЫЗОВ.
                    </p>
                </div>
            </div>
        </div>

        <!-- Статистика / Фичи -->
        <div class="row g-4 mt-4 border-top border-4 border-dark pt-5">
            <div class="col-md-4">
                <div class="stat-box">
                    <h2 class="mb-0 display-4 street-font">100%</h2>
                    <small>Качество</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-box">
                    <h2 class="mb-0 display-4 street-font">NO</h2>
                    <small>Fake Shit</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-box">
                    <h2 class="mb-0 display-4 street-font">RU</h2>
                    <small>Производство</small>
                </div>
            </div>
        </div>

        <div class="text-center mt-5">
            <a href="/pages/catalog.php" class="btn btn-black rounded-0 px-5 py-3 fs-4 shadow-sm position-relative overflow-hidden">
                Смотреть коллекцию ->
            </a>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
