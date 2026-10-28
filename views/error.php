<?php defined('APP_ROOT') || exit; ?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Site indisponível — PartyGames</title>
    <link rel="icon" href="assets/img/favicon.svg?v=<?= e(APP_VERSION) ?>" type="image/svg+xml">
    <link rel="icon" href="assets/img/favicon-32.png" sizes="32x32" type="image/png">
    <link rel="stylesheet" href="assets/css/app.css?v=<?= e(APP_VERSION) ?>">
</head>
<body>
<div class="backdrop" aria-hidden="true"><span></span><span></span><span></span></div>
<main class="app">
    <section class="screen is-visible">
        <div class="card card--narrow center">
            <div class="big-emoji" aria-hidden="true">🛠️</div>
            <h1 class="title-sm">O site ainda não pode ser iniciado</h1>
            <p class="lead"><?= e($message) ?></p>
            <?php if (!empty($detail)): ?>
                <pre class="debug"><?= e($detail) ?></pre>
            <?php endif; ?>
            <p class="muted small">Administrador: confira o arquivo <code>config/config.php</code> e os registros em <code>storage/logs/</code>.</p>
            <a class="btn btn--primary" href="./">Tentar novamente</a>
        </div>
    </section>
</main>
</body>
</html>
