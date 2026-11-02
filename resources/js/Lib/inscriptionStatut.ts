import type { CSSProperties } from 'react';

/**
 * Inscription statut → Bootstrap colour variant (Inscription::STATUTS:
 * Active / Annulée / Changement / Transférée / Expirée / Archivée).
 *
 * ⚠ THE SINGLE DEFINITION — shared by the Inscriptions list badges and the
 * « Appliquer l'avance » dropdown (17/09/2026). A second copy would let one
 * screen paint « Changement » blue while another paints it grey, and the
 * colour is precisely what tells the cashier which dossier they are about to
 * put money on.
 *
 * Palette (02/11/2026, owner's decision): vert Active, rouge Annulée,
 * bleu clair Changement, orange Transférée, gris tout le reste (Archivée,
 * Expirée). The grid fills below follow the SAME split.
 */
export type StatutVariant = 'success' | 'danger' | 'info' | 'orange' | 'secondary';

export function statutVariant(statut: string): StatutVariant {
    if (statut === 'Active') return 'success';
    if (statut === 'Annulée') return 'danger';
    if (statut === 'Changement') return 'info';
    // Dossier clos par un transfert de l'étudiant vers un autre centre
    // (Inscription::STATUT_TRANSFEREE, 25/09/2026).
    if (statut === 'Transférée') return 'orange';

    return 'secondary';
}

/** The same colour as a raw CSS var, for a dot/pill drawn outside a `.badge`. */
export function statutColor(statut: string): string {
    return `var(--bs-${statutVariant(statut)})`;
}

/**
 * Name-cell fill of a student row in the three per-group grids — « Statistique
 * de groupe » / Détails paiement (GroupPaymentMatrix), « Absence par groupe »
 * and the « Paiement prof » calculation. ONE definition so a group reads
 * identically on all three: nothing while the dossier is Active, red for a
 * cancellation, light blue for a group change, orange for a transfer to
 * another centre, grey for everything else closed (Archivée, Expirée, or no
 * inscription in the group at all). An unknown statut falls back to grey,
 * never to red.
 */
const GRID_FILL: Record<string, CSSProperties> = {
    Annulée: { backgroundColor: 'rgb(246, 45, 81)', color: '#fff' },
    Changement: { backgroundColor: 'rgb(173, 214, 255)', color: '#1a1a1a' },
    Transférée: { backgroundColor: 'rgb(253, 126, 20)', color: '#1a1a1a' },
};

const GRID_FILL_CLOS: CSSProperties = { backgroundColor: 'rgb(170, 170, 170)', color: '#1a1a1a' };

export function statutGridFill(statut: string | null | undefined): CSSProperties | undefined {
    if (statut === 'Active') {
        return undefined;
    }

    return (statut && GRID_FILL[statut]) || GRID_FILL_CLOS;
}
