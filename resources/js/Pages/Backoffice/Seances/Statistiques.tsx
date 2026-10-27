import { Link, router } from '@inertiajs/react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import SelectField from '@/Components/Forms/SelectField';
import DateField from '@/Components/Forms/DateField';
import TableToolbar from '@/Components/Tables/TableToolbar';
import {
    PresenceParDateChart,
    PresenceParEtudiantChart,
    type PresenceParDate,
    type PresenceParEtudiant,
} from '@/Components/Attendance/PresenceCharts';
import { useFilterReset } from '@/Hooks/useFilterReset';
import { withGroupStatutTone } from '@/Lib/groupStatut';
import type { SelectOption } from '@/Types';

type Vue = 'presence' | 'suivi';

interface StatistiquesFilters {
    vue: Vue;
    groupFilter: string;
    studentFilter: string;
    dateFrom: string;
    dateTo: string;
}

interface StatistiquesProps {
    statistiques: {
        etudiants: SelectOption[];
        parDate: PresenceParDate[];
        parEtudiant: PresenceParEtudiant[];
        totals: { seances: number; presents: number; absents: number };
    };
    filters: StatistiquesFilters;
    defaultDates: { dateFrom: string; dateTo: string };
    groupOptions: (SelectOption & { statut: string })[];
}

const VUES: { key: Vue; label: string; icon: string }[] = [
    { key: 'presence', label: 'Statistiques de présence', icon: 'ti ti-chart-line' },
    { key: 'suivi', label: 'Suivi de la présence', icon: 'ti ti-chart-bar' },
];

/**
 * « Statistiques » tab of Gestion des séances (27/10/2026) — présences /
 * absences of ONE group over a date window (default: the last month),
 * charted per date (« Statistiques de présence ») or per student (« Suivi de
 * la présence »), optionally narrowed to one student. Every figure comes from
 * GetStatistiquesPresence, itself derived from the « Absence par groupe »
 * matrix, so both screens always agree. No « Retard » series: that statut was
 * removed from the system and its legacy rows count as présences.
 */
export default function Statistiques({ statistiques, filters, defaultDates, groupOptions }: StatistiquesProps) {
    function reload(changes: Partial<StatistiquesFilters>) {
        router.get(
            '/backoffice/seances/statistiques',
            { ...filters, ...changes },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    const filterReset = useFilterReset(filters, reload, { vue: filters.vue, ...defaultDates });
    const hasGroup = filters.groupFilter !== '';
    const { totals } = statistiques;
    const hasData =
        filters.vue === 'presence' ? statistiques.parDate.length > 0 : statistiques.parEtudiant.length > 0;
    const marques = totals.presents + totals.absents;

    return (
        <BackofficeLayout
            title="Gestion des séances"
            breadcrumbs={[
                { label: 'Tableau de bord', href: '/backoffice/dashboard' },
                { label: 'Gestion des séances', href: '/backoffice/seances' },
                { label: 'Statistiques' },
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
                    <Link
                        href="/backoffice/seances/absence-par-groupe"
                        className="nav-link fw-medium"
                        role="tab"
                        aria-selected="false"
                    >
                        <i className="ti ti-table me-2" />
                        Absence par groupe
                    </Link>
                </li>
                <li className="nav-item" role="presentation">
                    <button type="button" className="nav-link active fw-medium" role="tab" aria-selected="true">
                        <i className="ti ti-chart-bar me-2" />
                        Statistiques
                    </button>
                </li>
            </ul>

            <Card bodyClassName="p-0 py-3">
                <ul className="nav nav-tabs nav-tabs-bottom justify-content-center px-3 mb-3" role="tablist">
                    {VUES.map((vue) => (
                        <li className="nav-item" role="presentation" key={vue.key}>
                            <button
                                type="button"
                                className={`nav-link fw-medium${filters.vue === vue.key ? ' active' : ''}`}
                                role="tab"
                                aria-selected={filters.vue === vue.key}
                                onClick={() => filters.vue !== vue.key && reload({ vue: vue.key })}
                            >
                                <i className={`${vue.icon} me-2`} />
                                {vue.label}
                            </button>
                        </li>
                    ))}
                </ul>

                <div className="px-3">
                    <TableToolbar onReset={filterReset.reset} resetActive={filterReset.active}>
                        <div style={{ width: 260 }}>
                            <label className="form-label" htmlFor="stat-f-groupe">
                                Groupe <span className="text-danger">*</span>
                            </label>
                            <SelectField
                                id="stat-f-groupe"
                                // Annulée in red, Fin de formation in orange.
                                options={withGroupStatutTone(groupOptions)}
                                placeholder="Choisir un groupe"
                                value={filters.groupFilter}
                                // The chosen student belongs to the previous group's roll.
                                onChange={(event) => reload({ groupFilter: event.target.value, studentFilter: '' })}
                            />
                        </div>
                        <div style={{ width: 260 }}>
                            <label className="form-label" htmlFor="stat-f-etudiant">
                                Étudiant
                            </label>
                            <SelectField
                                id="stat-f-etudiant"
                                options={statistiques.etudiants}
                                placeholder={hasGroup ? 'Tous les étudiants' : "Choisir d'abord un groupe"}
                                disabled={!hasGroup}
                                value={filters.studentFilter}
                                onChange={(event) => reload({ studentFilter: event.target.value })}
                            />
                        </div>
                        <div style={{ width: 190 }}>
                            <label className="form-label" htmlFor="stat-f-du">
                                Date de début
                            </label>
                            <DateField
                                id="stat-f-du"
                                value={filters.dateFrom}
                                onChange={(event) => reload({ dateFrom: event.target.value })}
                            />
                        </div>
                        <div style={{ width: 190 }}>
                            <label className="form-label" htmlFor="stat-f-au">
                                Date de fin
                            </label>
                            <DateField
                                id="stat-f-au"
                                value={filters.dateTo}
                                onChange={(event) => reload({ dateTo: event.target.value })}
                            />
                        </div>
                    </TableToolbar>
                </div>

                {!hasGroup && (
                    <div className="text-center py-5">
                        <i className="ti ti-chart-bar fs-32 text-muted d-block mb-2" />
                        <p className="mb-0 text-muted">Choisissez un groupe pour afficher les statistiques de présence.</p>
                    </div>
                )}

                {hasGroup && !hasData && (
                    <div className="text-center py-5">
                        <i className="ti ti-calendar-off fs-32 text-muted d-block mb-2" />
                        <p className="mb-0 text-muted">Aucune présence saisie pour ce groupe sur la période choisie.</p>
                    </div>
                )}

                {hasGroup && hasData && (
                    <div className="px-3 pb-2">
                        <div className="d-flex flex-wrap gap-3 align-items-center mb-3">
                            <span className="badge bg-outline-info">
                                {totals.seances} séance{totals.seances > 1 ? 's' : ''}
                            </span>
                            <span className="badge bg-outline-success">
                                {totals.presents} présence{totals.presents > 1 ? 's' : ''}
                            </span>
                            <span className="badge bg-outline-danger">
                                {totals.absents} absence{totals.absents > 1 ? 's' : ''}
                            </span>
                            {marques > 0 && (
                                <span className="badge bg-outline-primary">
                                    Taux de présence {Math.round((totals.presents / marques) * 100)} %
                                </span>
                            )}
                        </div>

                        {filters.vue === 'presence' ? (
                            <PresenceParDateChart data={statistiques.parDate} />
                        ) : (
                            <PresenceParEtudiantChart data={statistiques.parEtudiant} />
                        )}
                    </div>
                )}
            </Card>
        </BackofficeLayout>
    );
}
