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

    server: {
        // Bind to all interfaces inside the Docker container
        host: '0.0.0.0',
        port: 5173,

        // Tell the browser where to connect for hot reload
        // "localhost" = your machine, 5173 = exposed port
        hmr: {
            host: 'localhost',
            port: 5173,
        },
    },
});
