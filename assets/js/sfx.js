/*
 * Efeitos sonoros do PartyGames — sintetizados com Web Audio (sem arquivos de áudio).
 * Uso: PartyGames.sfx.play('tick'). O navegador só libera o áudio após um toque/clique do
 * jogador; até lá os sons são ignorados em silêncio. A preferência "mudo" fica no aparelho.
 */
(function () {
    'use strict';
    const KEY = 'partygames.muted';
    let ctx = null;
    let master = null;
    let muted = false;
    try { muted = localStorage.getItem(KEY) === '1'; } catch (e) { /* indisponível */ }

    function audio() {
        if (ctx) return ctx;
        const AC = window.AudioContext || window.webkitAudioContext;
        if (!AC) return null;
        ctx = new AC();
        master = ctx.createGain();
        master.gain.value = 0.5;
        master.connect(ctx.destination);
        return ctx;
    }
    function unlock() {
        const c = audio();
        if (c && c.state === 'suspended') c.resume();
    }
    ['pointerdown', 'keydown', 'touchstart'].forEach((ev) => document.addEventListener(ev, unlock, { passive: true }));

    // ---------------------------------------------------------------- blocos básicos
    /** Nota com envelope. type: sine|square|triangle|sawtooth; freq pode deslizar até 'to'. */
    function tone(t, o) {
        const osc = ctx.createOscillator();
        const g = ctx.createGain();
        osc.type = o.type || 'sine';
        osc.frequency.setValueAtTime(o.freq, t);
        if (o.to) osc.frequency.exponentialRampToValueAtTime(o.to, t + o.dur);
        const vol = o.vol == null ? 0.4 : o.vol;
        g.gain.setValueAtTime(0.0001, t);
        g.gain.exponentialRampToValueAtTime(vol, t + (o.attack || 0.005));
        g.gain.exponentialRampToValueAtTime(0.0001, t + o.dur);
        osc.connect(g).connect(master);
        osc.start(t);
        osc.stop(t + o.dur + 0.02);
    }
    let noiseBuf = null;
    /** Ruído filtrado (explosões, papel, tambor). */
    function noise(t, o) {
        if (!noiseBuf) {
            noiseBuf = ctx.createBuffer(1, ctx.sampleRate * 2, ctx.sampleRate);
            const d = noiseBuf.getChannelData(0);
            for (let i = 0; i < d.length; i++) d[i] = Math.random() * 2 - 1;
        }
        const src = ctx.createBufferSource();
        src.buffer = noiseBuf;
        const f = ctx.createBiquadFilter();
        f.type = o.filter || 'lowpass';
        f.frequency.setValueAtTime(o.freq || 1000, t);
        if (o.to) f.frequency.exponentialRampToValueAtTime(o.to, t + o.dur);
        const g = ctx.createGain();
        g.gain.setValueAtTime(o.vol == null ? 0.5 : o.vol, t);
        g.gain.exponentialRampToValueAtTime(0.0001, t + o.dur);
        src.connect(f).connect(g).connect(master);
        src.start(t);
        src.stop(t + o.dur + 0.02);
    }
    const notes = (t, list, o) => list.forEach((f, i) => tone(t + i * (o.gap || 0.1), Object.assign({}, o, { freq: f })));

    // ---------------------------------------------------------------- catálogo de sons
    const SOUNDS = {
        // Relógio: tic e tac alternados; nos 3 últimos segundos, mais agudo.
        tick: (t) => { tone(t, { type: 'square', freq: 1800, dur: 0.04, vol: 0.18 }); noise(t, { filter: 'highpass', freq: 3000, dur: 0.03, vol: 0.2 }); },
        tock: (t) => { tone(t, { type: 'square', freq: 1200, dur: 0.04, vol: 0.18 }); noise(t, { filter: 'highpass', freq: 2200, dur: 0.03, vol: 0.2 }); },
        tickUrgent: (t) => { tone(t, { type: 'square', freq: 2400, dur: 0.06, vol: 0.25 }); tone(t + 0.12, { type: 'square', freq: 2400, dur: 0.05, vol: 0.15 }); },
        timeUp: (t) => { tone(t, { type: 'sawtooth', freq: 220, dur: 0.5, vol: 0.3 }); tone(t, { type: 'square', freq: 165, dur: 0.5, vol: 0.2 }); },
        // Interface
        click: (t) => tone(t, { type: 'triangle', freq: 900, to: 600, dur: 0.06, vol: 0.25 }),
        send: (t) => tone(t, { type: 'sine', freq: 500, to: 1200, dur: 0.15, vol: 0.3 }),
        error: (t) => { tone(t, { type: 'square', freq: 180, dur: 0.12, vol: 0.2 }); tone(t + 0.13, { type: 'square', freq: 140, dur: 0.18, vol: 0.2 }); },
        whoosh: (t) => noise(t, { filter: 'bandpass', freq: 400, to: 3000, dur: 0.35, vol: 0.35 }),
        join: (t) => notes(t, [660, 990], { type: 'triangle', dur: 0.15, gap: 0.08, vol: 0.3 }),
        leave: (t) => notes(t, [660, 440], { type: 'triangle', dur: 0.15, gap: 0.08, vol: 0.25 }),
        start: (t) => notes(t, [523, 659, 784, 1047], { type: 'triangle', dur: 0.2, gap: 0.09, vol: 0.3 }),
        message: (t) => tone(t, { type: 'sine', freq: 1300, to: 1700, dur: 0.09, vol: 0.2 }),
        // Resultados
        correct: (t) => notes(t, [784, 1175], { type: 'sine', dur: 0.25, gap: 0.1, vol: 0.35 }),
        wrong: (t) => { tone(t, { type: 'sawtooth', freq: 300, to: 120, dur: 0.45, vol: 0.25 }); },
        win: (t) => { notes(t, [523, 659, 784], { type: 'square', dur: 0.14, gap: 0.12, vol: 0.18 }); tone(t + 0.36, { type: 'square', freq: 1047, dur: 0.6, vol: 0.2 }); tone(t + 0.36, { type: 'triangle', freq: 784, dur: 0.6, vol: 0.2 }); },
        drumroll: (t) => { for (let i = 0; i < 14; i++) noise(t + i * 0.05, { filter: 'bandpass', freq: 220, dur: 0.06, vol: 0.35 + i * 0.02 }); noise(t + 0.72, { filter: 'lowpass', freq: 2500, dur: 0.5, vol: 0.5 }); tone(t + 0.72, { freq: 110, to: 60, dur: 0.3, vol: 0.5 }); },
        reveal: (t) => { tone(t, { type: 'triangle', freq: 392, dur: 0.18, vol: 0.3 }); tone(t + 0.18, { type: 'triangle', freq: 587, dur: 0.5, vol: 0.3 }); },
        shuffle: (t) => { for (let i = 0; i < 8; i++) noise(t + i * 0.06, { filter: 'highpass', freq: 2500, dur: 0.05, vol: 0.25 }); },
        // Temáticos
        boom: (t) => { noise(t, { filter: 'lowpass', freq: 3000, to: 60, dur: 1.4, vol: 1 }); tone(t, { type: 'sine', freq: 120, to: 30, dur: 1.0, vol: 0.9 }); tone(t, { type: 'square', freq: 60, to: 25, dur: 0.5, vol: 0.4 }); },
        fuse: (t) => noise(t, { filter: 'highpass', freq: 4000, dur: 0.5, vol: 0.18 }),
        pass: (t) => { noise(t, { filter: 'bandpass', freq: 800, to: 2400, dur: 0.18, vol: 0.3 }); tone(t, { type: 'triangle', freq: 700, to: 1100, dur: 0.12, vol: 0.2 }); },
        alarm: (t) => { for (let i = 0; i < 3; i++) tone(t + i * 0.16, { type: 'square', freq: 1000, dur: 0.1, vol: 0.18 }); },
        gavel: (t) => { [0, 0.22].forEach((d) => { tone(t + d, { type: 'sine', freq: 180, to: 90, dur: 0.15, vol: 0.7 }); noise(t + d, { filter: 'bandpass', freq: 1200, dur: 0.06, vol: 0.6 }); }); },
        guilty: (t) => { tone(t, { type: 'sawtooth', freq: 147, dur: 0.9, vol: 0.2 }); tone(t, { type: 'sawtooth', freq: 175, dur: 0.9, vol: 0.18 }); tone(t, { type: 'sawtooth', freq: 208, dur: 0.9, vol: 0.15 }); },
        innocent: (t) => notes(t, [523, 659, 784, 1047], { type: 'sine', dur: 0.35, gap: 0.08, vol: 0.25 }),
        clue: (t) => notes(t, [1047, 1319, 1568], { type: 'sine', dur: 0.3, gap: 0.07, vol: 0.2 }),
        suspense: (t) => { tone(t, { type: 'triangle', freq: 98, dur: 1.2, vol: 0.35 }); tone(t + 0.3, { type: 'triangle', freq: 104, dur: 1.0, vol: 0.3 }); },
        press: (t) => { for (let i = 0; i < 6; i++) { noise(t + i * 0.09, { filter: 'bandpass', freq: 1800, dur: 0.04, vol: 0.5 }); tone(t + i * 0.09, { type: 'square', freq: 90, dur: 0.04, vol: 0.2 }); } tone(t + 0.6, { type: 'triangle', freq: 1568, dur: 0.4, vol: 0.25 }); },
        type: (t) => noise(t, { filter: 'bandpass', freq: 2500, dur: 0.03, vol: 0.4 }),
        secret: (t) => { tone(t, { type: 'sine', freq: 440, dur: 0.4, vol: 0.2 }); tone(t + 0.2, { type: 'sine', freq: 554, dur: 0.4, vol: 0.2 }); tone(t + 0.4, { type: 'sine', freq: 466, dur: 0.6, vol: 0.2 }); },
        mission: (t) => notes(t, [659, 880, 1319], { type: 'triangle', dur: 0.2, gap: 0.08, vol: 0.3 }),
        question: (t) => { tone(t, { type: 'sine', freq: 600, to: 900, dur: 0.18, vol: 0.25 }); },
    };

    const last = {};
    const sfx = {
        /** Toca um som (ignora se mudo, sem áudio liberado ou repetido em menos de 60 ms). */
        play(name, delay) {
            if (muted || !SOUNDS[name]) return;
            const c = audio();
            if (!c || c.state !== 'running') return;
            const now = performance.now();
            if (last[name] && now - last[name] < 60) return;
            last[name] = now;
            try { SOUNDS[name](c.currentTime + (delay || 0)); } catch (e) { /* som é opcional */ }
        },
        get muted() { return muted; },
        setMuted(v) {
            muted = !!v;
            try { localStorage.setItem(KEY, muted ? '1' : '0'); } catch (e) { /* indisponível */ }
        },
        names: Object.keys(SOUNDS),
    };
    window.PartyGamesSfx = sfx;
})();
