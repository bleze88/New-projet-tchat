<?php

declare(strict_types=1);

use App\Support\Auth;
use App\Support\Roles;
use App\Support\View;

/** @var string $__contentPath */
/** @var string $pageTitle */

$currentUser = Auth::user();
$pageTitle = $pageTitle ?? 'Tchat';
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= View::e($pageTitle) ?> · Tchat</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<?php if ($currentUser !== null): ?>
    <header class="app-header">
        <a class="brand" href="/rooms.php">Tchat</a>
        <nav class="app-nav">
            <a href="/rooms.php">Salons</a>
            <a href="/dm.php">Messages privés</a>
            <a href="/profile.php">Profil</a>
            <?php if (Roles::atLeast($currentUser['role'], Roles::ADMIN)): ?>
                <a href="/admin_users.php">Utilisateurs</a>
            <?php endif; ?>
        </nav>
        <div class="app-user">
            <img class="avatar avatar-sm" src="<?= View::e($currentUser['avatar_path'] ?? '/assets/img/default-avatar.svg') ?>" alt="">
            <span><?= View::e($currentUser['username']) ?></span>
            <form method="post" action="/logout.php" class="inline-form">
                <?= \App\Support\Csrf::field() ?>
                <button type="submit" class="link-button">Déconnexion</button>
            </form>
        </div>
    </header>
<?php endif; ?>
<main class="app-main">
    <?php require $__contentPath; ?>
</main>
</body>
</html>
