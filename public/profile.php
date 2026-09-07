<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use App\Models\User;
use App\Support\Auth;
use App\Support\AvatarUpload;
use App\Support\Csrf;
use App\Support\PresetAvatars;
use App\Support\Validator;
use App\Support\View;

Auth::requireLogin();

$user = Auth::user();
$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();

    $formName = (string) ($_POST['form'] ?? '');

    if ($formName === 'avatar_upload' && isset($_FILES['avatar'])) {
        try {
            $path = AvatarUpload::store($_FILES['avatar']);
            User::updateAvatar((int) $user['id'], $path);
            $success = 'Avatar mis à jour.';
            $user = Auth::user();
        } catch (\RuntimeException $e) {
            $error = $e->getMessage();
        }
    } elseif ($formName === 'avatar_preset') {
        $choice = basename((string) ($_POST['avatar_choice'] ?? ''));

        if (!PresetAvatars::isValid($choice)) {
            $error = 'Avatar invalide.';
        } else {
            User::updateAvatar((int) $user['id'], '/assets/img/avatars/' . $choice);
            $success = 'Avatar mis à jour.';
            $user = Auth::user();
        }
    } elseif ($formName === 'change_password') {
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $newPasswordConfirm = (string) ($_POST['new_password_confirm'] ?? '');

        if (!password_verify($currentPassword, $user['password_hash'])) {
            $error = 'Mot de passe actuel incorrect.';
        } elseif (!Validator::password($newPassword)) {
            $error = 'Le nouveau mot de passe doit faire au moins 8 caractères.';
        } elseif ($newPassword !== $newPasswordConfirm) {
            $error = 'Les mots de passe ne correspondent pas.';
        } else {
            User::updatePassword((int) $user['id'], $newPassword);
            $success = 'Mot de passe mis à jour.';
        }
    }
}

View::render('profile', [
    'pageTitle' => 'Profil',
    'user' => $user,
    'success' => $success,
    'error' => $error,
    'presetAvatars' => PresetAvatars::all(),
]);
