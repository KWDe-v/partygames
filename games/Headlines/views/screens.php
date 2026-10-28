<?php defined('APP_ROOT') || exit; /* Telas de "Manchete Absurda" — preenchidas por assets/game.js com textContent. */ ?>
<section class="screen theme-hl" id="hl-write" data-screen="hl-write" hidden>
    <div class="phase-head">
        <div class="game-head"><p class="eyebrow" data-round></p><p class="progress-text" data-progress></p></div>
        <?= $timer ?>
    </div>
    <div class="hl-paper">
        <p class="hl-paper__mast">O Diário Absurdo</p>
        <p class="hl-paper__section" data-section></p>
        <p class="hl-paper__theme" data-theme></p>
        <p class="hl-paper__example" id="hl-example"></p>
    </div>
    <div class="card card--focus" data-state="form">
        <form id="hl-form" novalidate>
            <label class="prompt" for="hl-text">Sua manchete</label>
            <textarea id="hl-text" name="text" rows="2" maxlength="140" placeholder="URGENTE: ..." required></textarea>
            <div class="field-foot"><span class="hint">Ninguém sabe quem escreveu até o fim da votação.</span><span class="counter" data-counter></span></div>
            <p class="form-error" role="alert" hidden></p>
            <button type="submit" class="btn btn--primary btn--block">Mandar para a redação</button>
        </form>
    </div>
    <div class="card status-card status-card--ok" data-state="submitted" hidden>
        <div class="status-card__icon" aria-hidden="true">📰</div>
        <h3>Manchete enviada!</h3>
        <p class="quote" id="hl-mine"></p>
        <p class="muted">Aguardando os outros repórteres...</p>
    </div>
    <div class="card status-card" data-state="spectator" hidden>
        <div class="status-card__icon" aria-hidden="true">👀</div><h3>Rodada em andamento</h3><p>Você entra na próxima rodada.</p>
    </div>
    <ul class="mini-players" data-mini-players></ul>
</section>

<section class="screen theme-hl" id="hl-vote" data-screen="hl-vote" hidden>
    <div class="phase-head">
        <div class="game-head"><p class="eyebrow" data-round></p><p class="progress-text" data-progress></p></div>
        <?= $timer ?>
    </div>
    <p class="hl-vote-theme"><span data-section></span> · <strong data-theme></strong></p>
    <p class="muted center" id="hl-vote-hint"></p>
    <ul class="choices choices--text hl-headlines" id="hl-headlines"></ul>
    <p class="form-error" role="alert" hidden></p>
    <ul class="mini-players" data-mini-players></ul>
</section>

<section class="screen theme-hl" id="hl-reveal" data-screen="hl-reveal" hidden>
    <div class="phase-head">
        <div class="game-head"><p class="eyebrow" data-round></p></div>
        <?= $timer ?>
    </div>
    <div class="hl-front" id="hl-front">
        <p class="hl-paper__mast">O Diário Absurdo</p>
        <p class="hl-front__label" id="hl-front-label">Capa de hoje</p>
        <div id="hl-front-headlines"></div>
    </div>
    <ol class="hl-ranking" id="hl-ranking"></ol>
    <div class="actions-row"><button type="button" class="btn btn--primary" data-skip id="hl-next">Próxima edição</button></div>
</section>
