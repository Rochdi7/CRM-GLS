import { Link } from '@inertiajs/react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import DataTable from '@/Components/Tables/DataTable';
import type { ImportBatch } from '@/Types/import';

interface ImportIndexProps {
    recentBatches: ImportBatch[];
    /** False only on "Tous les centres" — gates the redundant Centre column. */
    centerLocked: boolean;
}

const MODULE_LABELS: Record<ImportBatch['module'], string> = {
    students: 'Étudiants',
    inscriptions: 'Inscriptions',
    encaissements: 'Encaissements',
    presences: 'Présences & séances',
};

/**
 * Présences is deliberately described as its own step rather than "les
 * présences depuis l'ancien CRM": it is the only module that also creates
 * the séances, and it must run last (the roll call needs the élèves and the
 * groupes to exist first).
 */
const MODULE_HINTS: Record<ImportBatch['module'], string> = {
    students: "Importer les étudiants depuis un export de l'ancien CRM.",
    inscriptions: "Importer les inscriptions depuis un export de l'ancien CRM.",
    encaissements: "Importer les encaissements depuis un export de l'ancien CRM.",
    presences: "Importer le registre des présences : crée les séances manquantes et l'appel (présent / absent).",
};

const RESULT_ROUTES: Record<ImportBatch['module'], string> = {
    students: '/backoffice/import/students',
    inscriptions: '/backoffice/import/inscriptions',
    encaissements: '/backoffice/import/encaissements',
    presences: '/backoffice/import/presences',
};

function statusLabel(status: ImportBatch['status']): string {
    switch (status) {
        case 'analyzed':
            return 'Analysé';
        case 'committing':
            return 'Insertion en cours';
        case 'committed':
            return 'Terminé';
        case 'committed_with_errors':
            return 'Terminé avec erreurs';
    }
}

export default function ImportIndex({ recentBatches, centerLocked }: ImportIndexProps) {
    return (
        <BackofficeLayout
            title="Import de données"
            breadcrumbs={[
                { label: 'Tableau de bord', href: '/backoffice/dashboard' },
                { label: 'Import de données' },
            ]}
        >
            <div className="row mb-4">
                {(['students', 'inscriptions', 'encaissements', 'presences'] as const).map((module) => (
                    <div className="col-md-3" key={module}>
                        <Card title={MODULE_LABELS[module]}>
                            <p className="text-muted">{MODULE_HINTS[module]}</p>
                            <Link href={`${RESULT_ROUTES[module]}`} className="btn btn-primary">
                                Importer
                            </Link>
                        </Card>
                    </div>
                ))}
            </div>

            <Card title="Imports récents">
                <DataTable
                    head={
                        <tr>
                            <th>Module</th>
                            <th>Fichier</th>
                            {!centerLocked && <th>Centre</th>}
                            <th>Année scolaire</th>
                            <th>Statut</th>
                            <th>Insérées</th>
                            <th>Erreurs</th>
                            <th />
                        </tr>
                    }
                >
                    {recentBatches.map((batch) => (
                        <tr key={batch.id}>
                            <td>{MODULE_LABELS[batch.module]}</td>
                            <td>{batch.original_filename}</td>
                            {!centerLocked && <td>{batch.etablissement?.nom_centre ?? '—'}</td>}
                            <td>{batch.annee_scolaire?.nom ?? '—'}</td>
                            <td>{statusLabel(batch.status)}</td>
                            <td>{batch.inserted_rows}</td>
                            <td>{batch.error_rows}</td>
                            <td>
                                <Link href={`${RESULT_ROUTES[batch.module]}/${batch.id}/result`} className="btn btn-sm btn-outline-primary">
                                    Voir
                                </Link>
                            </td>
                        </tr>
                    ))}
                    {recentBatches.length === 0 && (
                        <tr>
                            <td colSpan={centerLocked ? 7 : 8} className="text-center text-muted">
                                Aucun import pour le moment.
                            </td>
                        </tr>
                    )}
                </DataTable>
            </Card>
        </BackofficeLayout>
    );
}
