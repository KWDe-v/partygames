/* "Quem é Mais Provável?" — interface. Regras e contagem ficam no servidor. */
(function () {
    'use strict';
    const $ = (s, r) => (r || document).querySelector(s);

    function roundLabel(g) {
        return 'Pergunta ' + g.round.number + ' de ' + g.round.total + ' · ' + g.category;
    }

    function renderVote(s, R) {
        const g = s.game;
        const ui = R.ui;
        const sec = $('#ml-vote');
        $('[data-round]', sec).textContent = roundLabel(g);
        $('[data-progress]', sec).textContent = g.votes_cast + ' de ' + g.voters + ' votaram';
        ui.setTimer(sec, g.round.started_at, g.round.ends_at);
        $('#ml-question').textContent = g.question;
        $('.ml-question', sec).hidden = !g.in_round;
        $('#ml-spectator').hidden = g.in_round;
        const voted = g.candidates.filter((c) => c.id === g.my_vote)[0];
        $('#ml-hint').textContent = voted
            ? 'Voto registrado em ' + voted.nickname + '. Aguardando os outros...'
            : 'Escolha em segredo. Ninguém vai saber em quem você votou.';
        ui.choices($('#ml-candidates'), g.candidates.map((c) => ({
            id: c.id,
            label: c.nickname,
            sub: c.me ? 'você' : (c.active ? '' : 'saiu da sala'),
            disabled: c.me || !c.active,
        })), g.my_vote, async (item, btn) => {
            ui.setLoading(btn, true);
            try {
                await R.action('vote', { target: item.id });
                R.pollNow();
            } catch (e) {
                ui.formError(sec, e.message);
                ui.setLoading(btn, false);
            }
        });
        ui.miniPlayers(sec, s.players, { submitted: '✓ votou', pending: '...' });
        return 'ml-vote';
    }

    function renderReveal(s, R) {
        const g = s.game;
        const r = g.result || { counts: [], leaders: [], total_votes: 0 };
        const sec = $('#ml-reveal');
        $('[data-round]', sec).textContent = roundLabel(g);
        $('#ml-reveal-sub').textContent = r.total_votes + (r.total_votes === 1 ? ' voto' : ' votos');
        R.ui.setTimer(sec, g.round.started_at, g.round.ends_at);
        $('#ml-reveal-question').textContent = g.question;
        const leaders = r.counts.filter((c) => r.leaders.indexOf(c.player_id) !== -1);
        const winner = $('#ml-winner');
        winner.textContent = '';
        if (!leaders.length) {
            winner.appendChild(R.ui.h('p', { class: 'ml-none', text: 'Ninguém votou nesta rodada.' }));
        } else {
            winner.appendChild(R.ui.h('p', { class: 'ml-crown', text: leaders.length > 1 ? 'Empate!' : '👑' }));
            winner.appendChild(R.ui.h('p', { class: 'ml-names', text: leaders.map((c) => c.nickname).join(' · ') }));
            if (r.points) winner.appendChild(R.ui.h('p', { class: 'muted', text: '+' + r.points + (r.points === 1 ? ' ponto' : ' pontos') + (leaders.length > 1 ? ' para cada' : '') }));
        }
        const max = Math.max(1, ...r.counts.map((c) => c.votes));
        const bars = $('#ml-bars');
        bars.textContent = '';
        r.counts.forEach((c) => {
            const li = R.ui.h('li', { class: 'bar' + (r.leaders.indexOf(c.player_id) !== -1 ? ' is-top' : '') }, [
                R.ui.h('strong', { text: c.nickname }),
                R.ui.h('span', { text: c.votes + (c.votes === 1 ? ' voto' : ' votos') }),
            ]);
            li.style.setProperty('--w', Math.round((c.votes / max) * 100) + '%');
            bars.appendChild(li);
        });
        R.ui.hostSkip($('#ml-next'), s, 'reveal');
        return 'ml-reveal';
    }

    window.PartyGames.registerGame('quem-e-mais-provavel', {
        phases: { voting: 'Votação', reveal: 'Resultado' },
        render(s, prev, R) {
            if (!s.game || !s.game.round) return null;
            return s.room.phase === 'reveal' ? renderReveal(s, R) : renderVote(s, R);
        },
    });
})();
