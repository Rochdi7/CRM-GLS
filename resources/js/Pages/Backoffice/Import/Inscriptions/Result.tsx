import { Link } from '@inertiajs/react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import DataTable from '@/Components/Tables/DataTable';
import ImportRowStatusBadge from '@/Components/Import/ImportRowStatusBadge';
import type { ImportBatch, ImportRow } from '@/Types/import';

interface InscriptionImportResultProps {
    batch: ImportBatch;
    rows: ImportRow[];
}

export default function InscriptionImportResult({ batch, rows }: InscriptionImportResultProps) {
    const failedRows = rows.filter((r) => r.status === 'ECHEC_COMMIT');

    return (
        <BackofficeLayout
            title="Résultat de l'import — Inscriptions"
            breadcrumbs={[
                { label: 'Tableau de bord', href: '/backoffice/dashboard' },
                { label: 'Import de données', href: '/backoffice/import' },
                { label: 'Résultat' },
            ]}
        >
            <Card title={`${batch.original_filename} — ${batch.etablissement?.nom_centre ?? ''} / ${batch.annee_scolaire?.nom ?? ''}`}>
                <div className="row mb-4">
                    <div className="col-md-3">
                        <div className="text-muted">Lignes dans le fichier</div>
                        <div className="fs-4 fw-semibold">{batch.total_rows}</div>
                    </div>
                    <div className="col-md-3">
                        <div className="text-muted">Insérées</div>
                        <div className="fs-4 fw-semibold text-success">{batch.inserted_rows}</div>
                    </div>
                    <div className="col-md-3">
                        <div className="text-muted">Ignorées</div>
                        <div className="fs-4 fw-semibold">{batch.skipped_rows}</div>
                    </div>
                    <div className="col-md-3">
                        <div className="text-muted">Erreurs</div>
                        <div className="fs-4 fw-semibold text-danger">{batch.error_rows}</div>
                    </div>
                </div>

                {failedRows.length > 0 && (
                    <>
                        <h6>Lignes en échec</h6>
                        <DataTable
                            head={
                                <tr>
                                    <th>N°</th>
                                    <th>Réf</th>
                                    <th>Statut</th>
                                    <th>Erreur</th>
                                </tr>
                            }
                        >
                            {failedRows.map((row) => (
                                <tr key={row.id}>
                                    <td>{row.source_row_number}</td>
                                    <td>{String(row.raw.legacy_ref ?? '')}</td>
                                    <td>
                                        <ImportRowStatusBadge status={row.status} />
                                    </td>
                                    <td>{row.errors?.map((e) => e.message).join(' ') ?? ''}</td>
                                </tr>
                            ))}
                        </DataTable>
                    </>
                )}

                <Link href="/backoffice/import/inscriptions" className="btn btn-primary mt-3">
                    Nouvel import
                </Link>
            </Card>
        </BackofficeLayout>
    );
}
