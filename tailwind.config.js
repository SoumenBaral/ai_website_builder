import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

// Landing-page palette: values come from CSS variables in resources/css/landing.css,
// so every `lp-*` utility automatically follows the light/dark theme.
const lp = (name) => `rgb(var(--lp-${name}) / <alpha-value>)`;

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './resources/js/Components/Landing/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                serif: ['"Instrument Serif"', ...defaultTheme.fontFamily.serif],
            },
            colors: {
                lp: {
                    bg: lp('bg'),
                    surface: lp('surface'),
                    fg: lp('fg'),
                    text: lp('text'),
                    muted: lp('muted'),
                    subtle: lp('subtle'),
                    ink: lp('ink'),
                    accent: lp('accent'),
                },
            },
        },
    },

    plugins: [forms],
};
