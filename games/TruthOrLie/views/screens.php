<?php defined('APP_ROOT') || exit; /* Telas de "Mentira ou Verdade?" — preenchidas por assets/game.js com textContent. */ ?>
<section class="screen theme-tl" id="tl-write" data-screen="tl-write" hidden>
    <div class="phase-head">
        <div class="game-head"><p class="eyebrow" data-round></p><p class="progress-text" data-progress></p></div>
        <?= $timer ?>
    </div>
    <div class="card card--focus" data-state="form">
        <form id="tl-form" novalidate>
            <p class="tl-theme">Tema: <strong data-theme></strong></p>
            <label class="prompt" for="tl-text">Escreva uma afirmação sobre você</label>
            <p class="muted" id="tl-example"></p>
            <textarea id="tl-text" name="text" rows="3" maxlength="200" placeholder="Já..." required></textarea>
            <div class="field-foot"><span class="hint">Pode ser verdade ou inventada.</span><span class="counter" data-counter></span></div>
            <fieldset class="tl-truth">
                <legend>Em segredo, ela é:</legend>
                <label class="tl-pick tl-pick--true"><input type="radio" name="truth" value="1" required><span>Verdade</span></label>
                <label class="tl-pick tl-pick--false"><input type="radio" name="truth" value="0"><span>Mentira</span></label>
            </fieldset>
            <p class="form-error" role="alert" hidden></p>
            <button type="submit" class="btn btn--primary btn--block">Enviar afirmação</button>
        </form>
    </div>
    <div class="card status-card status-card--ok" data-state="submitted" hidden>
        <div class="status-card__icon" aria-hidden="true">✓</div>
        <h3>Afirmação enviada!</h3>
        <p class="quote" id="tl-mine"></p>
        <p class="muted" id="tl-mine-truth"></p>
    </div>
    <div class="card status-card" data-state="spectator" hidden>
        <div class="status-card__icon" aria-hidden="true">👀</div><h3>Rodada em andamento</h3><p>Você entra na próxima rodada.</p>
    </div>
    <ul class="mini-players" data-mini-players></ul>
</section>

<section class="screen theme-tl" id="tl-vote" data-screen="tl-vote" hidden>
    <div class="phase-head">
        <div class="game-head"><p class="eyebrow" data-round></p><h2 class="title-sm">Verdade ou mentira?</h2><p class="progress-text" data-progress></p></div>
        <?= $timer ?>
    </div>
    <div class="card status-card status-card--bad" id="tl-excluded" hidden>
        <div class="status-card__icon" aria-hidden="true">🚫</div>
        <h3>Você ficou fora desta rodada</h3>
        <p>Quem não envia afirmação no prazo não vota. Você volta na próxima!</p>
    </div>
    <ul class="tl-list" id="tl-statements"></ul>
    <p class="form-error" role="alert" hidden></p>
    <ul class="mini-players" data-mini-players></ul>
</section>

<section class="screen theme-tl" id="tl-reveal" data-screen="tl-reveal" hidden>
    <div class="phase-head">
        <div class="game-head"><p class="eyebrow" data-round></p><h2 class="title-sm">Revelação</h2></div>
        <?= $timer ?>
    </div>
    <ul class="tl-list" id="tl-results"></ul>
    <div class="actions-row"><button type="button" class="btn btn--primary" data-skip id="tl-next">Próxima rodada</button></div>
</section>
