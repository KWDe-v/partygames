/* "A Bomba Relógio" — interface. Turnos, prazos e validação das respostas ficam no servidor. */
(function () {
    'use strict';
    const $ = (s, r) => (r || document).querySelector(s);
    const $$ = (s, r) => Array.from((r || document).querySelectorAll(s));
    let lastTurn = null;
    let revealed = null;

    function history(list, items, R) {
        list.textContent = '';
        items.forEach((it) => list.appendChild(R.ui.h('li', { class: it.boom ? 'tb-boom' : '' }, [
            R.ui.h('strong', { text: it.nickname }),
            R.ui.h('span', { text: it.boom ? '💥 explodiu!' : it.text }),
        ])));
        if (!items.length) list.appendChild(R.ui.h('li', { class: 'muted', text: 'Ninguém respondeu ainda.' }));
    }

    function renderTurn(s, R) {
        const g = s.game;
        const sec = $('#tb-turn');
        $$('[data-round]', sec).forEach((el) => { el.textContent = 'Rodada ' + g.round.number + ' de ' + g.round.total + ' · vez ' + g.turn; });
        $('#tb-rule').textContent = g.no_repeat ? 'Sem respostas repetidas nesta rodada.' : 'Respostas repetidas valem.';
        R.ui.setTimer(sec, g.round.started_at, g.round.ends_at);
        $('#tb-category').textContent = g.category;
        const holder = g.order.filter((p) => p.current)[0];
        $('#tb-holder').textContent = g.my_turn ? 'A bomba está com VOCÊ!' : (holder ? 'A bomba está com ' + holder.nickname : '');
        const bomb = $('#tb-bomb');
        bomb.classList.toggle('is-mine', g.my_turn);
        if (lastTurn !== g.round.id + ':' + g.turn) {
            const last = g.history[0];
            if (lastTurn !== null && last) R.sfx.play(last.boom ? 'boom' : 'pass');
            if (g.my_turn) R.sfx.play('alarm', last && last.boom ? 0.9 : 0.15);
            lastTurn = g.round.id + ':' + g.turn;
            R.ui.restartAnimation(bomb);
            const input = $('#tb-text');
            input.value = '';
            input.disabled = false;
            R.ui.formError($('#tb-form'), '');
            if (g.my_turn) setTimeout(() => input.focus(), 30);
        }
        $('#tb-answer').hidden = !g.my_turn;
        const ring = $('#tb-ring');
        ring.textContent = '';
        g.order.forEach((p) => ring.appendChild(R.ui.h('li', { class: 'tb-player' + (p.current ? ' is-current' : '') + (p.alive ? '' : ' is-out') }, [
            R.ui.h('span', { text: (p.current ? '💣 ' : '') + p.nickname }),
        ])));
        history($('#tb-history'), g.history, R);
        return 'tb-turn';
    }

    function renderReveal(s, R) {
        const g = s.game;
        const r = g.result || { survivors: [], answers: 0 };
        const sec = $('#tb-reveal');
        $$('[data-round]', sec).forEach((el) => { el.textContent = 'Rodada ' + g.round.number + ' de ' + g.round.total + ' · ' + g.category; });
        R.ui.setTimer(sec, g.round.started_at, g.round.ends_at);
        $('#tb-survivor').textContent = r.survivors.length ? r.survivors.join(', ') : 'Ninguém!';
        $('#tb-summary').textContent = r.answers + ' respostas antes do fim.';
        history($('#tb-final-history'), g.history, R);
        R.ui.hostSkip($('#tb-next'), s, 'reveal');
        if (revealed !== g.round.id) {
            revealed = g.round.id;
            const boom = g.history[0] && g.history[0].boom;
            if (boom) R.sfx.play('boom');
            R.sfx.play('win', boom ? 1.1 : 0);
        }
        return 'tb-reveal';
    }

    window.PartyGames.registerGame('bomba-relogio', {
        phases: { turn: 'Bomba em jogo', reveal: 'Resultado' },
        // Efeitos sonoros por fase (assets/js/sfx.js).
        sounds: { turn: null, reveal: null },
        init(R) {
            $('#tb-form').addEventListener('submit', async (ev) => {
                ev.preventDefault();
                const form = ev.currentTarget;
                const input = $('#tb-text');
                if (!input.value.trim()) {
                    R.ui.formError(form, 'Escreva uma resposta.');
                    return;
                }
                const btn = $('button[type="submit"]', form);
                R.ui.setLoading(btn, true);
                try {
                    await R.action('answer', { text: input.value });
                    R.pollNow();
                } catch (e) {
                    R.ui.formError(form, e.message);
                    input.select();
                    if (['time_over', 'not_your_turn', 'invalid_state'].indexOf(e.code) !== -1) R.pollNow();
                } finally {
                    R.ui.setLoading(btn, false);
                }
            });
        },
        render(s, prev, R) {
            if (!s.game || !s.game.round) return null;
            return s.room.phase === 'reveal' ? renderReveal(s, R) : renderTurn(s, R);
        },
        onDeadline(sec) {
            const input = $('#tb-text', sec);
            if (input && !$('#tb-answer').hidden) input.disabled = true;
        },
    });
})();
