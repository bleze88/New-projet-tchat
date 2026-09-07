<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;

final class Auth
{
    /**
     * @return array<string, mixed>|null
     */
    public static function user(): ?array
    {
        if (empty($_SESSION['user_id'])) {
            return null;
        }

        $user = User::find((int) $_SESSION['user_id']);

        if ($user === null) {
            self::logout();
            return null;
        }

        return $user;
    }

    public static function id(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    public static function check(): bool
    {
        return self::id() !== null;
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: /login.php');
            exit;
        }
    }

    public static function hasRole(string $minimum): bool
    {
        $user = self::user();

        return $user !== null && Roles::atLeast($user['role'], $minimum);
    }

    public static function requireRole(string $minimum): void
    {
        self::requireLogin();

        if (!self::hasRole($minimum)) {
            http_response_code(403);
            exit('Accès refusé.');
        }
    }

    public static function login(int $userId): void
    {
        Session::regenerate();
        $_SESSION['user_id'] = $userId;
    }

    public static function logout(): void
    {
        Session::destroy();
    }
}
