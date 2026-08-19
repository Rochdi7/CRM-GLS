<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * "Changement de groupe" snapshot (gls-crm-schema.md §7-style archive),
 * written exclusively by
 * App\Domain\Registrations\Actions\ChangerGroupeInscription.
 */
class InscriptionHistorique extends Model
{
    use Auditable;

    public $timestamps = false;

    protected $table = 'inscriptions_historique';

    protected $fillable = [
        'inscription_id', 'new_inscription_id', 'student_id', 'group_id',
        'montant_paye', 'date_fin', 'note', 'archived_at', 'archived_by',
    ];

    protected function casts(): array
    {
        return [
            'montant_paye' => 'decimal:2',
            'date_fin' => 'date',
            'archived_at' => 'datetime',
        ];
    }

    public function inscription(): BelongsTo
    {
        return $this->belongsTo(Inscription::class);
    }

    public function newInscription(): BelongsTo
    {
        return $this->belongsTo(Inscription::class, 'new_inscription_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function archivedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'archived_by');
    }
}
