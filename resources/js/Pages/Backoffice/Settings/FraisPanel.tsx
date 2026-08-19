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
import type { CrudPermissions, FraisForm, FraisRow, PaginatedData, SelectOption } from '@/Types';

interface FraisPanelProps {
    frais: PaginatedData<FraisRow>;
    permissions: CrudPermissions;
}

const STATUT_OPTIONS: SelectOption[] = [
    { value: 'Actif', label: 'Actif' },
    { value: 'Inactif', label: 'Inactif' },
];

const EMPTY_FORM: FraisForm = { nom: '', montant_defaut: '0.00', statut: 'Actif' };

/**
 * Frais (fee catalog) CRUD panel — replaces the Livewire FraisTab.
 *
 * Catalog-level only: name, status, and the DEFAULT amount every group
 * starts from. It still never writes group_frais or inscription_fees
 * directly — a group inherits montant_defaut when its own amount is left
 * blank (GroupController::normalizedFraisLignes) and can always override
 * it.
 */
export default function FraisPanel({ frais, permissions }: FraisPanelProps) {
    const [showModal, setShowModal] = useState(false);
    const [editingId, setEditingId] = useState<number | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<FraisRow | null>(null);
    const [deleteError, setDeleteError] = useState<string>();
    const [deleting, setDeleting] = useState(false);

    const form = useForm<FraisForm>(EMPTY_FORM);

    function openCreate() {
        form.reset();
        form.clearErrors();
        setEditingId(null);
        setShowModal(true);
    }

    function openEdit(row: FraisRow) {
        form.setData({ nom: row.nom, montant_defaut: row.montantDefaut, statut: row.statut });
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
            form.put(`/backoffice/frais/${editingId}`, options);
        } else {
            form.post('/backoffice/frais', options);
        }
    }

    function confirmDelete() {
        if (!deleteTarget) {
            return;
        }

        setDeleting(true);
        setDeleteError(undefined);

        form.transform(() => ({}));
        form.delete(`/backoffice/frais/${deleteTarget.id}`, {
            preserveScroll: true,
            // Reset the transform so a later create/update on this shared
            // form doesn't submit an empty payload (Phase 12 UX fix).
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
                <h5 className="mb-0">Catalogue des frais</h5>
                {permissions.create && (
                    <button type="button" className="btn btn-primary d-flex align-items-center" onClick={openCreate}>
                        <i className="ti ti-square-rounded-plus me-2" />
                        Ajouter un frais
                    </button>
                )}
            </div>

            <RelatedRecordsTable
                isEmpty={frais.data.length === 0}
                emptyTitle="Aucun frais pour le moment"
                emptyIcon="ti ti-receipt"
                head={
                    <tr>
                        <th>Nom du frais</th>
                        <th>Montant par défaut</th>
                        <th>Groupes</th>
                        <th>Statut</th>
                        <th className="text-end">Action</th>
                    </tr>
                }
            >
                {frais.data.map((row) => (
                    <tr key={row.id}>
                        <td className="fw-medium">{row.nom}</td>
                        <td>{row.montantDefaut} MAD</td>
                        <td>
                            <span className="badge badge-soft-secondary">{row.groupsCount}</span>
                        </td>
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
            <Pagination paginator={frais} showJumpToPage />

            <Modal show={showModal} title={editingId ? 'Modifier le frais' : 'Ajouter un frais'} onClose={closeModal} processing={form.processing}>
                <form onSubmit={handleSubmit}>
                    <FormField
                        id="f-nom"
                        label="Nom du frais"
                        required
                        value={form.data.nom}
                        onChange={(event) => form.setData('nom', event.target.value)}
                        error={form.errors.nom}
                        placeholder="ex : Frais de Juillet"
                    />
                    <FormField
                        id="f-montant-defaut"
                        label="Montant par défaut (MAD)"
                        required
                        type="number"
                        step="0.01"
                        min="0"
                        value={form.data.montant_defaut}
                        onChange={(event) => form.setData('montant_defaut', event.target.value)}
                        error={form.errors.montant_defaut}
                        placeholder="ex : 1300.00"
                    />
                    <p className="form-text mb-3">
                        Montant appliqué automatiquement à ce frais dans chaque groupe. Il reste
                        modifiable groupe par groupe.
                    </p>
                    <SelectField
                        id="f-statut"
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
                title="Supprimer ce frais ?"
                recordLabel={deleteTarget?.nom ?? ''}
                message="Cette action est définitive. Le frais sera supprimé s'il n'est plus assigné à des groupes."
                error={deleteError}
                processing={deleting}
                onConfirm={confirmDelete}
                onCancel={() => setDeleteTarget(null)}
            />
        </div>
    );
}
