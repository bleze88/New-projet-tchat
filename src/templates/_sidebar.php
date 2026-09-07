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
        <?php foreach ($rooms as $sidebarRoom): ?>
            <li>
                <a href="/room.php?id=<?= (int) $sidebarRoom['id'] ?>" class="<?= $activeRoomId === (int) $sidebarRoom['id'] ? 'active' : '' ?>">
                    # <?= View::e($sidebarRoom['name']) ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <h2>Messages privés</h2>
    <ul class="sidebar-list">
        <?php if (empty($conversations)): ?>
            <li style="padding: 0.5rem; color: var(--fg-muted); font-size: 0.85rem;">Aucune conversation</li>
        <?php endif; ?>
        <?php foreach ($conversations as $sidebarConv): ?>
            <li>
                <a href="/dm.php?with=<?= (int) $sidebarConv['user_id'] ?>" class="<?= $activeDmUserId === (int) $sidebarConv['user_id'] ? 'active' : '' ?>">
                    <img class="avatar avatar-sm" src="<?= View::e($sidebarConv['avatar_path'] ?: '/assets/img/default-avatar.svg') ?>" alt="">
                    <?= View::e($sidebarConv['username']) ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
    <a href="/dm.php" class="btn btn-block" style="margin-top: 0.5rem;">Nouvelle conversation</a>
</aside>
