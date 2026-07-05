<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Models\AdminContenidoRepo;
use PDOException;

/**
 * API REST JSON pura. Validacion minima en PHP (titulo, tipo, anio);
 * duplicados (titulo+tipo+anio) y genero_id invalido se delegan a la DB
 * (UNIQUE KEY + FK) y se traducen del PDOException a HTTP status.
 */
final class ContenidoApiController
{
    public function __construct(private AdminContenidoRepo $adminContenidoRepo)
    {
    }

    public function crear(): void
    {
        if (!$this->autenticado()) {
            $this->responder(401, ['error' => 'API key inválida o ausente.']);
            return;
        }

        $body = $this->leerJson();
        if ($body === null) {
            $this->responder(400, ['error' => 'JSON inválido.']);
            return;
        }

        $errores = $this->validarBasico($body);
        if ($errores !== []) {
            $this->responder(400, ['errors' => $errores]);
            return;
        }

        $idUsuarioApi = (string) ($_ENV['ADMIN_API_USER_ID'] ?? '');
        if ($idUsuarioApi === '') {
            $this->responder(500, ['error' => 'ADMIN_API_USER_ID no configurado.']);
            return;
        }

        try {
            $id = $this->adminContenidoRepo->crearContenidoLocal(
                (string) $body['tipo'],
                trim((string) $body['titulo']),
                isset($body['descripcion']) ? (string) $body['descripcion'] : null,
                isset($body['poster_path']) ? (string) $body['poster_path'] : null,
                isset($body['anio']) ? (int) $body['anio'] : null,
                array_map('intval', $body['generos'] ?? []),
                $idUsuarioApi
            );
        } catch (PDOException $e) {
            $this->responderErrorDb($e);
            return;
        }

        $this->responder(201, ['id' => $id]);
    }

    public function actualizar(): void
    {
        if (!$this->autenticado()) {
            $this->responder(401, ['error' => 'API key inválida o ausente.']);
            return;
        }

        $id = (string) ($_GET['id'] ?? '');
        if ($id === '') {
            $this->responder(400, ['error' => 'Falta el parámetro id.']);
            return;
        }

        $body = $this->leerJson();
        if ($body === null) {
            $this->responder(400, ['error' => 'JSON inválido.']);
            return;
        }

        $errores = $this->validarBasico($body);
        if ($errores !== []) {
            $this->responder(400, ['errors' => $errores]);
            return;
        }

        try {
            $actual = $this->adminContenidoRepo->obtenerLocalPorIdParaEditar($id);
        } catch (\InvalidArgumentException) {
            $actual = null;
        }

        if ($actual === null) {
            $this->responder(404, ['error' => 'Contenido no encontrado.']);
            return;
        }

        try {
            $this->adminContenidoRepo->actualizarContenidoLocal(
                $actual['id'],
                trim((string) $body['titulo']),
                isset($body['descripcion']) ? (string) $body['descripcion'] : null,
                isset($body['poster_path']) ? (string) $body['poster_path'] : $actual['poster_path'],
                isset($body['anio']) ? (int) $body['anio'] : null,
                array_map('intval', $body['generos'] ?? [])
            );
        } catch (PDOException $e) {
            $this->responderErrorDb($e);
            return;
        }

        $this->responder(200, ['id' => $actual['id']]);
    }

    public function eliminar(): void
    {
        if (!$this->autenticado()) {
            $this->responder(401, ['error' => 'API key inválida o ausente.']);
            return;
        }

        $id = (string) ($_GET['id'] ?? '');
        if ($id === '') {
            $this->responder(400, ['error' => 'Falta el parámetro id.']);
            return;
        }

        try {
            $existe = $this->adminContenidoRepo->obtenerLocalPorIdParaEditar($id) !== null;
        } catch (\InvalidArgumentException) {
            $existe = false;
        }

        if (!$existe) {
            $this->responder(404, ['error' => 'Contenido no encontrado.']);
            return;
        }

        $this->adminContenidoRepo->eliminarContenidoLocal($id);
        $this->responder(200, ['id' => $id, 'is_active' => 0]);
    }

    public function generos(): void
    {
        if (!$this->autenticado()) {
            $this->responder(401, ['error' => 'API key inválida o ausente.']);
            return;
        }

        $this->responder(200, $this->adminContenidoRepo->listarGeneros());
    }

    /**
     * @return list<string>
     */
    private function validarBasico(array $body): array
    {
        $errores = [];

        if (trim((string) ($body['titulo'] ?? '')) === '') {
            $errores[] = 'titulo requerido.';
        }
        if (!in_array($body['tipo'] ?? '', ['movie', 'series'], true)) {
            $errores[] = 'tipo debe ser movie|series.';
        }
        if (isset($body['anio']) && $body['anio'] !== null && !ctype_digit((string) $body['anio'])) {
            $errores[] = 'anio invalido.';
        }

        return $errores;
    }

    private function leerJson(): ?array
    {
        $data = json_decode((string) file_get_contents('php://input'), true);

        return is_array($data) ? $data : null;
    }

    private function autenticado(): bool
    {
        $esperada = (string) ($_ENV['ADMIN_API_KEY'] ?? '');
        $recibida = (string) ($_SERVER['HTTP_X_API_KEY'] ?? '');

        return $esperada !== '' && $recibida !== '' && hash_equals($esperada, $recibida);
    }

    /**
     * Mapea codigo de error MySQL -> HTTP status. 1062 = UNIQUE (duplicado),
     * 1452 = FK invalida (genero_id no existe).
     */
    private function responderErrorDb(PDOException $e): void
    {
        $codigo = (int) ($e->errorInfo[1] ?? 0);

        if ($codigo === 1062) {
            $this->responder(409, ['error' => 'Ya existe un contenido con ese título+tipo+año.']);
            return;
        }
        if ($codigo === 1452) {
            $this->responder(400, ['error' => 'Uno de los genero_id no existe.']);
            return;
        }

        $this->responder(500, ['error' => 'Error de base de datos.']);
    }

    /**
     * @param array<string,mixed>|list<array<string,mixed>> $payload
     */
    private function responder(int $status, array $payload): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }
}
