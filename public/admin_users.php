<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use App\Models\User;
use App\Support\Auth;
use App\Support\Csrf;
use App\Support\Roles;
use App\Support\View;

Auth::requireRole(Roles::ADMIN);

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();

    $targetId = (int) ($_POST['user_id'] ?? 0);
    $newRole = (string) ($_POST['role'] ?? '');
    $target = User::find($targetId);

    if ($target === null || !Roles::isValid($newRole)) {
        $error = 'Requête invalide.';
    } elseif ($target['role'] === Roles::ADMIN && $newRole !== Roles::ADMIN && User::countByRole(Roles::ADMIN) <= 1) {
        $error = "Impossible de retirer le dernier compte admin.";
    } else {
        User::updateRole($targetId, $newRole);
        $success = 'Rôle mis à jour pour ' . $target['username'] . '.';
    }
}

View::render('admin_users', [
    'pageTitle' => 'Utilisateurs',
    'users' => User::all(),
    'currentUserId' => (int) Auth::id(),
    'error' => $error,
    'success' => $success,
], true);
