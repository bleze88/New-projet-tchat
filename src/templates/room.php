<?php

declare(strict_types=1);

use App\Support\BbCode;
use App\Support\Csrf;
use App\Support\Moderation;
use App\Support\Roles;
use App\Support\View;

/** @var array<string, mixed> $room */
/** @var list<array<string, mixed>> $rooms */
/** @var list<array<string, mixed>> $conversations */
/** @var int|null $activeRoomId */
/** @var int|null $activeDmUserId */
/** @var list<array<string, mixed>> $messages */
/** @var string|null $error */
/** @var int $currentUserId */
/** @var string $currentUserRole */
/** @var bool $canModerate */
/** @var array<string, mixed>|null $myTimeout */
/** @var list<array<string, mixed>> $activeTimeouts */

$lastId = 0;
foreach ($messages as $m) {
    $lastId = max($lastId, (int) $m['id']);
}
?>
<div class="app-shell">
    <?php require __DIR__ . '/_sidebar.php'; ?>
    <div class="chat-panel"
         id="chat-panel"
         data-room-id="<?= (int) $room['id'] ?>"
         data-last-message-id="<?= $lastId ?>"
         data-current-user-id="<?= $currentUserId ?>">
        <div class="chat-header"># <?= View::e($room['name']) ?></div>

        <?php if ($error !== null): ?>
            <p class="alert alert-error" style="margin: 0.75rem 1.25rem;"><?= View::e($error) ?></p>
        <?php endif; ?>

        <?php if ($canModerate && !empty($activeTimeouts)): ?>
            <div style="margin: 0.75rem 1.25rem; font-size: 0.8rem; color: var(--fg-muted);">
                En time-out :
                <?php foreach ($activeTimeouts as $timeoutRow): ?>
                    <span style="margin-right: 0.5rem;">
                        <?= View::e($timeoutRow['username']) ?> (<?= View::e(Moderation::formatRemaining($timeoutRow['expires_at'])) ?>)
                        <form method="post" action="/room.php?id=<?= (int) $room['id'] ?>" class="inline-form">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="form" value="remove_timeout">
                            <input type="hidden" name="target_user_id" value="<?= (int) $timeoutRow['user_id'] ?>">
                            <button type="submit" class="link-button" style="font-size: 0.72rem;">lever</button>
                        </form>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="chat-messages" id="chat-messages">
            <?php if (empty($messages)): ?>
                <p class="empty-state">Aucun message pour l'instant. Sois le premier à écrire !</p>
            <?php endif; ?>
            <?php foreach ($messages as $message): ?>
                <?php
                $mine = (int) $message['user_id'] === $currentUserId;
                $canModerateAuthor = $canModerate && !$mine && Roles::outranks($currentUserRole, $message['role']);
                ?>
                <div class="message-row <?= $mine ? 'mine' : '' ?>" data-message-id="<?= (int) $message['id'] ?>">
                    <img class="avatar avatar-sm" src="<?= View::e($message['avatar_path'] ?: '/assets/img/default-avatar.svg') ?>" alt="">
                    <div>
                        <div class="message-meta">
                            <?= View::e($message['username']) ?><?= View::roleBadge($message['role'] ?? null) ?> · <?= View::e(date('d/m H:i', strtotime((string) $message['created_at']))) ?>
                            <?php if ($canModerate): ?>
                                <form method="post" action="/room.php?id=<?= (int) $room['id'] ?>" class="inline-form" onsubmit="return confirm('Supprimer ce message ?');">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="form" value="delete_message">
                                    <input type="hidden" name="message_id" value="<?= (int) $message['id'] ?>">
                                    <button type="submit" class="link-button" style="font-size: 0.72rem;">supprimer</button>
                                </form>
                            <?php endif; ?>
                            <?php if ($canModerateAuthor): ?>
                                <details class="mod-menu">
                                    <summary class="link-button" style="font-size: 0.72rem; display: inline;">modérer</summary>
                                    <div class="mod-menu-panel">
                                        <form method="post" action="/room.php?id=<?= (int) $room['id'] ?>">
                                            <?= Csrf::field() ?>
                                            <input type="hidden" name="form" value="apply_timeout">
                                            <input type="hidden" name="target_user_id" value="<?= (int) $message['user_id'] ?>">
                                            <label>Time-out
                                                <select name="duration_seconds">
                                                    <?php foreach (\App\Support\Moderation::TIMEOUT_OPTIONS as $seconds => $label): ?>
                                                        <option value="<?= $seconds ?>"><?= View::e($label) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </label>
                                            <button type="submit" class="btn" style="padding: 0.25rem 0.6rem; font-size: 0.75rem;">Appliquer</button>
                                        </form>
                                        <form method="post" action="/room.php?id=<?= (int) $room['id'] ?>" onsubmit="return confirm('Bannir cet utilisateur du site ?');">
                                            <?= Csrf::field() ?>
                                            <input type="hidden" name="form" value="apply_ban">
                                            <input type="hidden" name="target_user_id" value="<?= (int) $message['user_id'] ?>">
                                            <label>Ban
                                                <select name="ban_duration">
                                                    <?php foreach (\App\Support\Moderation::BAN_OPTIONS as $value => $label): ?>
                                                        <option value="<?= View::e((string) $value) ?>"><?= View::e($label) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </label>
                                            <input type="text" name="reason" placeholder="raison (optionnel)" style="width: 100%; margin: 0.25rem 0;">
                                            <button type="submit" class="btn" style="padding: 0.25rem 0.6rem; font-size: 0.75rem; background: var(--danger);">Bannir</button>
                                        </form>
                                    </div>
                                </details>
                            <?php endif; ?>
                        </div>
                        <div class="message-bubble"><?= BbCode::render((string) $message['body']) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($myTimeout !== null): ?>
            <p class="alert alert-error" style="margin: 0 1.25rem 0.75rem;">
                Tu es en time-out sur ce salon encore <?= View::e(Moderation::formatRemaining($myTimeout['expires_at'])) ?>.
            </p>
        <?php else: ?>
            <form method="post" action="/room.php?id=<?= (int) $room['id'] ?>" class="composer" id="composer-form">
                <?= Csrf::field() ?>
                <textarea name="body" rows="1" maxlength="1000" placeholder="Écris un message... ([b]gras[/b], [i]italique[/i], [url=https://...]lien[/url])" required></textarea>
                <button type="submit" class="btn">Envoyer</button>
            </form>
        <?php endif; ?>
    </div>
</div>
<script src="/assets/js/app.js"></script>
