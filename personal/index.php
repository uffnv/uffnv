<?php
// personal/index.php
require_once __DIR__ . '/../includes/functions.php';
initAppSession();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/recommender.php';

checkAuth('/pages/login.php');

$userId = (int)$_SESSION['user_id'];
$username = $_SESSION['username'] ?? '';
if ($username === '') {
    $uStmt = $pdo->prepare("SELECT username FROM users WHERE id = ? LIMIT 1");
    $uStmt->execute([$userId]);
    $username = (string)($uStmt->fetchColumn() ?: 'USER_' . $userId);
    $_SESSION['username'] = $username;
}

$recData = getUserRecommendations($pdo, $userId);
$recTopics = $recData['topics'] ?? [];
$recProducts = $recData['products'] ?? [];
$recIntel = $recData['external_intel'] ?? [];

$pageTitle = 'NEURAL_HUB';
include __DIR__ . '/../includes/header.php';
?>

<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap" rel="stylesheet">

<style>
    body {
        background-color: #000;
        background-image:
            linear-gradient(rgba(0, 5, 10, 0.92), rgba(0, 0, 0, 0.98)),
            radial-gradient(circle at 20% 20%, rgba(0, 255, 255, 0.08), transparent 40%);
        background-attachment: fixed;
        color: #e0e0e0;
        font-family: 'Arial', sans-serif;
    }
    .street-font { font-family: 'Arial Black', 'Impact', sans-serif; text-transform: uppercase; }
    .mono-font { font-family: 'Share Tech Mono', monospace; letter-spacing: 1px; }
    .section-header { border-left: 5px solid cyan; padding-left: 20px; margin-bottom: 30px; position: relative; }
    .section-header h2 { text-shadow: 0 0 10px rgba(0, 255, 255, 0.3); }
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
    .material-card { background: #0d0d0d; border: 1px solid #333; border-top: 3px solid #bc13fe; transition: 0.3s; }
    .material-card:hover { transform: scale(1.02); border-color: #bc13fe; box-shadow: 0 0 15px rgba(188, 19, 254, 0.3); }
    .product-card { background: rgba(20, 20, 0, 0.2); border: 1px solid #555; border-bottom: 3px solid #FCE300; position: relative; overflow: hidden; }
    .product-card .img-wrapper { height: 200px; overflow: hidden; position: relative; }
    .product-card img { transition: transform 0.5s; width: 100%; height: 100%; object-fit: cover; opacity: 0.85; }
    .product-card:hover img { transform: scale(1.1); opacity: 1; }
    .product-card:hover { box-shadow: 0 0 20px rgba(252, 227, 0, 0.2); border-color: #FCE300; }
    .btn-cyber-sm { background: transparent; border: 1px solid cyan; color: cyan; font-family: 'Share Tech Mono', monospace; font-size: 0.8rem; transition: 0.3s; text-transform: uppercase; text-decoration: none; }
    .btn-cyber-sm:hover { background: cyan; color: #000; box-shadow: 0 0 15px cyan; }
    .personal-hero { padding: 80px 0 50px 0; background: radial-gradient(circle at center, rgba(0, 255, 255, 0.03) 0%, transparent 60%); }
</style>

<div class="personal-hero text-center mb-4">
    <div class="container" data-aos="fade-down">
        <div class="d-inline-block border border-info px-3 py-1 mb-3 mono-font text-info rounded-pill" style="font-size: 0.75rem; letter-spacing: 2px;">
            ● SYSTEM_ONLINE
        </div>
        <h1 class="street-font display-4 text-white mb-2" style="letter-spacing: 3px;">
            NEURAL<span style="color: cyan;">_HUB</span>
        </h1>
        <p class="mono-font text-light opacity-75">
            // WELCOME USER: <span class="text-white"><?= e($username) ?></span><br>
            // ANALYZING PREFERENCES... [COMPLETE]
        </p>
    </div>
</div>

<div class="container pb-5">

    <!-- 1. ТЕМЫ -->
    <?php if (!empty($recTopics)): ?>
    <div class="mb-5">
        <div class="section-header" data-aos="fade-right">
            <h2 class="street-font text-white mb-0 fs-3">РЕКОМЕНДАЦИИ</h2>
            <div class="mono-font text-info small">&gt; BASED ON YOUR ACTIVITY</div>
        </div>
        <div class="row g-3">
            <?php foreach ($recTopics as $topic): ?>
            <div class="col-lg-6" data-aos="fade-up">
                <div class="topic-card p-3 p-sm-4 h-100 d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3">
                    <div class="min-w-0">
                        <div class="mb-2 text-info mono-font" style="font-size: 0.7rem;">
                            <span class="status-dot me-1"></span> LIVE THREAD
                        </div>
                        <h5 class="mono-font text-white fw-bold mb-1 text-uppercase text-break"><?= e($topic['title']) ?></h5>
                        <p class="text-light opacity-75 small mb-0">
                            #<?= (int)$topic['id'] ?> • <?= date('d.m.Y', strtotime($topic['created_at'])) ?>
                        </p>
                    </div>
                    <a href="/pages/topic.php?id=<?= (int)$topic['id'] ?>" class="btn-cyber-sm px-3 py-2 flex-shrink-0">
                        READ_LOG
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- 2. БАЗА ЗНАНИЙ -->
    <?php if (!empty($recIntel)): ?>
    <div class="mb-5">
        <div class="section-header" style="border-color: #bc13fe;" data-aos="fade-right">
            <h2 class="street-font text-white mb-0 fs-3">ВНЕШНИЙ ИНТЕЛЛЕКТ</h2>
            <div class="mono-font small" style="color: #e09eff;">&gt; CURATED_OPEN_SOURCES... [READY]</div>
        </div>
        <div class="row g-4">
            <?php foreach ($recIntel as $link): ?>
            <div class="col-md-6" data-aos="flip-up">
                <div class="material-card h-100 p-4 position-relative" style="background: linear-gradient(135deg, #0d0d0d 0%, #1a002a 100%);">
                    <div class="position-absolute top-0 end-0 m-3 mono-font text-light opacity-75 small" style="font-size: 0.65rem;">
                        SRC: <?= e(parse_url($link['url'], PHP_URL_HOST)) ?>
                    </div>
                    <h4 class="text-white street-font mb-2 pe-4" style="letter-spacing: 1px;"><?= e($link['title']) ?></h4>
                    <p class="text-light opacity-75 small mb-4 mono-font">
                        <?= e($link['desc']) ?>
                    </p>
                    <a href="<?= e(safeUrl($link['url'])) ?>" target="_blank" rel="noopener noreferrer" class="text-decoration-none fw-bold small mono-font" style="color: #e09eff;">
                        <span class="border border-secondary px-2 py-1 d-inline-block">OPEN_CONNECTION_URL</span>
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
            <div class="mono-font small text-warning">&gt; STORE_OFFERS</div>
        </div>
        <div class="row g-3 g-md-4">
            <?php foreach ($recProducts as $product):
                $pImg = !empty($product['image']) ? '/' . ltrim($product['image'], '/') : '';
            ?>
            <div class="col-md-3 col-6" data-aos="zoom-in">
                <div class="product-card h-100">
                    <div class="img-wrapper bg-black d-flex align-items-center justify-content-center">
                        <?php if ($pImg !== ''): ?>
                            <img src="<?= e($pImg) ?>" alt="<?= e($product['title']) ?>">
                        <?php else: ?>
                            <i class="bi bi-box-seam fs-1 text-light opacity-50" aria-hidden="true"></i>
                        <?php endif; ?>
                    </div>
                    <div class="p-2 p-sm-3">
                        <h6 class="text-white street-font text-truncate mb-2"><?= e($product['title']) ?></h6>
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <span class="text-warning mono-font fw-bold fs-6 fs-md-5"><?= number_format((float)$product['price'], 0, '.', ' ') ?> ₽</span>
                            <a href="/pages/product.php?id=<?= (int)$product['id'] ?>" class="btn btn-sm btn-outline-warning rounded-0 border-2 fw-bold">
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
    <?php if (empty($recTopics) && empty($recIntel) && empty($recProducts)): ?>
        <div class="text-center py-5 mono-font text-light opacity-75 border border-dark bg-black p-4 p-md-5">
            <i class="bi bi-cpu fs-1 mb-3 d-block text-danger" aria-hidden="true"></i>
            <h3 class="text-white">НЕДОСТАТОЧНО ДАННЫХ</h3>
            <p>&gt; Проявляйте активность на форуме, чтобы система подобрала рекомендации.</p>
            <a href="/" class="btn btn-outline-info rounded-0 mt-3">НА ГЛАВНУЮ</a>
        </div>
    <?php endif; ?>

</div>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ offset: 50, duration: 800, easing: 'ease-out-quad', once: true });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
