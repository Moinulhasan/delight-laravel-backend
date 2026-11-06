import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/dashboard.js', 'resources/js/swal.js', 'resources/js/hero-slides.js', 'resources/js/videos.js', 'resources/js/leaders.js', 'resources/js/projects.js','resources/js/page-form.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
