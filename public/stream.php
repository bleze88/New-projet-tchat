<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use App\Models\DirectMessage;
use App\Models\Message;
use App\Support\Auth;

if (!Auth::check()) {
    http_response_code(401);
    exit;
}

$userId = (int) Auth::id();
$roomId = isset($_GET['room_id']) && $_GET['room_id'] !== '' ? (int) $_GET['room_id'] : null;
$lastMessageId = isset($_GET['last_message_id']) ? max(0, (int) $_GET['last_message_id']) : 0;
$lastDmId = isset($_GET['last_dm_id']) ? max(0, (int) $_GET['last_dm_id']) : DirectMessage::maxIdFor($userId);
$dmWith = isset($_GET['dm_with']) && $_GET['dm_with'] !== '' ? (int) $_GET['dm_with'] : null;

// Release the session file lock now: this script runs for up to MAX_DURATION
// seconds and must not block other tabs/requests (e.g. sending a message)
// from the same browser session.
session_write_close();

set_time_limit(0);
ignore_user_abort(false);

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // disable nginx buffering if ever proxied

while (ob_get_level() > 0) {
    ob_end_flush();
}

/**
 * @param array<string, mixed> $data
 */
function sse_emit(string $event, array $data, int $id): void
{
    echo "event: {$event}\n";
    echo 'data: ' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    echo "id: {$id}\n\n";
    flush();
}

echo "retry: 2000\n\n";
flush();

const MAX_DURATION = 60;
const POLL_INTERVAL_MICROSECONDS = 1_000_000;

$start = microtime(true);

while (microtime(true) - $start < MAX_DURATION) {
    if (connection_aborted()) {
        break;
    }

    if ($roomId !== null) {
        foreach (Message::since($roomId, $lastMessageId) as $message) {
            $lastMessageId = (int) $message['id'];
            sse_emit('message', [
                'id' => (int) $message['id'],
                'room_id' => (int) $message['room_id'],
                'user_id' => (int) $message['user_id'],
                'username' => $message['username'],
                'avatar_path' => $message['avatar_path'],
                'role' => $message['role'],
                'body' => \App\Support\BbCode::render((string) $message['body']),
                'created_at' => $message['created_at'],
            ], $lastMessageId);
        }
    }

    if ($dmWith !== null) {
        foreach (DirectMessage::since($userId, $dmWith, $lastDmId) as $dm) {
            $lastDmId = (int) $dm['id'];
            sse_emit('dm', [
                'id' => (int) $dm['id'],
                'sender_id' => (int) $dm['sender_id'],
                'recipient_id' => (int) $dm['recipient_id'],
                'sender_username' => $dm['sender_username'],
                'sender_avatar_path' => $dm['sender_avatar_path'],
                'body' => \App\Support\BbCode::render((string) $dm['body']),
                'created_at' => $dm['created_at'],
            ], $lastDmId);
        }
    } else {
        $newMax = DirectMessage::maxIdFor($userId);
        if ($newMax > $lastDmId) {
            $lastDmId = $newMax;
            sse_emit('dm-notify', ['last_dm_id' => $lastDmId], $lastDmId);
        }
    }

    echo ": heartbeat\n\n";
    flush();

    if (connection_aborted()) {
        break;
    }

    usleep(POLL_INTERVAL_MICROSECONDS);
}
