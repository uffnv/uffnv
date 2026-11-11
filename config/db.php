<?php
// config/db.php

// 1. Загрузка локальной конфигурации БД (не коммитится в Git)
$localConfigFile = __DIR__ . '/db.local.php';
$localConfig = file_exists($localConfigFile) ? require $localConfigFile : [];

// 2. Загрузка локальных секретов (токены внешних API и т.д.)
if (!function_exists('getAppSecret')) {
    function getAppSecret(string $key, $default = null) {
        static $secrets = null;
        if ($secrets === null) {
            $secretsFile = __DIR__ . '/secrets.local.php';
            $secrets = file_exists($secretsFile) ? (require $secretsFile) : [];
            if (!is_array($secrets)) {
                $secrets = [];
            }
        }
        return $secrets[$key] ?? getenv(strtoupper($key)) ?: $default;
    }
}

// 3. Параметры подключения: локальный файл -> переменные окружения -> значения по умолчанию
$host    = $localConfig['host']    ?? getenv('DB_HOST') ?: 'localhost';
$db      = $localConfig['db']      ?? getenv('DB_NAME') ?: 'street_forum';
$user    = $localConfig['user']    ?? getenv('DB_USER') ?: 'root';
$pass    = $localConfig['pass']    ?? getenv('DB_PASS') ?: '';
$charset = $localConfig['charset'] ?? 'utf8mb4';
$debug   = $localConfig['debug']   ?? false;

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    if ($debug) {
        die("Ошибка подключения к БД: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
    }
    error_log("UFFNV DB Connection error: " . $e->getMessage());
    http_response_code(500);
    die('<div style="font-family: sans-serif; padding: 20px; background: #000; color: #fce300; border: 4px solid #fce300;">
         <h1 style="text-transform: uppercase;">Ошибка соединения!</h1>
         <p>База данных временно недоступна. Попробуйте обновить страницу позже.</p>
         </div>');
}
