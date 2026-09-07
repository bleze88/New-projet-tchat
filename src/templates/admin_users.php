<?php

declare(strict_types=1);

use App\Support\Auth;
use App\Support\Csrf;
use App\Support\Moderation;
use App\Support\Roles;
use App\Support\View;

/** @var list<array<string, mixed>> $users */
/** @var list<array<string, mixed>> $rooms */
/** @var array<int, array<string, mixed>> $activeBans */
/** @var int $currentUserId */
/** @var string $currentUserRole */
/** @var bool $isAdmin */
/** @var string|null $error */
/** @var string|null $success */
?>
<div class="profile-page" style="max-width: 720px;">
    <h1>Utilisateurs</h1>

    <?php if ($success !== null): ?>
        <p class="alert" style="background: color-mix(in srgb, var(--accent) 15%, transparent); color: var(--accent);"><?= View::e($success) ?></p>
    <?php endif; ?>
    <?php if ($error !== null): ?>
        <p class="alert alert-error"><?= View::e($error) ?></p>
    <?php endif; ?>

    <div class="card">
        <?php foreach ($users as $listedUser): ?>
            <?php
            $ban = $activeBans[(int) $listedUser['id']] ?? null;
            $canModerateUser = (int) $listedUser['id'] !== $currentUserId && Roles::outranks($currentUserRole, $listedUser['role']);
            ?>
            <div style="padding: 0.75rem 0; border-bottom: 1px solid var(--border);">
                <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 160px;">
                        <strong><?= View::e($listedUser['username']) ?></strong><?= View::roleBadge($listedUser['role']) ?>
                        <?= (int) $listedUser['id'] === $currentUserId ? ' (toi)' : '' ?><br>
                        <span style="color: var(--fg-muted); font-size: 0.85rem;"><?= View::e($listedUser['email']) ?></span>
                        <?php if ($ban !== null): ?>
                            <br><span class="role-badge role-badge-admin">banni <?= View::e(Moderation::formatUntil($ban['expires_at'])) ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if ($isAdmin): ?>
                        <form method="post" action="/admin_users.php">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="form" value="change_role">
                            <input type="hidden" name="user_id" value="<?= (int) $listedUser['id'] ?>">
                            <select name="role">
                                <?php foreach (Roles::all() as $roleOption): ?>
                                    <option value="<?= View::e($roleOption) ?>" <?= $listedUser['role'] === $roleOption ? 'selected' : '' ?>><?= View::e($roleOption) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn" style="padding: 0.3rem 0.6rem; font-size: 0.8rem;">Rôle</button>
                        </form>
                    <?php endif; ?>
                </div>

                <?php if ($canModerateUser): ?>
                    <details style="margin-top: 0.5rem;">
                        <summary class="link-button" style="font-size: 0.8rem;">Modération</summary>
                        <div style="display: flex; gap: 1.5rem; flex-wrap: wrap; margin-top: 0.5rem;">
                            <form method="post" action="/admin_users.php" class="mod-inline-form">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="form" value="apply_timeout">
                                <input type="hidden" name="user_id" value="<?= (int) $listedUser['id'] ?>">
                                <div style="font-size: 0.78rem; color: var(--fg-muted); margin-bottom: 0.25rem;">Time-out sur un salon</div>
                                <select name="room_id">
                                    <?php foreach ($rooms as $roomOption): ?>
                                        <option value="<?= (int) $roomOption['id'] ?>"># <?= View::e($roomOption['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <select name="duration_seconds">
                                    <?php foreach (Moderation::TIMEOUT_OPTIONS as $seconds => $label): ?>
                                        <option value="<?= $seconds ?>"><?= View::e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn" style="padding: 0.25rem 0.6rem; font-size: 0.75rem;">Appliquer</button>
                            </form>

                            <form method="post" action="/admin_users.php" class="mod-inline-form" onsubmit="return confirm('Bannir cet utilisateur du site ?');">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="form" value="apply_ban">
                                <input type="hidden" name="user_id" value="<?= (int) $listedUser['id'] ?>">
                                <div style="font-size: 0.78rem; color: var(--fg-muted); margin-bottom: 0.25rem;">Bannir du site</div>
                                <select name="ban_duration">
                                    <?php foreach (Moderation::BAN_OPTIONS as $value => $label): ?>
                                        <option value="<?= View::e((string) $value) ?>"><?= View::e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="text" name="reason" placeholder="raison (optionnel)">
                                <button type="submit" class="btn" style="padding: 0.25rem 0.6rem; font-size: 0.75rem; background: var(--danger);">Bannir</button>
                            </form>

                            <?php if ($ban !== null): ?>
                                <form method="post" action="/admin_users.php" class="mod-inline-form">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="form" value="remove_ban">
                                    <input type="hidden" name="user_id" value="<?= (int) $listedUser['id'] ?>">
                                    <div style="font-size: 0.78rem; color: var(--fg-muted); margin-bottom: 0.25rem;">&nbsp;</div>
                                    <button type="submit" class="btn" style="padding: 0.25rem 0.6rem; font-size: 0.75rem;">Lever le ban</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </details>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
