import Card from '@/Components/Shared/Card';
import StatusBadge from '@/Components/Details/StatusBadge';
import RelatedRecordsTable from '@/Components/Details/RelatedRecordsTable';
import type { AbsencesEtudiant } from '@/Types';

/** Couleur d'une ligne d'appel — mêmes teintes partout (Présent vert, Absent rouge…). */
export function presenceVariant(statut: string): 'success' | 'danger' | 'warning' | 'info' | 'secondary' {
    if (statut === 'Présent') return 'success';
    if (statut === 'Absent') return 'danger';
    if (statut === 'Retard') return 'warning';
    if (statut === 'Justifié') return 'info';

    return 'secondary';
}

interface AbsencesPanelProps {
    data: AbsencesEtudiant;
    /** Phrase de portée affichée sous le titre (« tous groupes » / « groupe X »). */
    scope: string;
}

/**
 * Onglet « Absences » partagé par la fiche étudiant et la fiche inscription
 * (25/09/2026). Les données viennent du serveur (GetAbsencesEtudiant) : la
 * page ne filtre ni ne recompte rien.
 */
export default function AbsencesPanel({ data, scope }: AbsencesPanelProps) {
    return (
        <Card
            title="Absences"
            tools={<span className="badge badge-soft-danger">{data.absences.length}</span>}
        >
            <p className="text-muted fs-13 mb-3">{scope}</p>

            <div className="d-flex flex-wrap gap-2 mb-3">
                <span className="badge badge-soft-dark fs-13">{data.total} appel(s)</span>
                {Object.entries(data.compteurs).map(([statut, n]) => (
                    <span key={statut} className={`badge badge-soft-${presenceVariant(statut)} fs-13`}>
                        {statut} : {n}
                    </span>
                ))}
            </div>

            <RelatedRecordsTable
                isEmpty={data.absences.length === 0}
                emptyTitle="Aucune absence"
                emptyIcon="ti ti-calendar-check"
                head={
                    <tr>
                        <th>Date</th>
                        <th>Heure</th>
                        <th>Groupe</th>
                        <th>Enseignant</th>
                        <th>Statut</th>
                        <th>Note</th>
                    </tr>
                }
            >
                {data.absences.map((a) => (
                    <tr key={a.id}>
                        <td>
                            <a href={`/backoffice/seances/${a.seanceId}`}>{a.date}</a>
                        </td>
                        <td>{a.heure ?? '-'}</td>
                        <td>{a.groupe ?? '-'}</td>
                        <td>{a.enseignant ?? '-'}</td>
                        <td>
                            <StatusBadge label={a.statut} variant={presenceVariant(a.statut)} dot />
                        </td>
                        <td className="text-normal-case">{a.note ?? '-'}</td>
                    </tr>
                ))}
            </RelatedRecordsTable>
        </Card>
    );
}
