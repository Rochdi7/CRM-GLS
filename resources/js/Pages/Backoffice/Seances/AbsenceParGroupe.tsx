import { Link, router } from '@inertiajs/react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
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
    /** False when the séance carries NO roll-call at all — its whole column is greyed. */
    saisie: boolean;
}

interface MatriceCell {
    statut: string;
    lettre: 'P' | 'A';
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
 * Column tooltip, in the reference CRM's wording:
 * « 01/07/2026 de 19:00 à 21:30 ». The hours are dropped when the séance has
 * none, and a séance nobody pointed says so — that is what its grey column
 * means.
 */
function seanceTitle(seance: MatriceSeance): string {
    const quand =
        seance.heureDebut && seance.heureFin
            ? `${formatDate(seance.date)} de ${seance.heureDebut} à ${seance.heureFin}`
            : seance.heureDebut
              ? `${formatDate(seance.date)} à ${seance.heureDebut}`
              : formatDate(seance.date);

    return seance.saisie ? quand : `${quand} — absence non saisie`;
}

/**
 * Name-cell fill per inscription statut — the SAME split « Détails paiement »
 * uses (Components/Groups/GroupPaymentMatrix.tsx ROW_FILL): grey once the
 * enrollment has moved on, red only for a cancellation, nothing while it is
 * Active. Keyed on the statut rather than on the `actif` boolean so the two
 * screens can never drift apart; an unknown statut falls back to grey (closed
 * but not cancelled), never to red.
 */
const ROW_CLASS: Record<string, string> = {
    Active: '',
    Changement: 'gls-absence-clos',
    Expirée: 'gls-absence-clos',
    Archivée: 'gls-absence-clos',
    Annulée: 'gls-absence-annulee',
};

function rowClass(student: MatriceStudent): string {
    if (student.actif) {
        return '';
    }

    return ROW_CLASS[student.inscriptionStatut] ?? 'gls-absence-clos';
}

/**
 * « Absence par groupe » — the presence MATRIX of one group: students in
 * rows, the séances of the selected window in columns, one P/A cell each.
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
                                                className={`text-center${seance.saisie ? '' : ' gls-absence-non-saisie'}`}
                                                title={seanceTitle(seance)}
                                            >
                                                {seance.numero}
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {matrice.students.map((student) => (
                                        <tr key={student.id} className={rowClass(student)}>
                                            {/* The statut is what the row's
                                                colour means, so it is spelled
                                                out on hover — a grey name is
                                                otherwise unexplained. */}
                                            <td
                                                className="gls-absence-student"
                                                title={student.actif ? undefined : `Inscription ${student.inscriptionStatut}`}
                                            >
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

                                                // A séance nobody pointed greys
                                                // its WHOLE column (header
                                                // included), so it reads as one
                                                // missing day rather than as a
                                                // column of blanks that each
                                                // look like an individual
                                                // oversight.
                                                return (
                                                    <td
                                                        key={seance.id}
                                                        className={`text-center gls-absence-cell${
                                                            !seance.saisie
                                                                ? ' gls-absence-non-saisie'
                                                                : cell
                                                                  ? cell.lettre === 'P'
                                                                      ? ' gls-absence-present'
                                                                      : ' gls-absence-absent'
                                                                  : ' gls-absence-vide'
                                                        }`}
                                                        title={
                                                            !seance.saisie
                                                                ? seanceTitle(seance)
                                                                : cell
                                                                  ? `${formatDate(seance.date)} — ${cell.statut}${
                                                                        cell.note ? ` (${cell.note})` : ''
                                                                    }`
                                                                  : `${formatDate(seance.date)} — non pointé`
                                                        }
                                                    >
                                                        {seance.saisie ? (cell?.lettre ?? '') : ''}
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
