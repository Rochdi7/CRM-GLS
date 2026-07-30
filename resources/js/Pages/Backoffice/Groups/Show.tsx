import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import DetailRow from '@/Components/Details/DetailRow';
import StatusBadge from '@/Components/Details/StatusBadge';
import RelatedRecordsTable from '@/Components/Details/RelatedRecordsTable';
import type { GroupDetails } from '@/Types';

interface GroupShowProps {
    group: GroupDetails;
}

/**
 * Replaces resources/views/backoffice/groups/show.blade.php exactly —
 * identity card, assigned-fees card, enrolled-students table. The "End
 * training" (Fin de formation) archive action is preserved as a PLAIN HTML
 * form posting to the existing, unconverted backoffice.groups.archive
 * route — Phase 5 migrates only the read-only show page, not this
 * mutation (docs/phase-5-read-pages-inventory.md).
 */
export default function GroupShow({ group }: GroupShowProps) {
    function getCsrfToken(): string {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
    }

    function handleArchiveSubmit(event: React.FormEvent<HTMLFormElement>) {
        if (!window.confirm('Marquer ce groupe comme terminé (Fin de formation) ? Cette action est irréversible.')) {
            event.preventDefault();
        }
    }

    return (
        <BackofficeLayout
            title={group.nom}
            breadcrumbs={[
                { label: 'Tableau de bord', href: '/backoffice/dashboard' },
                { label: 'Groupes', href: '/backoffice/groups' },
                { label: group.nom },
            ]}
            actions={
                group.canArchive ? (
                    <form method="POST" action={group.archiveUrl} onSubmit={handleArchiveSubmit}>
                        <input type="hidden" name="_token" value={getCsrfToken()} />
                        <button type="submit" className="btn btn-outline-secondary d-flex align-items-center">
                            <i className="ti ti-archive me-2" />
                            Terminer la formation
                        </button>
                    </form>
                ) : undefined
            }
        >
            <div className="row">
                <div className="col-xl-4">
                    <Card title="Groupe">
                        <DetailRow label="Niveau" value={group.niveau} />
                        <DetailRow label="Enseignant" value={group.enseignant} />
                        <DetailRow label="Centre" value={group.centre} />
                        <DetailRow label="Année scolaire" value={group.anneeScolaire} />
                        <DetailRow label="Date de début" value={group.dateDebutFormation} />
                        <DetailRow label="Date de fin" value={group.dateFinFormation} />
                        <div className="d-flex justify-content-between">
                            <span className="text-muted">Statut</span>
                            <StatusBadge label={group.statut} />
                        </div>
                    </Card>

                    <Card title="Frais assignés">
                        {group.fees.length === 0 ? (
                            <p className="text-muted mb-0">Aucun frais assigné.</p>
                        ) : (
                            group.fees.map((fee) => (
                                <div className="d-flex justify-content-between mb-2" key={fee.nom}>
                                    <span>
                                        {fee.nom}
                                        {fee.classification && (
                                            <span className="badge badge-soft-info ms-1">{fee.classification}</span>
                                        )}
                                    </span>
                                    <span className="fw-medium">{Number(fee.montant).toFixed(2)} DH</span>
                                </div>
                            ))
                        )}
                    </Card>
                </div>

                <div className="col-xl-8">
                    <Card
                        title="Étudiants"
                        tools={<span className="badge badge-soft-secondary">{group.inscriptions.length}</span>}
                    >
                        <RelatedRecordsTable
                            isEmpty={group.inscriptions.length === 0}
                            emptyTitle="Aucun étudiant inscrit"
                            emptyIcon="ti ti-users"
                            head={
                                <tr>
                                    <th>Référence</th>
                                    <th>Étudiant</th>
                                    <th>Date</th>
                                    <th>Statut</th>
                                </tr>
                            }
                        >
                            {group.inscriptions.map((insc) => (
                                <tr key={insc.reference}>
                                    <td>
                                        <code>{insc.reference}</code>
                                    </td>
                                    <td>
                                        {insc.studentShowUrl ? (
                                            <a href={insc.studentShowUrl}>{insc.student}</a>
                                        ) : (
                                            (insc.student ?? '—')
                                        )}
                                    </td>
                                    <td>{insc.date ?? '—'}</td>
                                    <td>
                                        <StatusBadge label={insc.statut} />
                                    </td>
                                </tr>
                            ))}
                        </RelatedRecordsTable>
                    </Card>
                </div>
            </div>
        </BackofficeLayout>
    );
}
