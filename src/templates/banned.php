<?php

declare(strict_types=1);

use App\Support\Moderation;
use App\Support\View;

/** @var string|null $until */
/** @var string|null $reason */
?>
<div class="auth-page">
    <h1>Compte banni</h1>
    <div class="card">
        <p class="alert alert-error">
            Ce compte a été banni <?= $until !== null ? View::e(Moderation::formatUntil($until)) : 'définitivement' ?>.
        </p>
        <?php if ($reason !== null): ?>
            <p>Raison : <?= View::e($reason) ?></p>
        <?php endif; ?>
    </div>
    <p class="auth-switch"><a href="/login.php">Retour à la connexion</a></p>
</div>
