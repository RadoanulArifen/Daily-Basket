import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/storefront.css',
                'resources/js/storefront.js',
                'resources/css/admin.css',
                'resources/js/admin.js',
                // Storefront themes (resources/themes/<name>)
                'resources/themes/furnivo/css/theme.css',
                'resources/themes/furnivo/js/theme.js',
                'resources/themes/freshnest/css/theme.css',
                'resources/themes/freshnest/js/theme.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    build: {
        rollupOptions: {
            output: {
                // Alpine (from Livewire's ES build, see resources/js/alpine.js) in its own file, so
                // pages share one cached copy and the bundle names say what they hold.
                manualChunks(id) {
                    if (id.includes('livewire.esm')) return 'alpine';
                },
            },
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
