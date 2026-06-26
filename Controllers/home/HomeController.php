<?php

declare(strict_types=1);

namespace App\Controllers\Home;

use App\Core\Session;
use App\Helpers\TmdbTipo;
use App\Models\ContenidoRepo;
use App\Services\TmdbClient;

class HomeController
{
    public function __construct(
        private ContenidoRepo $contenidoRepo,
        private TmdbClient $tmdb,
    ) {
    }

    public function index(): void
    {
        $username = Session::obtener('username');
        $csrf = Session::generarCsrf();
        $page = TmdbTipo::pagina($_GET['page'] ?? null, 10);
        $movies = $this->fetch('/discover/movie', $page);

        $idUsuario = (string) Session::obtener('user_id');
        $vistoReciente = $this->contenidoRepo->historialReciente($idUsuario, 10);

        require ROOT . '/views/home.php';
    }

    private function fetch(string $endpoint, int $page): array
    {
        $params = [
            'language' => 'es-MX',
            'page' => (string) $page,
            'sort_by' => 'popularity.desc',
            'without_genres' => '27,53',
            'vote_average.gte' => '6.0',
            'certification_country' => 'US',
            'certification.lte' => 'R',
            'include_adult' => 'false',
        ];

        $resultado = $this->tmdb->fetch($endpoint, $params);

        return $resultado['results'] ?? [];
    }
}
