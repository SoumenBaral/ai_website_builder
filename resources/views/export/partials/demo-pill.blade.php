<style>
    .demo-pill {
        position: fixed; left: max(1rem, env(safe-area-inset-left)); bottom: max(1rem, env(safe-area-inset-bottom)); z-index: 55;
        display: flex; align-items: center; gap: .6rem; padding: .45rem .5rem .45rem .55rem; border-radius: 9999px;
        background: rgb(17 17 22 / .86); color: #fff; font: 500 13px/1.2 Inter, ui-sans-serif, system-ui, sans-serif;
        -webkit-backdrop-filter: blur(14px); backdrop-filter: blur(14px);
        box-shadow: 0 18px 40px -16px rgb(0 0 0 / .55), inset 0 0 0 1px rgb(255 255 255 / .08);
        animation: demo-pill-in .7s cubic-bezier(.22,1,.36,1) 1.2s both;
    }
    .demo-pill-mark { display: inline-flex; width: 1.6rem; height: 1.6rem; align-items: center; justify-content: center; border-radius: 9999px; background: linear-gradient(135deg, #6366f1, #d946ef); }
    .demo-pill-mark svg { width: .9rem; height: .9rem; }
    .demo-pill a { color: inherit; text-decoration: none; }
    .demo-pill-cta { padding: .4rem .8rem; border-radius: 9999px; background: #fff; color: #111 !important; font-weight: 600; white-space: nowrap; transition: transform .2s; }
    .demo-pill-cta:hover { transform: translateY(-1px); }
    .demo-pill-close { width: 1.6rem; height: 1.6rem; border-radius: 9999px; color: rgb(255 255 255 / .6); }
    .demo-pill-close:hover { color: #fff; background: rgb(255 255 255 / .1); }
    /* After a moment it folds into a small badge (expands again on hover / focus). */
    .demo-pill { transition: padding .35s cubic-bezier(.22,1,.36,1); }
    .demo-pill .demo-pill-text, .demo-pill .demo-pill-cta, .demo-pill .demo-pill-close { transition: opacity .25s, max-width .35s cubic-bezier(.22,1,.36,1); max-width: 22rem; overflow: hidden; white-space: nowrap; }
    .demo-pill.is-folded:not(:hover):not(:focus-within) { gap: 0; padding: .4rem; }
    .demo-pill.is-folded:not(:hover):not(:focus-within) .demo-pill-text,
    .demo-pill.is-folded:not(:hover):not(:focus-within) .demo-pill-cta,
    .demo-pill.is-folded:not(:hover):not(:focus-within) .demo-pill-close { max-width: 0; opacity: 0; padding: 0; }
    @keyframes demo-pill-in { from { opacity: 0; transform: translateY(1rem); } }
    @media (max-width: 640px) { .demo-pill-text { display: none; } }
    html.sc-has-bar .demo-pill { bottom: calc(5.75rem + env(safe-area-inset-bottom)); }
    @media (min-width: 768px) { html.sc-has-bar .demo-pill { bottom: 1rem; } }
    @media (prefers-reduced-motion: reduce) { .demo-pill { animation: none; } }
</style>
<div class="demo-pill" role="complementary" aria-label="About this demo">
    <a href="{{ $home }}" class="demo-pill-mark" title="VibeWeb"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9z"/></svg></a>
    <span class="demo-pill-text">Demo site — built with <strong>VibeWeb</strong> in minutes</span>
    <a href="{{ $signup }}" class="demo-pill-cta">Build yours free →</a>
    <button type="button" class="demo-pill-close" aria-label="Hide" onclick="this.parentNode.remove()">✕</button>
</div>
<script>setTimeout(function () { var p = document.querySelector('.demo-pill'); if (p) p.classList.add('is-folded'); }, 6500);</script>
