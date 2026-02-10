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
        watch: {
            ignored: [
                '**/node_modules/**',
                '**/vendor/**',
                '**/public/**',
                '**/storage/**',
                '**/.git/**',
                '**/.idea/**',
                '**/city-generator/**',
            ],
        },
    },
});