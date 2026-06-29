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

    public function despachar(): void
    {
        $metodo = $_SERVER['REQUEST_METHOD'];
        $ruta = $this->normalizarRuta((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
        $manejador = $this->rutas[$metodo][$ruta] ?? null;

        if ($manejador === null) {
            http_response_code(404);
            require ROOT . '/views/errors/404.php';
            return;
        }

        $this->ejecutar($manejador);
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
