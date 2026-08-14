import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import Modal from '@/Components/Modals/Modal';
import ConfirmDialog from '@/Components/Modals/ConfirmDialog';
import DateField from '@/Components/Forms/DateField';
import FormField from '@/Components/Forms/FormField';
import CheckboxField from '@/Components/Forms/CheckboxField';
import FormActions from '@/Components/Forms/FormActions';
import RelatedRecordsTable from '@/Components/Details/RelatedRecordsTable';
import Pagination from '@/Components/Tables/Pagination';
import RowActions, { RowActionItem } from '@/Components/Tables/RowActions';
import type { AnneeScolaireForm, AnneeScolaireRow, CrudPermissions, PaginatedData } from '@/Types';

interface AnneesScolairesPanelProps {
    anneesScolaires: PaginatedData<AnneeScolaireRow>;
    permissions: CrudPermissions;
}

const EMPTY_FORM: AnneeScolaireForm = {
    nom: '',
    date_debut: '',
    date_fin: '',
    par_defaut: false,
    inscription_ouverte: true,
};

/**
 * Années scolaires (academic years) CRUD panel — replaces the Livewire
 * AnneesScolairesTab. Setting par_defaut is enforced server-side
 * (AnneeScolaireController::persist, single DB::transaction) — never
 * recomputed client-side.
 */
export default function AnneesScolairesPanel({ anneesScolaires, permissions }: AnneesScolairesPanelProps) {
    const [showModal, setShowModal] = useState(false);
    const [editingId, setEditingId] = useState<number | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<AnneeScolaireRow | null>(null);
    const [deleteError, setDeleteError] = useState<string>();
    const [deleting, setDeleting] = useState(false);

    const form = useForm<AnneeScolaireForm>(EMPTY_FORM);

    function toInputDate(display: string): string {
        const [d, m, y] = display.split('/');

        return d && m && y ? `${y}-${m}-${d}` : display;
    }

    function openCreate() {
        form.reset();
        form.clearErrors();
        setEditingId(null);
        setShowModal(true);
    }

    function openEdit(row: AnneeScolaireRow) {
        form.setData({
            nom: row.nom,
            date_debut: toInputDate(row.dateDebut),
            date_fin: toInputDate(row.dateFin),
            par_defaut: row.parDefaut,
            inscription_ouverte: row.inscriptionOuverte,
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
            form.put(`/backoffice/annees-scolaires/${editingId}`, options);
        } else {
            form.post('/backoffice/annees-scolaires', options);
        }
    }

    function confirmDelete() {
        if (!deleteTarget) {
            return;
        }

        setDeleting(true);
        setDeleteError(undefined);

        form.transform(() => ({}));
        form.delete(`/backoffice/annees-scolaires/${deleteTarget.id}`, {
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
                <h5 className="mb-0">Années scolaires</h5>
                {permissions.create && (
                    <button type="button" className="btn btn-primary d-flex align-items-center" onClick={openCreate}>
                        <i className="fa fa-square-plus me-2" />
                        Ajouter une année scolaire
                    </button>
                )}
            </div>

            <RelatedRecordsTable
                isEmpty={anneesScolaires.data.length === 0}
                emptyTitle="Aucune année scolaire pour le moment"
                emptyIcon="fa fa-calendar"
                head={
                    <tr>
                        <th>Nom</th>
                        <th>Date de début</th>
                        <th>Date de fin</th>
                        <th>Année par défaut</th>
                        <th>Inscriptions</th>
                        <th className="text-end">Action</th>
                    </tr>
                }
            >
                {anneesScolaires.data.map((row) => (
                    <tr key={row.id}>
                        <td className="fw-medium">{row.nom}</td>
                        <td>{row.dateDebut}</td>
                        <td>{row.dateFin}</td>
                        <td>
                            {row.parDefaut ? (
                                <span className="badge badge-soft-success">Par défaut</span>
                            ) : (
                                <span className="text-muted">—</span>
                            )}
                        </td>
                        <td>
                            <span className={`badge badge-soft-${row.inscriptionOuverte ? 'info' : 'secondary'}`}>
                                {row.inscriptionOuverte ? 'Ouvertes' : 'Fermées'}
                            </span>
                        </td>
                        <td className="text-end">
                            <RowActions>
                                {permissions.update && (
                                    <RowActionItem icon="fa-pen" onClick={() => openEdit(row)}>
                                        Modifier
                                    </RowActionItem>
                                )}
                                {permissions.delete && (
                                    <RowActionItem
                                        icon="fa-trash"
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
            <Pagination paginator={anneesScolaires} showJumpToPage />

            <Modal
                show={showModal}
                title={editingId ? "Modifier l'année scolaire" : 'Ajouter une année scolaire'}
                onClose={closeModal}
                processing={form.processing}
                size="lg"
            >
                <form onSubmit={handleSubmit}>
                    <div className="row">
                        <div className="col-md-4">
                            <FormField
                                id="a-nom"
                                label="Nom"
                                required
                                value={form.data.nom}
                                onChange={(event) => form.setData('nom', event.target.value)}
                                error={form.errors.nom}
                                placeholder="2025/2026"
                            />
                        </div>
                        <div className="col-md-4">
                            <DateField
                                id="a-debut"
                                label="Date de début"
                                required
                                value={form.data.date_debut}
                                onChange={(event) => form.setData('date_debut', event.target.value)}
                                error={form.errors.date_debut}
                            />
                        </div>
                        <div className="col-md-4">
                            <DateField
                                id="a-fin"
                                label="Date de fin"
                                required
                                value={form.data.date_fin}
                                onChange={(event) => form.setData('date_fin', event.target.value)}
                                error={form.errors.date_fin}
                            />
                        </div>
                        <div className="col-md-6">
                            <CheckboxField
                                id="a-defaut"
                                label="Année par défaut"
                                checked={form.data.par_defaut}
                                onChange={(event) => form.setData('par_defaut', event.target.checked)}
                            />
                        </div>
                        <div className="col-md-6">
                            <CheckboxField
                                id="a-ouverte"
                                label="Inscriptions ouvertes"
                                checked={form.data.inscription_ouverte}
                                onChange={(event) => form.setData('inscription_ouverte', event.target.checked)}
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
                title="Supprimer cette année scolaire ?"
                recordLabel={deleteTarget?.nom ?? ''}
                message="Cette action est définitive. L'année scolaire sera supprimée si elle n'est plus utilisée."
                error={deleteError}
                processing={deleting}
                onConfirm={confirmDelete}
                onCancel={() => setDeleteTarget(null)}
            />
        </div>
    );
}
