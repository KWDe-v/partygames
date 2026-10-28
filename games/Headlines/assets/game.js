/* "Manchete Absurda" — interface. Autores só chegam depois da votação. */
(function () {
    'use strict';
    const $ = (s, r) => (r || document).querySelector(s);
    const $$ = (s, r) => Array.from((r || document).querySelectorAll(s));

    function common(sec, g, R) {
        $$('[data-round]', sec).forEach((el) => { el.textContent = 'Edição ' + g.round.number + ' de ' + g.round.total; });
        $$('[data-theme]', sec).forEach((el) => { el.textContent = g.theme; });
        $$('[data-section]', sec).forEach((el) => { el.textContent = g.section; });
        if (g.round.ends_at) R.ui.setTimer(sec, g.round.started_at, g.round.ends_at);
        else R.ui.setTimer(sec, null, null);
    }

    function renderWrite(s, R) {
        const g = s.game;
        const sec = $('#hl-write');
        common(sec, g, R);
        const ta = $('#hl-text');
        ta.setAttribute('maxlength', String(g.max_len));
        if (R.ui.preparePhase(g.round.id + ':hl', ta, $('#hl-form'))) setTimeout(() => ta.focus(), 50);
        $('#hl-example').textContent = g.example ? 'Exemplo: "' + g.example + '"' : '';
        $('[data-progress]', sec).textContent = g.written + ' manchete(s) na redação';
        const state = !g.in_round ? 'spectator' : (g.my_headline ? 'submitted' : 'form');
        if (g.my_headline) $('#hl-mine').textContent = g.my_headline;
        R.ui.setSubState(sec, state);
        R.ui.miniPlayers(sec, s.players);
        return 'hl-write';
    }

    function renderVote(s, R) {
        const g = s.game;
        const sec = $('#hl-vote');
        common(sec, g, R);
        $('[data-progress]', sec).textContent = g.votes_cast + ' voto(s)';
        $('#hl-vote-hint').textContent = g.my_vote ? 'Voto registrado! Aguardando os outros...' : 'Vote na manchete mais engraçada (não vale a sua).';
        R.ui.choices($('#hl-headlines'), (g.headlines || []).map((x) => ({
            id: x.code, label: x.text, sub: x.mine ? 'sua manchete' : '', mine: x.mine, disabled: x.mine || !g.in_round,
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
        return 'hl-vote';
    }

    function renderReveal(s, R) {
        const g = s.game;
        const r = g.result || { items: [], tie: false, total_votes: 0 };
        const sec = $('#hl-reveal');
        common(sec, g, R);
        const fronts = r.items.filter((x) => x.front);
        $('#hl-front-label').textContent = !fronts.length ? 'Sem capa: ninguém votou' : (r.tie ? 'Empate! Capa dividida' : 'Capa de hoje');
        const front = $('#hl-front-headlines');
        front.textContent = '';
        fronts.forEach((x) => {
            front.appendChild(R.ui.h('p', { class: 'hl-front__headline', text: x.text }));
            front.appendChild(R.ui.h('p', { class: 'hl-front__byline', text: 'Por ' + x.author + ' · ' + x.votes + ' voto(s)' }));
        });
        const list = $('#hl-ranking');
        list.textContent = '';
        r.items.forEach((x) => list.appendChild(R.ui.h('li', { class: x.front ? 'is-front' : '' }, [
            R.ui.h('span', { class: 'hl-ranking__pos', text: x.position + 'º' }),
            R.ui.h('div', null, [R.ui.h('strong', { text: x.text }), R.ui.h('span', { text: x.author + ' · ' + x.votes + ' voto(s)' })]),
        ])));
        const next = $('#hl-next');
        R.ui.hostSkip(next, s, 'reveal');
        return 'hl-reveal';
    }

    window.PartyGames.registerGame('manchete-absurda', {
        phases: { writing: 'Redação', voting: 'Votação', reveal: 'Capa' },
        init(R) {
            R.ui.bindTextarea($('#hl-text'));
            $('#hl-form').addEventListener('submit', (ev) => {
                ev.preventDefault();
                const g = R.state().game;
                R.ui.submitText(ev.currentTarget, 'headline', g.min_len, g.max_len, 'A manchete', (data) => {
                    $('#hl-mine').textContent = data.headline;
                    R.ui.setSubState($('#hl-write'), 'submitted');
                });
            });
        },
        render(s, prev, R) {
            if (!s.game || !s.game.round) return null;
            switch (s.room.phase) {
                case 'writing': return renderWrite(s, R);
                case 'voting': return renderVote(s, R);
                case 'reveal': return renderReveal(s, R);
                default: return null;
            }
        },
    });
})();
