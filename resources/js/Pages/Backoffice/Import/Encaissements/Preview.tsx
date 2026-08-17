import { router } from '@inertiajs/react';
import { useState } from 'react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import DataTable from '@/Components/Tables/DataTable';
import ImportRowStatusBadge from '@/Components/Import/ImportRowStatusBadge';
import type { ImportBatch, ImportRow } from '@/Types/import';

interface EncaissementImportPreviewProps {
    batch: ImportBatch;
    rows: ImportRow[];
}

const SELECTABLE_STATUSES = ['NOUVEAU', 'CONFLIT'];

export default function EncaissementImportPreview({ batch, rows }: EncaissementImportPreviewProps) {
    const [selected, setSelected] = useState<Set<number>>(
        () => new Set(rows.filter((r) => r.status === 'NOUVEAU').map((r) => r.id))
    );
    const [filter, setFilter] = useState<string>('');

    const filteredRows = filter === '' ? rows : rows.filter((r) => r.status === filter);
    const counts = rows.reduce<Record<string, number>>((acc, r) => {
        acc[r.status] = (acc[r.status] ?? 0) + 1;
        return acc;
    }, {});

    const excelTotal = rows.reduce((sum, r) => sum + (Number(r.raw.montant) || 0), 0);

    function toggleRow(id: number) {
        setSelected((prev) => {
            const next = new Set(prev);
            if (next.has(id)) {
                next.delete(id);
            } else {
                next.add(id);
            }
            return next;
        });
    }

    function commit() {
        router.post(`/backoffice/import/encaissements/${batch.id}/commit`, {
            selected_row_ids: Array.from(selected),
        });
    }

    return (
        <BackofficeLayout
            title="Aperçu de l'import — Encaissements"
            breadcrumbs={[
                { label: 'Tableau de bord', href: '/backoffice/dashboard' },
                { label: 'Import de données', href: '/backoffice/import' },
                { label: 'Aperçu' },
            ]}
        >
            <Card title={`${batch.original_filename} — ${batch.etablissement?.nom_centre ?? ''} / ${batch.annee_scolaire?.nom ?? ''}`}>
                <div className="d-flex gap-2 mb-3 flex-wrap">
                    <button
                        type="button"
                        className={`btn btn-sm ${filter === '' ? 'btn-primary' : 'btn-outline-primary'}`}
                        onClick={() => setFilter('')}
                    >
                        Tous ({rows.length})
                    </button>
                    {(['NOUVEAU', 'DOUBLON', 'ERREUR', 'CONFLIT'] as const).map((status) => (
                        <button
                            key={status}
                            type="button"
                            className={`btn btn-sm ${filter === status ? 'btn-primary' : 'btn-outline-primary'}`}
                            onClick={() => setFilter(status)}
                        >
                            {status} ({counts[status] ?? 0})
                        </button>
                    ))}
                </div>

                <div className="text-muted mb-2">Total Excel : {excelTotal.toFixed(2)} DH (toutes lignes, y compris erreurs/conflits)</div>

                <DataTable
                    head={
                        <tr>
                            <th />
                            <th>N°</th>
                            <th>Réf</th>
                            <th>Payeur</th>
                            <th>Montant</th>
                            <th>Méthode</th>
                            <th>Frais</th>
                            <th>Date</th>
                            <th>Opérateur</th>
                            <th>État</th>
                            <th>Détails</th>
                        </tr>
                    }
                >
                    {filteredRows.map((row) => (
                        <tr key={row.id}>
                            <td>
                                <input
                                    type="checkbox"
                                    className="form-check-input"
                                    checked={selected.has(row.id)}
                                    disabled={!SELECTABLE_STATUSES.includes(row.status)}
                                    onChange={() => toggleRow(row.id)}
                                />
                            </td>
                            <td>{row.source_row_number}</td>
                            <td>{String(row.raw.legacy_ref ?? '')}</td>
                            <td>{String(row.raw.payeur ?? '')}</td>
                            <td>{String(row.raw.montant ?? '—')}</td>
                            <td>{String(row.raw.methode_label ?? '')}</td>
                            <td>{String(row.raw.frais_label ?? '')}</td>
                            <td>{String(row.raw.date_paiement ?? '—')}</td>
                            <td>{String(row.raw.operateur ?? '')}</td>
                            <td>
                                <ImportRowStatusBadge status={row.status} />
                            </td>
                            <td>{row.errors?.map((e) => e.message).join(' ') ?? ''}</td>
                        </tr>
                    ))}
                </DataTable>

                <div className="d-flex justify-content-between align-items-center mt-3">
                    <span className="text-muted">{selected.size} ligne(s) sélectionnée(s)</span>
                    <button type="button" className="btn btn-primary" onClick={commit} disabled={selected.size === 0}>
                        Insérer la sélection
                    </button>
                </div>
            </Card>
        </BackofficeLayout>
    );
}
