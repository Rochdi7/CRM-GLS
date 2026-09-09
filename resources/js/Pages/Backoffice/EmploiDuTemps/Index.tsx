import { router, useForm } from '@inertiajs/react';
import { useMemo, useState, type FormEvent } from 'react';
import { useAutoOpenCreate } from '@/Hooks/useAutoOpenCreate';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import TableToolbar from '@/Components/Tables/TableToolbar';
import { useFilterReset } from '@/Hooks/useFilterReset';
import Modal from '@/Components/Modals/Modal';
import ConfirmDialog from '@/Components/Modals/ConfirmDialog';
import SelectField from '@/Components/Forms/SelectField';
import MultiSelectField from '@/Components/Forms/MultiSelectField';
import FormField from '@/Components/Forms/FormField';
import FormActions from '@/Components/Forms/FormActions';
import RowActions, { RowActionItem } from '@/Components/Tables/RowActions';
import WeekTimeline from '@/Components/EmploiDuTemps/WeekTimeline';
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

/** "HH:MM" → minutes since midnight — the « Paramétrage » table sorts on it. */
function toMinutes(heure: string): number {
    const [h, m] = heure.split(':').map(Number);
    return h * 60 + (m || 0);
}

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
    // Fiche du créneau (lecture seule) — ce qu'ouvre un clic sur une carte de
    // la grille. Voir openView.
    const [viewing, setViewing] = useState<CreneauRow | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<CreneauRow | null>(null);
    const [deleteError, setDeleteError] = useState<string>();
    const [deleting, setDeleting] = useState(false);
    // Which of the two views of the SAME créneaux is showing. Purely client
    // side: both read the identical server-filtered `creneaux` prop, so
    // switching tabs never refetches and never touches the filters (§5 —
    // filters are only ever cleared by the explicit reset button).
    const [vue, setVue] = useState<'grille' | 'parametrage'>('grille');
    // Table-view sort — client-side over the already-filtered rows, which is
    // safe here because the emploi du temps of one centre+année is a weekly
    // template of a few dozen rows, not a paginated dataset (§7).
    const [sortField, setSortField] = useState<'groupe' | 'jour' | 'heure' | 'salle' | 'enseignant'>('jour');
    const [sortDir, setSortDir] = useState<'asc' | 'desc'>('asc');

    const createForm = useForm<CreneauCreateForm>(EMPTY_CREATE_FORM);
    const editForm = useForm<CreneauForm>(EMPTY_EDIT_FORM);
    const isEditing = editingId !== null;
    const form = isEditing ? editForm : createForm;

    const jourOptions: SelectOption[] = Object.entries(jours).map(([value, label]) => ({ value: Number(value), label }));

    // Rows for the « Paramétrage » table — same créneaux as the grid, flat
    // and sorted. Default order (jour, then heure) reads like the week.
    const lignes = useMemo(() => {
        const dir = sortDir === "asc" ? 1 : -1;
        const value = (row: CreneauRow): string | number => {
            switch (sortField) {
                case "groupe":
                    return row.groupNom.toLocaleLowerCase("fr");
                case "heure":
                    return toMinutes(row.heureDebut);
                case "salle":
                    return (row.salle ?? "").toLocaleLowerCase("fr");
                case "enseignant":
                    return (row.enseignant ?? "").toLocaleLowerCase("fr");
                default:
                    return row.jourSemaine;
            }
        };

        return [...creneaux].sort((a, b) => {
            const va = value(a);
            const vb = value(b);
            if (va < vb) return -1 * dir;
            if (va > vb) return 1 * dir;
            // Stable secondary key so equal cells keep a predictable order.
            return (a.jourSemaine - b.jourSemaine) || (toMinutes(a.heureDebut) - toMinutes(b.heureDebut));
        });
    }, [creneaux, sortField, sortDir]);

    function toggleSort(field: typeof sortField) {
        if (sortField === field) {
            setSortDir((current) => (current === "asc" ? "desc" : "asc"));
            return;
        }
        setSortField(field);
        setSortDir('asc');
    }
    function reload(nextFilters: Partial<typeof filters>) {
        router.get(
            '/backoffice/emploi-du-temps',
            { ...filters, ...nextFilters },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    const filterReset = useFilterReset(filters, reload);

    function openCreate() {
        setEditingId(null);
        createForm.reset();
        createForm.clearErrors();
        createForm.setData(EMPTY_CREATE_FORM);
        setShowModal(true);
    }

    // Raccourci « Actions rapides » du tableau de bord : ?nouveau=1 ouvre
    // directement ce formulaire (confort d'interface seulement, §5).
    useAutoOpenCreate(openCreate, permissions.create);

    /**
     * Fiche du créneau, en LECTURE SEULE — ce qu'ouvre un clic sur une carte
     * de la grille (09/09/2026).
     *
     * Le clic ouvrait le formulaire de modification. La carte est petite et
     * son contenu tronqué : on clique dessus pour LIRE, et on atterrissait
     * dans un formulaire prérempli où un Entrée suffisait à enregistrer. Le
     * geste le plus courant de l'écran était donc le plus risqué.
     *
     * Depuis la fiche, « Modifier » reste à un clic pour qui en a le droit —
     * l'action d'écriture s'énonce, elle ne se déduit pas d'un clic sur du
     * texte.
     */
    function openView(row: CreneauRow) {
        setViewing(row);
    }

    /** Passe de la fiche au formulaire, sans refermer/rouvrir la grille. */
    function editFromView(row: CreneauRow) {
        setViewing(null);
        openEdit(row);
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
            {/* Two views of the same weekly template: the calendar grid, and
                « Paramétrage » — a flat sortable table where each créneau is
                edited row by row. Client-side only, no reload, filters shared. */}
            <ul className="nav nav-tabs p-0 border-bottom rounded-0 mb-4" role="tablist">
                {([
                    { key: 'grille' as const, label: "Emploi du temps", icon: 'ti ti-calendar-time' },
                    { key: 'parametrage' as const, label: "Paramétrage d'emploi du temps", icon: 'ti ti-list-details' },
                ]).map((tab) => (
                    <li className="nav-item" key={tab.key} role="presentation">
                        <button
                            type="button"
                            className={`nav-link d-inline-flex align-items-center${vue === tab.key ? ' active' : ''}`}
                            aria-current={vue === tab.key ? 'page' : undefined}
                            onClick={() => setVue(tab.key)}
                        >
                            <i className={`${tab.icon} me-2`} aria-hidden="true" />
                            {tab.label}
                        </button>
                    </li>
                ))}
            </ul>
            <Card title="Emploi du temps" bodyClassName="p-0 py-3">
                <div className="px-3 pt-2">
                    <TableToolbar onReset={filterReset.reset} resetActive={filterReset.active}>
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

                {vue === 'grille' && (
                    <div className="px-3">
                        <WeekTimeline
                            creneaux={creneaux}
                            jours={jours}
                            jourFilter={filters.jourFilter}
                            canUpdate={permissions.update}
                            canDelete={permissions.delete}
                            onView={openView}
                            onEdit={openEdit}
                            onDelete={(row) => {
                                setDeleteTarget(row);
                                setDeleteError(undefined);
                            }}
                        />
                    </div>
                )}

                {/* « Paramétrage d’emploi du temps » -- the same créneaux as a flat, sortable list,
                    one row per slot, edited through the same modal as the grid so
                    both views stay in sync with one form and one endpoint. */}
                {vue === 'parametrage' && (
                    <div className="table-responsive px-3">
                        <table className="table align-middle mb-0">
                            <thead className="thead-light">
                                <tr>
                                    {([
                                        { field: 'groupe' as const, label: 'Groupe' },
                                        { field: 'jour' as const, label: 'Jour' },
                                        { field: 'heure' as const, label: 'Heure' },
                                        { field: 'salle' as const, label: 'Salle' },
                                        { field: 'enseignant' as const, label: 'Enseignant' },
                                    ]).map((col) => (
                                        <th key={col.field}>
                                            <button
                                                type="button"
                                                className="btn btn-link p-0 text-reset text-decoration-none fw-semibold d-inline-flex align-items-center"
                                                onClick={() => toggleSort(col.field)}
                                            >
                                                {col.label}
                                                <i
                                                    className={`ti ms-1 ${
                                                        sortField === col.field
                                                            ? sortDir === 'asc'
                                                                ? 'ti-caret-up-filled'
                                                                : 'ti-caret-down-filled'
                                                            : 'ti-selector opacity-50'
                                                    }`}
                                                    aria-hidden="true"
                                                />
                                            </button>
                                        </th>
                                    ))}
                                    <th className="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                {lignes.length === 0 ? (
                                    <tr>
                                        <td colSpan={6} className="text-center text-muted py-4">
                                            Aucun créneau pour ces filtres.
                                        </td>
                                    </tr>
                                ) : (
                                    lignes.map((row) => (
                                        <tr key={row.id}>
                                            <td>
                                                {row.groupNom}
                                                {row.groupNiveau && (
                                                    <span className="badge badge-soft-secondary ms-1">
                                                        {row.groupNiveau}
                                                    </span>
                                                )}
                                            </td>
                                            <td>{jours[String(row.jourSemaine)] ?? '—'}</td>
                                            <td className="text-nowrap">
                                                de {row.heureDebut} à {row.heureFin}
                                            </td>
                                            <td>{row.salle ?? '—'}</td>
                                            <td>{row.enseignant ?? '—'}</td>
                                            <td className="text-end">
                                                <RowActions>
                                                    {permissions.update && (
                                                        <RowActionItem icon="ti ti-edit" onClick={() => openEdit(row)}>
                                                            Modifier
                                                        </RowActionItem>
                                                    )}
                                                    {permissions.delete && (
                                                        <RowActionItem
                                                            icon="ti ti-trash"
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
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                )}
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

            {/*
              Fiche du créneau — LECTURE SEULE. Elle ne réaffiche que ce que la
              grille porte déjà (CreneauRow), sans requête supplémentaire : un
              clic sur une carte doit répondre instantanément, c'est un geste de
              consultation.
            */}
            <Modal
                show={viewing !== null}
                title="Détail du créneau"
                onClose={() => setViewing(null)}
                footer={
                    <>
                        <button type="button" className="btn btn-light" onClick={() => setViewing(null)}>
                            Fermer
                        </button>
                        {permissions.update && viewing && (
                            <button type="button" className="btn btn-primary" onClick={() => editFromView(viewing)}>
                                <i className="ti ti-edit me-1" />
                                Modifier
                            </button>
                        )}
                    </>
                }
            >
                {viewing && (
                    <dl className="row mb-0">
                        <dt className="col-sm-4 text-muted fw-normal">Groupe</dt>
                        <dd className="col-sm-8 fw-medium">
                            {viewing.groupNom}
                            {viewing.groupNiveau && (
                                <span className="badge bg-primary-transparent ms-2">{viewing.groupNiveau}</span>
                            )}
                        </dd>

                        <dt className="col-sm-4 text-muted fw-normal">Jour</dt>
                        <dd className="col-sm-8">{jours[String(viewing.jourSemaine)] ?? '—'}</dd>

                        <dt className="col-sm-4 text-muted fw-normal">Horaire</dt>
                        <dd className="col-sm-8">de {viewing.heureDebut} à {viewing.heureFin}</dd>

                        <dt className="col-sm-4 text-muted fw-normal">Enseignant</dt>
                        <dd className="col-sm-8">{viewing.enseignant ?? '—'}</dd>

                        <dt className="col-sm-4 text-muted fw-normal">Salle</dt>
                        <dd className="col-sm-8">{viewing.salle ?? '—'}</dd>

                        {/*
                          Une case morte s'explique ici comme sur la grille : une fin
                          de formation est NORMALE, un remplacement d'enseignant aussi
                          — jamais présentés comme une anomalie (07/09/2026).
                        */}
                        {viewing.clos && (
                            <>
                                <dt className="col-sm-4 text-muted fw-normal">Statut</dt>
                                <dd className="col-sm-8 mb-0">
                                    <span className="badge bg-secondary-transparent">
                                        {viewing.motifCloture === 'termine' ? 'Fin de formation' : 'Enseignant remplacé'}
                                        {viewing.dateFin ? ` le ${viewing.dateFin}` : ''}
                                    </span>
                                    <div className="text-muted fs-13 mt-1">
                                        Ce créneau ne génère plus de séance.
                                    </div>
                                </dd>
                            </>
                        )}
                    </dl>
                )}
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
