<?php

declare(strict_types=1);

namespace App\Helpers;

final class TmdbImagen
{
    private const BASE = 'https://image.tmdb.org/t/p';

    private static function esLocal(string $path): bool
    {
        return str_starts_with($path, '/assets/') || str_starts_with($path, 'http');
    }

    public static function poster(?string $path, string $tamano = 'sm'): string
    {
        if (empty($path)) {
            return '/assets/images/placeholder.webp';
        }

        if (self::esLocal($path)) {
            return $path;
        }

        $ancho = match ($tamano) {
            'xs' => 'w92',
            'md' => 'w500',
            default => 'w342',
        };

        return self::BASE . '/' . $ancho . $path;
    }

    public static function backdrop(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        return self::esLocal($path) ? $path : self::BASE . '/w1280' . $path;
    }

    public static function logo(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        return self::esLocal($path) ? $path : self::BASE . '/w300' . $path;
    }
}
