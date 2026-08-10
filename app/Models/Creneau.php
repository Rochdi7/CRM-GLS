<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Weekly recurring schedule slot (emploi du temps) — "this group meets every
 * Monday 10:00-12:00 in room X". Distinct from Seance (one row per dated
 * occurrence, used for attendance): a créneau is the template,
 * GenererSeancesDepuisCreneau generates/syncs the actual dated séances.
 */
class Creneau extends Model
{
    use HasFactory;

    protected $table = 'creneaux';

    public const JOURS = [
        1 => 'Lundi',
        2 => 'Mardi',
        3 => 'Mercredi',
        4 => 'Jeudi',
        5 => 'Vendredi',
        6 => 'Samedi',
        7 => 'Dimanche',
    ];

    protected $fillable = [
        'group_id', 'jour_semaine', 'heure_debut', 'heure_fin',
        'enseignant_id', 'salle_id',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function enseignant(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'enseignant_id');
    }

    public function salle(): BelongsTo
    {
        return $this->belongsTo(Salle::class);
    }

    public function seances(): HasMany
    {
        return $this->hasMany(Seance::class);
    }
}
