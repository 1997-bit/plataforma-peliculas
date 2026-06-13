<?php
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Home</title>
</head>
<body>
    <h1>Hola, <?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></h1>
    <form method="POST" action="/logout">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
        <button type="submit">Cerrar sesion</button>
    </form>
</body>
</html>
