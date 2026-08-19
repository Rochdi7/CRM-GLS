<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One parsed line from an import batch's source file. `raw` holds the
 * as-parsed column=>value map (pre-dedupe); `resolution` holds per-row
 * resolved matches (frais_id, group_id, caisse_id, candidate lists...)
 * filled in during analyze()/the preview screen; `errors` holds blocking
 * validation/dedupe failures. See the status-machine doc in the import plan.
 */
class ImportRow extends Model
{
    public const STATUT_NOUVEAU = 'NOUVEAU';

    public const STATUT_DOUBLON = 'DOUBLON';

    public const STATUT_ERREUR = 'ERREUR';

    public const STATUT_CONFLIT = 'CONFLIT';

    public const STATUT_INSERE = 'INSERE';

    public const STATUT_IGNORE = 'IGNORE';

    public const STATUT_ECHEC_COMMIT = 'ECHEC_COMMIT';

    /**
     * Rows one commit() pass will process.
     *
     * ECHEC_COMMIT is deliberately NOT here: a failed row keeps its status,
     * so leaving it eligible made commit() hand back the same row forever —
     * `remaining` never reached 0 and the progress loop spun indefinitely.
     * Retrying a failure is an explicit user action instead (see
     * RETRYABLE_STATUTS), which re-queues it as CONFLIT for the next pass.
     */
    public const SELECTABLE_STATUTS = [
        self::STATUT_NOUVEAU,
        self::STATUT_CONFLIT,
    ];

    /** Statuses the user may explicitly re-queue once their cause is fixed. */
    public const RETRYABLE_STATUTS = [
        self::STATUT_ECHEC_COMMIT,
    ];

    /** Every valid status — used to validate the Preview screen's status filter. */
    public const STATUTS = [
        self::STATUT_NOUVEAU,
        self::STATUT_DOUBLON,
        self::STATUT_ERREUR,
        self::STATUT_CONFLIT,
        self::STATUT_INSERE,
        self::STATUT_IGNORE,
        self::STATUT_ECHEC_COMMIT,
    ];

    protected $fillable = [
        'import_batch_id', 'source_row_number', 'raw', 'status', 'errors',
        'resolution', 'legacy_ref', 'created_model_type', 'created_model_id',
        'selected',
    ];

    protected function casts(): array
    {
        return [
            'raw' => 'array',
            'errors' => 'array',
            'resolution' => 'array',
            'selected' => 'boolean',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'import_batch_id');
    }
}
