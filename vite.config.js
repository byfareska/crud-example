import { defineConfig } from 'vite';
import Symfony from '@symfony/reprise/vite';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    // One entry per theme of the panel; the pages load panel_<CRUD_THEME> (config/packages/byfareska_crud.yaml).
    input: {
        panel_bootstrap5: './assets/bootstrap5.js',
        panel_tailwind: './assets/tailwind.js',
        panel_daisyui: './assets/daisyui.js',
    },
    plugins: [
        tailwindcss(),
        Symfony({
            stimulus: 'assets/controllers.json',
            // The dev server runs in the `node` container; the pages come from the `php` one.
            devServerOrigin: 'http://localhost:5173',
        }),
    ],
    resolve: {
        // @byfareska/crud is linked from vendor/byfareska/crud/assets: kept under node_modules, its imports of
        // Stimulus and gridstack resolve to the application's copies.
        preserveSymlinks: true,
    },
    server: {
        host: true,
        port: 5173,
        strictPort: true,
    },
});
