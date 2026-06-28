<?php
/** @var list<array{id:int,nombre:string}> $generos */
/** @var list<int> $generosFavoritos */
/** @var string $csrf */
/** @var string|null $errorMsg */

$tema = $_COOKIE['tema'] ?? null;
$errorMsg = $errorMsg ?? null;
$generosFavoritos = $generosFavoritos ?? [];
?>
<!DOCTYPE html>
<html lang="es" <?= $tema ? 'data-tema="' . htmlspecialchars($tema, ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elegí tus géneros favoritos</title>
    <link rel="stylesheet" href="/assets/css/base.css"/>
    <link rel="stylesheet" href="/assets/css/tokens.css"/>
    <link rel="stylesheet" href="/assets/css/componentes.css"/>
    <link rel="stylesheet" href="/assets/css/perfil.css"/>
</head>
<body>
    <main class="perfil perfil--onboarding">
        <h1 class="perfil-titulo">¿Qué te gusta ver?</h1>
        <p class="perfil-subtexto">Elegí algunos géneros para personalizar tus recomendaciones. Podés cambiarlos después en Configuración.</p>

        <?php if ($errorMsg): ?>
            <p class="perfil-aviso perfil-aviso-error"><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <section class="perfil-seccion">
            <form method="POST" action="/onboarding" class="perfil-form">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

                <fieldset class="perfil-campo">
                    <legend>Géneros favoritos</legend>
                    <div class="perfil-generos-grid">
                        <?php foreach ($generos as $genero): ?>
                            <label class="chip">
                                <input
                                    type="checkbox"
                                    name="generos[]"
                                    value="<?= $genero['id'] ?>"
                                    class="chip-input"
                                    <?= in_array($genero['id'], $generosFavoritos, true) ? 'checked' : '' ?>
                                >
                                <?= htmlspecialchars($genero['nombre'], ENT_QUOTES, 'UTF-8') ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>

                <div class="perfil-onboarding-acciones">
                    <button type="submit" class="boton boton--primario perfil-boton">Continuar</button>
                    <a href="/home" class="perfil-onboarding-saltar">Saltar por ahora</a>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
