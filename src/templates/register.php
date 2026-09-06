<?php

declare(strict_types=1);

use App\Support\Csrf;
use App\Support\View;

/** @var string|null $error */
/** @var string $username */
/** @var string $email */
?>
<div class="auth-page">
    <h1>S'enregistrer</h1>
    <div class="card">
        <?php if ($error !== null): ?>
            <p class="alert alert-error"><?= View::e($error) ?></p>
        <?php endif; ?>
        <form method="post" action="/register.php" novalidate>
            <?= Csrf::field() ?>
            <div class="field">
                <label for="username">Pseudo</label>
                <input type="text" id="username" name="username" value="<?= View::e($username) ?>" required minlength="3" maxlength="32" pattern="[A-Za-z0-9_-]+" autofocus>
            </div>
            <div class="field">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" value="<?= View::e($email) ?>" required maxlength="255">
            </div>
            <div class="field">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required minlength="8">
            </div>
            <div class="field">
                <label for="password_confirm">Confirmer le mot de passe</label>
                <input type="password" id="password_confirm" name="password_confirm" required minlength="8">
            </div>
            <button type="submit" class="btn btn-block">Créer mon compte</button>
        </form>
    </div>
    <p class="auth-switch">Déjà inscrit ? <a href="/login.php">Se connecter</a></p>
</div>
