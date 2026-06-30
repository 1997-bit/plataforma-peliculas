<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Lista oficial TMDB (/genre/movie/list + /genre/tv/list combinadas, deduplicadas por id).
 * Hardcodeada porque es estable (casi nunca cambia) y evita una llamada HTTP extra
 * en cada carga de catalogo/perfil.
 */
final class GenerosTmdb
{
    public const LISTA = [
        28 => 'Acción', 12 => 'Aventura', 16 => 'Animación', 35 => 'Comedia',
        80 => 'Crimen', 99 => 'Documental', 18 => 'Drama', 10751 => 'Familia',
        14 => 'Fantasía', 36 => 'Historia', 27 => 'Terror', 10402 => 'Música',
        9648 => 'Misterio', 10749 => 'Romance', 878 => 'Ciencia ficción',
        10770 => 'Película de TV', 53 => 'Suspense', 10752 => 'Bélica', 37 => 'Western',
        10759 => 'Acción y Aventura', 10762 => 'Infantil', 10763 => 'Noticias',
        10764 => 'Reality', 10765 => 'Ciencia ficción y Fantasía', 10766 => 'Telenovela',
        10767 => 'Talk Show', 10768 => 'Guerra y Política',
    ];

    /**
     * @return list<array{id:int,nombre:string}>
     */
    public static function comoLista(): array
    {
        $items = [];
        foreach (self::LISTA as $id => $nombre) {
            $items[] = ['id' => $id, 'nombre' => $nombre];
        }

        usort($items, static fn (array $a, array $b): int => strcmp($a['nombre'], $b['nombre']));

        return $items;
    }
}
