<?php
require_once __DIR__ . '/config/auth.php';
Auth::logout();
header("Location: /login?logged_out=1");
exit;
