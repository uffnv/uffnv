<?php
// admin/admin_auth.php — Изолированный сторож авторизации админ-панели (до вывода HTML)
require_once __DIR__ . '/../includes/functions.php';
initAppSession();
require_once __DIR__ . '/../config/db.php';

if (empty($_SESSION['user_id']) || !isAdmin()) {
    header("Location: /pages/login.php");
    exit;
}
