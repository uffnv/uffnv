<?php
// api/dadata_suggest.php — Серверный прокси для DaData без утечки токена в клиентский JS
require_once __DIR__ . '/../includes/functions.php';
initAppSession();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['suggestions' => [], 'error' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['suggestions' => [], 'error' => 'Method Not Allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

requireCsrfOrRedirect('/pages/profile.php', true);

$rawInput = file_get_contents('php://input');
$payload = json_decode($rawInput ?: '', true);
if (!is_array($payload)) {
    $payload = $_POST;
}

$type  = $payload['type'] ?? 'address';
$query = trim((string)($payload['query'] ?? ''));

if (!in_array($type, ['fio', 'address'], true) || mb_strlen($query, 'UTF-8') < 2) {
    echo json_encode(['suggestions' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

$token = getAppSecret('dadata_token', '');
if ($token === '' || $token === 'YOUR_DADATA_API_TOKEN') {
    echo json_encode(['suggestions' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

$endpoint = 'https://suggestions.dadata.ru/suggestions/api/4_1/rs/suggest/' . $type;
$body = json_encode(['query' => mb_substr($query, 0, 120, 'UTF-8'), 'count' => 6], JSON_UNESCAPED_UNICODE);

$response = false;
if (function_exists('curl_init')) {
    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_TIMEOUT        => 4,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Token ' . $token,
        ],
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
} else {
    $ctx = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'timeout' => 4,
            'header'  => "Content-Type: application/json\r\nAccept: application/json\r\nAuthorization: Token {$token}\r\n",
            'content' => $body,
        ],
    ]);
    $response = @file_get_contents($endpoint, false, $ctx);
}

if ($response === false) {
    echo json_encode(['suggestions' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

echo $response;
