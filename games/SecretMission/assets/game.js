/* "Missão Secreta" — interface. Cada navegador recebe apenas a própria missão. */
(function () {
    'use strict';
    const $ = (s, r) => (r || document).querySelector(s);
    const $$ = (s, r) => Array.from((r || document).querySelectorAll(s));
    const TITLES = {
        briefing: ['Sua missão', 'Memorize a missão. O bate-papo começa em instantes.', 'Abrir o bate-papo'],
        chat: ['Bate-papo', 'Converse e cumpra sua missão sem chamar atenção.', 'Encerrar o bate-papo'],
        guessing: ['Palpites', 'Adivinhe a missão de cada jogador.', ''],
        evaluation: ['Avaliação', 'Julgue as missões que dependem da opinião do grupo.', ''],
    };
    let lastCount = -1;

    function renderMain(s, R) {
        const g = s.game;
        const phase = s.room.phase;
        const sec = $('#sm-main');
        $$('[data-round]', sec).forEach((el) => { el.textContent = 'Rodada ' + g.round.number + ' de ' + g.round.total; });
        const t = TITLES[phase] || ['', '', ''];
        $('#sm-title').textContent = t[0];
        $('#sm-sub').textContent = t[1];
        R.ui.setTimer(sec, g.round.started_at, g.round.ends_at);
        $('#sm-mission-text').textContent = g.mission || 'Você entra na próxima rodada.';
        $('#sm-mission').classList.toggle('is-compact', phase !== 'briefing');
        $('#sm-topic').textContent = g.topic;

        // Bate-papo
        const chatOn = phase === 'chat' || (phase !== 'briefing' && g.messages.length > 0);
        $('#sm-chat-box').hidden = !chatOn;
        $('#sm-form').hidden = phase !== 'chat' || !g.in_round;
        const log = $('#sm-log');
        if (g.messages.length !== lastCount) {
            const atBottom = log.scrollHeight - log.scrollTop - log.clientHeight < 40;
            log.textContent = '';
            g.messages.forEach((m) => log.appendChild(R.ui.h('li', { class: m.me ? 'is-me' : '' }, [
                R.ui.h('strong', { text: m.nickname }), R.ui.h('span', { text: m.text }),
            ])));
            if (!g.messages.length) log.appendChild(R.ui.h('li', { class: 'muted', text: 'Ninguém falou nada ainda. Puxe o assunto!' }));
            if (atBottom || lastCount < 0) log.scrollTop = log.scrollHeight;
            lastCount = g.messages.length;
        }

        // Palpites
        const guessBox = $('#sm-guess-box');
        guessBox.hidden = phase !== 'guessing' || !g.in_round;
        if (!guessBox.hidden) {
            const list = $('#sm-guesses');
            list.textContent = '';
            (g.targets || []).forEach((tg) => {
                const sel = R.ui.h('select', { 'aria-label': 'Missão de ' + tg.nickname });
                sel.appendChild(R.ui.h('option', { value: '', text: 'Escolha a missão...' }));
                g.options.forEach((o) => sel.appendChild(R.ui.h('option', { value: o.id, text: o.label })));
                if (tg.my_guess) {
                    sel.value = tg.my_guess;
                    sel.disabled = true;
                }
                sel.addEventListener('change', async () => {
                    if (!sel.value) return;
                    sel.disabled = true;
                    try {
                        await R.action('guess', { player: tg.id, option: sel.value });
                        R.pollNow();
                    } catch (e) {
                        sel.disabled = false;
                        R.ui.formError(guessBox, e.message);
                    }
                });
                list.appendChild(R.ui.h('li', null, [R.ui.h('strong', { text: tg.nickname }), sel]));
            });
        }

        // Avaliação das missões subjetivas
        const evalBox = $('#sm-eval-box');
        evalBox.hidden = phase !== 'evaluation';
        if (!evalBox.hidden) {
            const list = $('#sm-evals');
            list.textContent = '';
            (g.evaluations || []).forEach((ev) => {
                const yes = R.ui.h('button', { type: 'button', class: 'btn btn--sm ' + (ev.my_vote === 'yes' ? 'btn--primary' : 'btn--secondary'), text: 'Cumpriu' });
                const no = R.ui.h('button', { type: 'button', class: 'btn btn--sm ' + (ev.my_vote === 'no' ? 'btn--primary' : 'btn--secondary'), text: 'Não cumpriu' });
                [yes, no].forEach((b, i) => {
                    b.disabled = !!ev.my_vote;
                    b.addEventListener('click', async () => {
                        R.ui.setLoading(b, true);
                        try {
                            await R.action('evaluate', { player: ev.player, done: i === 0 });
                            R.pollNow();
                        } catch (e) {
                            R.ui.formError(evalBox, e.message);
                        } finally {
                            R.ui.setLoading(b, false);
                        }
                    });
                });
                list.appendChild(R.ui.h('li', null, [
                    R.ui.h('strong', { text: ev.nickname }), R.ui.h('p', { text: ev.mission }), R.ui.h('div', { class: 'sm-evals__btns' }, [yes, no]),
                ]));
            });
            $('#sm-eval-empty').hidden = (g.evaluations || []).length > 0;
        }

        const skip = $('#sm-skip');
        skip.textContent = t[2];
        if (t[2]) R.ui.hostSkip(skip, s, phase);
        else skip.hidden = true;
        R.ui.miniPlayers(sec, s.players, { submitted: '✓', pending: '...' });
        return 'sm-main';
    }

    function renderReveal(s, R) {
        const g = s.game;
        const sec = $('#sm-reveal');
        $$('[data-round]', sec).forEach((el) => { el.textContent = 'Rodada ' + g.round.number + ' de ' + g.round.total; });
        R.ui.setTimer(sec, g.round.started_at, g.round.ends_at);
        const list = $('#sm-results');
        list.textContent = '';
        ((g.result && g.result.items) || []).forEach((it) => list.appendChild(R.ui.h('li', { class: 'sm-result' + (it.completed ? ' is-done' : '') }, [
            R.ui.h('strong', { text: (it.completed ? '✔ ' : '✘ ') + it.nickname }),
            R.ui.h('p', { text: it.mission }),
            R.ui.h('span', { text: (it.completed ? 'Cumprida' : 'Não cumprida') + ' (' + it.how + ')' + (it.guessed_by ? ' · descoberta por ' + it.guessed_by : ' · ninguém descobriu') }),
        ])));
        R.ui.hostSkip($('#sm-next'), s, 'reveal');
        return 'sm-reveal';
    }

    window.PartyGames.registerGame('missao-secreta', {
        phases: { briefing: 'Missão', chat: 'Bate-papo', guessing: 'Palpites', evaluation: 'Avaliação', reveal: 'Revelação' },
        init(R) {
            $('#sm-form').addEventListener('submit', async (ev) => {
                ev.preventDefault();
                const form = ev.currentTarget;
                const input = $('#sm-text');
                if (!input.value.trim()) return;
                const text = input.value;
                input.value = '';
                try {
                    await R.action('say', { text: text });
                    R.pollNow();
                } catch (e) {
                    input.value = text;
                    R.ui.formError($('#sm-chat-box'), e.message);
                }
                input.focus();
                void form;
            });
        },
        render(s, prev, R) {
            if (!s.game || !s.game.round) return null;
            return s.room.phase === 'reveal' ? renderReveal(s, R) : renderMain(s, R);
        },
    });
})();
