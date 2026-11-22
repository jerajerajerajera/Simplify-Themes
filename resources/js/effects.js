/*
 * Simplify Themes: a moving background drawn on a canvas behind the panel (shooting stars, snow, fireflies, rain,
 * aurora, bubbles). One loop draws every effect canvas on the page: the panel's own and the editor's previews.
 * It pauses while the page is hidden, draws one still frame for people who ask their device for less motion,
 * caps the pixel density at 1.5 and draws at 30 frames a second and with fewer pieces on a narrow screen.
 */
(() => {
    if (window.SimplifyThemesFx) {
        window.SimplifyThemesFx.mountPage();
        return;
    }
    const still = matchMedia('(prefers-reduced-motion: reduce)').matches;
    const rand = (a, b) => a + Math.random() * (b - a);
    const area = (w, h, per, a) => Math.max(4, Math.round((w * h) / 100000 * per * a));
    // Pieces that leave one side come back on the other, whichever way they travel.
    const wrap = (o, w, h, m) => {
        o.x = ((o.x + m) % (w + 2 * m) + w + 2 * m) % (w + 2 * m) - m;
        o.y = ((o.y + m) % (h + 2 * m) + h + 2 * m) % (h + 2 * m) - m;
    };
    const halo = (c, k, r) => { c.shadowColor = k.col; c.shadowBlur = k.glow > 0 ? r * 6 * k.glow : 0; };

    /* Every effect: make(w, h, amount) gives its pieces, step(context, pieces, w, h, seconds, k) moves and draws them.
       k holds amount, speed, size and glow as factors, dx/dy (where pieces travel), col and bands (aurora). */
    const FX = {
        stars: {
            make: (w, h, a) => ({ dots: Array.from({ length: area(w, h, 14, a) }, () => ({ x: rand(0, w), y: rand(0, h * .8), r: rand(.4, 1.4), p: rand(0, 6) })), trails: [], next: 0 }),
            step(c, s, w, h, dt, k) {
                c.fillStyle = k.col; halo(c, k, 1.5 * k.size);
                for (const d of s.dots) {
                    d.p += dt * 1.5 * k.speed; c.globalAlpha = .35 + .35 * Math.sin(d.p);
                    c.beginPath(); c.arc(d.x, d.y, d.r * k.size, 0, 7); c.fill();
                }
                s.next -= dt;
                if (s.next <= 0) { s.trails.push({ x: rand(0, w) - k.dx * w * .35, y: rand(0, h) - k.dy * h * .35, life: 1 }); s.next = rand(.8, 2.6) / k.amount; }
                const len = 110 * k.size * Math.min(1, w / 500);
                for (const t of s.trails) {
                    t.x += 580 * k.dx * dt * k.speed; t.y += 580 * k.dy * dt * k.speed; t.life -= dt * .9 * k.speed;
                    const tx = t.x - k.dx * len, ty = t.y - k.dy * len, g = c.createLinearGradient(t.x, t.y, tx, ty);
                    g.addColorStop(0, k.col); g.addColorStop(1, 'transparent');
                    c.globalAlpha = Math.max(0, t.life); c.strokeStyle = g; c.lineWidth = 1.6 * k.size;
                    c.beginPath(); c.moveTo(t.x, t.y); c.lineTo(tx, ty); c.stroke();
                }
                s.trails = s.trails.filter((t) => t.life > 0);
            },
        },
        snow: {
            make: (w, h, a) => Array.from({ length: area(w, h, 16, a) }, () => ({ x: rand(0, w), y: rand(0, h), r: rand(1, 3.2), v: rand(18, 46), p: rand(0, 6) })),
            step(c, s, w, h, dt, k) {
                c.fillStyle = k.col; halo(c, k, 3 * k.size);
                for (const f of s) {
                    f.p += dt; const sway = Math.sin(f.p) * 12;
                    f.x += (k.dx * f.v - k.dy * sway) * dt * k.speed; f.y += (k.dy * f.v + k.dx * sway) * dt * k.speed; wrap(f, w, h, 6);
                    c.globalAlpha = .55 + f.r / 8; c.beginPath(); c.arc(f.x, f.y, f.r * k.size, 0, 7); c.fill();
                }
            },
        },
        fireflies: {
            make: (w, h, a) => Array.from({ length: area(w, h, 4, a) }, () => ({ x: rand(0, w), y: rand(h * .3, h), a: rand(0, 6), p: rand(0, 6) })),
            step(c, s, w, h, dt, k) {
                const r = 14 * k.size * (1 + k.glow);
                for (const f of s) {
                    f.a += rand(-1, 1) * dt * 2; f.p += dt * 2.2 * k.speed;
                    f.x += (Math.cos(f.a) * 22 + k.dx * 14) * dt * k.speed; f.y += (Math.sin(f.a) * 16 + k.dy * 14) * dt * k.speed; wrap(f, w, h, r);
                    const g = c.createRadialGradient(f.x, f.y, 0, f.x, f.y, r); g.addColorStop(0, k.col); g.addColorStop(1, 'transparent');
                    c.globalAlpha = .25 + .75 * Math.max(0, Math.sin(f.p)); c.fillStyle = g; c.fillRect(f.x - r, f.y - r, r * 2, r * 2);
                }
            },
        },
        rain: {
            make: (w, h, a) => Array.from({ length: area(w, h, 22, a) }, () => ({ x: rand(0, w), y: rand(0, h), l: rand(8, 18), v: rand(420, 640) })),
            step(c, s, w, h, dt, k) {
                c.strokeStyle = k.col; c.lineWidth = k.size; c.globalAlpha = .45; halo(c, k, 2 * k.size); c.beginPath();
                for (const d of s) {
                    const l = d.l * k.size;
                    d.x += k.dx * d.v * dt * k.speed; d.y += k.dy * d.v * dt * k.speed; wrap(d, w, h, l);
                    c.moveTo(d.x, d.y); c.lineTo(d.x - k.dx * l, d.y - k.dy * l);
                }
                c.stroke();
            },
        },
        aurora: {
            make: () => ({ t: rand(0, 50), dx: 0, dy: 0 }),
            step(c, s, w, h, dt, k) {
                s.t += dt * .25 * k.speed; s.dx += k.dx * dt * 40 * k.speed; s.dy += k.dy * dt * 20 * k.speed;
                k.bands.forEach((col, i) => {
                    const x = ((w * (.5 + .38 * Math.sin(s.t + i * 2.1)) + s.dx) % (w * 1.4) + w * 1.4) % (w * 1.4) - w * .2;
                    const y = ((h * (.25 + .12 * Math.cos(s.t * .8 + i)) + s.dy) % (h * 1.4) + h * 1.4) % (h * 1.4) - h * .2;
                    const r = Math.max(w, h) * (.3 + .15 * k.size), g = c.createRadialGradient(x, y, 0, x, y, r);
                    g.addColorStop(0, col); g.addColorStop(1, 'transparent');
                    c.globalAlpha = Math.min(1, .5 * k.amount ** .5 * (1 + k.glow * .6)); c.fillStyle = g; c.fillRect(0, 0, w, h);
                });
            },
        },
        bubbles: {
            make: (w, h, a) => Array.from({ length: area(w, h, 5, a) }, () => ({ x: rand(0, w), y: rand(0, h), r: rand(4, 16), v: rand(14, 34), p: rand(0, 6) })),
            step(c, s, w, h, dt, k) {
                c.strokeStyle = k.col; c.lineWidth = 1.2; halo(c, k, 3);
                for (const b of s) {
                    const r = b.r * k.size; b.p += dt; const sway = Math.sin(b.p) * 10;
                    b.x += (k.dx * b.v - k.dy * sway) * dt * k.speed; b.y += (k.dy * b.v + k.dx * sway) * dt * k.speed; wrap(b, w, h, r);
                    c.globalAlpha = .5; c.beginPath(); c.arc(b.x, b.y, r, 0, 7); c.stroke();
                }
            },
        },
    };

    const runs = new Set();
    let frame = 0;

    /* The settings of one canvas; opts() is read every frame, so the editor's sliders apply at once. */
    function draw(run, now) {
        const o = run.opts();
        const cv = run.canvas;
        if (!o || !FX[o.type]) { if (run.drawn !== 'none') { cv.getContext('2d').clearRect(0, 0, cv.width, cv.height); run.drawn = 'none'; } return; }
        const r = cv.getBoundingClientRect();
        if (!r.width || !r.height) return;
        const narrow = r.width < 600;
        if (narrow && now - run.at < 1000 / 30) return;
        const step = Math.min(.05, (now - (run.at || now)) / 1000);
        run.at = now;
        const dpr = Math.min(1.5, window.devicePixelRatio || 1);
        const key = `${o.type}|${o.amount}|${Math.round(r.width)}x${Math.round(r.height)}`;
        if (run.key !== key) {
            cv.width = Math.round(r.width * dpr); cv.height = Math.round(r.height * dpr);
            run.key = key; run.pieces = FX[o.type].make(r.width, r.height, o.amount / 100 * (narrow && !o.thumb ? .6 : 1)); run.drawn = '';
        }
        if (still && run.drawn === key) return;
        const c = cv.getContext('2d');
        c.setTransform(dpr, 0, 0, dpr, 0, 0); c.clearRect(0, 0, r.width, r.height);
        cv.style.opacity = String(o.strength / 100);
        const rad = o.dir * Math.PI / 180;
        FX[o.type].step(c, run.pieces, r.width, r.height, still ? 0 : step, {
            amount: o.amount / 100, speed: o.speed / 100, size: o.size / 100, glow: o.glow / 100,
            dx: Math.sin(rad), dy: -Math.cos(rad), col: o.col, bands: o.bands,
        });
        c.globalAlpha = 1; c.shadowBlur = 0;
        run.drawn = key;
    }

    function tick(now) {
        frame = 0;
        if (document.hidden) return;
        for (const run of runs) {
            // The panel's own canvas is off the page for a moment while a menu click swaps it (it comes back below).
            if (!run.canvas.isConnected) { if (!run.page) runs.delete(run); continue; }
            draw(run, now);
        }
        if (runs.size) frame = requestAnimationFrame(tick);
    }
    const wake = () => { if (!frame && !document.hidden && runs.size) frame = requestAnimationFrame(tick); };
    document.addEventListener('visibilitychange', wake);

    /* The panel's own canvas: its settings are in data-fx, with colours for light and dark mode. While it runs, the
       page's body lets it show through; the body's own colour moves to the page behind it. */
    const mounted = new WeakSet();
    let page = null;
    function mountPage() {
        const cv = document.getElementById('simplify-themes-fx');
        if (!cv || mounted.has(cv)) return;
        // A menu click (wire:navigate) draws a new page with a new canvas. With the same effect the running canvas
        // takes its place, so the effect goes on instead of starting again (TH-B17).
        if (page && page.fx === cv.dataset.fx) { cv.replaceWith(page.cv); page.behind(); return; }
        if (page) { runs.delete(page.run); page.observer.disconnect(); }
        mounted.add(cv);
        let fx;
        try { fx = JSON.parse(cv.dataset.fx || ''); } catch (e) { return; }
        const html = document.documentElement;
        const behind = () => {
            html.classList.remove('st-fx-on');
            html.style.backgroundColor = '';
            const body = getComputedStyle(document.body).backgroundColor;
            if (body && body !== 'transparent' && body !== 'rgba(0, 0, 0, 0)') html.style.backgroundColor = body;
            html.classList.add('st-fx-on');
        };
        let dark = html.classList.contains('dark');
        behind();
        // Only a switch between light and dark changes the body's colour; our own class change is ignored.
        const observer = new MutationObserver(() => { if (html.classList.contains('dark') !== dark) { dark = !dark; behind(); } });
        observer.observe(html, { attributes: true, attributeFilter: ['class'] });
        const r = run(cv, () => ({ ...fx, ...fx[html.classList.contains('dark') ? 'dark' : 'light'] }));
        r.page = true;
        page = { cv, fx: cv.dataset.fx, run: r, observer, behind };
    }

    function run(canvas, opts) {
        const r = { canvas, opts, key: '', at: 0, drawn: '' };
        r.stop = () => runs.delete(r);
        runs.add(r);
        wake();
        return r;
    }

    window.SimplifyThemesFx = { run, mountPage, types: Object.keys(FX) };
    mountPage();
    document.addEventListener('livewire:navigated', mountPage);
    window.dispatchEvent(new Event('simplify-themes-fx'));
})();
