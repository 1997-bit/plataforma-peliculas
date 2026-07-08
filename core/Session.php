<?php

declare(strict_types=1);

namespace App\Core;

class Session
{
    private static bool $started = false;

    public static function iniciar(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        self::configurarCookie();

        session_name('CINESESSID');
        session_start();
        self::$started = true;

        if (self::haExpirado()) {
            self::destruir();
            return;
        }

        $_SESSION['_last_activity'] = time();
    }

    public static function regenerar(): void
    {
        session_regenerate_id(true);
    }

    /**
     * Suelta el lock del archivo de sesion sin perder los datos ya leidos en
     * memoria. Llamar antes de operaciones largas (ej. peticiones HTTP a
     * servicios externos) para no bloquear otras peticiones concurrentes del
     * mismo usuario, que de otro modo esperan este lock hasta agotar
     * max_execution_time.
     */
    public static function liberarBloqueo(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }

    public static function destruir(): void
    {
        $_SESSION = [];
        self::eliminarCookieSesion();
        session_destroy();
        self::$started = false;
    }

    public static function establecer(string $clave, mixed $valor): void
    {
        $_SESSION[$clave] = $valor;
    }

    public static function obtener(string $clave, mixed $defecto = null): mixed
    {
        return $_SESSION[$clave] ?? $defecto;
    }

    public static function existe(string $clave): bool
    {
        return isset($_SESSION[$clave]);
    }

    public static function generarCsrf(): string
    {
        if (!empty($_SESSION['_csrf_token'])) {
            return (string) $_SESSION['_csrf_token'];
        }

        $token = bin2hex(random_bytes(32));
        $_SESSION['_csrf_token'] = $token;
        return $token;
    }

    public static function validarCsrf(string $token): bool
    {
        return hash_equals((string) ($_SESSION['_csrf_token'] ?? ''), $token);
    }

    /** Valida el CSRF del POST actual o corta la ejecucion con un 403. */
    public static function exigirCsrfOFallar(): void
    {
        if (self::validarCsrf($_POST['_csrf'] ?? '')) {
            return;
        }

        http_response_code(403);
        require ROOT . '/views/errors/403.php';
        exit;
    }

    private static function configurarCookie(): void
    {
        $esSeguro = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? null) == 443);

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $esSeguro,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
    }

    private static function haExpirado(): bool
    {
        $lifetime = (int) ($_ENV['SESSION_LIFETIME'] ?? 3600);
        $ultimaActividad = $_SESSION['_last_activity'] ?? null;

        return is_int($ultimaActividad) && (time() - $ultimaActividad > $lifetime);
    }

    private static function eliminarCookieSesion(): void
    {
        if (!ini_get('session.use_cookies')) {
            return;
        }

        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $params['path']);
    }
}
