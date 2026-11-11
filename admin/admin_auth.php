<?php
// admin/admin_auth.php — Изолированный сторож авторизации и временной сессии админ-панели (по эталону mih)
require_once __DIR__ . '/../includes/functions.php';

if (!defined('ADMIN_SESSION_IDLE_LIMIT')) {
    define('ADMIN_SESSION_IDLE_LIMIT', 900); // 15 минут бездействия
}
if (!defined('ADMIN_SESSION_ABS_LIMIT')) {
    define('ADMIN_SESSION_ABS_LIMIT', 14400); // 4 часа максимум на одну сессию
}

// Запрет кеширования страниц админки (защита от кнопки «Назад» на общем ПК)
if (!headers_sent()) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');
    header('X-Frame-Options: SAMEORIGIN');
}

initAppSession();
require_once __DIR__ . '/../config/db.php';

/**
 * Полное уничтожение сессии и редирект / 401 ответ для AJAX
 */
function forceAdminSessionExit(string $redirectUrl = '/pages/login.php?timeout=1'): void {
    destroySessionAndCookie();

    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);

    if ($isAjax) {
        if (!headers_sent()) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode(['ok' => false, 'expired' => true, 'redirect' => $redirectUrl]);
        exit;
    }

    $safeRedirect = safeRedirectUrl($redirectUrl, '/pages/login.php');
    if (!headers_sent()) {
        header('Location: ' . $safeRedirect);
    } else {
        $safeHtml = htmlspecialchars($safeRedirect, ENT_QUOTES, 'UTF-8');
        echo '<script>window.location.href="' . $safeHtml . '";</script>';
        echo '<meta http-equiv="refresh" content="0;url=' . $safeHtml . '">';
    }
    exit;
}

// 1. Синхронизация роли с БД (мгновенный отзыв доступа у удалённого/разжалованного админа) + проверка роли
if (empty($_SESSION['user_id']) || !syncSessionUserRole($pdo) || !isAdmin()) {
    forceAdminSessionExit('/pages/login.php');
}

$now = time();
$currentUaHash = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');

// 2. Привязка сессии к User-Agent браузера
if (!empty($_SESSION['admin_ua_hash'])) {
    if (!hash_equals($_SESSION['admin_ua_hash'], $currentUaHash)) {
        forceAdminSessionExit('/pages/login.php?timeout=1');
    }
} else {
    $_SESSION['admin_ua_hash'] = $currentUaHash;
}

// 3. Проверка таймаута бездействия и максимального срока сессии
if (isset($_SESSION['admin_last_activity'])) {
    if (($now - (int)$_SESSION['admin_last_activity']) > ADMIN_SESSION_IDLE_LIMIT) {
        forceAdminSessionExit('/pages/login.php?timeout=1');
    }
}
if (isset($_SESSION['admin_login_time'])) {
    if (($now - (int)$_SESSION['admin_login_time']) > ADMIN_SESSION_ABS_LIMIT) {
        forceAdminSessionExit('/pages/login.php?timeout=1');
    }
} else {
    $_SESSION['admin_login_time'] = $now;
}

// 4. Продлеваем отметку активности (если не пассивный запрос)
if (!defined('ADMIN_PING_PASSIVE')) {
    $_SESSION['admin_last_activity'] = $now;
}

$admin_session_remaining = max(0, ADMIN_SESSION_IDLE_LIMIT - ($now - (int)$_SESSION['admin_last_activity']));
