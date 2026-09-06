<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use App\Support\Auth;
use App\Support\Csrf;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /rooms.php');
    exit;
}

Csrf::requireValid();
Auth::logout();

header('Location: /login.php');
exit;
