<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use App\Models\DirectMessage;
use App\Models\Message;
use App\Models\Room;
use App\Support\Auth;
use App\Support\Csrf;
use App\Support\RateLimiter;
use App\Support\Validator;
use App\Support\View;

Auth::requireLogin();

$roomId = (int) ($_GET['id'] ?? 0);
$room = Room::find($roomId);

if ($room === null) {
    http_response_code(404);
    exit('Salon introuvable.');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();

    $body = (string) ($_POST['body'] ?? '');

    if (!Validator::messageBody($body)) {
        $error = 'Le message est vide ou trop long (1000 caractères max).';
    } elseif (!RateLimiter::attempt((string) Auth::id(), 'chat_message', 5, 10)) {
        $error = 'Tu envoies des messages trop vite, patiente un instant.';
    } else {
        Message::create($roomId, (int) Auth::id(), trim($body));
        header('Location: /room.php?id=' . $roomId);
        exit;
    }
}

$messages = Message::recent($roomId, 50);
$conversations = DirectMessage::conversationsFor((int) Auth::id());

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
]);
