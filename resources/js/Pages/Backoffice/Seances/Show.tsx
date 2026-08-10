import { useState } from 'react';
import { Link, router, useForm } from '@inertiajs/react';
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

interface PresencesFormData {
    presences: Record<string, { statut: string; note: string }>;
    [key: string]: Record<string, { statut: string; note: string }>;
}

/** The three toggle columns of the roll-call design (Justifié stays a data value, not a column). */
const TOGGLE_STATUTS = ['Présent', 'Retard', 'Absent'] as const;

/**
 * Fiche de présence — "Saisir absence" design: Date / Employé / Séances
 * pickers on top (they re-query the séance list server-side and navigate
 * between séances), a "Suivi des présences" tab, the collapsible
 * "Formation" section and one roll-call line per student with mutually
 * exclusive Présent / Retard / Absent switches (header switches mark the
 * whole column). The whole roll call still saves in one PUT
 * (EnregistrerPresences, single transaction).
 */
export default function SeanceShow({
    seance,
    canMark,
    filters,
    enseignantOptions,
    seanceOptions,
}: SeanceShowProps) {
    const [sectionOpen, setSectionOpen] = useState(true);

    const form = useForm<PresencesFormData>({
        presences: Object.fromEntries(
            seance.students.map((student) => [
                String(student.id),
                { statut: student.statut ?? '', note: student.note },
            ]),
        ),
    });

    function setLine(studentId: number, statut: string) {
        form.setData('presences', {
            ...form.data.presences,
            [String(studentId)]: { ...form.data.presences[String(studentId)], statut },
        });
    }

    function markAll(statut: string) {
        form.setData(
            'presences',
            Object.fromEntries(
                Object.entries(form.data.presences).map(([id, line]) => [id, { ...line, statut }]),
            ),
        );
    }

    function allHave(statut: string): boolean {
        return (
            seance.students.length > 0 &&
            seance.students.every((s) => form.data.presences[String(s.id)]?.statut === statut)
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

    function handleSubmit(event: React.FormEvent) {
        event.preventDefault();

        // Only lines with a chosen statut are submitted — untouched students
        // stay unrecorded rather than being forced into a default.
        form.transform((data) => ({
            presences: Object.fromEntries(
                Object.entries((data as PresencesFormData).presences).filter(([, line]) => line.statut !== ''),
            ),
        }));
        form.put(`/backoffice/seances/${seance.id}/presences`, {
            preserveScroll: true,
            onFinish: () => form.transform((data) => data),
        });
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

                <ul className="nav nav-tabs mb-0" role="tablist">
                    <li className="nav-item" role="presentation">
                        <button type="button" className="nav-link active fw-medium" role="tab" aria-selected="true">
                            <i className="ti ti-search me-2" />
                            Suivi des présences
                        </button>
                    </li>
                </ul>

                <button
                    type="button"
                    className="btn p-0 border-0 d-flex align-items-center fw-bold fs-6 my-3"
                    aria-expanded={sectionOpen}
                    onClick={() => setSectionOpen((o) => !o)}
                >
                    <i
                        className={`ti ${sectionOpen ? 'ti-circle-chevron-down' : 'ti-circle-chevron-right'} me-2 fs-5`}
                        aria-hidden="true"
                    />
                    Formation
                </button>

                {sectionOpen && (
                    <form onSubmit={handleSubmit}>
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
                                            <td colSpan={4} className="text-center text-muted py-4">
                                                Aucun étudiant inscrit dans ce groupe
                                            </td>
                                        </tr>
                                    )}
                                    {seance.students.map((student) => {
                                        const line = form.data.presences[String(student.id)];

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

                        {form.errors.presences && (
                            <p className="text-danger mt-2 mb-0">{form.errors.presences}</p>
                        )}

                        {canMark && seance.students.length > 0 && (
                            <div className="d-flex justify-content-end pt-3">
                                <button type="submit" className="btn btn-primary" disabled={form.processing}>
                                    {form.processing ? 'Enregistrement…' : 'Enregistrer les présences'}
                                </button>
                            </div>
                        )}
                    </form>
                )}
            </Card>
        </BackofficeLayout>
    );
}
