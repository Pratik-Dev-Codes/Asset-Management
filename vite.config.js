import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import { resolve } from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.jsx',
            refresh: true,
            ssr: {
                noExternal: ['@inertiajs/server'],
            },
        }),
        react({
            include: '**/*.jsx',
            babel: {
                plugins: ['@babel/plugin-transform-react-jsx'],
            },
        }),
    ],
    resolve: {
        alias: {
            '@': resolve('./resources/js'),
        },
        extensions: ['.js', '.jsx', '.json'],
    },
    optimizeDeps: {
        include: ['react', 'react-dom', '@inertiajs/react'],
        exclude: ['ziggy-js'],
    },
    build: {
        chunkSizeWarningLimit: 1000,
        commonjsOptions: {
            transformMixedEsModules: true,
        },
        rollupOptions: {
            output: {
                manualChunks: {
                    'vendor': ['react', 'react-dom', '@inertiajs/react'],
                    'ui': ['@radix-ui/react-dialog', '@radix-ui/react-dropdown-menu', '@radix-ui/react-slot'],
                },
            },
        },
    },
});
