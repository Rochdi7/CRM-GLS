import { useEffect, useRef, useState } from 'react';
import axios from 'axios';
import { Link, router } from '@inertiajs/react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import DateField from '@/Components/Forms/DateField';
import SelectField from '@/Components/Forms/SelectField';
import type { SeanceDetails, SelectOption } from '@/Types';

interface SeanceShowProps {
    seance: SeanceDetails;
    presenceStatuts: string[];
    canMark: boolean;
    filters: { date: string; enseignant: number | null };
    enseignantOptions: SelectOption[];
    seanceOptions: SelectOption[];
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
    canMark,
    filters,
    enseignantOptions,
    seanceOptions,
}: SeanceShowProps) {
    const [sectionOpen, setSectionOpen] = useState(true);
    const [saving, setSaving] = useState(false);
    const [saveError, setSaveError] = useState(false);
    const saveTimer = useRef<number | undefined>(undefined);

    useEffect(() => () => window.clearTimeout(saveTimer.current), []);

    const [presences, setPresences] = useState<Record<string, PresenceLine>>(() =>
        Object.fromEntries(
            seance.students.map((student) => [
                String(student.id),
                { statut: student.statut ?? '', note: student.note },
            ]),
        ),
    );

    function save(next: Record<string, PresenceLine>) {
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
            axios
                .put(`/backoffice/seances/${seance.id}/presences`, { presences: marked })
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
            seance.students.length > 0 &&
            seance.students.every((s) => presences[String(s.id)]?.statut === statut)
        );
    }

    function changeFilters(next: { date?: string; enseignant?: string }) {
        router.get(
            `/backoffice/seances/${seance.id}`,
            {
                date: next.date ?? filters.date,
                enseignant: next.enseignant ?? (filters.enseignant === null ? '' : String(filters.enseignant)),
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function openSeance(id: string) {
        if (id !== '' && Number(id) !== seance.id) {
            router.get(`/backoffice/seances/${id}`, {
                date: filters.date,
                enseignant: filters.enseignant === null ? '' : String(filters.enseignant),
            });
        }
    }

    return (
        <BackofficeLayout
            title="Saisir absence"
            breadcrumbs={[
                { label: 'Tableau de bord', href: '/backoffice/dashboard' },
                { label: 'Présences', href: '/backoffice/seances' },
                { label: `Séance du ${seance.dateSeance}` },
            ]}
            actions={
                <Link href="/backoffice/seances" className="btn btn-outline-light d-flex align-items-center">
                    <i className="ti ti-arrow-left me-2" />
                    Retour aux séances
                </Link>
            }
        >
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
                            placeholder="Tous les employés"
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
                            value={String(seance.id)}
                            onChange={(event) => openSeance(event.target.value)}
                        />
                    </div>
                </div>

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
                        className={`ti ${sectionOpen ? 'ti-circle-chevron-down' : 'ti-circle-chevron-right'} me-2 fs-4 text-dark`}
                        aria-hidden="true"
                    />
                    <span className="fs-5 fw-bold text-dark">Formation</span>
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
                                                        disabled={!canMark || seance.students.length === 0}
                                                        onChange={(event) => markAll(event.target.checked ? statut : '')}
                                                    />
                                                </div>
                                            </div>
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {seance.students.length === 0 && (
                                    <tr>
                                        <td colSpan={3} className="text-center text-muted py-4">
                                            Aucun étudiant inscrit dans ce groupe
                                        </td>
                                    </tr>
                                )}
                                {seance.students.map((student) => {
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
                                                            <i className="ti ti-user fs-5" />
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
        </BackofficeLayout>
    );
}
