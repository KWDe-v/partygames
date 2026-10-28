<?php defined('APP_ROOT') || exit; /* Telas de "O Infiltrado" — preenchidas por assets/game.js com textContent. */ ?>
<section class="screen theme-if" id="if-roles" data-screen="if-roles" hidden>
    <div class="phase-head">
        <div class="game-head"><p class="eyebrow" data-round></p><p class="progress-text">Memorize sua informação secreta. Não mostre para ninguém!</p></div>
        <?= $timer ?>
    </div>
    <div class="if-dossier" id="if-dossier">
        <p class="if-dossier__stamp">Confidencial</p>
        <p class="if-dossier__label" id="if-role-label"></p>
        <p class="if-dossier__word" id="if-role-word"></p>
        <p class="if-dossier__note" id="if-role-note"></p>
    </div>
    <div class="actions-row"><button type="button" class="btn btn--primary" data-skip id="if-skip-roles">Começar as dicas</button></div>
</section>

<section class="screen theme-if" id="if-hints" data-screen="if-hints" hidden>
    <div class="phase-head">
        <div class="game-head"><p class="eyebrow" data-round></p><p class="progress-text" data-progress></p></div>
        <?= $timer ?>
    </div>
    <p class="if-secret"><span class="secret-chip" data-secret></span></p>
    <div class="card card--focus" data-state="form">
        <form id="if-form-hint" novalidate>
            <label class="prompt" for="if-hint-text">Sua dica</label>
            <p class="muted">Uma palavra ou frase curta ligada à palavra secreta — sem entregar demais.</p>
            <textarea id="if-hint-text" name="text" rows="2" maxlength="40" placeholder="Ex.: areia" required></textarea>
            <div class="field-foot"><span class="hint">Não pode conter a palavra secreta.</span><span class="counter" data-counter></span></div>
            <p class="form-error" role="alert" hidden></p>
            <button type="submit" class="btn btn--primary btn--block">Enviar dica</button>
        </form>
    </div>
    <div class="card status-card status-card--ok" data-state="submitted" hidden>
        <div class="status-card__icon" aria-hidden="true">✓</div>
        <h3>Dica enviada!</h3>
        <p class="quote" id="if-my-hint"></p>
        <p class="muted">Aguardando as dicas dos outros agentes...</p>
    </div>
    <div class="card status-card" data-state="spectator" hidden>
        <div class="status-card__icon" aria-hidden="true">👀</div><h3>Rodada em andamento</h3><p>Você entra na próxima rodada.</p>
    </div>
    <ul class="mini-players" data-mini-players></ul>
</section>

<section class="screen theme-if" id="if-talk" data-screen="if-talk" hidden>
    <div class="phase-head">
        <div class="game-head"><p class="eyebrow" data-round></p><h2 class="title-sm" id="if-talk-title">Discussão</h2><p class="progress-text" data-progress></p></div>
        <?= $timer ?>
    </div>
    <p class="if-secret"><span class="secret-chip" data-secret></span></p>
    <div class="card">
        <h3 class="card__title">Dicas da rodada</h3>
        <ul class="if-hints" id="if-hint-list"></ul>
    </div>
    <div class="card" id="if-vote-box" hidden>
        <h3 class="card__title" id="if-vote-title">Quem é o infiltrado?</h3>
        <ul class="choices" id="if-candidates"></ul>
        <p class="form-error" role="alert" hidden></p>
    </div>
    <div class="actions-row"><button type="button" class="btn btn--primary" data-skip id="if-skip-talk">Ir para a votação</button></div>
    <ul class="mini-players" data-mini-players></ul>
</section>

<section class="screen theme-if" id="if-reveal" data-screen="if-reveal" hidden>
    <div class="phase-head">
        <div class="game-head"><p class="eyebrow" data-round></p></div>
        <?= $timer ?>
    </div>
    <div class="if-outcome" id="if-outcome">
        <p class="if-outcome__title" id="if-outcome-title"></p>
        <p class="if-outcome__reason" id="if-outcome-reason"></p>
    </div>
    <div class="card">
        <p class="if-reveal-line">Palavra secreta: <strong id="if-word"></strong><span id="if-similar"></span></p>
        <p class="if-reveal-line">Infiltrado(s): <strong id="if-infiltrators"></strong></p>
        <ul class="bars" id="if-bars"></ul>
    </div>
    <div class="actions-row"><button type="button" class="btn btn--primary" data-skip id="if-next">Próxima rodada</button></div>
</section>
