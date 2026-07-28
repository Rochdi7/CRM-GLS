<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Academic year (gls-crm-schema.md §2), e.g. "2025/2026".
 */
class AnneeScolaire extends Model
{
    use HasFactory;

    protected $table = 'annees_scolaires';

    protected $fillable = [
        'nom', 'date_debut', 'date_fin', 'par_defaut', 'inscription_ouverte',
    ];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin' => 'date',
            'par_defaut' => 'boolean',
            'inscription_ouverte' => 'boolean',
        ];
    }

    public function scopeParDefaut(Builder $query): Builder
    {
        return $query->where('par_defaut', true);
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
