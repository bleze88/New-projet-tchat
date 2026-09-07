<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database;

final class RoomTimeout
{
    public static function apply(int $roomId, int $userId, int $moderatorId, int $seconds): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO room_timeouts (room_id, user_id, moderator_id, expires_at)
             VALUES (:room_id, :user_id, :moderator_id, DATE_ADD(NOW(), INTERVAL :seconds SECOND))
             ON DUPLICATE KEY UPDATE
                moderator_id = VALUES(moderator_id),
                expires_at = VALUES(expires_at),
                created_at = CURRENT_TIMESTAMP'
        );
        $stmt->bindValue('room_id', $roomId, \PDO::PARAM_INT);
        $stmt->bindValue('user_id', $userId, \PDO::PARAM_INT);
        $stmt->bindValue('moderator_id', $moderatorId, \PDO::PARAM_INT);
        $stmt->bindValue('seconds', $seconds, \PDO::PARAM_INT);
        $stmt->execute();
    }

    public static function remove(int $roomId, int $userId): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM room_timeouts WHERE room_id = :room_id AND user_id = :user_id');
        $stmt->execute(['room_id' => $roomId, 'user_id' => $userId]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function active(int $roomId, int $userId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM room_timeouts WHERE room_id = :room_id AND user_id = :user_id AND expires_at > NOW()'
        );
        $stmt->execute(['room_id' => $roomId, 'user_id' => $userId]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * Active time-outs for a room, joined with the muted user's info.
     *
     * @return list<array<string, mixed>>
     */
    public static function activeForRoom(int $roomId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT rt.*, u.username
             FROM room_timeouts rt
             JOIN users u ON u.id = rt.user_id
             WHERE rt.room_id = :room_id AND rt.expires_at > NOW()
             ORDER BY rt.expires_at ASC'
        );
        $stmt->execute(['room_id' => $roomId]);

        return $stmt->fetchAll();
    }
}
