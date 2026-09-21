<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Academic year (gls-crm-schema.md §2), e.g. "2025/2026".
 */
class AnneeScolaire extends Model
{
    use Auditable;
    use HasFactory;

    protected $table = 'annees_scolaires';

    /**
     * Mirrors the DB defaults so a create() that omits the key leaves the
     * model and the row agreeing — otherwise the next change is journalled
     * as « avant : vide », stating a false previous value (CLAUDE.md §11).
     */
    protected $attributes = [
        'par_defaut' => false,
        'inscription_ouverte' => true,
        'cloturee' => false,
    ];

    protected $fillable = [
        'nom', 'date_debut', 'date_fin', 'par_defaut', 'inscription_ouverte', 'cloturee',
    ];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin' => 'date',
            'par_defaut' => 'boolean',
            'inscription_ouverte' => 'boolean',
            'cloturee' => 'boolean',
        ];
    }

    public function scopeParDefaut(Builder $query): Builder
    {
        return $query->where('par_defaut', true);
    }

    /**
     * Glissements de frontière en attente, posés par la VALIDATION
     * (`CouvertureAnneesRules::refuserTrou`) et joués par l'ÉCRITURE
     * (`AnneeScolaireController::persist`), dans la même transaction que
     * l'année soumise.
     *
     * ⚠ C'est un tampon PAR REQUÊTE, jamais un état partagé : le tableau
     * est vidé à la lecture (`prendreGlissements`) pour qu'une requête ne
     * puisse pas hériter du glissement calculé par la précédente — dans un
     * test, un worker de file ou Octane, le conteneur survit à la requête.
     *
     * @var array<int, array{annee: self, colonne: string, valeur: \Illuminate\Support\Carbon}>
     */
    private static array $glissements = [];

    /**
     * Un seul glissement par année et par colonne : si deux trous désignent
     * la même borne, c'est le même geste, pas deux.
     */
    public static function enregistrerGlissement(self $annee, string $colonne, \Illuminate\Support\Carbon $valeur): void
    {
        self::$glissements[] = [
            'annee' => $annee,
            'colonne' => $colonne,
            'valeur' => $valeur->copy()->startOfDay(),
        ];
    }

    /**
     * @return array<int, array{annee: self, colonne: string, valeur: \Illuminate\Support\Carbon}>
     */
    public static function prendreGlissements(): array
    {
        $enAttente = self::$glissements;
        self::$glissements = [];

        return $enAttente;
    }

    /**
     * A closed year accepts NO write at all — creation or modification, in
     * any module, by anyone (super-admin included). It is a business
     * invariant like the money rules of §11, not a permission: the only way
     * through is to un-tick « Année clôturée » in Paramètres → Années
     * scolaires, which is an explicit, audited gesture.
     *
     * Enforced at the single funnel every guarded write already passes
     * through — AssertsContextScope — so a new module inherits it without
     * having to remember anything.
     */
    public function estCloturee(): bool
    {
        return (bool) $this->cloturee;
    }

    public function scopeOuvertes(Builder $query): Builder
    {
        return $query->where('cloturee', false);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class, 'annee_scolaire_id');
    }

    public function inscriptions(): HasMany
    {
        return $this->hasMany(Inscription::class, 'annee_scolaire_id');
    }
}
