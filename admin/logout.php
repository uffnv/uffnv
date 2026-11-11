<?php
// admin/logout.php — Безопасное завершение сессии администратора
require_once __DIR__ . '/../includes/functions.php';
initAppSession();

$reason = $_GET['reason'] ?? '';
destroySessionAndCookie();

if (!headers_sent()) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}

$target = ($reason === 'timeout') ? '/pages/login.php?timeout=1' : '/pages/login.php?logged_out=1';

if (!headers_sent()) {
    header('Location: ' . $target);
} else {
    $safeTarget = htmlspecialchars($target, ENT_QUOTES, 'UTF-8');
    echo '<script>window.location.href="' . $safeTarget . '";</script>';
    echo '<meta http-equiv="refresh" content="0;url=' . $safeTarget . '">';
}
exit;
