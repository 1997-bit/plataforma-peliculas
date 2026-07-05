<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\UuidHelper;
use PDO;

final class AdminContenidoRepo
{
    public function __construct(private PDO $pdo)
    {
    }

    public function descargarImagenTmdb(string $tmdbPath, string $tamano = 'w500'): ?string
    {
        $url = "https://image.tmdb.org/t/p/{$tamano}" . $tmdbPath;
        $ctx = stream_context_create(['http' => ['timeout' => 10]]);
        $bin = @file_get_contents($url, false, $ctx);
        if ($bin === false || $bin === '') {
            return null;
        }

        $dir = ROOT . '/public/assets/images/posters/admin';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $nombre = bin2hex(random_bytes(16)) . '.jpg';
        file_put_contents($dir . '/' . $nombre, $bin);

        return '/assets/images/posters/admin/' . $nombre;
    }

    public function upsertGenero(string $nombre, ?int $tmdbId = null): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO generos (nombre, tmdb_id) VALUES (:nombre, :tmdb_id)
             ON DUPLICATE KEY UPDATE tmdb_id = COALESCE(:tmdb_id2, tmdb_id)'
        );
        $stmt->execute([':nombre' => $nombre, ':tmdb_id' => $tmdbId, ':tmdb_id2' => $tmdbId]);

        $busca = $this->pdo->prepare('SELECT id FROM generos WHERE nombre = :nombre');
        $busca->execute([':nombre' => $nombre]);

        return (int) $busca->fetchColumn();
    }

    public function crearContenidoLocal(
        string $tipo,
        string $titulo,
        ?string $descripcion,
        ?string $posterPath,
        ?int $anio,
        array $generoIdsLocales,
        string $creadoPorIdUsuario,
        ?int $tmdbId = null,
        ?string $backdropPath = null,
    ): string {
        $idBinario = UuidHelper::v7();

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO contenido
                    (id, tmdb_id, origen, created_by, type, titulo, descripcion, poster_path, backdrop_path, anio_lanzamiento)
                 VALUES
                    (:id, :tmdb_id, 'local', :created_by, :type, :titulo, :descripcion, :poster_path, :backdrop_path, :anio)"
            );
            $stmt->execute([
                ':id' => $idBinario,
                ':tmdb_id' => $tmdbId,
                ':created_by' => UuidHelper::uuidABinario($creadoPorIdUsuario),
                ':type' => $tipo === 'series' ? 'series' : 'movie',
                ':titulo' => $titulo,
                ':descripcion' => $descripcion,
                ':poster_path' => $posterPath,
                ':backdrop_path' => $backdropPath,
                ':anio' => $anio,
            ]);

            $this->sincronizarGeneros($idBinario, $generoIdsLocales);
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        return UuidHelper::binarioAUuid($idBinario);
    }

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
            throw new \RuntimeException('Solo se puede editar contenido local.');
        }

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                'UPDATE contenido SET titulo=:titulo, descripcion=:descripcion,
                    poster_path=:poster_path, anio_lanzamiento=:anio
                 WHERE id=:id'
            );
            $stmt->execute([
                ':titulo' => $titulo,
                ':descripcion' => $descripcion,
                ':poster_path' => $posterPath,
                ':anio' => $anio,
                ':id' => $idBinario,
            ]);

            $this->sincronizarGeneros($idBinario, $generoIdsLocales);
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function eliminarContenidoLocal(string $contentId): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM contenido WHERE id=:id AND origen='local'");
        $stmt->execute([':id' => UuidHelper::uuidABinario($contentId)]);
    }

    public function listarContenidoLocal(int $limite = 50, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, type, titulo, descripcion, poster_path, backdrop_path,
                    anio_lanzamiento, rating_avg, rating_count, created_at
             FROM contenido
             WHERE origen='local'
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

    public function contenidoParaCatalogo(string $tipo, array $generoIdsLocales = [], int $limite = 20, int $offset = 0, string $busqueda = ''): array
    {
        $tipoDb = $tipo === 'series' ? 'series' : 'movie';
        $params = [':type' => $tipoDb, ':limite' => $limite, ':offset' => $offset];

        if ($busqueda !== '') {
            $sql = "SELECT DISTINCT c.id, c.type, c.titulo, c.descripcion, c.poster_path, c.backdrop_path, c.logo_path,
                           c.anio_lanzamiento, c.rating_avg, c.rating_count
                    FROM contenido c
                    WHERE c.is_active=1 AND c.type=:type AND c.titulo LIKE :q
                    ORDER BY c.created_at DESC LIMIT :limite OFFSET :offset";
            $params[':q'] = '%' . $busqueda . '%';
        } elseif ($generoIdsLocales !== []) {
            $marcadores = implode(',', array_map(fn ($i) => ":g{$i}", array_keys($generoIdsLocales)));
            foreach (array_values($generoIdsLocales) as $i => $id) {
                $params[":g{$i}"] = $id;
            }
            $sql = "SELECT DISTINCT c.id, c.type, c.titulo, c.descripcion, c.poster_path, c.backdrop_path, c.logo_path,
                           c.anio_lanzamiento, c.rating_avg, c.rating_count
                    FROM contenido c
                    INNER JOIN contenido_generos cg ON cg.content_id=c.id
                    WHERE c.is_active=1 AND c.type=:type AND cg.genre_id IN ({$marcadores})
                    ORDER BY c.created_at DESC LIMIT :limite OFFSET :offset";
        } else {
            $sql = "SELECT id, type, titulo, descripcion, poster_path, backdrop_path, logo_path,
                           anio_lanzamiento, rating_avg, rating_count
                    FROM contenido
                    WHERE is_active=1 AND type=:type
                    ORDER BY created_at DESC LIMIT :limite OFFSET :offset";
        }

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, in_array($k, [':limite', ':offset'], true) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($filas as &$fila) {
            $fila['id'] = UuidHelper::binarioAUuid($fila['id']);
        }

        return $filas;
    }

    public function buscarPorId(string $contentId): ?array
    {
        $idBinario = UuidHelper::uuidABinario($contentId);

        $stmt = $this->pdo->prepare(
            "SELECT id, type, titulo, descripcion, poster_path, backdrop_path, logo_path,
                    anio_lanzamiento, rating_avg, rating_count
             FROM contenido WHERE id=:id AND is_active=1"
        );
        $stmt->execute([':id' => $idBinario]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!is_array($fila)) {
            return null;
        }

        $fila['id'] = UuidHelper::binarioAUuid($fila['id']);

        $g = $this->pdo->prepare(
            'SELECT g.nombre FROM generos g
             INNER JOIN contenido_generos cg ON cg.genre_id=g.id
             WHERE cg.content_id=:id'
        );
        $g->execute([':id' => $idBinario]);
        $fila['generos'] = $g->fetchAll(PDO::FETCH_COLUMN) ?: [];

        return $fila;
    }

    public function obtenerLocalPorIdParaEditar(string $contentId): ?array
    {
        $idBinario = UuidHelper::uuidABinario($contentId);

        $stmt = $this->pdo->prepare(
            "SELECT id, type, titulo, descripcion, poster_path, anio_lanzamiento, is_active
             FROM contenido WHERE id=:id AND origen='local'"
        );
        $stmt->execute([':id' => $idBinario]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!is_array($fila)) {
            return null;
        }

        $fila['id'] = UuidHelper::binarioAUuid($fila['id']);

        $g = $this->pdo->prepare('SELECT genre_id FROM contenido_generos WHERE content_id=:id');
        $g->execute([':id' => $idBinario]);
        $fila['genero_ids'] = array_map('intval', $g->fetchAll(PDO::FETCH_COLUMN) ?: []);

        return $fila;
    }

    public function existenPorTmdbId(array $tmdbIds): array
    {
        if ($tmdbIds === []) {
            return [];
        }
        $m = implode(',', array_fill(0, count($tmdbIds), '?'));
        $stmt = $this->pdo->prepare("SELECT tmdb_id FROM contenido WHERE tmdb_id IN ({$m})");
        $stmt->execute(array_values($tmdbIds));
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
    }

    public function listarGeneros(): array
    {
        return $this->pdo->query('SELECT id, nombre, tmdb_id FROM generos ORDER BY nombre ASC')
            ->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function crearGenero(string $nombre): void
    {
        $stmt = $this->pdo->prepare('INSERT IGNORE INTO generos (nombre) VALUES (:nombre)');
        $stmt->execute([':nombre' => trim($nombre)]);
    }

    public function eliminarGenero(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM generos WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public function idsInternosPorTmdbId(array $tmdbIds): array
    {
        if ($tmdbIds === []) {
            return [];
        }

        $m = implode(',', array_fill(0, count($tmdbIds), '?'));
        $stmt = $this->pdo->prepare("SELECT id FROM generos WHERE tmdb_id IN ({$m})");
        $stmt->execute(array_values($tmdbIds));

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
    }

    private function sincronizarGeneros(string $contentIdBin, array $generoIds): void
    {
        $this->pdo->prepare('DELETE FROM contenido_generos WHERE content_id=:id')
            ->execute([':id' => $contentIdBin]);

        if ($generoIds === []) {
            return;
        }

        $ins = $this->pdo->prepare('INSERT INTO contenido_generos (content_id, genre_id) VALUES (:c, :g)');
        foreach ($generoIds as $gid) {
            $ins->execute([':c' => $contentIdBin, ':g' => $gid]);
        }
    }
}
