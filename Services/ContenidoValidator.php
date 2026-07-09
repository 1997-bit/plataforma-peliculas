<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Valida/sanea campos de contenido (titulo, tipo, descripcion, anio, generos).
 * Compartido entre el form del panel admin (AdminController) y la API REST
 * de import (ContenidoApiController) para no duplicar reglas en dos lados.
 */
final class ContenidoValidator
{
    /**
     * @param array<string,mixed> $datos titulo, tipo, descripcion, anio, generos (list<int>)
     * @param array<int,array{id:int,nombre:string,tmdb_id:?int}> $generosDisponibles ids validos de la tabla generos
     * @return array{titulo:string,descripcion:?string,tipo:string,anio:?int,generoIds:list<int>,errores:list<string>}
     */
    public static function validar(array $datos, array $generosDisponibles): array
    {
        $errores = [];

        // strip_tags saca cualquier intento de <script>/HTML embebido. El
        // escape para mostrarlo (htmlspecialchars) se hace en la vista,
        // nunca al guardar, para no guardar entidades HTML dobles.
        $titulo = trim(strip_tags((string) ($datos['titulo'] ?? '')));
        $titulo = mb_substr($titulo, 0, 255);
        if ($titulo === '') {
            $errores[] = 'El título es obligatorio.';
        }

        $descripcionCruda = trim(strip_tags((string) ($datos['descripcion'] ?? '')));
        $descripcion = $descripcionCruda !== '' ? mb_substr($descripcionCruda, 0, 2000) : null;

        $tipo = (string) ($datos['tipo'] ?? '');
        if (!in_array($tipo, ['movie', 'series'], true)) {
            $errores[] = 'El tipo debe ser "movie" o "series".';
            $tipo = 'movie';
        }

        $anioCrudo = trim((string) ($datos['anio'] ?? ''));
        $anio = null;
        if ($anioCrudo !== '') {
            if (!ctype_digit($anioCrudo) || (int) $anioCrudo < 1888 || (int) $anioCrudo > ((int) date('Y') + 1)) {
                $errores[] = 'El año no es válido.';
            } else {
                $anio = (int) $anioCrudo;
            }
        }

        $idsValidos = array_map('intval', array_column($generosDisponibles, 'id'));
        $generoIdsCrudos = is_array($datos['generos'] ?? null) ? $datos['generos'] : [];
        $generoIds = array_values(array_intersect(array_map('intval', $generoIdsCrudos), $idsValidos));

        return [
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'tipo' => $tipo,
            'anio' => $anio,
            'generoIds' => $generoIds,
            'errores' => $errores,
        ];
    }
}
