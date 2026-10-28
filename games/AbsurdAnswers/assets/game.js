/* "A Resposta Mais Absurdamente Errada" — interface. Autores só chegam depois da votação. */
(function () {
    'use strict';
    const $ = (s, r) => (r || document).querySelector(s);
    const $$ = (s, r) => Array.from((r || document).querySelectorAll(s));

    function common(sec, g, R) {
        $$('[data-round]', sec).forEach((el) => { el.textContent = 'Pergunta ' + g.round.number + ' de ' + g.round.total + ' · ' + g.category; });
        $$('[data-question]', sec).forEach((el) => { el.textContent = g.question; });
        if (g.round.ends_at) R.ui.setTimer(sec, g.round.started_at, g.round.ends_at);
        else R.ui.setTimer(sec, null, null);
    }

    function renderAnswer(s, R) {
        const g = s.game;
        const sec = $('#ab-answer');
        common(sec, g, R);
        const ta = $('#ab-text');
        ta.setAttribute('maxlength', String(g.max_len));
        if (R.ui.preparePhase(g.round.id + ':ab', ta, $('#ab-form'))) setTimeout(() => ta.focus(), 50);
        $('[data-progress]', sec).textContent = g.answered + ' resposta(s) enviada(s)';
        const state = !g.in_round ? 'spectator' : (g.my_answer ? 'submitted' : 'form');
        if (g.my_answer) $('#ab-mine').textContent = g.my_answer;
        R.ui.setSubState(sec, state);
        R.ui.miniPlayers(sec, s.players);
        return 'ab-answer';
    }

    function renderVote(s, R) {
        const g = s.game;
        const sec = $('#ab-vote');
        common(sec, g, R);
        $('[data-progress]', sec).textContent = g.votes_cast + ' voto(s)';
        $('#ab-vote-hint').textContent = g.my_vote ? 'Voto registrado! Aguardando os outros...' : (g.in_round ? 'Vote na resposta mais absurda (não vale a sua).' : 'Você entra na próxima pergunta.');
        R.ui.choices($('#ab-answers'), (g.answers || []).map((a) => ({
            id: a.code,
            label: a.text,
            sub: a.mine ? 'sua resposta' : '',
            mine: a.mine,
            disabled: a.mine || !g.in_round,
        })), g.my_vote, async (item, btn) => {
            R.ui.setLoading(btn, true);
            try {
                await R.action('vote', { code: item.id });
                R.pollNow();
            } catch (e) {
                R.ui.formError(sec, e.message);
                R.ui.setLoading(btn, false);
            }
        });
        R.ui.miniPlayers(sec, s.players, { submitted: '✓ votou', pending: '...' });
        return 'ab-vote';
    }

    function renderReveal(s, R) {
        const g = s.game;
        const r = g.result || { items: [], total_votes: 0 };
        const sec = $('#ab-reveal');
        common(sec, g, R);
        $('#ab-reveal-sub').textContent = r.total_votes ? r.total_votes + ' voto(s)' : 'Ninguém votou nesta rodada.';
        const list = $('#ab-results');
        list.textContent = '';
        r.items.forEach((it, i) => {
            const li = R.ui.h('li', { class: 'ab-result' + (it.winner ? ' is-winner' : '') }, [
                it.winner ? R.ui.h('span', { class: 'ab-trophy', text: '🏆' }) : null,
                R.ui.h('p', { class: 'ab-result__text', text: it.text }),
                R.ui.h('p', { class: 'ab-result__meta', text: it.author + ' · ' + it.votes + (it.votes === 1 ? ' voto' : ' votos') }),
            ]);
            li.style.animationDelay = (i * 0.12) + 's';
            list.appendChild(li);
        });
        R.ui.hostSkip($('#ab-next'), s, 'reveal');
        const next = $('#ab-next');
        if (g.manual && !s.me.host) next.hidden = true;
        return 'ab-reveal';
    }

    window.PartyGames.registerGame('resposta-absurda', {
        phases: { answering: 'Respostas', voting: 'Votação', reveal: 'Resultado' },
        init(R) {
            const ta = $('#ab-text');
            R.ui.bindTextarea(ta);
            $('#ab-form').addEventListener('submit', (ev) => {
                ev.preventDefault();
                const g = R.state().game;
                R.ui.submitText(ev.currentTarget, 'answer', g.min_len, g.max_len, 'A resposta', (data) => {
                    $('#ab-mine').textContent = data.answer;
                    R.ui.setSubState($('#ab-answer'), 'submitted');
                });
            });
        },
        render(s, prev, R) {
            if (!s.game || !s.game.round) return null;
            switch (s.room.phase) {
                case 'answering': return renderAnswer(s, R);
                case 'voting': return renderVote(s, R);
                case 'reveal': return renderReveal(s, R);
                default: return null;
            }
        },
    });
})();
