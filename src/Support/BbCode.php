<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Renders user-submitted chat text as safe HTML.
 *
 * The old project's chat did the opposite: it took raw user input and spliced
 * it straight into href/src attributes with a regex, with no HTML escaping and
 * no URL scheme check (stored XSS). Here the raw text is ALWAYS escaped first;
 * only a small safelist of tags is then reconstructed on top of the already-escaped
 * text, and any URL (link or image) must parse as an absolute http(s) URL before
 * it is allowed into an attribute.
 */
final class BbCode
{
    public static function render(string $raw): string
    {
        $escaped = htmlspecialchars($raw, ENT_QUOTES, 'UTF-8');

        $escaped = preg_replace('/\[b\](.*?)\[\/b\]/su', '<strong>$1</strong>', $escaped) ?? $escaped;
        $escaped = preg_replace('/\[i\](.*?)\[\/i\]/su', '<em>$1</em>', $escaped) ?? $escaped;
        $escaped = preg_replace('/\[u\](.*?)\[\/u\]/su', '<u>$1</u>', $escaped) ?? $escaped;

        $escaped = preg_replace_callback('/\[url=([^\]]+)\](.*?)\[\/url\]/su', static function (array $m): string {
            $url = self::sanitizeUrl($m[1]);

            if ($url === null) {
                return $m[2];
            }

            return '<a href="' . $url . '" target="_blank" rel="noopener noreferrer nofollow">' . $m[2] . '</a>';
        }, $escaped) ?? $escaped;

        $escaped = preg_replace_callback('/\[img\](.*?)\[\/img\]/su', static function (array $m): string {
            $url = self::sanitizeUrl($m[1]);

            if ($url === null) {
                return '';
            }

            return '<a href="' . $url . '" target="_blank" rel="noopener noreferrer nofollow">'
                . '<img src="' . $url . '" alt="image partagee" loading="lazy" class="chat-image"></a>';
        }, $escaped) ?? $escaped;

        return nl2br($escaped, false);
    }

    private static function sanitizeUrl(string $escapedUrl): ?string
    {
        $decoded = trim(htmlspecialchars_decode($escapedUrl, ENT_QUOTES));

        if (!preg_match('#^https?://#i', $decoded)) {
            return null;
        }

        $parts = parse_url($decoded);

        if ($parts === false || empty($parts['host'])) {
            return null;
        }

        return htmlspecialchars($decoded, ENT_QUOTES, 'UTF-8');
    }
}
