<?php

declare(strict_types=1);

namespace App\Helpers;

final class Normalizador
{
    /** DB da: titulo, poster_path, backdrop_path, logo_path, anio_lanzamiento. Vistas esperan: title, name, release_date, first_air_date. */
    public static function contenido(array $fila): array
    {
        return [
            'id' => $fila['id'],
            'type' => $fila['type'],
            'title' => $fila['titulo'],
            'name' => $fila['titulo'],
            'overview' => $fila['descripcion'] ?? '',
            'poster_path' => $fila['poster_path'] ?? null,
            'backdrop_path' => $fila['backdrop_path'] ?? null,
            'logo_path' => $fila['logo_path'] ?? null,
            'release_date' => isset($fila['anio_lanzamiento']) ? $fila['anio_lanzamiento'] . '-01-01' : null,
            'first_air_date' => isset($fila['anio_lanzamiento']) ? $fila['anio_lanzamiento'] . '-01-01' : null,
            'origen' => 'local',
        ];
    }

    /** @param list<array<string,mixed>> $filas @return list<array<string,mixed>> */
    public static function lista(array $filas): array
    {
        return array_map(self::contenido(...), $filas);
    }
}
