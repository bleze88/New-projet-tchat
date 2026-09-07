<?php

declare(strict_types=1);

use App\Support\Csrf;
use App\Support\Roles;
use App\Support\View;

/** @var list<array<string, mixed>> $users */
/** @var int $currentUserId */
/** @var string|null $error */
/** @var string|null $success */
?>
<div class="profile-page" style="max-width: 640px;">
    <h1>Utilisateurs</h1>

    <?php if ($success !== null): ?>
        <p class="alert" style="background: color-mix(in srgb, var(--accent) 15%, transparent); color: var(--accent);"><?= View::e($success) ?></p>
    <?php endif; ?>
    <?php if ($error !== null): ?>
        <p class="alert alert-error"><?= View::e($error) ?></p>
    <?php endif; ?>

    <div class="card">
        <?php foreach ($users as $listedUser): ?>
            <form method="post" action="/admin_users.php" style="display: flex; align-items: center; gap: 0.75rem; padding: 0.5rem 0; border-bottom: 1px solid var(--border);">
                <?= Csrf::field() ?>
                <input type="hidden" name="user_id" value="<?= (int) $listedUser['id'] ?>">
                <div style="flex: 1;">
                    <strong><?= View::e($listedUser['username']) ?></strong>
                    <?= (int) $listedUser['id'] === $currentUserId ? ' (toi)' : '' ?><br>
                    <span style="color: var(--fg-muted); font-size: 0.85rem;"><?= View::e($listedUser['email']) ?></span>
                </div>
                <select name="role">
                    <?php foreach (Roles::all() as $roleOption): ?>
                        <option value="<?= View::e($roleOption) ?>" <?= $listedUser['role'] === $roleOption ? 'selected' : '' ?>><?= View::e($roleOption) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn">Mettre à jour</button>
            </form>
        <?php endforeach; ?>
    </div>
</div>
