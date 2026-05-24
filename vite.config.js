import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: ['resources/views/**', 'routes/**', 'app/**'],
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

    server: {
        host: '0.0.0.0',
        port: 5173,
        hmr: {
            host: 'localhost',
            clientPort: 5300,
        },
        watch: {
            usePolling: true,
            interval: 300,
            ignored: ['**/vendor/**', '**/storage/**', '**/node_modules/**'],
        },
    },
});
