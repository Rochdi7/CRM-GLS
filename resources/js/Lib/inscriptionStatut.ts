/**
 * Inscription statut → Bootstrap colour variant (Inscription::STATUTS:
 * Active / Annulée / Changement / Expirée / Archivée).
 *
 * ⚠ THE SINGLE DEFINITION — shared by the Inscriptions list badges and the
 * « Appliquer l'avance » dropdown (17/09/2026). A second copy would let one
 * screen paint « Changement » yellow while another paints it grey, and the
 * colour is precisely what tells the cashier which dossier they are about to
 * put money on.
 */
export type StatutVariant = 'success' | 'danger' | 'warning' | 'secondary';

export function statutVariant(statut: string): StatutVariant {
    if (statut === 'Active') return 'success';
    if (statut === 'Annulée') return 'danger';
    if (statut === 'Changement') return 'warning';

    return 'secondary';
}

/** The same colour as a raw CSS var, for a dot/pill drawn outside a `.badge`. */
export function statutColor(statut: string): string {
    return `var(--bs-${statutVariant(statut)})`;
}
