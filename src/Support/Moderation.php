<?php

declare(strict_types=1);

namespace App\Support;

final class Moderation
{
    /** @var array<int, string> seconds => label */
    public const TIMEOUT_OPTIONS = [
        60 => '1 minute',
        300 => '5 minutes',
        900 => '15 minutes',
        1800 => '30 minutes',
        3600 => '1 heure',
        7200 => '2 heures',
    ];

    /** @var array<string, string> seconds (or "permanent") => label */
    public const BAN_OPTIONS = [
        '86400' => '1 jour',
        '259200' => '3 jours',
        '604800' => '1 semaine',
        '1209600' => '2 semaines',
        '2592000' => '1 mois',
        'permanent' => 'Définitivement',
    ];

    public static function isValidTimeoutSeconds(int $seconds): bool
    {
        return isset(self::TIMEOUT_OPTIONS[$seconds]);
    }

    /**
     * @return int|null Seconds, or null for a permanent ban. False if invalid.
     */
    public static function parseBanDuration(string $value): int|false|null
    {
        if ($value === 'permanent') {
            return null;
        }

        if (!isset(self::BAN_OPTIONS[$value])) {
            return false;
        }

        return (int) $value;
    }

    public static function formatRemaining(string $expiresAt): string
    {
        $diff = (new \DateTimeImmutable($expiresAt))->getTimestamp() - time();

        if ($diff <= 0) {
            return 'quelques instants';
        }
        if ($diff < 3600) {
            return ceil($diff / 60) . ' min';
        }
        if ($diff < 86400) {
            return ceil($diff / 3600) . ' h';
        }

        return ceil($diff / 86400) . ' j';
    }

    public static function formatUntil(?string $expiresAt): string
    {
        if ($expiresAt === null) {
            return 'définitivement';
        }

        return 'jusqu\'au ' . (new \DateTimeImmutable($expiresAt))->format('d/m/Y à H:i');
    }
}
