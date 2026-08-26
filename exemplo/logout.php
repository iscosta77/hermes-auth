<?php
/** LOGOUT — iscosta77/auth */
declare(strict_types=1);

$config = require __DIR__ . '/config.php';

use Hermes\Auth\Auth;

(new Auth($config['db']))->logout();
header('Location: login.php');
exit;
