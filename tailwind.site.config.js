/**
 * Tailwind for PUBLISHED sites (and the demo / ZIP exports) — compiled once at build time to
 * public/site/site.css instead of running the Tailwind Play CDN in every visitor's browser.
 * Same defaults as the CDN (no app theme, no forms plugin) so pages look exactly the same.
 */
/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/views/export/**/*.blade.php',
        './resources/js/site/**/*.js',
        './app/Services/ExportService.php',
    ],
    // Classes built from values at render time (hero heights).
    safelist: ['md:min-h-[300px]', 'md:min-h-[400px]', 'md:min-h-[500px]', 'md:min-h-[100vh]'],
    theme: { extend: {} },
    plugins: [],
};
