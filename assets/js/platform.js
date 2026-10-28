/* =========================================================
   PartyGames — núcleo da plataforma (JavaScript puro)

   Cuida de: sessão, salas, lobby, polling, relógio do servidor,
   reconexão e telas comuns. Cada jogo registra um módulo:

     PartyGames.registerGame('slug-do-jogo', {
       phases: { fase: 'Rótulo na barra' },
       init(R) {},                 // uma vez, para ligar eventos
       render(state, prev, R) {},  // retorna o data-screen a exibir
       onFrame(now, R) {},         // opcional, ~5x por segundo
       onDeadline(section, R) {},  // opcional, quando o cronômetro zera
     });

   Toda a lógica de jogo está no servidor. Conteúdo vindo do
   servidor é sempre inserido com textContent.
   ========================================================= */
(function () {
    'use strict';

    // ------------------------------------------------------------ utilidades
    const $ = (sel, root) => (root || document).querySelector(sel);
    const $$ = (sel, root) => Array.from((root || document).querySelectorAll(sel));
    const num = (v, d) => (Number.isFinite(parseInt(v, 10)) ? parseInt(v, 10) : d);

    const POLL = num(document.body.dataset.poll, 1500);
    const API_URL = 'api/index.php';
    const STORE_KEY = 'partygames.rejoin';
    const RING = 119.38;
    const noop = { play() {}, setMuted() {}, muted: true };
    const sfx = window.PartyGamesSfx || noop;

    function h(tag, attrs, children) {
        const el = document.createElement(tag);
        if (attrs) {
            Object.keys(attrs).forEach((k) => {
                if (k === 'class') el.className = attrs[k];
                else if (k === 'text') el.textContent = attrs[k];
                else el.setAttribute(k, attrs[k]);
            });
        }
        (children || []).forEach((c) => c && el.appendChild(typeof c === 'string' ? document.createTextNode(c) : c));
        return el;
    }

    const store = {
        get() {
            try {
                const v = JSON.parse(localStorage.getItem(STORE_KEY) || 'null');
                return v && typeof v.code === 'string' && typeof v.token === 'string' ? v : null;
            } catch (e) { return null; }
        },
        set(v) { try { localStorage.setItem(STORE_KEY, JSON.stringify(v)); } catch (e) { /* indisponível */ } },
        clear() { try { localStorage.removeItem(STORE_KEY); } catch (e) { /* ignore */ } },
    };
    const drafts = {
        get(k) { try { return sessionStorage.getItem('partygames.draft.' + k) || ''; } catch (e) { return ''; } },
        set(k, v) { try { sessionStorage.setItem('partygames.draft.' + k, v); } catch (e) { /* ignore */ } },
        clear(k) { try { sessionStorage.removeItem('partygames.draft.' + k); } catch (e) { /* ignore */ } },
    };
    const textLength = (s) => Array.from(s.trim().replace(/\s+/g, ' ')).length;

    // ------------------------------------------------------------ estado do cliente
    const games = {};
    const app = {
        csrf: ($('meta[name="csrf-token"]') || {}).content || '',
        state: null,
        version: null,
        offset: 0,
        bestRtt: Infinity,
        polling: false,
        pollTimer: null,
        inFlight: false,
        failures: 0,
        screen: 'loading',
        phaseKey: null,
        expiredKey: null,
        lastNotice: null,
        wasHost: null,
        booted: false,
    };

    // ------------------------------------------------------------ API
    class ApiError extends Error {
        constructor(message, code, status) {
            super(message);
            this.code = code;
            this.status = status;
        }
    }

    function syncClock(serverTime, t0, t1) {
        const rtt = t1 - t0;
        // Usa preferencialmente as amostras de menor latência; o limite relaxa aos poucos.
        app.bestRtt += 5;
        if (rtt <= app.bestRtt) {
            app.bestRtt = rtt;
            app.offset = serverTime - (Date.now() - rtt / 2);
        }
    }
    const serverNow = () => Date.now() + app.offset;

    async function api(action, opts) {
        opts = opts || {};
        const isPost = opts.body !== undefined;
        const params = new URLSearchParams(Object.assign({ action: action }, opts.query || {}));
        const ctrl = typeof AbortController !== 'undefined' ? new AbortController() : null;
        const timer = setTimeout(() => ctrl && ctrl.abort(), opts.timeout || 10000);
        const t0 = performance.now();
        let res;
        try {
            res = await fetch(API_URL + '?' + params.toString(), {
                method: isPost ? 'POST' : 'GET',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: isPost
                    ? { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-Token': app.csrf }
                    : { 'Accept': 'application/json' },
                body: isPost ? JSON.stringify(opts.body) : undefined,
                signal: ctrl ? ctrl.signal : undefined,
            });
        } catch (e) {
            throw new ApiError(e && e.name === 'AbortError' ? 'O servidor demorou para responder.' : 'Sem conexão com o servidor.', 'network', 0);
        } finally {
            clearTimeout(timer);
        }
        const t1 = performance.now();
        let json = null;
        try { json = await res.json(); } catch (e) { json = null; }
        if (!json || typeof json !== 'object' || typeof json.ok !== 'boolean') {
            throw new ApiError('Resposta inválida do servidor.', 'bad_response', res.status);
        }
        if (!json.ok) {
            const err = json.error || {};
            if (err.code === 'csrf' && isPost && opts.retry !== false) {
                await refreshSession();
                return api(action, Object.assign({}, opts, { retry: false }));
            }
            throw new ApiError(String(err.message || 'Erro desconhecido.'), String(err.code || 'error'), res.status);
        }
        const data = json.data && typeof json.data === 'object' ? json.data : {};
        if (typeof data.server_time === 'number') syncClock(data.server_time, t0, t1);
        return data;
    }

    /** Ação do jogo da sala atual (o servidor valida tudo). */
    function gameAction(op, body) {
        return api('game', { query: { op: op }, body: body || {} }).then((d) => {
            if (op !== 'skip') sfx.play('send');
            return d;
        }, (e) => {
            sfx.play('error');
            throw e;
        });
    }

    async function refreshSession() {
        const s = await api('session');
        if (typeof s.csrf === 'string') app.csrf = s.csrf;
        return s;
    }

    // ------------------------------------------------------------ interface geral
    function show(name) {
        $$('.screen').forEach((s) => {
            const on = s.dataset.screen === name;
            s.hidden = !on;
            s.classList.toggle('is-visible', on);
        });
        if (app.screen !== name) window.scrollTo(0, 0);
        app.screen = name;
        $('#topbar').hidden = ['home', 'create', 'join', 'reconnect', 'loading'].indexOf(name) !== -1;
    }

    function toast(message, type) {
        const el = h('div', { class: 'toast toast--' + (type || 'ok'), role: type === 'error' ? 'alert' : 'status', text: message });
        $('#toasts').appendChild(el);
        setTimeout(() => {
            el.classList.add('is-leaving');
            setTimeout(() => el.remove(), 350);
        }, type === 'error' ? 5000 : 3500);
    }

    function setNetBanner(on) { $('#net-banner').hidden = !on; }

    function formError(container, message) {
        const el = $('.form-error', container);
        if (!el) return;
        el.textContent = message || '';
        el.hidden = !message;
    }

    function setLoading(btn, on) {
        if (!btn) return;
        btn.disabled = on;
        btn.classList.toggle('is-loading', on);
    }

    function confirmDialog(text, okLabel) {
        const dlg = $('#confirm-dialog');
        if (!dlg || typeof dlg.showModal !== 'function') return Promise.resolve(window.confirm(text));
        $('#confirm-text').textContent = text;
        $('#confirm-ok').textContent = okLabel || 'Confirmar';
        return new Promise((resolve) => {
            const onClose = () => { dlg.removeEventListener('close', onClose); resolve(dlg.returnValue === 'ok'); };
            dlg.addEventListener('close', onClose);
            dlg.returnValue = '';
            dlg.showModal();
        });
    }

    function avatar(player) {
        const hue = (player.id * 67) % 360;
        const el = h('span', { class: 'avatar', 'aria-hidden': 'true', text: Array.from(player.nickname)[0].toUpperCase() });
        el.style.background = 'hsl(' + hue + ' 90% 72%)';
        return el;
    }

    function restartAnimation(el) {
        el.style.animation = 'none';
        void el.offsetHeight;
        el.style.animation = '';
    }

    // ------------------------------------------------------------ componentes para os jogos
    const ui = {
        h: h,
        $: $,
        $$: $$,
        toast: toast,
        confirm: confirmDialog,
        formError: formError,
        setLoading: setLoading,
        avatar: avatar,
        restartAnimation: restartAnimation,

        /** Mostra só o bloco [data-state="name"] dentro da seção. */
        setSubState(section, name) {
            $$('[data-state]', section).forEach((el) => { el.hidden = el.dataset.state !== name; });
        },

        /** Cronômetro [data-timer] da seção; null esconde. Tempos em milissegundos do servidor. */
        setTimer(section, startedAt, endsAt) {
            const timer = $('[data-timer]', section);
            if (!timer) return;
            timer.dataset.ends = endsAt ? String(endsAt) : '';
            timer.dataset.start = startedAt ? String(startedAt) : '';
            timer.classList.toggle('is-off', !endsAt);
        },

        /** Lista compacta de jogadores com o andamento (state.players[].progress). */
        miniPlayers(section, players, labels) {
            const list = $('[data-mini-players]', section);
            if (!list) return;
            labels = Object.assign({ submitted: '✓', missed: 'fora', excluded: 'fora', pending: '...', waiting: 'próxima' }, labels || {});
            list.textContent = '';
            players.forEach((p) => {
                const cls = p.progress === 'submitted' ? 'is-done' : (['missed', 'excluded'].indexOf(p.progress) !== -1 ? 'is-out' : '');
                list.appendChild(h('li', { class: cls, title: p.online ? '' : 'desconectado' }, [
                    avatar(p),
                    h('span', { text: p.nickname + ' ' + (labels[p.progress] || '') }),
                ]));
            });
        },

        /** Reinicia um formulário de texto quando a fase muda (recupera rascunho salvo). */
        preparePhase(key, textarea, form) {
            if (app.phaseKey === key) return false;
            app.phaseKey = key;
            app.expiredKey = null;
            textarea.value = drafts.get(key);
            textarea.disabled = false;
            formError(form, '');
            setLoading($('button[type="submit"]', form), false);
            ui.updateCounter(textarea);
            return true;
        },

        updateCounter(ta) {
            const counter = $('[data-counter]', ta.closest('form'));
            if (!counter) return;
            const max = Number(ta.getAttribute('maxlength')) || 250;
            const len = textLength(ta.value);
            counter.textContent = len + '/' + max;
            counter.classList.toggle('is-warn', len > max * 0.85 && len <= max);
            counter.classList.toggle('is-bad', len > max);
        },

        /** Contador de caracteres, rascunho automático e Enter para enviar. */
        bindTextarea(ta) {
            ta.addEventListener('input', () => {
                ui.updateCounter(ta);
                if (app.phaseKey) drafts.set(app.phaseKey, ta.value);
            });
            ta.addEventListener('keydown', (ev) => {
                if (ev.key === 'Enter' && !ev.shiftKey && !ev.isComposing) {
                    ev.preventDefault();
                    if (ta.form.requestSubmit) ta.form.requestSubmit();
                    else ta.form.dispatchEvent(new Event('submit', { cancelable: true }));
                }
            });
        },

        /** Envia o texto do formulário como ação do jogo, com validação de tamanho no cliente. */
        async submitText(form, op, min, max, label, onDone) {
            const ta = $('textarea', form);
            const btn = $('button[type="submit"]', form);
            const len = textLength(ta.value);
            if (len < min || len > max) {
                formError(form, label + ' deve ter entre ' + min + ' e ' + max + ' caracteres.');
                ta.focus();
                return;
            }
            formError(form, '');
            setLoading(btn, true);
            try {
                const data = await gameAction(op, { text: ta.value });
                onDone(data);
                drafts.clear(app.phaseKey);
                pollNow();
            } catch (e) {
                formError(form, e.message);
                if (['time_over', 'invalid_state', 'already_submitted', 'not_started'].indexOf(e.code) !== -1) pollNow();
            } finally {
                setLoading(btn, false);
            }
        },

        textLength: textLength,

        /** Placar compartilhado (state.scoreboard) dentro de um elemento <ol>. */
        scoreboard(list, rows) {
            renderScoreboard(list, rows || [], true);
        },

        /**
         * Lista de opções clicáveis (votar em jogador, resposta, suspeito...).
         * items: [{id, label, sub?, disabled?, mine?}]; selected: id já escolhido (bloqueia a lista).
         */
        choices(list, items, selected, onPick) {
            list.textContent = '';
            items.forEach((it) => {
                const isSel = selected !== null && selected !== undefined && String(selected) === String(it.id);
                const btn = h('button', {
                    type: 'button',
                    class: 'choice' + (isSel ? ' is-selected' : '') + (it.mine ? ' is-mine' : ''),
                }, [
                    h('span', { class: 'choice__label', text: it.label }),
                    it.sub ? h('span', { class: 'choice__sub', text: it.sub }) : null,
                ]);
                btn.disabled = !!it.disabled || (selected !== null && selected !== undefined);
                btn.addEventListener('click', () => onPick(it, btn));
                list.appendChild(h('li', null, [btn]));
            });
        },

        /** Botão "pular" do anfitrião para fases com prazo (revelação, discussão...). */
        hostSkip(btn, state, phase) {
            const round = state.game && state.game.round;
            btn.hidden = !state.me.host || !round || !round.skippable;
            btn.dataset.phase = phase;
        },
    };

    // ------------------------------------------------------------ polling
    function startPolling() { app.polling = true; pollNow(); }
    function stopPolling() { app.polling = false; clearTimeout(app.pollTimer); }
    function pollNow() {
        if (!app.polling) return;
        clearTimeout(app.pollTimer);
        app.pollTimer = setTimeout(poll, 0);
    }
    /** Pede o estado completo (ignora a versão em cache). */
    function refresh() { app.version = null; pollNow(); }

    function nextDelay() {
        if (app.failures > 0) return Math.min(10000, 1000 * Math.pow(2, Math.min(app.failures, 4)));
        if (document.hidden) return 5000;
        const status = app.state && app.state.room ? app.state.room.status : null;
        return status === 'lobby' ? Math.max(POLL, 2000) : POLL;
    }

    async function poll() {
        if (!app.polling) return;
        if (app.inFlight) { app.pollTimer = setTimeout(poll, 250); return; }
        app.inFlight = true;
        try {
            const data = await api('state', { query: app.version !== null ? { v: app.version } : {}, timeout: 8000 });
            if (app.failures > 0) { app.failures = 0; setNetBanner(false); }
            if (data.changed && data.room) {
                app.version = data.version;
                render(data);
            }
        } catch (e) {
            if (e.code === 'not_in_room' || e.code === 'room_not_found') {
                app.inFlight = false;
                onSessionLost();
                return;
            }
            app.failures++;
            if (app.failures >= 2) setNetBanner(true);
        } finally {
            app.inFlight = false;
        }
        if (app.polling) {
            clearTimeout(app.pollTimer);
            app.pollTimer = setTimeout(poll, nextDelay());
        }
    }

    // ------------------------------------------------------------ sessão / sala
    function enterRoom() {
        app.version = null;
        app.state = null;
        app.phaseKey = null;
        app.lastNotice = null;
        app.wasHost = null;
        show('loading');
        startPolling();
    }

    function resetToHome(message) {
        stopPolling();
        setNetBanner(false);
        app.state = null;
        app.version = null;
        if (message) toast(message, 'error');
        try { history.replaceState(null, '', location.pathname); } catch (e) { /* ignore */ }
        show('home');
    }

    async function onSessionLost() {
        stopPolling();
        const saved = store.get();
        if (!saved) { resetToHome('Você não está mais nesta sala.'); return; }
        setNetBanner(true);
        try {
            const data = await api('reconnect', { body: { code: saved.code, token: saved.token } });
            store.set({ code: data.code, token: data.token, nickname: data.nickname });
            setNetBanner(false);
            enterRoom();
        } catch (e) {
            setNetBanner(false);
            if (e.code === 'network') { showReconnect(saved, 'Sem conexão. Tente novamente.'); return; }
            store.clear();
            resetToHome(e.message);
        }
    }

    function showReconnect(saved, error) {
        $('#reconnect-text').textContent = 'Você estava na sala ' + saved.code + (saved.nickname ? ' como “' + saved.nickname + '”' : '') + '.';
        formError($('#screen-reconnect'), error || '');
        show('reconnect');
    }

    // ------------------------------------------------------------ renderização
    function render(s) {
        const prev = app.state;
        app.state = s;
        const room = s.room;
        const mod = games[room.game];

        $('#tb-code').textContent = room.code;
        $('#tb-game-name').textContent = room.game_name;
        $('#tb-game-cover').src = room.game_cover;
        const tbRound = $('#tb-round');
        const totalRounds = s.game && s.game.round && s.game.round.total > 1 ? s.game.round.total : 0;
        tbRound.hidden = !room.round_number || room.status !== 'playing' || (s.game && s.game.round && s.game.round.total === 1);
        $('strong', tbRound).textContent = room.round_number + (totalRounds ? '/' + totalRounds : '');
        $('#btn-score').hidden = !s.match;
        $('#btn-howto').dataset.howto = room.game;
        $('#btn-howto-lobby').dataset.howto = room.game;
        if ($('#score-dialog').open) renderScoreDialog();
        let phaseLabel = { lobby: 'Aguardando', finished: 'Encerrada' }[room.status] || 'Jogando';
        if (room.status === 'playing' && mod && mod.phases && mod.phases[room.phase]) phaseLabel = mod.phases[room.phase];
        $('#tb-phase').textContent = phaseLabel;
        $('#btn-end').hidden = !s.me.host || room.status === 'finished';
        $('#btn-end').textContent = room.status === 'playing' ? 'Encerrar partida' : 'Fechar sala';
        $('#btn-leave').hidden = room.status === 'finished';

        if (app.wasHost === false && s.me.host) toast('Você agora é o anfitrião da sala.');
        app.wasHost = s.me.host;
        if (room.notice && room.notice !== app.lastNotice && room.status !== 'lobby' && prev) toast(room.notice);
        app.lastNotice = room.notice;

        const saved = store.get();
        if (saved && saved.nickname !== s.me.nickname) store.set(Object.assign(saved, { nickname: s.me.nickname }));

        playTransitionSounds(prev, s, mod);
        if (room.status === 'lobby') {
            renderLobby(s);
            show('lobby');
        } else if (room.status === 'finished') {
            renderFinished(s);
            show('finished');
        } else if (!mod) {
            show('unsupported');
        } else {
            const screen = mod.render(s, prev, PartyGames);
            if (screen) show(screen);
        }
        updateTimers();
    }

    /** Sons comuns: entrada/saída de jogadores, início, troca de fase e fim de partida. */
    function playTransitionSounds(prev, s, mod) {
        if (!prev || !prev.room || prev.room.code !== s.room.code) return;
        const was = prev.room;
        const room = s.room;
        if (was.status === 'lobby' && room.status === 'playing') { sfx.play('start'); return; }
        if (was.status === 'playing' && room.status !== 'playing') { sfx.play(s.match ? 'win' : 'leave'); return; }
        if (room.status === 'playing' && (was.phase !== room.phase || was.round_number !== room.round_number)) {
            const custom = mod && mod.sounds ? mod.sounds[room.phase] : undefined;
            if (custom !== null) sfx.play(custom || 'whoosh');
        }
        const before = prev.players ? prev.players.length : 0;
        const now = s.players ? s.players.length : 0;
        if (now > before) sfx.play('join');
        else if (now < before) sfx.play('leave');
    }

    function renderLobby(s) {
        const room = s.room;
        $('#lobby-name').textContent = room.name || 'Sala ' + room.code;
        $('#lobby-game').textContent = room.game_name;
        $('#lobby-cover').src = room.game_cover;
        $('#btn-configure').hidden = !s.me.host;
        const last = $('#lobby-last');
        const showLast = !!(s.match && s.match.status === 'finished' && s.match.scoring && (s.scoreboard || []).length);
        last.hidden = !showLast;
        if (showLast) {
            $('#lobby-last-game').textContent = '· ' + s.match.game_name;
            renderScoreboard($('#lobby-last-board'), s.scoreboard, false);
        }
        $('#lobby-code').textContent = room.code;
        $('#lobby-count').textContent = String(s.players.length);
        $('#lobby-share').textContent = room.has_password
            ? 'Compartilhe o código e a senha com seus amigos.'
            : 'Sala sem senha: basta compartilhar o código ou o link.';

        const notice = $('#lobby-notice');
        notice.textContent = room.notice || '';
        notice.hidden = !room.notice;

        const need = room.required_players;
        const online = room.online_players;
        let status;
        if (online < need) status = 'Aguardando jogadores... (' + online + ' de ' + need + ' necessários conectados)';
        else if (s.me.host) status = 'Tudo pronto! Inicie quando quiser — quem entrar depois participa da próxima rodada.';
        else status = 'Aguardando o anfitrião iniciar a partida...';
        $('#lobby-status').textContent = status;

        const start = $('#btn-start');
        start.hidden = !s.me.host;
        if (!start.classList.contains('is-loading')) start.disabled = !room.can_start;
        start.textContent = s.match ? 'Jogar de novo' : 'Iniciar partida';

        const dl = $('#lobby-settings');
        dl.textContent = '';
        (room.settings_summary || []).forEach((pair) => dl.appendChild(h('div', null, [h('dt', { text: pair[0] }), h('dd', { text: pair[1] })])));

        const list = $('#lobby-players');
        list.textContent = '';
        s.players.forEach((p) => {
            const tags = [];
            if (p.host) tags.push(h('span', { class: 'badge', text: 'anfitrião' }));
            if (!p.online) tags.push(h('span', { class: 'player__tag', text: 'desconectado' }));
            list.appendChild(h('li', { class: 'player' + (p.me ? ' is-me' : '') + (p.online ? '' : ' is-offline') }, [
                avatar(p),
                h('span', { class: 'player__name', text: p.nickname + (p.me ? ' (você)' : '') }),
            ].concat(tags)));
        });
    }

    function renderFinished(s) {
        stopPolling();
        store.clear();
        $('#finished-text').textContent = s.room.end_message || 'A partida foi encerrada.';
        const n = s.room.round_number;
        $('#finished-rounds').textContent = n > 0 ? (n === 1 ? '1 rodada jogada.' : n + ' rodadas jogadas.') : '';
    }

    // ------------------------------------------------------------ cronômetro (prazo do servidor)
    function updateTimers() {
        const now = serverNow();
        const mod = app.state && app.state.room ? games[app.state.room.game] : null;
        if (mod && mod.onFrame && app.state.room.status === 'playing') mod.onFrame(now, PartyGames);

        const sec = $('.screen.is-visible');
        if (!sec) return;
        const timer = $('[data-timer]', sec);
        if (!timer || !timer.dataset.ends) return;
        const ends = Number(timer.dataset.ends);
        const start = Number(timer.dataset.start) || ends - 60000;
        const remaining = Math.max(0, ends - now);
        const total = Math.max(1000, ends - start);
        const secs = Math.ceil(remaining / 1000);
        // Fases longas (ex.: investigação) aparecem como m:ss.
        $('.timer__num', timer).textContent = secs >= 100 ? Math.floor(secs / 60) + ':' + String(secs % 60).padStart(2, '0') : String(secs);
        timer.classList.toggle('is-long', secs >= 100);
        $('.timer__bar', timer).style.strokeDashoffset = String(RING * (1 - Math.min(1, remaining / total)));
        timer.classList.toggle('is-urgent', secs <= 10);
        // Tic-tac nos últimos 10 segundos (um som por segundo; mais agudo nos 3 finais).
        const tickKey = sec.id + ':' + ends + ':' + secs;
        if (secs >= 1 && secs <= 10 && remaining > 0 && app.tickKey !== tickKey && !timer.classList.contains('is-off')) {
            app.tickKey = tickKey;
            sfx.play(secs <= 3 ? 'tickUrgent' : (secs % 2 ? 'tick' : 'tock'));
        }

        if (remaining <= 0 && app.expiredKey !== sec.id + ':' + ends) {
            if (now - ends < 1500) sfx.play('timeUp'); // não toca ao abrir uma fase que já tinha vencido
            app.expiredKey = sec.id + ':' + ends;
            if (mod && mod.onDeadline) mod.onDeadline(sec, PartyGames);
            pollNow(); // o servidor faz a transição nesta consulta
        }
    }

    // ------------------------------------------------------------ eventos da plataforma
    /** Valores do formulário; campos "nome[]" (caixas de seleção) viram listas, mesmo vazias. */
    function formValues(form) {
        const out = {};
        $$('input[type="checkbox"]', form).forEach((cb) => {
            if (cb.name.slice(-2) === '[]' && !cb.disabled && !cb.closest('fieldset:disabled')) out[cb.name.slice(0, -2)] = [];
        });
        new FormData(form).forEach((v, k) => {
            v = typeof v === 'string' ? v : '';
            if (k.slice(-2) === '[]') (out[k.slice(0, -2)] = out[k.slice(0, -2)] || []).push(v);
            else out[k] = v;
        });
        return out;
    }

    /** Mostra as regras do jogo escolhido; campos de outros jogos ficam desativados e não são enviados. */
    function selectGameInForm(slug, resetPlayers) {
        const form = $('#form-create');
        let chosen = null;
        $$('.game-settings', form).forEach((fs) => {
            const on = fs.dataset.game === slug;
            fs.hidden = !on;
            fs.disabled = !on;
            if (on) chosen = fs;
        });
        if (!chosen) return null;
        form.elements.game.value = slug;
        $('#create-title').textContent = chosen.dataset.name;
        $('#create-cover').src = chosen.dataset.cover;
        ['min_players', 'max_players'].forEach((name) => {
            const input = form.elements[name];
            input.min = chosen.dataset.pmin;
            input.max = chosen.dataset.pmax;
            if (resetPlayers) input.value = name === 'min_players' ? chosen.dataset.pminDefault : chosen.dataset.pmaxDefault;
        });
        return chosen;
    }

    function setCreateMode(mode) {
        const form = $('#form-create');
        form.dataset.mode = mode;
        const configure = mode === 'configure';
        $('#create-personal').hidden = configure;
        $('#create-personal').disabled = configure;
        $('#create-eyebrow').textContent = configure ? 'Trocar jogo / regras' : 'Nova sala';
        $('#create-submit').textContent = configure ? 'Salvar e voltar ao lobby' : 'Criar sala';
        formError(form, '');
    }

    function openCreate(slug) {
        setCreateMode('create');
        if (!selectGameInForm(slug, true)) return;
        show('create');
        setTimeout(() => $('#form-create').elements.nickname.focus(), 60);
    }

    /** Anfitrião no lobby: abre o formulário com o jogo e as regras atuais da sala. */
    function openConfigure() {
        const room = app.state && app.state.room;
        if (!room) return;
        setCreateMode('configure');
        const fs = selectGameInForm(room.game, false);
        const form = $('#form-create');
        form.elements.min_players.value = room.min_players;
        form.elements.max_players.value = room.max_players;
        const settings = room.settings || {};
        Object.keys(settings).forEach((key) => {
            const value = settings[key];
            if (Array.isArray(value)) {
                const boxes = $$('input[name="' + key + '[]"]', fs);
                if (boxes.length) boxes.forEach((cb) => { cb.checked = value.indexOf(cb.value) !== -1; });
                else if (fs.querySelector('[name="' + key + '"]')) fs.querySelector('[name="' + key + '"]').value = value.join('\n');
                return;
            }
            const el = fs.querySelector('[name="' + key + '"]');
            if (el) el.value = typeof value === 'boolean' ? (value ? '1' : '0') : String(value);
        });
        show('create');
    }

    /** Abre o diálogo "Como Jogar?" com as regras do jogo (conteúdo vindo de um <template> do servidor). */
    function openHowto(slug) {
        const tpl = document.getElementById('howto-' + slug);
        const dlg = $('#howto-dialog');
        if (!tpl || !dlg) return;
        $('#howto-title').textContent = tpl.dataset.name;
        $('#howto-cover').src = tpl.dataset.cover;
        const body = $('#howto-body');
        body.textContent = '';
        body.appendChild(tpl.content.cloneNode(true));
        body.scrollTop = 0;
        if (typeof dlg.showModal === 'function') {
            if (!dlg.open) dlg.showModal();
        } else {
            dlg.setAttribute('open', '');
        }
    }

    function renderScoreboard(list, rows, withRound) {
        list.textContent = '';
        rows.forEach((r) => {
            const li = h('li', { class: 'score-row' + (r.position === 1 && r.total > 0 ? ' is-top' : '') + (r.left ? ' is-left' : '') }, [
                h('span', { class: 'score-row__pos', text: r.position + 'º' }),
                h('span', { class: 'score-row__name', text: r.nickname + (r.left ? ' (saiu)' : '') }),
                withRound && r.round_points ? h('span', { class: 'score-row__delta', text: (r.round_points > 0 ? '+' : '') + r.round_points }) : null,
                h('span', { class: 'score-row__total', text: r.total + ' pts' }),
            ]);
            list.appendChild(li);
        });
    }

    function renderScoreDialog() {
        const s = app.state;
        if (!s) return;
        const scoring = s.match ? s.match.scoring : true;
        $('#score-empty').hidden = scoring;
        renderScoreboard($('#score-board'), scoring ? s.scoreboard || [] : [], true);
        const hist = $('#score-history');
        hist.textContent = '';
        (s.history || []).forEach((ev) => {
            hist.appendChild(h('li', null, [
                h('strong', { text: (ev.type === 'match_end' ? '' : 'Rodada ' + ev.round + ' · ') + ev.title }),
                h('span', { text: ev.text }),
            ]));
        });
        if (!(s.history || []).length) hist.appendChild(h('li', { class: 'muted', text: 'Nenhuma rodada concluída ainda.' }));
    }

    function bindEvents() {
        $$('[data-go]').forEach((btn) => btn.addEventListener('click', () => {
            const target = btn.dataset.go;
            show(target);
            const first = $('#screen-' + target + ' input:not([type="hidden"])');
            if (first) setTimeout(() => first.focus(), 60);
        }));
        $$('[data-create]').forEach((btn) => btn.addEventListener('click', () => openCreate(btn.dataset.create)));
        $$('.game-card[data-accent]').forEach((card) => card.style.setProperty('--accent', card.dataset.accent));

        $('#form-create').addEventListener('submit', async (ev) => {
            ev.preventDefault();
            const form = ev.currentTarget;
            const btn = $('button[type="submit"]', form);
            const v = formValues(form);
            Object.keys(v).forEach((k) => {
                const el = form.elements[k];
                if (el && el.type === 'number') v[k] = num(v[k], undefined);
            });
            formError(form, '');
            setLoading(btn, true);
            try {
                if (form.dataset.mode === 'configure') {
                    await api('configure_room', { body: v });
                    toast('Jogo e regras atualizados!');
                    show('lobby');
                    refresh();
                } else {
                    const data = await api('create_room', { body: v });
                    store.set({ code: data.code, token: data.token, nickname: data.nickname });
                    enterRoom();
                }
            } catch (e) {
                formError(form, e.message);
            } finally {
                setLoading(btn, false);
            }
        });
        $('#create-game').addEventListener('change', (ev) => selectGameInForm(ev.target.value, $('#form-create').dataset.mode === 'create'));
        $('#create-back').addEventListener('click', () => show($('#form-create').dataset.mode === 'configure' ? 'lobby' : 'home'));
        $('#btn-configure').addEventListener('click', openConfigure);
        $('#btn-score').addEventListener('click', () => {
            renderScoreDialog();
            const dlg = $('#score-dialog');
            if (dlg.showModal) dlg.showModal();
        });

        const codeInput = $('#form-join input[name="code"]');
        codeInput.addEventListener('input', () => {
            codeInput.value = codeInput.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 6);
        });

        $('#form-join').addEventListener('submit', async (ev) => {
            ev.preventDefault();
            const form = ev.currentTarget;
            const btn = $('button[type="submit"]', form);
            formError(form, '');
            setLoading(btn, true);
            try {
                const data = await api('join_room', { body: formValues(form) });
                store.set({ code: data.code, token: data.token, nickname: data.nickname });
                if (data.reconnected) toast('Você voltou para a sala.');
                enterRoom();
            } catch (e) {
                formError(form, e.message);
            } finally {
                setLoading(btn, false);
            }
        });

        $('#btn-reconnect').addEventListener('click', async (ev) => {
            const saved = store.get();
            if (!saved) { show('home'); return; }
            const btn = ev.currentTarget;
            setLoading(btn, true);
            try {
                const data = await api('reconnect', { body: { code: saved.code, token: saved.token } });
                store.set({ code: data.code, token: data.token, nickname: data.nickname });
                enterRoom();
            } catch (e) {
                const temporary = e.code === 'network' || e.code === 'rate_limited';
                if (!temporary) store.clear();
                formError($('#screen-reconnect'), e.message);
                if (!temporary) setTimeout(() => resetToHome(), 2500);
            } finally {
                setLoading(btn, false);
            }
        });

        $('#btn-forget').addEventListener('click', () => { store.clear(); resetToHome(); });

        $('#btn-copy').addEventListener('click', async () => {
            const room = app.state ? app.state.room : null;
            const link = location.origin + location.pathname + '?sala=' + encodeURIComponent(room ? room.code : '');
            try {
                await navigator.clipboard.writeText(link);
                toast(room && room.has_password ? 'Link de convite copiado! Envie também a senha.' : 'Link de convite copiado!');
            } catch (e) {
                window.prompt('Copie o link de convite:', link);
            }
        });

        $('#btn-start').addEventListener('click', async (ev) => {
            const btn = ev.currentTarget;
            setLoading(btn, true);
            try {
                await api('start_game', { body: {} });
                pollNow();
            } catch (e) {
                toast(e.message, 'error');
            } finally {
                setLoading(btn, false);
            }
        });

        const soundBtn = $('#btn-sound');
        const syncSound = () => {
            soundBtn.textContent = sfx.muted ? '🔇' : '🔊';
            soundBtn.title = sfx.muted ? 'Ativar sons' : 'Desativar sons';
            soundBtn.setAttribute('aria-pressed', sfx.muted ? 'true' : 'false');
        };
        syncSound();
        soundBtn.addEventListener('click', () => {
            sfx.setMuted(!sfx.muted);
            syncSound();
            sfx.play('click');
        });

        $('#btn-leave').addEventListener('click', async () => {
            if (!(await confirmDialog('Sair da sala? Você pode entrar novamente com o código da sala.', 'Sair'))) return;
            try { await api('leave_room', { body: {} }); } catch (e) { /* sai localmente mesmo assim */ }
            store.clear();
            resetToHome();
        });

        $('#btn-end').addEventListener('click', async () => {
            const playing = app.state && app.state.room.status === 'playing';
            const question = playing
                ? 'Encerrar a partida agora? Todos voltam ao lobby com o placar atual.'
                : 'Fechar a sala para todos os jogadores?';
            if (!(await confirmDialog(question, playing ? 'Encerrar partida' : 'Fechar sala'))) return;
            try {
                await api('end_game', { body: {} });
                pollNow();
            } catch (e) {
                toast(e.message, 'error');
            }
        });

        $('#btn-home').addEventListener('click', async () => {
            try { await api('leave_room', { body: {} }); } catch (e) { /* já encerrada */ }
            store.clear();
            resetToHome();
        });

        $('#tb-home').addEventListener('click', (ev) => {
            // Na sala, o logo não navega para fora (evita sair sem querer).
            if (app.polling) ev.preventDefault();
        });

        // Botões "Como Jogar?": data-howto com o jogo (vazio = jogo atual da sala).
        document.addEventListener('click', (ev) => {
            const btn = ev.target.closest('[data-howto]');
            if (!btn) return;
            const slug = btn.dataset.howto || (app.state && app.state.room ? app.state.room.game : '');
            openHowto(slug);
        });

        // Botões [data-skip] dos jogos: o anfitrião encerra a fase atual (o servidor valida).
        document.addEventListener('click', async (ev) => {
            const btn = ev.target.closest('[data-skip]');
            if (!btn) return;
            setLoading(btn, true);
            try {
                await gameAction('skip', { phase: btn.dataset.phase || '' });
                pollNow();
            } catch (e) {
                toast(e.message, 'error');
            } finally {
                setLoading(btn, false);
            }
        });

        document.addEventListener('visibilitychange', () => { if (!document.hidden) pollNow(); });
        window.addEventListener('online', () => pollNow());
    }

    // ------------------------------------------------------------ API pública para os jogos
    const PartyGames = {
        registerGame(slug, mod) {
            games[slug] = mod;
            if (app.booted && mod.init) mod.init(PartyGames);
        },
        api: api,
        action: gameAction,
        pollNow: pollNow,
        refresh: refresh,
        serverNow: serverNow,
        state: () => app.state,
        sfx: sfx,
        drafts: drafts,
        ui: ui,
    };
    window.PartyGames = PartyGames;

    // ------------------------------------------------------------ início
    async function boot() {
        app.booted = true;
        bindEvents();
        Object.keys(games).forEach((slug) => { if (games[slug].init) games[slug].init(PartyGames); });
        setInterval(updateTimers, 200);

        const params = new URLSearchParams(location.search);
        const codeParam = (params.get('sala') || '').toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 6);
        if (codeParam) $('#form-join input[name="code"]').value = codeParam;

        try {
            const s = await refreshSession();
            if (s.room) { enterRoom(); return; }
            const saved = store.get();
            if (saved && (!codeParam || codeParam === saved.code)) { showReconnect(saved); return; }
            show(codeParam ? 'join' : 'home');
        } catch (e) {
            show('home');
            toast(e.message, 'error');
        }
    }

    // Scripts "defer" rodam antes do DOMContentLoaded: até lá todos os jogos já se registraram.
    if (document.readyState === 'complete') boot();
    else document.addEventListener('DOMContentLoaded', boot);
})();
