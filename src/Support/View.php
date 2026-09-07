<?php

declare(strict_types=1);

namespace App\Support;

final class View
{
    public static function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }

    public static function roleBadge(?string $role): string
    {
        if ($role === Roles::ADMIN) {
            return '<span class="role-badge role-badge-admin">admin</span>';
        }

        if ($role === Roles::MODERATOR) {
            return '<span class="role-badge role-badge-moderator">mod</span>';
        }

        return '';
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function render(string $template, array $data = [], bool $withLayout = true): void
    {
        $__templatePath = dirname(__DIR__) . '/templates/' . $template . '.php';

        if (!is_file($__templatePath)) {
            throw new \RuntimeException("Template introuvable : {$template}");
        }

        extract($data, EXTR_SKIP);

        if ($withLayout) {
            $__contentPath = $__templatePath;
            require dirname(__DIR__) . '/templates/layout.php';
        } else {
            require $__templatePath;
        }
    }
}
