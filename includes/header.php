<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UFFNV STORE</title>
    
    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <style>
        /* === ОБЩИЕ СТИЛИ === */
        body { font-family: 'Arial', sans-serif; overflow-x: hidden; background: #120024; }
        :root { --street-yellow: #FCE300; --street-dark: #121212; }

        /* АНИМАЦИЯ ПОЯВЛЕНИЯ */
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fade-in-down { animation: fadeInDown 0.6s ease-out forwards; }

        /* ХЕДЕР (ТЕМНЫЙ) */
        .street-header {
            background-color: var(--street-dark);
            border-bottom: 3px solid var(--street-yellow);
            padding: 10px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 10px 20px rgba(0,0,0,0.5);
        }

        /* ЛОГОТИП */
        .header-logo-sticker {
            background-color: #000; color: #fff !important;
            text-decoration: none; display: inline-flex; align-items: center; justify-content: center;
            padding: 8px 15px; transform: rotate(-2deg);
            border: 2px solid var(--street-yellow);
            box-shadow: 0 0 10px rgba(252, 227, 0, 0.2);
            transition: transform 0.2s; margin-right: 2rem;
        }
        .header-logo-sticker:hover { transform: rotate(0deg) scale(1.05); box-shadow: 0 0 15px rgba(252, 227, 0, 0.6); }
        .header-logo-text {
            font-family: 'Impact', sans-serif; font-size: 1.8rem;
            line-height: 0.8; letter-spacing: 1px; text-transform: uppercase; margin: 0;
        }
        .header-logo-text span { color: var(--street-yellow); }

        /* НАВИГАЦИЯ */
        .nav-link-street {
            color: #fff; font-weight: 900; text-transform: uppercase; text-decoration: none;
            padding: 8px 12px; margin: 0 2px; border: 2px solid transparent;
            transition: all 0.2s; font-size: 0.85rem; display: inline-block;
        }

        /* Эффекты десктоп */
        @media (min-width: 992px) {
            .nav-link-street:hover, .nav-link-street.active {
                background-color: var(--street-yellow); border-color: var(--street-yellow);
                color: #000; box-shadow: 0 0 10px var(--street-yellow); transform: skew(-10deg);
            }
            .nav-link-street.text-danger:hover, .nav-link-street.text-danger.active {
                background-color: #dc3545; color: #fff !important; border-color: #dc3545; box-shadow: 0 0 10px #dc3545;
            }
        }

        /* Эффекты мобильные */
        @media (max-width: 991px) {
            .nav-link-street { display: block; margin-bottom: 5px; border-bottom: 1px solid #333; width: 100%; }
            .nav-link-street:hover, .nav-link-street.active {
                background-color: var(--street-yellow); color: #000; border-color: var(--street-yellow); padding-left: 20px;
            }
            .header-logo-sticker { margin-right: auto; transform: scale(0.9) rotate(-2deg); }
        }

        /* КНОПКИ */
        .btn-icon-street {
            width: 42px; height: 42px; display: inline-flex; align-items: center; justify-content: center;
            background: #000; border: 2px solid #fff; color: #fff;
            font-size: 1.1rem; text-decoration: none; margin-left: 8px; transition: all 0.2s;
        }
        .btn-icon-street:hover, .btn-icon-street.show {
            background: var(--street-yellow); border-color: var(--street-yellow);
            color: #000; box-shadow: 0 0 15px var(--street-yellow); transform: translateY(-2px);
        }

        /* БЕЙДЖИ */
        .cart-badge {
            position: absolute; top: -6px; right: -6px;
            background-color: var(--street-yellow); color: #000;
            font-size: 0.65rem; font-weight: 900; width: 20px; height: 20px;
            display: flex; align-items: center; justify-content: center; border: 2px solid #000;
        }
        .cart-badge.bg-danger { background-color: #dc3545 !important; color: #fff; border-color: #fff; }

        /* ГАМБУРГЕР */
        .navbar-toggler { border: 2px solid var(--street-yellow) !important; border-radius: 0; padding: 4px; background: #000; }
        .navbar-toggler i { color: var(--street-yellow); }
        .navbar-toggler:focus { box-shadow: 0 0 10px var(--street-yellow); }

        /* УВЕДОМЛЕНИЯ */
        .notif-dropdown-menu {
            width: 320px; padding: 0; border: 2px solid var(--street-yellow); border-radius: 0;
            margin-top: 15px !important; box-shadow: 0 10px 30px rgba(0,0,0,1); background: #1a1a1a;
        }
        .notif-dropdown-menu::before {
            content: ''; position: absolute; top: -10px; right: 15px;
            border-left: 10px solid transparent; border-right: 10px solid transparent; border-bottom: 10px solid var(--street-yellow);
        }
        .notif-header {
            background: var(--street-yellow); color: #000; padding: 10px 15px;
            font-weight: 900; text-transform: uppercase; font-size: 0.8rem; border-bottom: 2px solid #000;
        }
        .notif-item {
            padding: 12px; border-bottom: 1px solid #333; color: #fff; text-decoration: none;
            display: block; transition: 0.2s; position: relative; background: #1a1a1a;
        }
        .notif-item:hover { background: #333; color: var(--street-yellow); padding-left: 18px; }
        .notif-item.unread { background: #2a2a2a; border-left: 4px solid var(--street-yellow); }
        .notif-item.unread::after {
            content: ''; position: absolute; top: 12px; right: 12px;
            width: 8px; height: 8px; background: #dc3545; border-radius: 50%;
        }
        .notif-footer { background: #000; padding: 10px; text-align: center; border-top: 1px solid var(--street-yellow); }
        .notif-footer a { color: var(--street-yellow); text-decoration: none; font-weight: 900; text-transform: uppercase; font-size: 0.75rem; }
        .street-header .dropdown-menu { border-radius: 0; }
    </style>
</head>
<body>

<?php
// Подсчет товаров
$cartCount = 0;
if (isset($_SESSION['cart'])) { foreach ($_SESSION['cart'] as $qty) $cartCount += $qty; }

// УВЕДОМЛЕНИЯ
$notifCount = 0; $notifications = [];
if (isset($_SESSION['user_id'])) {
    if (!isset($pdo)) require_once __DIR__ . '/../config/db.php';
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$_SESSION['user_id']]);
    $notifCount = $stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$_SESSION['user_id']]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
$curPage = basename($_SERVER['PHP_SELF']);
?>

<header class="street-header fade-in-down">
    <div class="container">
        <nav class="navbar navbar-expand-lg p-0">
            <!-- ЛОГОТИП -->
            <a class="header-logo-sticker me-lg-4" href="/">
                <h2 class="header-logo-text">UFFNV<span>.</span></h2>
            </a>

            <!-- МОБИЛЬНЫЕ КНОПКИ -->
            <div class="d-flex d-lg-none align-items-center gap-2">
                <a href="/pages/cart.php" class="btn-icon-street position-relative" style="margin-left:0;">
                    <i class="bi bi-bag-fill"></i>
                    <?php if($cartCount > 0): ?><span class="cart-badge"><?= $cartCount ?></span><?php endif; ?>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <i class="bi bi-list fs-2"></i>
                </button>
            </div>

            <!-- МЕНЮ -->
            <div class="collapse navbar-collapse mt-3 mt-lg-0" id="navbarNav">
                <ul class="navbar-nav me-auto align-items-lg-center">
                    <li class="nav-item"><a class="nav-link-street <?= ($curPage == 'index.php') ? 'active' : '' ?>" href="/">Главная</a></li>
                    <li class="nav-item"><a class="nav-link-street <?= ($curPage == 'catalog.php' && !isset($_GET['sale'])) ? 'active' : '' ?>" href="/pages/catalog.php">Каталог</a></li>
                    <li class="nav-item"><a class="nav-link-street <?= ($curPage == 'topics_list.php' || $curPage == 'forum.php' || $curPage == 'topic.php') ? 'active' : '' ?>" href="/pages/topics_list.php">Форум</a></li>
                    <li class="nav-item">
                        <a class="nav-link-street text-danger <?= (isset($_GET['sale']) && $_GET['sale'] == '1') ? 'active' : '' ?>" href="/pages/catalog.php?sale=1">
                            <i class="bi bi-fire me-1"></i> SALE
                        </a>
                    </li>
                </ul>

                <!-- ДЕСКТОПНЫЕ ИКОНКИ -->
                <div class="d-none d-lg-flex align-items-center">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <div class="dropdown">
                            <a href="#" class="btn-icon-street position-relative" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                                <i class="bi bi-bell-fill"></i>
                                <?php if($notifCount > 0): ?><span class="cart-badge bg-danger"><?= $notifCount ?></span><?php endif; ?>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end notif-dropdown-menu">
                                <div class="notif-header d-flex justify-content-between">
                                    <span>События</span>
                                    <?php if($notifCount > 0): ?><span class="badge bg-danger rounded-0"><?= $notifCount ?> новых</span><?php endif; ?>
                                </div>
                                <?php if (empty($notifications)): ?>
                                    <div class="p-4 text-center text-muted fw-bold"><i class="bi bi-check-circle fs-3 d-block mb-1 opacity-25"></i>Тихо...</div>
                                <?php else: ?>
                                    <?php foreach($notifications as $notif): ?>
                                        <a href="<?= $notif['link'] ?: '#' ?>" class="notif-item <?= $notif['is_read'] ? 'read' : 'unread' ?>" onclick="markAsRead(<?= $notif['id'] ?>)">
                                            <div class="d-flex justify-content-between mb-1">
                                                <small class="fw-bold text-uppercase text-secondary" style="font-size: 0.6rem;"><?= date('d.m H:i', strtotime($notif['created_at'])) ?></small>
                                            </div>
                                            <div style="line-height: 1.2; font-size: 0.85rem; font-weight: 700;"><?= htmlspecialchars($notif['message']) ?></div>
                                        </a>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <div class="notif-footer"><a href="/pages/notifications.php">Все уведомления →</a></div>
                            </div>
                        </div>
                        <a href="/pages/profile.php" class="btn-icon-street" title="Профиль"><i class="bi bi-person-fill"></i></a>
                    <?php else: ?>
                        <a href="/pages/login.php" class="nav-link-street border-2 border-white ms-3 text-center">ВОЙТИ</a>
                    <?php endif; ?>
                    <a href="/pages/cart.php" class="btn-icon-street position-relative" title="Корзина">
                        <i class="bi bi-bag-fill"></i>
                        <?php if($cartCount > 0): ?><span class="cart-badge"><?= $cartCount ?></span><?php endif; ?>
                    </a>
                </div>
                
                <!-- МОБИЛЬНОЕ МЕНЮ (СПИСОК) -->
                <div class="d-lg-none mt-3 border-top border-secondary pt-3">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <a href="/pages/notifications.php" class="nav-link-street mb-2">
                            УВЕДОМЛЕНИЯ <?php if($notifCount > 0): ?><span class="badge bg-danger ms-2"><?= $notifCount ?></span><?php endif; ?>
                        </a>
                        <a href="/pages/profile.php" class="nav-link-street mb-2">МОЙ ПРОФИЛЬ</a>
                        <a href="/actions/logout.php" class="nav-link-street text-secondary">ВЫЙТИ</a>
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
    if(!id) return;
    if (navigator.sendBeacon) { let data = new FormData(); data.append('id', id); navigator.sendBeacon('/actions/mark_read.php', data); }
    else { fetch('/actions/mark_read.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: 'id=' + id }); }
}
</script>
