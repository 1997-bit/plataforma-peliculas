<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Construye URLs de imagenes de TMDB. Antes cada vista repetia a mano
 * el ternario "poster_path ? url completa : placeholder", con el ancho
 * (w92, w342, w500, w1280) tipeado distinto en cada lugar.
 */
final class TmdbImagen
{
    private const BASE = 'https://image.tmdb.org/t/p';

    /**
     * Poster con fallback al placeholder local si no hay poster_path.
     * Tamaños pensados para los usos reales del proyecto:
     * xs = listas compactas (historial/calificaciones), sm = cards de
     * shelf/grid, md = detalle de pelicula/serie.
     */
    public static function poster(?string $path, string $tamano = 'sm'): string
    {
        if (empty($path)) {
            return '/assets/images/placeholder.webp';
        }

        $ancho = match ($tamano) {
            'xs' => 'w92',
            'md' => 'w500',
            default => 'w342',
        };

        return self::BASE . '/' . $ancho . $path;
    }

    /**
     * Backdrop (imagen ancha de fondo). Sin fallback: si no hay
     * backdrop_path, las vistas usan null para no poner background-image.
     */
    public static function backdrop(?string $path): ?string
    {
        return !empty($path) ? self::BASE . '/w1280' . $path : null;
    }
}
