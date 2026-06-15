<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Session;
use App\Services\Respuesta;

class AuthMiddleware
{
    public static function verificarAutenticacion(): void
    {
        if (!Session::existe('user_id')) {
            header('Location: /login');
            exit;
        }
    }

    public static function requerirRol(string $rol): void
    {
        self::verificarAutenticacion();

        if (Session::obtener('user_role') !== $rol) {
            (new Respuesta())->abortar(403, 'errors/403.php');
        }
    }
}
