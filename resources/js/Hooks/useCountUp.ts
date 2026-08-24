import { useEffect, useRef, useState } from 'react';

/**
 * Animates a number from its previous value to `target` (ease-out cubic,
 * requestAnimationFrame). Re-runs whenever `target` changes, so a context
 * switch (year/centre) counts from the old figure to the new one instead of
 * restarting at 0. Honours `prefers-reduced-motion` by jumping straight to
 * the target.
 */
export function useCountUp(target: number, duration = 1000): number {
    const [current, setCurrent] = useState(0);
    const fromRef = useRef(0);

    useEffect(() => {
        if (!Number.isFinite(target)) {
            setCurrent(0);
            return;
        }

        const reduced =
            typeof window !== 'undefined' &&
            typeof window.matchMedia === 'function' &&
            window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        if (reduced || duration <= 0) {
            fromRef.current = target;
            setCurrent(target);
            return;
        }

        const from = fromRef.current;
        const delta = target - from;
        let frame = 0;
        let start: number | null = null;

        const tick = (now: number) => {
            if (start === null) {
                start = now;
            }
            const progress = Math.min(1, (now - start) / duration);
            const eased = 1 - Math.pow(1 - progress, 3);
            const value = from + delta * eased;
            setCurrent(progress >= 1 ? target : value);

            if (progress < 1) {
                frame = window.requestAnimationFrame(tick);
            } else {
                fromRef.current = target;
            }
        };

        frame = window.requestAnimationFrame(tick);

        return () => {
            window.cancelAnimationFrame(frame);
            fromRef.current = target;
        };
    }, [target, duration]);

    return current;
}
