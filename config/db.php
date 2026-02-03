<?php
// config/db.php

$host = 'localhost';
$db   = 'street_forum';
$user = 'root';     // Ваш логин БД
$pass = '';         // Ваш пароль БД
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // В стиле уличного искусства: "Стена обрушилась" вместо скучной ошибки
    die('<div style="font-family: sans-serif; padding: 20px; background: #000; color: #fce300; border: 4px solid #fce300;">
         <h1 style="text-transform: uppercase;">Ошибка соединения!</h1>
         <p>База данных недоступна. Попробуйте позже.</p>
         <small>Error: ' . (int)$e->getCode() . '</small>
         </div>');
}
?>
