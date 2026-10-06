/**
 * Le nom d'un étudiant s'AFFICHE en capitales partout — tableaux (CSS
 * `.table td`), modals, titres, reçus et rapports. Pour du JSX, préférer la
 * classe `text-uppercase` ; ce helper sert là où le nom vit dans une CHAÎNE
 * (titre de modal, `recordLabel`, `aria-label`, infobulle) qu'aucune classe
 * ne peut atteindre.
 *
 * Transformation d'AFFICHAGE uniquement (CLAUDE.md §5) : ne jamais l'appliquer
 * à une valeur envoyée au serveur, stockée ou comparée — la donnée garde la
 * casse saisie.
 */
export function enCapitales(nom: string | null | undefined): string {
    return (nom ?? '').toLocaleUpperCase('fr-FR');
}
