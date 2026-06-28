<?php

declare(strict_types=1);

namespace App\Controllers\Home;

use App\Core\Session;
use App\Helpers\TmdbTipo;
use App\Models\ContenidoRepo;
use App\Models\UserRepo;
use App\Services\TmdbClient;

class HomeController
{
    public function __construct(
        private ContenidoRepo $contenidoRepo,
        private UserRepo $userRepo,
        private TmdbClient $tmdb,
    ) {
    }

    public function index(): void
    {
        $username = Session::obtener('username');
        $csrf = Session::generarCsrf();

        $tipo = TmdbTipo::normalizar($_GET['tipo'] ?? null);
        $recurso = TmdbTipo::aRecursoTmdb($tipo);
        $page = TmdbTipo::pagina($_GET['page'] ?? null, 10);

        $idUsuario = (string) Session::obtener('user_id');
        $usuario = $idUsuario !== '' ? $this->userRepo->buscarPorId($idUsuario) : null;
        $generosFavoritos = $usuario?->preferences['generos'] ?? [];

        // Hero: populares segun generos favoritos del usuario.
        // Fallback a popular general TMDB si no eligio generos.
        $hero = $this->fetch("/discover/{$recurso}", $page, $generosFavoritos);
        $hero = array_slice($hero, 0, 8);

        $populares = $this->fetch("/discover/{$recurso}", $page);

        $recomendaciones = $generosFavoritos !== []
            ? $this->fetch("/discover/{$recurso}", $page + 1, $generosFavoritos)
            : [];

        $tipoActual = $tipo;
        $vistoReciente = $this->contenidoRepo->historialReciente($idUsuario, 10);

        require ROOT . '/views/home.php';
    }

    /** @param list<int> $generoIds */
    private function fetch(string $endpoint, int $page, array $generoIds = []): array
    {
        $params = [
            'language' => 'es-MX',
            'page' => (string) $page,
            'sort_by' => 'popularity.desc',
            'without_genres' => '27,53',
            'vote_average.gte' => '6.0',
            'include_adult' => 'false',
        ];

        if (str_contains($endpoint, '/movie')) {
            $params['certification_country'] = 'US';
            $params['certification.lte'] = 'R';
        }

        if ($generoIds !== []) {
            $params['with_genres'] = implode('|', $generoIds);
        }

        $resultado = $this->tmdb->fetch($endpoint, $params);

        return $resultado['results'] ?? [];
    }
}
