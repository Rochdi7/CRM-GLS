import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import Modal from '@/Components/Modals/Modal';
import ConfirmDialog from '@/Components/Modals/ConfirmDialog';
import FormField from '@/Components/Forms/FormField';
import PhoneField from '@/Components/Forms/PhoneField';
import CheckboxField from '@/Components/Forms/CheckboxField';
import FormActions from '@/Components/Forms/FormActions';
import RelatedRecordsTable from '@/Components/Details/RelatedRecordsTable';
import Pagination from '@/Components/Tables/Pagination';
import RowActions, { RowActionItem } from '@/Components/Tables/RowActions';
import { DEFAULT_COUNTRY, joinPhone, splitPhone } from '@/Data/countries';
import type { CrudPermissions, EtablissementRow, PaginatedData } from '@/Types';

interface EtablissementsPanelProps {
    etablissements: PaginatedData<EtablissementRow>;
    permissions: CrudPermissions;
}

interface FormShape {
    nom_centre: string;
    ville: string;
    adresse: string;
    ice: string;
    countryIso: string;
    national: string;
    email: string;
    siege_social: boolean;
}

const EMPTY_FORM: FormShape = {
    nom_centre: '',
    ville: '',
    adresse: '',
    ice: '',
    countryIso: DEFAULT_COUNTRY,
    national: '',
    email: '',
    siege_social: false,
};

/**
 * Établissements (centers) CRUD panel — replaces the Livewire
 * EtablissementsTab. Same fields/validation/delete-guard as
 * app/Livewire/Backoffice/Settings/EtablissementsTab.php.
 */
export default function EtablissementsPanel({ etablissements, permissions }: EtablissementsPanelProps) {
    const [showModal, setShowModal] = useState(false);
    const [editingId, setEditingId] = useState<number | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<EtablissementRow | null>(null);
    const [deleteError, setDeleteError] = useState<string>();
    const [deleting, setDeleting] = useState(false);

    const form = useForm<FormShape>(EMPTY_FORM);

    function openCreate() {
        form.reset();
        form.clearErrors();
        setEditingId(null);
        setShowModal(true);
    }

    function openEdit(row: EtablissementRow) {
        const [countryIso, national] = splitPhone(row.telephone);
        form.setData({
            nom_centre: row.nomCentre,
            ville: row.ville,
            adresse: row.adresse ?? '',
            ice: row.ice ?? '',
            countryIso,
            national,
            email: row.email ?? '',
            siege_social: row.siegeSocial,
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

        const payload = {
            nom_centre: form.data.nom_centre,
            ville: form.data.ville,
            adresse: form.data.adresse,
            ice: form.data.ice,
            telephone: joinPhone(form.data.countryIso, form.data.national) ?? '',
            email: form.data.email,
            siege_social: form.data.siege_social,
        };

        const options = {
            onSuccess: () => closeModal(),
        };

        form.transform(() => payload);

        if (editingId) {
            form.put(`/backoffice/etablissements/${editingId}`, options);
        } else {
            form.post('/backoffice/etablissements', options);
        }
    }

    function confirmDelete() {
        if (!deleteTarget) {
            return;
        }

        setDeleting(true);
        setDeleteError(undefined);

        form.transform(() => ({}));
        form.delete(`/backoffice/etablissements/${deleteTarget.id}`, {
            preserveScroll: true,
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
                <h5 className="mb-0">Centres</h5>
                {permissions.create && (
                    <button type="button" className="btn btn-primary d-flex align-items-center" onClick={openCreate}>
                        <i className="ti ti-square-rounded-plus me-2" />
                        Ajouter un centre
                    </button>
                )}
            </div>

            <RelatedRecordsTable
                isEmpty={etablissements.data.length === 0}
                emptyTitle="Aucun centre pour le moment"
                emptyIcon="ti ti-building"
                head={
                    <tr>
                        <th>Nom du centre</th>
                        <th>Ville</th>
                        <th>Téléphone</th>
                        <th>Salles</th>
                        <th>Siège social</th>
                        <th className="text-end">Action</th>
                    </tr>
                }
            >
                {etablissements.data.map((row) => (
                    <tr key={row.id}>
                        <td className="fw-medium">{row.nomCentre}</td>
                        <td>{row.ville}</td>
                        <td>{row.telephone ?? '—'}</td>
                        <td>
                            <span className="badge badge-soft-secondary">{row.sallesCount}</span>
                        </td>
                        <td>
                            {row.siegeSocial ? (
                                <span className="badge badge-soft-success">Oui</span>
                            ) : (
                                <span className="text-muted">—</span>
                            )}
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
            <Pagination paginator={etablissements} showJumpToPage />

            <Modal show={showModal} title={editingId ? 'Modifier le centre' : 'Ajouter un centre'} onClose={closeModal} processing={form.processing} size="lg">
                <form onSubmit={handleSubmit}>
                    <div className="row">
                        <div className="col-md-6">
                            <FormField
                                id="e-nom"
                                label="Nom du centre"
                                required
                                value={form.data.nom_centre}
                                onChange={(event) => form.setData('nom_centre', event.target.value)}
                                error={form.errors.nom_centre}
                                placeholder="ex : GLS Rabat"
                            />
                        </div>
                        <div className="col-md-6">
                            <FormField
                                id="e-ville"
                                label="Ville"
                                required
                                value={form.data.ville}
                                onChange={(event) => form.setData('ville', event.target.value)}
                                error={form.errors.ville}
                                placeholder="ex : Rabat"
                            />
                        </div>
                        <div className="col-12">
                            <FormField
                                id="e-adresse"
                                label="Adresse"
                                value={form.data.adresse}
                                onChange={(event) => form.setData('adresse', event.target.value)}
                                error={(form.errors as Record<string, string>).adresse}
                                placeholder="ex : Laghrabliya rue Halima Saadia N 12, 2ème étage, 11060 Salé"
                            />
                        </div>
                        <div className="col-12">
                            <PhoneField
                                id="e-tel"
                                label="Téléphone"
                                countryIso={form.data.countryIso}
                                national={form.data.national}
                                onCountryChange={(iso) => form.setData('countryIso', iso)}
                                onNationalChange={(value) => form.setData('national', value)}
                                error={(form.errors as Record<string, string>).telephone}
                            />
                        </div>
                        <div className="col-md-6">
                            <FormField
                                id="e-ice"
                                label="ICE"
                                value={form.data.ice}
                                onChange={(event) => form.setData('ice', event.target.value)}
                                error={(form.errors as Record<string, string>).ice}
                                placeholder="ex : 001234567000089"
                            />
                        </div>
                        <div className="col-md-6">
                            <FormField
                                id="e-email"
                                label="Email"
                                type="email"
                                value={form.data.email}
                                onChange={(event) => form.setData('email', event.target.value)}
                                error={form.errors.email}
                                placeholder="ex : contact@gls.ma"
                            />
                        </div>
                        <div className="col-12">
                            <CheckboxField
                                id="e-siege"
                                label="Siège social"
                                checked={form.data.siege_social}
                                onChange={(event) => form.setData('siege_social', event.target.checked)}
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
                title="Supprimer ce centre ?"
                recordLabel={deleteTarget?.nomCentre ?? ''}
                message="Cette action est définitive. Le centre sera supprimé s'il n'est plus utilisé."
                error={deleteError}
                processing={deleting}
                onConfirm={confirmDelete}
                onCancel={() => setDeleteTarget(null)}
            />
        </div>
    );
}
