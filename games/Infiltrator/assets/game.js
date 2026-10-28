/* "O Infiltrado" — interface. Papéis e palavra secreta chegam apenas para o próprio jogador. */
(function () {
    'use strict';
    const $ = (s, r) => (r || document).querySelector(s);
    const $$ = (s, r) => Array.from((r || document).querySelectorAll(s));

    function roundLabel(g) {
        return 'Rodada ' + g.round.number + ' de ' + g.round.total + ' · ' + g.category;
    }

    function secretText(g) {
        if (!g.secret) return 'Você está assistindo esta rodada';
        if (g.secret.role === 'infiltrator') return 'Você é o INFILTRADO';
        return 'Sua palavra: ' + (g.secret.word || '?');
    }

    function fillCommon(sec, g, R) {
        $$('[data-round]', sec).forEach((el) => { el.textContent = roundLabel(g); });
        $$('[data-secret]', sec).forEach((el) => { el.textContent = secretText(g); });
        R.ui.setTimer(sec, g.round.started_at, g.round.ends_at);
    }

    function renderRoles(s, R) {
        const g = s.game;
        const sec = $('#if-roles');
        fillCommon(sec, g, R);
        const label = $('#if-role-label');
        const word = $('#if-role-word');
        const note = $('#if-role-note');
        const dossier = $('#if-dossier');
        dossier.classList.toggle('is-infiltrator', !!(g.secret && g.secret.role === 'infiltrator'));
        if (!g.secret) {
            label.textContent = 'Espectador';
            word.textContent = '—';
            note.textContent = 'Você participa a partir da próxima rodada.';
        } else if (g.secret.role === 'infiltrator') {
            label.textContent = 'Você é o';
            word.textContent = 'INFILTRADO';
            note.textContent = 'Você não sabe a palavra. Categoria: ' + g.category + '. Ouça as dicas e tente se misturar!';
        } else {
            label.textContent = 'Sua palavra secreta';
            word.textContent = g.secret.word;
            note.textContent = g.mode === 'similar'
                ? 'Atenção: alguém recebeu uma palavra parecida — e nem sabe disso. Pode ser você!'
                : 'Há ' + g.infiltrators + ' infiltrado(s) sem a palavra. Dê dicas que só quem sabe entenderia.';
        }
        R.ui.hostSkip($('#if-skip-roles'), s, 'roles');
        return 'if-roles';
    }

    function renderHints(s, R) {
        const g = s.game;
        const sec = $('#if-hints');
        fillCommon(sec, g, R);
        const ta = $('#if-hint-text');
        ta.setAttribute('maxlength', String(g.hint_max));
        const fresh = R.ui.preparePhase(g.round.id + ':hint', ta, $('#if-form-hint'));
        $('[data-progress]', sec).textContent = g.hints_done + ' de ' + g.candidates.length + ' dicas enviadas';
        let state = !g.in_round ? 'spectator' : (g.my_hint ? 'submitted' : 'form');
        if (state === 'submitted') $('#if-my-hint').textContent = g.my_hint;
        R.ui.setSubState(sec, state);
        if (state === 'form' && fresh) setTimeout(() => ta.focus(), 50);
        R.ui.miniPlayers(sec, s.players);
        return 'if-hints';
    }

    function renderTalk(s, R) {
        const g = s.game;
        const sec = $('#if-talk');
        fillCommon(sec, g, R);
        const voting = s.room.phase === 'voting';
        $('#if-talk-title').textContent = voting ? 'Votação' : 'Discussão';
        $('[data-progress]', sec).textContent = voting
            ? g.votes_cast + ' de ' + g.candidates.length + ' votaram'
            : 'Conversem: quem deu a dica mais suspeita?';
        const list = $('#if-hint-list');
        list.textContent = '';
        (g.hints || []).forEach((hnt) => list.appendChild(R.ui.h('li', null, [
            R.ui.h('strong', { text: hnt.nickname }),
            R.ui.h('span', { text: hnt.text }),
        ])));
        if (!(g.hints || []).length) list.appendChild(R.ui.h('li', { class: 'muted', text: 'Ninguém enviou dica nesta rodada.' }));
        $('#if-vote-box').hidden = !voting || !g.in_round;
        if (voting) {
            const chosen = g.candidates.filter((c) => c.id === g.my_vote)[0];
            $('#if-vote-title').textContent = chosen ? 'Você votou em ' + chosen.nickname + '. Aguardando...' : (g.infiltrators > 1 ? 'Vote em um dos infiltrados' : 'Quem é o infiltrado?');
            R.ui.choices($('#if-candidates'), g.candidates.map((c) => ({ id: c.id, label: c.nickname, sub: c.me ? 'você' : '', disabled: c.me })), g.my_vote, async (item, btn) => {
                R.ui.setLoading(btn, true);
                try {
                    await R.action('vote', { target: item.id });
                    R.pollNow();
                } catch (e) {
                    R.ui.formError($('#if-vote-box'), e.message);
                    R.ui.setLoading(btn, false);
                }
            });
        }
        const skip = $('#if-skip-talk');
        if (voting) skip.hidden = true;
        else R.ui.hostSkip(skip, s, 'discussion');
        R.ui.miniPlayers(sec, s.players, { submitted: '✓ votou', pending: '...' });
        return 'if-talk';
    }

    function renderReveal(s, R) {
        const g = s.game;
        const r = g.result;
        const sec = $('#if-reveal');
        fillCommon(sec, g, R);
        const out = $('#if-outcome');
        out.classList.toggle('is-group', r.outcome === 'group');
        $('#if-outcome-title').textContent = r.outcome === 'group' ? 'Vitória do grupo!' : 'Vitória dos infiltrados!';
        $('#if-outcome-reason').textContent = r.reason;
        $('#if-word').textContent = r.word;
        $('#if-similar').textContent = r.similar ? ' (infiltrado recebeu: ' + r.similar + ')' : '';
        $('#if-infiltrators').textContent = r.infiltrators.map((x) => x.nickname).join(', ');
        const max = Math.max(1, ...r.counts.map((c) => c.votes));
        const bars = $('#if-bars');
        bars.textContent = '';
        const infIds = r.infiltrators.map((x) => x.id);
        r.counts.forEach((c) => {
            const li = R.ui.h('li', { class: 'bar' + (infIds.indexOf(c.player_id) !== -1 ? ' is-top' : '') }, [
                R.ui.h('strong', { text: c.nickname + (infIds.indexOf(c.player_id) !== -1 ? ' 🕵️' : '') }),
                R.ui.h('span', { text: c.votes + (c.votes === 1 ? ' voto' : ' votos') }),
            ]);
            li.style.setProperty('--w', Math.round((c.votes / max) * 100) + '%');
            bars.appendChild(li);
        });
        R.ui.hostSkip($('#if-next'), s, 'reveal');
        return 'if-reveal';
    }

    window.PartyGames.registerGame('o-infiltrado', {
        phases: { roles: 'Papéis', hints: 'Dicas', discussion: 'Discussão', voting: 'Votação', reveal: 'Revelação' },
        // Efeitos sonoros por fase (assets/js/sfx.js).
        sounds: { roles: 'secret', voting: 'suspense', reveal: 'drumroll' },
        init(R) {
            const ta = $('#if-hint-text');
            R.ui.bindTextarea(ta);
            $('#if-form-hint').addEventListener('submit', (ev) => {
                ev.preventDefault();
                const max = Number(ta.getAttribute('maxlength')) || 40;
                R.ui.submitText(ev.currentTarget, 'hint', 2, max, 'A dica', (data) => {
                    $('#if-my-hint').textContent = data.hint;
                    R.ui.setSubState($('#if-hints'), 'submitted');
                });
            });
        },
        render(s, prev, R) {
            if (!s.game || !s.game.round) return null;
            switch (s.room.phase) {
                case 'roles': return renderRoles(s, R);
                case 'hints': return renderHints(s, R);
                case 'discussion':
                case 'voting': return renderTalk(s, R);
                case 'reveal': return renderReveal(s, R);
                default: return null;
            }
        },
    });
})();
