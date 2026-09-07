<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use App\Models\Ban;
use App\Models\Room;
use App\Models\RoomTimeout;
use App\Models\User;
use App\Support\Auth;
use App\Support\Csrf;
use App\Support\Moderation;
use App\Support\Roles;
use App\Support\View;

Auth::requireRole(Roles::MODERATOR);

$currentUser = Auth::user();
$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();

    $formName = (string) ($_POST['form'] ?? '');
    $targetId = (int) ($_POST['user_id'] ?? 0);
    $target = User::find($targetId);

    if ($formName === 'change_role' && Auth::hasRole(Roles::ADMIN)) {
        $newRole = (string) ($_POST['role'] ?? '');

        if ($target === null || !Roles::isValid($newRole)) {
            $error = 'Requête invalide.';
        } elseif ($target['role'] === Roles::ADMIN && $newRole !== Roles::ADMIN && User::countByRole(Roles::ADMIN) <= 1) {
            $error = 'Impossible de retirer le dernier compte admin.';
        } else {
            User::updateRole($targetId, $newRole);
            $success = 'Rôle mis à jour pour ' . $target['username'] . '.';
        }
    } elseif (in_array($formName, ['apply_ban', 'remove_ban', 'apply_timeout', 'remove_timeout'], true)) {
        if ($target === null || !Auth::canModerate($currentUser, $target)) {
            $error = 'Action de modération impossible sur cet utilisateur.';
        } elseif ($formName === 'apply_ban') {
            $duration = Moderation::parseBanDuration((string) ($_POST['ban_duration'] ?? ''));
            if ($duration === false) {
                $error = 'Durée de ban invalide.';
            } else {
                $reason = trim((string) ($_POST['reason'] ?? ''));
                Ban::apply($targetId, (int) $currentUser['id'], $duration, $reason !== '' ? $reason : null);
                $success = $target['username'] . ' a été banni ' . Moderation::formatUntil($duration === null ? null : (new DateTimeImmutable("+{$duration} seconds"))->format('Y-m-d H:i:s')) . '.';
            }
        } elseif ($formName === 'remove_ban') {
            Ban::remove($targetId);
            $success = 'Ban levé pour ' . $target['username'] . '.';
        } elseif ($formName === 'apply_timeout') {
            $roomId = (int) ($_POST['room_id'] ?? 0);
            $seconds = (int) ($_POST['duration_seconds'] ?? 0);
            $room = Room::find($roomId);

            if ($room === null || !Moderation::isValidTimeoutSeconds($seconds)) {
                $error = 'Salon ou durée invalide.';
            } else {
                RoomTimeout::apply($roomId, $targetId, (int) $currentUser['id'], $seconds);
                $success = $target['username'] . ' est en time-out sur ' . $room['name'] . '.';
            }
        } elseif ($formName === 'remove_timeout') {
            $roomId = (int) ($_POST['room_id'] ?? 0);
            RoomTimeout::remove($roomId, $targetId);
            $success = 'Time-out levé pour ' . $target['username'] . '.';
        }
    }
}

View::render('admin_users', [
    'pageTitle' => 'Utilisateurs',
    'users' => User::all(),
    'rooms' => Room::all(),
    'activeBans' => Ban::activeMap(),
    'currentUserId' => (int) $currentUser['id'],
    'currentUserRole' => $currentUser['role'],
    'isAdmin' => Auth::hasRole(Roles::ADMIN),
    'error' => $error,
    'success' => $success,
]);
