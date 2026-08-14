<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class session (attendance module). Belongs to a group; the per-student
 * roll call is `presences`. etablissement_id / annee_scolaire_id are always
 * inherited from the group at creation (SeanceController), never form inputs
 * — same denormalization pattern as inscriptions.
 */
class Seance extends Model
{
    use HasFactory;

    public const STATUT_PREVUE = 'Prévue';

    public const STATUT_EFFECTUEE = 'Effectuée';

    public const STATUT_ANNULEE = 'Annulée';

    public const STATUTS = [
        self::STATUT_PREVUE,
        self::STATUT_EFFECTUEE,
        self::STATUT_ANNULEE,
    ];

    protected $fillable = [
        'group_id', 'creneau_id', 'date_seance', 'heure_debut', 'heure_fin',
        'enseignant_id', 'etablissement_id', 'annee_scolaire_id',
        'statut', 'note', 'motif_annulation', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date_seance' => 'date',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function creneau(): BelongsTo
    {
        return $this->belongsTo(Creneau::class);
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }

    public function presences(): HasMany
    {
        return $this->hasMany(Presence::class);
    }

    /**
     * Manually confirms the séance actually took place. The roll call no
     * longer flips the statut on its own (EnregistrerPresences) — marking
     * "Effectuée" is a deliberate action from the row menu.
     */
    public function valider(): void
    {
        $this->update(['statut' => self::STATUT_EFFECTUEE]);
    }

    /**
     * Cancels the séance with a required reason, kept separate from the
     * general `note` field so it never overwrites unrelated notes already on
     * the séance.
     */
    public function annuler(string $motif): void
    {
        $this->update(['statut' => self::STATUT_ANNULEE, 'motif_annulation' => $motif]);
    }
}
