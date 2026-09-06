<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Validates and stores an uploaded avatar image.
 *
 * The legacy version trusted the client-supplied filename, kept the original
 * name (path traversal / overwrite risk), used the raw username as a folder
 * name, and had an `&&` instead of `||` in its dimension check that let
 * oversized images through. Here: content is sniffed (not trusted by
 * extension/name), the stored filename is always server-generated random
 * bytes, and every limit is a hard rejection.
 */
final class AvatarUpload
{
    private const MAX_BYTES = 2 * 1024 * 1024; // 2 MB
    private const MAX_WIDTH = 512;
    private const MAX_HEIGHT = 512;

    private const ALLOWED_MIME_TO_EXT = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    /**
     * @param array<string, mixed> $file One entry of $_FILES
     * @return string Public web path of the stored avatar (e.g. /uploads/avatars/xxxx.png)
     * @throws \RuntimeException on any validation failure
     */
    public static function store(array $file): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException("Échec de l'envoi du fichier.");
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            throw new \RuntimeException('Fichier invalide.');
        }

        if ((int) $file['size'] > self::MAX_BYTES) {
            throw new \RuntimeException('Le fichier dépasse la taille maximale de 2 Mo.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);

        if ($mime === false || !isset(self::ALLOWED_MIME_TO_EXT[$mime])) {
            throw new \RuntimeException('Format non supporté (png, jpg, gif ou webp uniquement).');
        }

        $dimensions = getimagesize($file['tmp_name']);

        if ($dimensions === false) {
            throw new \RuntimeException("Le fichier n'est pas une image valide.");
        }

        [$width, $height] = $dimensions;

        if ($width > self::MAX_WIDTH || $height > self::MAX_HEIGHT) {
            throw new \RuntimeException('Image trop grande (512x512 px maximum).');
        }

        $extension = self::ALLOWED_MIME_TO_EXT[$mime];
        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $destination = self::uploadDir() . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new \RuntimeException("Échec de l'enregistrement du fichier.");
        }

        return '/uploads/avatars/' . $filename;
    }

    private static function uploadDir(): string
    {
        return dirname(__DIR__, 2) . '/public/uploads/avatars';
    }
}
