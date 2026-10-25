import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                // Framework code changes rarely: keep it in its own long-cached chunk.
                manualChunks(id) {
                    if (/node_modules\/(vue|@vue|@inertiajs|pinia|axios)\//.test(id)) return 'vendor';
                },
            },
        },
    },
    test: {
        environment: 'node',
        include: ['resources/js/**/*.test.js'],
    },
});
