<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Master staff record (gls-crm-schema.md §4) — teachers are employees,
 * `categorie` distinguishes roles (plain VARCHAR, validated in requests).
 *
 * Creating an Employee auto-generates its login credentials via
 * EmployeeObserver → EmployeeCredentialService (structure doc §5).
 */
class Employee extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

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

    public function etablissement(): BelongsTo
    {
        return $this->belongsTo(Etablissement::class);
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
}
