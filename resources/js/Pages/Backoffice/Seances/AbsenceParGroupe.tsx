import { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import Modal from '@/Components/Modals/Modal';
import SelectField from '@/Components/Forms/SelectField';
import DateField from '@/Components/Forms/DateField';
import TableToolbar from '@/Components/Tables/TableToolbar';
import { useFilterReset } from '@/Hooks/useFilterReset';
import type { SelectOption } from '@/Types';

interface GroupOption extends SelectOption {
    enseignantId: number | null;
}

interface MatriceSeance {
    id: number;
    numero: number;
    date: string;
    heureDebut: string | null;
    heureFin: string | null;
    statut: string;
}

interface MatriceCell {
    statut: string;
    lettre: 'P' | 'Q';
    note: string | null;
}

interface MatriceStudent {
    id: number;
    reference: string | null;
    nom: string;
    prenom: string;
    photoUrl: string | null;
    inscriptionStatut: string;
    actif: boolean;
    presents: number;
    absents: number;
    /** Keyed by seance id (string), only the séances this student was marked on. */
    cells: Record<string, MatriceCell>;
}

interface AbsenceParGroupeProps {
    matrice: {
        seances: MatriceSeance[];
        students: MatriceStudent[];
        totals: { etudiants: number; presents: number; absents: number };
    };
    filters: {
        groupFilter: string;
        dateFrom: string;
        dateTo: string;
        statutFilter: string;
    };
    groupOptions: GroupOption[];
    statuts: string[];
}

function formatDate(date: string): string {
    const [year, month, day] = date.split('-');

    return year && month && day ? `${day}/${month}/${year}` : date;
}

/**
 * « Absence par groupe » — the presence MATRIX of one group: students in
 * rows, the séances of the selected window in columns, one P/Q cell each.
 *
 * The whole matrix is rendered at once (no pagination): it is one group over
 * one period, and the point of the screen is seeing every séance side by
 * side. Wide periods scroll horizontally inside the table wrapper, never the
 * page body (CLAUDE.md §5).
 */
export default function AbsenceParGroupe({
    matrice,
    filters,
    groupOptions,
    statuts,
}: AbsenceParGroupeProps) {
    function reload(changes: Partial<Record<string, string>>) {
        router.get(
            '/backoffice/seances/absence-par-groupe',
            { ...filters, ...changes },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    // « Fonctionnalité en test » — shown on EVERY arrival on this tab (no
    // localStorage, no dismissal memory): the matrix is still being verified
    // against the real registers, so every user must be told each time they
    // open it that what they read here is not yet authoritative. Plain React
    // state initialised to true, so an Inertia partial reload (a filter
    // change, which keeps the component mounted) does NOT re-open it — only
    // a real navigation to the tab does.
    const [showTestNotice, setShowTestNotice] = useState(true);
    const filterReset = useFilterReset(filters, reload);
    const hasGroup = filters.groupFilter !== '';
    const exportUrl = `/backoffice/seances/absence-par-groupe/export?${new URLSearchParams(filters).toString()}`;

    return (
        <BackofficeLayout
            title="Absence par groupe"
            breadcrumbs={[
                { label: 'Tableau de bord', href: '/backoffice/dashboard' },
                { label: 'Gestion des séances', href: '/backoffice/seances' },
                { label: 'Absence par groupe' },
            ]}
        >
            <Modal
                show={showTestNotice}
                title="Fonctionnalité en cours de test"
                onClose={() => setShowTestNotice(false)}
                footer={
                    <button type="button" className="btn btn-primary" onClick={() => setShowTestNotice(false)}>
                        J'ai compris
                    </button>
                }
            >
                <div className="text-center mb-3">
                    <span className="avatar avatar-xl bg-warning-transparent text-warning rounded-circle">
                        <i className="ti ti-flask fs-24" />
                    </span>
                </div>
                <p className="mb-0 text-center">
                    L'onglet <strong>« Absence par groupe »</strong> est encore en <strong>phase de test</strong>.
                </p>
            </Modal>

            <ul className="nav nav-tabs mb-3" role="tablist">
                <li className="nav-item" role="presentation">
                    <Link href="/backoffice/seances" className="nav-link fw-medium" role="tab" aria-selected="false">
                        <i className="ti ti-calendar-check me-2" />
                        Séances
                    </Link>
                </li>
                <li className="nav-item" role="presentation">
                    <Link
                        href="/backoffice/seances/saisir-absence"
                        className="nav-link fw-medium"
                        role="tab"
                        aria-selected="false"
                    >
                        <i className="ti ti-checklist me-2" />
                        Saisir l'absence
                    </Link>
                </li>
                <li className="nav-item" role="presentation">
                    <button type="button" className="nav-link active fw-medium" role="tab" aria-selected="true">
                        <i className="ti ti-table me-2" />
                        Absence par groupe
                    </button>
                </li>
            </ul>

            {/* Permanent reminder once the modal is closed — the popup is
                seen on arrival, this line keeps the caveat on screen while
                the user actually reads the matrix. */}
            <div className="alert alert-warning d-flex align-items-center gap-2 mb-3" role="status">
                <i className="ti ti-flask fs-18" />
                <span>
                    Fonctionnalité en cours de test — une case vide signifie « séance non pointée », pas une absence.
                </span>
            </div>

            <Card bodyClassName="p-0 py-3">
                <div className="px-3 pt-2">
                    <TableToolbar
                        onReset={filterReset.reset}
                        resetActive={filterReset.active}
                        actions={
                            <a
                                href={exportUrl}
                                className={`btn btn-success d-flex align-items-center${hasGroup ? '' : ' disabled'}`}
                                aria-disabled={!hasGroup}
                                tabIndex={hasGroup ? undefined : -1}
                            >
                                <i className="ti ti-file-spreadsheet me-2" />
                                Exporter
                            </a>
                        }
                    >
                        <div style={{ width: 260 }}>
                            <label className="form-label" htmlFor="abs-f-groupe">
                                Groupe <span className="text-danger">*</span>
                            </label>
                            <SelectField
                                id="abs-f-groupe"
                                options={groupOptions}
                                placeholder="Choisir un groupe"
                                value={filters.groupFilter}
                                onChange={(event) => reload({ groupFilter: event.target.value })}
                            />
                        </div>
                        <div style={{ width: 190 }}>
                            <label className="form-label" htmlFor="abs-f-du">
                                Date de début
                            </label>
                            <DateField
                                id="abs-f-du"
                                value={filters.dateFrom}
                                onChange={(event) => reload({ dateFrom: event.target.value })}
                            />
                        </div>
                        <div style={{ width: 190 }}>
                            <label className="form-label" htmlFor="abs-f-au">
                                Date de fin
                            </label>
                            <DateField
                                id="abs-f-au"
                                value={filters.dateTo}
                                onChange={(event) => reload({ dateTo: event.target.value })}
                            />
                        </div>
                        <div style={{ width: 200 }}>
                            <label className="form-label" htmlFor="abs-f-statut">
                                Statut
                            </label>
                            <SelectField
                                id="abs-f-statut"
                                options={statuts.map((statut) => ({ value: statut, label: statut }))}
                                placeholder="Choisir un statut"
                                value={filters.statutFilter}
                                onChange={(event) => reload({ statutFilter: event.target.value })}
                            />
                        </div>
                    </TableToolbar>
                </div>

                {!hasGroup && (
                    <div className="text-center py-5">
                        <i className="ti ti-table fs-32 text-muted d-block mb-2" />
                        <p className="mb-0 text-muted">Choisissez un groupe pour afficher la matrice des présences.</p>
                    </div>
                )}

                {hasGroup && matrice.seances.length === 0 && (
                    <div className="text-center py-5">
                        <i className="ti ti-calendar-off fs-32 text-muted d-block mb-2" />
                        <p className="mb-0 text-muted">Aucune séance pour ce groupe sur la période choisie.</p>
                    </div>
                )}

                {hasGroup && matrice.seances.length > 0 && (
                    <>
                        <div className="px-3 pb-3 d-flex flex-wrap gap-3 align-items-center">
                            <span className="badge bg-outline-info">
                                {matrice.totals.etudiants} étudiant{matrice.totals.etudiants > 1 ? 's' : ''}
                            </span>
                            <span className="badge bg-outline-info">
                                {matrice.seances.length} séance{matrice.seances.length > 1 ? 's' : ''}
                            </span>
                            <span className="badge bg-outline-success">{matrice.totals.presents} présences</span>
                            <span className="badge bg-outline-danger">{matrice.totals.absents} absences</span>
                        </div>

                        <div className="table-responsive gls-absence-matrix">
                            <table className="table table-bordered mb-0">
                                <thead className="thead-light">
                                    <tr>
                                        <th className="gls-absence-student">Étudiant</th>
                                        {matrice.seances.map((seance) => (
                                            <th
                                                key={seance.id}
                                                className="text-center"
                                                title={`${formatDate(seance.date)}${
                                                    seance.heureDebut ? ` — ${seance.heureDebut}` : ''
                                                } (${seance.statut})`}
                                            >
                                                {seance.numero}
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {matrice.students.map((student) => (
                                        <tr key={student.id} className={student.actif ? '' : 'gls-absence-inactif'}>
                                            <td className="gls-absence-student">
                                                <Link
                                                    href={`/backoffice/students/${student.id}`}
                                                    className="d-flex align-items-center gap-2 text-reset"
                                                >
                                                    <span className="avatar avatar-sm">
                                                        {student.photoUrl ? (
                                                            <img
                                                                src={student.photoUrl}
                                                                alt=""
                                                                className="img-fluid rounded-circle"
                                                            />
                                                        ) : (
                                                            <i className="ti ti-user" />
                                                        )}
                                                    </span>
                                                    <span>
                                                        {student.prenom} {student.nom}
                                                    </span>
                                                </Link>
                                            </td>
                                            {matrice.seances.map((seance) => {
                                                const cell = student.cells[String(seance.id)];

                                                return (
                                                    <td
                                                        key={seance.id}
                                                        className={`text-center gls-absence-cell${
                                                            cell
                                                                ? cell.lettre === 'P'
                                                                    ? ' gls-absence-present'
                                                                    : ' gls-absence-absent'
                                                                : ' gls-absence-vide'
                                                        }`}
                                                        title={
                                                            cell
                                                                ? `${formatDate(seance.date)} — ${cell.statut}${
                                                                      cell.note ? ` (${cell.note})` : ''
                                                                  }`
                                                                : `${formatDate(seance.date)} — non pointé`
                                                        }
                                                    >
                                                        {cell?.lettre ?? ''}
                                                    </td>
                                                );
                                            })}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </>
                )}
            </Card>
        </BackofficeLayout>
    );
}
