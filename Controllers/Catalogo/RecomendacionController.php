<?php

declare(strict_types=1);

namespace App\Controllers\Catalogo;

use App\Core\Session;
use App\Helpers\TmdbTipo;
use App\Models\UserRepo;
use App\Services\TmdbClient;

/**
 * Recomendaciones basicas: toma los generos favoritos guardados en
 * preferences.generos (perfil del usuario) y trae contenido de TMDB
 * que matchee esos generos via with_genres (OR logico).
 *
 * Es deliberadamente simple: no hay scoring, no hay collaborative
 * filtering, no hay peso por historial. Es el primer corte funcional;
 * si despues se quiere mezclar con view_history/ratings, este es el
 * punto de entrada a extender.
 */
final class RecomendacionController
{
    public function __construct(
        private UserRepo $userRepo,
        private TmdbClient $tmdb,
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
            // Sin generos elegidos no hay base para recomendar nada;
            // se muestra el estado vacio y se manda a elegir en /profile.
            $items = [];
            $totalPaginas = 1;
        } else {
            $recurso = TmdbTipo::aRecursoTmdb($tipo);
            $resultado = $this->descubrirPorGeneros($recurso, $generosFavoritos, $page);
            $items = $resultado['results'] ?? [];
            $totalPaginas = (int) ($resultado['total_pages'] ?? 1);
        }

        $tipoActual = $tipo;
        $csrf = Session::generarCsrf();

        require ROOT . '/views/catalogo/recomendaciones.php';
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

        // certification solo existe en discover/movie, TV no lo soporta.
        if ($recurso === 'movie') {
            $params['certification_country'] = 'US';
            $params['certification.lte'] = 'R';
        }

        return $this->tmdb->fetch("/discover/{$recurso}", $params);
    }
}
