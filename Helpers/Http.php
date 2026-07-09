<?php

declare(strict_types=1);

namespace App\Helpers;

/** Respuestas de error repetidas en varios controllers: setea el codigo, renderiza la vista y corta. */
final class Http
{
    public static function error404(): void
    {
        http_response_code(404);
        require ROOT . '/views/errors/404.php';
        exit;
    }

    public static function error403(): void
    {
        http_response_code(403);
        require ROOT . '/views/errors/403.php';
        exit;
    }
}
