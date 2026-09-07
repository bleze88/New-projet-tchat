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

    public static function nameTaken(string $name): bool
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM rooms WHERE name = :name');
        $stmt->execute(['name' => $name]);

        return ((int) $stmt->fetchColumn()) > 0;
    }

    public static function create(string $name): int
    {
        $slug = self::slugify($name);

        // Guarantee slug uniqueness even if two room names collapse to the
        // same slug (e.g. "Café" and "Cafe").
        $baseSlug = $slug;
        $suffix = 2;

        while (self::slugTaken($slug)) {
            $slug = $baseSlug . '-' . $suffix;
            $suffix++;
        }

        $stmt = Database::connection()->prepare('INSERT INTO rooms (name, slug) VALUES (:name, :slug)');
        $stmt->execute(['name' => $name, 'slug' => $slug]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function delete(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM rooms WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private static function slugTaken(string $slug): bool
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM rooms WHERE slug = :slug');
        $stmt->execute(['slug' => $slug]);

        return ((int) $stmt->fetchColumn()) > 0;
    }

    private static function slugify(string $name): string
    {
        $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name) ?: $name;
        $slug = strtolower(trim((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $transliterated), '-'));

        return $slug !== '' ? $slug : 'salon';
    }
}
