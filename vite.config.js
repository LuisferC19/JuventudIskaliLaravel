import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/ModulosAdmi.css',
                'resources/css/Login_Styles.css',
                'resources/css/Landing_Styles.css',
                'resources/js/app.js',
                'resources/js/ModuleShell.js',
                'resources/js/resource-assets.js',
                'resources/js/AppSistema.js',
                'resources/js/Validaciones.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
