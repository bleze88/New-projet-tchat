<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $rooms */
/** @var list<array<string, mixed>> $conversations */
/** @var int|null $activeRoomId */
/** @var int|null $activeDmUserId */
?>
<div class="app-shell">
    <?php require __DIR__ . '/_sidebar.php'; ?>
    <div class="chat-panel">
        <div class="empty-state">
            <p>Choisis un salon à gauche pour commencer à discuter.</p>
        </div>
    </div>
</div>
