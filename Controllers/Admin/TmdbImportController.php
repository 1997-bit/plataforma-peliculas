<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Session;
use App\Models\AdminContenidoRepo;
use App\Services\TmdbClient;

final class TmdbImportController
{
    public function __construct(
        private TmdbClient $tmdb,
        private AdminContenidoRepo $repo,
    ) {
    }

    public function buscar(): void
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        $tipo = ($_GET['tipo'] ?? 'movie') === 'series' ? 'series' : 'movie';
        $recurso = $tipo === 'series' ? 'tv' : 'movie';

        $resultados = [];
        if ($q !== '') {
            $data = $this->tmdb->fetch("/search/{$recurso}", [
                'query' => $q,
                'language' => 'es-MX',
                'include_adult' => 'false',
            ]);
            $resultados = $data['results'] ?? [];
        }

        $csrf = Session::generarCsrf();
        require ROOT . '/views/admin/tmdb-buscar.php';
    }

    public function importar(): void
    {
        if (!Session::validarCsrf($_POST['_csrf'] ?? '')) {
            http_response_code(403);
            require ROOT . '/views/errors/403.php';
            exit;
        }

        $tmdbId = (int) ($_POST['tmdb_id'] ?? 0);
        $tipo = ($_POST['tipo'] ?? 'movie') === 'series' ? 'series' : 'movie';
        $recurso = $tipo === 'series' ? 'tv' : 'movie';

        if ($tmdbId <= 0) {
            header('Location: /admin/tmdb/buscar?error=id_invalido');
            exit;
        }

        $detalle = $this->tmdb->fetch("/{$recurso}/{$tmdbId}", [
            'language' => 'es-MX',
            'append_to_response' => 'credits,images',
        ]);

        if (empty($detalle['id'])) {
            header('Location: /admin/tmdb/buscar?error=no_encontrado');
            exit;
        }

        $titulo = (string) ($detalle['title'] ?? $detalle['name'] ?? '');
        $descripcion = (string) ($detalle['overview'] ?? '');
        $posterTmdb = (string) ($detalle['poster_path'] ?? '');
        $backdropTmdb = (string) ($detalle['backdrop_path'] ?? '');
        $fecha = (string) ($detalle['release_date'] ?? $detalle['first_air_date'] ?? '');
        $anio = $fecha !== '' ? (int) substr($fecha, 0, 4) : null;

        $generoIdsLocales = [];
        foreach ($detalle['genres'] ?? [] as $g) {
            $tmdbGenreId = (int) $g['id'];
            $nombre = \App\Helpers\GenerosTmdb::LISTA[$tmdbGenreId] ?? (string) ($g['name'] ?? '');
            if ($nombre === '') {
                continue;
            }
            $generoIdsLocales[] = $this->repo->upsertGenero($nombre, $tmdbGenreId);
        }

        $posterLocal = $posterTmdb !== '' ? $this->repo->descargarImagenTmdb($posterTmdb, 'w500') : null;
        $backdropLocal = $backdropTmdb !== '' ? $this->repo->descargarImagenTmdb($backdropTmdb, 'w1280') : null;

        $logos = $detalle['images']['logos'] ?? [];
        $logoElegido = null;
        foreach (['es', 'en', null] as $lang) {
            foreach ($logos as $l) {
                if (($l['iso_639_1'] ?? null) === $lang) {
                    $logoElegido = $l['file_path'];
                    break 2;
                }
            }
        }
        $logoLocal = $logoElegido !== null ? $this->repo->descargarImagenTmdb($logoElegido, 'w300') : null;

        $this->repo->crearContenidoLocal(
            $tipo,
            $titulo,
            $descripcion !== '' ? $descripcion : null,
            $posterLocal,
            $anio,
            $generoIdsLocales,
            (string) Session::obtener('user_id'),
            $tmdbId,
            $backdropLocal,
            $logoLocal,
        );

        header('Location: /admin?contenido_creado=1');
        exit;
    }
}
