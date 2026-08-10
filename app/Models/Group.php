<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

/**
 * Class / cohort (gls-crm-schema.md §6).
 *
 * statut lifecycle: En inscription → En formation → Fin de formation.
 * A group row is NEVER deleted, even after finishing (inscriptions.group_id
 * must stay a valid FK). groups_historique is an archive snapshot only.
 */
class Group extends Model
{
    use HasFactory;

    // statut lifecycle — enforced here, not at the database level (schema §6)
    public const STATUT_EN_INSCRIPTION = 'En inscription';

    public const STATUT_EN_FORMATION = 'En formation';

    public const STATUT_FIN_FORMATION = 'Fin de formation';

    public const STATUTS = [
        self::STATUT_EN_INSCRIPTION,
        self::STATUT_EN_FORMATION,
        self::STATUT_FIN_FORMATION,
    ];

    /**
     * CEFR levels only (schema §5). Deliberately NOT Student::NIVEAUX: the
     * student list also carries the German tracks (Arbeit/Studium/Ausbildung),
     * which describe a person's goal, not a class level — and a group's niveau
     * drives the per-fee `classification`.
     */
    public const NIVEAUX = Student::NIVEAUX_CEFR;

    protected $fillable = [
        'nom', 'niveau', 'enseignant_id', 'salle_id', 'etablissement_id',
        'annee_scolaire_id', 'capacite_max', 'statut',
        'date_debut_formation', 'date_fin_formation',
    ];

    protected function casts(): array
    {
        return [
            'date_debut_formation' => 'date',
            'date_fin_formation' => 'date',
        ];
    }

    public function enseignant(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'enseignant_id');
    }

    public function salle(): BelongsTo
    {
        return $this->belongsTo(Salle::class);
    }

    public function etablissement(): BelongsTo
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class, 'annee_scolaire_id');
    }

    public function inscriptions(): HasMany
    {
        return $this->hasMany(Inscription::class);
    }

    /**
     * Catalog fees assigned to this group (with the group's amount) —
     * these become the "Frais disponibles" when enrolling a student.
     */
    public function frais(): BelongsToMany
    {
        return $this->belongsToMany(Frais::class, 'group_frais')
            ->withPivot('montant', 'date_echeance', 'classification')
            ->withTimestamps();
    }

    public function historique(): HasOne
    {
        return $this->hasOne(GroupHistorique::class);
    }

    /**
     * Marks the group finished and archives a snapshot in ONE transaction.
     *
     * This is the ONLY correct way to transition a group to "Fin de formation"
     * — never set ->statut directly, or groups_historique silently falls out
     * of sync (gls-crm-schema.md §7, structure doc §3).
     */
    public function archiverCommeTermine(?Employee $archivedBy = null): void
    {
        DB::transaction(function () use ($archivedBy): void {
            $this->update([
                'statut' => self::STATUT_FIN_FORMATION,
                'date_fin_formation' => $this->date_fin_formation ?? now(),
            ]);

            $this->historique()->create([
                'nom' => $this->nom,
                'niveau' => $this->niveau,
                'enseignant_id' => $this->enseignant_id,
                'etablissement_id' => $this->etablissement_id,
                'annee_scolaire_id' => $this->annee_scolaire_id,
                'nombre_etudiants_final' => $this->inscriptions()->count(),
                'date_debut_formation' => $this->date_debut_formation,
                'date_fin_formation' => $this->date_fin_formation,
                'archived_at' => now(),
                'archived_by' => $archivedBy?->id,
            ]);
        });
    }
}
