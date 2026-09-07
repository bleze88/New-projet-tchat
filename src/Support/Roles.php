<?php

declare(strict_types=1);

namespace App\Support;

final class Roles
{
    public const MEMBER = 'member';
    public const MODERATOR = 'moderator';
    public const ADMIN = 'admin';

    private const RANK = [
        self::MEMBER => 0,
        self::MODERATOR => 1,
        self::ADMIN => 2,
    ];

    public static function atLeast(string $role, string $minimum): bool
    {
        return (self::RANK[$role] ?? -1) >= (self::RANK[$minimum] ?? PHP_INT_MAX);
    }

    public static function isValid(string $role): bool
    {
        return isset(self::RANK[$role]);
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return array_keys(self::RANK);
    }
}
