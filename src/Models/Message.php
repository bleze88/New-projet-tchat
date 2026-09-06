<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database;

final class Message
{
    public static function create(int $roomId, int $userId, string $body): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO messages (room_id, user_id, body) VALUES (:room_id, :user_id, :body)'
        );
        $stmt->execute(['room_id' => $roomId, 'user_id' => $userId, 'body' => $body]);

        return (int) Database::connection()->lastInsertId();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function recent(int $roomId, int $limit = 50): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT m.*, u.username, u.avatar_path
             FROM messages m
             JOIN users u ON u.id = m.user_id
             WHERE m.room_id = :room_id
             ORDER BY m.id DESC
             LIMIT :limit'
        );
        $stmt->bindValue('room_id', $roomId, \PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return array_reverse($stmt->fetchAll());
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function since(int $roomId, int $lastId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT m.*, u.username, u.avatar_path
             FROM messages m
             JOIN users u ON u.id = m.user_id
             WHERE m.room_id = :room_id AND m.id > :last_id
             ORDER BY m.id ASC'
        );
        $stmt->bindValue('room_id', $roomId, \PDO::PARAM_INT);
        $stmt->bindValue('last_id', $lastId, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
