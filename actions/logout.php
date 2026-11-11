<?php
// actions/logout.php
require_once __DIR__ . '/../includes/functions.php';
initAppSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /");
    exit;
}

requireCsrfOrRedirect('/');

destroySessionAndCookie();

header("Location: /pages/login.php?logged_out=1");
exit;
