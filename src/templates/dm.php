<?php

declare(strict_types=1);

use App\Support\BbCode;
use App\Support\Csrf;
use App\Support\View;

/** @var array<string, mixed>|null $otherUser */
/** @var list<array<string, mixed>> $thread */
/** @var string|null $error */
/** @var int $currentUserId */
/** @var string $searchTerm */
/** @var list<array<string, mixed>> $searchResults */

$lastId = 0;
foreach ($thread as $m) {
    $lastId = max($lastId, (int) $m['id']);
}
?>
<div class="app-shell">
    <?php require __DIR__ . '/_sidebar.php'; ?>
    <div class="chat-panel"
         id="chat-panel"
         data-dm-with="<?= $otherUser !== null ? (int) $otherUser['id'] : '' ?>"
         data-last-dm-id="<?= $lastId ?>"
         data-current-user-id="<?= $currentUserId ?>">

        <?php if ($otherUser === null): ?>
            <div class="chat-header">Nouvelle conversation</div>
            <div style="padding: 1rem 1.25rem;">
                <form method="get" action="/dm.php" class="field" style="max-width: 320px;">
                    <label for="q">Rechercher un utilisateur</label>
                    <input type="text" id="q" name="q" value="<?= View::e($searchTerm) ?>" placeholder="pseudo...">
                </form>
                <?php if ($searchTerm !== ''): ?>
                    <ul class="sidebar-list">
                        <?php if (empty($searchResults)): ?>
                            <li style="color: var(--fg-muted);">Aucun utilisateur trouvé.</li>
                        <?php endif; ?>
                        <?php foreach ($searchResults as $user): ?>
                            <li>
                                <a href="/dm.php?with=<?= (int) $user['id'] ?>">
                                    <img class="avatar avatar-sm" src="<?= View::e($user['avatar_path'] ?: '/assets/img/default-avatar.svg') ?>" alt="">
                                    <?= View::e($user['username']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="chat-header"><?= View::e($otherUser['username']) ?></div>

            <?php if ($error !== null): ?>
                <p class="alert alert-error" style="margin: 0.75rem 1.25rem;"><?= View::e($error) ?></p>
            <?php endif; ?>

            <div class="chat-messages" id="chat-messages">
                <?php if (empty($thread)): ?>
                    <p class="empty-state">Dites bonjour à <?= View::e($otherUser['username']) ?> !</p>
                <?php endif; ?>
                <?php foreach ($thread as $message): ?>
                    <?php $mine = (int) $message['sender_id'] === $currentUserId; ?>
                    <div class="message-row <?= $mine ? 'mine' : '' ?>" data-message-id="<?= (int) $message['id'] ?>">
                        <img class="avatar avatar-sm" src="<?= View::e($message['sender_avatar_path'] ?: '/assets/img/default-avatar.svg') ?>" alt="">
                        <div>
                            <div class="message-meta"><?= View::e($message['sender_username']) ?> · <?= View::e(date('d/m H:i', strtotime((string) $message['created_at']))) ?></div>
                            <div class="message-bubble"><?= BbCode::render((string) $message['body']) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <form method="post" action="/dm.php?with=<?= (int) $otherUser['id'] ?>" class="composer" id="composer-form">
                <?= Csrf::field() ?>
                <input type="hidden" name="recipient_id" value="<?= (int) $otherUser['id'] ?>">
                <textarea name="body" rows="1" maxlength="1000" placeholder="Écris un message..." required></textarea>
                <button type="submit" class="btn">Envoyer</button>
            </form>
        <?php endif; ?>
    </div>
</div>
<script src="/assets/js/app.js"></script>
