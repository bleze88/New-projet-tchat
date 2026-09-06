<?php

declare(strict_types=1);

use App\Support\Csrf;
use App\Support\View;

/** @var array<string, mixed> $user */
/** @var string|null $success */
/** @var string|null $error */
?>
<div class="profile-page">
    <h1>Profil</h1>
    <div class="card">
        <?php if ($success !== null): ?>
            <p class="alert" style="background: color-mix(in srgb, var(--accent) 15%, transparent); color: var(--accent);"><?= View::e($success) ?></p>
        <?php endif; ?>
        <?php if ($error !== null): ?>
            <p class="alert alert-error"><?= View::e($error) ?></p>
        <?php endif; ?>

        <div class="profile-avatar-row">
            <img class="avatar avatar-lg" src="<?= View::e($user['avatar_path'] ?: '/assets/img/default-avatar.svg') ?>" alt="">
            <div>
                <strong><?= View::e($user['username']) ?></strong><br>
                <span style="color: var(--fg-muted); font-size: 0.9rem;"><?= View::e($user['email']) ?></span>
            </div>
        </div>

        <form method="post" action="/profile.php" enctype="multipart/form-data">
            <?= Csrf::field() ?>
            <div class="field">
                <label for="avatar">Nouvel avatar (png, jpg, gif ou webp, 512x512 px et 2 Mo max)</label>
                <input type="file" id="avatar" name="avatar" accept="image/png,image/jpeg,image/gif,image/webp" required>
            </div>
            <button type="submit" class="btn">Mettre à jour l'avatar</button>
        </form>
    </div>
</div>
