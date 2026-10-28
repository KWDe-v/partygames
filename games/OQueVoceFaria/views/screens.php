<?php defined('APP_ROOT') || exit;
/**
 * Telas de "O que você faria se...". Incluídas pela plataforma em views/app.php.
 * Disponíveis: $timer (HTML do cronômetro) e $game (manifesto). Conteúdo dinâmico é preenchido
 * pelo assets/game.js sempre com textContent.
 */
?>
<!-- Etapa 1: escrever a pergunta -->
<section class="screen" id="oqvf-question" data-screen="oqvf-question" hidden>
    <div class="phase-head">
        <div>
            <p class="eyebrow">Etapa 1 de 3</p>
            <h2 class="title-sm">Escreva sua pergunta</h2>
            <p class="progress-text" data-progress></p>
        </div>
        <?= $timer ?>
    </div>
    <div class="card card--focus" data-state="form">
        <form id="oqvf-form-question" novalidate>
            <label class="prompt" for="oqvf-question-text">O que você faria se<span class="dots">...</span></label>
            <textarea id="oqvf-question-text" name="text" rows="3" maxlength="250" placeholder="o mundo acabasse hoje" required></textarea>
            <div class="field-foot"><span class="hint">Escreva apenas o complemento.</span><span class="counter" data-counter></span></div>
            <p class="form-error" role="alert" hidden></p>
            <button type="submit" class="btn btn--primary btn--block">Concluir pergunta</button>
        </form>
    </div>
    <div class="card status-card status-card--ok" data-state="submitted" hidden>
        <div class="status-card__icon" aria-hidden="true">✓</div>
        <h3>Pergunta enviada!</h3>
        <p class="quote" data-my-question></p>
        <p class="muted">Aguardando os demais participantes...</p>
    </div>
    <div class="card status-card status-card--bad" data-state="missed" hidden>
        <div class="status-card__icon" aria-hidden="true">⏰</div>
        <h3>Tempo esgotado</h3>
        <p>Você não enviou uma pergunta e foi excluído desta rodada. Você volta a jogar na próxima!</p>
    </div>
    <div class="card status-card" data-state="spectator" hidden>
        <div class="status-card__icon" aria-hidden="true">👀</div>
        <h3>A rodada já começou</h3>
        <p>Você participará a partir da próxima rodada.</p>
    </div>
    <ul class="mini-players" data-mini-players></ul>
</section>

<!-- Etapa 2: responder -->
<section class="screen" id="oqvf-answer" data-screen="oqvf-answer" hidden>
    <div class="phase-head">
        <div>
            <p class="eyebrow">Etapa 2 de 3</p>
            <h2 class="title-sm">Responda à pergunta</h2>
            <p class="progress-text" data-progress></p>
        </div>
        <?= $timer ?>
    </div>
    <div class="card oqvf-shuffle" data-state="shuffling" hidden>
        <div class="oqvf-shuffle__deck" aria-hidden="true"><span></span><span></span><span></span><span></span><span></span></div>
        <h3 class="oqvf-shuffle__title">Embaralhando perguntas<span class="dots">...</span></h3>
        <p class="muted">Cada um vai receber a pergunta de outra pessoa — sem saber de quem.</p>
    </div>
    <div class="card card--focus" data-state="form">
        <form id="oqvf-form-answer" novalidate>
            <p class="prompt prompt--question" data-question></p>
            <label class="sr-only" for="oqvf-answer-text">Sua resposta</label>
            <textarea id="oqvf-answer-text" name="text" rows="3" maxlength="250" placeholder="Eu..." required></textarea>
            <div class="field-foot"><span class="hint">Ninguém sabe quem escreveu esta pergunta.</span><span class="counter" data-counter></span></div>
            <p class="form-error" role="alert" hidden></p>
            <button type="submit" class="btn btn--primary btn--block">Enviar resposta</button>
        </form>
    </div>
    <div class="card status-card status-card--ok" data-state="submitted" hidden>
        <div class="status-card__icon" aria-hidden="true">✓</div>
        <h3>Resposta enviada!</h3>
        <p class="quote" data-my-answer></p>
        <p class="muted">Aguardando os demais participantes...</p>
    </div>
    <div class="card status-card status-card--bad" data-state="missed" hidden>
        <div class="status-card__icon" aria-hidden="true">⏰</div>
        <h3>Tempo esgotado</h3>
        <p>Sua resposta não foi enviada a tempo e ficará fora da apresentação desta rodada.</p>
    </div>
    <div class="card status-card status-card--bad" data-state="excluded" hidden>
        <div class="status-card__icon" aria-hidden="true">🚫</div>
        <h3>Você foi excluído desta rodada</h3>
        <p>Como sua pergunta não foi enviada a tempo, você aguarda a próxima rodada.</p>
    </div>
    <div class="card status-card" data-state="spectator" hidden>
        <div class="status-card__icon" aria-hidden="true">👀</div>
        <h3>Rodada em andamento</h3>
        <p>Você participará a partir da próxima rodada.</p>
    </div>
    <ul class="mini-players" data-mini-players></ul>
</section>

<!-- Etapa 3: resultados embaralhados -->
<section class="screen" id="oqvf-results" data-screen="oqvf-results" hidden>
    <div class="phase-head">
        <div>
            <p class="eyebrow">Etapa 3 de 3</p>
            <h2 class="title-sm">As combinações</h2>
            <p class="progress-text" id="oqvf-result-counter"></p>
        </div>
        <?= $timer ?>
    </div>
    <article class="oqvf-reveal" id="oqvf-result-card" hidden>
        <p class="oqvf-reveal__q"><span class="oqvf-reveal__label">O que você faria se</span> <span id="oqvf-result-question"></span>?</p>
        <p class="oqvf-reveal__a" id="oqvf-result-answer"></p>
    </article>
    <div class="card status-card" id="oqvf-result-empty" hidden>
        <div class="status-card__icon" aria-hidden="true">🤷</div>
        <h3>Sem combinações nesta rodada</h3>
        <p id="oqvf-result-note"></p>
    </div>
    <div class="results-actions">
        <p class="muted" id="oqvf-result-wait"></p>
        <button type="button" class="btn btn--primary btn--lg" id="oqvf-btn-next" hidden>Próximo</button>
    </div>
</section>
