<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\UuidHelper;
use PDO;
use Ramsey\Uuid\Uuid;

/**
 * Puente entre el catalogo en-vivo (TMDB) y la persistencia local necesaria
<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\UuidHelper;
use PDO;
use Ramsey\Uuid\Uuid;

/**
 * Puente entre el catalogo en-vivo (TMDB) y la persistencia local necesaria
 * para view_history/ratings (que requieren un content_id propio, no el tmdb_id).
 *
 * Estrategia "lazy seed": la primera vez que alguien abre el detalle de un
 * tmdb_id, se hace upsert en content. No depende del seeder ni del admin.
 */
final class ContenidoRepo
{
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

        $busca = $this->pdo->prepare('SELECT id FROM content WHERE tmdb_id = :tmdb_id');
        $busca->execute([':tmdb_id' => $tmdbId]);
        $idExistente = $busca->fetchColumn();

        $idBinario = $idExistente !== false ? $idExistente : Uuid::uuid7()->getBytes();

        $stmt = $this->pdo->prepare(
            'INSERT INTO content (id, tmdb_id, type, title, description, poster_path, release_year)
             VALUES (:id, :tmdb_id, :type, :title, :description, :poster_path, :release_year)
             ON DUPLICATE KEY UPDATE
                title = VALUES(title),
                description = VALUES(description),
                poster_path = VALUES(poster_path),
                release_year = VALUES(release_year)'
        );
        $stmt->execute([
            ':id' => $idBinario,
            ':tmdb_id' => $tmdbId,
            ':type' => $tipoDb,
            ':title' => $titulo,
            ':description' => $descripcion,
            ':poster_path' => $posterPath,
            ':release_year' => $anio,
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
            'SELECT id, rating_avg, rating_count FROM content WHERE tmdb_id = :tmdb_id AND is_active = 1'
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

    public function registrarVista(string $idUsuario, string $contentId): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO view_history (user_id, content_id) VALUES (:user_id, :content_id)'
        );
        $stmt->execute([
            ':user_id' => UuidHelper::uuidABinario($idUsuario),
            ':content_id' => UuidHelper::uuidABinario($contentId),
        ]);
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
            'SELECT c.tmdb_id, c.type, c.title, c.poster_path, MAX(vh.viewed_at) AS viewed_at
             FROM view_history vh
             INNER JOIN content c ON c.id = vh.content_id
             WHERE vh.user_id = :user_id
             GROUP BY vh.content_id, c.tmdb_id, c.type, c.title, c.poster_path
             ORDER BY viewed_at DESC
             LIMIT :limite'
        );
        $stmt->bindValue(':user_id', UuidHelper::uuidABinario($idUsuario));
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                'UPDATE content SET
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
            'SELECT c.tmdb_id, c.type, c.title, c.poster_path, r.score, r.created_at
             FROM ratings r
             INNER JOIN content c ON c.id = r.content_id
             WHERE r.user_id = :user_id
             ORDER BY r.created_at DESC
             LIMIT :limite'
        );
        $stmt->bindValue(':user_id', UuidHelper::uuidABinario($idUsuario));
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @param list<int> $generoIdsTmdb */
    private function sincronizarGeneros(string $contentIdBin, array $generoIdsTmdb): void
    {
        if ($generoIdsTmdb === []) {
            return;
        }

        $upsertGenero = $this->pdo->prepare(
            'INSERT INTO genres (name, tmdb_id) VALUES (:name, :tmdb_id)
             ON DUPLICATE KEY UPDATE tmdb_id = VALUES(tmdb_id)'
        );
        $buscaGenero = $this->pdo->prepare('SELECT id FROM genres WHERE tmdb_id = :tmdb_id');

        $borrar = $this->pdo->prepare('DELETE FROM content_genres WHERE content_id = :content_id');
        $borrar->execute([':content_id' => $contentIdBin]);

        $insertar = $this->pdo->prepare(
            'INSERT IGNORE INTO content_genres (content_id, genre_id) VALUES (:content_id, :genre_id)'
        );

        foreach ($generoIdsTmdb as $tmdbGenreId) {
            $nombre = \App\Helpers\GenerosTmdb::LISTA[$tmdbGenreId] ?? null;
            if ($nombre === null) {
                continue;
            }

            $upsertGenero->execute([':name' => $nombre, ':tmdb_id' => $tmdbGenreId]);
            $buscaGenero->execute([':tmdb_id' => $tmdbGenreId]);
            $genreIdInterno = $buscaGenero->fetchColumn();

            if ($genreIdInterno !== false) {
                $insertar->execute([':content_id' => $contentIdBin, ':genre_id' => $genreIdInterno]);
            }
        }
    }
}
