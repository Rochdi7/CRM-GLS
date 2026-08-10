import { Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import Modal from '@/Components/Modals/Modal';
import ConfirmDialog from '@/Components/Modals/ConfirmDialog';
import FormField from '@/Components/Forms/FormField';
import SelectField from '@/Components/Forms/SelectField';
import TextareaField from '@/Components/Forms/TextareaField';
import FormActions from '@/Components/Forms/FormActions';
import RelatedRecordsTable from '@/Components/Details/RelatedRecordsTable';
import StatusBadge from '@/Components/Details/StatusBadge';
import Pagination from '@/Components/Tables/Pagination';
import TableLengthRow from '@/Components/Tables/TableLengthRow';
import TableToolbar from '@/Components/Tables/TableToolbar';
import SearchInput from '@/Components/Tables/SearchInput';
import DateField from '@/Components/Forms/DateField';
import RowActions, { RowActionItem } from '@/Components/Tables/RowActions';
import type { PaginatedData, SeanceForm, SeanceRow, SelectOption } from '@/Types';

interface GroupOption extends SelectOption {
    enseignantId: number | null;
}

interface SeancesIndexProps {
    seances: PaginatedData<SeanceRow>;
    filters: {
        search: string;
        groupFilter: string;
        statutFilter: string;
        enseignantFilter: string;
        dateFrom: string;
        dateTo: string;
        perPage: number;
    };
    perPageOptions: number[];
    groupOptions: GroupOption[];
    enseignants: SelectOption[];
    statuts: string[];
    permissions: { create: boolean; update: boolean; delete: boolean; mark: boolean };
}

const EMPTY_FORM: SeanceForm = {
    group_id: '',
    date_seance: '',
    heure_debut: '',
    heure_fin: '',
    enseignant_id: '',
    statut: 'Prévue',
    note: '',
};

function statutVariant(statut: string): 'success' | 'secondary' | 'danger' | 'info' {
    if (statut === 'Effectuée') return 'success';
    if (statut === 'Annulée') return 'danger';
    return 'info';
}

/**
 * Présences — séances list + modal add/edit. The roll call itself (fiche de
 * présence) lives on the séance detail page ("Faire l'appel"). Center +
 * academic year come from the séance's group server-side, never the form.
 */
export default function SeancesIndex({
    seances,
    filters,
    perPageOptions,
    groupOptions,
    enseignants,
    statuts,
    permissions,
}: SeancesIndexProps) {
    const [showModal, setShowModal] = useState(false);
    const [editingId, setEditingId] = useState<number | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<SeanceRow | null>(null);
    const [deleteError, setDeleteError] = useState<string>();
    const [deleting, setDeleting] = useState(false);

    const form = useForm<SeanceForm>(EMPTY_FORM);

    function reload(changes: Partial<Record<string, string | number>>) {
        router.get(
            '/backoffice/seances',
            { ...filters, ...changes, page: undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function openCreate() {
        form.setData({ ...EMPTY_FORM, date_seance: new Date().toISOString().slice(0, 10) });
        form.clearErrors();
        setEditingId(null);
        setShowModal(true);
    }

    function openEdit(row: SeanceRow) {
        form.setData({
            group_id: String(row.groupId),
            date_seance: row.dateSeance,
            heure_debut: row.heureDebut ?? '',
            heure_fin: row.heureFin ?? '',
            enseignant_id: row.enseignantId ? String(row.enseignantId) : '',
            statut: row.statut,
            note: row.note ?? '',
        });
        form.clearErrors();
        setEditingId(row.id);
        setShowModal(true);
    }

    function closeModal() {
        setShowModal(false);
        setEditingId(null);
        form.reset();
        form.clearErrors();
    }

    function handleGroupChange(groupId: string) {
        const group = groupOptions.find((option) => String(option.value) === groupId);
        form.setData((data) => ({
            ...data,
            group_id: groupId,
            // Pre-select the group's own teacher; still editable.
            enseignant_id: group?.enseignantId ? String(group.enseignantId) : data.enseignant_id,
        }));
    }

    function handleSubmit(event: React.FormEvent) {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => closeModal() };

        if (editingId) {
            form.put(`/backoffice/seances/${editingId}`, options);
        } else {
            form.post('/backoffice/seances', options);
        }
    }

    function confirmDelete() {
        if (!deleteTarget) {
            return;
        }

        setDeleting(true);
        setDeleteError(undefined);

        form.transform(() => ({}));
        form.delete(`/backoffice/seances/${deleteTarget.id}`, {
            preserveScroll: true,
            onFinish: () => form.transform((data) => data),
            onSuccess: () => {
                setDeleteTarget(null);
                setDeleting(false);
            },
            onError: (errors: Record<string, string>) => {
                setDeleteError(errors.delete ?? 'Suppression impossible.');
                setDeleting(false);
            },
        });
    }

    return (
        <BackofficeLayout
            title="Présences"
            breadcrumbs={[
                { label: 'Tableau de bord', href: '/backoffice/dashboard' },
                { label: 'Présences' },
            ]}
            actions={
                permissions.create && (
                    <button type="button" className="btn btn-primary d-flex align-items-center" onClick={openCreate}>
                        <i className="ti ti-square-rounded-plus me-2" />
                        Ajouter une séance
                    </button>
                )
            }
        >
            <Card title="Séances" bodyClassName="p-0 py-3">
                {/* Filter row (reference CRM's Séances filters): Groupe/
                    Statut/Enseignant/Date de début/Date de fin. */}
                <div className="px-3 pt-2">
                    <TableToolbar>
                        <div style={{ width: 220 }}>
                            <label className="form-label" htmlFor="seance-f-groupe">
                                Groupe
                            </label>
                            <SelectField
                                id="seance-f-groupe"
                                options={groupOptions}
                                placeholder="Choisir un niveau scolaire"
                                value={filters.groupFilter}
                                onChange={(event) => reload({ groupFilter: event.target.value })}
                            />
                        </div>
                        <div style={{ width: 190 }}>
                            <label className="form-label" htmlFor="seance-f-statut">
                                Statut
                            </label>
                            <SelectField
                                id="seance-f-statut"
                                options={statuts.map((statut) => ({ value: statut, label: statut }))}
                                placeholder="Choisir un statut"
                                value={filters.statutFilter}
                                onChange={(event) => reload({ statutFilter: event.target.value })}
                            />
                        </div>
                        <div style={{ width: 220 }}>
                            <label className="form-label" htmlFor="seance-f-enseignant">
                                Enseignant
                            </label>
                            <SelectField
                                id="seance-f-enseignant"
                                options={enseignants}
                                placeholder="Choisir un enseignant"
                                value={filters.enseignantFilter}
                                onChange={(event) => reload({ enseignantFilter: event.target.value })}
                            />
                        </div>
                        <div style={{ width: 170 }}>
                            <label className="form-label" htmlFor="seance-f-du">
                                Date de début
                            </label>
                            <DateField
                                id="seance-f-du"
                                value={filters.dateFrom}
                                onChange={(event) => reload({ dateFrom: event.target.value })}
                            />
                        </div>
                        <div style={{ width: 170 }}>
                            <label className="form-label" htmlFor="seance-f-au">
                                Date de fin
                            </label>
                            <DateField
                                id="seance-f-au"
                                value={filters.dateTo}
                                onChange={(event) => reload({ dateTo: event.target.value })}
                            />
                        </div>
                    </TableToolbar>
                </div>

                <TableLengthRow
                    perPage={filters.perPage}
                    perPageOptions={perPageOptions}
                    onPerPageChange={(perPage) => reload({ perPage })}
                    search={
                        <SearchInput
                            value={filters.search}
                            onSearch={(search) => reload({ search })}
                            placeholder="Rechercher un groupe"
                        />
                    }
                />

                <RelatedRecordsTable
                    isEmpty={seances.data.length === 0}
                    emptyTitle="Aucune séance pour le moment"
                    emptyIcon="ti ti-calendar-check"
                    head={
                        <tr>
                            <th>Date</th>
                            <th>Horaire</th>
                            <th>Groupe</th>
                            <th>Enseignant</th>
                            <th>Présences</th>
                            <th>Statut</th>
                            <th className="text-end">Action</th>
                        </tr>
                    }
                >
                    {seances.data.map((row) => (
                        <tr key={row.id}>
                            <td className="fw-medium">
                                <Link href={row.showUrl}>{row.dateSeance}</Link>
                            </td>
                            <td>
                                {row.heureDebut ? `${row.heureDebut}${row.heureFin ? ` – ${row.heureFin}` : ''}` : '—'}
                            </td>
                            <td>
                                {row.groupNom ?? '—'}
                                {row.groupNiveau && (
                                    <span className="badge badge-soft-secondary ms-2">{row.groupNiveau}</span>
                                )}
                            </td>
                            <td>{row.enseignant ?? '—'}</td>
                            <td>
                                {row.presencesCount > 0 ? (
                                    <>
                                        <span className="badge badge-soft-success me-1">
                                            {row.presentsCount} présent{row.presentsCount > 1 ? 's' : ''}
                                        </span>
                                        <span className="badge badge-soft-danger">
                                            {row.absentsCount} absent{row.absentsCount > 1 ? 's' : ''}
                                        </span>
                                    </>
                                ) : (
                                    <span className="text-muted">Appel non fait</span>
                                )}
                            </td>
                            <td>
                                <StatusBadge label={row.statut} variant={statutVariant(row.statut)} dot />
                            </td>
                            <td className="text-end">
                                <div className="d-flex align-items-center justify-content-end gap-2">
                                    {permissions.mark && (
                                        <Link
                                            href={row.showUrl}
                                            className="btn btn-outline-primary btn-sm d-inline-flex align-items-center"
                                        >
                                            <i className="ti ti-checklist me-1" />
                                            Faire l'appel
                                        </Link>
                                    )}
                                    <RowActions>
                                        {permissions.update && (
                                            <RowActionItem icon="ti-edit" onClick={() => openEdit(row)}>
                                                Modifier
                                            </RowActionItem>
                                        )}
                                        {permissions.delete && (
                                            <RowActionItem
                                                icon="ti-trash"
                                                danger
                                                onClick={() => {
                                                    setDeleteTarget(row);
                                                    setDeleteError(undefined);
                                                }}
                                            >
                                                Supprimer
                                            </RowActionItem>
                                        )}
                                    </RowActions>
                                </div>
                            </td>
                        </tr>
                    ))}
                </RelatedRecordsTable>
                <Pagination paginator={seances} showJumpToPage />
            </Card>

            <Modal
                show={showModal}
                title={editingId ? 'Modifier la séance' : 'Ajouter une séance'}
                onClose={closeModal}
                processing={form.processing}
            >
                <form onSubmit={handleSubmit}>
                    <div className="row">
                        <div className="col-md-6">
                            <SelectField
                                id="seance-group"
                                label="Groupe"
                                required
                                disabled={editingId !== null}
                                options={groupOptions}
                                placeholder="Sélectionner un groupe"
                                value={form.data.group_id}
                                onChange={(event) => handleGroupChange(event.target.value)}
                                error={form.errors.group_id}
                            />
                        </div>
                        <div className="col-md-6">
                            <FormField
                                id="seance-date"
                                label="Date"
                                type="date"
                                required
                                value={form.data.date_seance}
                                onChange={(event) => form.setData('date_seance', event.target.value)}
                                error={form.errors.date_seance}
                            />
                        </div>
                        <div className="col-md-6">
                            <FormField
                                id="seance-debut"
                                label="Heure de début"
                                type="time"
                                value={form.data.heure_debut}
                                onChange={(event) => form.setData('heure_debut', event.target.value)}
                                error={form.errors.heure_debut}
                            />
                        </div>
                        <div className="col-md-6">
                            <FormField
                                id="seance-fin"
                                label="Heure de fin"
                                type="time"
                                value={form.data.heure_fin}
                                onChange={(event) => form.setData('heure_fin', event.target.value)}
                                error={form.errors.heure_fin}
                            />
                        </div>
                        <div className="col-md-6">
                            <SelectField
                                id="seance-enseignant"
                                label="Enseignant"
                                options={enseignants}
                                placeholder="—"
                                value={form.data.enseignant_id}
                                onChange={(event) => form.setData('enseignant_id', event.target.value)}
                                error={form.errors.enseignant_id}
                            />
                        </div>
                        <div className="col-md-6">
                            <SelectField
                                id="seance-statut"
                                label="Statut"
                                required
                                options={statuts.map((statut) => ({ value: statut, label: statut }))}
                                value={form.data.statut}
                                onChange={(event) => form.setData('statut', event.target.value)}
                                error={form.errors.statut}
                            />
                        </div>
                        <div className="col-12">
                            <TextareaField
                                id="seance-note"
                                label="Note"
                                rows={2}
                                value={form.data.note}
                                onChange={(event) => form.setData('note', event.target.value)}
                                error={form.errors.note}
                            />
                        </div>
                    </div>
                    <div className="d-flex justify-content-end gap-2 mt-3">
                        <FormActions onCancel={closeModal} processing={form.processing} />
                    </div>
                </form>
            </Modal>

            <ConfirmDialog
                show={deleteTarget !== null}
                title="Supprimer cette séance ?"
                recordLabel={deleteTarget ? `${deleteTarget.groupNom ?? ''} — ${deleteTarget.dateSeance}` : ''}
                message="Cette action est définitive : la séance et son appel seront supprimés."
                error={deleteError}
                processing={deleting}
                onConfirm={confirmDelete}
                onCancel={() => setDeleteTarget(null)}
            />
        </BackofficeLayout>
    );
}
