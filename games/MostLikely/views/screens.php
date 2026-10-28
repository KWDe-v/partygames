<?php defined('APP_ROOT') || exit; /* Telas de "Quem é Mais Provável?" — preenchidas por assets/game.js com textContent. */ ?>
<section class="screen theme-ml" id="ml-vote" data-screen="ml-vote" hidden>
    <div class="phase-head">
        <div class="game-head">
            <p class="eyebrow" data-round></p>
            <p class="progress-text" data-progress></p>
        </div>
        <?= $timer ?>
    </div>
    <div class="card ml-question">
        <p class="big-prompt" id="ml-question"></p>
        <p class="muted" id="ml-hint">Escolha em segredo. Ninguém vai saber em quem você votou.</p>
        <ul class="choices" id="ml-candidates"></ul>
        <p class="form-error" role="alert" hidden></p>
    </div>
    <div class="card status-card" id="ml-spectator" hidden>
        <div class="status-card__icon" aria-hidden="true">👀</div>
        <h3>Rodada em andamento</h3>
        <p>Você entra na próxima pergunta.</p>
    </div>
    <ul class="mini-players" data-mini-players></ul>
</section>

<section class="screen theme-ml" id="ml-reveal" data-screen="ml-reveal" hidden>
    <div class="phase-head">
        <div class="game-head">
            <p class="eyebrow" data-round></p>
            <p class="progress-text" id="ml-reveal-sub"></p>
        </div>
        <?= $timer ?>
    </div>
    <div class="card">
        <p class="big-prompt ml-reveal-q" id="ml-reveal-question"></p>
        <div class="ml-winner" id="ml-winner"></div>
        <ul class="bars" id="ml-bars"></ul>
    </div>
    <div class="actions-row"><button type="button" class="btn btn--primary" data-skip id="ml-next">Próxima pergunta</button></div>
</section>
