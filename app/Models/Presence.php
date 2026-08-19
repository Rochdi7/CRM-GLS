<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-student roll-call line of a séance — unique per (seance, student).
 * statut is a plain VARCHAR validated against the constants below (same
 * deliberate pattern as every other statut field, gls-crm-schema.md).
 */
class Presence extends Model
{
    use Auditable;
    use HasFactory;

    public const STATUT_PRESENT = 'Présent';

    public const STATUT_ABSENT = 'Absent';

    public const STATUT_RETARD = 'Retard';

    public const STATUT_JUSTIFIE = 'Justifié';

    public const STATUTS = [
        self::STATUT_PRESENT,
        self::STATUT_ABSENT,
        self::STATUT_RETARD,
        self::STATUT_JUSTIFIE,
    ];

    protected $fillable = [
        'seance_id', 'student_id', 'statut', 'note',
    ];

    public function seance(): BelongsTo
    {
        return $this->belongsTo(Seance::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
