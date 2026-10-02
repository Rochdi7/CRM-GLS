import { router } from '@inertiajs/react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import EmptyState from '@/Components/Shared/EmptyState';
import DataTable from '@/Components/Tables/DataTable';
import Pagination from '@/Components/Tables/Pagination';
import RowActions from '@/Components/Tables/RowActions';
import StatusBadge from '@/Components/Details/StatusBadge';
import CalculPaiementProf, {
    type PaiementProfCalculParams,
    type PaiementProfPrefill,
} from '@/Components/PaiementProf/CalculPaiementProf';
import { useInertiaLoading } from '@/Hooks/useInertiaLoading';
import { t } from '@/Lib/i18n';
import type { PaiementProfPageProps } from '@/Types';

/** Même table que la liste Dépenses : statut d'approbation → badge PreSkool. */
const DEPENSE_STATUT_BADGE: Record<string, 'success' | 'warning' | 'danger' | 'secondary'> = {
    'En attente': 'warning',
    'Approuvée': 'success',
    'Refusée': 'danger',
    'Annulée': 'secondary',
};

/**
 * « Calcul paiement prof » — l'écran dédié (`/backoffice/paiement-prof`).
 *
 * Depuis le 30/09/2026 l'entrée NORMALE est l'onglet « Paiements prof » de
 * Gestion des dépenses, qui embarque le même composant
 * (`Components/PaiementProf/CalculPaiementProf`) ; cette page n'est plus
 * dans la barre latérale mais reste servie par sa route — et, comme tout
 * écran hors menu, c'est sa permission qui la protège, pas son absence du
 * menu (§16).
 *
 * ⚠ Cet écran n'écrit RIEN et ne touche AUCUNE caisse. Il PROPOSE un
 * montant ; « Enregistrer la dépense » ouvre le modal « Paiement prof »
 * habituel pré-rempli, et c'est là, et seulement là, que l'argent bouge (§11).
 */
export default function PaiementProfIndex({
    calcul,
    filters,
    groupOptions,
    paliersPaie,
    paiementProfTypeId,
    canCreateDepense,
    paiementsProf,
}: PaiementProfPageProps) {
    const isLoading = useInertiaLoading();

    function naviguer(params: PaiementProfCalculParams | null) {
        router.get('/backoffice/paiement-prof', params ?? {}, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }

    function enregistrer(prefill: PaiementProfPrefill) {
        // Query string seule (jamais une URL absolue) — c'est le modal qui la
        // re-préfixe.
        const retour = new URLSearchParams({
            groupFilter: prefill.retour.groupFilter,
            enseignantFilter: prefill.retour.enseignantFilter,
            debut: prefill.retour.debut,
            fin: prefill.retour.fin,
            ...(prefill.retour.heures !== '' ? { heures: prefill.retour.heures } : {}),
        });

        const params = new URLSearchParams({
            prefill_paiement_prof: '1',
            prefill_group_id: String(prefill.groupId),
            prefill_enseignant_id: String(prefill.enseignantId),
            prefill_montant: prefill.montant,
            prefill_periode_debut: prefill.periodeDebut,
            prefill_periode_fin: prefill.periodeFin,
            prefill_description: prefill.description,
            prefill_retour: `?${retour.toString()}`,
            ...(paiementProfTypeId !== null ? { prefill_type_depense_id: String(paiementProfTypeId) } : {}),
        });

        router.visit(`/backoffice/depenses?${params.toString()}`);
    }

    return (
        <BackofficeLayout title={t('Teacher payment calculation')}>
            <CalculPaiementProf
                calcul={calcul}
                filters={filters}
                groupOptions={groupOptions}
                paliersPaie={paliersPaie}
                canCreateDepense={canCreateDepense}
                onNavigate={naviguer}
                onEnregistrer={enregistrer}
            />

            {/* ── Paiements déjà enregistrés ─────────────────────────────
                Un calcul n'est pas un paiement : ces lignes sont les
                dépenses « Paiement prof » réellement saisies. */}
            {paiementsProf !== null && (
                <Card title={t('Recorded teacher payments')} className="mt-3">
                    <p className="fw-medium mb-3">
                        {t('Total amount')} : {Number(paiementsProf.montantTotal).toFixed(2)} MAD
                    </p>
                    {paiementsProf.data.data.length === 0 ? (
                        <EmptyState title={t('No teacher payment recorded')} icon="ti ti-user-dollar" />
                    ) : (
                        <>
                            <DataTable
                                loading={isLoading}
                                head={
                                    <tr>
                                        <th>{t('Reference')}</th>
                                        <th>{t('Group')}</th>
                                        <th>{t('Teacher')}</th>
                                        <th>{t('Period')}</th>
                                        <th className="text-end">{t('Amount')}</th>
                                        <th>{t('Status')}</th>
                                        <th>{t('Date')}</th>
                                        <th className="text-end">{t('Action')}</th>
                                    </tr>
                                }
                            >
                                {paiementsProf.data.data.map((row) => (
                                    <tr key={row.id} className={row.isAnnulee ? 'opacity-50' : undefined}>
                                        <td>
                                            <code>{row.reference}</code>
                                        </td>
                                        <td>{row.groupNom ?? '-'}</td>
                                        <td>{row.enseignant ?? '-'}</td>
                                        <td>
                                            {row.periodeDebut && row.periodeFin
                                                ? `${row.periodeDebut} → ${row.periodeFin}`
                                                : '-'}
                                        </td>
                                        <td
                                            className={`text-end fw-medium${row.isAnnulee ? ' text-muted text-decoration-line-through' : ''}`}
                                        >
                                            {Number(row.montant).toFixed(2)} MAD
                                        </td>
                                        <td>
                                            <StatusBadge
                                                label={row.statut}
                                                variant={DEPENSE_STATUT_BADGE[row.statut] ?? 'warning'}
                                                dot
                                            />
                                        </td>
                                        <td>{row.dateDepense ?? '-'}</td>
                                        <td>
                                            <RowActions view={row.showUrl} />
                                        </td>
                                    </tr>
                                ))}
                            </DataTable>
                            <Pagination paginator={paiementsProf.data} />
                        </>
                    )}
                </Card>
            )}
        </BackofficeLayout>
    );
}
