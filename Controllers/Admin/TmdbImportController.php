<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Session;
use App\Helpers\GenerosTmdb;
use App\Helpers\TmdbTipo;
use App\Models\AdminContenidoRepo;
use App\Services\TmdbClient;

final class TmdbImportController
{
    public function __construct(
        private TmdbClient $tmdb,
        private AdminContenidoRepo $repo,
    ) {
    }

    public function importar(): void
    {
        Session::exigirCsrfOFallar();

        $tmdbId = (int) ($_POST['tmdb_id'] ?? 0);
        $tipo = TmdbTipo::normalizar($_POST['tipo'] ?? null);
        $recurso = TmdbTipo::aRecursoTmdb($tipo);

        if ($tmdbId <= 0) {
            header('Location: /admin/tmdb/buscar?error=id_invalido');
            exit;
        }

        $detalle = $this->tmdb->fetch("/{$recurso}/{$tmdbId}", [
            'language' => 'es-MX',
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
            $nombre = GenerosTmdb::LISTA[$tmdbGenreId] ?? (string) ($g['name'] ?? '');
            if ($nombre === '') {
                continue;
            }
            $generoIdsLocales[] = $this->repo->upsertGenero($nombre, $tmdbGenreId);
        }

        $posterLocal = $posterTmdb !== '' ? $this->repo->descargarImagenTmdb($posterTmdb, 'w500') : null;
        $backdropLocal = $backdropTmdb !== '' ? $this->repo->descargarImagenTmdb($backdropTmdb, 'w1280') : null;

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
        );

        header('Location: /admin?contenido_creado=1');
        exit;
    }
}
