<?php

namespace App\Support;

final class BrandLogo
{
    public const FILENAME = 'mojokerto.png';

    public static function filename(): string
    {
        return self::FILENAME;
    }

    public static function publicPath(): string
    {
        return public_path(self::FILENAME);
    }

    public static function assetUrl(): string
    {
        return asset(self::FILENAME);
    }

    /**
     * @return list<string>
     */
    public static function candidatePaths(): array
    {
        return array_values(array_unique([
            public_path(self::FILENAME),
            public_path('logo.png'),
            public_path('logo.jpg'),
            public_path('logo.jpeg'),
            public_path('images/logo.png'),
        ]));
    }

    public static function dataUri(): ?string
    {
        foreach (self::candidatePaths() as $path) {
            if (!is_string($path) || $path === '' || !is_file($path)) {
                continue;
            }
            $ext = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
            $mime = match ($ext) {
                'png' => 'image/png',
                'jpg', 'jpeg' => 'image/jpeg',
                'webp' => 'image/webp',
                default => 'application/octet-stream',
            };
            $raw = @file_get_contents($path);
            if ($raw !== false) {
                return 'data:' . $mime . ';base64,' . base64_encode($raw);
            }
        }

        return null;
    }
}
