import type { SelectOption } from '@/Types';

/**
 * Group statut → dropdown row tint (Group::STATUTS_HISTORIQUE): « Annulée »
 * red, « Fin de formation » orange, open groups untinted (27/10/2026). The
 * label already ends with « - Annulée » / « - Fin de formation »
 * (GetSeanceFormOptions::allGroups), so the colour is never the only signal.
 */
export function groupStatutTone(statut: string | undefined): SelectOption['tone'] {
    if (statut === 'Annulée') return 'danger';
    if (statut === 'Fin de formation') return 'orange';

    return undefined;
}

export function withGroupStatutTone<T extends SelectOption & { statut?: string }>(options: T[]): T[] {
    return options.map((option) => ({ ...option, tone: groupStatutTone(option.statut) }));
}
