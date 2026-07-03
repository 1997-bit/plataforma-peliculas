<?php
use App\Helpers\TmdbImagen;
/** @var \App\Models\User $usuario */
/** @var list<array<string,mixed>> $historial */
/** @var list<array<string,mixed>> $calificaciones */
$tema = $_COOKIE['tema'] ?? null;
$historial = $historial ?? [];
$calificaciones = $calificaciones ?? [];
?>
<!DOCTYPE html>
<html lang="es" <?= $tema ? 'data-tema="' . htmlspecialchars($tema, ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi perfil</title>
    <link rel="stylesheet" href="/assets/css/navbar.css" />
    <link rel="stylesheet" href="/assets/css/footer.css" />
    <link rel="stylesheet" href="/assets/css/base.css"/>
    <link rel="stylesheet" href="/assets/css/tokens.css"/>
    <link rel="stylesheet" href="/assets/css/componentes.css"/>
    <link rel="stylesheet" href="/assets/css/perfil.css"/>
</head>
<body>
    <?php require ROOT . '/views/partials/nav.php'; ?>
    <?php
        $hueBanner = hexdec(substr(md5($usuario->id), 0, 4)) % 360;
        $iniciales = mb_strtoupper(mb_substr($usuario->username, 0, 1), 'UTF-8');
    ?>
    <div class="perfil-banner" style="--banner-hue: <?= $hueBanner ?>"></div>
    <main class="perfil">
        <div class="perfil-encabezado">
            <div class="perfil-avatar" style="--banner-hue: <?= $hueBanner ?>" aria-hidden="true">
                <?= htmlspecialchars($iniciales, ENT_QUOTES, 'UTF-8') ?>
            </div>
            <div class="perfil-encabezado-texto">
                <h1 class="perfil-titulo"><?= htmlspecialchars($usuario->username, ENT_QUOTES, 'UTF-8') ?></h1>
                <span class="perfil-badge <?= $usuario->esAdmin() ? 'perfil-badge-admin' : '' ?>">
                    <?= $usuario->esAdmin() ? 'Administrador' : 'Usuario estándar' ?>
                </span>
            </div>
        </div>

        <section class="perfil-seccion">
            <h2 class="perfil-subtitulo">Datos de la cuenta</h2>
            <dl class="perfil-datos">
                <div class="perfil-dato">
                    <dt>Correo</dt>
                    <dd><?= htmlspecialchars($usuario->email, ENT_QUOTES, 'UTF-8') ?></dd>
                </div>
                <div class="perfil-dato">
                    <dt>Nombre de usuario</dt>
                    <dd><?= htmlspecialchars($usuario->username, ENT_QUOTES, 'UTF-8') ?></dd>
                </div>
            </dl>
            <a href="/settings" class="perfil-editar-link">Editar perfil y configuración</a>
        </section>

        <section class="perfil-seccion">
            <h2 class="perfil-subtitulo">Visto recientemente</h2>
            <?php if ($historial === []): ?>
                <p class="perfil-vacio">Todavía no has visto nada.</p>
            <?php else: ?>
                <ul class="perfil-lista" role="list">
                    <?php foreach ($historial as $item): ?>
                        <?php
                            $titulo = htmlspecialchars($item['title'] ?? 'Sin título', ENT_QUOTES, 'UTF-8');
                            $tipoUrl = $item['type'] === 'series' ? 'series' : 'movie';
                            $poster = TmdbImagen::poster($item['poster_path'] ?? null, 'xs');
                            $fecha = !empty($item['viewed_at']) ? date('d/m/Y', strtotime((string) $item['viewed_at'])) : '';
                        ?>
                        <li class="perfil-item">
                            <a href="/contenido?id=<?= urlencode((string) $item['id']) ?>&tipo=<?= $tipoUrl ?>" class="perfil-item-link">
                                <div class="poster-marco poster-marco--xs"><img class="poster-img" src="<?= $poster ?>" alt="" loading="lazy"></div>
                                <span class="perfil-item-info">
                                    <span class="perfil-item-titulo"><?= $titulo ?></span>
                                    <span class="perfil-item-meta">Visto el <?= $fecha ?></span>
                                </span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <section class="perfil-seccion">
            <h2 class="perfil-subtitulo">Tus calificaciones</h2>
            <?php if ($calificaciones === []): ?>
                <p class="perfil-vacio">Todavía no has calificado nada.</p>
            <?php else: ?>
                <ul class="perfil-lista" role="list">
                    <?php foreach ($calificaciones as $item): ?>
                        <?php
                            $titulo = htmlspecialchars($item['title'] ?? 'Sin título', ENT_QUOTES, 'UTF-8');
                            $tipoUrl = $item['type'] === 'series' ? 'series' : 'movie';
                            $poster = TmdbImagen::poster($item['poster_path'] ?? null, 'xs');
                            $estrellas = (int) round(((int) $item['score']) / 2);
                        ?>
                        <li class="perfil-item">
                            <a href="/contenido?id=<?= urlencode((string) $item['id']) ?>&tipo=<?= $tipoUrl ?>" class="perfil-item-link">
                                <div class="poster-marco poster-marco--xs"><img class="poster-img" src="<?= $poster ?>" alt="" loading="lazy"></div>
                                <span class="perfil-item-info">
                                    <span class="perfil-item-titulo"><?= $titulo ?></span>
                                    <span class="perfil-item-estrellas" aria-label="<?= $estrellas ?> de 5 estrellas">
                                        <?= str_repeat('★', $estrellas) . str_repeat('☆', 5 - $estrellas) ?>
                                    </span>
                                </span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </main>
    <?php require ROOT . '/views/partials/footer.php'; ?>
</body>
</html>
