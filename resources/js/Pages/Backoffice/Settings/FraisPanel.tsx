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
import type { CrudPermissions, FraisCentreForm, FraisForm, FraisRow, PaginatedData, SelectOption } from '@/Types';

interface FraisPanelProps {
    frais: PaginatedData<FraisRow>;
    /** Centers the acting user may price — same restriction as the Salles tab. */
    centerOptions: SelectOption[];
    permissions: CrudPermissions;
}

const STATUT_OPTIONS: SelectOption[] = [
    { value: 'Actif', label: 'Actif' },
    { value: 'Inactif', label: 'Inactif' },
];

const EMPTY_FORM: FraisForm = { nom: '', montant_defaut: '0.00', statut: 'Actif', centres: [] };

/**
 * Frais (fee catalog) CRUD panel — replaces the Livewire FraisTab.
 *
 * Catalog-level only: name, status, the fallback amount, and the PRICE
 * EACH CENTER CHARGES — the same monthly fee is 1400 MAD in
 * Rabat/Casablanca, 1300 in Kénitra/Marrakech/Salé and 1200 à Agadir, so a
 * fee is attached to the centers that charge it with that center's own
 * amount (frais_etablissement), never duplicated once per branch.
 *
 * It still never writes group_frais or inscription_fees directly — a group
 * inherits its center's price when its own amount is left blank
 * (GroupController::normalizedFraisLignes) and can always override it.
 */
export default function FraisPanel({ frais, centerOptions, permissions }: FraisPanelProps) {
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
        // Only centers this user may act on are editable here; prices for
        // the others stay untouched server-side.
        const attachees = new Map(row.centres.map((c) => [c.etablissementId, c.montant]));

        form.setData({
            nom: row.nom,
            montant_defaut: row.montantDefaut,
            statut: row.statut,
            centres: centerOptions
                .filter((option) => attachees.has(Number(option.value)))
                .map((option) => ({
                    etablissement_id: Number(option.value),
                    montant: attachees.get(Number(option.value)) ?? row.montantDefaut,
                })),
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

    /**
     * Attaching a center pre-fills it with the fallback amount, so the
     * common case (every branch at the same price) is one click per center
     * and only the branches that differ get retyped.
     */
    function toggleCentre(etablissementId: number, attach: boolean) {
        const centres: FraisCentreForm[] = attach
            ? [...form.data.centres, { etablissement_id: etablissementId, montant: form.data.montant_defaut }]
            : form.data.centres.filter((c) => c.etablissement_id !== etablissementId);

        form.setData('centres', centres);
    }

    function setCentreMontant(etablissementId: number, montant: string) {
        form.setData(
            'centres',
            form.data.centres.map((c) => (c.etablissement_id === etablissementId ? { ...c, montant } : c)),
        );
    }

    function toggleTousLesCentres(attach: boolean) {
        form.setData(
            'centres',
            attach
                ? centerOptions.map((option) => {
                      const existant = form.data.centres.find((c) => c.etablissement_id === Number(option.value));

                      return existant ?? { etablissement_id: Number(option.value), montant: form.data.montant_defaut };
                  })
                : [],
        );
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
                        <th>Tarifs par centre</th>
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
                            {row.centres.length === 0 ? (
                                <span className="text-muted">—</span>
                            ) : (
                                <div className="d-flex flex-wrap gap-1">
                                    {row.centres.map((centre) => (
                                        <span className="badge badge-soft-info" key={centre.etablissementId}>
                                            {centre.nomCentre} : {centre.montant}
                                        </span>
                                    ))}
                                </div>
                            )}
                        </td>
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
                        Montant de repli, utilisé par les centres sans tarif propre ci-dessous. Il
                        reste modifiable groupe par groupe.
                    </p>

                    <div className="border rounded p-3 mb-3">
                        <div className="d-flex align-items-center justify-content-between mb-2">
                            <label className="form-label mb-0 fw-medium">Tarif par centre</label>
                            <div className="form-check mb-0">
                                <input
                                    id="f-centres-tous"
                                    className="form-check-input"
                                    type="checkbox"
                                    checked={centerOptions.length > 0 && form.data.centres.length === centerOptions.length}
                                    onChange={(event) => toggleTousLesCentres(event.target.checked)}
                                />
                                <label className="form-check-label" htmlFor="f-centres-tous">
                                    Tous les centres
                                </label>
                            </div>
                        </div>
                        <p className="form-text mt-0 mb-3">
                            Cochez les centres qui appliquent ce frais et saisissez le montant propre
                            à chacun (ex : 1400 à Rabat, 1200 à Agadir).
                        </p>

                        {centerOptions.length === 0 ? (
                            <p className="text-muted mb-0">Aucun centre disponible.</p>
                        ) : (
                            centerOptions.map((option) => {
                                const id = Number(option.value);
                                const ligne = form.data.centres.find((c) => c.etablissement_id === id);

                                return (
                                    <div className="row align-items-center g-2 mb-2" key={id}>
                                        <div className="col-7">
                                            <div className="form-check mb-0">
                                                <input
                                                    id={`f-centre-${id}`}
                                                    className="form-check-input"
                                                    type="checkbox"
                                                    checked={ligne !== undefined}
                                                    onChange={(event) => toggleCentre(id, event.target.checked)}
                                                />
                                                <label className="form-check-label" htmlFor={`f-centre-${id}`}>
                                                    {option.label}
                                                </label>
                                            </div>
                                        </div>
                                        <div className="col-5">
                                            <div className="input-group input-group-sm">
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    className="form-control"
                                                    aria-label={`Montant pour ${option.label}`}
                                                    value={ligne?.montant ?? ''}
                                                    disabled={ligne === undefined}
                                                    onChange={(event) => setCentreMontant(id, event.target.value)}
                                                />
                                                <span className="input-group-text">MAD</span>
                                            </div>
                                        </div>
                                    </div>
                                );
                            })
                        )}
                    </div>
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
