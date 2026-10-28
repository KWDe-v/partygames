<?php defined('APP_ROOT') || exit; /* Telas de "A Resposta Mais Absurdamente Errada" — preenchidas por assets/game.js com textContent. */ ?>
<section class="screen theme-ab" id="ab-answer" data-screen="ab-answer" hidden>
    <div class="phase-head">
        <div class="game-head"><p class="eyebrow" data-round></p><p class="progress-text" data-progress></p></div>
        <?= $timer ?>
    </div>
    <div class="ab-question" data-question></div>
    <div class="card card--focus" data-state="form">
        <form id="ab-form" novalidate>
            <label class="sr-only" for="ab-text">Sua resposta</label>
            <textarea id="ab-text" name="text" rows="3" maxlength="200" placeholder="Quanto mais absurda, melhor..." required></textarea>
            <div class="field-foot"><span class="hint">Ninguém vai saber que foi você até o fim da votação.</span><span class="counter" data-counter></span></div>
            <p class="form-error" role="alert" hidden></p>
            <button type="submit" class="btn btn--primary btn--block">Enviar resposta</button>
        </form>
    </div>
    <div class="card status-card status-card--ok" data-state="submitted" hidden>
        <div class="status-card__icon" aria-hidden="true">✓</div>
        <h3>Resposta enviada!</h3>
        <p class="quote" id="ab-mine"></p>
        <p class="muted">Aguardando as respostas dos outros...</p>
    </div>
    <div class="card status-card" data-state="spectator" hidden>
        <div class="status-card__icon" aria-hidden="true">👀</div><h3>Rodada em andamento</h3><p>Você entra na próxima pergunta.</p>
    </div>
    <ul class="mini-players" data-mini-players></ul>
</section>

<section class="screen theme-ab" id="ab-vote" data-screen="ab-vote" hidden>
    <div class="phase-head">
        <div class="game-head"><p class="eyebrow" data-round></p><p class="progress-text" data-progress></p></div>
        <?= $timer ?>
    </div>
    <div class="ab-question" data-question></div>
    <p class="ab-instruction" id="ab-vote-hint">Vote na resposta mais absurda (não vale a sua).</p>
    <ul class="choices choices--text ab-answers" id="ab-answers"></ul>
    <p class="form-error" role="alert" hidden></p>
    <ul class="mini-players" data-mini-players></ul>
</section>

<section class="screen theme-ab" id="ab-reveal" data-screen="ab-reveal" hidden>
    <div class="phase-head">
        <div class="game-head"><p class="eyebrow" data-round></p><p class="progress-text" id="ab-reveal-sub"></p></div>
        <?= $timer ?>
    </div>
    <div class="ab-question" data-question></div>
    <ul class="ab-results" id="ab-results"></ul>
    <div class="actions-row"><button type="button" class="btn btn--primary" data-skip id="ab-next">Próxima pergunta</button></div>
</section>
