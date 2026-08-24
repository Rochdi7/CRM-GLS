import { useCountUp } from '@/Hooks/useCountUp';

interface CountUpProps {
    value: number | string | null | undefined;
    /** Formats the in-flight number for display; defaults to a rounded fr-FR integer. */
    format?: (n: number) => string;
    duration?: number;
}

/**
 * Animated counter for dashboard KPIs. The raw number is animated and the
 * caller's formatter runs on each frame, so money cards keep their
 * "21 050,00" / "1,25 M" rendering while the digits roll up.
 */
export default function CountUp({ value, format, duration }: CountUpProps) {
    const target = Number(value ?? 0);
    const current = useCountUp(Number.isFinite(target) ? target : 0, duration);
    const text = format ? format(current) : Math.round(current).toLocaleString('fr-FR');

    return <span className="stat-counter-animated">{text}</span>;
}
