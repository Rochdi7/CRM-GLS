<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One teaching-assignment period of a group ("affectation prof").
 *
 * A group has exactly ONE Actif row at a time (enforced by the partial
 * unique index in the migration) and any number of Archivé ones. Changing a
 * group's teacher never overwrites history: the current row is closed
 * (date_fin = the changeover date, statut = Archivé) and a new Actif row
 * opens — so "from when to when did this teacher run this group" stays
 * answerable for payroll.
 *
 * Assignment rows are NEVER deleted, same rationale as groups themselves
 * (schema §6): they are the payroll trail. Use
 * Domain\Groups\Actions\ChangerEnseignantGroupe — never write statut /
 * date_fin directly, or groups.enseignant_id falls out of sync with the
 * active row.
 */
class GroupEnseignant extends Model
{
    use Auditable;
    use HasFactory;

    public const STATUT_ACTIF = 'Actif';

    public const STATUT_ARCHIVE = 'Archivé';

    public const STATUTS = [self::STATUT_ACTIF, self::STATUT_ARCHIVE];

    protected $fillable = [
        'group_id', 'enseignant_id', 'date_debut', 'date_fin',
        'statut', 'motif', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin' => 'date',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function enseignant(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'enseignant_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }

    public function isActif(): bool
    {
        return $this->statut === self::STATUT_ACTIF;
    }
}
