/* "O Tribunal" — interface. Papéis secretos, verdade e votos ficam no servidor. */
(function () {
    'use strict';
    const $ = (s, r) => (r || document).querySelector(s);
    const $$ = (s, r) => Array.from((r || document).querySelectorAll(s));
    const STAGE = {
        briefing: ['Sessão aberta', 'Leia seu papel e o caso.'],
        accusation: ['Acusação', 'A promotoria apresenta o caso.'],
        defense: ['Defesa', 'A defesa responde à acusação.'],
        testimony: ['Depoimentos', 'As testemunhas contam o que viram.'],
        final: ['Palavra do réu', 'O réu fala pela última vez.'],
        voting: ['Votação do júri', 'Jurados e testemunhas decidem em segredo.'],
    };

    function speeches(list, items, R) {
        list.textContent = '';
        items.forEach((sp) => list.appendChild(R.ui.h('li', { class: 'tr-speech tr-speech--' + sp.stage }, [
            R.ui.h('strong', { text: sp.role + ' · ' + sp.nickname }),
            R.ui.h('p', { text: '“' + sp.text + '”' }),
        ])));
    }

    function common(sec, g, R) {
        $$('[data-round]', sec).forEach((el) => { el.textContent = 'Caso ' + g.round.number + ' de ' + g.round.total; });
        $$('[data-title]', sec).forEach((el) => { el.textContent = g.title; });
        $$('[data-charge]', sec).forEach((el) => { el.textContent = g.charge; });
        R.ui.setTimer(sec, g.round.started_at, g.round.ends_at);
    }

    function renderCourt(s, R) {
        const g = s.game;
        const phase = s.room.phase;
        const sec = $('#tr-court');
        common(sec, g, R);
        const st = STAGE[phase] || ['', ''];
        $('#tr-stage').textContent = st[0];
        $('[data-progress]', sec).textContent = phase === 'voting' ? g.votes_cast + ' de ' + g.voters + ' votaram' : st[1];

        const role = $('#tr-role');
        role.textContent = '';
        if (g.my_role) {
            role.appendChild(R.ui.h('p', { class: 'tr-role__label', text: 'Seu papel' }));
            role.appendChild(R.ui.h('p', { class: 'tr-role__name', text: g.my_role.name }));
            g.my_role.info.forEach((line) => role.appendChild(R.ui.h('p', { class: 'tr-role__info', text: line })));
        }

        const speak = $('#tr-speak');
        speak.hidden = !g.my_turn;
        if (g.my_turn) {
            const ta = $('#tr-text');
            ta.setAttribute('maxlength', String(g.max_len));
            if (R.ui.preparePhase(g.round.id + ':' + phase, ta, $('#tr-form'))) setTimeout(() => ta.focus(), 50);
            $('#tr-speak-label').textContent = 'Sua fala — ' + st[0];
        }
        const vote = $('#tr-vote');
        vote.hidden = !(phase === 'voting' && g.can_vote);
        $('#tr-vote-title').textContent = g.my_vote ? 'Voto registrado: ' + (g.my_vote === 'guilty' ? 'culpado' : 'inocente') + '. Aguardando...' : 'O réu é...';
        $$('.tr-vote__btn', vote).forEach((b) => {
            b.disabled = !!g.my_vote;
            b.classList.toggle('is-selected', !!g.my_vote && (b.dataset.guilty === '1') === (g.my_vote === 'guilty'));
        });

        speeches($('#tr-speeches'), g.speeches, R);
        const cast = $('#tr-cast');
        cast.textContent = '';
        g.cast.forEach((c) => cast.appendChild(R.ui.h('li', { class: 'tr-cast__item tr-cast__item--' + c.role }, [
            R.ui.h('strong', { text: c.nickname }), R.ui.h('span', { text: c.role_name }),
        ])));
        const skip = $('#tr-skip-brief');
        if (phase === 'briefing') R.ui.hostSkip(skip, s, 'briefing');
        else skip.hidden = true;
        return 'tr-court';
    }

    function renderReveal(s, R) {
        const g = s.game;
        const r = g.result;
        const sec = $('#tr-reveal');
        common(sec, g, R);
        $('#tr-verdict').classList.toggle('is-guilty', r.verdict === 'culpado');
        $('#tr-verdict-word').textContent = r.verdict === 'culpado' ? 'CULPADO' : 'INOCENTE';
        $('#tr-verdict-count').textContent = r.guilty + ' voto(s) culpado · ' + r.innocent + ' voto(s) inocente' + (r.by_judge ? ' · desempate do juiz' : '');
        $('#tr-verdict-truth').textContent = 'A verdade: ' + r.reu + ' era ' + r.truth + '.';
        speeches($('#tr-reveal-speeches'), g.speeches, R);
        R.ui.hostSkip($('#tr-next'), s, 'reveal');
        return 'tr-reveal';
    }

    window.PartyGames.registerGame('o-tribunal', {
        phases: { briefing: 'Sessão aberta', accusation: 'Acusação', defense: 'Defesa', testimony: 'Depoimentos', final: 'Palavra do réu', voting: 'Júri', reveal: 'Veredito' },
        init(R) {
            const ta = $('#tr-text');
            R.ui.bindTextarea(ta);
            $('#tr-form').addEventListener('submit', (ev) => {
                ev.preventDefault();
                const g = R.state().game;
                R.ui.submitText(ev.currentTarget, 'speak', g.min_len, g.max_len, 'A fala', () => {
                    $('#tr-speak').hidden = true;
                    R.ui.toast('Fala registrada!');
                });
            });
            $$('.tr-vote__btn').forEach((btn) => btn.addEventListener('click', async () => {
                R.ui.setLoading(btn, true);
                try {
                    await R.action('vote', { guilty: btn.dataset.guilty === '1' });
                    R.pollNow();
                } catch (e) {
                    R.ui.formError($('#tr-vote'), e.message);
                } finally {
                    R.ui.setLoading(btn, false);
                }
            }));
        },
        render(s, prev, R) {
            if (!s.game || !s.game.round) return null;
            return s.room.phase === 'reveal' ? renderReveal(s, R) : renderCourt(s, R);
        },
    });
})();
