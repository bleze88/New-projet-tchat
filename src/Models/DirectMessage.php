<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database;
use PDO;

final class DirectMessage
{
    public static function create(int $senderId, int $recipientId, string $body): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO direct_messages (sender_id, recipient_id, body) VALUES (:sender_id, :recipient_id, :body)'
        );
        $stmt->execute(['sender_id' => $senderId, 'recipient_id' => $recipientId, 'body' => $body]);

        return (int) Database::connection()->lastInsertId();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function conversation(int $userId, int $otherUserId, int $limit = 50): array
    {
        // Native (non-emulated) prepared statements don't allow a named
        // placeholder to be reused more than once in the same query, so each
        // occurrence of user/other id gets its own placeholder name below.
        $stmt = Database::connection()->prepare(
            'SELECT dm.*, u.username AS sender_username, u.avatar_path AS sender_avatar_path
             FROM direct_messages dm
             JOIN users u ON u.id = dm.sender_id
             WHERE (dm.sender_id = :user_id1 AND dm.recipient_id = :other_id1)
                OR (dm.sender_id = :other_id2 AND dm.recipient_id = :user_id2)
             ORDER BY dm.id DESC
             LIMIT :limit'
        );
        $stmt->bindValue('user_id1', $userId, PDO::PARAM_INT);
        $stmt->bindValue('other_id1', $otherUserId, PDO::PARAM_INT);
        $stmt->bindValue('other_id2', $otherUserId, PDO::PARAM_INT);
        $stmt->bindValue('user_id2', $userId, PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return array_reverse($stmt->fetchAll());
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function since(int $userId, int $otherUserId, int $lastId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT dm.*, u.username AS sender_username, u.avatar_path AS sender_avatar_path
             FROM direct_messages dm
             JOIN users u ON u.id = dm.sender_id
             WHERE ((dm.sender_id = :user_id1 AND dm.recipient_id = :other_id1)
                OR (dm.sender_id = :other_id2 AND dm.recipient_id = :user_id2))
                AND dm.id > :last_id'
            . ' ORDER BY dm.id ASC'
        );
        $stmt->bindValue('user_id1', $userId, PDO::PARAM_INT);
        $stmt->bindValue('other_id1', $otherUserId, PDO::PARAM_INT);
        $stmt->bindValue('other_id2', $otherUserId, PDO::PARAM_INT);
        $stmt->bindValue('user_id2', $userId, PDO::PARAM_INT);
        $stmt->bindValue('last_id', $lastId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Latest message per conversation partner, for the DM inbox list.
     *
     * @return list<array<string, mixed>>
     */
    public static function conversationsFor(int $userId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT u.id AS user_id, u.username, u.avatar_path,
                    (SELECT dm2.body FROM direct_messages dm2
                        WHERE (dm2.sender_id = u.id AND dm2.recipient_id = :user_id1)
                           OR (dm2.sender_id = :user_id2 AND dm2.recipient_id = u.id)
                        ORDER BY dm2.id DESC LIMIT 1) AS last_body,
                    (SELECT dm3.created_at FROM direct_messages dm3
                        WHERE (dm3.sender_id = u.id AND dm3.recipient_id = :user_id3)
                           OR (dm3.sender_id = :user_id4 AND dm3.recipient_id = u.id)
                        ORDER BY dm3.id DESC LIMIT 1) AS last_at
             FROM users u
             WHERE u.id IN (
                 SELECT sender_id FROM direct_messages WHERE recipient_id = :user_id5
                 UNION
                 SELECT recipient_id FROM direct_messages WHERE sender_id = :user_id6
             )
             ORDER BY last_at DESC'
        );
        $stmt->execute([
            'user_id1' => $userId,
            'user_id2' => $userId,
            'user_id3' => $userId,
            'user_id4' => $userId,
            'user_id5' => $userId,
            'user_id6' => $userId,
        ]);

        return $stmt->fetchAll();
    }

    public static function maxIdFor(int $userId): int
    {
        $stmt = Database::connection()->prepare(
            'SELECT COALESCE(MAX(id), 0) FROM direct_messages WHERE sender_id = :user_id1 OR recipient_id = :user_id2'
        );
        $stmt->execute(['user_id1' => $userId, 'user_id2' => $userId]);

        return (int) $stmt->fetchColumn();
    }
}
