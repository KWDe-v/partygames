<?php defined('APP_ROOT') || exit; /* Telas de "Quem Sou Eu?" — preenchidas por assets/game.js com textContent. */ ?>
<section class="screen theme-wa" id="wa-play" data-screen="wa-play" hidden>
    <div class="phase-head">
        <div class="game-head"><p class="eyebrow" data-round></p><p class="progress-text" id="wa-clock"></p></div>
        <?= $timer ?>
    </div>
    <div class="wa-me" id="wa-me">
        <p class="wa-me__label">Você é...</p>
        <p class="wa-me__name" id="wa-me-name">???</p>
        <p class="wa-me__hint" id="wa-me-hint"></p>
    </div>
    <p class="wa-turn" id="wa-turn"></p>
    <div class="card card--focus" id="wa-ask" hidden>
        <form id="wa-ask-form" novalidate>
            <label class="prompt" for="wa-question">Faça uma pergunta de sim ou não</label>
            <input type="text" id="wa-question" name="text" maxlength="150" autocomplete="off" placeholder="Eu sou um animal?">
            <p class="form-error" role="alert" hidden></p>
            <button type="submit" class="btn btn--primary btn--block">Perguntar</button>
        </form>
        <form id="wa-guess-form" class="wa-guess" novalidate>
            <label class="prompt" for="wa-guess">...ou arrisque um palpite <span class="muted" id="wa-guesses"></span></label>
            <input type="text" id="wa-guess" name="text" maxlength="60" autocomplete="off" placeholder="Eu sou...">
            <p class="form-error" role="alert" hidden></p>
            <button type="submit" class="btn btn--ghost btn--block">Palpitar (encerra a vez)</button>
        </form>
    </div>
    <div class="card card--focus" id="wa-answer" hidden>
        <p class="prompt" id="wa-asker"></p>
        <p class="big-prompt" id="wa-question-text"></p>
        <div class="wa-answers" id="wa-answer-buttons">
            <button type="button" class="btn wa-yes" data-value="yes">Sim</button>
            <button type="button" class="btn wa-no" data-value="no">Não</button>
            <button type="button" class="btn wa-idk" data-value="idk">Não sei</button>
        </div>
        <p class="form-error" role="alert" hidden></p>
    </div>
    <div class="card status-card" id="wa-wait" hidden><p id="wa-wait-text"></p></div>
    <h3 class="card__title wa-h">Identidades da mesa</h3>
    <ul class="wa-people" id="wa-people"></ul>
    <h3 class="card__title wa-h">Histórico</h3>
    <ol class="wa-log" id="wa-log"></ol>
    <ul class="mini-players" data-mini-players></ul>
</section>

<section class="screen theme-wa" id="wa-reveal" data-screen="wa-reveal" hidden>
    <div class="phase-head">
        <div class="game-head"><p class="eyebrow" data-round></p></div>
        <?= $timer ?>
    </div>
    <h2 class="wa-reveal-title">Quem era quem</h2>
    <ul class="wa-people wa-people--reveal" id="wa-result"></ul>
    <div class="actions-row"><button type="button" class="btn btn--primary" data-skip id="wa-next">Continuar</button></div>
</section>
