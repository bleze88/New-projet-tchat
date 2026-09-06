<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use App\Models\User;
use App\Support\Auth;
use App\Support\Csrf;
use App\Support\RateLimiter;
use App\Support\View;

if (Auth::check()) {
    header('Location: /rooms.php');
    exit;
}

$error = null;
$login = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();

    $login = trim((string) ($_POST['login'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    $rateKey = strtolower($login) . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');

    if (!RateLimiter::attempt($rateKey, 'login_attempt', 8, 300)) {
        $error = 'Trop de tentatives. Réessayez dans quelques minutes.';
    } else {
        $user = $login !== '' ? User::findByUsernameOrEmail($login) : null;

        if ($user !== null && password_verify($password, $user['password_hash'])) {
            Auth::login((int) $user['id']);
            header('Location: /rooms.php');
            exit;
        }

        $error = 'Identifiants incorrects.';
    }
}

View::render('login', [
    'pageTitle' => 'Connexion',
    'error' => $error,
    'login' => $login,
]);
