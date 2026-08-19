import { useEffect, useRef, useState } from 'react';
import { Link, router, useForm } from '@inertiajs/react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import DateField from '@/Components/Forms/DateField';
import SelectField from '@/Components/Forms/SelectField';
import Modal from '@/Components/Modals/Modal';
import ConfirmDialog from '@/Components/Modals/ConfirmDialog';
import TextareaField from '@/Components/Forms/TextareaField';
import FormActions from '@/Components/Forms/FormActions';
import StatusBadge from '@/Components/Details/StatusBadge';
import RowActions, { RowActionItem } from '@/Components/Tables/RowActions';
import RelatedRecordsTable from '@/Components/Details/RelatedRecordsTable';
import Pagination from '@/Components/Tables/Pagination';
import TableLengthRow from '@/Components/Tables/TableLengthRow';
import TableToolbar from '@/Components/Tables/TableToolbar';
import SearchInput from '@/Components/Tables/SearchInput';
import type { PaginatedData, SeanceDetails, SeanceRow, SelectOption } from '@/Types';

interface GroupOption extends SelectOption {
    enseignantId: number | null;
}

interface SeanceListFilters {
    search: string;
    groupFilter: string;
    statutFilter: string;
    enseignantFilter: string;
    dateFrom: string;
    dateTo: string;
    perPage: number;
}

interface SeanceShowProps {
    seance: SeanceDetails | null;
    /** The current page's own URL (seances.show/{id} or seances.presences) — every filter reload targets this, never a hardcoded id-based path. */
    pageUrl: string;
    presenceStatuts: string[];
    canMark: boolean;
    canValidate: boolean;
    canCancel: boolean;
    filters: { date: string; enseignant: number | null };
    enseignantOptions: SelectOption[];
    seanceOptions: SelectOption[];
    seances: PaginatedData<SeanceRow>;
    listFilters: SeanceListFilters;
    perPageOptions: number[];
    groupOptions: GroupOption[];
    statuts: string[];
    listPermissions: { create: boolean; update: boolean; delete: boolean; mark: boolean };
}

type ShowTab = 'seances' | 'appel';

function statutVariant(statut: string): 'success' | 'secondary' | 'danger' | 'info' {
    if (statut === 'Effectuée') return 'success';
    if (statut === 'Annulée') return 'danger';
    return 'info';
}

type PresenceLine = { statut: string; note: string };

/** The toggle columns of the roll-call design (Retard/Justifié stay valid data values, not columns). */
const TOGGLE_STATUTS = ['Présent', 'Absent'] as const;

/**
 * Fiche de présence — "Saisir absence" design: Date / Employé / Séances
 * pickers on top (they re-query the séance list server-side and navigate
 * between séances), a "Suivi des présences" tab, the collapsible
 * "Formation" section and one roll-call line per student with mutually
 * exclusive Présent / Retard / Absent switches (header switches mark the
 * whole column). Every toggle auto-saves the roll call in one PUT
 * (EnregistrerPresences, single transaction) — no save button.
 */
export default function SeanceShow({
    seance,
    pageUrl,
    canMark,
    canValidate,
    canCancel,
    filters,
    enseignantOptions,
    seanceOptions,
    seances,
    listFilters,
    perPageOptions,
    groupOptions,
    statuts,
    listPermissions,
}: SeanceShowProps) {
    const [activeTab, setActiveTab] = useState<ShowTab>('appel');
    const [sectionOpen, setSectionOpen] = useState(true);
    const [saving, setSaving] = useState(false);
    const [saveError, setSaveError] = useState(false);
    const saveTimer = useRef<number | undefined>(undefined);

    const [validateTarget, setValidateTarget] = useState(false);
    const [validating, setValidating] = useState(false);
    const [showAnnulerModal, setShowAnnulerModal] = useState(false);
    const annulerForm = useForm<{ motif: string }>({ motif: '' });

    useEffect(() => () => window.clearTimeout(saveTimer.current), []);

    const [presences, setPresences] = useState<Record<string, PresenceLine>>(() =>
        Object.fromEntries(
            (seance?.students ?? []).map((student) => [
                String(student.id),
                { statut: student.statut ?? '', note: student.note },
            ]),
        ),
    );

    function save(next: Record<string, PresenceLine>) {
        if (!seance) {
            return;
        }

        // Only lines with a chosen statut are persisted — untouched students
        // stay unrecorded. Nothing marked yet ⇒ nothing to save.
        const marked = Object.fromEntries(
            Object.entries(next).filter(([, line]) => line.statut !== ''),
        );

        if (Object.keys(marked).length === 0) {
            return;
        }

        // Background XHR — never an Inertia visit, so toggling never
        // re-renders the page. Debounced so a burst of toggles (or a
        // header "mark all") lands as one request with the latest state.
        window.clearTimeout(saveTimer.current);
        saveTimer.current = window.setTimeout(() => {
            setSaving(true);
            setSaveError(false);
            const csrf =
                document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
                    ?.content ?? '';

            fetch(`/backoffice/seances/${seance.id}/presences`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ presences: marked }),
            })
                .then((response) => {
                    // fetch only rejects on network failure, so a 4xx/5xx
                    // has to be turned into an error explicitly.
                    if (! response.ok) {
                        throw new Error(String(response.status));
                    }
                })
                .catch(() => setSaveError(true))
                .finally(() => setSaving(false));
        }, 400);
    }

    function apply(next: Record<string, PresenceLine>) {
        setPresences(next);
        save(next);
    }

    function setLine(studentId: number, statut: string) {
        apply({
            ...presences,
            [String(studentId)]: { ...presences[String(studentId)], statut },
        });
    }

    function markAll(statut: string) {
        apply(
            Object.fromEntries(
                Object.entries(presences).map(([id, line]) => [id, { ...line, statut }]),
            ),
        );
    }

    function allHave(statut: string): boolean {
        return (
            (seance?.students.length ?? 0) > 0 &&
            (seance?.students ?? []).every((s) => presences[String(s.id)]?.statut === statut)
        );
    }

    function changeFilters(next: { date?: string; enseignant?: string }) {
        router.get(
            pageUrl,
            {
                date: next.date ?? filters.date,
                enseignant: next.enseignant ?? (filters.enseignant === null ? '' : String(filters.enseignant)),
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function reloadList(changes: Partial<Record<string, string | number>>) {
        router.get(
            pageUrl,
            { ...listFilters, ...changes, page: undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function openSeance(id: string) {
        if (id !== '' && Number(id) !== seance?.id) {
            router.get(`/backoffice/seances/${id}`, {
                date: filters.date,
                enseignant: filters.enseignant === null ? '' : String(filters.enseignant),
            });
        }
    }

    function confirmValider() {
        if (!seance) {
            return;
        }

        setValidating(true);
        router.post(
            `/backoffice/seances/${seance.id}/valider`,
            {},
            {
                preserveScroll: true,
                onFinish: () => {
                    setValidating(false);
                    setValidateTarget(false);
                },
            },
        );
    }

    function openAnnulerModal() {
        annulerForm.reset();
        annulerForm.clearErrors();
        setShowAnnulerModal(true);
    }

    function closeAnnulerModal() {
        setShowAnnulerModal(false);
        annulerForm.reset();
        annulerForm.clearErrors();
    }

    function submitAnnuler(event: React.FormEvent) {
        event.preventDefault();

        if (!seance) {
            return;
        }

        annulerForm.post(`/backoffice/seances/${seance.id}/annuler`, {
            preserveScroll: true,
            onSuccess: () => closeAnnulerModal(),
        });
    }

    return (
        <BackofficeLayout
            title="Saisir absence"
            breadcrumbs={[
                { label: 'Tableau de bord', href: '/backoffice/dashboard' },
                { label: 'Gestion des séances', href: '/backoffice/seances' },
                { label: seance ? `Séance du ${seance.dateSeance}` : "Saisir l'absence" },
            ]}
            actions={
                <div className="d-flex align-items-center gap-2">
                    {seance && <StatusBadge label={seance.statut} variant={statutVariant(seance.statut)} dot />}
                    {(canValidate || canCancel) && (
                        <RowActions>
                            {canValidate && (
                                <RowActionItem
                                    icon="ti-circle-check"
                                    onClick={() => setValidateTarget(true)}
                                >
                                    Valider la séance
                                </RowActionItem>
                            )}
                            {canCancel && (
                                <RowActionItem icon="ti-x" danger onClick={openAnnulerModal}>
                                    Annuler la séance
                                </RowActionItem>
                            )}
                        </RowActions>
                    )}
                    <Link href="/backoffice/seances" className="btn btn-outline-light d-flex align-items-center">
                        <i className="ti ti-arrow-left me-2" />
                        Retour aux séances
                    </Link>
                </div>
            }
        >
            <ul className="nav nav-tabs mb-3" role="tablist">
                <li className="nav-item" role="presentation">
                    <button
                        type="button"
                        className={`nav-link fw-medium${activeTab === 'seances' ? ' active' : ''}`}
                        role="tab"
                        aria-selected={activeTab === 'seances'}
                        onClick={() => setActiveTab('seances')}
                    >
                        <i className="ti ti-calendar-check me-2" />
                        Séances
                    </button>
                </li>
                <li className="nav-item" role="presentation">
                    <button
                        type="button"
                        className={`nav-link fw-medium${activeTab === 'appel' ? ' active' : ''}`}
                        role="tab"
                        aria-selected={activeTab === 'appel'}
                        onClick={() => setActiveTab('appel')}
                    >
                        <i className="ti ti-checklist me-2" />
                        Saisir l'absence
                    </button>
                </li>
            </ul>

            {activeTab === 'seances' && (
                <Card title="Séances" bodyClassName="p-0 py-3">
                    <div className="px-3 pt-2">
                        <TableToolbar>
                            <div style={{ width: 220 }}>
                                <label className="form-label" htmlFor="seance-list-f-groupe">
                                    Groupe
                                </label>
                                <SelectField
                                    id="seance-list-f-groupe"
                                    options={groupOptions}
                                    placeholder="Choisir un niveau scolaire"
                                    value={listFilters.groupFilter}
                                    onChange={(event) => reloadList({ groupFilter: event.target.value })}
                                />
                            </div>
                            <div style={{ width: 190 }}>
                                <label className="form-label" htmlFor="seance-list-f-statut">
                                    Statut
                                </label>
                                <SelectField
                                    id="seance-list-f-statut"
                                    options={statuts.map((statut) => ({ value: statut, label: statut }))}
                                    placeholder="Choisir un statut"
                                    value={listFilters.statutFilter}
                                    onChange={(event) => reloadList({ statutFilter: event.target.value })}
                                />
                            </div>
                            <div style={{ width: 220 }}>
                                <label className="form-label" htmlFor="seance-list-f-enseignant">
                                    Enseignant
                                </label>
                                <SelectField
                                    id="seance-list-f-enseignant"
                                    options={enseignantOptions}
                                    placeholder="Choisir un enseignant"
                                    value={listFilters.enseignantFilter}
                                    onChange={(event) => reloadList({ enseignantFilter: event.target.value })}
                                />
                            </div>
                            <div style={{ width: 170 }}>
                                <label className="form-label" htmlFor="seance-list-f-du">
                                    Date de début
                                </label>
                                <DateField
                                    id="seance-list-f-du"
                                    value={listFilters.dateFrom}
                                    onChange={(event) => reloadList({ dateFrom: event.target.value })}
                                />
                            </div>
                            <div style={{ width: 170 }}>
                                <label className="form-label" htmlFor="seance-list-f-au">
                                    Date de fin
                                </label>
                                <DateField
                                    id="seance-list-f-au"
                                    value={listFilters.dateTo}
                                    onChange={(event) => reloadList({ dateTo: event.target.value })}
                                />
                            </div>
                        </TableToolbar>
                    </div>

                    <TableLengthRow
                        perPage={listFilters.perPage}
                        perPageOptions={perPageOptions}
                        onPerPageChange={(perPage) => reloadList({ perPage })}
                        search={
                            <SearchInput
                                value={listFilters.search}
                                onSearch={(search) => reloadList({ search })}
                                placeholder="Rechercher un groupe"
                            />
                        }
                    />

                    <RelatedRecordsTable
                        isEmpty={seances.data.length === 0}
                        emptyTitle="Aucune séance pour le moment"
                        emptyIcon="ti ti-calendar-check"
                        head={
                            <tr>
                                <th>Date</th>
                                <th>Horaire</th>
                                <th>Groupe</th>
                                <th>Enseignant</th>
                                <th>Présences</th>
                                <th>Statut</th>
                                <th className="text-end">Action</th>
                            </tr>
                        }
                    >
                        {seances.data.map((row) => (
                            <tr key={row.id}>
                                <td className="fw-medium">
                                    <Link href={row.showUrl}>{row.dateSeance}</Link>
                                </td>
                                <td>
                                    {row.heureDebut ? `${row.heureDebut}${row.heureFin ? ` – ${row.heureFin}` : ''}` : '—'}
                                </td>
                                <td>
                                    {row.groupNom ?? '—'}
                                    {row.groupNiveau && (
                                        <span className="badge badge-soft-secondary ms-2">{row.groupNiveau}</span>
                                    )}
                                </td>
                                <td>{row.enseignant ?? '—'}</td>
                                <td>
                                    {row.presencesCount > 0 ? (
                                        <>
                                            <span className="badge badge-soft-success me-1">
                                                {row.presentsCount} présent{row.presentsCount > 1 ? 's' : ''}
                                            </span>
                                            <span className="badge badge-soft-danger">
                                                {row.absentsCount} absent{row.absentsCount > 1 ? 's' : ''}
                                            </span>
                                        </>
                                    ) : (
                                        <span className="text-muted">Appel non fait</span>
                                    )}
                                </td>
                                <td>
                                    <StatusBadge label={row.statut} variant={statutVariant(row.statut)} dot />
                                </td>
                                <td className="text-end">
                                    <div className="d-flex align-items-center justify-content-end gap-2">
                                        {listPermissions.mark && (
                                            <Link
                                                href={row.showUrl}
                                                className="btn btn-outline-primary btn-sm d-inline-flex align-items-center"
                                            >
                                                <i className="ti ti-checklist me-1" />
                                                Faire l'appel
                                            </Link>
                                        )}
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </RelatedRecordsTable>
                    <Pagination paginator={seances} showJumpToPage />
                </Card>
            )}

            {activeTab === 'appel' && (
            <Card>
                <div className="row">
                    <div className="col-md-4">
                        <DateField
                            id="presence-date"
                            label="Date"
                            required
                            value={filters.date}
                            onChange={(event) => changeFilters({ date: event.target.value })}
                        />
                    </div>
                    <div className="col-md-4">
                        <SelectField
                            id="presence-employe"
                            label="Employé"
                            required
                            options={enseignantOptions}
                            value={filters.enseignant === null ? '' : String(filters.enseignant)}
                            onChange={(event) => changeFilters({ enseignant: event.target.value })}
                        />
                    </div>
                    <div className="col-md-4">
                        <SelectField
                            id="presence-seance"
                            label="Séances"
                            required
                            options={seanceOptions}
                            placeholder="Choisir une séance…"
                            value={seance ? String(seance.id) : ''}
                            onChange={(event) => openSeance(event.target.value)}
                        />
                    </div>
                </div>

                {seance === null && (
                    <div className="alert alert-info d-flex align-items-start" role="alert">
                        <i className="ti ti-info-circle me-2 mt-1" aria-hidden="true" />
                        <div>Aucune séance à cette date. Choisissez une autre date ou une séance ci-dessus.</div>
                    </div>
                )}

                {seance && seance.statut === 'Annulée' && seance.motifAnnulation && (
                    <div className="alert alert-danger d-flex align-items-start" role="alert">
                        <i className="ti ti-circle-x me-2 mt-1" aria-hidden="true" />
                        <div>
                            <div className="fw-semibold">Séance annulée</div>
                            <div>{seance.motifAnnulation}</div>
                        </div>
                    </div>
                )}

                <div className="d-flex align-items-center justify-content-between">
                    <ul className="nav nav-tabs mb-0" role="tablist">
                        <li className="nav-item" role="presentation">
                            <button type="button" className="nav-link active fw-medium" role="tab" aria-selected="true">
                                <i className="ti ti-search me-2" />
                                Suivi des présences
                            </button>
                        </li>
                    </ul>
                    {saving && (
                        <span className="text-muted d-flex align-items-center">
                            <span className="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true" />
                            Enregistrement…
                        </span>
                    )}
                    {!saving && saveError && (
                        <span className="text-danger d-flex align-items-center">
                            <i className="ti ti-alert-circle me-2" aria-hidden="true" />
                            Échec de l'enregistrement — réessayez
                        </span>
                    )}
                </div>

                <button
                    type="button"
                    className="bg-transparent border-0 p-0 d-flex align-items-center my-3"
                    aria-expanded={sectionOpen}
                    onClick={() => setSectionOpen((o) => !o)}
                >
                    <i
                        className={`ti ${sectionOpen ? 'ti-circle-chevron-down' : 'ti-circle-chevron-right'} me-2 fs-20 text-dark`}
                        aria-hidden="true"
                    />
                    <span className="fs-18 fw-bold text-dark">Formation</span>
                </button>

                {sectionOpen && (
                    <div className="table-responsive">
                        <table className="table table-bordered align-middle mb-0">
                            <thead className="thead-light">
                                <tr>
                                    <th className="fw-semibold">Étudiant</th>
                                    {TOGGLE_STATUTS.map((statut) => (
                                        <th key={statut} style={{ width: '15%' }}>
                                            <div className="d-flex align-items-center gap-2">
                                                <span className="fw-semibold">{statut}</span>
                                                <div className="form-check form-switch mb-0">
                                                    <input
                                                        className="form-check-input"
                                                        type="checkbox"
                                                        role="switch"
                                                        aria-label={`Marquer tous ${statut}`}
                                                        checked={allHave(statut)}
                                                        disabled={!canMark || (seance?.students.length ?? 0) === 0}
                                                        onChange={(event) => markAll(event.target.checked ? statut : '')}
                                                    />
                                                </div>
                                            </div>
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {seance === null && (
                                    <tr>
                                        <td colSpan={3} className="text-center text-muted py-4">
                                            Aucune séance sélectionnée
                                        </td>
                                    </tr>
                                )}
                                {seance && seance.students.length === 0 && (
                                    <tr>
                                        <td colSpan={3} className="text-center text-muted py-4">
                                            Aucun étudiant inscrit dans ce groupe
                                        </td>
                                    </tr>
                                )}
                                {(seance?.students ?? []).map((student) => {
                                    const line = presences[String(student.id)];

                                    return (
                                        <tr key={student.id}>
                                            <td>
                                                <div className="d-flex align-items-center">
                                                    {student.photoUrl ? (
                                                        <img
                                                            src={student.photoUrl}
                                                            alt=""
                                                            className="avatar avatar-md rounded-circle me-2"
                                                        />
                                                    ) : (
                                                        <span
                                                            className="avatar avatar-md rounded-circle bg-light text-muted d-flex align-items-center justify-content-center me-2"
                                                            aria-hidden="true"
                                                        >
                                                            <i className="ti ti-user fs-18" />
                                                        </span>
                                                    )}
                                                    <span className="fw-medium text-uppercase">
                                                        {student.nom} {student.prenom}
                                                    </span>
                                                </div>
                                            </td>
                                            {TOGGLE_STATUTS.map((statut) => (
                                                <td key={statut}>
                                                    <div className="form-check form-switch mb-0">
                                                        <input
                                                            className="form-check-input"
                                                            type="checkbox"
                                                            role="switch"
                                                            aria-label={`${statut} — ${student.nom} ${student.prenom}`}
                                                            checked={line?.statut === statut}
                                                            disabled={!canMark}
                                                            onChange={(event) =>
                                                                setLine(student.id, event.target.checked ? statut : '')
                                                            }
                                                        />
                                                    </div>
                                                </td>
                                            ))}
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                )}
            </Card>
            )}

            <ConfirmDialog
                show={validateTarget}
                title="Valider la séance"
                recordLabel={seance ? `Séance du ${seance.dateSeance}` : ''}
                message="Confirmer que cette séance a bien eu lieu (statut Effectuée) ?"
                processing={validating}
                icon="ti-circle-check"
                variant="primary"
                confirmLabel="Oui, valider"
                processingLabel="Validation…"
                onConfirm={confirmValider}
                onCancel={() => setValidateTarget(false)}
            />

            <Modal
                show={showAnnulerModal}
                title="Annuler la séance"
                onClose={closeAnnulerModal}
                processing={annulerForm.processing}
            >
                <form onSubmit={submitAnnuler}>
                    <TextareaField
                        id="seance-annuler-motif"
                        label="Motif de l'annulation"
                        rows={4}
                        value={annulerForm.data.motif}
                        onChange={(event) => annulerForm.setData('motif', event.target.value)}
                        error={annulerForm.errors.motif}
                    />
                    <div className="d-flex justify-content-end gap-2 mt-3">
                        <FormActions
                            onCancel={closeAnnulerModal}
                            processing={annulerForm.processing}
                            submitLabel="Oui, annuler"
                            processingLabel="Annulation…"
                        />
                    </div>
                </form>
            </Modal>
        </BackofficeLayout>
    );
}
