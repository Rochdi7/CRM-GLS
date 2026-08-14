import { router, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import EmptyState from '@/Components/Shared/EmptyState';
import DataTable from '@/Components/Tables/DataTable';
import TableToolbar from '@/Components/Tables/TableToolbar';
import FilterTextInput from '@/Components/Tables/FilterTextInput';
import TableLengthRow from '@/Components/Tables/TableLengthRow';
import Pagination from '@/Components/Tables/Pagination';
import RowActions, { RowActionDivider, RowActionItem } from '@/Components/Tables/RowActions';
import Modal from '@/Components/Modals/Modal';
import ConfirmDialog from '@/Components/Modals/ConfirmDialog';
import SelectField from '@/Components/Forms/SelectField';
import DateField from '@/Components/Forms/DateField';
import FormField from '@/Components/Forms/FormField';
import TextareaField from '@/Components/Forms/TextareaField';
import FormActions from '@/Components/Forms/FormActions';
import StatusBadge from '@/Components/Details/StatusBadge';
import { useInertiaLoading } from '@/Hooks/useInertiaLoading';
import type { ChequeRow, ChequesPageProps, SelectOption } from '@/Types';

interface ChequeFormState {
    source: string;
    student_id: number | '';
    proprietaire_nom: string;
    numero_cheque: string;
    montant: string;
    banque: string;
    date_reception: string;
    type: string;
    date_echeance: string;
    note: string;
}

function emptyForm(): ChequeFormState {
    return {
        source: 'Étudiant',
        student_id: '',
        proprietaire_nom: '',
        numero_cheque: '',
        montant: '',
        banque: '',
        date_reception: new Date().toISOString().slice(0, 10),
        type: 'Garantie (À encaisser)',
        date_echeance: '',
        note: '',
    };
}

function statutVariant(statut: string): 'primary' | 'warning' | 'success' | 'danger' {
    if (statut === 'Déposé') return 'warning';
    if (statut === 'Encaissé') return 'success';
    if (statut === 'Rejeté') return 'danger';
    return 'primary';
}

/**
 * Chèques en main — off-ledger inventory of physical checks received
 * (garantie / à déposer). A chèque here never moves money by itself:
 * paying with one happens in the Encaissements page ("Payer avec un
 * chèque"), which creates a normal Encaissement linked back via cheque_id
 * — its montant sum drives the "Reste" column. Lifecycle: En possession
 * -> Déposé (Remise à la banque) -> Encaissé | Rejeté.
 */
export default function ChequesIndex({
    cheques,
    filters,
    perPageOptions,
    sources,
    types,
    statuts,
    banques,
    students,
    canCreate,
    canUpdate,
}: ChequesPageProps) {
    const isLoading = useInertiaLoading();
    const [showModal, setShowModal] = useState(false);
    const [editingCheque, setEditingCheque] = useState<ChequeRow | null>(null);
    const [statutTarget, setStatutTarget] = useState<{ cheque: ChequeRow; statut: 'Déposé' | 'Encaissé' | 'Rejeté' } | null>(null);
    const [statutError, setStatutError] = useState<string | undefined>(undefined);
    const [statutProcessing, setStatutProcessing] = useState(false);

    const form = useForm<ChequeFormState>(emptyForm());

    const sourceOptions: SelectOption[] = sources.map((s) => ({ value: s, label: s }));
    const typeOptions: SelectOption[] = types.map((t) => ({ value: t, label: t }));
    const statutFilterOptions: SelectOption[] = statuts.map((s) => ({ value: s, label: s }));
    const banqueOptions: SelectOption[] = banques.map((b) => ({ value: b, label: b }));
    const studentOptions: SelectOption[] = students.map((s) => ({ value: s.id, label: s.nom }));

    function reload(nextFilters: Partial<typeof filters>) {
        router.get(
            '/backoffice/cheques',
            { ...filters, ...nextFilters, page: undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function openCreate() {
        setEditingCheque(null);
        form.reset();
        form.clearErrors();
        form.setData(emptyForm());
        setShowModal(true);
    }

    function openEdit(cheque: ChequeRow) {
        setEditingCheque(cheque);
        form.clearErrors();
        form.setData({
            source: cheque.source,
            student_id: cheque.studentId ?? '',
            proprietaire_nom: cheque.proprietaireNom ?? '',
            numero_cheque: cheque.numeroCheque,
            montant: cheque.montant,
            banque: cheque.banque ?? '',
            date_reception: cheque.dateReception ?? '',
            type: cheque.type,
            date_echeance: cheque.dateEcheance ?? '',
            note: cheque.note,
        });
        setShowModal(true);
    }

    function closeModal() {
        setShowModal(false);
        setEditingCheque(null);
        form.reset();
        form.clearErrors();
    }

    function handleSourceChange(source: string) {
        form.setData((data) => ({
            ...data,
            source,
            student_id: source === 'Étudiant' ? data.student_id : '',
            proprietaire_nom: source === 'Étudiant' ? '' : data.proprietaire_nom,
        }));
    }

    function submit(event: FormEvent) {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => closeModal() };

        if (editingCheque) {
            form.put(`/backoffice/cheques/${editingCheque.id}`, options);
        } else {
            form.post('/backoffice/cheques', options);
        }
    }

    function confirmStatut(cheque: ChequeRow, statut: 'Déposé' | 'Encaissé' | 'Rejeté') {
        setStatutTarget({ cheque, statut });
        setStatutError(undefined);
    }

    function handleStatutConfirm() {
        if (!statutTarget) {
            return;
        }

        setStatutProcessing(true);
        router.patch(
            `/backoffice/cheques/${statutTarget.cheque.id}/statut`,
            { statut: statutTarget.statut },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setStatutTarget(null);
                    setStatutError(undefined);
                },
                onError: (errors) => {
                    setStatutError(errors.statut ?? 'Action impossible.');
                },
                onFinish: () => setStatutProcessing(false),
            },
        );
    }

    const statutCopy: Record<'Déposé' | 'Encaissé' | 'Rejeté', { title: string; message: string; icon: string; variant: 'primary' | 'danger'; confirmLabel: string; processingLabel: string }> = {
        'Déposé': {
            title: 'Remise à la banque',
            message: 'Marquer ce chèque comme déposé à la banque ?',
            icon: 'ti-building-bank',
            variant: 'primary',
            confirmLabel: 'Oui, déposer',
            processingLabel: 'Dépôt…',
        },
        'Encaissé': {
            title: 'Marquer comme encaissé',
            message: 'Confirmer que ce chèque a été encaissé par la banque ?',
            icon: 'ti-check',
            variant: 'primary',
            confirmLabel: 'Oui, encaissé',
            processingLabel: 'Enregistrement…',
        },
        'Rejeté': {
            title: 'Marquer comme rejeté',
            message: 'Confirmer que ce chèque a été rejeté (impayé) par la banque ?',
            icon: 'ti-x',
            variant: 'danger',
            confirmLabel: 'Oui, rejeté',
            processingLabel: 'Enregistrement…',
        },
    };

    return (
        <BackofficeLayout
            title="Chèques"
            breadcrumbs={[{ label: 'Tableau de bord', href: '/backoffice/dashboard' }, { label: 'Chèques' }]}
            actions={
                canCreate ? (
                    <button type="button" className="btn btn-primary d-flex align-items-center" onClick={openCreate}>
                        <i className="ti ti-square-rounded-plus me-2" />
                        Ajouter un chèque
                    </button>
                ) : undefined
            }
        >
            {/* Cross-links back into Encaissements, mirroring that page's own tab bar exactly (Encaissements /
                Avances / Chèques) — Avances is a view=avance filter on the Encaissements page, not its own
                route, so it links there with the query string. */}
            <ul className="nav nav-tabs p-0 border-bottom rounded-0 mb-4" role="tablist">
                <li className="nav-item" role="presentation">
                    <a href="/backoffice/encaissements" className="nav-link d-inline-flex align-items-center">
                        <i className="ti ti-cash-banknote me-2" aria-hidden="true" />
                        Encaissements
                    </a>
                </li>
                <li className="nav-item" role="presentation">
                    <a href="/backoffice/encaissements?view=avance" className="nav-link d-inline-flex align-items-center">
                        <i className="ti ti-clock-dollar me-2" aria-hidden="true" />
                        Avances
                    </a>
                </li>
                <li className="nav-item" role="presentation">
                    <button type="button" className="nav-link d-inline-flex align-items-center active" aria-current="page">
                        <i className="ti ti-building-bank me-2" aria-hidden="true" />
                        Chèques
                    </button>
                </li>
            </ul>

            <Card title="Chèques" bodyClassName="p-0 py-3">
                <div className="px-3 pt-2">
                    <TableToolbar>
                        <div style={{ width: 160 }}>
                            <label className="form-label" htmlFor="chq-f-numero">
                                Num Chèque
                            </label>
                            <FilterTextInput
                                id="chq-f-numero"
                                value={filters.numeroFilter}
                                onChange={(value) => reload({ numeroFilter: value })}
                                placeholder="ex : A12445"
                            />
                        </div>
                        <div style={{ width: 180 }}>
                            <label className="form-label" htmlFor="chq-f-proprietaire">
                                Propriétaire
                            </label>
                            <FilterTextInput
                                id="chq-f-proprietaire"
                                value={filters.proprietaireFilter}
                                onChange={(value) => reload({ proprietaireFilter: value })}
                                placeholder="ex : Alaoui"
                            />
                        </div>
                        <div style={{ width: 200 }}>
                            <label className="form-label" htmlFor="chq-f-banque">
                                Banque
                            </label>
                            <SelectField
                                id="chq-f-banque"
                                options={banqueOptions}
                                placeholder="Toutes les banques"
                                value={filters.banqueFilter}
                                onChange={(event) => reload({ banqueFilter: event.target.value })}
                            />
                        </div>
                        <div style={{ width: 200 }}>
                            <label className="form-label" htmlFor="chq-f-type">
                                Type
                            </label>
                            <SelectField
                                id="chq-f-type"
                                options={typeOptions}
                                placeholder="Tous les types"
                                value={filters.typeFilter}
                                onChange={(event) => reload({ typeFilter: event.target.value })}
                            />
                        </div>
                        <div style={{ width: 180 }}>
                            <label className="form-label" htmlFor="chq-f-statut">
                                Statut
                            </label>
                            <SelectField
                                id="chq-f-statut"
                                options={statutFilterOptions}
                                placeholder="Tous les statuts"
                                value={filters.statutFilter}
                                onChange={(event) => reload({ statutFilter: event.target.value })}
                            />
                        </div>
                        <div style={{ width: 170 }}>
                            <label className="form-label" htmlFor="chq-f-echeance-from">
                                Échéance du
                            </label>
                            <DateField
                                id="chq-f-echeance-from"
                                value={filters.dateEcheanceFrom}
                                onChange={(event) => reload({ dateEcheanceFrom: event.target.value })}
                            />
                        </div>
                        <div style={{ width: 170 }}>
                            <label className="form-label" htmlFor="chq-f-echeance-to">
                                Échéance au
                            </label>
                            <DateField
                                id="chq-f-echeance-to"
                                value={filters.dateEcheanceTo}
                                onChange={(event) => reload({ dateEcheanceTo: event.target.value })}
                            />
                        </div>
                    </TableToolbar>
                </div>

                <TableLengthRow
                    perPage={filters.perPage}
                    perPageOptions={perPageOptions}
                    onPerPageChange={(perPage) => reload({ perPage })}
                />

                {cheques.data.length === 0 ? (
                    <EmptyState title="Aucun chèque" message="Ajoutez votre premier chèque pour commencer." icon="ti ti-file-invoice" />
                ) : (
                    <>
                        <DataTable
                            loading={isLoading}
                            head={
                                <tr>
                                    <th>Num Chèque</th>
                                    <th>Propriétaire</th>
                                    <th>Téléphone</th>
                                    <th>Montant</th>
                                    <th>Reste</th>
                                    <th>Banque</th>
                                    <th>Type</th>
                                    <th>Date d'échéance</th>
                                    <th>Statut</th>
                                    <th className="text-end">Action</th>
                                </tr>
                            }
                        >
                            {cheques.data.map((cheque) => (
                                <tr key={cheque.id}>
                                    <td>
                                        <code>{cheque.numeroCheque}</code>
                                    </td>
                                    <td className="fw-medium">{cheque.proprietaire ?? '—'}</td>
                                    <td>
                                        {cheque.telephone ? (
                                            <a href={`tel:${cheque.telephone}`} className="d-inline-flex align-items-center">
                                                <i className="ti ti-phone me-1" />
                                                {cheque.telephone}
                                            </a>
                                        ) : (
                                            '—'
                                        )}
                                    </td>
                                    <td>{Number(cheque.montant).toFixed(2)} DH</td>
                                    <td className="fw-medium">{Number(cheque.reste).toFixed(2)} DH</td>
                                    <td>{cheque.banque ?? '—'}</td>
                                    <td>{cheque.type}</td>
                                    <td>{cheque.dateEcheance ?? '—'}</td>
                                    <td>
                                        <StatusBadge label={cheque.statut} variant={statutVariant(cheque.statut)} dot />
                                    </td>
                                    <td className="text-end">
                                        <RowActions>
                                            {canUpdate && (
                                                <RowActionItem icon="ti-edit" onClick={() => openEdit(cheque)}>
                                                    Modifier
                                                </RowActionItem>
                                            )}
                                            {canUpdate && cheque.statut === 'En possession' && (
                                                <>
                                                    <RowActionDivider />
                                                    <RowActionItem icon="ti-building-bank" onClick={() => confirmStatut(cheque, 'Déposé')}>
                                                        Remise à la banque
                                                    </RowActionItem>
                                                </>
                                            )}
                                            {canUpdate && cheque.statut === 'Déposé' && (
                                                <>
                                                    <RowActionDivider />
                                                    <RowActionItem icon="ti-check" onClick={() => confirmStatut(cheque, 'Encaissé')}>
                                                        Marquer encaissé
                                                    </RowActionItem>
                                                    <RowActionItem icon="ti-x" danger onClick={() => confirmStatut(cheque, 'Rejeté')}>
                                                        Marquer rejeté
                                                    </RowActionItem>
                                                </>
                                            )}
                                        </RowActions>
                                    </td>
                                </tr>
                            ))}
                        </DataTable>
                        <Pagination paginator={cheques} showJumpToPage />
                    </>
                )}
            </Card>

            <Modal
                show={showModal}
                title={editingCheque ? 'Modifier le chèque' : 'Ajouter un chèque'}
                onClose={closeModal}
                processing={form.processing}
                size="lg"
                footer={<FormActions form="cheque-form" onCancel={closeModal} processing={form.processing} />}
            >
                <form id="cheque-form" onSubmit={submit}>
                    <div className="row">
                        <div className="col-md-6">
                            <SelectField
                                id="chq-source"
                                label="Source"
                                required
                                options={sourceOptions}
                                value={form.data.source}
                                onChange={(event) => handleSourceChange(event.target.value)}
                                error={form.errors.source}
                            />
                        </div>
                        <div className="col-md-6">
                            {form.data.source === 'Étudiant' ? (
                                <SelectField
                                    id="chq-student"
                                    label="Propriétaire"
                                    required
                                    options={studentOptions}
                                    placeholder="Choisir un étudiant"
                                    value={form.data.student_id}
                                    onChange={(event) =>
                                        form.setData('student_id', event.target.value ? Number(event.target.value) : '')
                                    }
                                    error={form.errors.student_id}
                                />
                            ) : (
                                <FormField
                                    id="chq-proprietaire"
                                    label="Propriétaire"
                                    required
                                    value={form.data.proprietaire_nom}
                                    onChange={(event) => form.setData('proprietaire_nom', event.target.value)}
                                    error={form.errors.proprietaire_nom}
                                    placeholder="Nom du propriétaire"
                                />
                            )}
                        </div>
                        <div className="col-md-4">
                            <FormField
                                id="chq-numero"
                                label="Num Chèque"
                                required
                                value={form.data.numero_cheque}
                                onChange={(event) => form.setData('numero_cheque', event.target.value)}
                                error={form.errors.numero_cheque}
                                placeholder="ex : A12445"
                            />
                        </div>
                        <div className="col-md-4">
                            <FormField
                                id="chq-montant"
                                label="Montant"
                                type="number"
                                step="0.01"
                                min="0.01"
                                required
                                value={form.data.montant}
                                onChange={(event) => form.setData('montant', event.target.value)}
                                error={form.errors.montant}
                                placeholder="ex : 500"
                            />
                        </div>
                        <div className="col-md-4">
                            <SelectField
                                id="chq-banque"
                                label="Banque"
                                options={banqueOptions}
                                placeholder="Choisir un élément"
                                value={form.data.banque}
                                onChange={(event) => form.setData('banque', event.target.value)}
                                error={form.errors.banque}
                            />
                        </div>
                        <div className="col-md-4">
                            <DateField
                                id="chq-reception"
                                label="Date de réception"
                                required
                                value={form.data.date_reception}
                                onChange={(event) => form.setData('date_reception', event.target.value)}
                                error={form.errors.date_reception}
                            />
                        </div>
                        <div className="col-md-4">
                            <SelectField
                                id="chq-type"
                                label="Type"
                                required
                                options={typeOptions}
                                value={form.data.type}
                                onChange={(event) => form.setData('type', event.target.value)}
                                error={form.errors.type}
                            />
                        </div>
                        <div className="col-md-4">
                            <DateField
                                id="chq-echeance"
                                label="Date d'échéance"
                                value={form.data.date_echeance}
                                onChange={(event) => form.setData('date_echeance', event.target.value)}
                                error={form.errors.date_echeance}
                            />
                        </div>
                        <div className="col-12">
                            <TextareaField
                                id="chq-note"
                                label="Note"
                                rows={3}
                                value={form.data.note}
                                onChange={(event) => form.setData('note', event.target.value)}
                                error={form.errors.note}
                            />
                        </div>
                    </div>
                </form>
            </Modal>

            <ConfirmDialog
                show={statutTarget !== null}
                title={statutTarget ? statutCopy[statutTarget.statut].title : ''}
                recordLabel={statutTarget?.cheque.numeroCheque ?? ''}
                message={statutTarget ? statutCopy[statutTarget.statut].message : ''}
                icon={statutTarget ? statutCopy[statutTarget.statut].icon : undefined}
                variant={statutTarget ? statutCopy[statutTarget.statut].variant : undefined}
                confirmLabel={statutTarget ? statutCopy[statutTarget.statut].confirmLabel : undefined}
                processingLabel={statutTarget ? statutCopy[statutTarget.statut].processingLabel : undefined}
                error={statutError}
                processing={statutProcessing}
                onConfirm={handleStatutConfirm}
                onCancel={() => {
                    setStatutTarget(null);
                    setStatutError(undefined);
                }}
            />
        </BackofficeLayout>
    );
}
