/* "Quem Sou Eu?" — interface. A identidade do próprio jogador nunca chega ao navegador antes da hora. */
(function () {
    'use strict';
    const $ = (s, r) => (r || document).querySelector(s);
    const $$ = (s, r) => Array.from((r || document).querySelectorAll(s));
    const local = { roundEnd: null, turnKey: null };

    function people(list, items, R) {
        list.textContent = '';
        items.forEach((p) => list.appendChild(R.ui.h('li', {
            class: 'wa-person' + (p.current ? ' is-current' : '') + (p.solved ? ' is-solved' : '') + (p.me ? ' is-me' : ''),
        }, [
            R.ui.h('span', { class: 'wa-person__nick', text: p.nickname + (p.me ? ' (você)' : '') + (p.solved ? ' ✓' : '') }),
            R.ui.h('span', { class: 'wa-person__id', text: p.identity === null ? '???' : p.identity }),
        ])));
    }

    function log(list, items, R) {
        list.textContent = '';
        items.forEach((it) => {
            const text = it.type === 'guess' ? 'palpitou "' + it.text + '"' + (it.correct ? ' — acertou! 🎉' : ' — errou') : it.text;
            const children = [R.ui.h('strong', { text: it.nickname }), R.ui.h('span', { text: text })];
            if (it.type === 'question') {
                const a = it.answers;
                children.push(R.ui.h('span', { class: 'wa-log__counts', text: a ? 'Sim ' + a.yes + ' · Não ' + a.no + ' · Não sei ' + a.idk : 'respondendo...' }));
            }
            list.appendChild(R.ui.h('li', { class: it.type === 'guess' ? (it.correct ? 'is-right' : 'is-wrong') : '' }, children));
        });
        if (!items.length) list.appendChild(R.ui.h('li', { class: 'muted', text: 'Nenhuma pergunta ainda.' }));
    }

    function renderPlay(s, R) {
        const g = s.game;
        const sec = $('#wa-play');
        const phase = s.room.phase;
        $$('[data-round]', sec).forEach((el) => { el.textContent = 'Rodada ' + g.round.number + ' de ' + g.round.total; });
        R.ui.setTimer(sec, g.round.started_at, g.round.ends_at);
        local.roundEnd = g.round_end;
        const me = g.people.filter((p) => p.me)[0];
        const current = g.people.filter((p) => p.current)[0];
        $('#wa-me').classList.toggle('is-solved', g.solved_me);
        $('#wa-me-name').textContent = me && me.identity !== null ? me.identity : '???';
        $('#wa-me-hint').textContent = !me ? 'Você entra na próxima rodada.' : (g.solved_me ? 'Você descobriu! Agora ajude os outros.' : 'Categoria: ' + g.my_category);
        $('#wa-turn').textContent = !current ? '' : (current.me ? 'Sua vez!' : 'Vez de ' + current.nickname);

        // Mudou o turno ou a fase: limpa os formulários.
        const key = g.round.id + ':' + (current ? current.id : '') + ':' + phase + ':' + g.log.length;
        const fresh = key !== local.turnKey;
        local.turnKey = key;
        if (fresh) {
            R.ui.formError($('#wa-ask-form'), '');
            R.ui.formError($('#wa-guess-form'), '');
            R.ui.formError($('#wa-answer'), '');
        }
        $('#wa-ask').hidden = !g.my_turn;
        if (g.my_turn && fresh) {
            $('#wa-question').value = '';
            $('#wa-guess').value = '';
            setTimeout(() => $('#wa-question').focus(), 30);
        }
        $('#wa-guesses').textContent = '(' + g.guesses_left + ' restante' + (g.guesses_left === 1 ? '' : 's') + ')';
        $('#wa-guess-form').hidden = g.guesses_left < 1;

        $('#wa-answer').hidden = !g.can_answer;
        if (g.can_answer) {
            $('#wa-asker').textContent = current ? current.nickname + ' pergunta:' : '';
            $('#wa-question-text').textContent = g.current_question || '';
            $$('#wa-answer-buttons .btn').forEach((b) => {
                b.classList.toggle('is-selected', b.dataset.value === g.my_answer);
                b.disabled = !!g.my_answer;
            });
        }
        const waiting = !g.my_turn && !g.can_answer;
        $('#wa-wait').hidden = !waiting;
        if (waiting) {
            $('#wa-wait-text').textContent = phase === 'answer' ? 'A mesa está respondendo à sua pergunta...' : (current ? current.nickname + ' está pensando...' : 'Aguarde...');
        }
        people($('#wa-people'), g.people, R);
        log($('#wa-log'), g.log, R);
        R.ui.miniPlayers(sec, s.players, { submitted: '✓ respondeu', pending: '...' });
        return 'wa-play';
    }

    function renderReveal(s, R) {
        const g = s.game;
        const sec = $('#wa-reveal');
        local.roundEnd = null;
        $$('[data-round]', sec).forEach((el) => { el.textContent = 'Rodada ' + g.round.number + ' de ' + g.round.total + ' · revelação'; });
        R.ui.setTimer(sec, g.round.started_at, g.round.ends_at);
        people($('#wa-result'), g.people, R);
        R.ui.hostSkip($('#wa-next'), s, 'reveal');
        return 'wa-reveal';
    }

    window.PartyGames.registerGame('quem-sou-eu', {
        phases: { ask: 'Pergunta', answer: 'Respostas', reveal: 'Revelação' },
        init(R) {
            const submit = (formSel, inputSel, op, empty) => {
                $(formSel).addEventListener('submit', async (ev) => {
                    ev.preventDefault();
                    const form = ev.currentTarget;
                    const input = $(inputSel);
                    if (!input.value.trim()) {
                        R.ui.formError(form, empty);
                        return;
                    }
                    const btn = $('button[type="submit"]', form);
                    R.ui.setLoading(btn, true);
                    try {
                        const res = await R.action(op, { text: input.value });
                        if (op === 'guess') R.ui.toast(res.correct ? 'Acertou! Você é ' + res.identity + '!' : 'Não foi dessa vez.');
                        R.pollNow();
                    } catch (e) {
                        R.ui.formError(form, e.message);
                        if (['time_over', 'not_your_turn', 'invalid_state'].indexOf(e.code) !== -1) R.pollNow();
                    } finally {
                        R.ui.setLoading(btn, false);
                    }
                });
            };
            submit('#wa-ask-form', '#wa-question', 'ask', 'Escreva uma pergunta.');
            submit('#wa-guess-form', '#wa-guess', 'guess', 'Escreva quem você acha que é.');
            $$('#wa-answer-buttons .btn').forEach((b) => b.addEventListener('click', async () => {
                $$('#wa-answer-buttons .btn').forEach((x) => { x.disabled = true; });
                b.classList.add('is-selected');
                try {
                    await R.action('answer', { value: b.dataset.value });
                } catch (e) {
                    R.ui.formError($('#wa-answer'), e.message);
                }
                R.pollNow();
            }));
        },
        render(s, prev, R) {
            if (!s.game || !s.game.round) return null;
            return s.room.phase === 'reveal' ? renderReveal(s, R) : renderPlay(s, R);
        },
        // Relógio geral da rodada (o prazo oficial é conferido no servidor).
        onFrame(now) {
            const el = $('#wa-clock');
            if (!el) return;
            if (!local.roundEnd) { el.textContent = ''; return; }
            const left = Math.max(0, Math.ceil((local.roundEnd - now) / 1000));
            el.textContent = 'Fim da rodada em ' + Math.floor(left / 60) + ':' + String(left % 60).padStart(2, '0');
        },
    });
})();
