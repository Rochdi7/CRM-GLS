import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

/*
 * GLS CRM — Vite entries.
 *
 * Vite manages ONLY our own code (Frontoffice overrides/behaviour + the
 * Inertia/React app). The PreSkool theme itself is served as static,
 * pre-built assets from public/assets/preskool/ (see CLAUDE.md § Assets).
 *
 * Tailwind was removed from the default Laravel skeleton on purpose:
 * this project uses Bootstrap 5 (PreSkool). Do not re-add Tailwind.
 *
 * resources/js/app.tsx is the Inertia + React entry
 * (docs/inertia-react-migration-plan.md §4) — the backoffice is fully
 * Inertia+React as of Phase 11; the old Livewire/Blade backoffice bundle
 * was removed.
 */
export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/scss/frontoffice/app.scss',
                'resources/js/frontoffice/app.js',
                'resources/js/app.tsx',
            ],
            // Blade-file full-reload only — scoped away from resources/js/**
            // so laravel-vite-plugin's own refresh mechanism never fights
            // @vitejs/plugin-react's Fast Refresh preamble on .tsx files
            // (that collision is what threw "can't detect preamble").
            refresh: ['resources/views/**'],
        }),
        react(),
    ],
    server: {
        // Pin to IPv4. Without this the dev server binds to IPv6 on Windows and
        // Laravel emits <script src="http://[::1]:5173/…">, which the browser
        // fails to load, breaking every Vite-managed asset.
        host: '127.0.0.1',
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
