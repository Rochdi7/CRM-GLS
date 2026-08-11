import { router, useForm } from '@inertiajs/react';
import { useMemo, useState, type FormEvent } from 'react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import TableToolbar from '@/Components/Tables/TableToolbar';
import Modal from '@/Components/Modals/Modal';
import ConfirmDialog from '@/Components/Modals/ConfirmDialog';
import SelectField from '@/Components/Forms/SelectField';
import MultiSelectField from '@/Components/Forms/MultiSelectField';
import FormField from '@/Components/Forms/FormField';
import FormActions from '@/Components/Forms/FormActions';
import type { CreneauCreateForm, CreneauForm, CreneauRow, SelectOption } from '@/Types';

interface EmploiDuTempsIndexProps {
    creneaux: CreneauRow[];
    filters: {
        groupFilter: string;
        enseignantFilter: string;
        salleFilter: string;
        jourFilter: string;
    };
    jours: Record<string, string>;
    groupOptions: Array<SelectOption & { enseignantId: number | null; salleId: number | null }>;
    enseignantOptions: SelectOption[];
    salleOptions: SelectOption[];
    permissions: { create: boolean; update: boolean; delete: boolean };
}

/** Default hour rows the weekly grid always shows, even with no créneaux yet — actual créneau start times are added on top of these (see heuresGrille). */
const HEURES_GRILLE_PAR_DEFAUT = Array.from({ length: 14 }, (_, i) => `${String(8 + i).padStart(2, '0')}:00`);

const EMPTY_EDIT_FORM: CreneauForm = {
    group_id: '',
    jour_semaine: '',
    heure_debut: '',
    heure_fin: '',
    enseignant_id: '',
    salle_id: '',
};

const EMPTY_CREATE_FORM: CreneauCreateForm = {
    group_id: '',
    jours_semaine: [],
    heure_debut: '',
    heure_fin: '',
    enseignant_id: '',
    salle_id: '',
};

/**
 * Emploi du temps — the weekly recurring schedule grid ("créneaux"),
 * distinct from Présences/Séances (dated occurrences used for attendance,
 * SeancesIndex). Saving a créneau here generates/syncs its future séances
 * server-side (CreneauController + GenererSeancesDepuisCreneau) — this page
 * only manages the weekly template, never the roll call.
 */
export default function EmploiDuTempsIndex({
    creneaux,
    filters,
    jours,
    groupOptions,
    enseignantOptions,
    salleOptions,
    permissions,
}: EmploiDuTempsIndexProps) {
    const [showModal, setShowModal] = useState(false);
    const [editingId, setEditingId] = useState<number | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<CreneauRow | null>(null);
    const [deleteError, setDeleteError] = useState<string>();
    const [deleting, setDeleting] = useState(false);

    const createForm = useForm<CreneauCreateForm>(EMPTY_CREATE_FORM);
    const editForm = useForm<CreneauForm>(EMPTY_EDIT_FORM);
    const isEditing = editingId !== null;
    const form = isEditing ? editForm : createForm;

    const jourOptions: SelectOption[] = Object.entries(jours).map(([value, label]) => ({ value: Number(value), label }));

    // Bucket by day-of-week for the grid columns; each cell lists every
    // créneau whose [heure_debut, heure_fin) overlaps that row's hour.
    const parRang = useMemo(() => {
        const map = new Map<string, CreneauRow[]>();
        creneaux.forEach((c) => {
            const key = `${c.jourSemaine}`;
            map.set(key, [...(map.get(key) ?? []), c]);
        });
        return map;
    }, [creneaux]);

    const heuresGrille = useMemo(() => {
        const heures = new Set<string>();
        creneaux.forEach((c) => heures.add(c.heureDebut));
        HEURES_GRILLE_PAR_DEFAUT.forEach((h) => heures.add(h));
        return Array.from(heures).sort();
    }, [creneaux]);

    function reload(nextFilters: Partial<typeof filters>) {
        router.get(
            '/backoffice/emploi-du-temps',
            { ...filters, ...nextFilters },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function openCreate() {
        setEditingId(null);
        createForm.reset();
        createForm.clearErrors();
        createForm.setData(EMPTY_CREATE_FORM);
        setShowModal(true);
    }

    function openEdit(row: CreneauRow) {
        setEditingId(row.id);
        editForm.clearErrors();
        editForm.setData({
            group_id: String(row.groupId),
            jour_semaine: String(row.jourSemaine),
            heure_debut: row.heureDebut,
            heure_fin: row.heureFin,
            enseignant_id: row.enseignantId ? String(row.enseignantId) : '',
            salle_id: row.salleId ? String(row.salleId) : '',
        });
        setShowModal(true);
    }

    function closeModal() {
        setShowModal(false);
        setEditingId(null);
        createForm.reset();
        createForm.clearErrors();
        editForm.reset();
        editForm.clearErrors();
    }

    /** Sets a field shared by both form shapes (everything but jour(s)_semaine). */
    function setField(field: 'heure_debut' | 'heure_fin' | 'enseignant_id' | 'salle_id', value: string) {
        if (isEditing) {
            editForm.setData(field, value);
        } else {
            createForm.setData(field, value);
        }
    }

    function handleGroupChange(groupId: string) {
        const group = groupOptions.find((option) => String(option.value) === groupId);
        const applyDefaults = <T extends { group_id: string; enseignant_id: string; salle_id: string }>(data: T): T => ({
            ...data,
            group_id: groupId,
            enseignant_id: group?.enseignantId ? String(group.enseignantId) : data.enseignant_id,
            salle_id: group?.salleId ? String(group.salleId) : data.salle_id,
        });

        if (isEditing) {
            editForm.setData(applyDefaults);
        } else {
            createForm.setData(applyDefaults);
        }
    }

    function submit(event: FormEvent) {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => closeModal() };

        if (editingId) {
            editForm.put(`/backoffice/creneaux/${editingId}`, options);
        } else {
            createForm.post('/backoffice/creneaux', options);
        }
    }

    function confirmDelete() {
        if (!deleteTarget) return;

        setDeleting(true);
        setDeleteError(undefined);
        router.delete(`/backoffice/creneaux/${deleteTarget.id}`, {
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
        <BackofficeLayout
            title="Emploi du temps"
            breadcrumbs={[
                { label: 'Tableau de bord', href: '/backoffice/dashboard' },
                { label: 'Emploi du temps' },
            ]}
            actions={
                permissions.create && (
                    <button type="button" className="btn btn-primary d-flex align-items-center" onClick={openCreate}>
                        <i className="ti ti-square-rounded-plus me-2" />
                        Ajouter
                    </button>
                )
            }
        >
            <Card title="Emploi du temps" bodyClassName="p-0 py-3">
                <div className="px-3 pt-2">
                    <TableToolbar>
                        <div style={{ width: 220 }}>
                            <label className="form-label" htmlFor="edt-f-groupe">
                                Groupe
                            </label>
                            <SelectField
                                id="edt-f-groupe"
                                options={groupOptions}
                                placeholder="Tous les groupes"
                                value={filters.groupFilter}
                                onChange={(event) => reload({ groupFilter: event.target.value })}
                            />
                        </div>
                        <div style={{ width: 220 }}>
                            <label className="form-label" htmlFor="edt-f-enseignant">
                                Enseignant
                            </label>
                            <SelectField
                                id="edt-f-enseignant"
                                options={enseignantOptions}
                                placeholder="Tous les enseignants"
                                value={filters.enseignantFilter}
                                onChange={(event) => reload({ enseignantFilter: event.target.value })}
                            />
                        </div>
                        <div style={{ width: 200 }}>
                            <label className="form-label" htmlFor="edt-f-salle">
                                Salle
                            </label>
                            <SelectField
                                id="edt-f-salle"
                                options={salleOptions}
                                placeholder="Toutes les salles"
                                value={filters.salleFilter}
                                onChange={(event) => reload({ salleFilter: event.target.value })}
                            />
                        </div>
                        <div style={{ width: 180 }}>
                            <label className="form-label" htmlFor="edt-f-jour">
                                Jour
                            </label>
                            <SelectField
                                id="edt-f-jour"
                                options={jourOptions}
                                placeholder="Tous les jours"
                                value={filters.jourFilter}
                                onChange={(event) => reload({ jourFilter: event.target.value })}
                            />
                        </div>
                    </TableToolbar>
                </div>

                <div className="table-responsive px-3">
                    <table className="table table-bordered align-middle mb-0">
                        <thead className="table-light">
                            <tr>
                                <th style={{ width: 90 }} />
                                {jourOptions.map((jour) => (
                                    <th key={jour.value} className="text-uppercase text-center">
                                        {jour.label}
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {heuresGrille.map((heure) => {
                                const heureFin = `${String(Number(heure.slice(0, 2)) + 1).padStart(2, '0')}:00`;

                                return (
                                    <tr key={heure}>
                                        <td className="fw-medium text-nowrap">
                                            {heure.slice(0, 5)} - {heureFin}
                                        </td>
                                        {jourOptions.map((jour) => {
                                            const rows = (parRang.get(String(jour.value)) ?? []).filter(
                                                (c) => c.heureDebut === heure,
                                            );

                                            return (
                                                <td key={jour.value} style={{ minWidth: 160, verticalAlign: 'top' }}>
                                                    {rows.map((row) => (
                                                        <div
                                                            key={row.id}
                                                            className="bg-primary-transparent rounded p-2 mb-1"
                                                            style={{ cursor: permissions.update ? 'pointer' : 'default' }}
                                                            onClick={() => permissions.update && openEdit(row)}
                                                        >
                                                            <div className="fw-medium fs-13">
                                                                {row.groupNom}
                                                                {row.groupNiveau && (
                                                                    <span className="badge badge-soft-secondary ms-1">
                                                                        {row.groupNiveau}
                                                                    </span>
                                                                )}
                                                            </div>
                                                            <div className="text-muted fs-12">
                                                                {row.heureDebut} – {row.heureFin}
                                                            </div>
                                                            {row.enseignant && (
                                                                <div className="text-muted fs-12">{row.enseignant}</div>
                                                            )}
                                                            {row.salle && <div className="text-muted fs-12">{row.salle}</div>}
                                                            {permissions.delete && (
                                                                <button
                                                                    type="button"
                                                                    className="btn btn-link btn-sm text-danger p-0 mt-1"
                                                                    onClick={(event) => {
                                                                        event.stopPropagation();
                                                                        setDeleteTarget(row);
                                                                        setDeleteError(undefined);
                                                                    }}
                                                                >
                                                                    Supprimer
                                                                </button>
                                                            )}
                                                        </div>
                                                    ))}
                                                </td>
                                            );
                                        })}
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>
            </Card>

            <Modal
                show={showModal}
                title={editingId ? 'Modifier le créneau' : 'Ajouter un créneau'}
                onClose={closeModal}
                processing={form.processing}
                size="lg"
            >
                <form onSubmit={submit}>
                    <div className="row">
                        <div className="col-12">
                            <SelectField
                                id="crn-groupe"
                                label="Groupe"
                                required
                                disabled={editingId !== null}
                                options={groupOptions}
                                placeholder="Choisir une formation"
                                value={form.data.group_id}
                                onChange={(event) => handleGroupChange(event.target.value)}
                                error={form.errors.group_id}
                            />
                        </div>
                        <div className="col-md-6">
                            {isEditing ? (
                                <SelectField
                                    id="crn-jour"
                                    label="Jour"
                                    required
                                    options={jourOptions}
                                    placeholder="Choisir un jour"
                                    value={editForm.data.jour_semaine}
                                    onChange={(event) => editForm.setData('jour_semaine', event.target.value)}
                                    error={editForm.errors.jour_semaine}
                                />
                            ) : (
                                <MultiSelectField
                                    id="crn-jours"
                                    label="Jour"
                                    required
                                    options={jourOptions}
                                    placeholder="Choisir un ou plusieurs jours"
                                    values={createForm.data.jours_semaine}
                                    onChange={(values) => createForm.setData('jours_semaine', values)}
                                    error={createForm.errors.jours_semaine}
                                />
                            )}
                        </div>
                        <div className="col-md-3">
                            <FormField
                                id="crn-debut"
                                label="de"
                                type="time"
                                required
                                value={form.data.heure_debut}
                                onChange={(event) => setField('heure_debut', event.target.value)}
                                error={form.errors.heure_debut}
                            />
                        </div>
                        <div className="col-md-3">
                            <FormField
                                id="crn-fin"
                                label="à"
                                type="time"
                                required
                                value={form.data.heure_fin}
                                onChange={(event) => setField('heure_fin', event.target.value)}
                                error={form.errors.heure_fin}
                            />
                        </div>
                        <div className="col-md-6">
                            <SelectField
                                id="crn-enseignant"
                                label="Enseignant"
                                options={enseignantOptions}
                                placeholder="Choisir un enseignant"
                                value={form.data.enseignant_id}
                                onChange={(event) => setField('enseignant_id', event.target.value)}
                                error={form.errors.enseignant_id}
                            />
                        </div>
                        <div className="col-md-6">
                            <SelectField
                                id="crn-salle"
                                label="Salle"
                                options={salleOptions}
                                placeholder="Choisir une salle"
                                value={form.data.salle_id}
                                onChange={(event) => setField('salle_id', event.target.value)}
                                error={form.errors.salle_id}
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
                title="Supprimer ce créneau ?"
                recordLabel={deleteTarget ? `${deleteTarget.groupNom} — ${jours[String(deleteTarget.jourSemaine)]}` : ''}
                message="Les séances futures déjà générées à partir de ce créneau (non encore effectuées) seront aussi supprimées."
                error={deleteError}
                processing={deleting}
                onConfirm={confirmDelete}
                onCancel={() => setDeleteTarget(null)}
            />
        </BackofficeLayout>
    );
}
