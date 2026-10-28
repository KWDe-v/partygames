/* "Mentira ou Verdade?" — interface. Autor e marcação secreta só chegam na revelação. */
(function () {
    'use strict';
    const $ = (s, r) => (r || document).querySelector(s);
    const $$ = (s, r) => Array.from((r || document).querySelectorAll(s));

    function common(sec, g, R) {
        $$('[data-round]', sec).forEach((el) => { el.textContent = 'Rodada ' + g.round.number + ' de ' + g.round.total + ' · ' + g.category; });
        $$('[data-theme]', sec).forEach((el) => { el.textContent = g.theme; });
        R.ui.setTimer(sec, g.round.started_at, g.round.ends_at);
    }

    function renderWrite(s, R) {
        const g = s.game;
        const sec = $('#tl-write');
        common(sec, g, R);
        const form = $('#tl-form');
        const ta = $('#tl-text');
        ta.setAttribute('maxlength', String(g.max_len));
        if (R.ui.preparePhase(g.round.id + ':st', ta, form)) {
            $$('input[name="truth"]', form).forEach((r) => { r.checked = false; });
            setTimeout(() => ta.focus(), 50);
        }
        $('#tl-example').textContent = g.example ? 'Exemplo: ' + g.example : '';
        $('[data-progress]', sec).textContent = g.written + ' afirmação(ões) enviada(s)';
        const state = !g.in_round ? 'spectator' : (g.mine ? 'submitted' : 'form');
        if (g.mine) {
            $('#tl-mine').textContent = g.mine.text;
            $('#tl-mine-truth').textContent = 'Você marcou como ' + (g.mine.truth ? 'VERDADE' : 'MENTIRA') + '. Só você sabe disso.';
        }
        R.ui.setSubState(sec, state);
        R.ui.miniPlayers(sec, s.players);
        return 'tl-write';
    }

    function renderVote(s, R) {
        const g = s.game;
        const sec = $('#tl-vote');
        common(sec, g, R);
        const items = g.statements || [];
        const others = items.filter((x) => !x.mine);
        const done = others.filter((x) => x.my_vote !== null).length;
        $('[data-progress]', sec).textContent = g.excluded ? '' : 'Você votou em ' + done + ' de ' + others.length;
        $('#tl-excluded').hidden = !g.excluded;
        const list = $('#tl-statements');
        list.textContent = '';
        items.forEach((st) => {
            const vote = async (value, btn) => {
                R.ui.setLoading(btn, true);
                try {
                    await R.action('vote', { code: st.code, value: value });
                    R.pollNow();
                } catch (e) {
                    R.ui.formError(sec, e.message);
                    R.ui.setLoading(btn, false);
                }
            };
            const btnT = R.ui.h('button', { type: 'button', class: 'tl-btn tl-btn--true' + (st.my_vote === true ? ' is-selected' : ''), text: 'Verdade' });
            const btnF = R.ui.h('button', { type: 'button', class: 'tl-btn tl-btn--false' + (st.my_vote === false ? ' is-selected' : ''), text: 'Mentira' });
            const locked = st.mine || st.my_vote !== null || g.excluded;
            btnT.disabled = locked;
            btnF.disabled = locked;
            btnT.addEventListener('click', () => vote(true, btnT));
            btnF.addEventListener('click', () => vote(false, btnF));
            list.appendChild(R.ui.h('li', { class: 'tl-card' + (st.mine ? ' is-mine' : '') }, [
                R.ui.h('p', { class: 'tl-card__text', text: '“' + st.text + '”' }),
                st.mine
                    ? R.ui.h('p', { class: 'muted', text: 'Sua afirmação — você não vota nela.' })
                    : R.ui.h('div', { class: 'tl-card__actions' }, [btnT, btnF]),
            ]));
        });
        R.ui.miniPlayers(sec, s.players, { submitted: '✓', pending: '...', excluded: 'fora' });
        return 'tl-vote';
    }

    function renderReveal(s, R) {
        const g = s.game;
        const sec = $('#tl-reveal');
        common(sec, g, R);
        const list = $('#tl-results');
        list.textContent = '';
        ((g.result && g.result.items) || []).forEach((it, i) => {
            const li = R.ui.h('li', { class: 'tl-card tl-card--reveal' }, [
                R.ui.h('span', { class: 'tl-stamp ' + (it.truth ? 'tl-stamp--true' : 'tl-stamp--false'), text: it.truth ? 'Verdade' : 'Mentira' }),
                R.ui.h('p', { class: 'tl-card__text', text: '“' + it.text + '”' }),
                R.ui.h('p', { class: 'tl-card__meta', text: 'por ' + it.author + ' · ' + it.votes_true + ' acharam verdade, ' + it.votes_false + ' acharam mentira' }),
                it.fooled ? R.ui.h('p', { class: 'tl-fooled', text: 'Enganou a maioria!' }) : null,
            ]);
            li.style.animationDelay = (i * 0.15) + 's';
            list.appendChild(li);
        });
        R.ui.hostSkip($('#tl-next'), s, 'reveal');
        return 'tl-reveal';
    }

    window.PartyGames.registerGame('mentira-ou-verdade', {
        phases: { writing: 'Escrita', voting: 'Votação', reveal: 'Revelação' },
        // Efeitos sonoros por fase (assets/js/sfx.js).
        sounds: { writing: 'question', reveal: 'drumroll' },
        init(R) {
            const form = $('#tl-form');
            const ta = $('#tl-text');
            R.ui.bindTextarea(ta);
            form.addEventListener('submit', async (ev) => {
                ev.preventDefault();
                const picked = $('input[name="truth"]:checked', form);
                if (!picked) {
                    R.ui.formError(form, 'Marque se a afirmação é verdade ou mentira.');
                    return;
                }
                const btn = $('button[type="submit"]', form);
                R.ui.formError(form, '');
                R.ui.setLoading(btn, true);
                try {
                    await R.action('statement', { text: ta.value, truth: picked.value === '1' });
                    R.drafts.clear(R.state().game.round.id + ':st');
                    R.pollNow();
                } catch (e) {
                    R.ui.formError(form, e.message);
                    if (e.code === 'time_over') R.pollNow();
                } finally {
                    R.ui.setLoading(btn, false);
                }
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
