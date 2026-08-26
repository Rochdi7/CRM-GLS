import { Link } from '@inertiajs/react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import ImportRowReasonTable from '@/Components/Import/ImportRowReasonTable';
import type { PaginatedData } from '@/Types';
import type { ImportBatch, ImportRow, ImportStatusCounts } from '@/Types/import';

interface EncaissementImportResultProps {
    batch: ImportBatch;
    failedRows: PaginatedData<ImportRow>;
    skippedRows: PaginatedData<ImportRow>;
    unresolvedRows: PaginatedData<ImportRow>;
    statusCounts: ImportStatusCounts;
    excelTotal: string;
    importedTotal: string;
}

export default function EncaissementImportResult({
    batch,
    failedRows,
    skippedRows,
    unresolvedRows,
    excelTotal,
    importedTotal,
}: EncaissementImportResultProps) {
    const difference = (Number(excelTotal) - Number(importedTotal)).toFixed(2);

    return (
        <BackofficeLayout
            title="Résultat de l'import — Encaissements"
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

                <div className="row mb-4">
                    <div className="col-md-4">
                        <div className="text-muted fs-13 mb-1">Total Excel (toutes lignes)</div>
                        <div className="fs-20 fw-semibold">{excelTotal} DH</div>
                    </div>
                    <div className="col-md-4">
                        <div className="text-muted fs-13 mb-1">Total importé</div>
                        <div className="fs-20 fw-semibold">{importedTotal} DH</div>
                    </div>
                    <div className="col-md-4">
                        <div className="text-muted fs-13 mb-1">Différence</div>
                        <div className="fs-20 fw-semibold">{difference} DH</div>
                    </div>
                </div>

                <ImportRowReasonTable
                    title="Lignes en échec"
                    hint="Ces lignes ont été tentées mais refusées à l'écriture. Corrigez la cause puis relancez-les depuis l'aperçu."
                    rows={failedRows}
                />

                <ImportRowReasonTable
                    title="Lignes ignorées"
                    hint="Ces lignes n'ont volontairement pas été écrites — le plus souvent parce que les encaissements existaient déjà, ou parce que la même ligne apparaît deux fois dans le fichier. Rien n'a été perdu."
                    rows={skippedRows}
                />

                <ImportRowReasonTable
                    title="Lignes non résolues"
                    hint="Ces lignes n'ont pas pu être rattachées (étudiant, inscription ou frais introuvable) ou comportaient une cellule illisible. Elles sont conservées avec leur motif : corrigez la donnée manquante, puis relancez l'analyse du fichier — les encaissements déjà importés seront ignorés."
                    rows={unresolvedRows}
                />

                <Link href="/backoffice/import/encaissements" className="btn btn-primary mt-3">
                    Nouvel import
                </Link>
            </Card>
        </BackofficeLayout>
    );
}
