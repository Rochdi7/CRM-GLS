<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
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
 */
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

    public const CATEGORIE_COMMERCIAL = 'Commercial';

    public const CATEGORIE_ENSEIGNANT = 'Enseignant';

    public const CATEGORIE_COMPTABLE = 'Comptable';

    public const CATEGORIE_RESPONSABLE_MARKETING = 'Responsable Marketing';

    public const CATEGORIE_ASSISTANTE_ADMINISTRATIVE = 'Assistante administrative';

    public const CATEGORIE_DIRECTEUR_OPERATIONS = 'Directeur des opérations';

    public const CATEGORIE_DIRECTRICE_PEDAGOGIQUE = 'Directrice pédagogique';

    public const CATEGORIE_DIRECTEUR_QUALITE = 'Directeur Qualité et Amélioration continue';

    public const CATEGORIE_AUTRE = 'Autre';

    /** Order mirrors the screenshots (pic 1 & 2). */
    public const CATEGORIES = [
        self::CATEGORIE_DIRECTEUR,
        self::CATEGORIE_COMMERCIAL,
        self::CATEGORIE_ENSEIGNANT,
        self::CATEGORIE_COMPTABLE,
        self::CATEGORIE_RESPONSABLE_MARKETING,
        self::CATEGORIE_ASSISTANTE_ADMINISTRATIVE,
        self::CATEGORIE_DIRECTEUR_OPERATIONS,
        self::CATEGORIE_DIRECTRICE_PEDAGOGIQUE,
        self::CATEGORIE_DIRECTEUR_QUALITE,
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
