import { router, useForm } from '@inertiajs/react';
import { useMemo, useState, type FormEvent } from 'react';
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

/** "HH:MM" → minutes since midnight, used to place créneaux on the hour grid (see heuresGrille / cellules). */
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

    // Hour rows: the default 08:00–21:00 range, extended to cover any créneau
    // starting earlier / ending later. Rows are always full hours so a
    // créneau can span several of them with rowSpan (10:00–12:30 covers the
    // 10h, 11h and 12h rows), instead of being pinned to its start row only.
    const heuresGrille = useMemo(() => {
        let first = 8;
        let last = 21;
        creneaux.forEach((c) => {
            first = Math.min(first, Math.floor(toMinutes(c.heureDebut) / 60));
            last = Math.max(last, Math.ceil(toMinutes(c.heureFin) / 60) - 1);
        });
        return Array.from({ length: last - first + 1 }, (_, i) => `${String(first + i).padStart(2, '0')}:00`);
    }, [creneaux]);

    // Per (jour, hour row) → the créneaux anchored in that cell and how many
    // rows the cell spans; cells swallowed by a spanning cell above are skipped.
    const cellules = useMemo(() => {
        const anchors = new Map<string, { rows: CreneauRow[]; rowSpan: number }>();
        const skipped = new Set<string>();
        const firstHour = heuresGrille.length ? Number(heuresGrille[0].slice(0, 2)) : 8;

        jourOptions.forEach((jour) => {
            const jourKey = String(jour.value);
            const list = [...(parRang.get(jourKey) ?? [])].sort(
                (a, b) => toMinutes(a.heureDebut) - toMinutes(b.heureDebut),
            );
            let anchorIdx = -1;
            let coveredUntil = -1; // exclusive row index covered by the current anchor

            list.forEach((c) => {
                const startIdx = Math.floor(toMinutes(c.heureDebut) / 60) - firstHour;
                const endIdx = Math.ceil(toMinutes(c.heureFin) / 60) - firstHour; // exclusive
                if (startIdx >= coveredUntil) {
                    anchorIdx = startIdx;
                    coveredUntil = startIdx;
                }
                const key = `${jourKey}|${anchorIdx}`;
                const cell = anchors.get(key) ?? { rows: [], rowSpan: 1 };
                cell.rows.push(c);
                coveredUntil = Math.max(coveredUntil, endIdx);
                cell.rowSpan = Math.max(1, coveredUntil - anchorIdx);
                anchors.set(key, cell);
                for (let i = anchorIdx + 1; i < coveredUntil; i++) {
                    skipped.add(`${jourKey}|${i}`);
                }
            });
        });

        return { anchors, skipped };
    }, [heuresGrille, jourOptions, parRang]);

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
                                {heuresGrille.map((heure, rowIdx) => {
                                    const heureFin = `${String(Number(heure.slice(0, 2)) + 1).padStart(2, '0')}:00`;

                                    return (
                                        <tr key={heure}>
                                            <td className="fw-medium text-nowrap">
                                                {heure.slice(0, 5)} - {heureFin}
                                            </td>
                                            {jourOptions.map((jour) => {
                                                const key = `${jour.value}|${rowIdx}`;
                                                if (cellules.skipped.has(key)) {
                                                    return null;
                                                }
                                                const cell = cellules.anchors.get(key);
                                                const rows = cell?.rows ?? [];

                                                return (
                                                    <td
                                                        key={jour.value}
                                                        rowSpan={cell?.rowSpan ?? 1}
                                                        style={{ minWidth: 160, verticalAlign: 'top' }}
                                                    >
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
