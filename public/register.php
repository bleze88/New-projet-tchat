<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use App\Models\User;
use App\Support\Auth;
use App\Support\Csrf;
use App\Support\RateLimiter;
use App\Support\Validator;
use App\Support\View;

if (Auth::check()) {
    header('Location: /rooms.php');
    exit;
}

$error = null;
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();

    $username = trim((string) ($_POST['username'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

    if (!RateLimiter::attempt($_SERVER['REMOTE_ADDR'] ?? 'unknown', 'register', 10, 3600)) {
        $error = 'Trop de tentatives. Réessayez plus tard.';
    } elseif (!Validator::username($username)) {
        $error = 'Le pseudo doit faire 3 à 32 caractères (lettres, chiffres, - et _).';
    } elseif (!Validator::email($email)) {
        $error = 'Adresse e-mail invalide.';
    } elseif (!Validator::password($password)) {
        $error = 'Le mot de passe doit faire au moins 8 caractères.';
    } elseif ($password !== $passwordConfirm) {
        $error = 'Les mots de passe ne correspondent pas.';
    } elseif (User::usernameOrEmailTaken($username, $email)) {
        $error = 'Ce pseudo ou cette adresse e-mail est déjà utilisé.';
    } else {
        $userId = User::create($username, $email, $password);
        Auth::login($userId);
        header('Location: /rooms.php');
        exit;
    }
}

View::render('register', [
    'pageTitle' => "S'enregistrer",
    'error' => $error,
    'username' => $username,
    'email' => $email,
]);
