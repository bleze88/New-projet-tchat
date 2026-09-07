<?php

declare(strict_types=1);

namespace App\Support;

/**
 * A fixed set of ready-made avatar images the user can pick from instead of
 * uploading their own. The filename coming back from the profile form is
 * always checked against this whitelist before being turned into a path —
 * never trust it directly (that's how the old project's per-user folder
 * copying became a path-traversal risk).
 */
final class PresetAvatars
{
    private const COUNT = 16;

    /**
     * @return list<string> Public web paths, e.g. "/assets/img/avatars/1.png"
     */
    public static function all(): array
    {
        $paths = [];

        for ($i = 1; $i <= self::COUNT; $i++) {
            $paths[] = self::path($i);
        }

        return $paths;
    }

    public static function isValid(string $filename): bool
    {
        return (bool) preg_match('/^([1-9]|1[0-6])\.png$/', $filename);
    }

    private static function path(int $n): string
    {
        return '/assets/img/avatars/' . $n . '.png';
    }
}
