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
import type { CrudPermissions, PaginatedData, SalleForm, SalleRow, SelectOption } from '@/Types';
import { t } from '@/Lib/i18n';

interface SallesPanelProps {
    salles: PaginatedData<SalleRow>;
    centerOptions: SelectOption[];
    permissions: CrudPermissions;
    /** False only on "Tous les centres" — gates the redundant Centre column. */
    centerLocked: boolean;
}

const STATUT_OPTIONS: SelectOption[] = [
    { value: 'Active', label: t('Active') },
    { value: 'Inactive', label: t('Inactive') },
];

const EMPTY_FORM: SalleForm = {
    nom: '',
    etablissement_id: '',
    capacite: '',
    statut: 'Active',
};

/**
 * Salles (rooms) CRUD panel — replaces the Livewire SallesTab. The center
 * picker only lists centers the acting user can access (Phase 6 §Q3 fix) —
 * never a raw, unrestricted center list.
 */
export default function SallesPanel({ salles, centerOptions, permissions, centerLocked }: SallesPanelProps) {
    const [showModal, setShowModal] = useState(false);
    const [editingId, setEditingId] = useState<number | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<SalleRow | null>(null);
    const [deleteError, setDeleteError] = useState<string>();
    const [deleting, setDeleting] = useState(false);

    const form = useForm<SalleForm>(EMPTY_FORM);

    function openCreate() {
        form.reset();
        form.clearErrors();
        form.setData({ ...EMPTY_FORM, etablissement_id: (centerOptions[0]?.value as number) ?? '' });
        setEditingId(null);
        setShowModal(true);
    }

    function openEdit(row: SalleRow) {
        const center = centerOptions.find((option) => option.label === row.centre);
        form.setData({
            nom: row.nom,
            etablissement_id: (center?.value as number) ?? '',
            capacite: row.capacite ?? '',
            statut: row.statut,
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

    function handleSubmit(event: React.FormEvent) {
        event.preventDefault();
        const options = { onSuccess: () => closeModal() };

        if (editingId) {
            form.put(`/backoffice/salles/${editingId}`, options);
        } else {
            form.post('/backoffice/salles', options);
        }
    }

    function confirmDelete() {
        if (!deleteTarget) {
            return;
        }

        setDeleting(true);
        setDeleteError(undefined);

        form.transform(() => ({}));
        form.delete(`/backoffice/salles/${deleteTarget.id}`, {
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

    const noCenters = centerOptions.length === 0;

    return (
        <div>
            <div className="d-flex align-items-center justify-content-between flex-wrap mb-3">
                <h5 className="mb-0">Salles</h5>
                {permissions.create && (
                    <button type="button" className="btn btn-primary d-flex align-items-center" onClick={openCreate}>
                        <i className="ti ti-square-rounded-plus me-2" />
                        Ajouter une salle
                    </button>
                )}
            </div>

            <RelatedRecordsTable
                isEmpty={salles.data.length === 0}
                emptyTitle="Aucune salle pour le moment"
                emptyIcon="ti ti-door"
                head={
                    <tr>
                        <th>Nom de la salle</th>
                        {!centerLocked && <th>Centre</th>}
                        <th>Capacité</th>
                        <th>Statut</th>
                        <th className="text-end">Action</th>
                    </tr>
                }
            >
                {salles.data.map((row) => (
                    <tr key={row.id}>
                        <td className="fw-medium">{row.nom}</td>
                        {!centerLocked && <td>{row.centre ?? '—'}</td>}
                        <td>{row.capacite ?? '—'}</td>
                        <td>
                            <span className={`badge badge-soft-${row.statut === 'Active' ? 'success' : 'secondary'}`}>
                                {row.statut === 'Active' ? t('Active') : t('Inactive')}
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
            <Pagination paginator={salles} />

            <Modal show={showModal} title={editingId ? 'Modifier la salle' : 'Ajouter une salle'} onClose={closeModal} processing={form.processing} size="lg">
                {noCenters ? (
                    <>
                        <div className="alert alert-warning mb-0">Créez d'abord un centre avant d'ajouter des salles.</div>
                        <div className="d-flex justify-content-end mt-3">
                            <button type="button" className="btn btn-light" onClick={closeModal}>
                                Fermer
                            </button>
                        </div>
                    </>
                ) : (
                    <form onSubmit={handleSubmit}>
                        <div className="row">
                            <div className="col-md-6">
                                <FormField
                                    id="s-nom"
                                    label="Nom de la salle"
                                    required
                                    value={form.data.nom}
                                    onChange={(event) => form.setData('nom', event.target.value)}
                                    error={form.errors.nom}
                                    placeholder="ex : Salle 1"
                                />
                            </div>
                            <div className="col-md-6">
                                <SelectField
                                    id="s-etab"
                                    label="Centre"
                                    required
                                    options={centerOptions}
                                    placeholder="Choisir…"
                                    value={form.data.etablissement_id}
                                    onChange={(event) => form.setData('etablissement_id', Number(event.target.value))}
                                    error={form.errors.etablissement_id}
                                />
                            </div>
                            <div className="col-md-6">
                                <FormField
                                    id="s-cap"
                                    label="Capacité"
                                    type="number"
                                    min={1}
                                    value={form.data.capacite}
                                    onChange={(event) =>
                                        form.setData('capacite', event.target.value === '' ? '' : Number(event.target.value))
                                    }
                                    error={form.errors.capacite}
                                    placeholder="ex : 20"
                                />
                            </div>
                            <div className="col-md-6">
                                <SelectField
                                    id="s-statut"
                                    label="Statut"
                                    required
                                    options={STATUT_OPTIONS}
                                    value={form.data.statut}
                                    onChange={(event) => form.setData('statut', event.target.value)}
                                    error={form.errors.statut}
                                />
                            </div>
                        </div>
                        <div className="d-flex justify-content-end gap-2 mt-3">
                            <FormActions onCancel={closeModal} processing={form.processing} />
                        </div>
                    </form>
                )}
            </Modal>

            <ConfirmDialog
                show={deleteTarget !== null}
                title="Supprimer cette salle ?"
                recordLabel={deleteTarget?.nom ?? ''}
                message="Cette action est définitive. La salle sera supprimée si elle n'est plus assignée à des groupes."
                error={deleteError}
                processing={deleting}
                onConfirm={confirmDelete}
                onCancel={() => setDeleteTarget(null)}
            />
        </div>
    );
}
