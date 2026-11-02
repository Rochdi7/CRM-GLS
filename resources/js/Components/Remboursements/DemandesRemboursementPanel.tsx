import { router } from '@inertiajs/react';
import { useState } from 'react';
import Card from '@/Components/Shared/Card';
import EmptyState from '@/Components/Shared/EmptyState';
import DataTable from '@/Components/Tables/DataTable';
import Pagination from '@/Components/Tables/Pagination';
import SearchInput from '@/Components/Tables/SearchInput';
import RowActions, { RowActionItem } from '@/Components/Tables/RowActions';
import ConfirmDialog from '@/Components/Modals/ConfirmDialog';
import StatusBadge from '@/Components/Details/StatusBadge';
import SelectField from '@/Components/Forms/SelectField';
import TextareaField from '@/Components/Forms/TextareaField';
import { t } from '@/Lib/i18n';
import type { DemandeRemboursementRow, MoneyDisplay, PaginatedData, SelectOption } from '@/Types';

interface DemandesRemboursementPanelProps {
    demandes: PaginatedData<DemandeRemboursementRow>;
    enAttente: { count: number; montant: MoneyDisplay } | null;
    statuts: string[];
    methodes: string[];
    /** Current statut filter (server echo) — '' = every statut (history). */
    statut: string;
    onStatutChange: (statut: string) => void;
    /** `refunds.choose-method` (super-admin) — UI convenience only. */
    canChooseMethode: boolean;
    centerLocked: boolean;
    loading: boolean;
    /** Shared search box of the page (reference / bénéficiaire / paiement). */
    search: string;
    onSearch: (value: string) => void;
    /** « Demander un remboursement » — opens the page's refund modal; omitted when requests are off. */
    onCreate?: () => void;
    /**
     * « Nouvelle demande » on a REFUSED row: a refusal frees the payment
     * (only an approved refund consumes it), so the same refund can be asked
     * again — pre-filled. Omitted when requests are off.
     */
    onRequestAgain?: (row: DemandeRemboursementRow) => void;
}

const ESPECES = 'Espèces';

function statutVariant(statut: string): 'warning' | 'success' | 'danger' | 'secondary' {
    if (statut === 'En attente') return 'warning';
    if (statut === 'Approuvée') return 'success';
    if (statut === 'Refusée') return 'danger';
    return 'secondary';
}

/**
 * « Demandes de remboursement » (02/11/2026) — the inbox above the
 * Remboursements list while « Validation des remboursements » is ON.
 *
 * A request has debited NOTHING. Approving is the one moment money leaves:
 * in cash from the REQUESTER's till by default (the only method a director
 * may use), or — super-admin only — from the centre's TPE / Virement
 * account. Which rows are decidable, and whether the method is locked to
 * cash (a rejected-cheque payment), come from the server; refusals of the
 * action itself surface inside the dialog.
 */
export default function DemandesRemboursementPanel({
    demandes,
    enAttente,
    statuts,
    methodes,
    statut,
    onStatutChange,
    canChooseMethode,
    centerLocked,
    loading,
    search,
    onSearch,
    onCreate,
    onRequestAgain,
}: DemandesRemboursementPanelProps) {
    const [decision, setDecision] = useState<{ type: 'approve' | 'refuse'; row: DemandeRemboursementRow } | null>(null);
    const [methode, setMethode] = useState(ESPECES);
    const [motifRefus, setMotifRefus] = useState('');
    const [error, setError] = useState<string | undefined>(undefined);
    const [processing, setProcessing] = useState(false);

    const statutOptions: SelectOption[] = [
        { value: '', label: t('All statuses') },
        ...statuts.map((s) => ({ value: s, label: t(s) })),
    ];
    const methodeOptions: SelectOption[] = methodes.map((m) => ({ value: m, label: m }));

    function open(type: 'approve' | 'refuse', row: DemandeRemboursementRow) {
        setDecision({ type, row });
        setMethode(ESPECES);
        setMotifRefus('');
        setError(undefined);
    }

    function close() {
        setDecision(null);
        setError(undefined);
    }

    function confirm() {
        if (decision === null) return;

        setProcessing(true);
        const url = `/backoffice/demandes-remboursement/${decision.row.id}/${decision.type === 'approve' ? 'approuver' : 'refuser'}`;
        const payload = decision.type === 'approve' ? { methode } : { motif: motifRefus };

        router.patch(url, payload, {
            preserveScroll: true,
            onSuccess: () => close(),
            onError: (errors) => setError(Object.values(errors)[0]),
            onFinish: () => setProcessing(false),
        });
    }

    /** Which account the approval will debit — the same rule ApprouverDemandeRemboursement applies. */
    function compteDebite(row: DemandeRemboursementRow): string {
        if (row.chequeRejete) {
            return t("The centre's Cheque account (reversal of a rejected cheque)");
        }
        if (methode === ESPECES) {
            return t('The till of :name (cash)', { name: row.demandePar ?? '-' });
        }

        return t('The :methode account of :centre', { methode, centre: row.centre ?? '-' });
    }

    const row = decision?.row ?? null;

    return (
        <Card
            title={t('Refund requests')}
            bodyClassName="p-0 py-3"
            tools={onCreate && (
                <button type="button" className="btn btn-primary d-flex align-items-center mb-3" onClick={onCreate}>
                    <i className="ti ti-square-rounded-plus me-2" />
                    {t('Request a refund')}
                </button>
            )}
        >
            <div className="px-3 pb-3 d-flex flex-wrap align-items-end gap-3">
                <div style={{ width: 200 }}>
                    <label className="form-label" htmlFor="drb-f-statut">
                        {t('Status')}
                    </label>
                    <SelectField
                        id="drb-f-statut"
                        options={statutOptions}
                        value={statut}
                        searchable={false}
                        onChange={(e) => onStatutChange(e.target.value)}
                    />
                </div>
                <div className="ms-auto">
                    <SearchInput value={search} onSearch={onSearch} />
                </div>
                {enAttente && enAttente.count > 0 && (
                    <div className="d-flex align-items-center gap-2 mb-2 w-100">
                        <span className="text-muted">{t('Awaiting approval')} :</span>
                        <span className="fw-semibold text-warning fs-16">{Number(enAttente.montant).toFixed(2)} MAD</span>
                        <span className="text-muted">
                            ({enAttente.count} {enAttente.count > 1 ? t('requests') : t('request')})
                        </span>
                    </div>
                )}
            </div>

            {demandes.data.length === 0 ? (
                <EmptyState title={t('No refund request')} icon="ti ti-clipboard-list" />
            ) : (
                <>
                    <DataTable
                        loading={loading}
                        head={
                            <tr>
                                <th>{t('Reference')}</th>
                                <th>{t('Beneficiary')}</th>
                                <th>{t('Payment')}</th>
                                <th className="text-end">{t('Amount')}</th>
                                {!centerLocked && <th>{t('Centre')}</th>}
                                <th>{t('Requested by')}</th>
                                <th>{t('Status')}</th>
                                <th className="text-end">{t('Action')}</th>
                            </tr>
                        }
                    >
                        {demandes.data.map((d) => (
                            <tr key={d.id}>
                                <td>
                                    <code>{d.reference}</code>
                                </td>
                                <td>{d.beneficiaire ?? '-'}</td>
                                <td className="text-normal-case">{d.encaissementReference ?? '-'}</td>
                                <td className="text-end fw-medium">{Number(d.montant).toFixed(2)} MAD</td>
                                {!centerLocked && <td>{d.centre ?? '-'}</td>}
                                <td>
                                    {d.demandePar ?? '-'}
                                    <div className="text-muted fs-12">{d.demandeLe}</div>
                                </td>
                                <td>
                                    <StatusBadge label={t(d.statut)} variant={statutVariant(d.statut)} dot />
                                    {d.statut === 'Approuvée' && (
                                        <div className="text-muted fs-12 text-normal-case">
                                            {d.methode} - {d.remboursementReference} - {d.decidePar}
                                        </div>
                                    )}
                                    {d.statut === 'Refusée' && (
                                        <div className="text-muted fs-12 text-normal-case">
                                            {d.decidePar} : {d.motifRefus}
                                        </div>
                                    )}
                                </td>
                                <td>
                                    {d.canDecide && (
                                        <RowActions>
                                            <RowActionItem icon="ti-check" onClick={() => open('approve', d)}>
                                                {t('Approve')}
                                            </RowActionItem>
                                            <RowActionItem icon="ti-x" onClick={() => open('refuse', d)}>
                                                {t('Refuse')}
                                            </RowActionItem>
                                        </RowActions>
                                    )}
                                    {d.statut === 'Refusée' && onRequestAgain && (
                                        <RowActions>
                                            <RowActionItem icon="ti-repeat" onClick={() => onRequestAgain(d)}>
                                                {t('New request')}
                                            </RowActionItem>
                                        </RowActions>
                                    )}
                                </td>
                            </tr>
                        ))}
                    </DataTable>
                    <Pagination paginator={demandes} />
                </>
            )}

            <ConfirmDialog
                show={decision !== null && decision.type === 'approve'}
                title={t('Approve the refund request')}
                message={t('The refund is recorded now and the amount leaves the account below. This cannot be undone except by cancelling the refund.')}
                recordLabel={row ? `${row.reference} - ${Number(row.montant).toFixed(2)} MAD - ${row.beneficiaire ?? ''}` : ''}
                error={error}
                processing={processing}
                onConfirm={confirm}
                onCancel={close}
                icon="ti-check"
                variant="primary"
                confirmLabel={t('Approve')}
                processingLabel={t('Approving…')}
            >
                {row && (
                    <>
                        {canChooseMethode && !row.chequeRejete ? (
                            <SelectField
                                id="drb-methode"
                                label={t('Refund method')}
                                options={methodeOptions}
                                value={methode}
                                searchable={false}
                                onChange={(e) => setMethode(e.target.value)}
                            />
                        ) : (
                            <p className="mb-2">
                                <span className="text-muted">{t('Refund method')} :</span>{' '}
                                <span className="fw-medium">{ESPECES}</span>
                            </p>
                        )}
                        <p className="mb-0">
                            <span className="text-muted">{t('Debited account')} :</span>{' '}
                            <span className="fw-medium">{compteDebite(row)}</span>
                        </p>
                    </>
                )}
            </ConfirmDialog>

            <ConfirmDialog
                show={decision !== null && decision.type === 'refuse'}
                title={t('Refuse the refund request')}
                message={t('No money moves. The request stays listed with its reason.')}
                recordLabel={row ? `${row.reference} - ${Number(row.montant).toFixed(2)} MAD - ${row.beneficiaire ?? ''}` : ''}
                error={error}
                processing={processing}
                onConfirm={confirm}
                onCancel={close}
                icon="ti-x"
                confirmLabel={t('Refuse')}
                processingLabel={t('Refusing…')}
                confirmDisabled={motifRefus.trim().length < 3}
            >
                <TextareaField
                    id="drb-motif-refus"
                    label={t('Reason for refusal')}
                    required
                    value={motifRefus}
                    onChange={(e) => setMotifRefus(e.target.value)}
                />
            </ConfirmDialog>
        </Card>
    );
}
