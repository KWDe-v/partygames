<?php defined('APP_ROOT') || exit; /* Telas de "A Bomba Relógio" — preenchidas por assets/game.js com textContent. */ ?>
<section class="screen theme-tb" id="tb-turn" data-screen="tb-turn" hidden>
    <div class="phase-head">
        <div class="game-head"><p class="eyebrow" data-round></p><p class="progress-text" id="tb-rule"></p></div>
        <?= $timer ?>
    </div>
    <div class="tb-category"><span>Categoria</span><strong id="tb-category"></strong></div>
    <div class="tb-bomb" id="tb-bomb">
        <div class="tb-bomb__icon" aria-hidden="true">💣</div>
        <p class="tb-bomb__holder" id="tb-holder"></p>
    </div>
    <div class="card card--focus" id="tb-answer" hidden>
        <form id="tb-form" novalidate>
            <label class="sr-only" for="tb-text">Sua resposta</label>
            <input type="text" id="tb-text" name="text" maxlength="60" autocomplete="off" placeholder="Rápido! Algo da categoria...">
            <p class="form-error" role="alert" hidden></p>
            <button type="submit" class="btn btn--primary btn--block">Passar a bomba</button>
        </form>
    </div>
    <ul class="tb-ring" id="tb-ring"></ul>
    <h3 class="card__title tb-h">Respostas</h3>
    <ol class="tb-history" id="tb-history"></ol>
</section>

<section class="screen theme-tb" id="tb-reveal" data-screen="tb-reveal" hidden>
    <div class="phase-head">
        <div class="game-head"><p class="eyebrow" data-round></p></div>
        <?= $timer ?>
    </div>
    <div class="tb-result">
        <p class="tb-result__label">Sobrevivente</p>
        <p class="tb-result__name" id="tb-survivor"></p>
        <p class="muted" id="tb-summary"></p>
    </div>
    <ol class="tb-history" id="tb-final-history"></ol>
    <div class="actions-row"><button type="button" class="btn btn--primary" data-skip id="tb-next">Próxima categoria</button></div>
</section>
