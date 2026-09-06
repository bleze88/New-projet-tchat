<?php

declare(strict_types=1);

use App\Support\View;

/** @var list<array<string, mixed>> $rooms */
/** @var list<array<string, mixed>> $conversations */
/** @var int|null $activeRoomId */
/** @var int|null $activeDmUserId */
?>
<aside class="sidebar">
    <h2>Salons</h2>
    <ul class="sidebar-list">
        <?php foreach ($rooms as $room): ?>
            <li>
                <a href="/room.php?id=<?= (int) $room['id'] ?>" class="<?= $activeRoomId === (int) $room['id'] ? 'active' : '' ?>">
                    # <?= View::e($room['name']) ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <h2>Messages privés</h2>
    <ul class="sidebar-list">
        <?php if (empty($conversations)): ?>
            <li style="padding: 0.5rem; color: var(--fg-muted); font-size: 0.85rem;">Aucune conversation</li>
        <?php endif; ?>
        <?php foreach ($conversations as $conv): ?>
            <li>
                <a href="/dm.php?with=<?= (int) $conv['user_id'] ?>" class="<?= $activeDmUserId === (int) $conv['user_id'] ? 'active' : '' ?>">
                    <img class="avatar avatar-sm" src="<?= View::e($conv['avatar_path'] ?: '/assets/img/default-avatar.svg') ?>" alt="">
                    <?= View::e($conv['username']) ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
    <a href="/dm.php" class="btn btn-block" style="margin-top: 0.5rem;">Nouvelle conversation</a>
</aside>
