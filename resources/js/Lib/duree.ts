/**
 * Lecture d'une durée saisie à la main, en HEURES DÉCIMALES.
 *
 * ⚠ « 2.30 » veut dire 2 h 30, PAS 2,3 heures (= 2 h 18). C'est la
 * convention de saisie de GLS (demande du 22/09/2026) : l'utilisateur tape
 * une durée telle qu'il la lit sur un emploi du temps, pas un nombre
 * décimal. Interpréter « 2.30 » comme 2,3 h ferait perdre 12 minutes par
 * séance — sur 22 séances, plus de 4 heures de paie envolées sans que rien
 * ne le signale.
 *
 * Sont donc lus comme des MINUTES les chiffres après le séparateur :
 *
 *   « 2 »      → 2.00 h
 *   « 2.5 »    → 2.50 h   (un seul chiffre = demi-heure, lecture décimale
 *                          usuelle — « 2.5 » se lit « deux heures et demie »)
 *   « 2.30 »   → 2.50 h   (deux chiffres = minutes)
 *   « 2:30 »   → 2.50 h
 *   « 2h30 »   → 2.50 h
 *   « 2,45 »   → 2.75 h
 *   « 1h05 »   → 1.08 h
 *
 * Les minutes ≥ 60 sont refusées (`null`) plutôt que reportées : « 2.75 »
 * est presque sûrement une saisie décimale mal comprise, et deviner à la
 * place de l'utilisateur sur un montant de paie serait pire que refuser.
 */
export function parseDuree(saisie: string): number | null {
    const texte = saisie.trim().toLowerCase().replace(/\s+/g, '');

    if (texte === '') {
        return null;
    }

    // Heures seules : « 2 », « 12 »
    if (/^\d+$/.test(texte)) {
        return Number(texte);
    }

    const m = texte.match(/^(\d+)\s*[.,:h]\s*(\d{1,2})?$/);

    if (m === null) {
        return null;
    }

    const heures = Number(m[1]);

    // « 2h » / « 2: » — heures pleines.
    if (m[2] === undefined || m[2] === '') {
        return heures;
    }

    // UN seul chiffre après un point ou une virgule : lecture décimale
    // (« 2.5 » = deux heures et demie). Après « h » ou « : », c'est en
    // revanche toujours des minutes (« 2h5 » = 2 h 05).
    const separateurDecimal = /[.,]/.test(texte);
    if (m[2].length === 1 && separateurDecimal) {
        return heures + Number(m[2]) / 10;
    }

    const minutes = Number(m[2]);

    // 60 minutes ou plus : la saisie n'est pas une durée valide.
    if (minutes >= 60) {
        return null;
    }

    return heures + minutes / 60;
}

/** « 2.5 » → « 2h30 » — pour confirmer à l'écran ce qui a été compris. */
export function formatDuree(heures: number): string {
    if (!Number.isFinite(heures) || heures < 0) {
        return '-';
    }

    const total = Math.round(heures * 60);
    const h = Math.floor(total / 60);
    const m = total % 60;

    return m === 0 ? `${h}h` : `${h}h${String(m).padStart(2, '0')}`;
}
