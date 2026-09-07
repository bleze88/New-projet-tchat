<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use App\Models\DirectMessage;
use App\Models\Room;
use App\Support\Auth;
use App\Support\Csrf;
use App\Support\Roles;
use App\Support\Validator;
use App\Support\View;

Auth::requireLogin();

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();

    $formName = (string) ($_POST['form'] ?? '');

    if ($formName === 'create_room' && Auth::hasRole(Roles::MODERATOR)) {
        $name = trim((string) ($_POST['name'] ?? ''));

        if (!Validator::roomName($name)) {
            $error = 'Le nom du salon doit faire entre 1 et 64 caractères.';
        } elseif (Room::nameTaken($name)) {
            $error = 'Un salon porte déjà ce nom.';
        } else {
            $newId = Room::create($name);
            header('Location: /room.php?id=' . $newId);
            exit;
        }
    } elseif ($formName === 'delete_room' && Auth::hasRole(Roles::ADMIN)) {
        $roomId = (int) ($_POST['room_id'] ?? 0);
        Room::delete($roomId);
        header('Location: /rooms.php');
        exit;
    }
}

$rooms = Room::all();
$conversations = DirectMessage::conversationsFor((int) Auth::id());

View::render('rooms', [
    'pageTitle' => 'Salons',
    'rooms' => $rooms,
    'conversations' => $conversations,
    'activeRoomId' => null,
    'activeDmUserId' => null,
    'error' => $error,
]);
