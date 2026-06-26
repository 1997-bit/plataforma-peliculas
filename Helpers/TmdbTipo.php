<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * "Nuestro" tipo es siempre movie|series. TMDB usa movie|tv. Esta clase
 * es el unico lugar que traduce entre los dos, en vez de repetir el
 * ternario ($tipo === 'series' ? 'tv' : 'movie') en cada controller.
 */
final class TmdbTipo
{
    /** Normaliza cualquier valor crudo (de $_GET, etc) a 'movie'|'series'. */
    public static function normalizar(mixed $tipoCrudo): string
    {
        return $tipoCrudo === 'series' ? 'series' : 'movie';
    }

    /** Convierte nuestro tipo ('movie'|'series') al recurso de TMDB ('movie'|'tv'). */
    public static function aRecursoTmdb(string $tipo): string
    {
        return $tipo === 'series' ? 'tv' : 'movie';
    }

    /**
     * Clamp comun para el parametro de pagina ($_GET['page']): entero,
     * minimo 1, maximo el limite que le pasen (TMDB tope real es 500).
     */
    public static function pagina(mixed $pageCrudo, int $maximo = 500): int
    {
        return max(1, min($maximo, (int) ($pageCrudo ?? 1)));
    }
}
