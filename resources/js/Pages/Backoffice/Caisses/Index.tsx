import { router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useState, type FormEvent } from 'react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import EmptyState from '@/Components/Shared/EmptyState';
import DataTable from '@/Components/Tables/DataTable';
import TableToolbar from '@/Components/Tables/TableToolbar';
import TableLengthRow from '@/Components/Tables/TableLengthRow';
import SearchInput from '@/Components/Tables/SearchInput';
import Pagination from '@/Components/Tables/Pagination';
import RowActions, { RowActionItem } from '@/Components/Tables/RowActions';
import Modal from '@/Components/Modals/Modal';
import ConfirmDialog from '@/Components/Modals/ConfirmDialog';
import SelectField from '@/Components/Forms/SelectField';
import DateField from '@/Components/Forms/DateField';
import TextareaField from '@/Components/Forms/TextareaField';
import FormActions from '@/Components/Forms/FormActions';
import StatusBadge from '@/Components/Details/StatusBadge';
import { useInertiaLoading } from '@/Hooks/useInertiaLoading';
import ComptesPanel from '@/Pages/Backoffice/Caisses/ComptesPanel';
import GlobalePanel from '@/Pages/Backoffice/Caisses/GlobalePanel';
import type { CaissesPageProps, CaisseJournalData, CaisseTransferRow, SelectOption, SharedProps } from '@/Types';

type Tab = 'ma-caisse' | 'transferts' | 'globale' | 'comptes';

/**
 * No caisse_source_id: the source is ALWAYS the acting employee's own till,
 * derived server-side (CaisseTransferController::store) — the modal only
 * shows it read-only via the `myCaisse` page prop.
 */
interface TransferFormState {
    caisse_destination_id: number | '';
    montant: string;
    date_transfert: string;
    note: string;
}

function todayIso(): string {
    return new Date().toISOString().slice(0, 10);
}

function emptyTransferForm(): TransferFormState {
    return { caisse_destination_id: '', montant: '', date_transfert: todayIso(), note: '' };
}

const TRANSFER_STATUT_BADGE: Record<string, 'success' | 'secondary' | 'warning'> = {
    'Validé': 'success',
    'Annulé': 'secondary',
    'En attente': 'warning',
};

const TRANSACTION_TYPE_BADGE: Record<string, 'success' | 'danger'> = {
    'Réception': 'success',
    'Transfert': 'danger',
};

function JournalPanel({ scope, data }: { scope: 'mine' | 'all'; data: CaisseJournalData }) {
    const [typeFilter, setTypeFilter] = useState('');
    const [dateFrom, setDateFrom] = useState(todayIso);
    const [dateTo, setDateTo] = useState(todayIso);
    const [page, setPage] = useState(1);
    const [journal, setJournal] = useState<CaisseJournalData>(data);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        setLoading(true);
        const params = new URLSearchParams({ typeFilter, dateFrom, dateTo, page: String(page) });
        fetch(`/backoffice/caisses/journal/${scope}?${params.toString()}`)
            .then((r) => r.json())
            .then((next: CaisseJournalData) => setJournal(next))
            .finally(() => setLoading(false));
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [typeFilter, dateFrom, dateTo, page]);

    if (journal.caissesInScope.length === 0) {
        return (
            <Card>
                <EmptyState title="Aucune caisse" message="Votre compte n'est lié à aucune caisse." icon="ti ti-cash" />
            </Card>
        );
    }

    return (
        <>
            <div className="row">
                <div className="col-md-6 col-xl-3">
                    <div className="card">
                        <div className="card-body d-flex align-items-center">
                            <span className="avatar avatar-md bg-success-transparent rounded-circle me-3 d-inline-flex align-items-center justify-content-center">
                                <i className="ti ti-cash-banknote text-success fs-20" />
                            </span>
                            <div>
                                <p className="mb-0 text-muted">Encaissements</p>
                                <h5 className="mb-0 text-success">{Number(journal.totalEncaissements).toFixed(2)} DH</h5>
                            </div>
                        </div>
                    </div>
                </div>
                <div className="col-md-6 col-xl-3">
                    <div className="card">
                        <div className="card-body d-flex align-items-center">
                            <span className="avatar avatar-md bg-danger-transparent rounded-circle me-3 d-inline-flex align-items-center justify-content-center">
                                <i className="ti ti-cash-banknote-off text-danger fs-20" />
                            </span>
                            <div>
                                <p className="mb-0 text-muted">Dépenses</p>
                                <h5 className="mb-0 text-danger">{Number(journal.totalDepenses).toFixed(2)} DH</h5>
                            </div>
                        </div>
                    </div>
                </div>
                <div className="col-md-6 col-xl-3">
                    <div className="card">
                        <div className="card-body d-flex align-items-center">
                            <span className="avatar avatar-md bg-warning-transparent rounded-circle me-3 d-inline-flex align-items-center justify-content-center">
                                <i className="ti ti-arrow-back-up text-warning fs-20" />
                            </span>
                            <div>
                                <p className="mb-0 text-muted">Remboursements</p>
                                <h5 className="mb-0 text-warning">{Number(journal.totalRemboursements).toFixed(2)} DH</h5>
                            </div>
                        </div>
                    </div>
                </div>
                <div className="col-md-6 col-xl-3">
                    <div className="card">
                        <div className="card-body d-flex align-items-center">
                            <span className="avatar avatar-md bg-primary-transparent rounded-circle me-3 d-inline-flex align-items-center justify-content-center">
                                <i className="ti ti-currency-dollar text-primary fs-20" />
                            </span>
                            <div>
                                <p className="mb-0 text-muted">Solde espèces</p>
                                <h5 className="mb-0 text-primary">{Number(journal.solde).toFixed(2)} DH</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <Card bodyClassName="p-0 py-3">
                <div className="px-3 pt-2">
                    <TableToolbar>
                        <div style={{ width: 200 }}>
                            <label className="form-label" htmlFor={`cj-type-${scope}`}>
                                Type
                            </label>
                            <SelectField
                                id={`cj-type-${scope}`}
                                options={[
                                    { value: 'paiement', label: 'Recette' },
                                    { value: 'remboursement', label: "Récupération d'une autre caisse" },
                                    { value: 'depense', label: 'Dépense' },
                                    { value: 'transfert', label: 'Transfert à une autre caisse' },
                                ]}
                                placeholder="Choisir un type"
                                value={typeFilter}
                                onChange={(e) => {
                                    setTypeFilter(e.target.value);
                                    setPage(1);
                                }}
                            />
                        </div>
                        <div style={{ width: 170 }}>
                            <label className="form-label" htmlFor={`cj-from-${scope}`}>
                                Date de début
                            </label>
                            <DateField
                                id={`cj-from-${scope}`}
                                value={dateFrom}
                                onChange={(e) => {
                                    setDateFrom(e.target.value);
                                    setPage(1);
                                }}
                            />
                        </div>
                        <div style={{ width: 170 }}>
                            <label className="form-label" htmlFor={`cj-to-${scope}`}>
                                Date de fin
                            </label>
                            <DateField
                                id={`cj-to-${scope}`}
                                value={dateTo}
                                onChange={(e) => {
                                    setDateTo(e.target.value);
                                    setPage(1);
                                }}
                            />
                        </div>
                    </TableToolbar>
                </div>

                {loading ? (
                    <p className="text-muted px-3">Chargement…</p>
                ) : journal.rows.length === 0 ? (
                    <EmptyState title="Aucune caisse n'a été trouvée." icon="ti ti-report-money" />
                ) : (
                    <>
                        <DataTable
                            head={
                                <tr>
                                    <th>Référence</th>
                                    <th>Transaction</th>
                                    <th>Étudiant / Tiers</th>
                                    <th className="text-end">Montant</th>
                                    <th>Date</th>
                                    <th>Agent</th>
                                </tr>
                            }
                        >
                            {journal.rows.map((row, i) => (
                                <tr key={`${row.type}-${row.reference}-${i}`}>
                                    <td>{row.url ? <a href={row.url}><code>{row.reference}</code></a> : <code>{row.reference}</code>}</td>
                                    <td>{row.libelle ?? '—'}</td>
                                    <td>{row.tiers ?? '—'}</td>
                                    <td className={`text-end fw-medium ${row.sens < 0 ? 'text-danger' : 'text-success'}`}>
                                        {row.sens < 0 ? '−' : '+'}
                                        {Number(row.montant).toFixed(2)} DH
                                    </td>
                                    <td>{row.date ?? '—'}</td>
                                    <td>{row.agent ?? '—'}</td>
                                </tr>
                            ))}
                        </DataTable>

                        <div className="d-flex align-items-center justify-content-between mt-3 px-3">
                            <span className="text-muted">{journal.total} transaction(s)</span>
                            <div className="btn-group">
                                <button
                                    type="button"
                                    className="btn btn-sm btn-outline-primary"
                                    disabled={journal.page <= 1}
                                    onClick={() => setPage((p) => Math.max(1, p - 1))}
                                >
                                    Précédent
                                </button>
                                <span className="btn btn-sm btn-light disabled">
                                    {journal.page} / {journal.lastPage}
                                </span>
                                <button
                                    type="button"
                                    className="btn btn-sm btn-outline-primary"
                                    disabled={journal.page >= journal.lastPage}
                                    onClick={() => setPage((p) => p + 1)}
                                >
                                    Suivant
                                </button>
                            </div>
                        </div>
                    </>
                )}
            </Card>
        </>
    );
}

/**
 * "Gestion de la caisse" — Ma caisse + Validation de transfert + Comptes de
 * caisse. Each tab is a real Inertia visit (?tab=…) so only the active tab's
 * dataset is fetched; tab visibility mirrors the server's own permission
 * checks in CaisseController@index (the server re-derives them, this is UI
 * convenience only).
 */
export default function CaissesIndex({
    canViewCaisses,
    canViewTransfers,
    canViewComptes,
    journalMine,
    globale,
    transfers,
    transfersMontantTotal,
    transferCaisses,
    transferStatuts,
    myCaisse,
    transferFilters: initialTransferFilters,
    comptes,
    compteTypes,
    compteTypeFilters,
    compteEtablissements,
    comptePermissions,
    compteFilters: initialCompteFilters,
}: CaissesPageProps) {
    const isLoading = useInertiaLoading();
    const availableTabs: Tab[] = [
        ...(canViewCaisses ? (['ma-caisse'] as Tab[]) : []),
        ...(canViewTransfers ? (['transferts'] as Tab[]) : []),
        ...(canViewCaisses ? (['globale'] as Tab[]) : []),
        ...(canViewComptes ? (['comptes'] as Tab[]) : []),
    ];
    const requested = new URLSearchParams(window.location.search).get('tab') as Tab | null;
    const [tab, setTab] = useState<Tab>(requested && availableTabs.includes(requested) ? requested : availableTabs[0]);

    const [transferFilters, setTransferFilters] = useState(
        initialTransferFilters ?? { search: '', statutFilter: '', typeFilter: '' },
    );
    const [compteFilters, setCompteFilters] = useState(
        initialCompteFilters ?? { compteSearch: '', compteTypeFilter: '' },
    );

    // Client-side permission checks — UI convenience only (hide affordances
    // the server would refuse anyway); real enforcement stays in the
    // policies/controllers (CLAUDE.md §5/§16).
    const { auth } = usePage<SharedProps>().props;
    const canUpdateTransfers = auth.isSuperAdmin || auth.permissions.includes('cash-transfers.update');
    const canValidateTransfers = auth.isSuperAdmin || auth.permissions.includes('cash-transfers.validate');

    const [showTransferModal, setShowTransferModal] = useState(false);
    const [editingTransfer, setEditingTransfer] = useState<CaisseTransferRow | null>(null);
    // Validate/cancel go through a ConfirmDialog popup; a server refusal
    // (self-validation, already-validated, permission…) is shown INSIDE the
    // dialog instead of an inline page alert.
    const [confirmAction, setConfirmAction] = useState<{ type: 'validate' | 'cancel'; row: CaisseTransferRow } | null>(null);
    const [actionError, setActionError] = useState<string | undefined>(undefined);
    const [actionProcessing, setActionProcessing] = useState(false);

    // Destination choices — every accessible till EXCEPT the acting
    // employee's own (the fixed source): transferring to yourself is
    // meaningless and refused server-side anyway. The unfiltered list stays
    // available for the edit modal's frozen display of older rows. Labels
    // show the caisse name only — not its balance, which would otherwise
    // leak every other center/employee's till balance to whoever opens
    // this picker (this dropdown carries no permission gate of its own).
    const allTransferCaisseOptions: SelectOption[] = transferCaisses.map((c) => ({ value: c.id, label: c.nom }));
    const transferCaisseOptions: SelectOption[] = transferCaisses
        .filter((c) => c.id !== myCaisse?.id)
        .map((c) => ({ value: c.id, label: c.nom }));
    const transferStatutOptions: SelectOption[] = transferStatuts.map((s) => ({ value: s, label: s }));
    const transferTypeOptions: SelectOption[] = [
        { value: 'envoye', label: "Envoyé à une autre caisse" },
        { value: 'recu', label: "Reçu d'une autre caisse" },
    ];

    const transferForm = useForm<TransferFormState>(emptyTransferForm());

    // Live Solde / Reste preview for the modal. Create mode: the fixed
    // source is always MY own till (myCaisse). Edit mode: the frozen source
    // of the row being edited. Display-only either way — the server
    // independently re-validates against the caisse's real balance.
    const editingSourceCaisse = editingTransfer ? transferCaisses.find((c) => c.id === editingTransfer.caisseSourceId) : null;
    const sourceCaisseNom = editingTransfer ? (editingSourceCaisse?.nom ?? '—') : (myCaisse?.nom ?? '—');
    const soldeSource = editingTransfer
        ? (editingSourceCaisse ? Number(editingSourceCaisse.solde) : null)
        : (myCaisse ? Number(myCaisse.solde) : null);
    const montantATransferer = parseFloat(transferForm.data.montant || '0');
    const reste = soldeSource !== null ? soldeSource - (Number.isFinite(montantATransferer) ? montantATransferer : 0) : null;

    function switchTab(next: Tab) {
        setTab(next);
        router.get('/backoffice/caisses', { tab: next }, { preserveState: true, preserveScroll: true, replace: true });
    }

    function reloadTransfers(next: Partial<typeof transferFilters>) {
        const merged = { ...transferFilters, ...next };
        setTransferFilters(merged);
        router.get('/backoffice/caisses', { tab: 'transferts', ...merged, page: undefined }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }

    function reloadComptes(next: Partial<typeof compteFilters>) {
        const merged = { ...compteFilters, ...next };
        setCompteFilters(merged);
        router.get('/backoffice/caisses', { tab: 'comptes', ...merged, page: undefined }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }

    function openCreateTransfer() {
        setEditingTransfer(null);
        transferForm.clearErrors();
        transferForm.setData(emptyTransferForm());
        setShowTransferModal(true);
    }

    function openEditTransfer(row: CaisseTransferRow) {
        setEditingTransfer(row);
        transferForm.clearErrors();
        transferForm.setData({
            caisse_destination_id: row.caisseDestinationId ?? '',
            montant: row.montant,
            date_transfert: row.dateTransfert ? row.dateTransfert.slice(0, 10) : todayIso(),
            note: row.note ?? '',
        });
        setShowTransferModal(true);
    }

    function closeTransferModal() {
        setShowTransferModal(false);
        setEditingTransfer(null);
    }

    function submitTransfer(event: FormEvent) {
        event.preventDefault();

        // The server redirects to ?tab=transferts, but Inertia form posts
        // preserve component state — without syncing the local `tab` here the
        // page would keep showing the previous tab (e.g. "Ma caisse") whose
        // dataset was NOT recomputed for this visit, rendering an empty page.
        const onSuccess = () => {
            closeTransferModal();
            setTab('transferts');
        };

        if (editingTransfer) {
            transferForm.put(`/backoffice/caisse-transfers/${editingTransfer.id}`, {
                preserveScroll: true,
                onSuccess,
            });

            return;
        }

        transferForm.post('/backoffice/caisse-transfers', {
            preserveScroll: true,
            onSuccess,
        });
    }

    function openConfirmAction(type: 'validate' | 'cancel', row: CaisseTransferRow) {
        setActionError(undefined);
        setConfirmAction({ type, row });
    }

    function closeConfirmAction() {
        setConfirmAction(null);
        setActionError(undefined);
    }

    function runConfirmAction() {
        if (!confirmAction) return;
        const { type, row } = confirmAction;
        setActionError(undefined);
        setActionProcessing(true);

        const url = type === 'validate'
            ? `/backoffice/caisse-transfers/${row.id}/validate`
            : `/backoffice/caisse-transfers/${row.id}`;
        const payload = type === 'validate' ? {} : { note: row.note ?? '', statut: 'Annulé' };

        router.put(url, payload, {
            preserveScroll: true,
            onSuccess: () => closeConfirmAction(),
            // Surface the server's refusal verbatim inside the dialog —
            // whichever field it was keyed on.
            onError: (errors) => setActionError(Object.values(errors)[0]),
            onFinish: () => setActionProcessing(false),
        });
    }

    return (
        <BackofficeLayout
            title="Gestion de la caisse"
            breadcrumbs={[{ label: 'Tableau de bord', href: '/backoffice/dashboard' }, { label: 'Gestion de la caisse' }]}
            actions={
                canViewTransfers ? (
                    <button type="button" className="btn btn-info d-flex align-items-center" onClick={openCreateTransfer}>
                        <i className="ti ti-arrows-exchange me-2" />
                        transférer
                    </button>
                ) : undefined
            }
        >
            <ul className="nav nav-tabs nav-tabs-solid mb-4" role="tablist">
                {canViewCaisses && (
                    <li className="nav-item">
                        <button type="button" className={`nav-link${tab === 'ma-caisse' ? ' active' : ''}`} onClick={() => switchTab('ma-caisse')}>
                            <i className="ti ti-cube me-1" />
                            Ma caisse
                        </button>
                    </li>
                )}
                {canViewTransfers && (
                    <li className="nav-item">
                        <button type="button" className={`nav-link${tab === 'transferts' ? ' active' : ''}`} onClick={() => switchTab('transferts')}>
                            <i className="ti ti-circle-check me-1" />
                            Validation de transfert
                        </button>
                    </li>
                )}
                {canViewCaisses && (
                    <li className="nav-item">
                        <button type="button" className={`nav-link${tab === 'globale' ? ' active' : ''}`} onClick={() => switchTab('globale')}>
                            <i className="ti ti-world me-1" />
                            Caisse globale
                        </button>
                    </li>
                )}
                {canViewComptes && (
                    <li className="nav-item">
                        <button type="button" className={`nav-link${tab === 'comptes' ? ' active' : ''}`} onClick={() => switchTab('comptes')}>
                            <i className="ti ti-currency-dollar me-1" />
                            Comptes de caisse
                        </button>
                    </li>
                )}
            </ul>

            {tab === 'ma-caisse' && canViewCaisses && journalMine && <JournalPanel scope="mine" data={journalMine} />}

            {tab === 'globale' && canViewCaisses && globale && <GlobalePanel data={globale} />}

            {tab === 'transferts' && canViewTransfers && transfers && (
                <Card bodyClassName="p-0 py-3">
                    <div className="px-3 pt-2">
                        <TableToolbar>
                            <div style={{ width: 200 }}>
                                <label className="form-label" htmlFor="tr-f-type">
                                    Type
                                </label>
                                <SelectField
                                    id="tr-f-type"
                                    options={transferTypeOptions}
                                    placeholder="Tous les types"
                                    value={transferFilters.typeFilter}
                                    onChange={(event) => reloadTransfers({ typeFilter: event.target.value })}
                                />
                            </div>
                            <div style={{ width: 180 }}>
                                <label className="form-label" htmlFor="tr-f-statut">
                                    Statut
                                </label>
                                <SelectField
                                    id="tr-f-statut"
                                    options={transferStatutOptions}
                                    placeholder="Choisir un statut"
                                    value={transferFilters.statutFilter}
                                    onChange={(event) => reloadTransfers({ statutFilter: event.target.value })}
                                />
                            </div>
                        </TableToolbar>
                    </div>

                    <TableLengthRow
                        search={<SearchInput value={transferFilters.search} onSearch={(value) => reloadTransfers({ search: value })} />}
                    />

                    {/* Server-computed over the WHOLE filtered set
                        (GetCaisseTransfersList). Summing transfers.data here
                        totalled only the visible page, so the figure moved on
                        every page click while the filters were unchanged. */}
                    <p className="px-3 fw-semibold">Total : {Number(transfersMontantTotal ?? 0).toFixed(2)}</p>

                    {transfers.data.length === 0 ? (
                        <EmptyState title="Aucun transfert" icon="ti ti-arrows-exchange" />
                    ) : (
                        <>
                            <DataTable
                                loading={isLoading}
                                head={
                                    <tr>
                                        <th>Expéditeur</th>
                                        <th>Destinataire</th>
                                        <th>Type de transaction</th>
                                        <th className="text-end">Montant</th>
                                        <th>Statut</th>
                                        <th>Date</th>
                                        <th>Note</th>
                                        <th className="text-end">Action</th>
                                    </tr>
                                }
                            >
                                {transfers.data.map((row) => (
                                    <tr key={row.id}>
                                        <td>{row.expediteur ?? '—'}</td>
                                        <td>{row.destinataire ?? '—'}</td>
                                        <td>
                                            <StatusBadge label={row.typeTransaction} variant={TRANSACTION_TYPE_BADGE[row.typeTransaction] ?? 'secondary'} />
                                        </td>
                                        <td className="text-end fw-medium">{Number(row.montant).toFixed(2)} DH</td>
                                        <td>
                                            <StatusBadge label={row.statut} variant={TRANSFER_STATUT_BADGE[row.statut] ?? 'warning'} dot />
                                        </td>
                                        <td>{row.dateTransfert ? row.dateTransfert.slice(0, 10) : '—'}</td>
                                        <td>{row.note ?? '—'}</td>
                                        <td>
                                            {/* Mutating actions are permission-gated client-side
                                                (hidden, not just refused): Modifier/Annuler need
                                                cash-transfers.update; Valider additionally needs
                                                the viewer to BE the recipient (row.canValidate),
                                                which already excludes the requester. */}
                                            <RowActions view={row.showUrl}>
                                                {row.isPending && canUpdateTransfers && (
                                                    <RowActionItem icon="ti-edit" onClick={() => openEditTransfer(row)}>
                                                        Modifier
                                                    </RowActionItem>
                                                )}
                                                {row.isPending && canUpdateTransfers && (
                                                    <RowActionItem icon="ti-x" onClick={() => openConfirmAction('cancel', row)}>
                                                        Annuler
                                                    </RowActionItem>
                                                )}
                                                {/* Valider is RECIPIENT-ONLY: row.canValidate is
                                                    true only for the employee whose own till is
                                                    the destination (and never the requester), so
                                                    a third-party validator - super-admin included
                                                    - cannot move money between other people's
                                                    tills. Matches CaisseTransferPolicy@validate. */}
                                                {row.canValidate && canValidateTransfers && (
                                                    <RowActionItem icon="ti-check" onClick={() => openConfirmAction('validate', row)}>
                                                        Accepter la réception
                                                    </RowActionItem>
                                                )}
                                            </RowActions>
                                        </td>
                                    </tr>
                                ))}
                            </DataTable>
                            <Pagination paginator={transfers} />
                        </>
                    )}
                </Card>
            )}

            {tab === 'comptes' && canViewComptes && comptes && (
                <ComptesPanel
                    comptes={comptes}
                    compteTypes={compteTypes}
                    compteTypeFilters={compteTypeFilters}
                    compteEtablissements={compteEtablissements}
                    permissions={comptePermissions}
                    filters={compteFilters}
                    onFilter={reloadComptes}
                />
            )}

            {/* Validate/cancel confirmation popup — server refusals
                (self-validation, permission, already validated…) are shown
                inside it via `error`, replacing the old window.confirm +
                inline page alert. */}
            <ConfirmDialog
                show={confirmAction !== null}
                title={confirmAction?.type === 'validate' ? 'Accepter la réception' : 'Annuler le transfert'}
                message={
                    confirmAction?.type === 'validate'
                        ? 'Vous confirmez avoir reçu ce montant. Les soldes des deux caisses vont bouger immédiatement.'
                        : 'Le transfert sera marqué comme annulé — aucun solde ne bouge.'
                }
                recordLabel={
                    confirmAction
                        ? `${Number(confirmAction.row.montant).toFixed(2)} DH — ${confirmAction.row.expediteur ?? '—'} → ${confirmAction.row.destinataire ?? '—'}`
                        : ''
                }
                error={actionError}
                processing={actionProcessing}
                onConfirm={runConfirmAction}
                onCancel={closeConfirmAction}
                icon={confirmAction?.type === 'validate' ? 'ti-circle-check' : 'ti-circle-x'}
                variant={confirmAction?.type === 'validate' ? 'primary' : 'danger'}
                confirmLabel={confirmAction?.type === 'validate' ? 'Accepter' : "Oui, annuler"}
                processingLabel={confirmAction?.type === 'validate' ? 'Validation…' : 'Annulation…'}
            />

            <Modal
                show={showTransferModal}
                title={editingTransfer ? 'Modifier le transfert' : 'Transfert à une autre caisse'}
                onClose={closeTransferModal}
                processing={transferForm.processing}
                size="lg"
                footer={<FormActions form="transfer-form" onCancel={closeTransferModal} processing={transferForm.processing} submitLabel="Valider" />}
            >
                <form id="transfer-form" onSubmit={submitTransfer}>
                    <div className="alert alert-info">
                        {editingTransfer
                            ? 'Seule la note peut être modifiée — les caisses et le montant sont figés.'
                            : 'Les soldes ne bougent pas maintenant : un autre employé doit valider ce transfert.'}
                    </div>
                    {!editingTransfer && !myCaisse && (
                        <div className="alert alert-warning">
                            Votre compte n'est lié à aucune caisse — impossible de demander un transfert.
                        </div>
                    )}
                    <div className="row">
                        <div className="col-md-6">
                            {/* The source is ALWAYS the acting employee's own till —
                                fixed server-side (even for super-admins), never a
                                dropdown. */}
                            <div className="mb-3">
                                <label className="form-label" htmlFor="ct-source">Caisse source</label>
                                <input
                                    id="ct-source"
                                    type="text"
                                    className="form-control bg-light"
                                    readOnly
                                    value={sourceCaisseNom}
                                />
                            </div>
                        </div>
                        <div className="col-md-6">
                            <SelectField
                                id="ct-destination"
                                label="Caisse destination"
                                options={editingTransfer ? allTransferCaisseOptions : transferCaisseOptions}
                                placeholder="Choisir une caisse destination"
                                required
                                disabled={!!editingTransfer || !myCaisse}
                                value={transferForm.data.caisse_destination_id}
                                onChange={(e) => transferForm.setData('caisse_destination_id', e.target.value === '' ? '' : Number(e.target.value))}
                                error={transferForm.errors.caisse_destination_id}
                            />
                        </div>
                        <div className="col-md-6">
                            {/* Read-only live preview of the selected source caisse's balance — informational only, the server independently re-validates. */}
                            <div className="mb-3">
                                <label className="form-label">Solde</label>
                                <div className="input-group">
                                    <input type="text" className="form-control bg-light" readOnly value={soldeSource !== null ? soldeSource.toFixed(2) : ''} />
                                    <span className="input-group-text">DH</span>
                                </div>
                            </div>
                        </div>
                        <div className="col-md-6">
                            {/* Read-only live preview: Solde − Montant à transférer. */}
                            <div className="mb-3">
                                <label className="form-label">Reste</label>
                                <div className="input-group">
                                    <input type="text" className="form-control bg-light" readOnly value={reste !== null ? reste.toFixed(2) : ''} />
                                    <span className="input-group-text">DH</span>
                                </div>
                            </div>
                        </div>
                        <div className="col-md-6">
                            {editingTransfer ? (
                                <div className="d-flex justify-content-between mb-3">
                                    <span className="text-muted">Montant</span>
                                    <span className="fw-medium">{Number(transferForm.data.montant).toFixed(2)} DH</span>
                                </div>
                            ) : (
                                <div className="mb-3">
                                    <label className="form-label" htmlFor="ct-montant">
                                        Montant à transférer<span className="text-danger ms-1">*</span>
                                    </label>
                                    <div className="input-group">
                                        <input
                                            id="ct-montant"
                                            type="number"
                                            step="0.01"
                                            min="0.01"
                                            required
                                            placeholder="ex : 500"
                                            className={`form-control${transferForm.errors.montant ? ' is-invalid' : ''}`}
                                            value={transferForm.data.montant}
                                            onChange={(e) => transferForm.setData('montant', e.target.value)}
                                        />
                                        <span className="input-group-text">DH</span>
                                    </div>
                                    {transferForm.errors.montant && <div className="text-danger fs-12 mt-1">{transferForm.errors.montant}</div>}
                                </div>
                            )}
                        </div>
                        <div className="col-md-6">
                            <DateField
                                id="ct-date"
                                label="Date"
                                required
                                disabled
                                value={transferForm.data.date_transfert}
                                onChange={(e) => transferForm.setData('date_transfert', e.target.value)}
                            />
                        </div>
                    </div>
                    <TextareaField
                        id="ct-note"
                        label="Note"
                        value={transferForm.data.note}
                        onChange={(e) => transferForm.setData('note', e.target.value)}
                        error={transferForm.errors.note}
                    />
                </form>
            </Modal>
        </BackofficeLayout>
    );
}
