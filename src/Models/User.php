<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database;

final class User
{
    /**
     * @return array<string, mixed>|null
     */
    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();

        return $user === false ? null : $user;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findByUsername(string $username): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE username = :username');
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        return $user === false ? null : $user;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findByUsernameOrEmail(string $identifier): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE username = :identifier1 OR email = :identifier2');
        $stmt->execute(['identifier1' => $identifier, 'identifier2' => $identifier]);
        $user = $stmt->fetch();

        return $user === false ? null : $user;
    }

    public static function usernameOrEmailTaken(string $username, string $email): bool
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM users WHERE username = :username OR email = :email');
        $stmt->execute(['username' => $username, 'email' => $email]);

        return ((int) $stmt->fetchColumn()) > 0;
    }

    public static function create(string $username, string $email, string $password): int
    {
        $pdo = Database::connection();

        // The very first account on a fresh install becomes admin automatically,
        // so the role system is usable without ever needing a manual DB edit.
        $isFirstUser = ((int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn()) === 0;
        $role = $isFirstUser ? 'admin' : 'member';

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare(
            'INSERT INTO users (username, email, password_hash, role) VALUES (:username, :email, :password_hash, :role)'
        );
        $stmt->execute([
            'username' => $username,
            'email' => $email,
            'password_hash' => $hash,
            'role' => $role,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public static function updateAvatar(int $userId, string $avatarPath): void
    {
        $stmt = Database::connection()->prepare('UPDATE users SET avatar_path = :avatar_path WHERE id = :id');
        $stmt->execute(['avatar_path' => $avatarPath, 'id' => $userId]);
    }

    public static function updatePassword(int $userId, string $newPassword): void
    {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);

        $stmt = Database::connection()->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id');
        $stmt->execute(['password_hash' => $hash, 'id' => $userId]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function search(string $term, int $excludeUserId, int $limit = 20): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, username, avatar_path FROM users WHERE username LIKE :term AND id != :excluded ORDER BY username LIMIT :limit'
        );
        $stmt->bindValue('term', '%' . str_replace(['%', '_'], ['\\%', '\\_'], $term) . '%');
        $stmt->bindValue('excluded', $excludeUserId, \PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function all(): array
    {
        $stmt = Database::connection()->query('SELECT id, username, email, role FROM users ORDER BY username ASC');

        return $stmt->fetchAll();
    }

    public static function countByRole(string $role): int
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM users WHERE role = :role');
        $stmt->execute(['role' => $role]);

        return (int) $stmt->fetchColumn();
    }

    public static function updateRole(int $userId, string $role): void
    {
        $stmt = Database::connection()->prepare('UPDATE users SET role = :role WHERE id = :id');
        $stmt->execute(['role' => $role, 'id' => $userId]);
    }
}
