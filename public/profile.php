<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use App\Models\User;
use App\Support\Auth;
use App\Support\AvatarUpload;
use App\Support\Csrf;
use App\Support\View;

Auth::requireLogin();

$user = Auth::user();
$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();

    if (isset($_FILES['avatar'])) {
        try {
            $path = AvatarUpload::store($_FILES['avatar']);
            User::updateAvatar((int) $user['id'], $path);
            $success = 'Avatar mis à jour.';
            $user = Auth::user();
        } catch (\RuntimeException $e) {
            $error = $e->getMessage();
        }
    }
}

View::render('profile', [
    'pageTitle' => 'Profil',
    'user' => $user,
    'success' => $success,
    'error' => $error,
]);
