<?php defined('APP_ROOT') || exit; /* Telas de "O Tribunal" — preenchidas por assets/game.js com textContent. */ ?>
<section class="screen theme-tr" id="tr-court" data-screen="tr-court" hidden>
    <div class="phase-head">
        <div class="game-head"><p class="eyebrow" data-round></p><h2 class="title-sm" id="tr-stage"></h2><p class="progress-text" data-progress></p></div>
        <?= $timer ?>
    </div>
    <div class="tr-docket">
        <p class="tr-docket__title" data-title></p>
        <p class="tr-docket__charge" data-charge></p>
    </div>
    <div class="tr-role" id="tr-role"></div>
    <div class="card card--focus" id="tr-speak" hidden>
        <form id="tr-form" novalidate>
            <label class="prompt" for="tr-text" id="tr-speak-label">Sua fala</label>
            <textarea id="tr-text" name="text" rows="4" maxlength="400" required></textarea>
            <div class="field-foot"><span class="hint">Todos vão ler sua fala.</span><span class="counter" data-counter></span></div>
            <p class="form-error" role="alert" hidden></p>
            <button type="submit" class="btn btn--primary btn--block">Falar ao tribunal</button>
        </form>
    </div>
    <div class="card" id="tr-vote" hidden>
        <h3 class="card__title" id="tr-vote-title">O réu é...</h3>
        <div class="tr-vote">
            <button type="button" class="tr-vote__btn tr-vote__btn--guilty" data-guilty="1">Culpado</button>
            <button type="button" class="tr-vote__btn tr-vote__btn--innocent" data-guilty="0">Inocente</button>
        </div>
        <p class="form-error" role="alert" hidden></p>
    </div>
    <ol class="tr-speeches" id="tr-speeches"></ol>
    <h3 class="cr-h tr-h">No tribunal</h3>
    <ul class="tr-cast" id="tr-cast"></ul>
    <div class="actions-row"><button type="button" class="btn btn--ghost" data-skip id="tr-skip-brief">Abrir a sessão</button></div>
</section>

<section class="screen theme-tr" id="tr-reveal" data-screen="tr-reveal" hidden>
    <div class="phase-head">
        <div class="game-head"><p class="eyebrow" data-round></p><h2 class="title-sm" data-title></h2></div>
        <?= $timer ?>
    </div>
    <div class="tr-verdict" id="tr-verdict">
        <p class="tr-verdict__label">Veredito</p>
        <p class="tr-verdict__word" id="tr-verdict-word"></p>
        <p class="tr-verdict__count" id="tr-verdict-count"></p>
        <p class="tr-verdict__truth" id="tr-verdict-truth"></p>
    </div>
    <ol class="tr-speeches" id="tr-reveal-speeches"></ol>
    <div class="actions-row"><button type="button" class="btn btn--primary" data-skip id="tr-next">Próximo caso</button></div>
</section>
