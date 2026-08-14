import BackofficeLayout from '@/Layouts/BackofficeLayout';
import PageTabs from '@/Components/Navigation/PageTabs';
import { GROUPS_TABS } from '@/Config/pageTabs';
import Card from '@/Components/Shared/Card';
import RelatedRecordsTable from '@/Components/Details/RelatedRecordsTable';
import StatusBadge from '@/Components/Details/StatusBadge';
import Pagination from '@/Components/Tables/Pagination';
import type { GroupsHistoriqueRow, PaginatedData } from '@/Types';

interface GroupsHistoriqueIndexProps {
    historiques: PaginatedData<GroupsHistoriqueRow>;
}

/**
 * Replaces resources/views/backoffice/groups-historique/index.blade.php —
 * same columns, same order, same empty state, same pagination. Read-only:
 * rows only ever come from Group::archiverCommeTermine().
 */
export default function GroupsHistoriqueIndex({ historiques }: GroupsHistoriqueIndexProps) {
    return (
        <BackofficeLayout
            title="Historique des groupes"
            breadcrumbs={[
                { label: 'Tableau de bord', href: '/backoffice/dashboard' },
                { label: 'Groupes', href: '/backoffice/groups' },
                { label: 'Historique' },
            ]}
        >
            <PageTabs tabs={GROUPS_TABS} />

            <Card title="Groupes archivés">
                <RelatedRecordsTable
                    isEmpty={historiques.data.length === 0}
                    emptyTitle="Aucun groupe archivé"
                    emptyIcon="fa fa-clock-rotate-left"
                    head={
                        <tr>
                            <th>Nom</th>
                            <th>Niveau</th>
                            <th>Enseignant</th>
                            <th>Centre</th>
                            <th>Année scolaire</th>
                            <th>Étudiants</th>
                            <th>Période</th>
                            <th>Archivé le</th>
                            <th>Archivé par</th>
                        </tr>
                    }
                >
                    {historiques.data.map((row) => (
                        <tr key={row.id}>
                            <td className="fw-medium">
                                {row.groupShowUrl ? <a href={row.groupShowUrl}>{row.nom}</a> : row.nom}
                            </td>
                            <td>
                                <StatusBadge label={row.niveau} />
                            </td>
                            <td>{row.enseignant ?? '—'}</td>
                            <td>{row.centre ?? '—'}</td>
                            <td>{row.anneeScolaire ?? '—'}</td>
                            <td>
                                <StatusBadge label={String(row.nombreEtudiants)} variant="secondary" />
                            </td>
                            <td>
                                {row.dateDebutFormation ?? '—'} → {row.dateFinFormation ?? '—'}
                            </td>
                            <td>{row.archivedAt ?? '—'}</td>
                            <td>{row.archivedBy ?? '—'}</td>
                        </tr>
                    ))}
                </RelatedRecordsTable>
                <Pagination paginator={historiques} showJumpToPage />
            </Card>
        </BackofficeLayout>
    );
}
