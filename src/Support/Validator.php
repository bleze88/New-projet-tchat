<?php

declare(strict_types=1);

namespace App\Support;

final class Validator
{
    public static function username(string $value): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9_-]{3,32}$/', $value);
    }

    public static function email(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false && strlen($value) <= 255;
    }

    public static function password(string $value): bool
    {
        return strlen($value) >= 8 && strlen($value) <= 255;
    }

    public static function messageBody(string $value): bool
    {
        $trimmed = trim($value);

        return $trimmed !== '' && mb_strlen($trimmed) <= 1000;
    }
}
