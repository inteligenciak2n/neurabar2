import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { translationManifestPlugin } from './scripts/vite-translation-manifest.mjs';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const port = Number(env.VITE_PORT || 5173);
    const polling = env.VITE_USE_POLLING !== 'false';

    return {
        server: {
            host: '0.0.0.0',
            port,
            strictPort: true,
            hmr: {
                host: 'localhost',
                port,
                clientPort: port,
            },
            watch: polling
                ? {
                    usePolling: true,
                    interval: 1000,
                }
                : undefined,
        },
        plugins: [
            translationManifestPlugin(),
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
    };
});
