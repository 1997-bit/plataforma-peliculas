<?php

declare(strict_types=1);

namespace App\Controllers\Home;

use App\Core\Session;
use App\Helpers\TmdbTipo;
use App\Models\AdminContenidoRepo;
use App\Models\ContenidoRepo;
use App\Models\UserRepo;
use App\Services\TmdbClient;

class HomeController
{
    public function __construct(
        private ContenidoRepo $contenidoRepo,
        private UserRepo $userRepo,
        private TmdbClient $tmdb,
        private AdminContenidoRepo $adminContenidoRepo,
    ) {
    }

    public function index(): void
    {
        $username = Session::obtener('username');
        $csrf = Session::generarCsrf();

        $page = TmdbTipo::pagina($_GET['page'] ?? null, 10);

        $idUsuario = (string) Session::obtener('user_id');
        $usuario = $idUsuario !== '' ? $this->userRepo->buscarPorId($idUsuario) : null;
        $generosFavoritos = $usuario?->preferences['generos'] ?? [];

        // Hero: populares (movies + series mezclados) segun generos
        // favoritos del usuario, con fallback a popular general si no
        // eligio generos. Local no entra aqui: el hero necesita backdrop
        // ancho y el admin solo sube poster, asi que se veria roto.
        $hero = $this->fetchAmbosTipos($page, $generosFavoritos);
        $hero = array_slice($hero, 0, 8);

        // Populares: local primero (lo que agrega el admin), TMDB de
        // relleno hasta completar la cantidad habitual del shelf.
        $populares = $this->fetchConLocal($page, $generosFavoritos);

        $recomendaciones = $generosFavoritos !== []
            ? $this->fetchAmbosTipos($page, $generosFavoritos)
            : [];
        $recomendaciones = array_map(
            fn (array $item): array => $this->adjuntarLogo($item),
            $recomendaciones
        );

        $vistoReciente = $this->contenidoRepo->historialReciente($idUsuario, 10);

        require ROOT . '/views/home.php';
    }

    /**
     * Pide populares de movie y series por separado (TMDB no tiene un
     * /discover combinado) y los junta en una sola lista, ordenada por
     * popularidad. Asi cada shelf mezcla ambos tipos sin necesitar un
     * toggle ni mantener dos rutas distintas para la misma pagina.
     *
     * @param list<int> $generoIds
     * @return list<array<string,mixed>>
     */
    private function fetchAmbosTipos(int $page, array $generoIds = []): array
    {
        $movies = $this->fetch('/discover/movie', $page, $generoIds);
        $series = $this->fetch('/discover/tv', $page, $generoIds);

        $items = [...$movies, ...$series];

        usort($items, static fn (array $a, array $b): int =>
            ($b['popularity'] ?? 0) <=> ($a['popularity'] ?? 0));

        return $items;
    }

    /**
     * Igual que fetchAmbosTipos(), pero antepone contenido local (creado
     * por un admin) antes de los resultados de TMDB. Local primero, TMDB
     * rellena el resto hasta llegar a la cantidad tipica de un shelf (20).
     *
     * @param list<int> $generosFavoritosTmdbId tal cual vienen de preferences.generos (son tmdb_id, no id interno)
     * @return list<array<string,mixed>>
     */
    private function fetchConLocal(int $page, array $generosFavoritosTmdbId): array
    {
        $generosInternos = $this->adminContenidoRepo->idsInternosPorTmdbId($generosFavoritosTmdbId);

        $localMovies = $this->adminContenidoRepo->contenidoLocalParaCatalogo('movie', $generosInternos, 20);
        $localSeries = $this->adminContenidoRepo->contenidoLocalParaCatalogo('series', $generosInternos, 20);
        $local = array_map($this->normalizarLocal(...), [...$localMovies, ...$localSeries]);

        $faltan = max(0, 20 - count($local));
        $deTmdb = $faltan > 0 ? $this->fetchAmbosTipos($page, $generosFavoritosTmdbId) : [];

        return [...$local, ...array_slice($deTmdb, 0, $faltan)];
    }

    /**
     * Normaliza una fila de AdminContenidoRepo::contenidoLocalParaCatalogo()
     * al shape minimo que las vistas esperan de TMDB (title/poster_path/etc),
     * agregando 'origen' => 'local' para que la vista sepa armar la URL
     * correcta (/contenido?id=...&origen=local en vez de tmdb_id).
     *
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
            'logo_path' => null,
            'release_date' => $fila['anio_lanzamiento'] ? $fila['anio_lanzamiento'] . '-01-01' : null,
        ];
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
    private function adjuntarLogo(array $item): array
    {
        $tmdbId = $item['id'] ?? null;
        $recurso = isset($item['name']) && !isset($item['title']) ? 'tv' : 'movie';
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
        $tipo = str_contains($endpoint, '/movie') ? 'movie' : 'series';

        // Cada item lleva su propio tipo: en un shelf mezclado, el link
        // a /contenido?tipo=... tiene que apuntar al endpoint correcto
        // segun si es pelicula o serie, no a un tipo global de la pagina.
        return array_map(
            static function (array $item) use ($tipo): array {
                $item['type'] = $tipo;
                return $item;
            },
            $resultado['results'] ?? []
        );
    }
}
