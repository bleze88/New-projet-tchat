<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use App\Models\Ban;
use App\Models\DirectMessage;
use App\Models\Message;
use App\Models\Room;
use App\Models\RoomTimeout;
use App\Models\User;
use App\Support\Auth;
use App\Support\Csrf;
use App\Support\Moderation;
use App\Support\RateLimiter;
use App\Support\Roles;
use App\Support\Validator;
use App\Support\View;

Auth::requireLogin();

$roomId = (int) ($_GET['id'] ?? 0);
$room = Room::find($roomId);

if ($room === null) {
    http_response_code(404);
    exit('Salon introuvable.');
}

$currentUser = Auth::user();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();

    $formName = (string) ($_POST['form'] ?? 'send_message');

    if ($formName === 'delete_message' && Auth::hasRole(Roles::MODERATOR)) {
        Message::delete((int) ($_POST['message_id'] ?? 0));
        header('Location: /room.php?id=' . $roomId);
        exit;
    }

    if (in_array($formName, ['apply_timeout', 'remove_timeout', 'apply_ban'], true)) {
        $targetUser = User::find((int) ($_POST['target_user_id'] ?? 0));

        if ($targetUser === null || !Auth::canModerate($currentUser, $targetUser)) {
            $error = 'Action de modération impossible sur cet utilisateur.';
        } elseif ($formName === 'apply_timeout') {
            $seconds = (int) ($_POST['duration_seconds'] ?? 0);
            if (Moderation::isValidTimeoutSeconds($seconds)) {
                RoomTimeout::apply($roomId, (int) $targetUser['id'], (int) $currentUser['id'], $seconds);
            }
            header('Location: /room.php?id=' . $roomId);
            exit;
        } elseif ($formName === 'remove_timeout') {
            RoomTimeout::remove($roomId, (int) $targetUser['id']);
            header('Location: /room.php?id=' . $roomId);
            exit;
        } elseif ($formName === 'apply_ban') {
            $duration = Moderation::parseBanDuration((string) ($_POST['ban_duration'] ?? ''));
            if ($duration !== false) {
                $reason = trim((string) ($_POST['reason'] ?? ''));
                Ban::apply((int) $targetUser['id'], (int) $currentUser['id'], $duration, $reason !== '' ? $reason : null);
            }
            header('Location: /room.php?id=' . $roomId);
            exit;
        }
    }

    if ($formName === 'send_message') {
        $timeout = RoomTimeout::active($roomId, (int) $currentUser['id']);
        $body = (string) ($_POST['body'] ?? '');

        if ($timeout !== null) {
            $error = 'Tu es en time-out sur ce salon encore ' . Moderation::formatRemaining($timeout['expires_at']) . '.';
        } elseif (!Validator::messageBody($body)) {
            $error = 'Le message est vide ou trop long (1000 caractères max).';
        } elseif (!RateLimiter::attempt((string) Auth::id(), 'chat_message', 5, 10)) {
            $error = 'Tu envoies des messages trop vite, patiente un instant.';
        } else {
            Message::create($roomId, (int) Auth::id(), trim($body));
            header('Location: /room.php?id=' . $roomId);
            exit;
        }
    }
}

$messages = Message::recent($roomId, 50);
$conversations = DirectMessage::conversationsFor((int) Auth::id());
$canModerate = Auth::hasRole(Roles::MODERATOR);
$myTimeout = RoomTimeout::active($roomId, (int) $currentUser['id']);

View::render('room', [
    'pageTitle' => $room['name'],
    'room' => $room,
    'rooms' => \App\Models\Room::all(),
    'conversations' => $conversations,
    'activeRoomId' => $roomId,
    'activeDmUserId' => null,
    'messages' => $messages,
    'error' => $error,
    'currentUserId' => (int) Auth::id(),
    'currentUserRole' => $currentUser['role'],
    'canModerate' => $canModerate,
    'myTimeout' => $myTimeout,
    'activeTimeouts' => $canModerate ? RoomTimeout::activeForRoom($roomId) : [],
]);
