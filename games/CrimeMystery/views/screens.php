<?php defined('APP_ROOT') || exit; /* Telas de "Quem Cometeu o Crime?" — preenchidas por assets/game.js com textContent. */ ?>
<section class="screen theme-cr" id="cr-brief" data-screen="cr-brief" hidden>
    <div class="phase-head">
        <div class="game-head"><p class="eyebrow">Arquivo do caso · <span data-difficulty></span></p><h2 class="title-sm cr-title" data-title></h2><p class="progress-text" data-progress></p></div>
        <?= $timer ?>
    </div>
    <div class="cr-paper">
        <p class="cr-intro" id="cr-intro"></p>
        <dl class="cr-facts" id="cr-facts"></dl>
    </div>
    <div class="cr-role" id="cr-role"></div>
    <h3 class="cr-h">Suspeitos</h3>
    <ul class="cr-suspects" data-suspects></ul>
    <h3 class="cr-h">Equipe de investigação</h3>
    <ul class="cr-team" data-team></ul>
    <div class="actions-row">
        <button type="button" class="btn btn--primary" id="cr-ready-brief">Li o caso, estou pronto</button>
        <button type="button" class="btn btn--ghost" data-skip id="cr-skip-brief">Começar investigação</button>
    </div>
</section>

<section class="screen theme-cr" id="cr-investigate" data-screen="cr-investigate" hidden>
    <div class="phase-head">
        <div class="game-head"><p class="eyebrow">Investigação</p><h2 class="title-sm cr-title" data-title></h2><p class="progress-text" data-progress></p></div>
        <?= $timer ?>
    </div>
    <div class="cr-grid">
        <div>
            <div class="card cr-panel">
                <h3 class="card__title">Pistas <span class="count" id="cr-clue-count">0</span></h3>
                <p class="muted small" id="cr-next-clue"></p>
                <ol class="cr-clues" data-clues></ol>
            </div>
            <div class="card cr-panel">
                <h3 class="card__title">Seus interrogatórios</h3>
                <ol class="cr-log" id="cr-log"></ol>
            </div>
        </div>
        <div>
            <div class="card cr-panel">
                <h3 class="card__title">Ações <span class="count" id="cr-actions">0</span></h3>
                <p class="muted small" id="cr-role-line"></p>
                <label class="field"><span>Interrogar suspeito</span>
                    <select id="cr-suspect-select"></select>
                </label>
                <ul class="choices choices--text cr-questions" id="cr-questions"></ul>
                <h4 class="cr-h4">Vasculhar um local</h4>
                <ul class="choices cr-locations" id="cr-locations"></ul>
                <p class="form-error" role="alert" hidden></p>
            </div>
            <details class="card cr-panel">
                <summary class="card__title">Ficha dos suspeitos</summary>
                <ul class="cr-suspects cr-suspects--compact" data-suspects></ul>
            </details>
        </div>
    </div>
    <div class="actions-row">
        <button type="button" class="btn btn--primary" id="cr-ready-accuse">Estou pronto para acusar</button>
        <button type="button" class="btn btn--ghost" data-skip id="cr-skip-investigation">Encerrar investigação</button>
    </div>
    <ul class="mini-players" data-mini-players></ul>
</section>

<section class="screen theme-cr" id="cr-accuse" data-screen="cr-accuse" hidden>
    <div class="phase-head">
        <div class="game-head"><p class="eyebrow">Acusação final</p><h2 class="title-sm cr-title" data-title></h2><p class="progress-text" data-progress></p></div>
        <?= $timer ?>
    </div>
    <div class="card" data-state="form">
        <form id="cr-accuse-form" novalidate>
            <p class="prompt">Quem cometeu o crime?</p>
            <div class="cr-pick" id="cr-pick-suspect"></div>
            <label class="field"><span>Por quê? (motivo)</span><select name="motive" id="cr-motive"></select></label>
            <p class="muted small">Sua acusação é secreta e não pode ser alterada depois de confirmada.</p>
            <p class="form-error" role="alert" hidden></p>
            <button type="submit" class="btn btn--primary btn--block">Confirmar acusação</button>
        </form>
    </div>
    <div class="card status-card status-card--ok" data-state="submitted" hidden>
        <div class="status-card__icon" aria-hidden="true">⚖️</div>
        <h3>Acusação registrada</h3>
        <p class="quote" id="cr-my-accusation"></p>
        <p class="muted">Aguardando os outros investigadores...</p>
    </div>
    <details class="card cr-panel">
        <summary class="card__title">Rever pistas</summary>
        <ol class="cr-clues" data-clues></ol>
    </details>
    <ul class="mini-players" data-mini-players></ul>
</section>

<section class="screen theme-cr" id="cr-reveal" data-screen="cr-reveal" hidden>
    <div class="phase-head">
        <div class="game-head"><p class="eyebrow">Caso encerrado</p><h2 class="title-sm cr-title" data-title></h2></div>
        <?= $timer ?>
    </div>
    <div class="cr-verdict">
        <p class="cr-verdict__label">Culpado</p>
        <p class="cr-verdict__name" id="cr-culprit"></p>
        <p class="cr-verdict__motive" id="cr-motive-text"></p>
    </div>
    <div class="cr-paper"><p id="cr-explanation"></p></div>
    <h3 class="cr-h">Pistas que levavam à solução</h3>
    <ol class="cr-clues" id="cr-key-clues"></ol>
    <h3 class="cr-h">Acusações</h3>
    <ul class="cr-accusations" id="cr-accusations"></ul>
    <div class="actions-row"><button type="button" class="btn btn--primary" data-skip id="cr-end">Voltar ao lobby</button></div>
</section>
