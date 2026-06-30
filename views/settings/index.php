<?php
/** @var \App\Models\User $usuario */
/** @var list<array{id:int,name:string}> $generos */
/** @var list<int> $generosFavoritos */
/** @var string $csrf */
/** @var string|null $errorMsg */

$tema = $_COOKIE['tema'] ?? null;
$errorMsg = $errorMsg ?? null;
$actualizado = isset($_GET['actualizado']);
$errorQuery = $_GET['error'] ?? null;
?>
<!DOCTYPE html>
<html lang="es" <?= $tema ? 'data-tema="' . htmlspecialchars($tema, ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración</title>
    <link rel="stylesheet" href="/assets/css/navbar.css" />
    <link rel="stylesheet" href="/assets/css/footer.css" />
    <link rel="stylesheet" href="/assets/css/base.css"/>
    <link rel="stylesheet" href="/assets/css/tokens.css"/>
    <link rel="stylesheet" href="/assets/css/componentes.css"/>
    <link rel="stylesheet" href="/assets/css/perfil.css"/>
</head>
<body>
    <?php require ROOT . '/views/partials/nav.php'; ?>

    <main class="perfil">
        <h1 class="perfil-titulo">Configuración</h1>

        <?php if ($actualizado): ?>
            <p class="perfil-aviso perfil-aviso-ok">Cambios guardados correctamente.</p>
        <?php endif; ?>

        <?php if ($errorMsg): ?>
            <p class="perfil-aviso perfil-aviso-error"><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <?php if ($errorQuery === 'importar_no_implementado'): ?>
            <p class="perfil-aviso perfil-aviso-error">Importar configuración todavía no está disponible.</p>
        <?php endif; ?>

        <!-- nombre de usuario + generos favoritos -->
        <section class="perfil-seccion">
            <h2 class="perfil-subtitulo">Editar perfil</h2>
            <form method="POST" action="/settings" class="perfil-form">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

                <div class="perfil-campo">
                    <label for="username">Nombre de usuario</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        value="<?= htmlspecialchars($usuario->username, ENT_QUOTES, 'UTF-8') ?>"
                        minlength="2"
                        maxlength="50"
                        required
                    >
                </div>

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

                <button type="submit" class="boton boton--primario perfil-boton">Guardar cambios</button>
            </form>
        </section>

        <!-- tema -->
        <section class="perfil-seccion" id="apariencia">
            <h2 class="perfil-subtitulo">Apariencia</h2>
            <div class="perfil-tema-selector">
                <span class="perfil-tema-label">Tema</span>
                <div class="perfil-tema-opciones" role="radiogroup" aria-label="Seleccionar tema">
                    <button type="button" class="perfil-tema-boton" data-tema-valor="light" id="btn-tema-light">
                        Claro
                    </button>
                    <button type="button" class="perfil-tema-boton" data-tema-valor="dark" id="btn-tema-dark">
                        Oscuro
                    </button>
                </div>
            </div>
        </section>

        <!-- exportar / importar settings -->
        <!--
            TODO: importar usando XML, usando una API SOAP.
            
        -->
        <section class="perfil-seccion">
            <h2 class="perfil-subtitulo">Exportar / Importar configuración</h2>
            <p class="perfil-vacio">
              TODO: soap api
            </p>
            <div class="perfil-export-acciones">
                <a href="/settings/exportar" class="boton boton--secundario">
                    Exportar configuración (XML)
                </a>
                <button type="button" class="boton boton--secundario" disabled title="Disponible próximamente">
                    Importar configuración (XML)
                </button>
            </div>
        </section>
    </main>

    <script src="/assets/js/tema.js" defer></script>

    <?php require ROOT . '/views/partials/footer.php'; ?>
</body>
</html>
