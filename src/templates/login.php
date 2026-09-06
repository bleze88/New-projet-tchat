<?php

declare(strict_types=1);

use App\Support\Csrf;
use App\Support\View;

/** @var string|null $error */
/** @var string $login */
?>
<div class="auth-page">
    <h1>Connexion</h1>
    <div class="card">
        <?php if ($error !== null): ?>
            <p class="alert alert-error"><?= View::e($error) ?></p>
        <?php endif; ?>
        <form method="post" action="/login.php" novalidate>
            <?= Csrf::field() ?>
            <div class="field">
                <label for="login">Pseudo ou e-mail</label>
                <input type="text" id="login" name="login" value="<?= View::e($login) ?>" required autofocus>
            </div>
            <div class="field">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-block">Se connecter</button>
        </form>
    </div>
    <p class="auth-switch">Pas encore de compte ? <a href="/register.php">S'enregistrer</a></p>
</div>
