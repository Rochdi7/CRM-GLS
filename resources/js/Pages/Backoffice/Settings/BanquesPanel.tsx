import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import Modal from '@/Components/Modals/Modal';
import ConfirmDialog from '@/Components/Modals/ConfirmDialog';
import FormField from '@/Components/Forms/FormField';
import SelectField from '@/Components/Forms/SelectField';
import FormActions from '@/Components/Forms/FormActions';
import RelatedRecordsTable from '@/Components/Details/RelatedRecordsTable';
import Pagination from '@/Components/Tables/Pagination';
import RowActions, { RowActionItem } from '@/Components/Tables/RowActions';
import type { BanqueForm, BanqueRow, CrudPermissions, PaginatedData, SelectOption } from '@/Types';

interface BanquesPanelProps {
    banques: PaginatedData<BanqueRow>;
    permissions: CrudPermissions;
}

const STATUT_OPTIONS: SelectOption[] = [
    { value: 'Actif', label: 'Actif' },
    { value: 'Inactif', label: 'Inactif' },
];

const EMPTY_FORM: BanqueForm = { nom: '', statut: 'Actif' };

/**
 * Banques (bank catalog) CRUD panel — feeds the Chèque payment form's
 * Banque dropdown (Encaissements). Restricted to super-admin: `banks.*`
 * permissions are absent from every role in PermissionRegistry::matrix(),
 * so only super-admin's Gate::before bypass ever sees `permissions.create`
 * true and reaches this tab at all (SettingController::TAB_PERMISSIONS).
 */
export default function BanquesPanel({ banques, permissions }: BanquesPanelProps) {
    const [showModal, setShowModal] = useState(false);
    const [editingId, setEditingId] = useState<number | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<BanqueRow | null>(null);
    const [deleteError, setDeleteError] = useState<string>();
    const [deleting, setDeleting] = useState(false);

    const form = useForm<BanqueForm>(EMPTY_FORM);

    function openCreate() {
        form.reset();
        form.clearErrors();
        setEditingId(null);
        setShowModal(true);
    }

    function openEdit(row: BanqueRow) {
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
            form.put(`/backoffice/banques/${editingId}`, options);
        } else {
            form.post('/backoffice/banques', options);
        }
    }

    function confirmDelete() {
        if (!deleteTarget) {
            return;
        }

        setDeleting(true);
        setDeleteError(undefined);

        form.transform(() => ({}));
        form.delete(`/backoffice/banques/${deleteTarget.id}`, {
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
                <h5 className="mb-0">Catalogue des banques</h5>
                {permissions.create && (
                    <button type="button" className="btn btn-primary d-flex align-items-center" onClick={openCreate}>
                        <i className="ti ti-square-rounded-plus me-2" />
                        Ajouter une banque
                    </button>
                )}
            </div>

            <RelatedRecordsTable
                isEmpty={banques.data.length === 0}
                emptyTitle="Aucune banque pour le moment"
                emptyIcon="ti ti-building-bank"
                head={
                    <tr>
                        <th>Nom de la banque</th>
                        <th>Statut</th>
                        <th className="text-end">Action</th>
                    </tr>
                }
            >
                {banques.data.map((row) => (
                    <tr key={row.id}>
                        <td className="fw-medium">{row.nom}</td>
                        <td>
                            <span className={`badge badge-soft-${row.statut === 'Actif' ? 'success' : 'secondary'}`}>
                                {row.statut === 'Actif' ? 'Actif' : 'Inactif'}
                            </span>
                        </td>
                        <td className="text-end">
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
                        </td>
                    </tr>
                ))}
            </RelatedRecordsTable>
            <Pagination paginator={banques} showJumpToPage />

            <Modal show={showModal} title={editingId ? 'Modifier la banque' : 'Ajouter une banque'} onClose={closeModal} processing={form.processing}>
                <form onSubmit={handleSubmit}>
                    <FormField
                        id="b-nom"
                        label="Nom de la banque"
                        required
                        value={form.data.nom}
                        onChange={(event) => form.setData('nom', event.target.value)}
                        error={form.errors.nom}
                        placeholder="ex : Attijariwafa Bank"
                    />
                    <SelectField
                        id="b-statut"
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
                title="Supprimer cette banque ?"
                recordLabel={deleteTarget?.nom ?? ''}
                message="Cette action est définitive. La banque sera supprimée si elle n'est plus utilisée par des paiements."
                error={deleteError}
                processing={deleting}
                onConfirm={confirmDelete}
                onCancel={() => setDeleteTarget(null)}
            />
        </div>
    );
}
