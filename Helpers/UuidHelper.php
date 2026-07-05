<?php

declare(strict_types=1);

namespace App\Helpers;

class UuidHelper
{
    /**
     * UUID v7 (ordenable por tiempo: timestamp de 48 bits + random).
     * Es el unico generador del proyecto: toda fila nueva (usuarios,
     * contenido) usa v7 porque el orden de insercion en BD coincide
     * con el orden de los bytes, lo que ayuda al indice. Reemplaza
     * ramsey/uuid sin perder nada: es solo random_bytes() + el
     * formato exacto que pide la RFC 9562.
     *
     * @return string 16 bytes binarios, listos para guardar en BINARY(16)
     */
    public static function v7(): string
    {
        $timestampMs = (int) (microtime(true) * 1000);

        // 6 bytes (48 bits) con el timestamp en milisegundos
        $tiempo = substr(pack('J', $timestampMs), 2, 6);

        $aleatorio = random_bytes(10);

        // version 7: los 4 bits altos del primer byte aleatorio deben ser 0111
        $aleatorio[0] = chr((ord($aleatorio[0]) & 0x0f) | 0x70);
        // variante RFC 4122: los 2 bits altos del tercer byte deben ser 10
        $aleatorio[2] = chr((ord($aleatorio[2]) & 0x3f) | 0x80);

        return $tiempo . $aleatorio;
    }

    public static function uuidABinario(string $uuid): string
    {
        $hex = str_replace('-', '', $uuid);

        if (!ctype_xdigit($hex) || strlen($hex) !== 32) {
            throw new \InvalidArgumentException("UUID invalido: $uuid");
        }

        return hex2bin($hex);
    }

    public static function binarioAUuid(string $bin): string
    {
        $hex = bin2hex($bin);
        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20)
        );
    }

    /** Convierte la columna binaria $campo de cada fila a UUID string. Repetido antes en varios Repo. */
    public static function mapearIds(array $filas, string $campo = 'id'): array
    {
        foreach ($filas as &$fila) {
            $fila[$campo] = self::binarioAUuid($fila[$campo]);
        }
        unset($fila);

        return $filas;
    }
}
