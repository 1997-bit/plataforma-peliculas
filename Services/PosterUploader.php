<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Sube y valida un poster (mismo approach de seguridad que ISSUE-10):
 *  1. UPLOAD_ERR_OK + is_uploaded_file (evita path injection via tmp_name)
 *  2. limite de tamaño
 *  3. extension dentro de whitelist cerrada
 *  4. mime REAL del contenido via finfo (no confia en $_FILES['type'], que
 *     lo manda el cliente y se puede falsificar)
 *  5. getimagesize() confirma que el binario es una imagen real, no un
 *     .php disfrazado con extension .jpg
 *  6. nombre de archivo random (nunca el nombre original) -> evita
 *     path traversal y colisiones
 *
 * Compartido entre AdminController (panel) y ContenidoApiController (REST),
 * asi el criterio de seguridad es UNO solo en todo el proyecto.
 */
final class PosterUploader
{
    private const MAX_BYTES = 3 * 1024 * 1024; // 3MB

    private const MIME_PERMITIDOS = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
    ];

    /**
     * @param array<string,mixed>|null $archivo un item de $_FILES
     * @return array{path:?string,error:?string}
     */
    public static function subir(?array $archivo, bool $obligatorio): array
    {
        $sinArchivo = !is_array($archivo) || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE;

        if ($sinArchivo) {
            if ($obligatorio) {
                return ['path' => null, 'error' => 'Debes subir un poster.'];
            }
            // en editar/import parcial, "no mandaron nada nuevo" no es error.
            return ['path' => null, 'error' => null];
        }

        if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['path' => null, 'error' => 'Hubo un error al subir el poster.'];
        }

        if (!is_string($archivo['tmp_name']) || !is_uploaded_file($archivo['tmp_name'])) {
            return ['path' => null, 'error' => 'No se pudo leer el archivo subido.'];
        }

        if (($archivo['size'] ?? 0) > self::MAX_BYTES) {
            return ['path' => null, 'error' => 'El poster no puede pesar más de 3MB.'];
        }

        $extension = strtolower(pathinfo((string) $archivo['name'], PATHINFO_EXTENSION));
        if (!isset(self::MIME_PERMITIDOS[$extension])) {
            return ['path' => null, 'error' => 'Formato de poster no permitido (solo jpg, png o webp).'];
        }

        // El mime real del contenido, no lo que mande el header del cliente.
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeReal = $finfo ? finfo_file($finfo, $archivo['tmp_name']) : false;
        if ($finfo) {
            finfo_close($finfo);
        }

        if ($mimeReal !== self::MIME_PERMITIDOS[$extension]) {
            return ['path' => null, 'error' => 'El archivo no es una imagen válida.'];
        }

        // getimagesize confirma que el binario decodifica como imagen real
        // (un .php renombrado a .jpg pasaria la validacion de mime pero
        // fallaria aca).
        if (@getimagesize($archivo['tmp_name']) === false) {
            return ['path' => null, 'error' => 'El archivo no es una imagen válida.'];
        }

        $carpetaDestino = ROOT . '/public/assets/images/posters/admin';
        if (!is_dir($carpetaDestino) && !mkdir($carpetaDestino, 0755, true) && !is_dir($carpetaDestino)) {
            return ['path' => null, 'error' => 'No se pudo guardar el poster.'];
        }

        $nombreArchivo = bin2hex(random_bytes(16)) . '.' . $extension;
        $rutaDestino = $carpetaDestino . '/' . $nombreArchivo;

        if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
            return ['path' => null, 'error' => 'No se pudo guardar el poster.'];
        }

        return ['path' => '/assets/images/posters/admin/' . $nombreArchivo, 'error' => null];
    }
}
