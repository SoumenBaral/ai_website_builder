/*
 * Section entrance animations — ONE runtime shared by the editor canvas (imported by
 * BlockRenderer.vue) and published sites (inlined by resources/views/export/shell.blade.php).
 * Plain ES5 on purpose: it is pasted as-is into exported pages.
 *
 * A section wrapper carries  data-anim="fade-up" [data-anim-speed] [data-anim-stagger] [data-anim-whole].
 * Its CONTENT animates (headings, text, buttons, images, each card) while the section's
 * background stays put. Every element is revealed when it scrolls into view; elements that
 * enter together cascade when "one by one" (stagger) is on.
 *
 * Element state lives in data-at: "0" hidden · "1" animating in · "done" settled (hover effects
 * take over) · "skip" shown instantly (was already above the screen).
 */
(function (w) {
    if (w.SiteAnim || !w.document) return;

    var LEAF = /^(H[1-6]|P|IMG|PICTURE|VIDEO|SVG|FORM|BLOCKQUOTE|FIGURE|BUTTON|A|TABLE|IFRAME|DETAILS|HR|LABEL|INPUT|TEXTAREA|SELECT|SPAN|STRONG|EM|I|B|SMALL)$/;
    var SKIP = /^(SCRIPT|STYLE|TEMPLATE|NOSCRIPT|BR)$/;
    var DURATION = { fast: 0.5, normal: 0.8, slow: 1.2 };
    var STEP = 0.085; // seconds between elements when staggered
    var MAX_STEPS = 8; // cap the cascade so long lists don't drag
    var MAX_TARGETS = 60;
    var observer = null;

    function hasClass(el, c) {
        return (' ' + (el.getAttribute('class') || '') + ' ').indexOf(' ' + c + ' ') > -1;
    }

    /** Full-bleed photos/overlays are the section's background — keep them still. */
    function isBackdrop(el) {
        var abs = hasClass(el, 'absolute') || /position:\s*absolute/.test(el.getAttribute('style') || '');
        return abs && (hasClass(el, 'inset-0') || /inset:\s*0/.test(el.getAttribute('style') || ''));
    }

    /** A real list of cards (not a 2-column layout grid). */
    function isCardList(el) {
        var n = el.children.length;
        if (el.tagName === 'UL' || el.tagName === 'OL') return n > 0;
        if (!hasClass(el, 'grid') || n < 2) return false;
        if (n >= 3) return true;
        return (el.children[0].getAttribute('class') || '') === (el.children[1].getAttribute('class') || '');
    }

    function collect(el, out, depth) {
        for (var i = 0; i < el.children.length; i++) {
            var c = el.children[i];
            if (SKIP.test(c.tagName) || c.hasAttribute('data-anim-skip') || hasClass(c, 'fx-layer') || isBackdrop(c)) continue;
            if (out.length >= MAX_TARGETS) {
                out.push(c);
                continue;
            }
            if (isCardList(c)) {
                for (var j = 0; j < c.children.length; j++) out.push(c.children[j]);
            } else if (LEAF.test(c.tagName.toUpperCase()) || depth >= 6 || !c.children.length) {
                out.push(c);
            } else {
                collect(c, out, depth + 1);
            }
        }
        return out;
    }

    function targetsOf(section) {
        var root = section.firstElementChild;
        if (!root) return [];
        if (section.hasAttribute('data-anim-whole')) return [root];
        var list = collect(root, [], 0);
        return list.length ? list : [root];
    }

    function durationOf(section) {
        return DURATION[section.getAttribute('data-anim-speed')] || DURATION.normal;
    }

    function settle(el, after) {
        clearTimeout(el._animTimer);
        el._animTimer = setTimeout(function () {
            if (el.getAttribute('data-at') === '1') el.setAttribute('data-at', 'done');
        }, after * 1000 + 80);
    }

    /*
     * Visibility is judged from each element's RESTING position (offsetTop ignores transforms),
     * so start poses that sit outside a clipping box — e.g. "drop in from 120px above" — still
     * trigger. An IntersectionObserver per section switches a cheap scroll check on/off.
     */
    var active = [];
    var queued = false;

    function layoutTop(el) {
        var y = 0;
        while (el) {
            y += el.offsetTop || 0;
            el = el.offsetParent;
        }
        return y;
    }

    function reveal(section, list) {
        list.sort(function (a, b) {
            return a.compareDocumentPosition(b) & 4 ? -1 : 1;
        });
        var stagger = section.hasAttribute('data-anim-stagger');
        var dur = durationOf(section);
        for (var k = 0; k < list.length; k++) {
            var el = list[k];
            var delay = stagger ? Math.min(k, MAX_STEPS) * STEP : 0;
            el.style.setProperty('--d', delay.toFixed(3) + 's');
            el.setAttribute('data-at', '1');
            settle(el, dur + delay);
        }
    }

    function check() {
        queued = false;
        var vh = w.innerHeight || 800;
        for (var i = active.length - 1; i >= 0; i--) {
            var section = active[i];
            var top = section.getBoundingClientRect().top;
            var base = layoutTop(section);
            var entering = [];
            var hidden = 0;
            var targets = section._animTargets || [];
            for (var j = 0; j < targets.length; j++) {
                var el = targets[j];
                if (el.getAttribute('data-at') !== '0') continue;
                var y = typeof el.offsetTop === 'number' ? top + layoutTop(el) - base : el.getBoundingClientRect().top;
                var bottom = y + (el.offsetHeight || 1);
                if (bottom <= 0) {
                    el.setAttribute('data-at', 'skip'); // already scrolled past: just show it
                } else if (y < vh * 0.92) {
                    entering.push(el);
                } else {
                    hidden++;
                }
            }
            if (entering.length) reveal(section, entering);
            if (!hidden) {
                active.splice(i, 1);
                if (observer) observer.unobserve(section);
            }
        }
        if (!active.length) stopListening();
    }

    function schedule() {
        if (!queued) {
            queued = true;
            (w.requestAnimationFrame || setTimeout)(check);
        }
    }

    var listening = false;
    function startListening() {
        if (listening) return;
        listening = true;
        // capture: also hears scrolling inside containers (the editor canvas)
        w.document.addEventListener('scroll', schedule, { capture: true, passive: true });
        w.addEventListener('resize', schedule, { passive: true });
    }
    function stopListening() {
        if (!listening) return;
        listening = false;
        w.document.removeEventListener('scroll', schedule, { capture: true });
        w.removeEventListener('resize', schedule);
    }

    function ensureObserver() {
        if (!observer) {
            observer = new IntersectionObserver(function (entries) {
                for (var i = 0; i < entries.length; i++) {
                    var section = entries[i].target;
                    var at = active.indexOf(section);
                    if (entries[i].isIntersecting && at < 0) active.push(section);
                    if (!entries[i].isIntersecting && at > -1) active.splice(at, 1);
                }
                if (active.length) startListening();
                schedule();
            }, { threshold: 0 });
        }
        return observer;
    }

    /** Clear a section's previous run (editor replays, content changes). */
    function reset(section) {
        if (observer) observer.unobserve(section);
        var at = active.indexOf(section);
        if (at > -1) active.splice(at, 1);
        var old = section._animTargets || [];
        for (var i = 0; i < old.length; i++) {
            clearTimeout(old[i]._animTimer);
            old[i].removeAttribute('data-at');
            old[i].style.removeProperty('--d');
        }
        section._animTargets = [];
    }

    /** Hide a section's content and reveal it as it scrolls into view. */
    function play(section) {
        if (!section || !('IntersectionObserver' in w)) return;
        reset(section);
        if (!section.getAttribute('data-anim')) return;
        applyCustom(section);
        var targets = targetsOf(section);
        section._animTargets = targets;
        section.setAttribute('data-anim-reset', '');
        for (var i = 0; i < targets.length; i++) targets[i].setAttribute('data-at', '0');
        void section.offsetHeight; // commit the hidden state without a transition
        section.removeAttribute('data-anim-reset');
        ensureObserver().observe(section);
    }

    /* ------------------------------------------------------------------
     | Custom entrance (animation="custom" + data-anim-custom JSON)
     * ------------------------------------------------------------------ */
    var EASE = {
        smooth: 'cubic-bezier(0.22, 1, 0.36, 1)',
        spring: 'cubic-bezier(0.34, 1.56, 0.64, 1)',
        sharp: 'cubic-bezier(0.7, 0, 0.3, 1)',
        linear: 'linear',
        bounce: 'linear(0, 0.063, 0.25, 0.563, 1 36.4%, 0.812, 0.75, 0.813, 1 72.7%, 0.953, 0.938, 0.953, 1 90.9%, 0.984, 1)',
        elastic: 'linear(0, 1.25 15%, 0.86 28%, 1.06 42%, 0.97 57%, 1.01 72%, 1)',
    };
    var hasLinearEasing = !!(w.CSS && w.CSS.supports && w.CSS.supports('transition-timing-function', 'linear(0, 1)'));

    function parse(json) {
        try {
            return JSON.parse(json || 'null');
        } catch (e) {
            return null;
        }
    }

    function num(v, min, max, d) {
        v = parseFloat(v);
        return isNaN(v) ? d : Math.max(min, Math.min(max, v));
    }

    function applyCustom(section) {
        var props = ['--anim-from', '--anim-blur', '--anim-op', '--anim-ease'];
        for (var i = 0; i < props.length; i++) section.style.removeProperty(props[i]);
        if (section.getAttribute('data-anim') !== 'custom') return;
        var c = parse(section.getAttribute('data-anim-custom'));
        if (!c) return;
        var t = 'perspective(900px) translate3d(' + num(c.x, -400, 400, 0) + 'px, ' + num(c.y, -400, 400, 0) + 'px, 0)' +
            ' rotate(' + num(c.rotate, -720, 720, 0) + 'deg) rotateX(' + num(c.rotateX, -180, 180, 0) + 'deg)' +
            ' rotateY(' + num(c.rotateY, -180, 180, 0) + 'deg) skewX(' + num(c.skewX, -45, 45, 0) + 'deg)' +
            ' scale(' + num(c.scale, 0.1, 3, 1) + ')';
        var ease = EASE[c.easing] || EASE.smooth;
        if (ease.indexOf('linear(') === 0 && !hasLinearEasing) ease = EASE.spring;
        section.style.setProperty('--anim-from', t);
        section.style.setProperty('--anim-blur', num(c.blur, 0, 30, 0) + 'px');
        section.style.setProperty('--anim-op', String(num(c.opacity, 0, 1, 0)));
        section.style.setProperty('--anim-ease', ease);
    }

    /* ------------------------------------------------------------------
     | Background effects (data-fx JSON): particles, gradient, blobs.
     | Drawn on a layer above the section's background and below its content.
     * ------------------------------------------------------------------ */
    var SVG = {
        star: '<path d="M12 2l2.9 6.9 7.1.6-5.4 4.7 1.7 7-6.3-3.9-6.3 3.9 1.7-7L2 9.5l7.1-.6z"/>',
        sparkle: '<path d="M12 0c.6 6.6 5.4 11.4 12 12-6.6.6-11.4 5.4-12 12-.6-6.6-5.4-11.4-12-12C6.6 11.4 11.4 6.6 12 0z"/>',
        heart: '<path d="M12 21s-7.5-4.6-10-9.3C.4 8.4 2.4 4 6.4 4c2.3 0 3.8 1.3 5.6 3.4C13.8 5.3 15.3 4 17.6 4c4 0 6 4.4 4.4 7.7C19.5 16.4 12 21 12 21z"/>',
        snowflake: '<g fill="none" stroke="#000" stroke-width="2" stroke-linecap="round"><path d="M12 2v20M3.3 7l17.4 10M3.3 17L20.7 7M9 4l3 2 3-2M9 20l3-2 3 2"/></g>',
        petal: '<path d="M12 2C6.5 7.5 6.5 15.5 12 22c5.5-6.5 5.5-14.5 0-20z"/>',
        leaf: '<path d="M4 20C4 9 10.5 3.5 21 3c-.5 10.5-6 17-17 17z"/>',
        triangle: '<path d="M12 3l10 18H2z"/>',
    };
    var BASE = { twinkle: 3.2, pulse: 6, drift: 18, fall: 11, 'sway-fall': 13, 'float-up': 13, 'rise-sway': 11, rain: 1.2, shoot: 7, spin: 9 };
    var SPEED = { slow: 1.6, normal: 1, fast: 0.6 };
    var FALLING = { fall: 1, 'sway-fall': 1, rain: 1 };
    var RISING = { 'float-up': 1, 'rise-sway': 1 };

    function rng(seed) {
        // mulberry32 — the same effect looks the same in the editor and on the site
        return function () {
            seed |= 0;
            seed = (seed + 0x6d2b79f5) | 0;
            var t = Math.imul(seed ^ (seed >>> 15), 1 | seed);
            t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
            return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
        };
    }

    function hash(str) {
        var h = 2166136261;
        for (var i = 0; i < str.length; i++) h = Math.imul(h ^ str.charCodeAt(i), 16777619);
        return h;
    }

    function el(tag, cls) {
        var e = w.document.createElement(tag);
        e.className = cls;
        return e;
    }

    function maskUrl(shape) {
        return 'url("data:image/svg+xml,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">' + SVG[shape] + '</svg>') + '")';
    }

    function particle(spec, r, i, layer) {
        var p = el('span', 'fx-p');
        var inner = el('span', 'fx-i');
        var size = spec.sizeMin + r() * (spec.sizeMax - spec.sizeMin);
        var color = spec.colors[i % spec.colors.length];
        var dur = BASE[spec.motion] * (SPEED[spec.speed] || 1) * (0.7 + r() * 0.6);
        var x = r() * 100;
        var y = r() * 100;
        if (FALLING[spec.motion]) y = -1;
        if (RISING[spec.motion]) y = 101;
        if (spec.motion === 'shoot') {
            y = r() * 55;
            x = 25 + r() * 80;
        }

        p.style.left = x.toFixed(2) + '%';
        p.style.top = y === -1 ? -(size * 3 + 10) + 'px' : y.toFixed(2) + '%';
        p.style.setProperty('--s', size.toFixed(1) + 'px');
        p.style.setProperty('--c', color);
        p.style.setProperty('--dur', dur.toFixed(2) + 's');
        p.style.setProperty('--delay', (-r() * dur).toFixed(2) + 's'); // start mid-flight
        p.style.setProperty('--dx', ((r() - 0.5) * 120).toFixed(0) + 'px');
        p.style.setProperty('--dy', ((r() - 0.5) * 80).toFixed(0) + 'px');
        p.style.setProperty('--rot', ((r() - 0.5) * 720).toFixed(0) + 'deg');
        p.style.setProperty('--sway', (10 + r() * 30).toFixed(0) + 'px');
        if (spec.glow) p.style.filter = 'drop-shadow(0 0 ' + Math.min(14, size * 0.6).toFixed(1) + 'px ' + color + ')';

        var shape = spec.motion === 'shoot' ? 'streak' : spec.shape;
        inner.setAttribute('data-shape', shape);
        if (SVG[shape]) {
            inner.style.webkitMaskImage = inner.style.maskImage = maskUrl(shape);
        } else if (shape === 'emoji') {
            inner.textContent = spec.emoji || '✨';
        }
        p.appendChild(inner);
        layer.appendChild(p);
    }

    function mountFx(host) {
        unmountFx(host);
        var spec = parse(host.getAttribute('data-fx'));
        var section = host.firstElementChild;
        if (!spec || !section || !spec.kind) return;
        spec.colors = spec.colors && spec.colors.length ? spec.colors : ['#ffffff'];
        spec.motion = BASE[spec.motion] ? spec.motion : 'twinkle';
        spec.sizeMin = num(spec.sizeMin, 2, 80, 4);
        spec.sizeMax = Math.max(spec.sizeMin, num(spec.sizeMax, 2, 120, 10));

        var layer = el('div', 'fx-layer');
        layer.setAttribute('aria-hidden', 'true');
        layer.setAttribute('data-kind', spec.kind);
        layer.setAttribute('data-motion', spec.motion);
        layer.style.opacity = String(num(spec.opacity, 5, 100, 80) / 100);
        var r = rng(hash(host.getAttribute('data-fx')));
        var speed = SPEED[spec.speed] || 1;

        if (spec.kind === 'gradient') {
            var c = spec.colors.concat(spec.colors.length < 3 ? [spec.colors[0]] : []);
            layer.style.backgroundImage = 'linear-gradient(120deg, ' + c.join(', ') + ', ' + c[0] + ')';
            layer.style.setProperty('--dur', (16 * speed).toFixed(1) + 's');
        } else if (spec.kind === 'blobs') {
            var n = Math.max(3, spec.colors.length);
            for (var b = 0; b < n; b++) {
                var blob = el('span', 'fx-blob');
                blob.style.left = (r() * 80 - 10).toFixed(1) + '%';
                blob.style.top = (r() * 70 - 20).toFixed(1) + '%';
                blob.style.setProperty('--c', spec.colors[b % spec.colors.length]);
                blob.style.setProperty('--dur', ((14 + r() * 10) * speed).toFixed(1) + 's');
                blob.style.setProperty('--delay', (-r() * 10).toFixed(1) + 's');
                blob.style.setProperty('--dx', ((r() - 0.5) * 30).toFixed(0) + '%');
                blob.style.setProperty('--dy', ((r() - 0.5) * 30).toFixed(0) + '%');
                layer.appendChild(blob);
            }
        } else {
            var count = Math.round(num(spec.count, 3, 120, 40));
            if (w.innerWidth < 640) count = Math.max(3, Math.round(count * 0.55)); // lighter on phones
            for (var i = 0; i < count; i++) particle(spec, r, i, layer);
        }

        section.setAttribute('data-fx-host', '');
        section.appendChild(layer);
        host._fxLayer = layer;

        // Falling/rising particles travel the section's real height; placement follows layout.
        var measure = function () {
            layer.style.setProperty('--fx-h', layer.offsetHeight + 'px');
            place(section, layer);
        };
        measure();
        // Published pages style themselves a moment after load (Tailwind CDN): check again.
        if (w.document.readyState !== 'complete') w.addEventListener('load', measure, { once: true });
        setTimeout(measure, 700);
        if ('ResizeObserver' in w) {
            host._fxResize = new ResizeObserver(measure);
            host._fxResize.observe(layer);
        }
        // Save battery: pause while off screen.
        if ('IntersectionObserver' in w) {
            host._fxSeen = new IntersectionObserver(function (entries) {
                layer.setAttribute('data-paused', entries[0].isIntersecting ? 'false' : 'true');
            });
            host._fxSeen.observe(layer);
        }
    }

    function opaque(el) {
        if (/^(IMG|VIDEO|PICTURE|CANVAS|IFRAME)$/.test(el.tagName)) return true;
        var c = w.getComputedStyle(el);
        if (c.backgroundImage && c.backgroundImage !== 'none') return true;
        var m = /rgba?\(([^)]+)\)/.exec(c.backgroundColor || '');
        if (!m) return false;
        var parts = m[1].split(/[\s,\/]+/);
        return parts.length < 4 || parseFloat(parts[3]) > 0.5;
    }

    /**
     * Behind the content when the section itself shows the background; on top of it (clicks
     * pass through) when inner columns / photos paint over the section and would hide the layer.
     * Coverage is sampled on a grid against every large painted element, at any depth — hero
     * layouts often wrap their coloured columns in an extra div.
     */
    function place(section, layer) {
        var box = section.getBoundingClientRect();
        if (!box.width || !box.height) return;
        var painted = [];
        var walk = function (el, depth) {
            for (var i = 0; i < el.children.length; i++) {
                var c = el.children[i];
                if (c === layer || SKIP.test(c.tagName)) continue;
                var r = c.getBoundingClientRect();
                if (r.width * r.height < box.width * box.height * 0.08) continue; // small things don't hide a background
                if (opaque(c)) painted.push(r);
                else if (depth < 4) walk(c, depth + 1);
            }
        };
        walk(section, 0);

        var hits = 0;
        var N = 8;
        for (var y = 0; y < N; y++) {
            for (var x = 0; x < N; x++) {
                var px = box.left + ((x + 0.5) / N) * box.width;
                var py = box.top + ((y + 0.5) / N) * box.height;
                for (var k = 0; k < painted.length; k++) {
                    var r = painted[k];
                    if (px >= r.left && px <= r.right && py >= r.top && py <= r.bottom) {
                        hits++;
                        break;
                    }
                }
            }
        }
        layer.setAttribute('data-place', hits / (N * N) > 0.4 ? 'over' : 'under');
    }

    function unmountFx(host) {
        if (host._fxResize) host._fxResize.disconnect();
        if (host._fxSeen) host._fxSeen.disconnect();
        if (host._fxLayer && host._fxLayer.parentNode) host._fxLayer.parentNode.removeChild(host._fxLayer);
        host._fxLayer = host._fxResize = host._fxSeen = null;
    }

    /* ------------------------------------------------------------------
     | Button effects (data-btn-fx on the section wrapper)
     * ------------------------------------------------------------------ */
    var DISPLAY_CLASS = /(^|\s)(inline-block|block|flex|inline-flex|grid|inline-grid)(\s|$)/;

    /** A link or <button> drawn as a button: it paints its own background or border. */
    function isButton(el) {
        var style = el.getAttribute('style') || '';
        return /background|border\s*:/.test(style) || /(^|\s)(bg-|border)/.test(el.getAttribute('class') || '');
    }

    function markButtons(host) {
        var old = host.querySelectorAll('[data-btn]');
        for (var i = 0; i < old.length; i++) old[i].removeAttribute('data-btn');
        if (!host.getAttribute('data-btn-fx')) return;
        var candidates = host.querySelectorAll('a, button');
        for (var j = 0; j < candidates.length; j++) {
            var b = candidates[j];
            if (!isButton(b) || b.closest('.fx-layer')) continue;
            // The ring/glow takes the button's own colour (white on brand bands, brand elsewhere).
            var cs = w.getComputedStyle(b);
            var bg = cs.backgroundColor;
            var color = bg && !/rgba\(0, 0, 0, 0\)|transparent/.test(bg) ? bg : cs.borderTopColor || cs.color;
            b.style.setProperty('--btn-c', color);
            b.setAttribute('data-btn', DISPLAY_CLASS.test(b.getAttribute('class') || '') ? '' : 'inline');
            b.style.setProperty('--btn-delay', (j % 4) * 0.35 + 's'); // neighbours don't move in lockstep
        }
    }

    /* ------------------------------------------------------------------
     | Heading effects (data-text-fx on the section wrapper)
     |  typing · typing-loop · words · letters · gradient · highlight
     | The heading's text stays in the HTML (SEO, no-JS); effects only wrap it at runtime and
     | can always be undone (stopText), which the editor relies on to keep Vue's nodes intact.
     * ------------------------------------------------------------------ */
    var CHAR_MS = { fast: 26, normal: 46, slow: 80 };
    var WORD_MS = { fast: 55, normal: 95, slow: 150 };
    // Scripts whose letters join/shape (Bangla, Hindi, Arabic…) animate per word, never per letter.
    var COMPLEX_SCRIPT = /[\u0590-\u08FF\u0900-\u0DFF\u0E00-\u0FFF\u1000-\u109F]/;

    function headingOf(host) {
        var sec = host.firstElementChild;
        return sec ? sec.querySelector('h1') || sec.querySelector('h2') : null;
    }

    /** Swap the heading's content for a wrapped copy: words → .tx-w, letters → .tx-c. */
    function split(h, perLetter) {
        h._txOriginal = Array.prototype.slice.call(h.childNodes);
        var copy = w.document.createElement('div');
        for (var i = 0; i < h._txOriginal.length; i++) copy.appendChild(h._txOriginal[i].cloneNode(true));
        var texts = [];
        var walker = w.document.createTreeWalker(copy, 4, null);
        while (walker.nextNode()) texts.push(walker.currentNode);
        var units = [];
        for (var t = 0; t < texts.length; t++) {
            var frag = w.document.createDocumentFragment();
            var parts = texts[t].nodeValue.split(/(\s+)/);
            for (var p = 0; p < parts.length; p++) {
                if (!parts[p]) continue;
                if (/^\s+$/.test(parts[p])) {
                    frag.appendChild(w.document.createTextNode(parts[p]));
                    continue;
                }
                var word = el('span', 'tx-w');
                if (perLetter && !COMPLEX_SCRIPT.test(parts[p])) {
                    var chars = Array.from ? Array.from(parts[p]) : parts[p].split('');
                    for (var c = 0; c < chars.length; c++) {
                        var ch = el('span', 'tx-c tx-u');
                        ch.textContent = chars[c];
                        word.appendChild(ch);
                        units.push(ch);
                    }
                } else {
                    word.textContent = parts[p];
                    word.className = 'tx-w tx-u';
                    units.push(word);
                }
                frag.appendChild(word);
            }
            texts[t].parentNode.replaceChild(frag, texts[t]);
        }
        h.textContent = '';
        while (copy.firstChild) h.appendChild(copy.firstChild);
        return units;
    }

    function stopText(host) {
        var st = host._tx;
        if (!st) return;
        host._tx = null;
        for (var i = 0; i < st.timers.length; i++) clearTimeout(st.timers[i]);
        if (st.io) st.io.disconnect();
        var h = st.h;
        if (h._txOriginal) {
            h.textContent = '';
            for (var j = 0; j < h._txOriginal.length; j++) h.appendChild(h._txOriginal[j]); // the very same nodes
            h._txOriginal = null;
        }
        h.removeAttribute('data-tx');
        h.style.removeProperty('min-height');
        if (st.onEdit) {
            h.removeEventListener('focusin', st.onEdit);
            h.removeEventListener('pointerdown', st.onEdit);
        }
    }

    function later(st, fn, ms) {
        st.timers.push(setTimeout(fn, ms));
    }

    function runTyping(st, units, step, done) {
        var caret = el('span', 'tx-caret');
        st.h.setAttribute('data-tx', 'typing');
        if (units[0]) units[0].parentNode.insertBefore(caret, units[0]);
        var i = 0;
        (function next() {
            if (!st.live) return;
            if (i >= units.length) return done && done(caret);
            var u = units[i++];
            u.classList.add('tx-on');
            u.parentNode.insertBefore(caret, u.nextSibling);
            var pause = /[.,!?;:—]$/.test(u.textContent) ? step * 6 : step; // linger on punctuation
            later(st, next, pause * (0.65 + Math.random() * 0.7)); // human rhythm
        })();
    }

    /** typing-loop: type → hold → delete → next phrase, forever (preview: one round). */
    function runLoop(st, phrases, step, preview) {
        var h = st.h;
        // Reserve the tallest phrase's height so the page never jumps.
        var tallest = 0;
        for (var m = 0; m < phrases.length; m++) {
            h.textContent = phrases[m];
            tallest = Math.max(tallest, h.offsetHeight);
        }
        h.style.minHeight = tallest + 'px';
        h.setAttribute('data-tx', 'typing');
        h.textContent = '';
        var text = w.document.createTextNode('');
        var caret = el('span', 'tx-caret tx-caret--live');
        h.appendChild(text);
        h.appendChild(caret);
        var k = 0;
        var chars = [];
        function type() {
            if (!st.live) return;
            chars = Array.from ? Array.from(phrases[k]) : phrases[k].split('');
            var i = 0;
            (function add() {
                if (!st.live) return;
                if (i < chars.length) {
                    text.nodeValue += chars[i++];
                    return later(st, add, step * (0.65 + Math.random() * 0.7));
                }
                if (preview && k === phrases.length - 1) return later(st, function () { stopText(st.host); }, 1400);
                later(st, erase, 1900);
            })();
        }
        function erase() {
            if (!st.live) return;
            if (text.nodeValue.length) {
                text.nodeValue = (Array.from ? Array.from(text.nodeValue) : text.nodeValue.split('')).slice(0, -1).join('');
                return later(st, erase, step * 0.45);
            }
            k = (k + 1) % phrases.length;
            later(st, type, 350);
        }
        type();
    }

    function runText(host, preview) {
        var fx = host.getAttribute('data-text-fx');
        var h = headingOf(host);
        if (!h || !fx) return;
        var speed = host.getAttribute('data-text-speed') || 'normal';
        var st = { host: host, h: h, timers: [], live: true };
        host._tx = st;

        if (preview) {
            // Editing the heading must never fight the effect: hand the original nodes back at once.
            st.onEdit = function () {
                stopText(host);
            };
            h.addEventListener('focusin', st.onEdit);
            h.addEventListener('pointerdown', st.onEdit);
        }

        if (fx === 'gradient') {
            h.setAttribute('data-tx', 'gradient');
            return;
        }
        if (fx === 'typing-loop') {
            var phrases = [h.textContent.replace(/\s+/g, ' ').trim()].concat(parse(host.getAttribute('data-text-phrases')) || []);
            h._txOriginal = Array.prototype.slice.call(h.childNodes);
            return runLoop(st, phrases, CHAR_MS[speed] || CHAR_MS.normal, preview);
        }
        if (fx === 'highlight') {
            h._txOriginal = Array.prototype.slice.call(h.childNodes);
            var mark = el('span', 'tx-hl');
            for (var i = 0; i < h._txOriginal.length; i++) mark.appendChild(h._txOriginal[i].cloneNode(true));
            h.textContent = '';
            h.appendChild(mark);
            h.setAttribute('data-tx', 'highlight');
            later(st, function () {
                h.setAttribute('data-tx', 'highlight-on');
            }, 60);
            if (preview) later(st, function () { stopText(host); }, 2600);
            return;
        }

        var perLetter = fx === 'typing' || fx === 'letters';
        var units = split(h, perLetter);
        if (fx === 'typing') {
            var step = units.length && !units[0].classList.contains('tx-c') ? WORD_MS[speed] || WORD_MS.normal : CHAR_MS[speed] || CHAR_MS.normal;
            runTyping(st, units, step, function () {
                if (preview) later(st, function () { stopText(host); }, 1600);
            });
            return;
        }
        // words / letters: staggered reveal
        h.setAttribute('data-tx', fx);
        var gap = fx === 'words' ? (WORD_MS[speed] || 95) : (CHAR_MS[speed] || 46) * 0.6;
        for (var u = 0; u < units.length; u++) units[u].style.setProperty('--tx-d', (u * gap) / 1000 + 's');
        void h.offsetHeight;
        later(st, function () {
            h.setAttribute('data-tx', fx + '-on');
        }, 30);
        if (preview) later(st, function () { stopText(host); }, units.length * gap + 1200);
    }

    /**
     * Start the heading effect when the section comes into view.
     * preview=true (editor): play once, then restore the original DOM.
     */
    function textFx(host, preview) {
        stopText(host);
        if (!host.getAttribute('data-text-fx') || !headingOf(host)) return;
        if (w.matchMedia && w.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            if (host.getAttribute('data-text-fx') === 'gradient') headingOf(host).setAttribute('data-tx', 'gradient');
            return;
        }
        var st = { host: host, h: headingOf(host), timers: [], live: true };
        host._tx = st;
        var start = function () {
            if (host._tx !== st) return;
            st.live = false;
            host._tx = null;
            runText(host, preview);
        };
        if (!('IntersectionObserver' in w)) return start();
        st.io = new IntersectionObserver(function (entries) {
            if (!entries[0].isIntersecting) return;
            st.io.disconnect();
            // Let the section's entrance animation show the heading first.
            later(st, start, host.getAttribute('data-anim') ? 260 : 60);
        }, { threshold: 0.15 });
        st.io.observe(host);
    }

    /** Published pages: background effects + entrance animations for every section. */
    function init() {
        var textHosts = w.document.querySelectorAll('[data-text-fx]');
        for (var th = 0; th < textHosts.length; th++) textFx(textHosts[th], false);
        var btnHosts = w.document.querySelectorAll('[data-btn-fx]');
        for (var bh = 0; bh < btnHosts.length; bh++) markButtons(btnHosts[bh]);
        var effects = w.document.querySelectorAll('[data-fx]');
        for (var f = 0; f < effects.length; f++) mountFx(effects[f]); // static when reduced motion (CSS)
        if (!('IntersectionObserver' in w) || (w.matchMedia && w.matchMedia('(prefers-reduced-motion: reduce)').matches)) return;
        w.document.documentElement.className += ' anim-on';
        var sections = w.document.querySelectorAll('[data-anim]');
        for (var i = 0; i < sections.length; i++) play(sections[i]);
    }

    w.SiteAnim = { init: init, play: play, reset: reset, targetsOf: targetsOf, mountFx: mountFx, unmountFx: unmountFx, markButtons: markButtons, textFx: textFx, stopText: stopText };
})(typeof window !== 'undefined' ? window : {});
