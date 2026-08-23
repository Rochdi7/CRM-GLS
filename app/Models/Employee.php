<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Scopes\HiddenAccountScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Master staff record (gls-crm-schema.md §4) — teachers are employees,
 * `categorie` distinguishes roles (plain VARCHAR, validated in requests).
 *
 * Creating an Employee auto-generates its login credentials via
 * EmployeeObserver → EmployeeCredentialService (structure doc §5).
 *
 * ⚠ A GLOBAL SCOPE hides the maintainer's own staff record from every query
 * (HiddenAccountScope) — the developer holds a super-admin login but is not
 * GLS staff, so he must not appear in the roster, the head-counts or any
 * employee picker. It is a display filter only: his audit entries are still
 * recorded in full and his permissions are unchanged. Opt out with
 * `Employee::withoutGlobalScope(HiddenAccountScope::class)` when a row
 * genuinely must be reachable (provisioning, integrity checks, exports).
 */
#[ScopedBy(HiddenAccountScope::class)]
class Employee extends Model implements HasMedia
{
    use Auditable;
    use HasFactory;
    use InteractsWithMedia;

    /**
     * Transient, not a DB column — an optional admin-chosen username read by
     * EmployeeCredentialService::generateUniqueUsername() from the "created"
     * observer. Declared as a real property so Eloquent's attribute magic
     * (which would otherwise route any unknown property through
     * setAttribute() and try to persist it) doesn't intercept it.
     */
    public ?string $requestedUsername = null;

    public const CATEGORIE_DIRECTEUR = 'Directeur';

    public const CATEGORIE_CONSULTANT = 'Consultant';

    public const CATEGORIE_ENSEIGNANT = 'Enseignant';

    public const CATEGORIE_COMPTABLE = 'Comptable';

    public const CATEGORIE_RESPONSABLE_MARKETING = 'Responsable Marketing';

    public const CATEGORIE_ASSISTANTE_ADMINISTRATIVE = 'Assistante administrative';

    public const CATEGORIE_RESPONSABLE_ADMINISTRATIVE = 'Responsable administrative';

    public const CATEGORIE_RESPONSABLE_RH = 'Responsable RH';

    public const CATEGORIE_DIRECTEUR_FINANCIER = 'Directeur financier';

    public const CATEGORIE_DIRECTEUR_OPERATIONS = 'Directeur des opérations';

    public const CATEGORIE_DIRECTRICE_PEDAGOGIQUE = 'Directrice pédagogique';

    public const CATEGORIE_DIRECTEUR_QUALITE = 'Directeur Qualité et Amélioration continue';

    /**
     * The ONE job title whose default role is `super-admin`
     * (PermissionRegistry::defaultRoleFor()). EmployeeObserver grants it
     * unconditionally, and only a super-admin may pick it on the Employees
     * form (Store/UpdateEmployeeRequest) — otherwise any `employees.create`
     * holder could mint a super-admin, defeating CLAUDE.md §16.
     */
    public const CATEGORIE_RESPONSABLE_SYSTEME = 'Responsable de système';

    public const CATEGORIE_AUTRE = 'Autre';

    /**
     * Job titles offered by the Employees form.
     *
     * "Commercial" was dropped (no employee held it) and Consultant,
     * Responsable administrative, Responsable RH and Directeur financier
     * added. "Assistante administrative" stays alongside "Responsable
     * administrative" — they are two distinct posts, not a rename.
     *
     * This is a plain VARCHAR validated against these constants, by design
     * (CLAUDE.md §11) — do not "fix" it with a lookup table. Removing a
     * value here only removes it from the FORM: existing rows keep whatever
     * string they were saved with, so retire a value that employees still
     * hold with a migration that moves them, never by editing this list
     * alone.
     */
    public const CATEGORIES = [
        self::CATEGORIE_DIRECTEUR,
        self::CATEGORIE_DIRECTEUR_OPERATIONS,
        self::CATEGORIE_DIRECTEUR_FINANCIER,
        self::CATEGORIE_DIRECTEUR_QUALITE,
        self::CATEGORIE_DIRECTRICE_PEDAGOGIQUE,
        self::CATEGORIE_ENSEIGNANT,
        self::CATEGORIE_COMPTABLE,
        self::CATEGORIE_CONSULTANT,
        self::CATEGORIE_RESPONSABLE_RH,
        self::CATEGORIE_RESPONSABLE_MARKETING,
        self::CATEGORIE_RESPONSABLE_ADMINISTRATIVE,
        self::CATEGORIE_ASSISTANTE_ADMINISTRATIVE,
        self::CATEGORIE_RESPONSABLE_SYSTEME,
        self::CATEGORIE_AUTRE,
    ];

    public const STATUT_ACTIF = 'Actif';

    public const STATUT_INACTIF = 'Inactif';

    public const STATUTS = [
        self::STATUT_ACTIF,
        self::STATUT_INACTIF,
    ];

    public const SEXES = ['Homme', 'Femme'];

    protected $fillable = [
        'reference', 'nom', 'prenom', 'sexe', 'categorie', 'statut',
        'telephone', 'whatsapp', 'email', 'adresse', 'note',
        'date_naissance', 'date_embauche', 'salaire',
        'etablissement_id', 'user_id',
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
        'statut' => self::STATUT_ACTIF,
    ];

    protected function casts(): array
    {
        return [
            'date_naissance' => 'date',
            'date_embauche' => 'date',
            'salaire' => 'decimal:2',
        ];
    }

    /**
     * Media (spatie/laravel-medialibrary — URLs served from /media/<8-char-uuid>/…):
     * - "photo": single staff picture, same convention as Student::photo.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photo')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    /**
     * "thumb": small avatar used in list rows, same rationale as Student.
     * getFirstMediaUrl('photo') (no conversion name) is untouched.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(96)
            ->height(96)
            ->sharpen(10)
            ->nonQueued()
            ->performOnCollections('photo');
    }

    /**
     * The employee's PRIMARY center — the first of `etablissements`, kept in
     * the `etablissement_id` column so existing filters, the Caisse
     * provisioner and every center-scoped query keep working unchanged.
     */
    public function etablissement(): BelongsTo
    {
        return $this->belongsTo(Etablissement::class);
    }

    /**
     * All centers this employee works in (at least one — enforced by the
     * Form Requests). This pivot, not the single column, is what
     * CenterAccessService uses to decide which centers the linked user may
     * see and switch to in the top-bar context switcher.
     */
    public function etablissements(): BelongsToMany
    {
        return $this->belongsToMany(Etablissement::class, 'employee_etablissement')
            ->withTimestamps();
    }

    /**
     * Replaces the employee's center assignment in one place: syncs the
     * pivot and re-points the primary `etablissement_id` column at the first
     * given center (or keeps the current one when it is still among them, so
     * an edit that merely ADDS a center doesn't silently move the employee's
     * base — which would also move its Caisse).
     *
     * @param  list<int>  $etablissementIds
     */
    public function syncEtablissements(array $etablissementIds): void
    {
        $ids = array_values(array_unique(array_map('intval', $etablissementIds)));

        if ($ids === []) {
            return; // "At least one" is a validation rule; never wipe the assignment here.
        }

        $this->etablissements()->sync($ids);

        $primary = in_array((int) $this->etablissement_id, $ids, true)
            ? (int) $this->etablissement_id
            : $ids[0];

        if ((int) $this->etablissement_id !== $primary) {
            $this->forceFill(['etablissement_id' => $primary])->save();
        }

        $this->unsetRelation('etablissements');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function groupes(): HasMany
    {
        return $this->hasMany(Group::class, 'enseignant_id');
    }

    /**
     * Employees selectable for one center: those assigned to it (through the
     * `employee_etablissement` pivot, with the primary column as the legacy
     * fallback — CLAUDE.md §16) PLUS every employee whose login has GLOBAL
     * center access (super-admin, or any role/direct grant of
     * `centers.access-all`).
     *
     * Reason for the second branch: direction accounts (e.g. the CEO) are
     * attached to a single primary center but operate on all of them, so a
     * center-only filter hid them from the Import "Opérateur -> Employé"
     * mapping — the very people whose name appears in the legacy exports.
     */
    public function scopeAvailableForCenter(Builder $query, int $etablissementId): Builder
    {
        return $query->where(function (Builder $q) use ($etablissementId): void {
            $q
                ->whereHas('etablissements', fn ($p) => $p->where('etablissements.id', $etablissementId))
                ->orWhere('employees.etablissement_id', $etablissementId)
                ->orWhereHas('user', fn ($u) => $u
                    ->whereHas('roles', fn ($r) => $r->where('name', Role::SUPER_ADMIN))
                    ->orWhereHas('roles.permissions', fn ($p) => $p->where('name', 'centers.access-all'))
                    ->orWhereHas('permissions', fn ($p) => $p->where('name', 'centers.access-all')));
        });
    }

    public function caisses(): HasMany
    {
        return $this->hasMany(Caisse::class, 'responsable_employee_id');
    }

    public function encaissements(): HasMany
    {
        return $this->hasMany(Encaissement::class, 'agent_id');
    }

    public function depenses(): HasMany
    {
        return $this->hasMany(Depense::class, 'agent_id');
    }

    public function remboursements(): HasMany
    {
        return $this->hasMany(Remboursement::class, 'agent_id');
    }

    public function nomComplet(): string
    {
        return trim("{$this->prenom} {$this->nom}");
    }

    /** Uploaded photo, or the sexe-based default avatar (man/girl) when none exists. */
    public function avatarUrl(): string
    {
        return $this->getFirstMediaUrl('photo')
            ?: asset($this->sexe === 'Femme' ? 'assets/images/avatar/defaultgirl.webp' : 'assets/images/avatar/defaultman.webp');
    }
}
