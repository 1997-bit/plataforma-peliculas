<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\UuidHelper;
use PDO;

/**
 * Puente entre el catalogo en-vivo (TMDB) y la persistencia local necesaria
 * para historial_vistas/ratings (que requieren un content_id propio, no el tmdb_id).
 *
 * Estrategia "lazy seed": la primera vez que alguien abre el detalle de un
 * tmdb_id, se hace upsert en contenido. No depende del seeder ni del admin.
 */
final class ContenidoRepo
{
    private const VISTA_THROTTLE_SEGUNDOS = 60;

    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @param list<int> $generoIdsTmdb
     */
    public function upsertDesdeTmdb(
        int $tmdbId,
        string $tipo,
        string $titulo,
        ?string $descripcion,
        ?string $posterPath,
        ?int $anio,
        array $generoIdsTmdb
    ): string {
        $tipoDb = $tipo === 'tv' ? 'series' : 'movie';

        $busca = $this->pdo->prepare('SELECT id FROM contenido WHERE tmdb_id = :tmdb_id');
        $busca->execute([':tmdb_id' => $tmdbId]);
        $idExistente = $busca->fetchColumn();

        $idBinario = $idExistente !== false ? $idExistente : UuidHelper::v7();

        $stmt = $this->pdo->prepare(
            'INSERT INTO contenido (id, tmdb_id, type, titulo, descripcion, poster_path, anio_lanzamiento)
             VALUES (:id, :tmdb_id, :type, :titulo, :descripcion, :poster_path, :anio_lanzamiento)
             ON DUPLICATE KEY UPDATE
                titulo = VALUES(titulo),
                descripcion = VALUES(descripcion),
                poster_path = VALUES(poster_path),
                anio_lanzamiento = VALUES(anio_lanzamiento)'
        );
        $stmt->execute([
            ':id' => $idBinario,
            ':tmdb_id' => $tmdbId,
            ':type' => $tipoDb,
            ':titulo' => $titulo,
            ':descripcion' => $descripcion,
            ':poster_path' => $posterPath,
            ':anio_lanzamiento' => $anio,
        ]);

        $this->sincronizarGeneros($idBinario, $generoIdsTmdb);

        return UuidHelper::binarioAUuid($idBinario);
    }

    /**
     * @return array{id:string, rating_avg:float, rating_count:int}|null
     */
    public function buscarPorTmdbId(int $tmdbId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, rating_avg, rating_count FROM contenido WHERE tmdb_id = :tmdb_id AND is_active = 1'
        );
        $stmt->execute([':tmdb_id' => $tmdbId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!is_array($row)) {
            return null;
        }

        return [
            'id' => UuidHelper::binarioAUuid($row['id']),
            'rating_avg' => (float) $row['rating_avg'],
            'rating_count' => (int) $row['rating_count'],
        ];
    }

    /**
     * Verifica que un content_id (UUID string) exista realmente antes de
     * usarlo en ratings/historial_vistas. Evita PDOException por FK con un
     * UUID bien formado pero inexistente.
     */
    public function existeContenido(string $contentId): bool
    {
        try {
            $bin = UuidHelper::uuidABinario($contentId);
        } catch (\InvalidArgumentException) {
            return false;
        }

        $stmt = $this->pdo->prepare('SELECT 1 FROM contenido WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $bin]);

        return $stmt->fetchColumn() !== false;
    }

    public function registrarVista(string $idUsuario, string $contentId): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO historial_vistas (user_id, content_id) VALUES (:user_id, :content_id)'
        );
        $stmt->execute([
            ':user_id' => UuidHelper::uuidABinario($idUsuario),
            ':content_id' => UuidHelper::uuidABinario($contentId),
        ]);
    }

    /**
     * Igual que registrarVista, pero evita insertar una nueva fila si el
     * mismo usuario ya vio el mismo contenido en los últimos N segundos
     * (recarga de página, doble click, etc.). Sin esto, historial_vistas
     * se infla y distorsiona "géneros más vistos" en el panel de admin.
     */
    public function registrarVistaConThrottle(string $idUsuario, string $contentId): void
    {
        $idUsuarioBin = UuidHelper::uuidABinario($idUsuario);
        $contentIdBin = UuidHelper::uuidABinario($contentId);

        $stmt = $this->pdo->prepare(
            'SELECT viewed_at FROM historial_vistas
             WHERE user_id = :user_id AND content_id = :content_id
             ORDER BY viewed_at DESC LIMIT 1'
        );
        $stmt->execute([':user_id' => $idUsuarioBin, ':content_id' => $contentIdBin]);
        $ultimaVista = $stmt->fetchColumn();

        if ($ultimaVista !== false && (time() - strtotime((string) $ultimaVista)) < self::VISTA_THROTTLE_SEGUNDOS) {
            return;
        }

        $insertar = $this->pdo->prepare(
            'INSERT INTO historial_vistas (user_id, content_id) VALUES (:user_id, :content_id)'
        );
        $insertar->execute([':user_id' => $idUsuarioBin, ':content_id' => $contentIdBin]);
    }

    /**
     * Devuelve el historial de "visto recientemente" sin contenido repetido:
     * si el usuario vio la misma película/serie varias veces en días
     * distintos, solo aparece una vez, ordenada por su vista MAS reciente.
     * El throttle de registrarVistaConThrottle evita duplicados a corto
     * plazo (recargas); este GROUP BY evita duplicados a largo plazo
     * (mismo contenido visto en sesiones distintas).
     *
     * @return list<array{content_id:string, viewed_at:string}>
     */
    public function historialReciente(string $idUsuario, int $limite = 10): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.id, c.tmdb_id, c.origen, c.type, c.titulo AS title, c.poster_path, MAX(vh.viewed_at) AS viewed_at
             FROM historial_vistas vh
             INNER JOIN contenido c ON c.id = vh.content_id
             WHERE vh.user_id = :user_id
             GROUP BY vh.content_id, c.id, c.tmdb_id, c.origen, c.type, c.titulo, c.poster_path
             ORDER BY viewed_at DESC
             LIMIT :limite'
        );
        $stmt->bindValue(':user_id', UuidHelper::uuidABinario($idUsuario));
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($filas as &$fila) {
            $fila['id'] = UuidHelper::binarioAUuid($fila['id']);
        }

        return $filas;
    }

    /**
     * Inserta o actualiza la calificación del usuario (1-10) y recalcula
     * rating_avg/rating_count de forma atomica.
     */
    public function calificar(string $idUsuario, string $contentId, int $score): void
    {
        $idUsuarioBin = UuidHelper::uuidABinario($idUsuario);
        $contentIdBin = UuidHelper::uuidABinario($contentId);

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO ratings (user_id, content_id, score) VALUES (:user_id, :content_id, :score)
                 ON DUPLICATE KEY UPDATE score = VALUES(score)'
            );
            $stmt->execute([
                ':user_id' => $idUsuarioBin,
                ':content_id' => $contentIdBin,
                ':score' => $score,
            ]);

            $recalculo = $this->pdo->prepare(
                'UPDATE contenido SET
                    rating_avg = (SELECT COALESCE(AVG(score), 0) FROM ratings WHERE content_id = :content_id_1),
                    rating_count = (SELECT COUNT(*) FROM ratings WHERE content_id = :content_id_2)
                 WHERE id = :content_id_3'
            );
            $recalculo->execute([
                ':content_id_1' => $contentIdBin,
                ':content_id_2' => $contentIdBin,
                ':content_id_3' => $contentIdBin,
            ]);

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function obtenerCalificacionUsuario(string $idUsuario, string $contentId): ?int
    {
        $stmt = $this->pdo->prepare(
            'SELECT score FROM ratings WHERE user_id = :user_id AND content_id = :content_id'
        );
        $stmt->execute([
            ':user_id' => UuidHelper::uuidABinario($idUsuario),
            ':content_id' => UuidHelper::uuidABinario($contentId),
        ]);
        $score = $stmt->fetchColumn();

        return $score !== false ? (int) $score : null;
    }

    /**
     * Todas las calificaciones del usuario, con datos del contenido, para
     * mostrar en /profile. Ordenadas por más reciente primero.
     *
     * @return list<array{tmdb_id:int,type:string,title:string,poster_path:?string,score:int,created_at:string}>
     */
    public function calificacionesDeUsuario(string $idUsuario, int $limite = 50): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.tmdb_id, c.type, c.titulo AS title, c.poster_path, r.score, r.created_at
             FROM ratings r
             INNER JOIN contenido c ON c.id = r.content_id
             WHERE r.user_id = :user_id
             ORDER BY r.created_at DESC
             LIMIT :limite'
        );
        $stmt->bindValue(':user_id', UuidHelper::uuidABinario($idUsuario));
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Generos mas vistos, para el resumen de comportamiento del admin.
     *
     * @return list<array{nombre:string, vistas:int}>
     */
    public function generosMasVistos(int $limite = 10): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT g.nombre, COUNT(*) AS vistas
             FROM historial_vistas vh
             INNER JOIN contenido_generos cg ON cg.content_id = vh.content_id
             INNER JOIN generos g ON g.id = cg.genre_id
             GROUP BY g.id, g.nombre
             ORDER BY vistas DESC
             LIMIT :limite'
        );
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @param list<int> $generoIdsTmdb */
    private function sincronizarGeneros(string $contentIdBin, array $generoIdsTmdb): void
    {
        if ($generoIdsTmdb === []) {
            return;
        }

        $upsertGenero = $this->pdo->prepare(
            'INSERT INTO generos (nombre, tmdb_id) VALUES (:nombre, :tmdb_id)
             ON DUPLICATE KEY UPDATE tmdb_id = VALUES(tmdb_id)'
        );
        $buscaGenero = $this->pdo->prepare('SELECT id FROM generos WHERE tmdb_id = :tmdb_id');

        $borrar = $this->pdo->prepare('DELETE FROM contenido_generos WHERE content_id = :content_id');
        $borrar->execute([':content_id' => $contentIdBin]);

        $insertar = $this->pdo->prepare(
            'INSERT IGNORE INTO contenido_generos (content_id, genre_id) VALUES (:content_id, :genre_id)'
        );

        foreach ($generoIdsTmdb as $tmdbGenreId) {
            $nombre = \App\Helpers\GenerosTmdb::LISTA[$tmdbGenreId] ?? null;
            if ($nombre === null) {
                continue;
            }

            $upsertGenero->execute([':nombre' => $nombre, ':tmdb_id' => $tmdbGenreId]);
            $buscaGenero->execute([':tmdb_id' => $tmdbGenreId]);
            $genreIdInterno = $buscaGenero->fetchColumn();

            if ($genreIdInterno !== false) {
                $insertar->execute([':content_id' => $contentIdBin, ':genre_id' => $genreIdInterno]);
            }
        }
    }
}
