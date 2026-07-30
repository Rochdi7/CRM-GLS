import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

/*
 * GLS CRM — Vite entries.
 *
 * Vite manages ONLY our own code (Backoffice/Frontoffice overrides and
 * behaviour). The PreSkool theme itself is served as static, pre-built
 * assets from public/assets/preskool/ (see CLAUDE.md § Assets).
 *
 * Tailwind was removed from the default Laravel skeleton on purpose:
 * this project uses Bootstrap 5 (PreSkool). Do not re-add Tailwind.
 *
 * resources/js/app.tsx is the Inertia + React entry
 * (docs/inertia-react-migration-plan.md §4). It coexists with the existing
 * Livewire/Blade entries below throughout the migration — neither replaces
 * the other until every module is converted (Phase 10).
 */
export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/scss/backoffice/app.scss',
                'resources/js/backoffice/app.js',
                'resources/scss/frontoffice/app.scss',
                'resources/js/frontoffice/app.js',
                'resources/js/app.tsx',
            ],
            refresh: true,
        }),
        react(),
    ],
    server: {
        // Pin to IPv4. Without this the dev server binds to IPv6 on Windows and
        // Laravel emits <script src="http://[::1]:5173/…">, which the browser
        // fails to load — app.js never runs, so the glsSelect2 Alpine component
        // is never registered and every Select2 dropdown renders empty.
        host: '127.0.0.1',
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
