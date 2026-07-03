<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $rutas = [];

    public function registrarGet(string $ruta, callable|array $manejador): void
    {
        $this->rutas['GET'][$ruta] = $manejador;
    }

    public function registrarPost(string $ruta, callable|array $manejador): void
    {
        $this->rutas['POST'][$ruta] = $manejador;
    }

    public function registrarPut(string $ruta, callable|array $manejador): void
    {
        $this->rutas['PUT'][$ruta] = $manejador;
    }

    public function registrarDelete(string $ruta, callable|array $manejador): void
    {
        $this->rutas['DELETE'][$ruta] = $manejador;
    }

    public function despachar(): void
    {
        $metodo = $this->metodoReal();
        $ruta = $this->normalizarRuta((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
        $manejador = $this->rutas[$metodo][$ruta] ?? null;

        if ($manejador === null) {
            http_response_code(404);
            require ROOT . '/views/errors/404.php';
            return;
        }

        $this->ejecutar($manejador);
    }

    /**
     * Metodo HTTP real. Los forms HTML solo mandan GET/POST (limitacion del
     * browser, no de PHP), asi que para PUT/DELETE via form se acepta:
     *  - header X-HTTP-Method-Override (fetch/curl/API clients)
     *  - campo _method en el body (forms HTML con <input type=hidden>)
     * API clients pueden mandar PUT/DELETE real y no necesitan nada de esto.
     */
    private function metodoReal(): string
    {
        $metodo = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

        if ($metodo !== 'POST') {
            return $metodo;
        }

        $override = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? ($_POST['_method'] ?? null);
        if (is_string($override) && in_array(strtoupper($override), ['PUT', 'DELETE'], true)) {
            return strtoupper($override);
        }

        return 'POST';
    }

    private function normalizarRuta(string $ruta): string
    {
        return rtrim($ruta, '/') ?: '/';
    }

    private function ejecutar(callable|array $manejador): void
    {
        if (!is_array($manejador)) {
            $manejador();
            return;
        }

        [$clase, $accion] = $manejador;
        $instancia = is_object($clase) ? $clase : new $clase();
        $instancia->$accion();
    }
}
