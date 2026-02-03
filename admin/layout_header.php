<?php
// admin/layout_header.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';

// Проверка админа (Ваш оригинальный код)
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'super_admin')) {
    header("Location: /pages/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SUPER ADMIN PANEL</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: #f4f4f4;
            overflow-x: hidden; /* Скрываем гориз. скролл */
        }
        
        .street-font {
            font-family: 'Arial Black', 'Impact', sans-serif;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* --- SIDEBAR --- */
        .admin-sidebar {
            width: 280px;
            min-height: 100vh;
            background: #000;
            color: #fff;
            border-right: 5px solid #FCE300;
            /* Анимация для мобильных */
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1050;
            flex-shrink: 0;
        }
        .admin-sidebar a {
            color: #ccc;
            text-decoration: none;
            display: block;
            padding: 15px;
            border-bottom: 1px solid #333;
            font-weight: bold;
            transition: all 0.2s;
        }
        .admin-sidebar a:hover, .admin-sidebar a.active {
            background: #FCE300;
            color: #000;
            padding-left: 25px;
        }
        .admin-sidebar .brand {
            padding: 20px;
            font-size: 1.5rem;
            color: #FCE300;
            border-bottom: 5px solid #FCE300;
            text-align: center;
        }

        /* --- CONTENT STYLES --- */
        .admin-card {
            border: 3px solid #000;
            box-shadow: 6px 6px 0 rgba(0,0,0,0.2);
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
        
        /* --- BUTTONS & FORMS --- */
        .btn-admin { border: 2px solid #000; border-radius: 0; font-weight: bold; text-transform: uppercase; box-shadow: 3px 3px 0 #000; }
        .btn-admin:hover { transform: translate(-2px, -2px); box-shadow: 5px 5px 0 #000; }
        .btn-admin-primary { background: #FCE300; color: #000; }
        .btn-admin-danger { background: #dc3545; color: #fff; }
        .btn-admin-dark { background: #000; color: #fff; }

        .form-control, .form-select { border: 2px solid #000; border-radius: 0; }
        .form-control:focus { box-shadow: none; border-color: #FCE300; background: #fff9db; }
        
        .table-admin { border: 2px solid #000; }
        .table-admin th { background: #000; color: #fff; text-transform: uppercase; }
        .table-admin td { vertical-align: middle; border-bottom: 1px solid #ddd; }

        /* --- MOBILE ADAPTATION --- */
        .mobile-toggle-btn {
            display: none; /* Скрыта на ПК */
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
            box-shadow: 3px 3px 0 rgba(0,0,0,0.3);
            font-size: 1.5rem;
        }
        
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0,0,0,0.7);
            z-index: 1040;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
            backdrop-filter: blur(2px);
        }
        
        .sidebar-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        /* Только для экранов меньше 992px (планшеты и мобильные) */
        @media (max-width: 991.98px) {
            .mobile-toggle-btn {
                display: flex;
            }
            .admin-sidebar {
                position: fixed;
                top: 0;
                left: -290px; /* Скрыт за экраном */
                height: 100vh;
                box-shadow: 5px 0 15px rgba(0,0,0,0.5);
            }
            .admin-sidebar.show {
                left: 0; /* Выезжает */
            }
            /* Отступ контента сверху, чтобы текст не был под кнопкой */
            .content-wrapper {
                padding-top: 80px !important; 
            }
        }
    </style>
</head>
<body>

<div class="d-flex" style="min-height: 100vh;">
    
    <!-- MOBILE MENU BUTTON (SQUARE) -->
    <div class="mobile-toggle-btn" onclick="toggleSidebar()">
        <i class="bi bi-list"></i>
    </div>

    <!-- OVERLAY -->
    <div class="sidebar-overlay" onclick="toggleSidebar()" id="sidebarOverlay"></div>

    <!-- SIDEBAR -->
    <div class="admin-sidebar d-flex flex-column flex-shrink-0 p-0" id="adminSidebar">
        <div class="brand street-font">
            <i class="bi bi-shield-lock-fill"></i> ADMIN
        </div>
        
        <!-- Ссылки (восстановлены в соответствии с вашим оригиналом) -->
        <a href="/admin/index.php" class="<?= $_SERVER['PHP_SELF'] == '/admin/index.php' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2 me-2"></i> Главная
        </a>
        
        <div class="text-uppercase small text-muted fw-bold px-3 mt-4 mb-2 street-font">Форум</div>
        <a href="/admin/forum_sections.php" class="<?= strpos($_SERVER['PHP_SELF'], 'forum_sections') ? 'active' : '' ?>">
            <i class="bi bi-collection-fill me-2"></i> Разделы
        </a>
        <a href="/admin/forum_categories.php" class="<?= strpos($_SERVER['PHP_SELF'], 'forum_categories') ? 'active' : '' ?>">
            <i class="bi bi-folder-fill me-2"></i> Категории
        </a>
        <a href="/admin/topics.php" class="<?= strpos($_SERVER['PHP_SELF'], 'topics') ? 'active' : '' ?>">
            <i class="bi bi-chat-left-text-fill me-2"></i> Темы
        </a>

        <div class="text-uppercase small text-muted fw-bold px-3 mt-4 mb-2 street-font">Магазин</div>
        <a href="/admin/shop_categories.php" class="<?= strpos($_SERVER['PHP_SELF'], 'shop_categories') ? 'active' : '' ?>">
            <i class="bi bi-tags-fill me-2"></i> Категории товаров
        </a>
        <a href="/admin/products.php" class="<?= strpos($_SERVER['PHP_SELF'], 'products') ? 'active' : '' ?>">
            <i class="bi bi-box-seam-fill me-2"></i> Товары
        </a>
        <a href="/admin/orders.php" class="<?= strpos($_SERVER['PHP_SELF'], 'orders') ? 'active' : '' ?>">
            <i class="bi bi-cart-check-fill me-2"></i> Заказы
        </a>

        <div class="text-uppercase small text-muted fw-bold px-3 mt-4 mb-2 street-font">Система</div>
        <a href="/admin/users.php" class="<?= strpos($_SERVER['PHP_SELF'], 'users') ? 'active' : '' ?>">
            <i class="bi bi-people-fill me-2"></i> Пользователи
        </a>
        <a href="/" class="mt-auto bg-dark text-white border-top border-secondary">
            <i class="bi bi-box-arrow-left me-2"></i> На сайт
        </a>
    </div>

    <!-- CONTENT WRAPPER -->
    <div class="flex-grow-1 p-4 content-wrapper" style="max-height: 100vh; overflow-y: auto; width: 100%;">

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('adminSidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.toggle('show');
            overlay.classList.toggle('active');
        }
    </script>
