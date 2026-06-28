<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\UuidHelper;
use PDO;

/**
 * Capa de datos para que un admin gestione contenido LOCAL (peliculas/series
 * creadas a mano, sin tmdb_id real) y generos locales.
 *
 * IMPORTANTE: esto es solo acceso a datos (insert/update/delete/select).
 * NO valida campos de formulario, no maneja $_FILES, no decide mensajes
 * de error de UI — eso es trabajo del controller/API que todavia no
 * existe. Quien escriba esa capa solo necesita llamar a los metodos
 * de aqui.
 *
 * Contrato esperado de uso (referencia para quien construya la API):
 *   - crearContenidoLocal(...) -> content_id (string uuid)
 *   - actualizarContenidoLocal(...) -> void (lanza si no es local o no existe)
 *   - eliminarContenidoLocal($id) -> void (soft delete, is_active = 0)
 *   - listarContenidoLocal($limite, $offset) -> list<array>
 *   - crearGeneroLocal($nombre) -> genre_id (int)
 *   - listarGeneros() -> list<array{id:int,nombre:string,tmdb_id:?int}>
 */
final class AdminContenidoRepo
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Crea una pelicula/serie local. $posterPath ya debe venir resuelto
     * (ej. "/assets/images/posters/admin/<uuid>.webp") — esta capa no
     * sube archivos, solo guarda el path que le pasen.
     *
     * @param list<int> $generoIdsLocales ids de la tabla generos (NO tmdb_id)
     * @return string uuid del contenido creado
     */
    public function crearContenidoLocal(
        string $tipo,
        string $titulo,
        ?string $descripcion,
        ?string $posterPath,
        ?int $anio,
        array $generoIdsLocales,
        string $creadoPorIdUsuario
    ): string {
        $tipoDb = $tipo === 'series' ? 'series' : 'movie';
        $idBinario = UuidHelper::v7();

        $stmt = $this->pdo->prepare(
            'INSERT INTO contenido
                (id, tmdb_id, origen, created_by, type, titulo, descripcion, poster_path, anio_lanzamiento)
             VALUES
                (:id, NULL, \'local\', :created_by, :type, :titulo, :descripcion, :poster_path, :anio_lanzamiento)'
        );
        $stmt->execute([
            ':id' => $idBinario,
            ':created_by' => UuidHelper::uuidABinario($creadoPorIdUsuario),
            ':type' => $tipoDb,
            ':titulo' => $titulo,
            ':descripcion' => $descripcion,
            ':poster_path' => $posterPath,
            ':anio_lanzamiento' => $anio,
        ]);

        $this->sincronizarGenerosLocales($idBinario, $generoIdsLocales);

        return UuidHelper::binarioAUuid($idBinario);
    }

    /**
     * Edita un contenido local existente. No toca contenido con
     * origen='tmdb' (lanza excepcion) para no permitir que el admin
     * pise por error datos que vienen de la API.
     *
     * @param list<int> $generoIdsLocales
     */
    public function actualizarContenidoLocal(
        string $contentId,
        string $titulo,
        ?string $descripcion,
        ?string $posterPath,
        ?int $anio,
        array $generoIdsLocales
    ): void {
        $idBinario = UuidHelper::uuidABinario($contentId);

        $verifica = $this->pdo->prepare('SELECT origen FROM contenido WHERE id = :id');
        $verifica->execute([':id' => $idBinario]);
        $origen = $verifica->fetchColumn();

        if ($origen === false) {
            throw new \RuntimeException('Contenido no encontrado.');
        }
        if ($origen !== 'local') {
            throw new \RuntimeException('Solo se puede editar contenido local (origen=local).');
        }

        $stmt = $this->pdo->prepare(
            'UPDATE contenido SET
                titulo = :titulo,
                descripcion = :descripcion,
                poster_path = :poster_path,
                anio_lanzamiento = :anio_lanzamiento
             WHERE id = :id'
        );
        $stmt->execute([
            ':titulo' => $titulo,
            ':descripcion' => $descripcion,
            ':poster_path' => $posterPath,
            ':anio_lanzamiento' => $anio,
            ':id' => $idBinario,
        ]);

        $this->sincronizarGenerosLocales($idBinario, $generoIdsLocales);
    }

    /**
     * Soft delete: is_active=0. No se borra la fila para no perder
     * ratings/historial_vistas que la referencian (FK ON DELETE CASCADE
     * los borraria en cascada si se hiciera DELETE real).
     */
    public function eliminarContenidoLocal(string $contentId): void
    {
        $idBinario = UuidHelper::uuidABinario($contentId);

        $stmt = $this->pdo->prepare(
            "UPDATE contenido SET is_active = 0 WHERE id = :id AND origen = 'local'"
        );
        $stmt->execute([':id' => $idBinario]);
    }

    /**
     * Lista contenido local para el panel admin (tabla de gestion).
     * No filtra por is_active: el admin tiene que poder ver y reactivar
     * lo que borro antes.
     *
     * @return list<array<string,mixed>>
     */
    public function listarContenidoLocal(int $limite = 50, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, type, titulo, descripcion, poster_path, anio_lanzamiento,
                    rating_avg, rating_count, is_active, created_at
             FROM contenido
             WHERE origen = 'local'
             ORDER BY created_at DESC
             LIMIT :limite OFFSET :offset"
        );
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($filas as &$fila) {
            $fila['id'] = UuidHelper::binarioAUuid($fila['id']);
        }

        return $filas;
    }

    /**
     * Contenido local activo que matchea un tipo y (opcionalmente) un set
     * de generos. Pensado para que home/recomendaciones lo mezcle con los
     * resultados de TMDB. El shape de salida NO es el de TMDB: quien
     * consuma esto debe normalizarlo (eso se hace en HomeController/
     * RecomendacionController).
     *
     * @param list<int> $generoIdsLocales si está vacío, no filtra por género
     * @return list<array<string,mixed>>
     */
    public function contenidoLocalParaCatalogo(string $tipo, array $generoIdsLocales = [], int $limite = 20): array
    {
        $tipoDb = $tipo === 'series' ? 'series' : 'movie';

        if ($generoIdsLocales === []) {
            $stmt = $this->pdo->prepare(
                "SELECT c.id, c.tmdb_id, c.type, c.titulo, c.descripcion, c.poster_path,
                        c.anio_lanzamiento, c.rating_avg, c.rating_count
                 FROM contenido c
                 WHERE c.origen = 'local' AND c.is_active = 1 AND c.type = :type
                 ORDER BY c.created_at DESC
                 LIMIT :limite"
            );
            $stmt->bindValue(':type', $tipoDb);
            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            // PDO no permite mezclar marcadores nombrados (:type) con
            // posicionales (?) en la misma query, asi que los generos
            // tambien van nombrados (:genero0, :genero1, ...).
            $marcadoresGenero = [];
            $valoresGenero = [];
            foreach (array_values($generoIdsLocales) as $i => $genreId) {
                $clave = ":genero{$i}";
                $marcadoresGenero[] = $clave;
                $valoresGenero[$clave] = $genreId;
            }
            $marcadores = implode(',', $marcadoresGenero);

            $stmt = $this->pdo->prepare(
                "SELECT DISTINCT c.id, c.tmdb_id, c.type, c.titulo, c.descripcion, c.poster_path,
                        c.anio_lanzamiento, c.rating_avg, c.rating_count
                 FROM contenido c
                 INNER JOIN contenido_generos cg ON cg.content_id = c.id
                 WHERE c.origen = 'local' AND c.is_active = 1 AND c.type = :type
                   AND cg.genre_id IN ({$marcadores})
                 ORDER BY c.created_at DESC
                 LIMIT :limite"
            );
            $stmt->bindValue(':type', $tipoDb);
            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
            foreach ($valoresGenero as $clave => $genreId) {
                $stmt->bindValue($clave, $genreId, PDO::PARAM_INT);
            }
            $stmt->execute();
        }

        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($filas as &$fila) {
            $fila['id'] = UuidHelper::binarioAUuid($fila['id']);
        }

        return $filas;
    }

    /**
     * Un contenido local por id, con generos ya resueltos (para que el
     * detalle pueda mostrar chips de genero igual que el contenido TMDB).
     * Devuelve null si no existe o si no es local.
     *
     * @return array<string,mixed>|null
     */
    public function buscarLocalPorId(string $contentId): ?array
    {
        $idBinario = UuidHelper::uuidABinario($contentId);

        $stmt = $this->pdo->prepare(
            "SELECT id, type, titulo, descripcion, poster_path, anio_lanzamiento,
                    rating_avg, rating_count
             FROM contenido
             WHERE id = :id AND origen = 'local' AND is_active = 1"
        );
        $stmt->execute([':id' => $idBinario]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!is_array($fila)) {
            return null;
        }

        $fila['id'] = UuidHelper::binarioAUuid($fila['id']);

        $generos = $this->pdo->prepare(
            'SELECT g.nombre FROM generos g
             INNER JOIN contenido_generos cg ON cg.genre_id = g.id
             WHERE cg.content_id = :content_id'
        );
        $generos->execute([':content_id' => $idBinario]);
        $fila['generos'] = $generos->fetchAll(PDO::FETCH_COLUMN) ?: [];

        return $fila;
    }

    /**
     * Traduce tmdb_id (lo que el usuario tiene guardado en
     * preferencias.generos) a los ids INTERNOS de la tabla generos
     * (lo que usa contenido_generos.genre_id). Necesario porque
     * contenidoLocalParaCatalogo() filtra por id interno, no por tmdb_id.
     *
     * @param list<int> $tmdbIds
     * @return list<int>
     */
    public function idsInternosPorTmdbId(array $tmdbIds): array
    {
        if ($tmdbIds === []) {
            return [];
        }

        $marcadores = implode(',', array_fill(0, count($tmdbIds), '?'));
        $stmt = $this->pdo->prepare("SELECT id FROM generos WHERE tmdb_id IN ({$marcadores})");
        $stmt->execute(array_values($tmdbIds));

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
    }

    /**
     * Crea un genero nuevo SIN tmdb_id (genero 100% inventado por el admin).
     * Si ya existe uno con ese nombre, devuelve el id existente (UNIQUE en
     * nombre) en vez de fallar.
     */
    public function crearGeneroLocal(string $nombre): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO generos (nombre, tmdb_id) VALUES (:nombre, NULL)
             ON DUPLICATE KEY UPDATE nombre = nombre'
        );
        $stmt->execute([':nombre' => $nombre]);

        $busca = $this->pdo->prepare('SELECT id FROM generos WHERE nombre = :nombre');
        $busca->execute([':nombre' => $nombre]);

        return (int) $busca->fetchColumn();
    }

    /**
     * Todos los generos (vienen de TMDB o creados localmente, da igual
     * para mostrarlos en un <select>/checkboxes del formulario admin).
     *
     * @return list<array{id:int,nombre:string,tmdb_id:?int}>
     */
    public function listarGeneros(): array
    {
        $stmt = $this->pdo->query('SELECT id, nombre, tmdb_id FROM generos ORDER BY nombre ASC');

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Vincula contenido local con generos por su id INTERNO (no tmdb_id).
     * Distinto de ContenidoRepo::sincronizarGeneros(), que resuelve por
     * tmdb_id porque trabaja con datos que vienen de la API.
     *
     * @param list<int> $generoIdsLocales
     */
    private function sincronizarGenerosLocales(string $contentIdBin, array $generoIdsLocales): void
    {
        $borrar = $this->pdo->prepare('DELETE FROM contenido_generos WHERE content_id = :content_id');
        $borrar->execute([':content_id' => $contentIdBin]);

        if ($generoIdsLocales === []) {
            return;
        }

        $insertar = $this->pdo->prepare(
            'INSERT IGNORE INTO contenido_generos (content_id, genre_id) VALUES (:content_id, :genre_id)'
        );

        foreach ($generoIdsLocales as $genreId) {
            $insertar->execute([':content_id' => $contentIdBin, ':genre_id' => $genreId]);
        }
    }
}
