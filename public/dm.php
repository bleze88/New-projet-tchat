<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use App\Models\DirectMessage;
use App\Models\Room;
use App\Models\User;
use App\Support\Auth;
use App\Support\Csrf;
use App\Support\RateLimiter;
use App\Support\Validator;
use App\Support\View;

Auth::requireLogin();

$currentUserId = (int) Auth::id();
$otherUserId = isset($_GET['with']) ? (int) $_GET['with'] : null;
$otherUser = null;
$error = null;

if ($otherUserId !== null) {
    if ($otherUserId === $currentUserId) {
        http_response_code(400);
        exit('Impossible de démarrer une conversation avec soi-même.');
    }

    $otherUser = User::find($otherUserId);

    if ($otherUser === null) {
        http_response_code(404);
        exit('Utilisateur introuvable.');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();

    $recipientId = (int) ($_POST['recipient_id'] ?? 0);
    $body = (string) ($_POST['body'] ?? '');
    $recipient = User::find($recipientId);

    if ($recipient === null || $recipientId === $currentUserId) {
        $error = 'Destinataire invalide.';
    } elseif (!Validator::messageBody($body)) {
        $error = 'Le message est vide ou trop long (1000 caractères max).';
    } elseif (!RateLimiter::attempt((string) $currentUserId, 'dm_message', 5, 10)) {
        $error = 'Tu envoies des messages trop vite, patiente un instant.';
    } else {
        DirectMessage::create($currentUserId, $recipientId, trim($body));
        header('Location: /dm.php?with=' . $recipientId);
        exit;
    }

    $otherUserId = $recipientId;
    $otherUser = $recipient;
}

$searchTerm = trim((string) ($_GET['q'] ?? ''));
$searchResults = $searchTerm !== '' ? User::search($searchTerm, $currentUserId) : [];

$conversations = DirectMessage::conversationsFor($currentUserId);
$thread = $otherUserId !== null && $otherUser !== null
    ? DirectMessage::conversation($currentUserId, $otherUserId, 50)
    : [];

View::render('dm', [
    'pageTitle' => $otherUser !== null ? 'Message à ' . $otherUser['username'] : 'Messages privés',
    'rooms' => Room::all(),
    'conversations' => $conversations,
    'activeRoomId' => null,
    'activeDmUserId' => $otherUserId,
    'otherUser' => $otherUser,
    'thread' => $thread,
    'error' => $error,
    'currentUserId' => $currentUserId,
    'searchTerm' => $searchTerm,
    'searchResults' => $searchResults,
]);
