interface CommitProgressBarProps {
    inserted: number;
    errors: number;
    total: number;
}

/**
 * PreSkool progress-bar markup — visible feedback while a chunked commit()
 * loop is in flight, so "insérer la sélection" no longer looks like it
 * silently hangs on larger files (see useCommitProgress).
 */
export default function CommitProgressBar({ inserted, errors, total }: CommitProgressBarProps) {
    const processed = inserted + errors;
    const percent = total > 0 ? Math.round((processed / total) * 100) : 0;

    return (
        <div className="mb-3">
            <div className="d-flex justify-content-between mb-1">
                <span className="fw-semibold">Insertion en cours…</span>
                <span className="text-muted">
                    {processed} / {total} lignes traitées
                </span>
            </div>
            <div className="progress" style={{ height: 10 }}>
                <div
                    className="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                    role="progressbar"
                    style={{ width: `${percent}%` }}
                    aria-valuenow={percent}
                    aria-valuemin={0}
                    aria-valuemax={100}
                />
            </div>
            <div className="d-flex gap-3 mt-1 small text-muted">
                <span>{inserted} insérée(s)</span>
                {errors > 0 && <span className="text-danger">{errors} échec(s)</span>}
            </div>
        </div>
    );
}
