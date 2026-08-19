import DataTable from '@/Components/Tables/DataTable';
import Pagination from '@/Components/Tables/Pagination';
import ImportRowStatusBadge from '@/Components/Import/ImportRowStatusBadge';
import type { PaginatedData } from '@/Types';
import type { ImportRow } from '@/Types/import';

interface ImportRowReasonTableProps {
    title: string;
    /** Shown under the title — says what this table is for, and why rows landed here. */
    hint?: string;
    rows: PaginatedData<ImportRow>;
}

/**
 * The person a skipped row is about, whichever module it came from:
 * Inscriptions store `etudiant`, Encaissements store the (un-doubled)
 * `payeur`, Étudiants store prenom/nom straight off the sheet. A réf. alone
 * is unusable — the operator has to recognise the name to judge whether a
 * skip was harmless.
 */
function personLabel(row: ImportRow): string {
    const raw = row.raw as Record<string, unknown>;
    const candidates = [
        raw.etudiant,
        raw.payeur,
        raw.payeur_raw,
        [raw.prenom, raw.nom].filter(Boolean).join(' ').trim() || null,
    ];

    const found = candidates.find((v) => typeof v === 'string' && v.trim() !== '');

    return typeof found === 'string' ? found.trim() : '—';
}

/**
 * Lists import rows together with the reason they ended up in that state.
 *
 * Both the failed and the skipped tables on every Result screen render the
 * same columns, so they share this component — a skipped row without a
 * visible reason (and without a name) is just an unexplained number in a
 * counter.
 */
export default function ImportRowReasonTable({ title, hint, rows }: ImportRowReasonTableProps) {
    if (rows.total === 0) {
        return null;
    }

    return (
        <div className="mb-4">
            <h6 className="mb-1">
                {title} <span className="text-muted fw-normal">({rows.total})</span>
            </h6>
            {hint && <p className="text-muted small mb-2">{hint}</p>}
            <DataTable
                head={
                    <tr>
                        <th style={{ width: '6rem' }}>N° ligne</th>
                        <th style={{ width: '8rem' }}>Réf</th>
                        <th>Étudiant</th>
                        <th style={{ width: '10rem' }}>Statut</th>
                        <th>Raison</th>
                    </tr>
                }
            >
                {rows.data.map((row) => (
                    <tr key={row.id}>
                        <td>{row.source_row_number}</td>
                        <td>{String(row.raw.legacy_ref ?? '—')}</td>
                        <td>{personLabel(row)}</td>
                        <td>
                            <ImportRowStatusBadge status={row.status} />
                        </td>
                        <td>
                            {row.errors?.length
                                ? row.errors.map((e) => e.message).join(' ')
                                : <span className="text-muted">Raison non enregistrée.</span>}
                        </td>
                    </tr>
                ))}
            </DataTable>
            <Pagination paginator={rows} />
        </div>
    );
}
