/* "Quem Cometeu o Crime?" — interface. O servidor só envia as pistas e respostas a que o jogador tem acesso. */
(function () {
    'use strict';
    const $ = (s, r) => (r || document).querySelector(s);
    const $$ = (s, r) => Array.from((r || document).querySelectorAll(s));
    const local = { suspect: null, pickedSuspect: null, seenClues: {}, nextClueAt: null };

    function h(R, tag, attrs, children) { return R.ui.h(tag, attrs, children); }

    function common(sec, g, R) {
        $$('[data-title]', sec).forEach((el) => { el.textContent = g.case.title; });
        $$('[data-difficulty]', sec).forEach((el) => { el.textContent = g.case.difficulty; });
        R.ui.setTimer(sec, g.round.started_at, g.round.ends_at);
    }

    function suspects(list, g, R) {
        list.textContent = '';
        g.case.suspects.forEach((s) => list.appendChild(h(R, 'li', { class: 'cr-suspect' }, [
            h(R, 'strong', { text: s.name }),
            h(R, 'span', { class: 'cr-suspect__role', text: s.role }),
            h(R, 'p', { text: s.profile }),
            h(R, 'p', { class: 'cr-suspect__meta', text: 'Possível motivo: ' + s.motive }),
            h(R, 'p', { class: 'cr-suspect__meta', text: 'Álibi: ' + s.alibi }),
        ])));
    }

    function clues(list, items, R) {
        list.textContent = '';
        items.forEach((c) => {
            const isNew = !local.seenClues[c.id];
            local.seenClues[c.id] = true;
            list.appendChild(h(R, 'li', { class: 'cr-clue' + (c.private ? ' is-private' : '') + (isNew ? ' is-new' : '') }, [
                h(R, 'strong', { text: c.title }),
                c.private ? h(R, 'span', { class: 'cr-tag', text: 'só você vê' }) : null,
                h(R, 'p', { text: c.text }),
            ]));
        });
        if (!items.length) list.appendChild(h(R, 'li', { class: 'muted', text: 'Nenhuma pista ainda.' }));
    }

    function renderBrief(s, R) {
        const g = s.game;
        const sec = $('#cr-brief');
        common(sec, g, R);
        $('[data-progress]', sec).textContent = g.ready_count + ' de ' + g.team.length + ' prontos';
        $('#cr-intro').textContent = g.case.intro;
        const facts = $('#cr-facts');
        facts.textContent = '';
        [['O crime', g.case.crime], ['Vítima', g.case.victim], ['Local', g.case.location], ['Horário', g.case.time]].forEach((f) => {
            facts.appendChild(h(R, 'div', null, [h(R, 'dt', { text: f[0] }), h(R, 'dd', { text: f[1] })]));
        });
        const role = $('#cr-role');
        role.textContent = '';
        if (g.my_role) {
            role.appendChild(h(R, 'p', { class: 'cr-role__label', text: 'Seu papel' }));
            role.appendChild(h(R, 'p', { class: 'cr-role__name', text: g.my_role.name }));
            role.appendChild(h(R, 'p', { text: g.my_role.description }));
        }
        suspects($('[data-suspects]', sec), g, R);
        const team = $('[data-team]', sec);
        team.textContent = '';
        g.team.forEach((t) => team.appendChild(h(R, 'li', null, [h(R, 'strong', { text: t.nickname }), h(R, 'span', { text: t.role })])));
        const ready = $('#cr-ready-brief');
        ready.disabled = g.ready || !g.my_role;
        ready.textContent = g.ready ? 'Pronto! Aguardando os outros...' : 'Li o caso, estou pronto';
        R.ui.hostSkip($('#cr-skip-brief'), s, 'briefing');
        return 'cr-brief';
    }

    function renderInvestigate(s, R) {
        const g = s.game;
        const sec = $('#cr-investigate');
        common(sec, g, R);
        $('[data-progress]', sec).textContent = g.ready_count + ' de ' + g.team.length + ' prontos para acusar';
        clues($('[data-clues]', sec), g.clues || [], R);
        $('#cr-clue-count').textContent = String((g.clues || []).length);
        local.nextClueAt = g.next_clue_at;
        $('#cr-actions').textContent = g.actions_left + '/' + g.actions_total;
        $('#cr-role-line').textContent = g.my_role ? 'Você é ' + g.my_role.name + '. Cada pergunta ou busca gasta 1 ação.' : '';

        // Interrogatório: perguntas disponíveis para o suspeito escolhido.
        const select = $('#cr-suspect-select');
        if (!select.options.length || select.dataset.case !== g.case.title) {
            select.textContent = '';
            g.case.suspects.forEach((sp) => select.appendChild(h(R, 'option', { value: sp.id, text: sp.name + ' — ' + sp.role })));
            select.dataset.case = g.case.title;
            local.suspect = g.case.suspects[0].id;
        }
        select.value = local.suspect;
        const qs = (g.questions || []).filter((q) => q.suspect === local.suspect);
        R.ui.choices($('#cr-questions'), qs.map((q) => ({
            id: q.id,
            label: q.question,
            sub: q.special ? 'pergunta exclusiva do seu papel' : '',
            disabled: g.actions_left < 1,
        })), null, async (item, btn) => {
            R.ui.setLoading(btn, true);
            try {
                const data = await R.action('ask', { suspect: local.suspect, question: item.id });
                if (data.unlocked) R.ui.toast('Nova pista para todos: ' + data.unlocked.title);
                R.refresh();
            } catch (e) {
                R.ui.formError(sec, e.message);
                R.ui.setLoading(btn, false);
            }
        });
        if (!qs.length) $('#cr-questions').appendChild(h(R, 'li', { class: 'muted', text: 'Nenhuma pergunta disponível para este suspeito agora.' }));

        R.ui.choices($('#cr-locations'), (g.locations || []).map((l) => ({
            id: l.id,
            label: l.name,
            sub: l.searched ? 'já vasculhado' : '',
            disabled: l.searched || g.actions_left < 1,
        })), null, async (item, btn) => {
            R.ui.setLoading(btn, true);
            try {
                const data = await R.action('search', { location: item.id });
                R.ui.toast('Você encontrou: ' + data.clue.title);
                R.refresh();
            } catch (e) {
                R.ui.formError(sec, e.message);
                R.ui.setLoading(btn, false);
            }
        });

        const log = $('#cr-log');
        log.textContent = '';
        (g.interrogations || []).forEach((it) => log.appendChild(h(R, 'li', null, [
            h(R, 'strong', { text: it.suspect + ': ' + it.question }),
            h(R, 'p', { text: '“' + it.answer + '”' }),
        ])));
        if (!(g.interrogations || []).length) log.appendChild(h(R, 'li', { class: 'muted', text: 'Escolha um suspeito e uma pergunta.' }));

        suspects($('[data-suspects]', sec), g, R);
        const ready = $('#cr-ready-accuse');
        ready.disabled = g.ready;
        ready.textContent = g.ready ? 'Pronto! Aguardando os outros...' : 'Estou pronto para acusar';
        R.ui.hostSkip($('#cr-skip-investigation'), s, 'investigation');
        R.ui.miniPlayers(sec, s.players, { submitted: '✓ pronto', pending: 'investigando' });
        return 'cr-investigate';
    }

    function renderAccuse(s, R) {
        const g = s.game;
        const sec = $('#cr-accuse');
        common(sec, g, R);
        $('[data-progress]', sec).textContent = g.accused_count + ' de ' + g.team.length + ' acusaram';
        clues($('[data-clues]', sec), g.clues || [], R);
        if (g.my_accusation) {
            $('#cr-my-accusation').textContent = g.my_accusation.suspect + ' — ' + g.my_accusation.motive;
            R.ui.setSubState(sec, 'submitted');
        } else {
            R.ui.setSubState(sec, 'form');
            const pick = $('#cr-pick-suspect');
            if (!pick.children.length || pick.dataset.round !== String(g.round.id)) {
                pick.dataset.round = String(g.round.id);
                pick.textContent = '';
                local.pickedSuspect = null;
                g.case.suspects.forEach((sp) => {
                    const btn = h(R, 'button', { type: 'button', class: 'cr-pick__item' }, [
                        h(R, 'strong', { text: sp.name }), h(R, 'span', { text: sp.role }),
                    ]);
                    btn.addEventListener('click', () => {
                        local.pickedSuspect = sp.id;
                        $$('.cr-pick__item', pick).forEach((b) => b.classList.toggle('is-selected', b === btn));
                    });
                    pick.appendChild(btn);
                });
                const motive = $('#cr-motive');
                motive.textContent = '';
                motive.appendChild(h(R, 'option', { value: '', text: 'Escolha o motivo...' }));
                (g.motives || []).forEach((m) => motive.appendChild(h(R, 'option', { value: m.id, text: m.label })));
            }
        }
        R.ui.miniPlayers(sec, s.players, { submitted: '✓ acusou', pending: '...' });
        return 'cr-accuse';
    }

    function renderReveal(s, R) {
        const g = s.game;
        const r = g.result;
        const sec = $('#cr-reveal');
        common(sec, g, R);
        $('#cr-culprit').textContent = r.culprit.name;
        $('#cr-motive-text').textContent = 'Motivo: ' + r.motive;
        $('#cr-explanation').textContent = r.explanation;
        clues($('#cr-key-clues'), r.key_clues, R);
        const list = $('#cr-accusations');
        list.textContent = '';
        r.accusations.forEach((a) => list.appendChild(h(R, 'li', { class: a.correct ? 'is-right' : 'is-wrong' }, [
            h(R, 'strong', { text: (a.correct ? '✔ ' : '✘ ') + a.nickname }),
            h(R, 'span', { text: 'acusou ' + a.suspect + ' — ' + a.motive + (a.motive_correct ? ' (motivo certo!)' : '') }),
        ])));
        if (!r.accusations.length) list.appendChild(h(R, 'li', { class: 'muted', text: 'Ninguém fez uma acusação.' }));
        R.ui.hostSkip($('#cr-end'), s, 'reveal');
        return 'cr-reveal';
    }

    window.PartyGames.registerGame('quem-cometeu-o-crime', {
        phases: { briefing: 'Leitura do caso', investigation: 'Investigação', accusation: 'Acusação', reveal: 'Solução' },
        init(R) {
            const ready = async (btn) => {
                R.ui.setLoading(btn, true);
                try {
                    await R.action('ready');
                    R.pollNow();
                } catch (e) {
                    R.ui.toast(e.message, 'error');
                } finally {
                    R.ui.setLoading(btn, false);
                }
            };
            $('#cr-ready-brief').addEventListener('click', (ev) => ready(ev.currentTarget));
            $('#cr-ready-accuse').addEventListener('click', (ev) => ready(ev.currentTarget));
            $('#cr-suspect-select').addEventListener('change', (ev) => {
                local.suspect = ev.target.value;
                const s = R.state();
                if (s && s.room.phase === 'investigation') renderInvestigate(s, R);
            });
            $('#cr-accuse-form').addEventListener('submit', async (ev) => {
                ev.preventDefault();
                const form = ev.currentTarget;
                const motive = $('#cr-motive').value;
                if (!local.pickedSuspect || !motive) {
                    R.ui.formError(form, 'Escolha o suspeito e o motivo.');
                    return;
                }
                if (!(await R.ui.confirm('Confirmar a acusação? Ela não poderá ser alterada.', 'Acusar'))) return;
                const btn = $('button[type="submit"]', form);
                R.ui.setLoading(btn, true);
                try {
                    await R.action('accuse', { suspect: local.pickedSuspect, motive: motive });
                    R.pollNow();
                } catch (e) {
                    R.ui.formError(form, e.message);
                } finally {
                    R.ui.setLoading(btn, false);
                }
            });
        },
        render(s, prev, R) {
            if (!s.game || !s.game.round) return null;
            switch (s.room.phase) {
                case 'briefing': return renderBrief(s, R);
                case 'investigation': return renderInvestigate(s, R);
                case 'accusation': return renderAccuse(s, R);
                case 'reveal': return renderReveal(s, R);
                default: return null;
            }
        },
        // Contagem até a próxima pista; quando chega a hora, pede o estado ao servidor.
        onFrame(now, R) {
            const el = $('#cr-next-clue');
            if (!el) return;
            if (!local.nextClueAt) { el.textContent = ''; return; }
            const left = Math.max(0, Math.ceil((local.nextClueAt - now) / 1000));
            el.textContent = left > 0 ? 'Nova pista em ' + Math.floor(left / 60) + ':' + String(left % 60).padStart(2, '0') : 'Nova pista chegando...';
            if (left === 0) {
                local.nextClueAt = null;
                R.refresh();
            }
        },
    });
})();
