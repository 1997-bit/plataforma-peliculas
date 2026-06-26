<?php

declare(strict_types=1);

namespace App\Controllers\Catalogo;

use App\Core\Session;
use App\Helpers\GenerosTmdb;
use App\Helpers\TmdbTipo;
use App\Services\TmdbClient;

final class CatalogoController
{
    public function __construct(private TmdbClient $tmdb)
    {
    }

    public function index(): void
    {
        $tipo = TmdbTipo::normalizar($_GET['tipo'] ?? null);
        $generoId = isset($_GET['genero']) && $_GET['genero'] !== '' ? (int) $_GET['genero'] : null;
        $busqueda = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
        $page = TmdbTipo::pagina($_GET['page'] ?? null);

        $recursoTmdb = TmdbTipo::aRecursoTmdb($tipo);

        if ($busqueda !== '') {
            $resultado = $this->buscar($recursoTmdb, $busqueda, $page);
        } else {
            $resultado = $this->descubrir($recursoTmdb, $generoId, $page);
        }

        $items = $resultado['results'] ?? [];
        $totalPaginas = (int) ($resultado['total_pages'] ?? 1);

        $generos = GenerosTmdb::LISTA;
        $csrf = Session::generarCsrf();

        require ROOT . '/views/catalogo/index.php';
    }

    /**
     * Registra "vista" en este modo en-vivo: como no hay tabla content local,
     * por ahora solo confirma CSRF y responde ok. Cuando exista persistencia
     * propia de contenido (admin, #15) esto puede grabar view_history real.
     */
    public function registrarVista(): void
    {
        header('Content-Type: application/json');

        if (!Session::validarCsrf($_POST['_csrf'] ?? '')) {
            http_response_code(403);
            echo json_encode(['error' => 'CSRF invalido']);
            return;
        }

        echo json_encode(['ok' => true]);
    }

    /**
     * @return array{results: list<array<string,mixed>>, total_pages?: int}
     */
    private function descubrir(string $recurso, ?int $generoId, int $page): array
    {
        $params = [
            'language' => 'es-MX',
            'sort_by' => 'popularity.desc',
            'include_adult' => 'false',
            'vote_count.gte' => '50',
            'vote_average.gte' => '5.5',
            'page' => (string) $page,
        ];

        if ($generoId !== null) {
            $params['with_genres'] = (string) $generoId;
        }

        return $this->tmdb->fetch("/discover/{$recurso}", $params);
    }

    /**
     * @return array{results: list<array<string,mixed>>, total_pages?: int}
     */
    private function buscar(string $recurso, string $query, int $page): array
    {
        $params = [
            'language' => 'es-MX',
            'query' => $query,
            'include_adult' => 'false',
            'page' => (string) $page,
        ];

        return $this->tmdb->fetch("/search/{$recurso}", $params);
    }
}
