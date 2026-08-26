import { Link } from '@inertiajs/react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import ImportRowReasonTable from '@/Components/Import/ImportRowReasonTable';
import type { PaginatedData } from '@/Types';
import type { ImportBatch, ImportRow, ImportStatusCounts } from '@/Types/import';

interface StudentImportResultProps {
    batch: ImportBatch;
    failedRows: PaginatedData<ImportRow>;
    skippedRows: PaginatedData<ImportRow>;
    unresolvedRows: PaginatedData<ImportRow>;
    statusCounts: ImportStatusCounts;
}

export default function StudentImportResult({ batch, failedRows, skippedRows, unresolvedRows }: StudentImportResultProps) {
    return (
        <BackofficeLayout
            title="Résultat de l'import — Students"
            breadcrumbs={[
                { label: 'Tableau de bord', href: '/backoffice/dashboard' },
                { label: 'Import de données', href: '/backoffice/import' },
                { label: 'Résultat' },
            ]}
        >
            <Card title={`${batch.original_filename} — ${batch.etablissement?.nom_centre ?? ''} / ${batch.annee_scolaire?.nom ?? ''}`}>
                <div className="row mb-4">
                    <div className="col-md-3">
                        <div className="text-muted fs-13 mb-1">Lignes dans le fichier</div>
                        <div className="fs-24 fw-semibold">{batch.total_rows}</div>
                    </div>
                    <div className="col-md-3">
                        <div className="text-muted fs-13 mb-1">Insérées</div>
                        <div className="fs-24 fw-semibold text-success">{batch.inserted_rows}</div>
                    </div>
                    <div className="col-md-3">
                        <div className="text-muted fs-13 mb-1">Ignorées</div>
                        <div className="fs-24 fw-semibold">{batch.skipped_rows}</div>
                    </div>
                    <div className="col-md-3">
                        <div className="text-muted fs-13 mb-1">Erreurs</div>
                        <div className="fs-24 fw-semibold text-danger">{batch.error_rows}</div>
                    </div>
                </div>

                <ImportRowReasonTable
                    title="Lignes en échec"
                    hint="Ces lignes ont été tentées mais refusées à l'écriture. Corrigez la cause puis relancez-les depuis l'aperçu."
                    rows={failedRows}
                />

                <ImportRowReasonTable
                    title="Lignes ignorées"
                    hint="Ces lignes n'ont volontairement pas été écrites — le plus souvent parce que les étudiants existaient déjà, ou parce que la même ligne apparaît deux fois dans le fichier. Rien n'a été perdu."
                    rows={skippedRows}
                />

                <ImportRowReasonTable
                    title="Lignes non résolues"
                    hint="Ces lignes n'ont pas pu être rattachées ou comportaient une cellule illisible. Elles sont conservées avec leur motif : corrigez la donnée manquante, puis relancez l'analyse du fichier."
                    rows={unresolvedRows}
                />

                <Link href="/backoffice/import/students" className="btn btn-primary mt-3">
                    Nouvel import
                </Link>
            </Card>
        </BackofficeLayout>
    );
}
