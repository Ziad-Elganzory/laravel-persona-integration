import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            ssr: 'resources/js/ssr.tsx',
            refresh: true,
        }),
        react({
            babel: {
                plugins: ['babel-plugin-react-compiler'],
            },
        }),
        tailwindcss(),
        wayfinder({
            formVariants: true,
        }),
    ],
    esbuild: {
        jsx: 'automatic',
    },
    // Enable CORS and configure HMR so the Vite client can be loaded from other origins
    server: {
        cors: true,
        // If you're using a separate dev server origin (like 127.0.0.1:5173) or ngrok,
        // ensure the HMR client uses the correct host and protocol.
        hmr: {
            host: '127.0.0.1',
            protocol: 'ws',
        },
    },
});
