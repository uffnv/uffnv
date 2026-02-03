<?php
// index.php
session_start();
require_once __DIR__ . '/config/db.php';

// === ПОДКЛЮЧАЕМ ВАШ ХЕДЕР ===
require_once __DIR__ . '/includes/header.php';

// Загрузка топиков
try {
    $sqlTop = "SELECT t.*, u.username, c.name as cat_title, 
               (SELECT COUNT(*) FROM posts WHERE topic_id = t.id) as replies_count 
               FROM topics t 
               JOIN users u ON t.user_id = u.id 
               JOIN categories c ON t.category_id = c.id 
               ORDER BY replies_count DESC LIMIT 3";
    $topTopics = $pdo->query($sqlTop)->fetchAll();
} catch (PDOException $e) { $topTopics = []; }
?>

<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

<style>
    /* === 1. ФИКС ФУТЕРА И ХЕДЕРА === */
    
    /* Скрываем отступ, который зашит в footer.php */
    body + div[style*="margin-top: 100px"], 
    div[style*="margin-top: 100px"] { display: none !important; }

    /* Умный хедер */
    .street-header {
        transition: transform 0.4s cubic-bezier(0.2, 0.8, 0.2, 1);
        will-change: transform;
    }
    .header-hidden { transform: translateY(-100%); }

    /* === 2. ОБЩИЕ СТИЛИ (ОРИГИНАЛ + АДАПТАЦИЯ) === */
    html { scroll-behavior: smooth; }
    body { font-family: 'Arial', sans-serif; overflow-x: hidden; background: #000; margin: 0; padding: 0; }

    /* Секции на весь экран с поддержкой мобильных */
    .full-screen-section { 
        position: relative; 
        width: 100%; 
        min-height: 100vh;
        min-height: 100dvh; /* Фикс для мобильных браузеров */
        display: flex; 
        align-items: center; 
        justify-content: center; 
        overflow: hidden; 
        padding: 0; 
    }
    
    /* Навигация (кнопка вниз) */
    .scroll-down-btn-container { position: absolute; bottom: 30px; left: 0; width: 100%; text-align: center; z-index: 20; pointer-events: none; }
    .scroll-btn { pointer-events: auto; background: none; border: none; color: rgba(255, 255, 255, 0.7); font-size: 2.5rem; transition: all 0.3s ease; cursor: pointer; padding: 10px; }
    .scroll-btn:hover { color: #FCE300 !important; transform: translateY(-5px); text-shadow: 0 0 15px rgba(252, 227, 0, 0.8); }

    /* Типографика и кнопки */
    .street-font { font-family: 'Arial Black', 'Impact', sans-serif; font-weight: 900; text-transform: uppercase; letter-spacing: 1px; }
    
    .btn-street { background-color: #FCE300; color: #000 !important; border: 4px solid #000; font-family: 'Arial Black', 'Impact', sans-serif; font-weight: 900; text-transform: uppercase; padding: 15px 30px; font-size: 1.2rem; box-shadow: 6px 6px 0px #000; text-decoration: none; display: inline-block; transition: all 0.2s; cursor: pointer; }
    .btn-street:hover { background-color: #fff; transform: translate(-3px, -3px); box-shadow: 10px 10px 0px #000; }
    .btn-street-sm { padding: 10px 20px; font-size: 1rem; box-shadow: 4px 4px 0px #000; width: 100%; }

    /* Фоны */
    .section-bg { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 1; }
    .section-content { position: relative; z-index: 10; width: 100%; padding-bottom: 60px; }

    .bg-main-anim { background: #120024; background-image: linear-gradient(rgba(188, 19, 254, 0.3) 1px, transparent 1px), linear-gradient(90deg, rgba(188, 19, 254, 0.3) 1px, transparent 1px); background-size: 80px 80px; animation: gridMove 6s linear infinite; box-shadow: inset 0 0 150px rgba(0,0,0,0.9); }
    @keyframes gridMove { 0% { background-position: 0 0; } 100% { background-position: 0 80px; } }

    .bg-graffiti-brick { 
        background-color: #2a2a2a; 
        background-image: linear-gradient(335deg, rgba(0,0,0,0.4) 23px, transparent 23px), linear-gradient(155deg, rgba(0,0,0,0.4) 23px, transparent 23px), linear-gradient(335deg, rgba(0,0,0,0.4) 23px, transparent 23px), linear-gradient(155deg, rgba(0,0,0,0.4) 23px, transparent 23px); 
        background-size: 58px 58px; background-position: 0px 2px, 4px 35px, 29px 31px, 34px 6px; 
    }
    .bg-graffiti-brick::before { content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(0deg, rgba(188, 19, 254, 0.4) 0%, transparent 30%, transparent 70%, rgba(252, 227, 0, 0.2) 100%); z-index: 2; mix-blend-mode: overlay; }
    
    .street-tape { position: absolute; width: 120%; height: 60px; background: repeating-linear-gradient(45deg, #fce300, #fce300 20px, #000 20px, #000 40px); top: 20%; left: -10%; transform: rotate(-5deg); opacity: 0.8; box-shadow: 0 5px 15px rgba(0,0,0,0.5); z-index: 1; }
    .wall-noise { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMDAiIGhlaWdodD0iMjAwIj48ZmlsdGVyIGlkPSJnIj48ZmVUdXJidWxlbmNlIHR5cGU9ImZyYWN0YWxOb2lzZSIgYmFzZUZyZXF1ZW5jeT0iMC41IiBudW1PY3RhdmVzPSIzIiBzdGl0Y2hUaWxlcz0ic3RpdGNoIi8+PC9maWx0ZXI+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsdGVyPSJ1cmwoI2cpIiBvcGFjaXR5PSIwLjEiLz48L3N2Zz4='); opacity: 0.4; z-index: 3; pointer-events: none; }
    
    .card-placeholder-graffiti { width: 100%; height: 350px; background: #1a1a1a; display: flex; align-items: center; justify-content: center; border: 4px solid #fce300; box-shadow: 10px 10px 0 #000; background: repeating-linear-gradient(45deg, #1a1a1a, #1a1a1a 10px, #222 10px, #222 20px); }

    .bg-cyber-animated {
        background-color: #000;
        background-image: url('https://images.unsplash.com/photo-1558494949-ef526b0042a0?q=80&w=2000&auto=format&fit=crop');
        background-size: cover; background-position: center;
        animation: bg-breathe 20s ease-in-out infinite alternate; 
    }
    @keyframes bg-breathe { 0% { transform: scale(1); } 100% { transform: scale(1.1); } }
    .bg-cyber-overlay {
        position: absolute; top: 0; left: 0; width: 100%; height: 100%;
        background-image: radial-gradient(cyan 2px, transparent 2.5px), linear-gradient(rgba(0, 255, 255, 0.3) 1px, transparent 1px), linear-gradient(90deg, rgba(0, 255, 255, 0.3) 1px, transparent 1px);
        background-size: 50px 50px, 100px 100px, 100px 100px;
        animation: grid-fly 10s linear infinite; z-index: 2; mix-blend-mode: screen; 
    }
    @keyframes grid-fly { 0% { background-position: 0 0, 0 0, 0 0; } 100% { background-position: 50px 50px, 100px 100px, 100px 100px; } }
    .cyber-card-glitch {
        border: 2px solid cyan; background: rgba(0, 10, 20, 0.85); box-shadow: 0 0 50px rgba(0, 255, 255, 0.4);
        backdrop-filter: blur(10px); position: relative; z-index: 10; animation: container-glitch 4s infinite;
    }
    @keyframes container-glitch {
        0% { transform: translate(0, 0); border-color: cyan; }
        98% { transform: translate(0, 0); border-color: cyan; }
        98.5% { transform: translate(-2px, 2px); border-color: white; }
        99% { transform: translate(2px, -2px); border-color: magenta; }
        99.5% { transform: translate(0, 0); border-color: cyan; }
        100% { transform: translate(0, 0); border-color: cyan; }
    }
    .cyber-card-glitch::before {
        content: "AI_CORE_ACTIVE"; position: absolute; top: -15px; left: 50%; transform: translateX(-50%);
        background: #000; color: cyan; padding: 5px 20px; font-family: monospace; font-weight: bold; border: 1px solid cyan; box-shadow: 0 0 10px cyan;
    }
    .btn-cyber { background: rgba(0,0,0,0.8); color: cyan !important; border: 2px solid cyan; box-shadow: 0 0 15px cyan; text-shadow: 0 0 8px cyan; transition: 0.3s; }
    .btn-cyber:hover { background: cyan; color: #000 !important; box-shadow: 0 0 50px cyan; }
    .text-glow { text-shadow: 0 0 10px cyan; }

    .card-placeholder-retro { width: 100%; height: 350px; background: #120024; display: flex; align-items: center; justify-content: center; border: 4px solid #bc13fe; box-shadow: -10px 10px 0 #fff; }
    .bg-industrial-yellow { background-color: #FCE300; background-image: repeating-linear-gradient(45deg, #FCE300, #FCE300 10px, #e6cf00 10px, #e6cf00 20px); border-top: 5px solid #000; }
    .manifest-box-dark { background: #000; color: #fff; border: 4px solid #fff; box-shadow: 15px 15px 0px rgba(0,0,0,0.2); padding: 50px; transform: rotate(-1deg); position: relative; }
    .anim-icon { font-size: 6rem; animation: bounce 2s infinite; }
    @keyframes bounce { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.1); } }
    #matrixCanvas { width: 100%; height: 100%; background: #000; }
    .overlay-dark { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1; }

    /* === 3. МОБИЛЬНАЯ АДАПТАЦИЯ (COMPACT MODE) === */
    @media (max-width: 768px) {
        /* Уменьшаем шрифты, чтобы влезало */
        .mega-title { font-size: 3.5rem !important; margin-bottom: 0.5rem !important; }
        .display-2 { font-size: 2rem !important; }
        .display-3 { font-size: 1.8rem !important; }
        
        /* Сжимаем отступы */
        .full-screen-section { padding: 40px 15px !important; }
        .section-content { padding-bottom: 20px !important; }
        
        /* Уменьшаем блоки, но НЕ СКРЫВАЕМ их */
        .card-placeholder-graffiti, .card-placeholder-retro { height: 200px !important; margin-top: 15px; }
        
        /* Инфо-боксы: меньше паддинги */
        .info-box, .cyber-card-glitch, .manifest-box-dark { padding: 20px !important; }
        
        /* Ленту делаем тоньше и выше, но оставляем */
        .street-tape { height: 30px; top: 10%; }
        
        /* Значки и бейджи масштабируем */
        .badge { font-size: 0.7rem !important; padding: 0.3em 0.6em; }
        .anim-icon { font-size: 3rem !important; }
        
        /* Кнопка скролла чуть меньше */
        .scroll-btn { font-size: 1.8rem; }
        .scroll-down-btn-container { bottom: 10px; }
    }
</style>

<div id="section-hero" class="full-screen-section">
    <div class="section-bg bg-main-anim"></div>
    <div class="section-content container text-center">
        <h1 class="street-font text-white mb-4 mega-title" style="font-size: 6rem; line-height: 0.9; text-shadow: 4px 4px 0 #bc13fe;" data-aos="zoom-in">
            STREET<br>CULTURE<br>FORUM
        </h1>
        <div class="d-inline-block bg-warning text-black px-4 py-3 px-md-5 py-md-4 fs-5 fs-md-3 street-font border-3 border-dark" 
             style="border: 4px solid #000; transform: rotate(-2deg); box-shadow: 6px 6px 0 #bc13fe;"
             data-aos="fade-up" data-aos-delay="300">
            ТВОЙ ГОЛОС В ШУМЕ ГОРОДА
        </div>
    </div>
    <div class="scroll-down-btn-container">
        <button onclick="smoothScroll('section-graffiti')" class="scroll-btn"><i class="bi bi-chevron-down"></i></button>
    </div>
</div>

<div id="section-graffiti" class="full-screen-section" style="background: #111;">
    <div class="section-bg bg-graffiti-brick">
        <div class="street-tape"></div>
        <div class="wall-noise"></div>
    </div>
    <div class="section-content container">
        <div class="row align-items-center gy-4">
            <div class="col-lg-6 order-2 order-lg-1" data-aos="fade-right">
                <div class="info-box border-4 border-dark shadow-lg bg-white p-4 p-md-5 text-center">
                    <div class="mb-4 d-flex justify-content-center gap-2 flex-wrap">
                        <span class="badge bg-black text-white px-3 py-1 street-font">PASHA_CASHEL</span>
                        <span class="badge bg-black text-white px-3 py-1 street-font">ZACHEM</span>
                    </div>
                    <h2 class="display-3 street-font mb-4">Искусство<br>Протеста</h2>
                    <p class="lead text-dark fw-bold mb-4" style="font-family: monospace; letter-spacing: -1px;">// CITY IS A CANVAS</p>
                    <p class="text-muted mb-4">Город говорит с теми, кто умеет читать стены. От теггинга до муралов — мы документируем уличный код твоего района.</p>
                    
                    <div class="d-flex justify-content-center gap-2 mb-4 flex-wrap">
                        <span class="badge bg-light text-dark border border-dark p-2">BOMBING</span>
                        <span class="badge bg-light text-dark border border-dark p-2">MURALS</span>
                        <span class="badge bg-light text-dark border border-dark p-2">STENCIL</span>
                    </div>

                    <div class="mt-4"><a href="/pages/topics_list.php?cat=1" class="btn-street w-100">ЧИТАТЬ СТЕНЫ -></a></div>
                </div>
            </div>
            <div class="col-lg-6 order-1 order-lg-2" data-aos="fade-left">
                <div class="mx-auto position-relative overflow-hidden w-100" style="border: 6px solid #121212; box-shadow: 10px 10px 0 #bc13fe; transform: rotate(2deg); max-width: 500px;">
                    <div class="card-placeholder-graffiti">
                        <i class="bi bi-palette-fill anim-icon" style="color: #fce300;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="scroll-down-btn-container">
        <button onclick="smoothScroll('section-retro')" class="scroll-btn"><i class="bi bi-chevron-down"></i></button>
    </div>
</div>

<div id="section-retro" class="full-screen-section" style="background: #000;">
    <div class="section-bg bg-main-anim"></div>
    <div class="section-content container">
        <div class="row align-items-center gy-4">
            <div class="col-lg-6" data-aos="fade-right">
                <div class="mx-auto position-relative overflow-hidden w-100" style="border: 6px solid #fce300; box-shadow: -10px 10px 0 #fff; transform: rotate(-2deg); max-width: 500px;">
                     <div class="card-placeholder-retro">
                        <i class="bi bi-joystick anim-icon" style="color: #bc13fe;"></i>
                     </div>
                </div>
            </div>
            <div class="col-lg-6" data-aos="fade-left">
                <div class="info-box p-4 p-md-5 text-center" style="background: #121212; color: #fff; border: 4px solid #fce300; box-shadow: 10px 10px 0 #bc13fe;">
                    <h2 class="display-3 street-font mb-4">Geek Culture<br>& Retro</h2>
                    <p class="lead text-warning mb-4" style="font-family: monospace;">> INSERT COIN TO START</p>
                    <p class="text-white-50 mb-4">
                        Ламповый свет старых мониторов, синтвейв в наушниках и обсуждение культовой классики.
                        Здесь мы сохраняем историю пикселей и полигонов.
                    </p>
                    <div class="d-flex justify-content-center gap-2 mb-4 flex-wrap">
                        <span class="badge border border-warning text-warning p-2">GAMING</span>
                        <span class="badge border border-warning text-warning p-2">MOVIES</span>
                        <span class="badge border border-warning text-warning p-2">TECH</span>
                    </div>
                    <div class="mt-2"><a href="/pages/topics_list.php?cat=2" class="btn-street w-100">LEVEL UP -></a></div>
                </div>
            </div>
        </div>
    </div>
    <div class="scroll-down-btn-container">
        <button onclick="smoothScroll('section-forum')" class="scroll-btn"><i class="bi bi-chevron-down"></i></button>
    </div>
</div>

<div id="section-forum" class="full-screen-section" style="background: #222;">
    <div class="section-bg"><canvas id="matrixCanvas"></canvas><div class="overlay-dark"></div></div>
    <div class="section-content container py-5">
        <div class="text-center mb-5" data-aos="fade-down">
            <h2 class="display-2 street-font text-white" style="text-shadow: 4px 4px 0 #bc13fe;">ГОРЯЧИЕ ОБСУЖДЕНИЯ</h2>
        </div>
        <div class="row g-4 justify-content-center">
            <?php if (!empty($topTopics)): ?>
                <?php foreach ($topTopics as $i => $topic): ?>
                    <div class="col-md-4 mb-4" data-aos="flip-left" data-aos-delay="<?= $i * 100 ?>">
                        <div class="card h-100 border-3 border-dark rounded-0" style="box-shadow: 8px 8px 0px #000;">
                            <div class="card-header fw-bold bg-white border-bottom border-dark py-3">
                                <span class="badge bg-warning text-dark rounded-0 border border-dark street-font" style="font-size: 0.7rem;">
                                    <?= htmlspecialchars($topic['cat_title'] ?? 'Общее') ?>
                                </span>
                            </div>
                            <div class="card-body py-4 bg-light text-center">
                                <h4 class="street-font mb-3 fs-5"><?= htmlspecialchars($topic['title']) ?></h4>
                                <div class="text-muted small">Ответов: <?= $topic['replies_count'] ?></div>
                            </div>
                            <div class="card-footer bg-white border-top border-dark p-3">
                                <a href="/pages/topic.php?id=<?= $topic['id'] ?>" class="btn-street btn-street-sm text-center">ВРЫВАЙСЯ -></a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5" data-aos="zoom-in">
                    <div class="p-5 border border-secondary" style="background: rgba(0,0,0,0.5);">
                        <i class="bi bi-broadcast text-secondary anim-icon" style="font-size: 4rem; display:block; margin-bottom: 20px;"></i>
                        <h3 class="fw-bold text-white mt-4">ТИШИНА В ЭФИРЕ...</h3>
                        <p class="text-white-50">Будь первым, кто нарушит молчание.</p>
                        <a href="/pages/create_topic.php" class="btn-street mt-3">СОЗДАТЬ ТЕМУ</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="scroll-down-btn-container">
        <button onclick="smoothScroll('section-manifest')" class="scroll-btn"><i class="bi bi-chevron-down"></i></button>
    </div>
</div>

<div id="section-manifest" class="full-screen-section bg-industrial-yellow">
    <div class="section-content container">
        <div class="manifest-box-dark mx-auto text-center" style="max-width: 900px;" data-aos="zoom-in">
            <h2 class="display-2 street-font text-white mb-5">НАШ МАНИФЕСТ</h2>
            <p class="fs-5 mb-5 text-white-50">Мы — цифровое убежище для тех, кто ищет смысл среди шума.</p>
            <div class="row g-4 mt-2">
                <div class="col-md-4">
                    <div class="p-3 border border-secondary h-100">
                        <i class="bi bi-brush text-warning" style="font-size: 3rem;"></i>
                        <h3 class="mt-3 street-font text-white">ТВОРИ</h3>
                        <p class="text-secondary small mt-2">Делись своим артом, граффити и музыкой.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 border border-secondary h-100">
                        <i class="bi bi-controller text-info" style="font-size: 3rem; color: #bc13fe !important;"></i>
                        <h3 class="mt-3 street-font text-white">ИГРАЙ</h3>
                        <p class="text-secondary small mt-2">Обсуждай игры, гик-культуру и технологии.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 border border-secondary h-100">
                        <i class="bi bi-megaphone text-danger" style="font-size: 3rem;"></i>
                        <h3 class="mt-3 street-font text-white">ГОВОРИ</h3>
                        <p class="text-secondary small mt-2">Твой голос важен. Свобода слова.</p>
                    </div>
                </div>
            </div>
            <div class="mt-5 pt-4">
                <a href="/pages/register.php" class="btn-street w-100 py-3 fs-4 text-white shadow-sm" style="text-decoration:none;">ПРИСОЕДИНИТЬСЯ -></a>
            </div>
        </div>
    </div>
    <div class="scroll-down-btn-container">
        <button onclick="smoothScroll('section-ai')" class="scroll-btn" style="color: #000 !important;"><i class="bi bi-chevron-down"></i></button>
    </div>
</div>

<div id="section-ai" class="full-screen-section" style="background: #000;">
    <div class="section-bg bg-cyber-animated"></div>
    <div class="section-bg bg-cyber-overlay"></div>
    <div class="section-content container">
        <div class="row align-items-center justify-content-center">
            <div class="col-lg-8 text-center" data-aos="zoom-in-up">
                <div class="cyber-card-glitch p-5">
                    <h2 class="display-3 street-font text-glow text-white mb-2">NEURAL_NET</h2>
                    <p class="lead text-info mb-4" style="font-family: monospace;">>> SYSTEM_STATUS: ONLINE</p>
                    <p class="text-white mb-5 fw-bold" style="max-width: 600px; margin: 0 auto; text-shadow: 0 2px 2px #000;">
                        Персональная лента, собранная алгоритмом специально для тебя. <br>Вход в матрицу здесь.
                    </p>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <div class="d-flex justify-content-center flex-column align-items-center">
                            <a href="/personal/" class="btn-street btn-cyber px-5 py-3 fs-4 rounded-0">ЗАПУСТИТЬ ЛЕНТУ</a>
                        </div>
                    <?php else: ?>
                        <div class="d-flex justify-content-center flex-column align-items-center">
                            <a href="pages/login.php" class="btn-street btn-cyber px-5 py-3 fs-4 rounded-0">ИДЕНТИФИКАЦИЯ</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="scroll-down-btn-container">
        <button onclick="smoothScroll('section-hero')" class="scroll-btn"><i class="bi bi-arrow-up-circle-fill"></i></button>
    </div>
</div>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    // Плавный скролл
    function smoothScroll(id) {
        const element = document.getElementById(id);
        if (element) { element.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
    }
    
    // Умный хедер
    let lastScrollTop = 0;
    const header = document.querySelector('.street-header');
    window.addEventListener('scroll', function() {
        let scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        if (scrollTop > lastScrollTop && scrollTop > 100) {
            header.classList.add('header-hidden');
        } else {
            header.classList.remove('header-hidden');
        }
        lastScrollTop = scrollTop;
    });

    document.addEventListener('DOMContentLoaded', function() {
        AOS.init({ once: true, offset: 50, duration: 600, easing: 'ease-out' });
        
        // Матрица на фоне форума
        const canvas = document.getElementById('matrixCanvas');
        if (canvas) {
            const ctx = canvas.getContext('2d');
            function resizeCanvas() { canvas.width = canvas.parentElement.offsetWidth; canvas.height = canvas.parentElement.offsetHeight; }
            resizeCanvas(); window.addEventListener('resize', resizeCanvas);
            const alphabet = '01'; const fontSize = 16; const columns = canvas.width / fontSize; const drops = [];
            for(let x = 0; x < columns; x++) drops[x] = 1;
            function drawMatrix() {
                ctx.fillStyle = 'rgba(0, 0, 0, 0.05)'; ctx.fillRect(0, 0, canvas.width, canvas.height); ctx.fillStyle = '#0F0'; ctx.font = fontSize + 'px monospace';
                for(let i = 0; i < drops.length; i++) {
                    const text = alphabet.charAt(Math.floor(Math.random() * alphabet.length)); ctx.fillText(text, i * fontSize, drops[i] * fontSize);
                    if(drops[i] * fontSize > canvas.height && Math.random() > 0.975) drops[i] = 0; drops[i]++;
                }
            }
            setInterval(drawMatrix, 50);
        }
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
