<?php
require_once __DIR__ . '/functions.php';
initAppSession();
if (!isset($pdo)) {
    require_once __DIR__ . '/../config/db.php';
}

$cartCount = getCartCount($pdo);
$csrfToken = getCsrfToken();

$notifCount = 0;
$notifications = [];
if (!empty($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$_SESSION['user_id']]);
        $notifCount = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
        $stmt->execute([$_SESSION['user_id']]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (\Throwable $e) {
        error_log("Header notifications error: " . $e->getMessage());
    }
}
$curPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e($csrfToken) ?>">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' — UFFNV STORE' : 'UFFNV STORE' ?></title>

    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/style.css?v=4">

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<header class="street-header fade-in-down">
    <div class="container">
        <nav class="navbar navbar-expand-lg p-0" aria-label="Основная навигация">
            <!-- ЛОГОТИП -->
            <a class="header-logo-sticker me-lg-4" href="/" aria-label="UFFNV Главная">
                <h2 class="header-logo-text">UFFNV<span>.</span></h2>
            </a>

            <!-- МОБИЛЬНЫЕ КНОПКИ -->
            <div class="d-flex d-lg-none align-items-center gap-2">
                <a href="/pages/cart.php" class="btn-icon-street position-relative" style="margin-left:0;" aria-label="Корзина">
                    <i class="bi bi-bag-fill" aria-hidden="true"></i>
                    <?php if ($cartCount > 0): ?><span class="cart-badge"><?= $cartCount ?></span><?php endif; ?>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Открыть меню">
                    <i class="bi bi-list fs-2" aria-hidden="true"></i>
                </button>
            </div>

            <!-- МЕНЮ -->
            <div class="collapse navbar-collapse mt-3 mt-lg-0" id="navbarNav">
                <ul class="navbar-nav me-auto align-items-lg-center">
                    <li class="nav-item"><a class="nav-link-street <?= ($curPage === 'index.php') ? 'active' : '' ?>" href="/">Главная</a></li>
                    <li class="nav-item"><a class="nav-link-street <?= ($curPage === 'catalog.php' && !isset($_GET['sale'])) ? 'active' : '' ?>" href="/pages/catalog.php">Каталог</a></li>
                    <li class="nav-item"><a class="nav-link-street <?= in_array($curPage, ['topics_list.php', 'topic.php', 'create_topic.php', 'search_topics.php'], true) ? 'active' : '' ?>" href="/pages/topics_list.php">Форум</a></li>
                    <li class="nav-item">
                        <a class="nav-link-street text-danger <?= (isset($_GET['sale']) && $_GET['sale'] === '1') ? 'active' : '' ?>" href="/pages/catalog.php?sale=1">
                            <i class="bi bi-fire me-1" aria-hidden="true"></i> SALE
                        </a>
                    </li>
                </ul>

                <!-- ДЕСКТОПНЫЕ ИКОНКИ -->
                <div class="d-none d-lg-flex align-items-center">
                    <?php if (!empty($_SESSION['user_id'])): ?>
                        <div class="dropdown">
                            <a href="#" class="btn-icon-street position-relative" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-label="Уведомления">
                                <i class="bi bi-bell-fill" aria-hidden="true"></i>
                                <?php if ($notifCount > 0): ?><span class="cart-badge bg-danger"><?= $notifCount ?></span><?php endif; ?>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end notif-dropdown-menu">
                                <div class="notif-header d-flex justify-content-between">
                                    <span>События</span>
                                    <?php if ($notifCount > 0): ?><span class="badge bg-danger rounded-0"><?= $notifCount ?> новых</span><?php endif; ?>
                                </div>
                                <?php if (empty($notifications)): ?>
                                    <div class="p-4 text-center text-white-50 fw-bold"><i class="bi bi-check-circle fs-3 d-block mb-1 opacity-50" aria-hidden="true"></i>Тихо...</div>
                                <?php else: ?>
                                    <?php foreach ($notifications as $notif): ?>
                                        <a href="<?= e(safeUrl($notif['link'], '#')) ?>" class="notif-item <?= !empty($notif['is_read']) ? 'read' : 'unread' ?>" onclick="markAsRead(<?= (int)$notif['id'] ?>)">
                                            <div class="d-flex justify-content-between mb-1">
                                                <small class="fw-bold text-uppercase text-white-50" style="font-size: 0.65rem;"><?= date('d.m H:i', strtotime($notif['created_at'])) ?></small>
                                            </div>
                                            <div style="line-height: 1.2; font-size: 0.85rem; font-weight: 700;"><?= e($notif['message']) ?></div>
                                        </a>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <div class="notif-footer"><a href="/pages/notifications.php">Все уведомления →</a></div>
                            </div>
                        </div>
                        <a href="/pages/profile.php" class="btn-icon-street" title="Профиль" aria-label="Профиль"><i class="bi bi-person-fill" aria-hidden="true"></i></a>
                    <?php else: ?>
                        <a href="/pages/login.php" class="nav-link-street border-2 border-white ms-3 text-center">ВОЙТИ</a>
                    <?php endif; ?>
                    <a href="/pages/cart.php" class="btn-icon-street position-relative" title="Корзина" aria-label="Корзина">
                        <i class="bi bi-bag-fill" aria-hidden="true"></i>
                        <?php if ($cartCount > 0): ?><span class="cart-badge"><?= $cartCount ?></span><?php endif; ?>
                    </a>
                </div>

                <!-- МОБИЛЬНОЕ МЕНЮ -->
                <div class="d-lg-none mt-3 border-top border-secondary pt-3">
                    <?php if (!empty($_SESSION['user_id'])): ?>
                        <a href="/pages/notifications.php" class="nav-link-street mb-2">
                            УВЕДОМЛЕНИЯ <?php if ($notifCount > 0): ?><span class="badge bg-danger ms-2"><?= $notifCount ?></span><?php endif; ?>
                        </a>
                        <a href="/pages/profile.php" class="nav-link-street mb-2">МОЙ ПРОФИЛЬ</a>
                        <form action="/actions/logout.php" method="POST" class="m-0">
                            <?= csrfField() ?>
                            <button type="submit" class="nav-link-street text-warning text-start bg-transparent border-0">ВЫЙТИ</button>
                        </form>
                    <?php else: ?>
                        <a href="/pages/login.php" class="nav-link-street">ВОЙТИ В АККАУНТ</a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
    </div>
</header>

<script>
function markAsRead(id) {
    if (!id) return;
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const token = csrfMeta ? csrfMeta.getAttribute('content') : '';
    const data = new FormData();
    data.append('id', id);
    if (token) data.append('csrf_token', token);
    if (navigator.sendBeacon) {
        navigator.sendBeacon('/actions/mark_read.php', data);
    } else {
        fetch('/actions/mark_read.php', { method: 'POST', body: data });
    }
}
</script>
