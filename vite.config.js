import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/floorplan.js', 'resources/js/showroom.js', 'resources/js/dashboard.js', 'resources/js/analytics.js', 'resources/js/mon-viewer.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
