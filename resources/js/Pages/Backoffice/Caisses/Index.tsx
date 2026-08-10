import { router, useForm } from '@inertiajs/react';
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
import SelectField from '@/Components/Forms/SelectField';
import DateField from '@/Components/Forms/DateField';
import FormField from '@/Components/Forms/FormField';
import TextareaField from '@/Components/Forms/TextareaField';
import FormActions from '@/Components/Forms/FormActions';
import StatusBadge from '@/Components/Details/StatusBadge';
import { useInertiaLoading } from '@/Hooks/useInertiaLoading';
import type { CaissesPageProps, CaisseJournalData, CaisseTransferRow, SelectOption } from '@/Types';

type Tab = 'ma-caisse' | 'journal' | 'transferts' | 'comptes';

interface TransferFormState {
    caisse_source_id: number | '';
    caisse_destination_id: number | '';
    montant: string;
    note: string;
}

function emptyTransferForm(): TransferFormState {
    return { caisse_source_id: '', caisse_destination_id: '', montant: '', note: '' };
}

const JOURNAL_TYPE_BADGE: Record<string, 'success' | 'danger' | 'warning' | 'info'> = {
    paiement: 'success',
    depense: 'danger',
    remboursement: 'warning',
    transfert: 'info',
};

const JOURNAL_TYPE_LABEL: Record<string, string> = {
    paiement: 'Paiement',
    depense: 'Dépense',
    remboursement: 'Remboursement',
    transfert: 'Transfert',
};

const TRANSFER_STATUT_BADGE: Record<string, 'success' | 'secondary' | 'warning'> = {
    'Validé': 'success',
    'Annulé': 'secondary',
    'En attente': 'warning',
};

function JournalPanel({ scope, data }: { scope: 'mine' | 'all'; data: CaisseJournalData }) {
    const [typeFilter, setTypeFilter] = useState('');
    const [dateFrom, setDateFrom] = useState('');
    const [dateTo, setDateTo] = useState('');
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
                <EmptyState
                    title="Aucune caisse"
                    message={scope === 'mine' ? "Votre compte n'est lié à aucune caisse." : 'Aucune caisse visible dans le contexte actuel.'}
                    icon="ti ti-cash"
                />
            </Card>
        );
    }

    return (
        <>
            <div className="row">
                <div className="col-md-4">
                    <div className="card">
                        <div className="card-body d-flex align-items-center">
                            <span className="avatar avatar-md bg-success-transparent rounded-circle me-3 d-inline-flex align-items-center justify-content-center">
                                <i className="ti ti-cash-banknote text-success fs-20" />
                            </span>
                            <div>
                                <p className="mb-0 text-muted">Paiements reçus</p>
                                <h5 className="mb-0 text-success">{Number(journal.totalEncaissements).toFixed(2)} DH</h5>
                            </div>
                        </div>
                    </div>
                </div>
                <div className="col-md-4">
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
                <div className="col-md-4">
                    <div className="card">
                        <div className="card-body d-flex align-items-center">
                            <span className="avatar avatar-md bg-primary-transparent rounded-circle me-3 d-inline-flex align-items-center justify-content-center">
                                <i className="ti ti-wallet text-primary fs-20" />
                            </span>
                            <div>
                                <p className="mb-0 text-muted">Solde</p>
                                <h5 className="mb-0 text-primary">{Number(journal.solde).toFixed(2)} DH</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <Card title={scope === 'mine' ? 'Ma caisse' : 'Journal des transactions'}>
                <TableToolbar>
                    {/* External label + bare SelectField, matching the Du/Au
                        blocks exactly so the three fields align on one row. */}
                    <div style={{ width: 200 }}>
                        <label className="form-label" htmlFor={`cj-type-${scope}`}>
                            Type de transaction
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
                    <div style={{ width: 160 }}>
                        <label className="form-label" htmlFor={`cj-from-${scope}`}>
                            Du
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
                    <div style={{ width: 160 }}>
                        <label className="form-label" htmlFor={`cj-to-${scope}`}>
                            Au
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

                {scope === 'mine' && (
                    <div className="text-muted mb-1">{journal.caissesInScope.map((c) => c.nom).join(', ')}</div>
                )}

                <div className="mb-3 fw-medium">
                    Paiements : {Number(journal.totauxParType.paiement ?? 0).toFixed(2)} DH
                    {' / '}Remboursements : {Number(journal.totauxParType.remboursement ?? 0).toFixed(2)} DH
                    {' / '}Dépenses : {Number(journal.totauxParType.depense ?? 0).toFixed(2)} DH
                    {' / '}Transferts : {Number(journal.totauxParType.transfert ?? 0).toFixed(2)} DH
                </div>

                {loading ? (
                    <p className="text-muted">Chargement…</p>
                ) : journal.rows.length === 0 ? (
                    <EmptyState title="Aucune transaction pour cette période" icon="ti ti-report-money" />
                ) : (
                    <>
                        <DataTable
                            head={
                                <tr>
                                    <th>Référence</th>
                                    <th>Type</th>
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
                                    <td>
                                        <StatusBadge label={JOURNAL_TYPE_LABEL[row.type] ?? row.type} variant={JOURNAL_TYPE_BADGE[row.type] ?? 'secondary'} />
                                    </td>
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

                        <div className="d-flex align-items-center justify-content-between mt-3">
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
 * "Gestion de la caisse" — replaces the Livewire tabbed page (Ma caisse +
 * Journal des transactions + Transferts + Comptes de caisse). The journal
 * tabs fetch their own data client-side (CaisseController@journal) so
 * filter/date/page changes don't reload the whole tabbed page — mirrors
 * CaisseJournal's own live re-render exactly, including the confirmed,
 * still-unaddressed performance characteristics (4 merged queries per
 * scope) which this phase deliberately preserves rather than rewrites
 * (docs/phase-10-finance-mapping.md Q4).
 */
export default function CaissesIndex({
    canViewCaisses,
    canViewTransfers,
    journalMine,
    journalAll,
    caisses,
    etablissements,
    statuts,
    transfers,
    transferStatutCounts,
    transferCaisses,
    transferStatuts,
    currentEmployeeId,
}: CaissesPageProps) {
    const isLoading = useInertiaLoading();
    const availableTabs: Tab[] = [
        ...(canViewCaisses ? (['ma-caisse', 'journal'] as Tab[]) : []),
        ...(canViewTransfers ? (['transferts'] as Tab[]) : []),
        ...(canViewCaisses ? (['comptes'] as Tab[]) : []),
    ];
    const requested = new URLSearchParams(window.location.search).get('tab') as Tab | null;
    const [tab, setTab] = useState<Tab>(requested && availableTabs.includes(requested) ? requested : availableTabs[0]);

    const [caissesFilters, setCaissesFilters] = useState({ search: '', etablissementFilter: '', statutFilter: '' });
    const [transferFilters, setTransferFilters] = useState({ search: '', statutFilter: transferStatuts[0] ?? 'En attente', caisseFilter: '' });

    const [showTransferModal, setShowTransferModal] = useState(false);
    const [editingTransfer, setEditingTransfer] = useState<CaisseTransferRow | null>(null);
    const [validateError, setValidateError] = useState<string | undefined>(undefined);

    const etablissementOptions: SelectOption[] = etablissements.map((e) => ({ value: e.id, label: e.nom }));
    const statutOptions: SelectOption[] = statuts.map((s) => ({ value: s, label: s }));
    const transferCaisseOptions: SelectOption[] = transferCaisses.map((c) => ({ value: c.id, label: `${c.nom} (${Number(c.solde).toFixed(2)} DH)` }));

    const transferForm = useForm<TransferFormState>(emptyTransferForm());

    function switchTab(next: Tab) {
        setTab(next);
        router.get('/backoffice/caisses', { tab: next }, { preserveState: true, preserveScroll: true, replace: true });
    }

    function reloadCaisses(next: Partial<typeof caissesFilters>) {
        const merged = { ...caissesFilters, ...next };
        setCaissesFilters(merged);
        router.get('/backoffice/caisses', { tab: 'comptes', ...merged, page: undefined }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
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
            caisse_source_id: row.caisseSourceId ?? '',
            caisse_destination_id: '',
            montant: row.montant,
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

        if (editingTransfer) {
            transferForm.put(`/backoffice/caisse-transfers/${editingTransfer.id}`, {
                preserveScroll: true,
                onSuccess: () => closeTransferModal(),
            });

            return;
        }

        transferForm.post('/backoffice/caisse-transfers', {
            preserveScroll: true,
            onSuccess: () => closeTransferModal(),
        });
    }

    function cancelTransfer(row: CaisseTransferRow) {
        router.put(
            `/backoffice/caisse-transfers/${row.id}`,
            { note: row.note ?? '', statut: 'Annulé' },
            { preserveScroll: true },
        );
    }

    function validateTransfer(row: CaisseTransferRow) {
        setValidateError(undefined);
        router.put(
            `/backoffice/caisse-transfers/${row.id}/validate`,
            {},
            {
                preserveScroll: true,
                onError: (errors) => setValidateError(errors.validate),
            },
        );
    }

    return (
        <BackofficeLayout
            title="Gestion de la caisse"
            breadcrumbs={[{ label: 'Tableau de bord', href: '/backoffice/dashboard' }, { label: 'Gestion de la caisse' }]}
        >
            <ul className="nav nav-tabs nav-tabs-solid mb-4" role="tablist">
                {canViewCaisses && (
                    <li className="nav-item">
                        <button type="button" className={`nav-link${tab === 'ma-caisse' ? ' active' : ''}`} onClick={() => switchTab('ma-caisse')}>
                            <i className="ti ti-wallet me-1" />
                            Ma caisse
                        </button>
                    </li>
                )}
                {canViewCaisses && (
                    <li className="nav-item">
                        <button type="button" className={`nav-link${tab === 'journal' ? ' active' : ''}`} onClick={() => switchTab('journal')}>
                            <i className="ti ti-report-money me-1" />
                            Journal des transactions
                        </button>
                    </li>
                )}
                {canViewTransfers && (
                    <li className="nav-item">
                        <button type="button" className={`nav-link${tab === 'transferts' ? ' active' : ''}`} onClick={() => switchTab('transferts')}>
                            <i className="ti ti-arrows-exchange me-1" />
                            Transferts de caisse
                        </button>
                    </li>
                )}
                {canViewCaisses && (
                    <li className="nav-item">
                        <button type="button" className={`nav-link${tab === 'comptes' ? ' active' : ''}`} onClick={() => switchTab('comptes')}>
                            <i className="ti ti-cash me-1" />
                            Comptes de caisse
                        </button>
                    </li>
                )}
            </ul>

            {tab === 'ma-caisse' && canViewCaisses && journalMine && <JournalPanel scope="mine" data={journalMine} />}
            {tab === 'journal' && canViewCaisses && journalAll && <JournalPanel scope="all" data={journalAll} />}

            {tab === 'comptes' && canViewCaisses && caisses && (
                <Card title="Comptes de caisse" bodyClassName="p-0 py-3">
                    <div className="px-3 pt-2">
                        <TableToolbar>
                            <div style={{ width: 220 }}>
                                <label className="form-label" htmlFor="cc-f-centre">
                                    Centre
                                </label>
                                <SelectField
                                    id="cc-f-centre"
                                    options={etablissementOptions}
                                    placeholder="Tous les centres"
                                    value={caissesFilters.etablissementFilter}
                                    onChange={(event) => reloadCaisses({ etablissementFilter: event.target.value })}
                                />
                            </div>
                            <div style={{ width: 180 }}>
                                <label className="form-label" htmlFor="cc-f-statut">
                                    Statut
                                </label>
                                <SelectField
                                    id="cc-f-statut"
                                    options={statutOptions}
                                    placeholder="Tous les statuts"
                                    value={caissesFilters.statutFilter}
                                    onChange={(event) => reloadCaisses({ statutFilter: event.target.value })}
                                />
                            </div>
                        </TableToolbar>
                    </div>

                    <TableLengthRow
                        search={<SearchInput value={caissesFilters.search} onSearch={(value) => reloadCaisses({ search: value })} />}
                    />

                    <div className="alert alert-info mx-3">
                        Chaque employé possède une caisse, créée automatiquement avec lui. Les soldes ne bougent qu'à
                        travers les paiements, dépenses, remboursements et transferts validés.
                    </div>

                    {caisses.data.length === 0 ? (
                        <EmptyState title="Aucune caisse" message="Une caisse apparaît dès qu'un employé est créé." icon="ti ti-cash" />
                    ) : (
                        <>
                            <DataTable
                                loading={isLoading}
                                head={
                                    <tr>
                                        <th>Nom</th>
                                        <th>Centre</th>
                                        <th>Responsable</th>
                                        <th className="text-end">Solde</th>
                                        <th>Statut</th>
                                        <th className="text-end">Action</th>
                                    </tr>
                                }
                            >
                                {caisses.data.map((row) => (
                                    <tr key={row.id}>
                                        <td className="fw-medium">
                                            <a href={row.showUrl} className="text-dark">
                                                {row.nom}
                                            </a>
                                        </td>
                                        <td>{row.centre ?? '—'}</td>
                                        <td>{row.responsable ?? '—'}</td>
                                        <td className="text-end fw-medium">{Number(row.solde).toFixed(2)} DH</td>
                                        <td>
                                            <StatusBadge label={row.statut} variant={row.statut === 'Active' ? 'success' : 'secondary'} dot />
                                        </td>
                                        <td>
                                            <RowActions view={row.showUrl} />
                                        </td>
                                    </tr>
                                ))}
                            </DataTable>
                            <Pagination paginator={caisses} showJumpToPage />
                        </>
                    )}
                </Card>
            )}

            {tab === 'transferts' && canViewTransfers && transfers && (
                <Card
                    title="Transferts de caisse"
                    bodyClassName="p-0 py-3"
                    tools={
                        <button
                            type="button"
                            className="btn btn-primary d-flex align-items-center mb-3"
                            onClick={openCreateTransfer}
                        >
                            <i className="ti ti-square-rounded-plus me-2" />
                            Demander un transfert
                        </button>
                    }
                >
                    <div className="px-3 pt-2">
                        <TableToolbar>
                            <div style={{ width: 220 }}>
                                <label className="form-label" htmlFor="tr-f-caisse">
                                    Caisse source
                                </label>
                                <SelectField
                                    id="tr-f-caisse"
                                    options={transferCaisseOptions}
                                    placeholder="Toutes les caisses"
                                    value={transferFilters.caisseFilter}
                                    onChange={(event) => reloadTransfers({ caisseFilter: event.target.value })}
                                />
                            </div>
                        </TableToolbar>
                    </div>

                    <ul className="nav nav-pills px-3 mb-3">
                        {transferStatuts.map((statut) => (
                            <li className="nav-item" key={statut}>
                                <button
                                    type="button"
                                    className={`nav-link${transferFilters.statutFilter === statut ? ' active' : ''}`}
                                    onClick={() => reloadTransfers({ statutFilter: statut })}
                                >
                                    {statut} <span className="badge bg-light text-dark ms-1">{transferStatutCounts[statut] ?? 0}</span>
                                </button>
                            </li>
                        ))}
                    </ul>

                    <TableLengthRow
                        search={<SearchInput value={transferFilters.search} onSearch={(value) => reloadTransfers({ search: value })} />}
                    />

                    {validateError && <div className="alert alert-danger mx-3">{validateError}</div>}

                    {transfers.data.length === 0 ? (
                        <EmptyState title="Aucun transfert" icon="ti ti-arrows-exchange" />
                    ) : (
                        <>
                            <DataTable
                                loading={isLoading}
                                head={
                                    <tr>
                                        <th>Référence</th>
                                        <th>De</th>
                                        <th>Vers</th>
                                        <th className="text-end">Montant</th>
                                        <th>Date</th>
                                        <th>Statut</th>
                                        <th className="text-end">Action</th>
                                    </tr>
                                }
                            >
                                {transfers.data.map((row) => (
                                    <tr key={row.id}>
                                        <td><a href={row.showUrl}><code>{row.reference}</code></a></td>
                                        <td>{row.caisseSource ?? '—'}</td>
                                        <td>{row.caisseDestination ?? '—'}</td>
                                        <td className="text-end fw-medium">{Number(row.montant).toFixed(2)} DH</td>
                                        <td>{row.dateTransfert ?? '—'}</td>
                                        <td>
                                            <StatusBadge label={row.statut} variant={TRANSFER_STATUT_BADGE[row.statut] ?? 'warning'} dot />
                                        </td>
                                        <td>
                                            <RowActions view={row.showUrl}>
                                                {row.isPending && (
                                                    <RowActionItem icon="ti-edit" onClick={() => openEditTransfer(row)}>
                                                        Modifier
                                                    </RowActionItem>
                                                )}
                                                {row.isPending && (
                                                    <RowActionItem icon="ti-x" onClick={() => cancelTransfer(row)}>
                                                        Annuler
                                                    </RowActionItem>
                                                )}
                                                {row.isPending && row.requestedById !== currentEmployeeId && (
                                                    <RowActionItem
                                                        icon="ti-check"
                                                        onClick={() => {
                                                            if (window.confirm('Valider ce transfert ? Les soldes vont bouger.')) {
                                                                validateTransfer(row);
                                                            }
                                                        }}
                                                    >
                                                        Valider
                                                    </RowActionItem>
                                                )}
                                            </RowActions>
                                        </td>
                                    </tr>
                                ))}
                            </DataTable>
                            <Pagination paginator={transfers} showJumpToPage />
                        </>
                    )}
                </Card>
            )}

            <Modal
                show={showTransferModal}
                title={editingTransfer ? 'Modifier le transfert' : 'Demander un transfert'}
                onClose={closeTransferModal}
                processing={transferForm.processing}
                footer={<FormActions form="transfer-form" onCancel={closeTransferModal} processing={transferForm.processing} />}
            >
                <form id="transfer-form" onSubmit={submitTransfer}>
                    <div className="alert alert-info">
                        {editingTransfer
                            ? 'Seule la note peut être modifiée — les caisses et le montant sont figés.'
                            : 'Les soldes ne bougent pas maintenant : un autre employé doit valider ce transfert.'}
                    </div>
                    <SelectField
                        id="ct-source"
                        label="Caisse source"
                        options={transferCaisseOptions}
                        placeholder="Sélectionner une caisse"
                        required
                        disabled={!!editingTransfer}
                        value={transferForm.data.caisse_source_id}
                        onChange={(e) => transferForm.setData('caisse_source_id', e.target.value === '' ? '' : Number(e.target.value))}
                        error={transferForm.errors.caisse_source_id}
                    />
                    <SelectField
                        id="ct-destination"
                        label="Caisse destination"
                        options={transferCaisseOptions}
                        placeholder="Sélectionner une caisse"
                        required
                        disabled={!!editingTransfer}
                        value={transferForm.data.caisse_destination_id}
                        onChange={(e) => transferForm.setData('caisse_destination_id', e.target.value === '' ? '' : Number(e.target.value))}
                        error={transferForm.errors.caisse_destination_id}
                    />
                    {editingTransfer ? (
                        <div className="d-flex justify-content-between mb-3">
                            <span className="text-muted">Montant</span>
                            <span className="fw-medium">{Number(transferForm.data.montant).toFixed(2)} DH</span>
                        </div>
                    ) : (
                        <FormField
                            id="ct-montant"
                            label="Montant"
                            type="number"
                            step="0.01"
                            min="0.01"
                            required
                            value={transferForm.data.montant}
                            onChange={(e) => transferForm.setData('montant', e.target.value)}
                            error={transferForm.errors.montant}
                        />
                    )}
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
