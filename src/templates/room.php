<?php

declare(strict_types=1);

use App\Support\BbCode;
use App\Support\Csrf;
use App\Support\View;

/** @var array<string, mixed> $room */
/** @var list<array<string, mixed>> $rooms */
/** @var list<array<string, mixed>> $conversations */
/** @var int|null $activeRoomId */
/** @var int|null $activeDmUserId */
/** @var list<array<string, mixed>> $messages */
/** @var string|null $error */
/** @var int $currentUserId */
/** @var bool $canModerate */

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

        <div class="chat-messages" id="chat-messages">
            <?php if (empty($messages)): ?>
                <p class="empty-state">Aucun message pour l'instant. Sois le premier à écrire !</p>
            <?php endif; ?>
            <?php foreach ($messages as $message): ?>
                <?php $mine = (int) $message['user_id'] === $currentUserId; ?>
                <div class="message-row <?= $mine ? 'mine' : '' ?>" data-message-id="<?= (int) $message['id'] ?>">
                    <img class="avatar avatar-sm" src="<?= View::e($message['avatar_path'] ?: '/assets/img/default-avatar.svg') ?>" alt="">
                    <div>
                        <div class="message-meta">
                            <?= View::e($message['username']) ?> · <?= View::e(date('d/m H:i', strtotime((string) $message['created_at']))) ?>
                            <?php if ($canModerate): ?>
                                <form method="post" action="/room.php?id=<?= (int) $room['id'] ?>" class="inline-form" onsubmit="return confirm('Supprimer ce message ?');">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="form" value="delete_message">
                                    <input type="hidden" name="message_id" value="<?= (int) $message['id'] ?>">
                                    <button type="submit" class="link-button" style="font-size: 0.72rem;">supprimer</button>
                                </form>
                            <?php endif; ?>
                        </div>
                        <div class="message-bubble"><?= BbCode::render((string) $message['body']) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <form method="post" action="/room.php?id=<?= (int) $room['id'] ?>" class="composer" id="composer-form">
            <?= Csrf::field() ?>
            <textarea name="body" rows="1" maxlength="1000" placeholder="Écris un message... ([b]gras[/b], [i]italique[/i], [url=https://...]lien[/url])" required></textarea>
            <button type="submit" class="btn">Envoyer</button>
        </form>
    </div>
</div>
<script src="/assets/js/app.js"></script>
