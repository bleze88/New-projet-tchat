<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use App\Models\DirectMessage;
use App\Models\Room;
use App\Support\Auth;
use App\Support\View;

Auth::requireLogin();

$rooms = Room::all();
$conversations = DirectMessage::conversationsFor((int) Auth::id());

View::render('rooms', [
    'pageTitle' => 'Salons',
    'rooms' => $rooms,
    'conversations' => $conversations,
    'activeRoomId' => null,
    'activeDmUserId' => null,
]);
