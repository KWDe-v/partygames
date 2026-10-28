<?php defined('APP_ROOT') || exit; /* Telas de "Missão Secreta" — preenchidas por assets/game.js com textContent. */ ?>
<section class="screen theme-sm" id="sm-main" data-screen="sm-main" hidden>
    <div class="phase-head">
        <div class="game-head"><p class="eyebrow" data-round></p><h2 class="title-sm" id="sm-title"></h2><p class="progress-text" id="sm-sub"></p></div>
        <?= $timer ?>
    </div>
    <div class="sm-mission" id="sm-mission">
        <p class="sm-mission__label">Sua missão secreta</p>
        <p class="sm-mission__text" id="sm-mission-text"></p>
        <p class="sm-mission__hint">Não conte para ninguém!</p>
    </div>
    <p class="sm-topic">Assunto: <strong id="sm-topic"></strong></p>

    <div class="card sm-chat" id="sm-chat-box" hidden>
        <ol class="sm-chat__log" id="sm-log"></ol>
        <form class="sm-chat__form" id="sm-form" novalidate>
            <label class="sr-only" for="sm-text">Mensagem</label>
            <input type="text" id="sm-text" name="text" maxlength="200" autocomplete="off" placeholder="Escreva no chat...">
            <button type="submit" class="btn btn--primary">Enviar</button>
        </form>
        <p class="form-error" role="alert" hidden></p>
    </div>

    <div class="card" id="sm-guess-box" hidden>
        <h3 class="card__title">Qual era a missão de cada um?</h3>
        <ul class="sm-guesses" id="sm-guesses"></ul>
        <p class="form-error" role="alert" hidden></p>
    </div>

    <div class="card" id="sm-eval-box" hidden>
        <h3 class="card__title">Essas missões foram cumpridas?</h3>
        <ul class="sm-evals" id="sm-evals"></ul>
        <p class="muted" id="sm-eval-empty" hidden>Nenhuma missão para você avaliar. Aguarde os outros.</p>
        <p class="form-error" role="alert" hidden></p>
    </div>

    <div class="actions-row"><button type="button" class="btn btn--ghost" data-skip id="sm-skip"></button></div>
    <ul class="mini-players" data-mini-players></ul>
</section>

<section class="screen theme-sm" id="sm-reveal" data-screen="sm-reveal" hidden>
    <div class="phase-head">
        <div class="game-head"><p class="eyebrow" data-round></p><h2 class="title-sm">Missões reveladas</h2></div>
        <?= $timer ?>
    </div>
    <ul class="sm-results" id="sm-results"></ul>
    <div class="actions-row"><button type="button" class="btn btn--primary" data-skip id="sm-next">Próxima rodada</button></div>
</section>
