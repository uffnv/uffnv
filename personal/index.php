<?php
// personal/index.php
session_start();

// Подключаем конфиг БД
require_once '../config/db.php';

// Подключаем наш новый файл рекомендаций (лежит рядом)
require_once 'recommender.php'; 

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: ../pages/login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'GUEST_SYS';

// --- ЗАПУСКАЕМ ФУНКЦИЮ РЕКОМЕНДАЦИЙ ---
$recData = getUserRecommendations($pdo, $userId);

$recTopics = $recData['topics'];
$recProducts = $recData['products'];
$recMaterials = $recData['materials'];

// --- ХЕДЕР ---
ob_start();
include '../includes/header.php';
$headerContent = ob_get_clean();
// Фикс путей для активов, т.к. мы в подпапке
echo str_replace('"assets/', '"../assets/', $headerContent);
?>

<!-- AOS & FONTS -->
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap" rel="stylesheet">

<style>
    /* === КИБЕР-СТИЛИ === */
    body {
        background-color: #000;
        /* Темный техно-фон */
        background-image: 
            linear-gradient(rgba(0, 5, 10, 0.92), rgba(0, 0, 0, 0.98)),
            url('https://images.unsplash.com/photo-1518770660439-4636190af475?q=80&w=2070');
        background-attachment: fixed;
        background-size: cover;
        color: #e0e0e0;
        font-family: 'Arial', sans-serif;
    }

    /* Шрифты */
    .street-font { font-family: 'Arial Black', 'Impact', sans-serif; text-transform: uppercase; }
    .mono-font { font-family: 'Share Tech Mono', monospace; letter-spacing: 1px; }

    /* Заголовки секций */
    .section-header {
        border-left: 5px solid cyan;
        padding-left: 20px;
        margin-bottom: 30px;
        position: relative;
    }
    /* Свечение заголовка */
    .section-header h2 { text-shadow: 0 0 10px rgba(0, 255, 255, 0.3); }

    /* === 1. TOPICS (ТЕРМИНАЛ) === */
    .topic-card {
        background: rgba(0, 20, 20, 0.6);
        border: 1px solid #004444;
        transition: all 0.3s ease;
        position: relative;
        backdrop-filter: blur(5px);
    }
    .topic-card:hover {
        border-color: cyan;
        box-shadow: 0 0 25px rgba(0, 255, 255, 0.15);
        transform: translateY(-5px);
        background: rgba(0, 30, 30, 0.8);
    }
    .status-dot { height: 8px; width: 8px; background-color: #0f0; border-radius: 50%; display: inline-block; box-shadow: 0 0 5px #0f0; }

    /* === 2. MATERIALS (DATA CHIP) === */
    .material-card {
        background: #0d0d0d;
        border: 1px solid #333;
        border-top: 3px solid #bc13fe; 
        transition: 0.3s;
    }
    .material-card:hover {
        transform: scale(1.02);
        border-color: #bc13fe;
        box-shadow: 0 0 15px rgba(188, 19, 254, 0.3);
    }

    /* === 3. PRODUCTS (LOOTBOX) === */
    .product-card {
        background: rgba(20, 20, 0, 0.2);
        border: 1px solid #555;
        border-bottom: 3px solid #FCE300; 
        position: relative;
        overflow: hidden;
    }
    .product-card .img-wrapper { height: 200px; overflow: hidden; position: relative; }
    .product-card img { transition: transform 0.5s; width: 100%; height: 100%; object-fit: cover; opacity: 0.8; }
    .product-card:hover img { transform: scale(1.1); opacity: 1; }
    .product-card:hover { box-shadow: 0 0 20px rgba(252, 227, 0, 0.2); border-color: #FCE300; }

    /* Кнопки */
    .btn-cyber-sm {
        background: transparent; border: 1px solid cyan; color: cyan;
        font-family: 'Share Tech Mono', monospace; font-size: 0.8rem;
        transition: 0.3s; text-transform: uppercase;
    }
    .btn-cyber-sm:hover { background: cyan; color: #000; box-shadow: 0 0 15px cyan; }

    /* Hero */
    .personal-hero {
        padding: 100px 0 60px 0;
        background: radial-gradient(circle at center, rgba(0, 255, 255, 0.03) 0%, transparent 60%);
    }
    .glitch-load { animation: glitch-anim 2s infinite; }
    @keyframes glitch-anim {
        0% { opacity: 1; } 50% { opacity: 0.8; } 52% { opacity: 0.2; } 54% { opacity: 0.8; } 100% { opacity: 1; }
    }
</style>

<!-- HEADER SECTION -->
<div class="personal-hero text-center mb-4">
    <div class="container" data-aos="fade-down">
        <div class="d-inline-block border border-info px-3 py-1 mb-3 mono-font text-info rounded-pill" style="font-size: 0.75rem; letter-spacing: 2px;">
            ● SYSTEM_ONLINE
        </div>
        <h1 class="street-font display-4 text-white mb-2" style="letter-spacing: 3px;">
            NEURAL<span style="color: cyan;">_HUB</span>
        </h1>
        <p class="mono-font text-secondary glitch-load">
            // WELCOME USER: <span class="text-white"><?= htmlspecialchars($username) ?></span><br>
            // ANALYZING PREFERENCES... [COMPLETE]
        </p>
    </div>
</div>

<div class="container pb-5">

    <!-- 1. ТЕМЫ (ТЕРМИНАЛ) -->
    <?php if (!empty($recTopics)): ?>
    <div class="mb-5">
        <div class="section-header" data-aos="fade-right">
            <h2 class="street-font text-white mb-0 fs-3">РЕКОМЕНДАЦИИ</h2>
            <div class="mono-font text-info small">> BASED ON YOUR ACTIVITY</div>
        </div>
        <div class="row g-3">
            <?php foreach ($recTopics as $topic): ?>
            <div class="col-lg-6" data-aos="fade-up">
                <div class="topic-card p-4 h-100 d-flex justify-content-between align-items-center">
                    <div>
                        <div class="mb-2 text-info mono-font" style="font-size: 0.7rem;">
                            <span class="status-dot me-1"></span> LIVE THREAD
                        </div>
                        <h5 class="mono-font text-white fw-bold mb-1 text-uppercase"><?= htmlspecialchars($topic['title']) ?></h5>
                        <p class="text-white-50 small mb-0">
                            #<?= $topic['id'] ?> • <?= date('d.m.Y', strtotime($topic['created_at'])) ?>
                        </p>
                    </div>
                    <a href="../pages/topic.php?id=<?= $topic['id'] ?>" class="btn-cyber-sm px-3 py-2 ms-3">
                        READ_LOG
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- 2. БАЗА ЗНАНИЙ -->
    <?php if (!empty($recMaterials)): ?>
    <div class="mb-5">
        <div class="section-header" style="border-color: #bc13fe;" data-aos="fade-right">
            <h2 class="street-font text-white mb-0 fs-3">АПГРЕЙД НАВЫКОВ</h2>
            <div class="mono-font small" style="color: #bc13fe;">> SKILL_DATABASE_ACCESS</div>
        </div>
        <div class="row g-4">
            <?php foreach ($recMaterials as $material): ?>
            <div class="col-md-6" data-aos="flip-up">
                <div class="material-card h-100 p-4 position-relative">
                    <i class="bi bi-chip position-absolute top-0 end-0 m-3 fs-3 text-white-50"></i>
                    <h4 class="text-white street-font mb-3"><?= htmlspecialchars($material['title']) ?></h4>
                    <p class="text-secondary small mb-4 mono-font" style="min-height: 40px;">
                        <?= htmlspecialchars($material['description']) ?>
                    </p>
                    <a href="<?= htmlspecialchars($material['content_url']) ?>" class="text-decoration-none text-white fw-bold small mono-font">
                        <i class="bi bi-download me-2"></i> ЗАГРУЗИТЬ ДАННЫЕ
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- 3. СНАРЯЖЕНИЕ -->
    <?php if (!empty($recProducts)): ?>
    <div class="mb-5">
        <div class="section-header" style="border-color: #FCE300;" data-aos="fade-right">
            <h2 class="street-font text-white mb-0 fs-3">РЕДКИЙ ЛУТ</h2>
            <div class="mono-font small text-warning">> BLACK_MARKET_OFFERS</div>
        </div>
        <div class="row g-4">
            <?php foreach ($recProducts as $product): ?>
            <div class="col-md-3 col-6" data-aos="zoom-in">
                <div class="product-card h-100">
                    <div class="img-wrapper bg-black d-flex align-items-center justify-content-center">
                        <?php if(!empty($product['image_url'])): ?>
                            <img src="../assets/uploads/<?= htmlspecialchars($product['image_url']) ?>" alt="img">
                        <?php else: ?>
                            <i class="bi bi-box-seam fs-1 text-secondary"></i>
                        <?php endif; ?>
                    </div>
                    <div class="p-3">
                        <h6 class="text-white street-font text-truncate mb-2"><?= htmlspecialchars($product['name'] ?? $product['title']) ?></h6>
                        <div class="d-flex justify-content-between align-items-end">
                            <span class="text-warning mono-font fw-bold fs-5"><?= number_format($product['price'], 0, '.', ' ') ?>₽</span>
                            <a href="../pages/product.php?id=<?= $product['id'] ?>" class="btn btn-sm btn-outline-warning rounded-0 border-2 fw-bold">
                                BUY
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ЕСЛИ ПУСТО -->
    <?php if(empty($recTopics) && empty($recMaterials) && empty($recProducts)): ?>
        <div class="text-center py-5 mono-font text-secondary border border-dark bg-black p-5">
            <i class="bi bi-cpu fs-1 mb-3 d-block text-danger"></i>
            <h3 class="text-white">НЕДОСТАТОЧНО ДАННЫХ</h3>
            <p>> Проявляйте активность на форуме, чтобы алгоритм изучил вас.</p>
            <a href="../index.php" class="btn btn-outline-info rounded-0 mt-3">НА ГЛАВНУЮ</a>
        </div>
    <?php endif; ?>

</div>

<!-- SCRIPTS -->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ offset: 50, duration: 800, easing: 'ease-out-quad', once: true });
</script>

<?php 
// FOOTER
ob_start();
include '../includes/footer.php';
$footerContent = ob_get_clean();
echo str_replace('"assets/', '"../assets/', $footerContent);
?>
