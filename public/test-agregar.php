<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';

use App\Core\Database;
use App\Core\Session;
use App\Helpers\UuidHelper;

// Solo para pruebas: requiere estar logueado como admin.
if (Session::obtener('user_role') !== 'admin') {
    http_response_code(403);
    echo 'Solo admin. Inicia sesion como admin primero.';
    exit;
}

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = Database::obtenerInstancia();

    $idBinario = UuidHelper::v7();
    $idUsuarioBin = UuidHelper::uuidABinario((string) Session::obtener('user_id'));

    $tipo = $_POST['type'] === 'series' ? 'series' : 'movie';
    $titulo = trim((string) ($_POST['titulo'] ?? ''));
    $descripcion = trim((string) ($_POST['descripcion'] ?? '')) ?: null;
    $anio = $_POST['anio'] !== '' ? (int) $_POST['anio'] : null;

    $stmt = $pdo->prepare(
        'INSERT INTO contenido
            (id, tmdb_id, origen, created_by, type, titulo, descripcion, poster_path, anio_lanzamiento)
         VALUES
            (:id, NULL, \'local\', :created_by, :type, :titulo, :descripcion, NULL, :anio)'
    );
    $stmt->execute([
        ':id' => $idBinario,
        ':created_by' => $idUsuarioBin,
        ':type' => $tipo,
        ':titulo' => $titulo,
        ':descripcion' => $descripcion,
        ':anio' => $anio,
    ]);

    $uuid = UuidHelper::binarioAUuid($idBinario);
    $mensaje = "Insertado. UUID: {$uuid} -> probar en /contenido?id={$uuid}&origen=local";
}

// Lista lo que hay como contenido local para confirmar visualmente.
$pdo = Database::obtenerInstancia();
$filas = $pdo->query(
    "SELECT id, type, titulo, anio_lanzamiento, created_at
     FROM contenido WHERE origen = 'local' ORDER BY created_at DESC"
)->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html>
<head><title>Test agregar contenido local</title></head>
<body>

<h1>Test: agregar pelicula/serie local (SOLO PRUEBA, BORRAR DESPUES)</h1>

<?php if ($mensaje !== ''): ?>
    <p><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>

<form method="POST">
    <label>Tipo:
        <select name="type">
            <option value="movie">Pelicula</option>
            <option value="series">Serie</option>
        </select>
    </label>
    <br><br>

    <label>Titulo: <input type="text" name="titulo" required></label>
    <br><br>

    <label>Descripcion: <textarea name="descripcion"></textarea></label>
    <br><br>

    <label>Anio: <input type="number" name="anio" min="1900" max="2100"></label>
    <br><br>

    <button type="submit">Insertar</button>
</form>

<hr>

<h2>Contenido local existente</h2>
<table border="1">
    <tr><th>UUID</th><th>Tipo</th><th>Titulo</th><th>Anio</th><th>Creado</th><th>Ver</th></tr>
    <?php foreach ($filas as $fila): ?>
        <?php $uuid = UuidHelper::binarioAUuid($fila['id']); ?>
        <tr>
            <td><?= htmlspecialchars($uuid, ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($fila['type'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($fila['titulo'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string) $fila['anio_lanzamiento'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($fila['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><a href="/contenido?id=<?= urlencode($uuid) ?>&origen=local">Ver detalle</a></td>
        </tr>
    <?php endforeach; ?>
</table>

</body>
</html>
