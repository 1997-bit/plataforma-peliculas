<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Session;

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
            http_response_code(403);
            require ROOT . '/views/errors/403.php';
            exit;
        }
    }
}
