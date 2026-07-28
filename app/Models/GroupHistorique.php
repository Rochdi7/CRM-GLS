<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Read-only archive snapshot of a finished group (gls-crm-schema.md §7).
 * Rows are inserted EXCLUSIVELY by Group::archiverCommeTermine() —
 * there is no manual CRUD for this table.
 */
class GroupHistorique extends Model
{
    use HasFactory;

    protected $table = 'groups_historique';

    public $timestamps = false; // archived_at is the only timestamp

    protected $fillable = [
        'group_id', 'nom', 'niveau', 'enseignant_id', 'etablissement_id',
        'annee_scolaire_id', 'nombre_etudiants_final',
        'date_debut_formation', 'date_fin_formation',
        'archived_at', 'archived_by',
    ];

    protected function casts(): array
    {
        return [
            'date_debut_formation' => 'date',
            'date_fin_formation' => 'date',
            'archived_at' => 'datetime',
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

    public function etablissement(): BelongsTo
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class, 'annee_scolaire_id');
    }

    public function archivedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'archived_by');
    }
}
