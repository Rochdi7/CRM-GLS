import { usePage } from '@inertiajs/react';
import type { SharedProps } from '@/Types';

/**
 * Shown at the top of every backoffice page while the ACTIVE academic year
 * is closed (« Année clôturée », Paramètres → Années scolaires).
 *
 * The lock itself is server-side and absolute — AssertsContextScope refuses
 * every create/update, super-admin included (CLAUDE.md §11). This banner
 * exists so the refusal is never a surprise: the incident it comes from was
 * an employee keying dépenses into a past year that had simply been left
 * selected in the top-bar switcher, with nothing on screen saying so.
 *
 * Reading, exports and receipts stay available — only writing is blocked,
 * which is why the wording says « consultation » rather than « accès ».
 */
export default function AnneeClotureeBanner() {
    const { context } = usePage<SharedProps>().props;

    if (!context?.anneeCloturee) {
        return null;
    }

    return (
        <div className="alert alert-warning d-flex align-items-start mb-3" role="alert">
            <i className="ti ti-lock fs-20 me-2 mt-1 flex-shrink-0" />
            <div>
                <strong>
                    Année {context.currentAcademicYear?.name ?? ''} clôturée — consultation uniquement.
                </strong>
                <div className="mt-1">
                    Aucun enregistrement ne peut être créé ni modifié dans cette année. Pour saisir une
                    opération, changez d’année dans le sélecteur en haut de page. Un super-administrateur
                    peut rouvrir l’année depuis Paramètres&nbsp;→&nbsp;Années scolaires.
                </div>
            </div>
        </div>
    );
}
