<?php

declare(strict_types=1);

namespace App\Domain\Groups\Support;

use App\Models\Creneau;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\Seance;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

/**
 * « Portée enseignant » (24/09/2026) — la SEULE définition de ce qu'un
 * enseignant voit : SES groupes, leurs étudiants, leurs séances et leur
 * emploi du temps, jamais le reste du centre.
 *
 * Un utilisateur est restreint quand il tient `groups.view-own` SANS
 * `groups.view` : c'est une question de PERMISSIONS, jamais de rôle (§16).
 * Un super-admin répond oui à `groups.view` via Gate::before, donc n'est
 * jamais restreint.
 *
 * « Ses groupes » = les groupes dont il est l'enseignant titulaire
 * (`groups.enseignant_id`) OU dont il tient un créneau OUVERT
 * (`creneaux.enseignant_id`, `date_fin` NULL — un groupe peut avoir deux
 * profs sur deux jours). Une séance est à lui quand elle appartient à l'un de
 * ses groupes OU qu'il l'assure lui-même (`seances.enseignant_id`, un
 * remplacement). L'identité vient de `$user->employee`, jamais du client.
 *
 * Chaque read-model applique le scope ; chaque policy revérifie la ligne
 * (`couvre*`) — un id forgé dans l'URL n'ouvre pas le groupe d'un collègue.
 */
final class PorteeEnseignant
{
    public const PERMISSION = 'groups.view-own';

    /**
     * NULL = aucune restriction. Sinon l'id employé de l'enseignant — 0 pour
     * un compte restreint sans fiche employé, qui ne voit alors RIEN (jamais
     * tout : c'est le sens d'erreur qui ouvre).
     */
    public static function enseignantId(?User $user): ?int
    {
        if ($user === null || $user->can('groups.view') || ! $user->can(self::PERMISSION)) {
            return null;
        }

        return (int) ($user->employee?->id ?? 0);
    }

    public static function estRestreint(?User $user): bool
    {
        return self::enseignantId($user) !== null;
    }

    /** Sous-requête des ids de groupe de l'enseignant. */
    public static function groupeIds(int $enseignantId): QueryBuilder
    {
        return DB::table('groups')
            ->select('id')
            ->where('enseignant_id', $enseignantId)
            ->union(
                DB::table('creneaux')
                    ->select('group_id')
                    ->where('enseignant_id', $enseignantId)
                    ->whereNull('date_fin'),
            );
    }

    /** @param  Builder<Group>  $query */
    public static function scopeGroupes(Builder $query, ?User $user, string $colonne = 'groups.id'): Builder
    {
        $id = self::enseignantId($user);

        return $id === null ? $query : $query->whereIn($colonne, self::groupeIds($id));
    }

    /** @param  Builder<Seance>  $query */
    public static function scopeSeances(Builder $query, ?User $user): Builder
    {
        $id = self::enseignantId($user);

        return $id === null ? $query : $query->where(fn (Builder $q) => $q
            ->whereIn('seances.group_id', self::groupeIds($id))
            ->orWhere('seances.enseignant_id', $id));
    }

    /** @param  Builder<Creneau>  $query */
    public static function scopeCreneaux(Builder $query, ?User $user): Builder
    {
        $id = self::enseignantId($user);

        return $id === null ? $query : $query->where(fn (Builder $q) => $q
            ->whereIn('creneaux.group_id', self::groupeIds($id))
            ->orWhere('creneaux.enseignant_id', $id));
    }

    /**
     * Ses étudiants = ceux qui ont une inscription ACTIVE dans l'un de ses
     * groupes. Un étudiant parti (Changement/Annulée) n'est plus le sien.
     *
     * @param  Builder<Student>  $query
     */
    public static function scopeStudents(Builder $query, ?User $user): Builder
    {
        $id = self::enseignantId($user);

        return $id === null ? $query : $query->whereHas('inscriptions', fn (Builder $q) => $q
            ->where('statut', Inscription::STATUT_ACTIVE)
            ->whereIn('group_id', self::groupeIds($id)));
    }

    public static function couvreGroupe(?User $user, Group $group): bool
    {
        return ! self::estRestreint($user)
            || self::scopeGroupes(Group::query()->whereKey($group->getKey()), $user)->exists();
    }

    public static function couvreSeance(?User $user, Seance $seance): bool
    {
        return ! self::estRestreint($user)
            || self::scopeSeances(Seance::query()->whereKey($seance->getKey()), $user)->exists();
    }

    public static function couvreCreneau(?User $user, Creneau $creneau): bool
    {
        return ! self::estRestreint($user)
            || self::scopeCreneaux(Creneau::query()->whereKey($creneau->getKey()), $user)->exists();
    }
}
