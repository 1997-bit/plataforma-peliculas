<?php

declare(strict_types=1);

namespace App\Controllers\Catalogo;

use App\Core\Session;
use App\Helpers\TmdbTipo;
use App\Models\AdminContenidoRepo;
use App\Models\UserRepo;
use App\Services\TmdbClient;

/**
 * Recomendaciones basicas: toma los generos favoritos guardados en
 * preferences.generos (perfil del usuario) y trae contenido de TMDB
 * que matchee esos generos via with_genres (OR logico), mezclado con
 * contenido local creado por un admin (local primero, TMDB de relleno).
 */
final class RecomendacionController
{
    public function __construct(
        private UserRepo $userRepo,
        private TmdbClient $tmdb,
        private AdminContenidoRepo $adminContenidoRepo,
    ) {
    }

    public function index(): void
    {
        $idUsuario = (string) Session::obtener('user_id', '');
        $usuario = $idUsuario !== '' ? $this->userRepo->buscarPorId($idUsuario) : null;

        if ($usuario === null) {
            http_response_code(404);
            require ROOT . '/views/errors/404.php';
            return;
        }

        $generosFavoritos = $usuario->preferences['generos'] ?? [];
        $tipo = TmdbTipo::normalizar($_GET['tipo'] ?? null);
        $page = TmdbTipo::pagina($_GET['page'] ?? null);

        $sinGenerosElegidos = $generosFavoritos === [];

        if ($sinGenerosElegidos) {
            $items = [];
            $totalPaginas = 1;
        } else {
            $recurso = TmdbTipo::aRecursoTmdb($tipo);

            $generosInternos = $this->adminContenidoRepo->idsInternosPorTmdbId($generosFavoritos);
            $local = $this->adminContenidoRepo->contenidoLocalParaCatalogo($tipo, $generosInternos, 20);
            $local = array_map($this->normalizarLocal(...), $local);

            $faltan = max(0, 20 - count($local));
            $resultado = $faltan > 0 ? $this->descubrirPorGeneros($recurso, $generosFavoritos, $page) : ['results' => [], 'total_pages' => 1];

            $deTmdb = array_slice($resultado['results'] ?? [], 0, $faltan);
            $items = [...$local, ...$deTmdb];
            $totalPaginas = (int) ($resultado['total_pages'] ?? 1);
        }

        $tipoActual = $tipo;
        $csrf = Session::generarCsrf();

        require ROOT . '/views/catalogo/recomendaciones.php';
    }

    /**
     * @param array<string,mixed> $fila
     * @return array<string,mixed>
     */
    private function normalizarLocal(array $fila): array
    {
        return [
            'id' => $fila['id'],
            'origen' => 'local',
            'type' => $fila['type'],
            'title' => $fila['titulo'],
            'overview' => $fila['descripcion'],
            'poster_path' => $fila['poster_path'],
            'backdrop_path' => null,
            'release_date' => $fila['anio_lanzamiento'] ? $fila['anio_lanzamiento'] . '-01-01' : null,
        ];
    }

    /**
     * @param list<int> $generoIds
     * @return array{results: list<array<string,mixed>>, total_pages?: int}
     */
    private function descubrirPorGeneros(string $recurso, array $generoIds, int $page): array
    {
        $params = [
            'language' => 'es-MX',
            'sort_by' => 'popularity.desc',
            'include_adult' => 'false',
            'vote_count.gte' => '50',
            'vote_average.gte' => '5.5',
            'with_genres' => implode('|', $generoIds),
            'page' => (string) $page,
        ];

        if ($recurso === 'movie') {
            $params['certification_country'] = 'US';
            $params['certification.lte'] = 'R';
        }

        return $this->tmdb->fetch("/discover/{$recurso}", $params);
    }
}
