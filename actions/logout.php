<?php
// actions/logout.php
session_start();
session_destroy();
header("Location: /");
exit;
