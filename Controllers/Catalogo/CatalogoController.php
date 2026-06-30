<?php

declare(strict_types=1);

namespace App\Controllers\Catalogo;

use App\Core\Session;
use App\Helpers\GenerosTmdb;
use App\Helpers\TmdbTipo;
use App\Models\AdminContenidoRepo;
use App\Services\TmdbClient;

final class CatalogoController
{
    private const TOTAL_POR_PAGINA = 20;

    public function __construct(
        private TmdbClient $tmdb,
        private AdminContenidoRepo $adminContenidoRepo,
    ) {
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

        // Contenido local solo se mezcla en la pagina 1: evita que se
        // repita en cada pagina de TMDB y que la paginacion se desordene
        // (TMDB pagina sus 20 resultados, local no esta paginado).
        if ($page === 1) {
            $items = $this->conContenidoLocal($items, $tipo, $generoId, $busqueda);
        }

        $generos = GenerosTmdb::LISTA;
        $csrf = Session::generarCsrf();

        require ROOT . '/views/catalogo/index.php';
    }

    /**
     * Antepone contenido local (creado por un admin) a los resultados de
     * TMDB, igual patron que HomeController::fetchConLocal(): local primero,
     * TMDB rellena el resto hasta completar el cupo de una pagina.
     *
     * @param list<array<string,mixed>> $itemsTmdb
     * @return list<array<string,mixed>>
     */
    private function conContenidoLocal(array $itemsTmdb, string $tipo, ?int $generoIdTmdb, string $busqueda): array
    {
        if ($busqueda !== '') {
            $local = $this->adminContenidoRepo->buscarContenidoLocal($tipo, $busqueda, self::TOTAL_POR_PAGINA);
        } else {
            $generosInternos = $generoIdTmdb !== null
                ? $this->adminContenidoRepo->idsInternosPorTmdbId([$generoIdTmdb])
                : [];

            // Si el filtro de genero no tiene equivalente local (genero
            // creado solo por TMDB, sin contenido local asociado), no
            // mostrar nada local en vez de mostrar todo sin filtrar.
            if ($generoIdTmdb !== null && $generosInternos === []) {
                return $itemsTmdb;
            }

            $local = $this->adminContenidoRepo->contenidoLocalParaCatalogo($tipo, $generosInternos, self::TOTAL_POR_PAGINA);
        }

        $local = array_map($this->normalizarLocal(...), $local);

        $faltan = max(0, self::TOTAL_POR_PAGINA - count($local));

        return [...$local, ...array_slice($itemsTmdb, 0, $faltan)];
    }

    /**
     * Normaliza una fila de AdminContenidoRepo al shape minimo que
     * views/catalogo/index.php espera de TMDB (title/poster_path/id),
     * agregando 'origen' => 'local' para que el link al detalle sepa
     * armar /contenido?...&origen=local en vez de asumir un tmdb_id.
     *
     * @param array<string,mixed> $fila
     * @return array<string,mixed>
     */
    private function normalizarLocal(array $fila): array
    {
        return [
            'id' => $fila['id'],
            'origen' => 'local',
            'title' => $fila['titulo'],
            'poster_path' => $fila['poster_path'],
        ];
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
