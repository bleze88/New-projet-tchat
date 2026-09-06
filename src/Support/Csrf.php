<?php

declare(strict_types=1);

namespace App\Support;

final class Csrf
{
    private const SESSION_KEY = 'csrf_token';

    public static function token(): string
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    public static function field(): string
    {
        $token = htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8');

        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }

    public static function verify(?string $submittedToken): bool
    {
        $expected = $_SESSION[self::SESSION_KEY] ?? null;

        if (!is_string($expected) || !is_string($submittedToken) || $submittedToken === '') {
            return false;
        }

        return hash_equals($expected, $submittedToken);
    }

    public static function requireValid(): void
    {
        if (!self::verify($_POST['csrf_token'] ?? null)) {
            http_response_code(419);
            exit('Session expiree ou requete invalide (CSRF). Rechargez la page et reessayez.');
        }
    }
}
