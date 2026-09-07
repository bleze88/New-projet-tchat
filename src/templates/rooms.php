<?php

declare(strict_types=1);

use App\Support\Auth;
use App\Support\Csrf;
use App\Support\Roles;
use App\Support\View;

/** @var list<array<string, mixed>> $rooms */
/** @var list<array<string, mixed>> $conversations */
/** @var int|null $activeRoomId */
/** @var int|null $activeDmUserId */
/** @var string|null $error */

$canCreateRooms = Auth::hasRole(Roles::MODERATOR);
$canDeleteRooms = Auth::hasRole(Roles::ADMIN);
?>
<div class="app-shell">
    <?php require __DIR__ . '/_sidebar.php'; ?>
    <div class="chat-panel">
        <div class="empty-state">
            <p>Choisis un salon à gauche pour commencer à discuter.</p>
        </div>

        <?php if ($canCreateRooms): ?>
            <div class="card" style="max-width: 420px; margin: 0 auto 1.5rem;">
                <?php if ($error !== null): ?>
                    <p class="alert alert-error"><?= View::e($error) ?></p>
                <?php endif; ?>
                <h2 style="font-size: 0.95rem; margin-bottom: 0.5rem;">Créer un salon</h2>
                <form method="post" action="/rooms.php">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="form" value="create_room">
                    <div class="field">
                        <label for="name">Nom du salon</label>
                        <input type="text" id="name" name="name" maxlength="64" required autofocus>
                    </div>
                    <button type="submit" class="btn">Créer</button>
                </form>
            </div>
        <?php endif; ?>

        <?php if ($canDeleteRooms): ?>
            <div class="card" style="max-width: 420px; margin: 0 auto;">
                <h2 style="font-size: 0.95rem; margin-bottom: 0.5rem;">Gérer les salons</h2>
                <ul class="sidebar-list">
                    <?php foreach ($rooms as $manageableRoom): ?>
                        <li style="display: flex; align-items: center; justify-content: space-between; padding: 0.35rem 0.5rem;">
                            <span># <?= View::e($manageableRoom['name']) ?></span>
                            <form method="post" action="/rooms.php" onsubmit="return confirm('Supprimer ce salon et tous ses messages ?');">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="form" value="delete_room">
                                <input type="hidden" name="room_id" value="<?= (int) $manageableRoom['id'] ?>">
                                <button type="submit" class="link-button" style="color: var(--danger);">Supprimer</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</div>
