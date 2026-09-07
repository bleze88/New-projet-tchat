<?php

declare(strict_types=1);

use App\Support\Csrf;
use App\Support\View;

/** @var array<string, mixed> $user */
/** @var string|null $success */
/** @var string|null $error */
/** @var list<string> $presetAvatars */
?>
<div class="profile-page">
    <h1>Profil</h1>

    <?php if ($success !== null): ?>
        <p class="alert" style="background: color-mix(in srgb, var(--accent) 15%, transparent); color: var(--accent);"><?= View::e($success) ?></p>
    <?php endif; ?>
    <?php if ($error !== null): ?>
        <p class="alert alert-error"><?= View::e($error) ?></p>
    <?php endif; ?>

    <div class="card" style="margin-bottom: 1.5rem;">
        <div class="profile-avatar-row">
            <img class="avatar avatar-lg" src="<?= View::e($user['avatar_path'] ?: '/assets/img/default-avatar.svg') ?>" alt="">
            <div>
                <strong><?= View::e($user['username']) ?></strong><br>
                <span style="color: var(--fg-muted); font-size: 0.9rem;"><?= View::e($user['email']) ?></span>
            </div>
        </div>

        <h2 style="font-size: 0.95rem; margin-bottom: 0.5rem;">Choisir un avatar prédéfini</h2>
        <form method="post" action="/profile.php">
            <?= Csrf::field() ?>
            <input type="hidden" name="form" value="avatar_preset">
            <div class="avatar-grid">
                <?php foreach ($presetAvatars as $avatarPath): ?>
                    <label class="avatar-choice <?= $user['avatar_path'] === $avatarPath ? 'selected' : '' ?>">
                        <input type="radio" name="avatar_choice" value="<?= View::e(basename($avatarPath)) ?>" <?= $user['avatar_path'] === $avatarPath ? 'checked' : '' ?> onchange="this.form.requestSubmit()">
                        <img src="<?= View::e($avatarPath) ?>" alt="">
                    </label>
                <?php endforeach; ?>
            </div>
        </form>

        <h2 style="font-size: 0.95rem; margin: 1.25rem 0 0.5rem;">Ou envoyer une image</h2>
        <form method="post" action="/profile.php" enctype="multipart/form-data">
            <?= Csrf::field() ?>
            <input type="hidden" name="form" value="avatar_upload">
            <div class="field">
                <label for="avatar">png, jpg, gif ou webp — 512x512 px et 2 Mo max</label>
                <input type="file" id="avatar" name="avatar" accept="image/png,image/jpeg,image/gif,image/webp" required>
            </div>
            <button type="submit" class="btn">Envoyer l'image</button>
        </form>
    </div>

    <div class="card">
        <h2 style="font-size: 0.95rem; margin-bottom: 0.5rem;">Changer de mot de passe</h2>
        <form method="post" action="/profile.php">
            <?= Csrf::field() ?>
            <input type="hidden" name="form" value="change_password">
            <div class="field">
                <label for="current_password">Mot de passe actuel</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>
            <div class="field">
                <label for="new_password">Nouveau mot de passe</label>
                <input type="password" id="new_password" name="new_password" required minlength="8">
            </div>
            <div class="field">
                <label for="new_password_confirm">Confirmer le nouveau mot de passe</label>
                <input type="password" id="new_password_confirm" name="new_password_confirm" required minlength="8">
            </div>
            <button type="submit" class="btn">Mettre à jour le mot de passe</button>
        </form>
    </div>
</div>
