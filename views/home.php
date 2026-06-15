<?php
$tema = $_COOKIE['tema'] ?? null;
?>
<!DOCTYPE html>
<html lang="es" <?= $tema ? 'data-tema="' . htmlspecialchars($tema, ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
<head>
    <meta charset="UTF-8">
    <title>Home</title>
    <link rel="stylesheet" href="/assets/css/base.css">
</head>
<body>
    <h1>Hola, <?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></h1>

    <form method="POST" action="/logout">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
        <button type="submit">Cerrar sesión</button>
    </form>

    <button id="btn-tema">Toggle</button>

    <script src="/assets/js/tema.js"></script>
</body>
</html>
