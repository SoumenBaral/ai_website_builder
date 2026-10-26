/**
 * Tablet / mobile preview in the editor: Tailwind's sm:/md:/lg: rules read the
 * viewport, but the preview canvas is a 768px / 390px box inside a wide window.
 * Every breakpoint rule therefore gets a twin `@container site (min-width: …)`
 * rule, and the viewport version is switched off inside a device preview
 * (`.site-canvas.is-device`, which is the `site` container). Blocks then lay out
 * exactly as the published site does on a real tablet or phone. Everywhere else
 * (the editor UI, the desktop preview) nothing changes: :where() adds no
 * specificity and the container rules only match inside the preview.
 */
const BREAKPOINT = /^\(min-width:\s*(\d+(?:\.\d+)?(?:px|rem|em))\)$/;
const OUTSIDE_DEVICE_PREVIEW = ':where(:not(.site-canvas.is-device *))';

function outsideDevicePreview(selector) {
    const pseudo = selector.search(/::|:(?:before|after|placeholder|selection|marker|file-selector-button)\b/);
    return pseudo === -1
        ? selector + OUTSIDE_DEVICE_PREVIEW
        : selector.slice(0, pseudo) + OUTSIDE_DEVICE_PREVIEW + selector.slice(pseudo);
}

const canvasContainerQueries = () => ({
    postcssPlugin: 'canvas-container-queries',
    OnceExit(root, { AtRule }) {
        root.walkAtRules('media', (media) => {
            const match = BREAKPOINT.exec(media.params.trim());
            if (!match || media.parent?.type === 'atrule' || media.nodes?.some((n) => n.type !== 'rule')) return;
            const container = new AtRule({ name: 'container', params: `site (min-width: ${match[1]})` });
            media.each((rule) => { container.append(rule.clone()); });
            media.each((rule) => { rule.selectors = rule.selectors.map(outsideDevicePreview); });
            media.after(container);
        });
    },
});
canvasContainerQueries.postcss = true;

export default {
    plugins: [
        (await import('tailwindcss')).default,
        (await import('autoprefixer')).default,
        canvasContainerQueries,
    ],
};
