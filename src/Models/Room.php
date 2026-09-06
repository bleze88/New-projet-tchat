<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database;

final class Room
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function all(): array
    {
        $stmt = Database::connection()->query('SELECT * FROM rooms ORDER BY name ASC');

        return $stmt->fetchAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM rooms WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $room = $stmt->fetch();

        return $room === false ? null : $room;
    }
}
