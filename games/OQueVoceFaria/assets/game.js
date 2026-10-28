/* =========================================================
   "O que você faria se..." — módulo de interface do jogo.
   Usa a API da plataforma (window.PartyGames); toda regra está no servidor.
   ========================================================= */
(function () {
    'use strict';

    const SLUG = 'o-que-voce-faria-se';
    const $ = (sel, root) => (root || document).querySelector(sel);

    const local = {
        resultKey: null,
        shuffleUntil: null,
        focusAfterShuffle: false,
        limits: { qmin: 5, qmax: 250, amin: 2, amax: 250 },
    };

    function applyLimits(limits) {
        if (!limits) return;
        local.limits = limits;
        $('#oqvf-question-text').setAttribute('maxlength', String(limits.qmax));
        $('#oqvf-answer-text').setAttribute('maxlength', String(limits.amax));
    }

    function renderQuestion(s, R) {
        const g = s.game;
        const r = g.round;
        const ui = R.ui;
        const sec = $('#oqvf-question');
        const ta = $('#oqvf-question-text');
        const fresh = ui.preparePhase(r.id + ':q', ta, $('#oqvf-form-question'));
        $('[data-progress]', sec).textContent = r.done + ' de ' + r.total + ' jogadores concluíram';
        ui.setTimer(sec, r.started_at, r.ends_at);

        let state = 'spectator';
        if (g.my.role === 'player') {
            state = g.my.status === 'submitted' ? 'submitted' : (g.my.status === 'missed' ? 'missed' : 'form');
        }
        if (state === 'submitted') {
            $('[data-my-question]', sec).textContent = 'O que você faria se ' + (g.my.question || '') + '?';
            R.drafts.clear(r.id + ':q');
        }
        ui.setSubState(sec, state);
        if (state === 'form' && fresh) setTimeout(() => ta.focus(), 50);
        ui.miniPlayers(sec, s.players);
        return 'oqvf-question';
    }

    function renderAnswer(s, R) {
        const g = s.game;
        const r = g.round;
        const ui = R.ui;
        const sec = $('#oqvf-answer');
        const ta = $('#oqvf-answer-text');
        ui.preparePhase(r.id + ':a', ta, $('#oqvf-form-answer'));
        $('[data-progress]', sec).textContent = r.shuffling
            ? 'Preparando a próxima etapa...'
            : r.done + ' de ' + r.total + ' jogadores responderam';
        // Durante o embaralhamento o cronômetro fica oculto; o prazo só começa depois.
        if (r.shuffling) ui.setTimer(sec, null, null);
        else ui.setTimer(sec, r.started_at, r.ends_at);

        let state = 'spectator';
        if (g.my.role === 'player') {
            const map = { pending: 'form', submitted: 'submitted', missed: 'missed', excluded: 'excluded' };
            state = map[g.my.status] || 'excluded';
        }
        local.shuffleUntil = r.shuffling ? r.started_at : null;
        if (r.shuffling && state === 'form') {
            state = 'shuffling';
            local.focusAfterShuffle = true;
        } else if (state === 'form' && local.focusAfterShuffle) {
            local.focusAfterShuffle = false;
            setTimeout(() => ta.focus(), 50);
        }
        if (state === 'form' || state === 'submitted') {
            $('[data-question]', sec).textContent = 'O que você faria se ' + (g.my.question || '...') + '?';
        }
        if (state === 'submitted') {
            $('[data-my-answer]', sec).textContent = g.my.answer || '';
            R.drafts.clear(r.id + ':a');
        }
        ui.setSubState(sec, state);
        ui.miniPlayers(sec, s.players);
        return 'oqvf-answer';
    }

    function renderResults(s, R) {
        const g = s.game;
        const r = g.round;
        const res = g.result || { position: 0, total: 0, auto: true };
        const ui = R.ui;
        const sec = $('#oqvf-results');
        if (res.auto) ui.setTimer(sec, r.started_at, r.ends_at);
        else ui.setTimer(sec, null, null);

        const card = $('#oqvf-result-card');
        const empty = $('#oqvf-result-empty');
        if (res.item) {
            card.hidden = false;
            empty.hidden = true;
            $('#oqvf-result-counter').textContent = 'Resultado ' + (res.position + 1) + ' de ' + res.total;
            const key = r.id + ':' + res.position;
            if (local.resultKey !== key) {
                local.resultKey = key;
                $('#oqvf-result-question').textContent = res.item.question;
                $('#oqvf-result-answer').textContent = res.item.answer;
                ui.restartAnimation(card);
                ui.restartAnimation($('#oqvf-result-answer'));
            }
        } else {
            card.hidden = true;
            empty.hidden = false;
            $('#oqvf-result-counter').textContent = '';
            $('#oqvf-result-note').textContent = res.note || 'Não houve respostas suficientes.';
        }

        const next = $('#oqvf-btn-next');
        const isLast = res.position + 1 >= res.total;
        next.hidden = !s.me.host;
        next.dataset.position = String(res.position);
        if (!next.classList.contains('is-loading')) next.disabled = false;
        next.textContent = isLast || res.total === 0 ? 'Próxima rodada' : 'Próximo';

        let wait;
        if (res.auto) wait = isLast || res.total === 0 ? 'A próxima rodada começa em instantes...' : 'O próximo resultado aparece automaticamente.';
        else wait = s.me.host ? 'Avance quando todos tiverem lido.' : 'Aguardando o anfitrião avançar...';
        $('#oqvf-result-wait').textContent = wait;
        return 'oqvf-results';
    }

    window.PartyGames.registerGame(SLUG, {
        phases: {
            question_writing: 'Perguntas',
            answer_writing: 'Respostas',
            results: 'Resultados',
        },

        init(R) {
            const ui = R.ui;
            ui.bindTextarea($('#oqvf-question-text'));
            ui.bindTextarea($('#oqvf-answer-text'));

            $('#oqvf-form-question').addEventListener('submit', (ev) => {
                ev.preventDefault();
                ui.submitText(ev.currentTarget, 'submit_question', local.limits.qmin, local.limits.qmax, 'A pergunta', (data) => {
                    const sec = $('#oqvf-question');
                    $('[data-my-question]', sec).textContent = 'O que você faria se ' + data.question + '?';
                    ui.setSubState(sec, 'submitted');
                    ui.toast('Pergunta enviada!');
                });
            });

            $('#oqvf-form-answer').addEventListener('submit', (ev) => {
                ev.preventDefault();
                ui.submitText(ev.currentTarget, 'submit_answer', local.limits.amin, local.limits.amax, 'A resposta', (data) => {
                    const sec = $('#oqvf-answer');
                    $('[data-my-answer]', sec).textContent = data.answer;
                    ui.setSubState(sec, 'submitted');
                    ui.toast('Resposta enviada!');
                });
            });

            $('#oqvf-btn-next').addEventListener('click', async (ev) => {
                const btn = ev.currentTarget;
                ui.setLoading(btn, true);
                try {
                    await R.action('advance', { position: parseInt(btn.dataset.position, 10) || 0 });
                    R.pollNow();
                } catch (e) {
                    ui.toast(e.message, 'error');
                } finally {
                    ui.setLoading(btn, false);
                }
            });
        },

        render(s, prev, R) {
            if (!s.game || !s.game.round) return null;
            applyLimits(s.game.limits);
            switch (s.room.phase) {
                case 'question_writing': return renderQuestion(s, R);
                case 'answer_writing': return renderAnswer(s, R);
                case 'results': return renderResults(s, R);
                default: return null;
            }
        },

        // Fim do "Embaralhando perguntas": busca o estado completo para receber a pergunta.
        onFrame(now, R) {
            if (local.shuffleUntil !== null && now >= local.shuffleUntil + 150) {
                local.shuffleUntil = null;
                R.refresh();
            }
        },

        // Cronômetro zerou no navegador: trava o formulário até o servidor confirmar.
        onDeadline(sec) {
            const form = $('form', sec);
            const formBox = $('[data-state="form"]', sec);
            if (!form || !formBox || formBox.hidden) return;
            $('textarea', form).disabled = true;
            $('button[type="submit"]', form).disabled = true;
            window.PartyGames.ui.formError(form, 'Tempo esgotado! Aguardando o servidor...');
        },
    });
})();
