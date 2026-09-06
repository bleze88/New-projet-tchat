<?php

declare(strict_types=1);

use App\Support\Session;
use Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

error_reporting(E_ALL);
ini_set('display_errors', (\App\Support\Env::get('APP_ENV', 'production') === 'local') ? '1' : '0');

date_default_timezone_set('Europe/Zurich');

Session::start();
