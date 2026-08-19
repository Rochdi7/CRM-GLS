<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Student (gls-crm-schema.md §5). Parent/guardian contact lives inline by
 * design (no separate parents table). `niveau` is a plain VARCHAR validated
 * against the fixed NIVEAUX list, not a lookup table (deliberate trade-off).
 */
class Student extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use Auditable;

    /** Fixed CEFR sublevels used by GLS — enforce in validation, never a FK. */
    public const NIVEAUX_CEFR = [
        'A1.1', 'A1.2',
        'A2.1', 'A2.2', 'A2.3',
        'B1.1', 'B1.2', 'B1.3',
        'B2.1', 'B2.2', 'B2.3',
    ];

    public const NIVEAU_ARBEIT = 'Arbeit';

    public const NIVEAU_STUDIUM = 'Studium';

    public const NIVEAU_AUSBILDUNG = 'Ausbildung';

    /**
     * German-track destinations, offered alongside the CEFR levels. Each one
     * opens a second dropdown in the forms (see DOMAINES / EXAMEN_TYPES).
     */
    public const NIVEAUX_TRACKS = [
        self::NIVEAU_ARBEIT,
        self::NIVEAU_STUDIUM,
        self::NIVEAU_AUSBILDUNG,
    ];

    /** Everything selectable in the "Niveau" dropdown. */
    public const NIVEAUX = [...self::NIVEAUX_CEFR, ...self::NIVEAUX_TRACKS];

    /**
     * Professional field — asked only for Arbeit / Ausbildung.
     * "Autre" covers anything not listed.
     */
    public const DOMAINES = [
        'Santé et soins infirmiers',
        'Hôtellerie',
        'Cuisine',
        'Chauffeur poids lourd',
        'Mécanique automobile',
        'Mécanique',
        'Électricien',
        'Autre',
    ];

    /** Entrance exam — asked only for Studium. */
    public const EXAMEN_TYPES = ['STK', 'DSH'];

    /** Tracks that ask for a professional field rather than an exam. */
    public const NIVEAUX_AVEC_DOMAINE = [
        self::NIVEAU_ARBEIT,
        self::NIVEAU_AUSBILDUNG,
    ];

    public const SEXES = ['Homme', 'Femme'];

    /** Parent/guardian relation ("Categorie") — fixed list, enforce in validation. */
    public const PARENT_RELATIONS = ['Le père', 'La mère', 'Le parrain'];

    protected $fillable = [
        'reference', 'legacy_ref', 'legacy_source', 'nom', 'prenom', 'sexe', 'date_naissance', 'cin',
        'telephone', 'whatsapp', 'email', 'adresse', 'niveau',
        'domaine', 'examen_type',
        'etablissement_id', 'parent_nom', 'parent_relation', 'parent_sexe',
        'parent_cin', 'parent_telephone', 'parent_whatsapp', 'note',
    ];

    protected function casts(): array
    {
        return [
            'date_naissance' => 'date',
        ];
    }

    /**
     * Media (spatie/laravel-medialibrary — URLs served from /media/<8-char-uuid>/…):
     * - "photo": single profile picture (new upload replaces the old one)
     * - "documents": CIN, contrats, attestations… (multiple files)
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photo')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);

        $this->addMediaCollection('documents');
    }

    /**
     * "thumb": small avatar used in list/show rows so they don't download the
     * full-size upload. nonQueued() because QUEUE_CONNECTION=database has no
     * guaranteed worker running in dev — generates synchronously on upload,
     * same as today's behavior. getFirstMediaUrl('photo') (no conversion
     * name) is untouched and keeps returning the original — no existing URL
     * changes.
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


    public function etablissement(): BelongsTo
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function inscriptions(): HasMany
    {
        return $this->hasMany(Inscription::class);
    }

    public function encaissements(): HasMany
    {
        return $this->hasMany(Encaissement::class);
    }

    public function remboursements(): HasMany
    {
        return $this->hasMany(Remboursement::class, 'beneficiaire_id');
    }

    public function nomComplet(): string
    {
        return trim("{$this->prenom} {$this->nom}");
    }

    /** Age in full years as of today, or null when date_naissance is unknown. */
    public function age(): ?int
    {
        return $this->date_naissance?->age;
    }

    /** Arbeit / Ausbildung ask for a professional field. */
    public static function niveauDemandeDomaine(?string $niveau): bool
    {
        return in_array($niveau, self::NIVEAUX_AVEC_DOMAINE, true);
    }

    /** Studium asks for an entrance exam (STK / DSH). */
    public static function niveauDemandeExamen(?string $niveau): bool
    {
        return $niveau === self::NIVEAU_STUDIUM;
    }

    /**
     * The orientation actually recorded for this student ("Cuisine", "DSH"…),
     * or null for a plain CEFR level. Used by the list/detail screens.
     */
    public function orientation(): ?string
    {
        if (self::niveauDemandeDomaine($this->niveau)) {
            return $this->domaine;
        }

        return self::niveauDemandeExamen($this->niveau) ? $this->examen_type : null;
    }

    /** Uploaded photo, or the sexe-based default avatar (man/girl) when none exists. */
    public function avatarUrl(): string
    {
        return $this->getFirstMediaUrl('photo')
            ?: asset($this->sexe === 'Femme' ? 'assets/images/avatar/defaultgirl.webp' : 'assets/images/avatar/defaultman.webp');
    }
}
