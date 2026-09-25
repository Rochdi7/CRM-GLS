import { Link, router } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import EmptyState from '@/Components/Shared/EmptyState';
import FilterTextInput from '@/Components/Tables/FilterTextInput';
import Pagination from '@/Components/Tables/Pagination';
import Modal from '@/Components/Modals/Modal';
import StatusBadge from '@/Components/Details/StatusBadge';
import { useInertiaLoading } from '@/Hooks/useInertiaLoading';
import { t } from '@/Lib/i18n';
import type { GroupRow, GroupStudentSegmentRow, MesGroupesPageProps } from '@/Types';

const STATUT_TABS: Array<{ key: string; icon: string; label: string }> = [
    { key: 'En formation', icon: 'ti-school', label: 'In training' },
    { key: 'En inscription', icon: 'ti-folder', label: 'In registration' },
    { key: 'Fin de formation', icon: 'ti-history', label: 'History' },
];

function statutVariant(statut: string): 'success' | 'secondary' | 'danger' | 'warning' {
    if (statut === 'En formation') return 'success';
    if (statut === 'Fin de formation') return 'secondary';
    if (statut === 'Annulée') return 'danger';
    return 'warning';
}

function formatDate(iso: string | null): string {
    if (!iso) return '—';
    const [y, m, d] = iso.split('-');
    return `${d}/${m}/${y}`;
}

/**
 * « Mes groupes » — the Groups page as an ENSEIGNANT sees it (portée
 * `groups.view-own`, PorteeEnseignant). Read-only by design: no create, edit,
 * archive or payment action exists here, and the server already sent only
 * this teacher's groups, without any amount. Each card shows the group's open
 * timetable; the eye opens the roster in a modal (same JSON endpoint as the
 * admin list's « Statistique » badges, re-authorized by GroupPolicy@view).
 */
export default function MesGroupes({ groups, statutCounts, filters }: MesGroupesPageProps) {
    const isLoading = useInertiaLoading();
    const [rosterGroup, setRosterGroup] = useState<GroupRow | null>(null);
    const [roster, setRoster] = useState<GroupStudentSegmentRow[]>([]);
    const [rosterLoading, setRosterLoading] = useState(false);
    const [rosterSearch, setRosterSearch] = useState('');

    function reload(next: Partial<typeof filters>) {
        router.get(
            '/backoffice/groups',
            { ...filters, ...next, page: undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    async function openRoster(group: GroupRow) {
        setRosterGroup(group);
        setRoster([]);
        setRosterSearch('');
        setRosterLoading(true);
        try {
            const response = await fetch(`/backoffice/groups/${group.id}/students-by-segment?segment=active`);
            const data: { students: GroupStudentSegmentRow[] } = await response.json();
            setRoster(data.students);
        } finally {
            setRosterLoading(false);
        }
    }

    const rosterFiltered = useMemo(() => {
        const needle = rosterSearch.trim().toLowerCase();
        if (needle === '') return roster;
        return roster.filter((s) =>
            `${s.prenom} ${s.nom} ${s.reference} ${s.telephone ?? ''}`.toLowerCase().includes(needle),
        );
    }, [roster, rosterSearch]);

    return (
        <BackofficeLayout
            title={t('My groups')}
            breadcrumbs={[{ label: 'Tableau de bord', href: '/backoffice/dashboard' }, { label: t('My groups') }]}
        >
            <div className="card">
                <div className="card-body pb-1">
                    <div className="d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <ul className="nav nav-tabs nav-tabs-solid nav-tabs-rounded-fill mb-2" role="tablist">
                            {STATUT_TABS.map((tab) => (
                                <li className="me-2 mb-2" role="presentation" key={tab.key}>
                                    <button
                                        type="button"
                                        className={`nav-link rounded${filters.statutFilter === tab.key ? ' active' : ''}`}
                                        onClick={() => reload({ statutFilter: tab.key })}
                                    >
                                        <i className={`ti ${tab.icon} me-1`} />
                                        {t(tab.label)}
                                        <span
                                            className={`badge ${filters.statutFilter === tab.key ? 'bg-white text-dark' : 'badge-soft-secondary'} ms-1`}
                                        >
                                            {statutCounts[tab.key] ?? 0}
                                        </span>
                                    </button>
                                </li>
                            ))}
                        </ul>
                        <div className="mb-2" style={{ width: 260, maxWidth: '100%' }}>
                            <FilterTextInput
                                id="mes-groupes-recherche"
                                value={filters.search}
                                onChange={(value) => reload({ search: value })}
                                placeholder={t('Search a group')}
                            />
                        </div>
                    </div>
                </div>
            </div>

            {groups.data.length === 0 ? (
                <div className="card">
                    <div className="card-body">
                        <EmptyState title={t('No group')} icon="ti ti-users-group" />
                    </div>
                </div>
            ) : (
                <div className={`row${isLoading ? ' opacity-50' : ''}`}>
                    {groups.data.map((group) => (
                        <div className="col-xxl-4 col-md-6 d-flex" key={group.id}>
                            <div className="card flex-fill">
                                <div className="card-header d-flex align-items-start justify-content-between gap-2">
                                    <div className="d-flex align-items-center overflow-hidden">
                                        <span className="avatar avatar-lg rounded bg-primary-transparent text-primary me-2 flex-shrink-0 d-inline-flex align-items-center justify-content-center">
                                            <i className="ti ti-users-group fs-24" />
                                        </span>
                                        <div className="overflow-hidden">
                                            <h5 className="mb-1 text-truncate text-uppercase">{group.nom}</h5>
                                            <span className="badge badge-soft-info">{group.niveau}</span>
                                        </div>
                                    </div>
                                    <StatusBadge label={group.statut} variant={statutVariant(group.statut)} dot />
                                </div>

                                <div className="card-body d-flex flex-column">
                                    <div className="d-flex align-items-center justify-content-between bg-light-300 rounded p-2 mb-3">
                                        <div className="text-center flex-fill">
                                            <h4 className="mb-0 text-success">{group.inscriptionsActivesCount}</h4>
                                            <span className="fs-13 text-muted">{t('Active students')}</span>
                                        </div>
                                        <div className="text-center flex-fill border-start">
                                            <h6 className="mb-0">{formatDate(group.dateDebutFormation)}</h6>
                                            <span className="fs-13 text-muted">{t('Start date')}</span>
                                        </div>
                                        <div className="text-center flex-fill border-start">
                                            <h6 className="mb-0">{formatDate(group.dateFinFormation)}</h6>
                                            <span className="fs-13 text-muted">{t('End date')}</span>
                                        </div>
                                    </div>

                                    <p className="fs-13 fw-medium text-muted mb-2">
                                        <i className="ti ti-calendar-time me-1" />
                                        {t('Timetable')}
                                    </p>
                                    {group.emploiDuTemps && group.emploiDuTemps.length > 0 ? (
                                        <div className="d-flex flex-wrap gap-2 mb-3">
                                            {group.emploiDuTemps.map((slot, index) => (
                                                <span key={index} className="badge badge-soft-primary fs-13 fw-normal">
                                                    <span className="fw-semibold">{slot.jour}</span> {slot.heureDebut}–{slot.heureFin}
                                                    {slot.salle && <span className="ms-1 opacity-75">· {slot.salle}</span>}
                                                </span>
                                            ))}
                                        </div>
                                    ) : (
                                        <p className="fs-13 text-muted mb-3">{t('No schedule yet')}</p>
                                    )}

                                    {group.salle && (
                                        <p className="fs-13 mb-3">
                                            <i className="ti ti-door me-1 text-muted" />
                                            {t('Room')} : <span className="fw-medium">{group.salle}</span>
                                        </p>
                                    )}

                                    <div className="d-flex flex-wrap gap-2 mt-auto pt-2 border-top">
                                        <button
                                            type="button"
                                            className="btn btn-primary btn-sm d-inline-flex align-items-center"
                                            onClick={() => openRoster(group)}
                                        >
                                            <i className="ti ti-eye me-1" />
                                            {t('View students')}
                                        </button>
                                        <Link
                                            href={`/backoffice/seances?groupFilter=${group.id}`}
                                            className="btn btn-light btn-sm d-inline-flex align-items-center"
                                        >
                                            <i className="ti ti-checklist me-1" />
                                            {t('Sessions')}
                                        </Link>
                                        <Link
                                            href={`/backoffice/seances/absence-par-groupe?groupFilter=${group.id}`}
                                            className="btn btn-light btn-sm d-inline-flex align-items-center"
                                        >
                                            <i className="ti ti-user-x me-1" />
                                            {t('Absences')}
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            )}

            <Pagination paginator={groups} />

            <Modal
                show={rosterGroup !== null}
                title={rosterGroup ? `${t('Students')} — ${rosterGroup.nom}` : ''}
                onClose={() => setRosterGroup(null)}
                size="lg"
            >
                <div className="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <span className="badge badge-soft-success fs-13">
                        {t('Active students')} : {roster.length}
                    </span>
                    <input
                        type="search"
                        className="form-control"
                        style={{ width: 240, maxWidth: '100%' }}
                        placeholder={t('Search a student')}
                        value={rosterSearch}
                        onChange={(event) => setRosterSearch(event.target.value)}
                    />
                </div>

                {rosterLoading ? (
                    <div className="text-center py-4 text-muted">
                        <span className="spinner-border spinner-border-sm me-2" />
                        {t('Loading…')}
                    </div>
                ) : rosterFiltered.length === 0 ? (
                    <EmptyState title={t('No student')} icon="ti ti-school" />
                ) : (
                    <div className="table-responsive">
                        <table className="table table-nowrap mb-0">
                            <thead className="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>{t('Full name')}</th>
                                    <th>{t('Reference')}</th>
                                    <th>{t('Phone')}</th>
                                    <th>{t('Registered on')}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {rosterFiltered.map((student, index) => (
                                    <tr key={student.reference}>
                                        <td className="text-muted">{index + 1}</td>
                                        <td>
                                            <div className="d-flex align-items-center">
                                                <span className="avatar avatar-sm rounded-circle bg-primary-transparent me-2 d-inline-flex align-items-center justify-content-center">
                                                    <span className="fw-bold text-primary">{student.prenom.charAt(0).toUpperCase()}</span>
                                                </span>
                                                <span className="fw-medium">
                                                    {student.prenom} {student.nom}
                                                </span>
                                            </div>
                                        </td>
                                        <td className="text-normal-case">
                                            <code>{student.reference}</code>
                                        </td>
                                        <td>{student.telephone ?? '—'}</td>
                                        <td>{student.dateInscription ?? '—'}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </Modal>
        </BackofficeLayout>
    );
}
