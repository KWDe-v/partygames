<?php defined('APP_ROOT') || exit;
/** @var string $csrf */
/** @var string $platform */
/** @var array<string, array> $games */
/** @var int $poll */
// Cronômetro reutilizável pelas telas dos jogos (sincronizado com o prazo do servidor).
$timer = '<div class="timer" data-timer aria-live="off"><svg viewBox="0 0 44 44" aria-hidden="true"><circle class="timer__track" cx="22" cy="22" r="19"/><circle class="timer__bar" cx="22" cy="22" r="19"/></svg><span class="timer__num">--</span></div>';
$title = $platform . ' — jogos pra jogar com a galera';
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#1b1140">
    <meta name="description" content="<?= e($description) ?>">
    <meta name="csrf-token" content="<?= e($csrf) ?>">
    <title><?= e($title) ?></title>
    <link rel="icon" href="assets/img/favicon.svg?v=<?= e(APP_VERSION) ?>" type="image/svg+xml">
    <link rel="icon" href="assets/img/favicon-32.png?v=<?= e(APP_VERSION) ?>" sizes="32x32" type="image/png">
    <link rel="alternate icon" href="favicon.ico" sizes="16x16 32x32 48x48">
    <link rel="apple-touch-icon" href="assets/img/apple-touch-icon.png?v=<?= e(APP_VERSION) ?>">
    <link rel="manifest" href="site.webmanifest">

    <meta property="og:type" content="website">
    <meta property="og:locale" content="pt_BR">
    <meta property="og:site_name" content="<?= e($platform) ?>">
    <meta property="og:title" content="<?= e($title) ?>">
    <meta property="og:description" content="<?= e($description) ?>">
    <meta property="og:url" content="<?= e($baseUrl) ?>">
    <meta property="og:image" content="<?= e($baseUrl) ?>assets/img/og-image.png?v=<?= e(APP_VERSION) ?>">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="<?= e($platform) ?> — jogos de festa online">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($title) ?>">
    <meta name="twitter:description" content="<?= e($description) ?>">
    <meta name="twitter:image" content="<?= e($baseUrl) ?>assets/img/og-image.png?v=<?= e(APP_VERSION) ?>">

    <link rel="stylesheet" href="assets/css/app.css?v=<?= e(APP_VERSION) ?>">
    <?php foreach ($games as $g): if (is_file($g['path'] . '/assets/game.css')): ?>
    <link rel="stylesheet" href="<?= e($g['assets_url']) ?>game.css?v=<?= e(APP_VERSION) ?>">
    <?php endif; endforeach; ?>
</head>
<body data-poll="<?= e($poll) ?>">

<div class="backdrop" aria-hidden="true"><span></span><span></span><span></span></div>

<header class="topbar" id="topbar" hidden>
    <a class="topbar__brand" href="./" id="tb-home" title="<?= e($platform) ?>"><img src="assets/img/favicon.svg?v=<?= e(APP_VERSION) ?>" alt="" width="28" height="28"><span><?= e($platform) ?></span></a>
    <div class="topbar__chips">
        <span class="chip chip--game" id="tb-game"><img src="" alt="" width="18" height="18" id="tb-game-cover"><strong id="tb-game-name"></strong></span>
        <span class="chip chip--code" title="Código da sala">Sala <strong id="tb-code">------</strong></span>
        <span class="chip" id="tb-round" hidden>Rodada <strong>1</strong></span>
        <span class="chip chip--phase" id="tb-phase">Lobby</span>
    </div>
    <div class="topbar__actions">
        <button type="button" class="btn btn--ghost btn--sm btn--icon" id="btn-sound" aria-pressed="false" title="Desativar sons">🔊</button>
        <button type="button" class="btn btn--ghost btn--sm" data-howto="" id="btn-howto" title="Regras e instruções do jogo">Como Jogar?</button>
        <button type="button" class="btn btn--ghost btn--sm" id="btn-score" hidden>Placar</button>
        <button type="button" class="btn btn--ghost btn--sm" id="btn-end" hidden>Encerrar</button>
        <button type="button" class="btn btn--ghost btn--sm" id="btn-leave">Sair</button>
    </div>
</header>

<main class="app" id="app">

    <!-- Página inicial: catálogo de jogos -->
    <section class="screen" id="screen-home" data-screen="home" hidden>
        <div class="hero">
            <img class="hero__logo" src="assets/img/favicon.svg?v=<?= e(APP_VERSION) ?>" alt="" width="96" height="96">
            <h1 class="hero__title hero__title--brand"><?= e($platform) ?></h1>
            <p class="hero__lead">Jogos pra jogar com a galera, cada um no seu celular. Crie uma sala, mande o código e <strong>bora jogar</strong> — sem instalar nada.</p>
            <div class="hero__actions">
                <button type="button" class="btn btn--primary btn--lg" data-go="join">Entrar com código</button>
                <a class="btn btn--secondary btn--lg" href="#jogos">Ver jogos</a>
            </div>
        </div>

        <h2 class="section-title" id="jogos">Escolha um jogo</h2>
        <div class="catalog">
            <?php foreach ($games as $slug => $g): ?>
            <article class="game-card" data-accent="<?= e($g['color']) ?>">
                <img class="game-card__cover" src="<?= e($g['assets_url'] . $g['cover']) ?>?v=<?= e(APP_VERSION) ?>" alt="" width="88" height="88" loading="lazy">
                <div class="game-card__body">
                    <h3><?= e($g['name']) ?></h3>
                    <p class="game-card__tagline"><?= e($g['tagline']) ?></p>
                    <p class="game-card__desc"><?= e($g['description']) ?></p>
                    <ul class="game-card__meta">
                        <li><?= e($g['pmin']) ?>–<?= e($g['pmax']) ?> jogadores</li>
                        <?php if ($g['duration'] !== ''): ?><li><?= e($g['duration']) ?></li><?php endif; ?>
                    </ul>
                    <?php if (!empty($g['call'])): ?>
                    <p class="game-card__call"><span aria-hidden="true">🎧</span> Para melhor jogabilidade, recomendamos jogar em call.</p>
                    <?php endif; ?>
                </div>
                <div class="game-card__actions">
                    <button type="button" class="btn btn--secondary" data-howto="<?= e($slug) ?>">Como Jogar?</button>
                    <button type="button" class="btn btn--primary" data-create="<?= e($slug) ?>">Criar sala</button>
                </div>
            </article>
            <?php endforeach; ?>
        </div>

        <ol class="steps">
            <li><span class="steps__n">1</span><strong>Escolha um jogo</strong><span>Crie a sala e ajuste as regras do seu jeito.</span></li>
            <li><span class="steps__n">2</span><strong>Chame a galera</strong><span>Mande o código ou o link de convite. Cada um entra pelo próprio celular.</span></li>
            <li><span class="steps__n">3</span><strong>Joguem juntos</strong><span>Tudo sincronizado em tempo real — no mesmo sofá ou em uma chamada de vídeo.</span></li>
        </ol>
    </section>

    <!-- Criar sala -->
    <section class="screen" id="screen-create" data-screen="create" hidden>
        <form class="card card--narrow" id="form-create" data-mode="create" novalidate>
            <button type="button" class="back" id="create-back">← Voltar</button>
            <div class="create-head">
                <img src="" alt="" width="56" height="56" id="create-cover">
                <div>
                    <p class="eyebrow" id="create-eyebrow">Nova sala</p>
                    <h2 class="title-sm" id="create-title">Criar sala</h2>
                </div>
            </div>
            <label class="field">
                <span>Jogo</span>
                <select name="game" id="create-game">
                    <?php foreach ($games as $slug => $g): ?>
                    <option value="<?= e($slug) ?>"><?= e($g['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <fieldset class="create-personal" id="create-personal">
                <legend class="sr-only">Seus dados e da sala</legend>
                <label class="field">
                    <span>Seu apelido</span>
                    <input type="text" name="nickname" maxlength="<?= e($nmax) ?>" autocomplete="nickname" required>
                </label>
                <label class="field">
                    <span>Nome da sala <small>(opcional)</small></span>
                    <input type="text" name="room_name" maxlength="40" autocomplete="off" placeholder="Ex.: Sexta da galera">
                </label>
                <label class="field">
                    <span>Senha da sala <small>(opcional — deixe em branco para sala aberta)</small></span>
                    <input type="text" name="password" minlength="<?= e($pmin) ?>" maxlength="<?= e($pmaxlen) ?>" autocomplete="off" placeholder="Sem senha">
                </label>
            </fieldset>
            <div class="grid-2">
                <label class="field">
                    <span>Mínimo de jogadores</span>
                    <input type="number" name="min_players" inputmode="numeric">
                </label>
                <label class="field">
                    <span>Máximo de jogadores</span>
                    <input type="number" name="max_players" inputmode="numeric">
                </label>
            </div>
            <?php foreach ($games as $slug => $g): ?>
            <fieldset class="game-settings grid-2" data-game="<?= e($slug) ?>"
                      data-name="<?= e($g['name']) ?>" data-cover="<?= e($g['assets_url'] . $g['cover']) ?>"
                      data-pmin="<?= e($g['pmin']) ?>" data-pmax="<?= e($g['pmax']) ?>"
                      data-pmin-default="<?= e($g['pmin_default']) ?>" data-pmax-default="<?= e($g['pmax_default']) ?>" hidden disabled>
                <legend class="sr-only">Regras de <?= e($g['name']) ?></legend>
                <?php foreach ($g['fields'] as $key => $f): ?>
                <?php if ($f['type'] === 'multi'): ?>
                <fieldset class="field field--wide checks">
                    <legend><?= e($f['label']) ?></legend>
                    <?php foreach ($f['options'] as $value => $label): ?>
                    <label class="check"><input type="checkbox" name="<?= e($key) ?>[]" value="<?= e($value) ?>"<?= in_array($value, (array) $f['default'], true) ? ' checked' : '' ?>><span><?= e($label) ?></span></label>
                    <?php endforeach; ?>
                </fieldset>
                <?php continue; elseif ($f['type'] === 'lines'): ?>
                <label class="field field--wide">
                    <span><?= e($f['label']) ?><?php if ($f['hint'] !== ''): ?> <small>(<?= e($f['hint']) ?>)</small><?php endif; ?></span>
                    <textarea name="<?= e($key) ?>" rows="3" class="textarea-sm" placeholder="Opcional"></textarea>
                </label>
                <?php continue; endif; ?>
                <label class="field">
                    <span><?= e($f['label']) ?><?php if ($f['hint'] !== ''): ?> <small>(<?= e($f['hint']) ?>)</small><?php endif; ?></span>
                    <?php if ($f['type'] === 'select'): ?>
                    <select name="<?= e($key) ?>">
                        <?php foreach ($f['options'] as $value => $label): ?>
                        <option value="<?= e($value) ?>"<?= (string) $value === (string) $f['default'] ? ' selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php elseif ($f['type'] === 'bool'): ?>
                    <select name="<?= e($key) ?>">
                        <option value="1"<?= $f['default'] ? ' selected' : '' ?>>Sim</option>
                        <option value="0"<?= $f['default'] ? '' : ' selected' ?>>Não</option>
                    </select>
                    <?php else: ?>
                    <input type="number" name="<?= e($key) ?>" min="<?= e($f['min']) ?>" max="<?= e($f['max']) ?>" value="<?= e($f['default']) ?>" inputmode="numeric">
                    <?php endif; ?>
                </label>
                <?php endforeach; ?>
            </fieldset>
            <?php endforeach; ?>
            <p class="form-error" role="alert" hidden></p>
            <button type="submit" class="btn btn--primary btn--block" id="create-submit">Criar sala</button>
        </form>
    </section>

    <!-- Entrar -->
    <section class="screen" id="screen-join" data-screen="join" hidden>
        <form class="card card--narrow" id="form-join" novalidate>
            <button type="button" class="back" data-go="home">← Voltar</button>
            <h2 class="title-sm">Entrar em uma sala</h2>
            <p class="muted">O código leva você direto para o jogo certo.</p>
            <label class="field">
                <span>Código da sala</span>
                <input type="text" name="code" class="input-code" maxlength="6" autocomplete="off" autocapitalize="characters" spellcheck="false" required>
            </label>
            <label class="field">
                <span>Senha <small>(se a sala tiver)</small></span>
                <input type="password" name="password" maxlength="<?= e($pmaxlen) ?>" autocomplete="off">
            </label>
            <label class="field">
                <span>Seu apelido</span>
                <input type="text" name="nickname" maxlength="<?= e($nmax) ?>" autocomplete="nickname" required>
            </label>
            <p class="form-error" role="alert" hidden></p>
            <button type="submit" class="btn btn--primary btn--block">Entrar</button>
        </form>
    </section>

    <!-- Reconexão / recuperação de sessão -->
    <section class="screen" id="screen-reconnect" data-screen="reconnect" hidden>
        <div class="card card--narrow center">
            <div class="big-emoji" aria-hidden="true">🔌</div>
            <h2 class="title-sm">Voltar para a partida?</h2>
            <p class="lead" id="reconnect-text">Encontramos uma partida em que você estava.</p>
            <p class="form-error" role="alert" hidden></p>
            <div class="stack">
                <button type="button" class="btn btn--primary btn--block" id="btn-reconnect">Reconectar</button>
                <button type="button" class="btn btn--ghost btn--block" id="btn-forget">Não, ir para o início</button>
            </div>
        </div>
    </section>

    <!-- Lobby (comum a todos os jogos) -->
    <section class="screen" id="screen-lobby" data-screen="lobby" hidden>
        <div class="layout">
            <div class="card">
                <h2 class="lobby-name" id="lobby-name"></h2>
                <div class="lobby-game">
                    <img src="" alt="" width="48" height="48" id="lobby-cover">
                    <div class="lobby-game__info"><p class="eyebrow">Jogo selecionado</p><strong id="lobby-game"></strong></div>
                    <div class="lobby-game__actions">
                        <button type="button" class="btn btn--secondary btn--sm" data-howto="" id="btn-howto-lobby">Como Jogar?</button>
                        <button type="button" class="btn btn--secondary btn--sm" id="btn-configure" hidden>Trocar jogo / regras</button>
                    </div>
                </div>
                <p class="eyebrow">Código da sala</p>
                <div class="room-code" id="lobby-code">------</div>
                <p class="muted" id="lobby-share">Compartilhe o código com seus amigos.</p>
                <button type="button" class="btn btn--secondary btn--sm" id="btn-copy">Copiar link de convite</button>
                <div class="notice" id="lobby-notice" hidden></div>
                <div class="lobby-status" id="lobby-status">Aguardando jogadores...</div>
                <button type="button" class="btn btn--primary btn--lg btn--block" id="btn-start" hidden>Iniciar partida</button>
                <dl class="settings" id="lobby-settings"></dl>
            </div>
            <aside class="stack-col">
                <div class="card">
                    <h3 class="card__title">Jogadores <span class="count" id="lobby-count">0</span></h3>
                    <ul class="players" id="lobby-players"></ul>
                </div>
                <div class="card" id="lobby-last" hidden>
                    <h3 class="card__title">Última partida <small class="muted" id="lobby-last-game"></small></h3>
                    <ol class="scoreboard" id="lobby-last-board"></ol>
                </div>
            </aside>
        </div>
    </section>

    <!-- Telas de cada jogo -->
    <?php foreach ($games as $slug => $game): if (is_file($game['path'] . '/views/screens.php')): ?>
    <div class="game-screens" data-game="<?= e($slug) ?>">
        <?php include $game['path'] . '/views/screens.php'; ?>
    </div>
    <?php endif; endforeach; ?>

    <!-- Jogo indisponível no navegador (script não carregou) -->
    <section class="screen" id="screen-unsupported" data-screen="unsupported" hidden>
        <div class="card card--narrow center">
            <div class="big-emoji" aria-hidden="true">🧩</div>
            <h2 class="title-sm">Não foi possível carregar este jogo</h2>
            <p class="lead">Atualize a página. Se o problema continuar, avise quem administra o site.</p>
        </div>
    </section>

    <!-- Partida encerrada -->
    <section class="screen" id="screen-finished" data-screen="finished" hidden>
        <div class="card card--narrow center">
            <div class="big-emoji" aria-hidden="true">🏁</div>
            <h2 class="title-sm">A partida foi encerrada</h2>
            <p class="lead" id="finished-text"></p>
            <p class="muted" id="finished-rounds"></p>
            <button type="button" class="btn btn--primary btn--block" id="btn-home">Voltar ao início</button>
        </div>
    </section>

    <!-- Carregando -->
    <section class="screen is-visible" id="screen-loading" data-screen="loading">
        <div class="loader" aria-label="Carregando"><span></span><span></span><span></span></div>
    </section>
</main>

<div class="net-banner" id="net-banner" role="status" hidden>Reconectando...</div>
<div class="toasts" id="toasts" aria-live="polite"></div>

<dialog class="dialog dialog--wide howto" id="howto-dialog" aria-labelledby="howto-title">
    <form method="dialog">
        <button value="close" class="dialog__close" aria-label="Fechar" title="Fechar">✕</button>
        <div class="howto__head">
            <img src="" alt="" width="56" height="56" id="howto-cover">
            <div><p class="eyebrow">Como Jogar?</p><h2 class="title-sm" id="howto-title"></h2></div>
        </div>
        <div id="howto-body"></div>
        <div class="dialog__actions"><button value="close" class="btn btn--primary">Entendi!</button></div>
    </form>
</dialog>

<?php
// Regras e instruções de cada jogo (manifesto 'how_to'), em <template> inertes: o JS só copia para o diálogo.
$howtoSections = ['steps' => 'Passo a passo', 'rules' => 'Regras', 'scoring' => 'Pontuação', 'tips' => 'Dicas'];
foreach ($games as $slug => $g): $h = (array) $g['how_to']; ?>
<template id="howto-<?= e($slug) ?>" data-name="<?= e($g['name']) ?>" data-cover="<?= e($g['assets_url'] . $g['cover']) ?>?v=<?= e(APP_VERSION) ?>">
    <p class="howto__goal"><strong>Objetivo:</strong> <?= e($h['goal'] ?? $g['description']) ?></p>
    <p class="howto__meta"><?= e($g['pmin']) ?>–<?= e($g['pmax']) ?> jogadores<?= $g['duration'] !== '' ? ' · ' . e($g['duration']) : '' ?></p>
    <?php if (!empty($g['call'])): ?><p class="howto__call">🎧 Para melhor jogabilidade, recomendamos jogar em call.</p><?php endif; ?>
    <?php foreach ($howtoSections as $key => $label): if (!empty($h[$key])): ?>
    <h3 class="howto__h"><?= e($label) ?></h3>
    <<?= $key === 'steps' ? 'ol' : 'ul' ?> class="howto__list howto__list--<?= e($key) ?>">
        <?php foreach ((array) $h[$key] as $item): ?><li><?= e($item) ?></li><?php endforeach; ?>
    </<?= $key === 'steps' ? 'ol' : 'ul' ?>>
    <?php endif; endforeach; ?>
    <p class="howto__note">Os tempos e os pontos podem ser ajustados por quem cria a sala.</p>
</template>
<?php endforeach; ?>

<dialog class="dialog dialog--wide" id="score-dialog">
    <form method="dialog">
        <button value="close" class="dialog__close" aria-label="Fechar" title="Fechar">✕</button>
        <h2 class="title-sm">Placar</h2>
        <p class="muted" id="score-empty" hidden>Este jogo não usa pontuação.</p>
        <ol class="scoreboard" id="score-board"></ol>
        <h3 class="card__title">Histórico da partida</h3>
        <ol class="history" id="score-history"></ol>
        <div class="dialog__actions"><button value="close" class="btn btn--primary">Fechar</button></div>
    </form>
</dialog>

<dialog class="dialog" id="confirm-dialog">
    <form method="dialog">
        <p id="confirm-text"></p>
        <div class="dialog__actions">
            <button value="cancel" class="btn btn--ghost">Cancelar</button>
            <button value="ok" class="btn btn--primary" id="confirm-ok">Confirmar</button>
        </div>
    </form>
</dialog>

<noscript><p class="noscript">Esta página precisa de JavaScript habilitado.</p></noscript>
<script src="assets/js/sfx.js?v=<?= e(APP_VERSION) ?>" defer></script>
<script src="assets/js/platform.js?v=<?= e(APP_VERSION) ?>" defer></script>
<?php foreach ($games as $g): if (is_file($g['path'] . '/assets/game.js')): ?>
<script src="<?= e($g['assets_url']) ?>game.js?v=<?= e(APP_VERSION) ?>" defer></script>
<?php endif; endforeach; ?>
</body>
</html>
