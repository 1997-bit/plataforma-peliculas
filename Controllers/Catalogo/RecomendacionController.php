<?php

declare(strict_types=1);

namespace App\Controllers\Catalogo;

use App\Core\Session;
use App\Helpers\Http;
use App\Helpers\Normalizador;
use App\Helpers\TmdbImagen;
use App\Helpers\TmdbTipo;
use App\Models\AdminContenidoRepo;
use App\Models\UserRepo;

final class RecomendacionController
{
    private const LIMITE = 20;
    private const OVERVIEW_MAX = 200;

    public function __construct(
        private UserRepo $userRepo,
        private AdminContenidoRepo $repo,
    ) {
    }

    public function index(): void
    {
        $idUsuario = (string) Session::obtener('user_id', '');
        $usuario = $idUsuario !== '' ? $this->userRepo->buscarPorId($idUsuario) : null;

        if ($usuario === null) {
            Http::error404();
        }

        $generosFavoritos = $usuario->preferences['generos'] ?? [];
        $sinGenerosElegidos = $generosFavoritos === [];

        $tipo = TmdbTipo::normalizar($_GET['tipo'] ?? null);

        if ($sinGenerosElegidos) {
            $items = [];
            $hasMore = false;
        } else {
            [$items, $hasMore] = $this->obtenerPagina($tipo, $generosFavoritos, 0);
        }

        $tipoActual = $tipo;
        $csrf = Session::generarCsrf();

        require ROOT . '/views/catalogo/recomendaciones.php';
    }

    /** Endpoint AJAX del scroll infinito: siguiente tanda de recomendaciones en JSON. */
    public function mas(): void
    {
        header('Content-Type: application/json; charset=UTF-8');

        $idUsuario = (string) Session::obtener('user_id', '');
        $usuario = $idUsuario !== '' ? $this->userRepo->buscarPorId($idUsuario) : null;

        if ($usuario === null) {
            http_response_code(401);
            echo json_encode(['error' => 'No autenticado.']);
            return;
        }

        $generosFavoritos = $usuario->preferences['generos'] ?? [];
        if ($generosFavoritos === []) {
            echo json_encode(['items' => [], 'hasMore' => false]);
            return;
        }

        $tipo = TmdbTipo::normalizar($_GET['tipo'] ?? null);
        $offset = max(0, (int) ($_GET['offset'] ?? 0));

        [$items, $hasMore] = $this->obtenerPagina($tipo, $generosFavoritos, $offset);

        echo json_encode(['items' => $items, 'hasMore' => $hasMore], JSON_UNESCAPED_UNICODE);
    }

    /** @param list<int> $generosFavoritos @return array{0: list<array<string,mixed>>, 1: bool} */
    private function obtenerPagina(string $tipo, array $generosFavoritos, int $offset): array
    {
        $filas = Normalizador::lista(
            $this->repo->contenidoParaCatalogo($tipo, $generosFavoritos, self::LIMITE, $offset)
        );
        $items = array_map($this->prepararItem(...), $filas);

        return [$items, count($items) === self::LIMITE];
    }

    /** Convierte una fila normalizada en el shape que consume la vista y el JSON del scroll infinito. */
    private function prepararItem(array $item): array
    {
        $overview = trim((string) ($item['overview'] ?? ''));
        if (mb_strlen($overview) > self::OVERVIEW_MAX) {
            $overview = rtrim(mb_substr($overview, 0, self::OVERVIEW_MAX)) . '…';
        }

        $fecha = $item['release_date'] ?? '';
        $ratingAvg = (float) ($item['rating_avg'] ?? 0.0);

        return [
            'id' => $item['id'],
            'tipo' => $item['type'] === 'series' ? 'series' : 'movie',
            'titulo' => $item['title'] ?? 'Sin título',
            'anio' => $fecha !== '' ? substr((string) $fecha, 0, 4) : '',
            'overview' => $overview !== '' ? $overview : 'Sin descripción disponible.',
            'poster' => TmdbImagen::poster($item['poster_path'] ?? null, 'md'),
            'backdrop' => TmdbImagen::backdrop($item['backdrop_path'] ?? null),
            'rating_avg' => $ratingAvg > 0 ? round($ratingAvg, 1) : 0,
            'rating_count' => (int) ($item['rating_count'] ?? 0),
        ];
    }
}
