<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Models\AdminContenidoRepo;
use App\Services\ContenidoValidator;
use App\Services\PosterUploader;

/**
 * API REST para importar contenido nuevo (peliculas/series) desde afuera
 * (script de import, cron, sistema externo). Sin sesion/cookie: auth via
 * header X-API-Key contra ADMIN_API_KEY del .env (comparacion timing-safe).
 *
 * Reusa la MISMA validacion y el MISMO upload de poster que el panel admin
 * (ContenidoValidator, PosterUploader) para no tener dos criterios de
 * seguridad distintos en el proyecto.
 */
final class ContenidoApiController
{
    public function __construct(private AdminContenidoRepo $adminContenidoRepo)
    {
    }

    /**
     * POST /api/v1/contenido
     * multipart/form-data: titulo, tipo(movie|series), descripcion, anio,
     *   generos[]        -> ids de generos existentes (opcional)
     *   genero_nombres[] -> nombres de genero, se crean si no existen (opcional)
     *   poster           -> archivo (opcional en API; el panel admin si lo exige)
     *
     * Devuelve 201 + el contenido creado, o 4xx + errores.
     */
    public function crear(): void
    {
        if (!$this->autenticado()) {
            $this->responder(401, ['success' => false, 'errors' => ['API key inválida o ausente.']]);
            return;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->responder(405, ['success' => false, 'errors' => ['Método no permitido, usá POST.']]);
            return;
        }

        $generosDisponibles = $this->adminContenidoRepo->listarGeneros();
        $datos = ContenidoValidator::validar($_POST, $generosDisponibles);

        // generos por nombre: se crean (o se reusan si ya existen) y se
        // suman a los ids que ya vinieron validados por ContenidoValidator.
        $nombresCrudos = is_array($_POST['genero_nombres'] ?? null) ? $_POST['genero_nombres'] : [];
        foreach ($nombresCrudos as $nombre) {
            $nombre = trim(strip_tags((string) $nombre));
            if ($nombre === '') {
                continue;
            }
            $datos['generoIds'][] = $this->adminContenidoRepo->crearGeneroLocal(mb_substr($nombre, 0, 50));
        }
        $datos['generoIds'] = array_values(array_unique($datos['generoIds']));

        // poster NO es obligatorio via API: muchos imports no traen imagen
        // todavia (se completa despues desde el panel).
        $poster = PosterUploader::subir($_FILES['poster'] ?? null, obligatorio: false);
        if ($poster['error'] !== null) {
            $datos['errores'][] = $poster['error'];
        }

        if ($datos['errores'] !== []) {
            $this->responder(400, ['success' => false, 'errors' => $datos['errores']]);
            return;
        }

        // idempotencia: si el import se corre 2 veces con el mismo catalogo,
        // no queremos duplicados por cada corrida.
        if ($this->adminContenidoRepo->existeContenidoLocal($datos['titulo'], $datos['tipo'], $datos['anio'])) {
            $this->responder(409, ['success' => false, 'errors' => ['Ya existe un contenido local con ese título, tipo y año.']]);
            return;
        }

        $idUsuarioApi = $this->idUsuarioApi();
        if ($idUsuarioApi === '') {
            $this->responder(500, ['success' => false, 'errors' => ['ADMIN_API_USER_ID no está configurado en el servidor.']]);
            return;
        }

        $id = $this->adminContenidoRepo->crearContenidoLocal(
            $datos['tipo'],
            $datos['titulo'],
            $datos['descripcion'],
            $poster['path'],
            $datos['anio'],
            $datos['generoIds'],
            $idUsuarioApi
        );

        $this->responder(201, [
            'success' => true,
            'data' => [
                'id' => $id,
                'titulo' => $datos['titulo'],
                'tipo' => $datos['tipo'],
                'anio' => $datos['anio'],
                'poster_path' => $poster['path'],
                'generos' => $datos['generoIds'],
            ],
        ]);
    }

    /**
     * GET /api/v1/generos — para que el script de import pueda mapear
     * nombre de genero -> id antes de mandar el POST, si prefiere eso
     * en vez de mandar genero_nombres[] y que se cree solo.
     */
    public function generos(): void
    {
        if (!$this->autenticado()) {
            $this->responder(401, ['success' => false, 'errors' => ['API key inválida o ausente.']]);
            return;
        }

        $this->responder(200, ['success' => true, 'data' => $this->adminContenidoRepo->listarGeneros()]);
    }

    /**
     * Compara la API key contra ADMIN_API_KEY del .env con hash_equals
     * (evita timing attack). Si ADMIN_API_KEY no esta configurada, la
     * API queda cerrada por default (fail closed, no fail open).
     */
    private function autenticado(): bool
    {
        $esperada = (string) ($_ENV['ADMIN_API_KEY'] ?? '');
        if ($esperada === '') {
            return false;
        }

        $recibida = (string) ($_SERVER['HTTP_X_API_KEY'] ?? '');
        if ($recibida === '') {
            return false;
        }

        return hash_equals($esperada, $recibida);
    }

    /**
     * created_by de contenido requiere un usuario (FK a usuarios.id). Como
     * la API no tiene sesion, se usa un UUID fijo de "sistema" configurado
     * en el .env (debe existir un usuario admin con ese id).
     */
    private function idUsuarioApi(): string
    {
        return (string) ($_ENV['ADMIN_API_USER_ID'] ?? '');
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function responder(int $status, array $payload): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }
}
