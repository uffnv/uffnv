<?php
// includes/functions.php

/**
 * Инициализация защищённой PHP-сессии (вызывать до любого вывода HTML)
 */
function initAppSession(): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/**
 * Защита от Session Fixation при входе/регистрации
 */
function regenerateAppSession(): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

/**
 * Безопасное HTML-экранирование
 */
function e($data): string {
    return htmlspecialchars((string)($data ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Обратная совместимость со старым именем clean()
 */
function clean($data): string {
    return htmlspecialchars(trim((string)($data ?? '')), ENT_QUOTES, 'UTF-8');
}

/**
 * Многобайтовая первая буква в верхнем регистре (UTF-8 safe)
 */
function safeFirstChar(?string $str, string $fallback = '?'): string {
    $trimmed = trim((string)$str);
    if ($trimmed === '') {
        return $fallback;
    }
    return mb_strtoupper(mb_substr($trimmed, 0, 1, 'UTF-8'), 'UTF-8');
}

/**
 * Получение / генерация CSRF-токена сессии
 */
function getCsrfToken(): string {
    initAppSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Скрытое поле формы для CSRF-защиты
 */
function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(getCsrfToken()) . '">';
}

/**
 * Проверка CSRF-токена (POST / заголовок X-CSRF-Token)
 */
function verifyCsrfToken(?string $token = null): bool {
    initAppSession();
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    if ($sessionToken === '') {
        return false;
    }
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    }
    if (!is_string($token) || $token === '') {
        return false;
    }
    return hash_equals($sessionToken, $token);
}

/**
 * Валидация CSRF для обработчиков (редирект или JSON 403 при ошибке)
 */
function requireCsrfOrRedirect(string $redirectUrl = '/', bool $json = false): void {
    if (verifyCsrfToken()) {
        return;
    }
    if ($json) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'error' => 'Неверный CSRF-токен. Обновите страницу.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    redirectWithFlash($redirectUrl, 'Ошибка безопасности (неверный CSRF-токен). Обновите страницу и повторите действие.', 'danger');
}

/**
 * Фильтрация URL для ссылок (блокирует javascript:, data:, vbscript:)
 */
function safeUrl(?string $url, string $fallback = '#'): string {
    $url = trim((string)$url);
    if ($url === '') {
        return $fallback;
    }
    if ($url[0] === '/' && ($url === '/' || ($url[1] ?? '') !== '/')) {
        return $url;
    }
    if ($url[0] === '#') {
        return $url;
    }
    if (preg_match('#^https?://#i', $url)) {
        return $url;
    }
    return $fallback;
}

/**
 * Безопасный редирект (исключает Open Redirect на чужой домен и //evil.com)
 */
function safeRedirectUrl(?string $url, string $default = '/'): string {
    $url = trim((string)$url);
    if ($url === '') {
        return $default;
    }
    $url = str_replace(["\r", "\n", "\0"], '', $url);
    if ($url[0] === '/' && ($url === '/' || ($url[1] ?? '') !== '/')) {
        return $url;
    }
    $parts = @parse_url($url);
    if ($parts === false) {
        return $default;
    }
    $currentHost = $_SERVER['HTTP_HOST'] ?? '';
    if (!empty($parts['host']) && strcasecmp($parts['host'], $currentHost) === 0) {
        $path = $parts['path'] ?? '/';
        if ($path === '' || $path[0] !== '/') {
            $path = '/' . $path;
        }
        $query = isset($parts['query']) ? '?' . $parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';
        return $path . $query . $fragment;
    }
    return $default;
}

/**
 * Проверка, авторизован ли пользователь
 */
function checkAuth(string $redirectTo = '/pages/login.php'): void {
    initAppSession();
    if (empty($_SESSION['user_id'])) {
        header("Location: " . safeRedirectUrl($redirectTo, '/pages/login.php'));
        exit;
    }
}

/**
 * Проверка прав администратора (admin и super_admin)
 */
function isAdmin(): bool {
    return isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'super_admin'], true);
}

/**
 * Проверка прав супер-администратора
 */
function isSuperAdmin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin';
}

/**
 * Проверка иерархии ролей при редактировании/удалении/бане пользователя
 */
function canManageTargetUser(string $actorRole, string $targetRole): bool {
    if ($actorRole === 'super_admin') {
        return true;
    }
    if ($actorRole === 'admin') {
        return !in_array($targetRole, ['admin', 'super_admin'], true);
    }
    return false;
}

/**
 * Хелпер для безопасного редиректа с флеш-сообщением
 */
function redirectWithFlash(string $url, string $message, string $type = 'success'): void {
    initAppSession();
    $_SESSION['flash'] = $message;
    $_SESSION['flash_type'] = $type;
    header("Location: " . safeRedirectUrl($url, '/'));
    exit;
}

/**
 * Проверяет наличие колонки topic_id в таблице user_bans (безопасно до применения SQL на сервере)
 */
function hasUserBansTopicColumn(PDO $pdo): bool {
    static $hasColumn = null;
    if ($hasColumn !== null) {
        return $hasColumn;
    }
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM `user_bans` LIKE 'topic_id'");
        $hasColumn = (bool)$stmt->fetch();
    } catch (\Throwable $e) {
        $hasColumn = false;
    }
    return $hasColumn;
}

/**
 * Возвращает активный бан пользователя ТОЛЬКО для форума (глобальный по форуму или в конкретной теме).
 * Магазин, заказы, корзина и профиль остаются полностью доступны.
 * Автоматически сбрасывает устаревший флаг users.role='banned', если все записи в user_bans истекли.
 */
function getActiveForumBan(PDO $pdo, int $userId, ?int $topicId = null): ?array {
    if ($userId <= 0) {
        return null;
    }

    $hasTopicCol = hasUserBansTopicColumn($pdo);

    if ($hasTopicCol) {
        if ($topicId !== null && $topicId > 0) {
            $sql = "SELECT * FROM user_bans
                    WHERE user_id = ?
                      AND (topic_id IS NULL OR topic_id = ?)
                      AND (expires_at IS NULL OR expires_at > NOW())
                    ORDER BY (topic_id IS NULL) DESC, banned_at DESC
                    LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId, $topicId]);
        } else {
            $sql = "SELECT * FROM user_bans
                    WHERE user_id = ?
                      AND topic_id IS NULL
                      AND (expires_at IS NULL OR expires_at > NOW())
                    ORDER BY banned_at DESC
                    LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId]);
        }
    } else {
        $sql = "SELECT * FROM user_bans
                WHERE user_id = ?
                  AND (expires_at IS NULL OR expires_at > NOW())
                ORDER BY banned_at DESC
                LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
    }

    $activeBan = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($activeBan) {
        return $activeBan;
    }

    // Обратная совместимость со старым механизмом users.role = 'banned'
    $roleStmt = $pdo->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
    $roleStmt->execute([$userId]);
    $userRole = $roleStmt->fetchColumn();

    if ($userRole === 'banned') {
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM user_bans WHERE user_id = ?");
        $countStmt->execute([$userId]);
        $hadTimedBans = (int)$countStmt->fetchColumn() > 0;

        if ($hadTimedBans) {
            // Срок бана истёк — автоматически восстанавливаем роль 'user'
            $pdo->prepare("UPDATE users SET role = 'user' WHERE id = ? AND role = 'banned'")->execute([$userId]);
            if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === $userId && ($_SESSION['role'] ?? '') === 'banned') {
                $_SESSION['role'] = 'user';
            }
            return null;
        }

        // Если записей в user_bans нет вообще, считаем старую роль 'banned' бессрочным баном форума
        return [
            'id'         => 0,
            'user_id'    => $userId,
            'topic_id'   => null,
            'reason'     => 'Нарушение правил форума',
            'expires_at' => null,
        ];
    }

    return null;
}

/**
 * Безопасная проверка и сохранение загруженного изображения (anti-RCE).
 * $destSubDir передаётся относительно корня сайта (например 'uploads/avatars' или 'assets/uploads').
 */
function uploadSafeImage(array $file, string $destSubDir, string $prefix = 'img_', int $maxBytes = 5242880): array {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'path' => null, 'error' => 'Ошибка загрузки файла (код ' . (int)($file['error'] ?? -1) . ').'];
    }
    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['ok' => false, 'path' => null, 'error' => 'Файл не был корректно загружен.'];
    }
    if (($file['size'] ?? 0) <= 0 || ($file['size'] ?? 0) > $maxBytes) {
        return ['ok' => false, 'path' => null, 'error' => 'Размер файла должен быть не более ' . round($maxBytes / 1048576, 1) . ' МБ.'];
    }

    $mimeMap = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    $finfo = new \finfo(FILEINFO_MIME_TYPE);
    $detectedMime = $finfo->file($file['tmp_name']);
    if (!$detectedMime || !isset($mimeMap[$detectedMime])) {
        return ['ok' => false, 'path' => null, 'error' => 'Допустимы только изображения JPG, PNG, WEBP или GIF.'];
    }

    $imgInfo = @getimagesize($file['tmp_name']);
    if ($imgInfo === false) {
        return ['ok' => false, 'path' => null, 'error' => 'Файл не является корректным изображением.'];
    }

    $cleanSubDir = trim(str_replace('\\', '/', $destSubDir), '/');
    $projectRoot = realpath(__DIR__ . '/..');
    $targetDir = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $cleanSubDir);

    if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true)) {
        return ['ok' => false, 'path' => null, 'error' => 'Не удалось создать каталог для загрузки.'];
    }

    $safePrefix = preg_replace('/[^a-zA-Z0-9_-]/', '', $prefix);
    $fileName = $safePrefix . bin2hex(random_bytes(8)) . '.' . $mimeMap[$detectedMime];
    $destPath = $targetDir . DIRECTORY_SEPARATOR . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        return ['ok' => false, 'path' => null, 'error' => 'Не удалось сохранить файл на сервере.'];
    }
    @chmod($destPath, 0644);

    return [
        'ok'   => true,
        'path' => $cleanSubDir . '/' . $fileName,
        'error' => '',
    ];
}

/**
 * Безопасное удаление загруженного файла только внутри разрешённых директорий (uploads/, assets/uploads/)
 */
function safeUnlinkUpload(?string $dbPath): void {
    $path = ltrim(str_replace('\\', '/', trim((string)$dbPath)), '/');
    if ($path === '' || strpos($path, '..') !== false) {
        return;
    }

    $projectRoot = realpath(__DIR__ . '/..');
    if ($projectRoot === false) {
        return;
    }

    $fullPath = realpath($projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path));
    if ($fullPath === false || !is_file($fullPath)) {
        return;
    }

    $allowedRoots = [
        realpath($projectRoot . DIRECTORY_SEPARATOR . 'uploads'),
        realpath($projectRoot . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'uploads'),
    ];

    foreach ($allowedRoots as $allowed) {
        if ($allowed !== false && strpos($fullPath, $allowed . DIRECTORY_SEPARATOR) === 0) {
            @unlink($fullPath);
            return;
        }
    }
}

/**
 * Возвращает веб-путь аватара, если файл существует в разрешённом каталоге
 */
function getAvatarUrl(?string $dbPath) {
    $path = ltrim(str_replace('\\', '/', trim((string)$dbPath)), '/');
    if ($path === '' || strpos($path, '..') !== false) {
        return false;
    }
    $physicalPath = __DIR__ . '/../' . $path;
    if (is_file($physicalPath)) {
        return '/' . $path;
    }
    return false;
}

/**
 * Актуальное количество товаров в корзине из таблицы cart по текущей PHP-сессии
 */
function getCartCount(PDO $pdo): int {
    try {
        $sid = session_id();
        if ($sid === '') {
            return 0;
        }
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity), 0) FROM cart WHERE session_id = ?");
        $stmt->execute([$sid]);
        return (int)$stmt->fetchColumn();
    } catch (\Throwable $e) {
        return 0;
    }
}
