<?php
// admin/layout_header.php
require_once __DIR__ . '/admin_auth.php';
$curAdminPage = basename($_SERVER['PHP_SELF']);
$csrfToken = getCsrfToken();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e($csrfToken) ?>">
    <title>SUPER ADMIN PANEL — UFFNV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/style.css?v=5">

    <style>
        body.admin-body {
            font-family: 'Arial', sans-serif;
            background: #f4f4f4;
            color: #000;
            display: block;
            overflow-x: clip;
        }
        .admin-sidebar {
            width: 280px;
            min-height: 100vh;
            background: #000;
            color: #fff;
            border-right: 5px solid #FCE300;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1050;
            flex-shrink: 0;
        }
        .admin-sidebar a {
            color: #e0e0e0;
            text-decoration: none;
            display: block;
            padding: 13px 16px;
            border-bottom: 1px solid #333;
            font-weight: bold;
            transition: all 0.2s;
        }
        .admin-sidebar a:hover, .admin-sidebar a.active {
            background: #FCE300;
            color: #000;
            padding-left: 24px;
        }
        .admin-sidebar .brand {
            padding: 20px;
            font-size: 1.5rem;
            color: #FCE300;
            border-bottom: 5px solid #FCE300;
            text-align: center;
        }
        .admin-sidebar-section {
            font-size: 0.72rem;
            letter-spacing: 1px;
            color: #b8bec5;
        }
        .admin-card {
            border: 3px solid #000;
            box-shadow: 6px 6px 0 rgba(0, 0, 0, 0.2);
            background: #fff;
            margin-bottom: 20px;
        }
        .admin-header {
            background: #000;
            color: #fff;
            padding: 15px;
            font-family: 'Arial Black', sans-serif;
            text-transform: uppercase;
        }
        .btn-admin { border: 2px solid #000; border-radius: 0; font-weight: bold; text-transform: uppercase; box-shadow: 3px 3px 0 #000; }
        .btn-admin:hover { transform: translate(-2px, -2px); box-shadow: 5px 5px 0 #000; }
        .btn-admin-primary { background: #FCE300; color: #000; }
        .btn-admin-danger { background: #dc3545; color: #fff; }
        .btn-admin-dark { background: #000; color: #fff; }
        .form-control, .form-select { border: 2px solid #000; border-radius: 0; }
        .form-control:focus, .form-select:focus { box-shadow: none; border-color: #FCE300; background: #fff9db; }
        .table-admin { border: 2px solid #000; }
        .table-admin th { background: #000; color: #fff; text-transform: uppercase; }
        .table-admin td { vertical-align: middle; border-bottom: 1px solid #ddd; }

        .mobile-toggle-btn {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            width: 50px;
            height: 50px;
            background: #000;
            border: 3px solid #FCE300;
            color: #FCE300;
            z-index: 2000;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 3px 3px 0 rgba(0, 0, 0, 0.3);
            font-size: 1.5rem;
        }
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1040;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
            backdrop-filter: blur(2px);
        }
        .sidebar-overlay.active { opacity: 1; visibility: visible; }
        @media (max-width: 991.98px) {
            .mobile-toggle-btn { display: flex; }
            .admin-sidebar {
                position: fixed;
                top: 0;
                left: -290px;
                height: 100vh;
                box-shadow: 5px 0 15px rgba(0, 0, 0, 0.5);
            }
            .admin-sidebar.show { left: 0; }
            .content-wrapper { padding-top: 80px !important; }
        }
    </style>
</head>
<body class="admin-body">

<div class="d-flex" style="min-height: 100vh;">
    <button type="button" class="mobile-toggle-btn" onclick="toggleSidebar()" aria-label="Открыть меню админки">
        <i class="bi bi-list" aria-hidden="true"></i>
    </button>
    <div class="sidebar-overlay" onclick="toggleSidebar()" id="sidebarOverlay"></div>

    <div class="admin-sidebar d-flex flex-column flex-shrink-0 p-0" id="adminSidebar">
        <div class="brand street-font">
            <i class="bi bi-shield-lock-fill" aria-hidden="true"></i> ADMIN
            <div class="mt-2 d-flex justify-content-center align-items-center gap-2" style="font-size: 0.72rem; font-family: monospace;">
                <span class="text-white-50">СЕССИЯ:</span>
                <span class="badge bg-black border border-warning text-warning rounded-0 admin-session-timer">--:--</span>
            </div>
        </div>

        <a href="/admin/index.php" class="<?= $curAdminPage === 'index.php' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2 me-2" aria-hidden="true"></i> Главная
        </a>

        <div class="text-uppercase fw-bold px-3 mt-4 mb-2 street-font admin-sidebar-section">Форум</div>
        <a href="/admin/forum_sections.php" class="<?= $curAdminPage === 'forum_sections.php' ? 'active' : '' ?>">
            <i class="bi bi-collection-fill me-2" aria-hidden="true"></i> Разделы
        </a>
        <a href="/admin/forum_categories.php" class="<?= $curAdminPage === 'forum_categories.php' ? 'active' : '' ?>">
            <i class="bi bi-folder-fill me-2" aria-hidden="true"></i> Категории
        </a>
        <a href="/admin/topics.php" class="<?= in_array($curAdminPage, ['topics.php', 'topic_edit.php'], true) ? 'active' : '' ?>">
            <i class="bi bi-chat-left-text-fill me-2" aria-hidden="true"></i> Темы
        </a>

        <div class="text-uppercase fw-bold px-3 mt-4 mb-2 street-font admin-sidebar-section">Магазин</div>
        <a href="/admin/shop_categories.php" class="<?= $curAdminPage === 'shop_categories.php' ? 'active' : '' ?>">
            <i class="bi bi-tags-fill me-2" aria-hidden="true"></i> Категории товаров
        </a>
        <a href="/admin/products.php" class="<?= in_array($curAdminPage, ['products.php', 'product_edit.php'], true) ? 'active' : '' ?>">
            <i class="bi bi-box-seam-fill me-2" aria-hidden="true"></i> Товары
        </a>
        <a href="/admin/orders.php" class="<?= in_array($curAdminPage, ['orders.php', 'order_view.php'], true) ? 'active' : '' ?>">
            <i class="bi bi-cart-check-fill me-2" aria-hidden="true"></i> Заказы
        </a>

        <div class="text-uppercase fw-bold px-3 mt-4 mb-2 street-font admin-sidebar-section">Система</div>
        <a href="/admin/users.php" class="<?= in_array($curAdminPage, ['users.php', 'user_edit.php'], true) ? 'active' : '' ?>">
            <i class="bi bi-people-fill me-2" aria-hidden="true"></i> Пользователи
        </a>
        <div class="mt-auto border-top border-secondary">
            <a href="/" class="bg-dark text-white">
                <i class="bi bi-globe2 me-2" aria-hidden="true"></i> На сайт
            </a>
            <a href="/admin/logout.php" class="bg-danger text-white border-top border-dark">
                <i class="bi bi-box-arrow-left me-2" aria-hidden="true"></i> Выйти
            </a>
        </div>
    </div>

    <div class="flex-grow-1 p-3 p-md-4 content-wrapper" style="min-height: 100vh; min-width: 0;">
    <?php if (!empty($_SESSION['flash'])): ?>
        <div class="alert alert-<?= e($_SESSION['flash_type'] ?? 'info') ?> border-2 border-dark rounded-0 fw-bold alert-dismissible fade show" role="alert">
            <?= e($_SESSION['flash']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Закрыть"></button>
        </div>
        <?php unset($_SESSION['flash'], $_SESSION['flash_type']); ?>
    <?php endif; ?>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('adminSidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.toggle('show');
            overlay.classList.toggle('active');
        }
    </script>
