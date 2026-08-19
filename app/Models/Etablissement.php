<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Branch / center (gls-crm-schema.md §1). Everything else in the system is
 * scoped to a branch through a direct or indirect etablissement_id.
 */
class Etablissement extends Model
{
    use Auditable;
    use HasFactory;

    protected $fillable = [
        'nom_centre', 'ville', 'adresse', 'ice', 'telephone', 'email', 'siege_social',
    ];

    protected function casts(): array
    {
        return [
            'siege_social' => 'boolean',
        ];
    }

    public function salles(): HasMany
    {
        return $this->hasMany(Salle::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }

    public function inscriptions(): HasMany
    {
        return $this->hasMany(Inscription::class);
    }

    public function caisses(): HasMany
    {
        return $this->hasMany(Caisse::class);
    }
}
