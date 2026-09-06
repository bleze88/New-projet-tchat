<?php

declare(strict_types=1);

namespace App\Support;

final class RateLimiter
{
    /**
     * Returns true if the action is allowed (and records it). Returns false if
     * $maxAttempts have already happened within $windowSeconds for this identifier+action.
     */
    public static function attempt(string $identifier, string $action, int $maxAttempts, int $windowSeconds): bool
    {
        $pdo = Database::connection();

        // Lazily purge old rows for this action so the table doesn't grow unbounded.
        $purge = $pdo->prepare('DELETE FROM rate_limits WHERE action = :action AND created_at < (NOW() - INTERVAL :window SECOND)');
        $purge->execute(['action' => $action, 'window' => $windowSeconds]);

        $count = $pdo->prepare(
            'SELECT COUNT(*) FROM rate_limits WHERE identifier = :identifier AND action = :action AND created_at >= (NOW() - INTERVAL :window SECOND)'
        );
        $count->execute(['identifier' => $identifier, 'action' => $action, 'window' => $windowSeconds]);

        if ((int) $count->fetchColumn() >= $maxAttempts) {
            return false;
        }

        $insert = $pdo->prepare('INSERT INTO rate_limits (identifier, action) VALUES (:identifier, :action)');
        $insert->execute(['identifier' => $identifier, 'action' => $action]);

        return true;
    }
}
