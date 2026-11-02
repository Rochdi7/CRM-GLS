import { useState } from 'react';
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

/**
 * Pastilles de compteurs à afficher : « Justifié » n'est montré que s'il
 * compte au moins une ligne (02/11/2026 — à 0 il n'apporte rien), pour que
 * les pastilles visibles s'additionnent toujours au total des appels.
 */
export function compteursVisibles(compteurs: Record<string, number>): Array<[string, number]> {
    return Object.entries(compteurs).filter(([statut, n]) => statut !== 'Justifié' || n > 0);
}

interface AbsencesPanelProps {
    data: AbsencesEtudiant;
    /** Phrase de portée affichée sous le titre (« tous groupes » / « groupe X »). */
    scope: string;
}

/**
 * Onglet « Présences » partagé par la fiche étudiant et la fiche inscription
 * (25/09/2026). Les données viennent du serveur (GetAbsencesEtudiant) : la
 * page ne filtre ni ne recompte rien — « Tous les appels » et « Absences »
 * sont deux listes servies telles quelles (02/11/2026 : les présences n'étaient
 * que comptées, jamais listées).
 */
export default function AbsencesPanel({ data, scope }: AbsencesPanelProps) {
    const [vue, setVue] = useState<'appels' | 'absences'>('appels');
    const lignes = vue === 'appels' ? data.appels : data.absences;

    return (
        <Card
            title="Présences et absences"
            tools={
                <div className="btn-group btn-group-sm" role="group">
                    <button
                        type="button"
                        className={`btn ${vue === 'appels' ? 'btn-primary' : 'btn-outline-primary'}`}
                        onClick={() => setVue('appels')}
                    >
                        Tous les appels ({data.appels.length})
                    </button>
                    <button
                        type="button"
                        className={`btn ${vue === 'absences' ? 'btn-danger' : 'btn-outline-danger'}`}
                        onClick={() => setVue('absences')}
                    >
                        Absences ({data.absences.length})
                    </button>
                </div>
            }
        >
            <p className="text-muted fs-13 mb-3">{scope}</p>

            <div className="d-flex flex-wrap gap-2 mb-3">
                <span className="badge badge-soft-dark fs-13">{data.total} appel(s)</span>
                {compteursVisibles(data.compteurs).map(([statut, n]) => (
                    <span key={statut} className={`badge badge-soft-${presenceVariant(statut)} fs-13`}>
                        {statut} : {n}
                    </span>
                ))}
            </div>

            <RelatedRecordsTable
                isEmpty={lignes.length === 0}
                emptyTitle={vue === 'appels' ? 'Aucun appel' : 'Aucune absence'}
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
                {lignes.map((a) => (
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
