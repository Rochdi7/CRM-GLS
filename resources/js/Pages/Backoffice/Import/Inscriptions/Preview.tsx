import { router } from '@inertiajs/react';
import { useCallback, useState } from 'react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import DataTable from '@/Components/Tables/DataTable';
import Pagination from '@/Components/Tables/Pagination';
import ImportRowStatusBadge from '@/Components/Import/ImportRowStatusBadge';
import CommitProgressBar from '@/Components/Import/CommitProgressBar';
import { useCommitProgress } from '@/Hooks/useCommitProgress';
import type { PaginatedData } from '@/Types';
import type { ImportBatch, ImportRow, ImportRowStatus, ImportStatusCounts } from '@/Types/import';

interface InscriptionImportPreviewProps {
    batch: ImportBatch;
    rows: PaginatedData<ImportRow>;
    statusCounts: ImportStatusCounts;
    /** NOUVEAU rows — pre-checked by default. */
    selectableRowIds: number[];
    /** CONFLIT rows — selectable only if the operator opts in, never pre-checked. */
    conflictRowIds: number[];
    /** ECHEC_COMMIT rows — retryable once their cause is fixed, never pre-checked. */
    failedRowIds: number[];
    filters: { status: string };
}

const FILTERABLE_STATUSES: ImportRowStatus[] = ['NOUVEAU', 'DOUBLON', 'ERREUR', 'CONFLIT', 'ECHEC_COMMIT'];
// Mirrors ImportRow::SELECTABLE_STATUTS — failures are retryable.
const SELECTABLE_STATUSES = ['NOUVEAU', 'CONFLIT'];

export default function InscriptionImportPreview({
    batch,
    rows,
    statusCounts,
    selectableRowIds,
    conflictRowIds,
    failedRowIds,
    filters,
}: InscriptionImportPreviewProps) {
    // Selection spans the WHOLE batch, not just the visible page, which is
    // why both id lists are sent in full. NOUVEAU rows start checked;
    // CONFLIT rows are unresolved by definition and start unchecked — the
    // operator opts each one in after resolving it.
    const [excluded, setExcluded] = useState<Set<number>>(() => new Set());
    const [includedConflicts, setIncludedConflicts] = useState<Set<number>>(() => new Set());

    const selectedIds = [
        ...selectableRowIds.filter((id) => !excluded.has(id)),
        ...conflictRowIds.filter((id) => includedConflicts.has(id)),
    ];
    const conflictSet = new Set(conflictRowIds);

    const handleDone = useCallback(() => {
        router.visit(`/backoffice/import/inscriptions/${batch.id}/result`);
    }, [batch.id]);

    const progress = useCommitProgress(`/backoffice/import/inscriptions/${batch.id}/commit`, handleDone);

    const totalRows = Object.values(statusCounts).reduce((sum, n) => sum + (n ?? 0), 0);

    function applyFilter(status: string) {
        router.get(
            `/backoffice/import/inscriptions/${batch.id}/preview`,
            status === '' ? {} : { status },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    const allNouveauSelected = selectableRowIds.length > 0 && excluded.size === 0;
    const allConflictsSelected = conflictRowIds.length > 0 && includedConflicts.size === conflictRowIds.length;

    /** Selects/clears every NOUVEAU row in the batch, not just the visible page. */
    function toggleSelectAllNouveau() {
        setExcluded(allNouveauSelected ? new Set(selectableRowIds) : new Set());
    }

    /**
     * Opt every CONFLIT row in at once. Unresolved rows still fail the
     * server-side guard with a reason — this is only a shortcut for batches
     * the operator has already worked through.
     */
    function toggleSelectAllConflicts() {
        setIncludedConflicts(allConflictsSelected ? new Set() : new Set(conflictRowIds));
    }

    /**
     * Re-queues previously-failed rows server-side (they become CONFLIT
     * again) and reloads. They are not simply added to the selection: a
     * failed row keeps its status after commit(), so leaving it eligible
     * made the progress loop hand back the same row forever.
     */
    function retryFailedRows() {
        router.post(
            `/backoffice/import/inscriptions/${batch.id}/retry-failed`,
            {},
            { preserveScroll: true },
        );
    }

    function toggleRow(id: number) {
        const setter = conflictSet.has(id) ? setIncludedConflicts : setExcluded;

        setter((prev) => {
            const next = new Set(prev);
            if (next.has(id)) {
                next.delete(id);
            } else {
                next.add(id);
            }
            return next;
        });
    }

    return (
        <BackofficeLayout
            title="Aperçu de l'import — Inscriptions"
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
                        className={`btn btn-sm ${filters.status === '' ? 'btn-primary' : 'btn-outline-primary'}`}
                        onClick={() => applyFilter('')}
                    >
                        Tous ({totalRows})
                    </button>
                    {FILTERABLE_STATUSES.map((status) => (
                        <button
                            key={status}
                            type="button"
                            className={`btn btn-sm ${filters.status === status ? 'btn-primary' : 'btn-outline-primary'}`}
                            onClick={() => applyFilter(status)}
                        >
                            {status} ({statusCounts[status] ?? 0})
                        </button>
                    ))}
                </div>

                <DataTable
                    loading={progress.running}
                    head={
                        <tr>
                            <th>
                                <input
                                    type="checkbox"
                                    className="form-check-input"
                                    checked={allNouveauSelected}
                                    onChange={toggleSelectAllNouveau}
                                    aria-label="Tout sélectionner"
                                    title="Tout sélectionner (lignes nouvelles)"
                                />
                            </th>
                            <th>N°</th>
                            <th>Réf</th>
                            <th>Étudiant</th>
                            <th>Groupe</th>
                            <th>Statut (fichier)</th>
                            <th>Date d'inscription</th>
                            <th>État</th>
                            <th>Détails</th>
                        </tr>
                    }
                >
                    {rows.data.map((row) => (
                        <tr key={row.id}>
                            <td>
                                <input
                                    type="checkbox"
                                    className="form-check-input"
                                    checked={selectedIds.includes(row.id)}
                                    disabled={!SELECTABLE_STATUSES.includes(row.status)}
                                    onChange={() => toggleRow(row.id)}
                                />
                            </td>
                            <td>{row.source_row_number}</td>
                            <td>{String(row.raw.legacy_ref ?? '')}</td>
                            <td>{String(row.raw.etudiant ?? '')}</td>
                            <td>{String(row.raw.groupe ?? '')}</td>
                            <td>{String(row.raw.statut ?? '')}</td>
                            <td>{String(row.raw.date_inscription ?? '—')}</td>
                            <td>
                                <ImportRowStatusBadge status={row.status} />
                            </td>
                            <td>{row.errors?.map((e) => e.message).join(' ') ?? ''}</td>
                        </tr>
                    ))}
                </DataTable>

                <Pagination paginator={rows} />

                {progress.running && <CommitProgressBar inserted={progress.inserted} errors={progress.errors} total={progress.total} />}
                {progress.failed && <div className="alert alert-danger mt-3">Une erreur est survenue pendant l'insertion.</div>}

                <div className="d-flex gap-2 mb-3 flex-wrap">
                    <button type="button" className="btn btn-sm btn-outline-secondary" onClick={toggleSelectAllNouveau}>
                        {allNouveauSelected ? 'Tout désélectionner' : 'Tout sélectionner'} ({selectableRowIds.length} nouvelle(s))
                    </button>
                    {conflictRowIds.length > 0 && (
                        <button type="button" className="btn btn-sm btn-outline-warning" onClick={toggleSelectAllConflicts}>
                            {allConflictsSelected ? 'Retirer les conflits' : 'Inclure les conflits'} ({conflictRowIds.length})
                        </button>
                    )}
                    {failedRowIds.length > 0 && (
                        <button type="button" className="btn btn-sm btn-outline-danger" onClick={retryFailedRows}>
                            Réessayer les échecs ({failedRowIds.length})
                        </button>
                    )}
                </div>

                <div className="d-flex justify-content-between align-items-center mt-3">
                    <span className="text-muted">
                        {selectedIds.length} ligne(s) sélectionnée(s) sur l'ensemble du lot
                        {conflictRowIds.length > 0 && (
                            <>
                                {' '}
                                — {conflictRowIds.length - includedConflicts.size} conflit(s) exclu(s), à résoudre avant insertion
                            </>
                        )}
                    </span>
                    <button
                        type="button"
                        className="btn btn-primary"
                        onClick={() => progress.start(selectedIds)}
                        disabled={selectedIds.length === 0 || progress.running}
                    >
                        {progress.running ? 'Insertion en cours…' : 'Insérer la sélection'}
                    </button>
                </div>
            </Card>
        </BackofficeLayout>
    );
}
