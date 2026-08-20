/**
 * Money formatting helpers.
 *
 * The app's amounts are Moroccan dirhams stored as `decimal(12,2)` and sent
 * to React as strings (Laravel serializes decimals as strings, never floats
 * — see §17 "Money rules"). Always run them through `Number(...)` before
 * formatting, never through arithmetic that could reintroduce float error.
 *
 * Locale is `fr-FR`, matching AnnualFraisChart and the French default UI
 * locale: narrow-no-break-space thousands separator, comma decimal
 * ("21 356,88"), which is what a Moroccan French-language user expects.
 */

const MONTANT_FORMAT = new Intl.NumberFormat('fr-FR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

/**
 * Format an amount for display, WITHOUT a currency unit — render "MAD"
 * separately so it can be styled as a suffix (see StatCard's `unit` prop)
 * and so the number itself stays on one line.
 */
export function formatMontant(value: number | string | null | undefined): string {
    const amount = Number(value ?? 0);

    return MONTANT_FORMAT.format(Number.isFinite(amount) ? amount : 0);
}

/**
 * Compact form for large amounts in tight spaces (KPI cards): 21 356,88
 * stays as-is, but 1 250 000 becomes "1,25 M" so a big month never wraps.
 * Below 100 000 the exact amount is always shown — CRM users read those
 * figures precisely and rounding them would be a regression, not a polish.
 */
export function formatMontantCompact(value: number | string | null | undefined): string {
    const amount = Number(value ?? 0);

    if (!Number.isFinite(amount)) {
        return MONTANT_FORMAT.format(0);
    }

    const abs = Math.abs(amount);

    if (abs >= 1_000_000) {
        return `${(amount / 1_000_000).toLocaleString('fr-FR', { maximumFractionDigits: 2 })} M`;
    }

    if (abs >= 100_000) {
        return `${(amount / 1_000).toLocaleString('fr-FR', { maximumFractionDigits: 1 })} k`;
    }

    return MONTANT_FORMAT.format(amount);
}
