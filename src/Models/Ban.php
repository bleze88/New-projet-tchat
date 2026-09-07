<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database;

final class Ban
{
    /**
     * @param int|null $seconds null means a permanent ban (no expiry)
     */
    public static function apply(int $userId, int $moderatorId, ?int $seconds, ?string $reason): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO bans (user_id, moderator_id, expires_at, reason)
             VALUES (:user_id, :moderator_id, :expires_at, :reason)
             ON DUPLICATE KEY UPDATE
                moderator_id = VALUES(moderator_id),
                expires_at = VALUES(expires_at),
                reason = VALUES(reason),
                created_at = CURRENT_TIMESTAMP'
        );
        $stmt->bindValue('user_id', $userId, \PDO::PARAM_INT);
        $stmt->bindValue('moderator_id', $moderatorId, \PDO::PARAM_INT);

        if ($seconds === null) {
            $stmt->bindValue('expires_at', null, \PDO::PARAM_NULL);
        } else {
            $stmt->bindValue('expires_at', (new \DateTimeImmutable())->modify("+{$seconds} seconds")->format('Y-m-d H:i:s'));
        }

        $stmt->bindValue('reason', $reason !== null && $reason !== '' ? $reason : null);
        $stmt->execute();
    }

    public static function remove(int $userId): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM bans WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function active(int $userId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM bans WHERE user_id = :user_id AND (expires_at IS NULL OR expires_at > NOW())'
        );
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * All currently active bans, keyed by user_id, for a bulk listing (e.g. the admin users page).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function activeMap(): array
    {
        $stmt = Database::connection()->query(
            'SELECT * FROM bans WHERE expires_at IS NULL OR expires_at > NOW()'
        );

        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $map[(int) $row['user_id']] = $row;
        }

        return $map;
    }
}
