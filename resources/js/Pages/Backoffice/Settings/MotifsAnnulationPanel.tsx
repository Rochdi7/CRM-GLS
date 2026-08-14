import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import Modal from '@/Components/Modals/Modal';
import ConfirmDialog from '@/Components/Modals/ConfirmDialog';
import FormField from '@/Components/Forms/FormField';
import SelectField from '@/Components/Forms/SelectField';
import FormActions from '@/Components/Forms/FormActions';
import RelatedRecordsTable from '@/Components/Details/RelatedRecordsTable';
import StatusBadge from '@/Components/Details/StatusBadge';
import Pagination from '@/Components/Tables/Pagination';
import RowActions, { RowActionItem } from '@/Components/Tables/RowActions';
import type { CrudPermissions, MotifAnnulationForm, MotifAnnulationRow, PaginatedData, SelectOption } from '@/Types';

interface MotifsAnnulationPanelProps {
    motifsAnnulation: PaginatedData<MotifAnnulationRow>;
    permissions: CrudPermissions;
}

const STATUT_OPTIONS: SelectOption[] = [
    { value: 'Actif', label: 'Actif' },
    { value: 'Inactif', label: 'Inactif' },
];

const EMPTY_FORM: MotifAnnulationForm = { nom: '', statut: 'Actif' };

/**
 * Raisons d'annulation ou archivage CRUD panel — the managed reason list for
 * cancelling/archiving inscriptions. Restricted to super-admin:
 * `cancellation-reasons.*` permissions are absent from every role in
 * PermissionRegistry::matrix(), so only super-admin's Gate::before bypass
 * ever reaches this tab (SettingController::TAB_PERMISSIONS). System rows
 * ("Changement de groupe" — written by the group-change flow) show a
 * Système badge and expose no edit/delete actions.
 */
export default function MotifsAnnulationPanel({ motifsAnnulation, permissions }: MotifsAnnulationPanelProps) {
    const [showModal, setShowModal] = useState(false);
    const [editingId, setEditingId] = useState<number | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<MotifAnnulationRow | null>(null);
    const [deleteError, setDeleteError] = useState<string>();
    const [deleting, setDeleting] = useState(false);

    const form = useForm<MotifAnnulationForm>(EMPTY_FORM);

    function openCreate() {
        form.reset();
        form.clearErrors();
        setEditingId(null);
        setShowModal(true);
    }

    function openEdit(row: MotifAnnulationRow) {
        form.setData({ nom: row.nom, statut: row.statut });
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

    function handleSubmit(event: React.FormEvent) {
        event.preventDefault();
        const options = { onSuccess: () => closeModal() };

        if (editingId) {
            form.put(`/backoffice/motifs-annulation/${editingId}`, options);
        } else {
            form.post('/backoffice/motifs-annulation', options);
        }
    }

    function confirmDelete() {
        if (!deleteTarget) {
            return;
        }

        setDeleting(true);
        setDeleteError(undefined);

        form.transform(() => ({}));
        form.delete(`/backoffice/motifs-annulation/${deleteTarget.id}`, {
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
        <div>
            <div className="d-flex align-items-center justify-content-between flex-wrap mb-3">
                <h5 className="mb-0">Raisons d&rsquo;annulation ou archivage</h5>
                {permissions.create && (
                    <button type="button" className="btn btn-primary d-flex align-items-center" onClick={openCreate}>
                        <i className="ti ti-square-rounded-plus me-2" />
                        Ajouter une raison
                    </button>
                )}
            </div>

            <RelatedRecordsTable
                isEmpty={motifsAnnulation.data.length === 0}
                emptyTitle="Aucune raison pour le moment"
                emptyIcon="ti ti-refresh"
                head={
                    <tr>
                        <th>Nom</th>
                        <th>Statut</th>
                        <th className="text-end">Action</th>
                    </tr>
                }
            >
                {motifsAnnulation.data.map((row) => (
                    <tr key={row.id}>
                        <td className="fw-medium">
                            {row.nom}
                            {row.isSystem && (
                                <span className="ms-2">
                                    <StatusBadge label="Système" variant="secondary" dot />
                                </span>
                            )}
                        </td>
                        <td>
                            <StatusBadge
                                label={row.statut === 'Actif' ? 'Actif' : 'Inactif'}
                                variant={row.statut === 'Actif' ? 'success' : 'secondary'}
                                dot
                            />
                        </td>
                        <td className="text-end">
                            {!row.isSystem && (
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
                            )}
                        </td>
                    </tr>
                ))}
            </RelatedRecordsTable>
            <Pagination paginator={motifsAnnulation} showJumpToPage />

            <Modal show={showModal} title={editingId ? 'Modifier la raison' : 'Ajouter une raison'} onClose={closeModal} processing={form.processing}>
                <form onSubmit={handleSubmit}>
                    <FormField
                        id="ma-nom"
                        label="Nom de la raison"
                        required
                        value={form.data.nom}
                        onChange={(event) => form.setData('nom', event.target.value)}
                        error={form.errors.nom}
                        placeholder="ex : Conflit d'horaires"
                    />
                    <SelectField
                        id="ma-statut"
                        label="Statut"
                        required
                        options={STATUT_OPTIONS}
                        value={form.data.statut}
                        onChange={(event) => form.setData('statut', event.target.value)}
                        error={form.errors.statut}
                    />
                    <div className="d-flex justify-content-end gap-2 mt-3">
                        <FormActions onCancel={closeModal} processing={form.processing} />
                    </div>
                </form>
            </Modal>

            <ConfirmDialog
                show={deleteTarget !== null}
                title="Supprimer cette raison ?"
                recordLabel={deleteTarget?.nom ?? ''}
                message="Cette action est définitive. La raison sera supprimée si elle n'est plus utilisée."
                error={deleteError}
                processing={deleting}
                onConfirm={confirmDelete}
                onCancel={() => setDeleteTarget(null)}
            />
        </div>
    );
}
