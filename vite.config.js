import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.scss', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    css: {
        preprocessorOptions: {
            scss: {
                // Bootstrap 5.3 still ships @import-based partials.
                silenceDeprecations: ['import', 'global-builtin', 'color-functions'],
                quietDeps: true,
            },
        },
    },
});
