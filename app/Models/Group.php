<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
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
 * statut lifecycle: En inscription → En formation → {Fin de formation |
 * Annulée} (both terminal, both shown in the list's "Historique" tab —
 * Annulée for a group that never actually ran, Fin de formation for one
 * that completed normally). En inscription -> Annulée is not offered by the
 * UI's quick actions (only from En formation) but is not blocked here.
 * A group row is NEVER deleted, even after finishing/cancelling
 * (inscriptions.group_id must stay a valid FK). groups_historique is an
 * archive snapshot only.
 */
class Group extends Model
{
    use Auditable;
    use HasFactory;

    // statut lifecycle — enforced here, not at the database level (schema §6)
    public const STATUT_EN_INSCRIPTION = 'En inscription';

    public const STATUT_EN_FORMATION = 'En formation';

    public const STATUT_FIN_FORMATION = 'Fin de formation';

    public const STATUT_ANNULEE = 'Annulée';

    public const STATUTS = [
        self::STATUT_EN_INSCRIPTION,
        self::STATUT_EN_FORMATION,
        self::STATUT_FIN_FORMATION,
        self::STATUT_ANNULEE,
    ];

    /** Terminal statuses grouped under the list's "Historique" tab. */
    public const STATUTS_HISTORIQUE = [
        self::STATUT_FIN_FORMATION,
        self::STATUT_ANNULEE,
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

    /**
     * Mirror of the column default, so a freshly created row and the model in
     * memory agree.
     *
     * Without this, a create() that omits `statut` leaves the model holding
     * NULL while the database row holds the default. The next status change
     * then records « avant : (vide) » in the audit journal — a false statement
     * of history, since the record did have a status. See InscriptionFee,
     * where this actually produced wrong entries.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'statut' => self::STATUT_EN_INSCRIPTION,
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
     * Full teaching-assignment history (one row per period, newest first) —
     * exactly one is Actif, the rest are Archivé. See GroupEnseignant and
     * Domain\Groups\Actions\ChangerEnseignantGroupe; `enseignant_id` above
     * is only a denormalized mirror of the Actif row.
     */
    public function enseignants(): HasMany
    {
        return $this->hasMany(GroupEnseignant::class)
            ->orderByDesc('date_debut')
            ->orderByDesc('id');
    }

    /** The single currently-running assignment, if any. */
    public function enseignantActif(): HasOne
    {
        return $this->hasOne(GroupEnseignant::class)
            ->where('statut', GroupEnseignant::STATUT_ACTIF);
    }

    /** Weekly recurring schedule slots (emploi du temps) of this group. */
    public function creneaux(): HasMany
    {
        return $this->hasMany(Creneau::class);
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

            $this->writeHistoriqueSnapshot($archivedBy);
        });
    }

    /**
     * Marks the group cancelled (never actually ran, as opposed to
     * "Fin de formation"'s normal completion) and archives the same kind of
     * snapshot — the group's own `statut` column is what distinguishes the
     * two in the Historique tab, since groups_historique itself carries no
     * separate "reason" column. Only valid from En inscription/En formation
     * (never from an already-terminal status — enforced by the caller via
     * the same authorize()/guard pattern archive() uses).
     */
    public function annuler(?Employee $archivedBy = null): void
    {
        DB::transaction(function () use ($archivedBy): void {
            $this->update(['statut' => self::STATUT_ANNULEE]);

            $this->writeHistoriqueSnapshot($archivedBy);
        });
    }

    /**
     * Reverses annuler() — brings a cancelled group back to "En inscription"
     * so enrollment can resume. Deliberately does NOT touch groups_historique
     * (the snapshot row is refreshed again by whichever terminal action
     * happens next, if any); "Fin de formation" is never reactivated through
     * this method — that transition stays one-way, matching
     * archiverCommeTermine()'s existing irreversibility.
     */
    public function reactiver(): void
    {
        $this->update(['statut' => self::STATUT_EN_INSCRIPTION]);
    }

    /**
     * Starts the training — moves a group from "En inscription" to
     * "En formation" (enrollment closes, the class is now actually running).
     * Only valid from En inscription; refused otherwise by the caller's own
     * guard, same pattern as annuler()/reactiver().
     */
    public function activer(): void
    {
        $this->update(['statut' => self::STATUT_EN_FORMATION]);
    }

    /**
     * Reverses activer() — brings a group that's actively "En formation"
     * back to "En inscription" (e.g. training started too early / needs more
     * enrollment). Only valid from En formation; refused otherwise by the
     * caller's own guard, same pattern as annuler()/reactiver()/activer().
     */
    public function retournerEnInscription(): void
    {
        $this->update(['statut' => self::STATUT_EN_INSCRIPTION]);
    }

    /**
     * updateOrCreate rather than create(): a group can cycle
     * annuler() -> reactiver() -> annuler()/archiverCommeTermine() more than
     * once, and historique() is a HasOne — always keep the single snapshot
     * row current instead of erroring or duplicating on a second terminal
     * transition.
     */
    private function writeHistoriqueSnapshot(?Employee $archivedBy): void
    {
        $this->historique()->updateOrCreate(
            ['group_id' => $this->id],
            [
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
            ],
        );
    }
}
