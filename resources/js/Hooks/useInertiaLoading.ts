import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

/** Local requests can finish in a handful of milliseconds — without a floor the spinner blips on and off faster than it's visible, or gets collapsed away entirely by React batching 'start'+'finish' into one render. */
const MIN_VISIBLE_MS = 200;

/**
 * True while any Inertia visit (search/filter/sort/pagination reload) is
 * in flight — global router events, so every page gets busy-state for free
 * without wiring per-request onStart/onFinish callbacks itself.
 */
export function useInertiaLoading(): boolean {
    const [loading, setLoading] = useState(false);
    const startedAtRef = useRef<number | null>(null);
    const hideTimeoutRef = useRef<ReturnType<typeof setTimeout> | undefined>(undefined);

    useEffect(() => {
        const stopStart = router.on('start', () => {
            clearTimeout(hideTimeoutRef.current);
            startedAtRef.current = Date.now();
            setLoading(true);
        });

        const stopFinish = router.on('finish', () => {
            const elapsed = startedAtRef.current === null ? MIN_VISIBLE_MS : Date.now() - startedAtRef.current;
            const remaining = Math.max(MIN_VISIBLE_MS - elapsed, 0);
            hideTimeoutRef.current = setTimeout(() => setLoading(false), remaining);
        });

        return () => {
            stopStart();
            stopFinish();
            clearTimeout(hideTimeoutRef.current);
        };
    }, []);

    return loading;
}
