<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A legacy-CRM Excel import run, scoped to exactly one Centre + Année
 * scolaire (etablissement_id/annee_scolaire_id — mandatory, immutable once
 * analyzed; see docs/... import plan). One row per Analyze click; import_rows
 * hold the per-line preview/commit state.
 */
class ImportBatch extends Model
{
    use Auditable;

    public const MODULE_STUDENTS = 'students';

    public const MODULE_INSCRIPTIONS = 'inscriptions';

    public const MODULE_ENCAISSEMENTS = 'encaissements';

    public const MODULES = [
        self::MODULE_STUDENTS,
        self::MODULE_INSCRIPTIONS,
        self::MODULE_ENCAISSEMENTS,
    ];

    public const STATUT_ANALYZED = 'analyzed';

    public const STATUT_COMMITTING = 'committing';

    public const STATUT_COMMITTED = 'committed';

    public const STATUT_COMMITTED_WITH_ERRORS = 'committed_with_errors';

    protected $fillable = [
        'module', 'original_filename', 'etablissement_id', 'annee_scolaire_id',
        'context', 'status', 'total_rows', 'inserted_rows', 'skipped_rows',
        'error_rows', 'created_by', 'analyzed_at', 'committed_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'analyzed_at' => 'datetime',
            'committed_at' => 'datetime',
        ];
    }

    public function etablissement(): BelongsTo
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }

    public function rows(): HasMany
    {
        return $this->hasMany(ImportRow::class);
    }
}
