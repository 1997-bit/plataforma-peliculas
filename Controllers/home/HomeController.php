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

        // Hero: populares segun generos favoritos del usuario, con
        // fallback a popular general de TMDB si no eligio generos.
        $hero = $generosFavoritos !== []
            ? $this->fetch("/discover/{$recurso}", $page, $generosFavoritos)
            : $this->fetch("/discover/{$recurso}", $page);
        $hero = array_slice($hero, 0, 8);

        $populares = $this->fetch("/discover/{$recurso}", $page);

        $recomendaciones = $generosFavoritos !== []
            ? $this->fetch("/discover/{$recurso}", $page, $generosFavoritos)
            : [];
        $recomendaciones = array_map(
            fn (array $item): array => $this->adjuntarLogo($item, $recurso),
            $recomendaciones
        );

        $tipoActual = $tipo;
        $vistoReciente = $this->contenidoRepo->historialReciente($idUsuario, 10);

        require ROOT . '/views/home.php';
    }

    /**
     * Trae el logo (PNG/SVG transparente del titulo) de un item via
     * /{recurso}/{id}/images. Discover no trae logo_path, asi que es
     * 1 request por item, pero TmdbClient cachea cada uno 1h en disco.
     *
     * Sin filtro de idioma en el server: muchos titulos solo tienen
     * logo en idiomas distintos a es/en (pt, de, ja, etc), y como el
     * logo es el nombre propio del titulo, sirve visualmente sin
     * importar el idioma. Filtrar en origen los descartaba a todos
     * y por eso casi nunca aparecia (caia siempre al texto plano).
     * Preferencia: es -> en -> sin idioma (xx/null) -> cualquier otro
     * -> ninguno (la vista cae a mostrar el titulo en texto).
     */
    private function adjuntarLogo(array $item, string $recurso): array
    {
        $tmdbId = $item['id'] ?? null;
        if ($tmdbId === null) {
            $item['logo_path'] = null;
            return $item;
        }

        // Sin 'language' aqui a proposito: TMDB filtra images/logos al
        // idioma pasado en 'language' (incluso sin include_image_language
        // explicito), lo que vuelve a descartar la mayoria de los logos.
        $resultado = $this->tmdb->fetch("/{$recurso}/{$tmdbId}/images", []);
        $logos = $resultado['logos'] ?? [];

        $porIdioma = static function (array $logos, array $idiomas): ?array {
            foreach ($logos as $logo) {
                if (in_array($logo['iso_639_1'] ?? null, $idiomas, true)) {
                    return $logo;
                }
            }
            return null;
        };

        $elegido = $porIdioma($logos, ['es'])
            ?? $porIdioma($logos, ['en'])
            ?? $porIdioma($logos, [null, 'xx'])
            ?? ($logos[0] ?? null);

        $item['logo_path'] = $elegido['file_path'] ?? null;

        return $item;
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
