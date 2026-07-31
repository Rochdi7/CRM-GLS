import { createInertiaApp } from '@inertiajs/react';
import type { ComponentType } from 'react';
import { createRoot } from 'react-dom/client';

// Lazy glob (no `eager`) → every page becomes its own Vite chunk, fetched on
// first visit and then browser-cached. The initial bundle carries only
// React + Inertia + the shared component/layout graph
// (Phase 12 bundle optimization — docs/phase12-performance-report.md).
const pages = import.meta.glob<{ default: ComponentType }>('./Pages/**/*.tsx');

createInertiaApp({
    resolve: async (name) => {
        const loader = pages[`./Pages/${name}.tsx`];

        if (!loader) {
            throw new Error(`Inertia page not found: ./Pages/${name}.tsx`);
        }

        return (await loader()).default;
    },
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
});
