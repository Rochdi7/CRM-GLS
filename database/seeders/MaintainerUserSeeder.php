<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Shared\Support\ReferenceGenerator;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Role;
use App\Models\User;
use App\Services\CaisseProvisioner;
use App\Support\Access\HiddenAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

/**
 * The maintainer / developer super-admin — invisible in the interface.
 *
 * This is the account described by App\Support\Access\HiddenAccount: the
 * developer of the system needs full access to diagnose problems on the live
 * database, but he is not GLS staff and must not show up in the Employés,
 * Utilisateurs, Caisses or Rôles screens (HiddenAccountScope + the explicit
 * filters in GetUsersList / GetCaissesList / GetComptesCaisse / GetRolesList).
 *
 * ⚠ Hidden is NOT untraceable. Everything this account does is written to the
 * audit journal exactly like anyone else's actions — same IP, same frozen
 * `causer_label`. The journal page merely collapses it behind the
 * « Inclure le compte technique » toggle. Never turn this into a
 * write-time skip (CLAUDE.md §11, docs/audit-journal.md).
 *
 * Idempotent, keyed on HiddenAccount::EMAIL — safe to re-run on production,
 * and it never resets an existing password.
 *
 * Password: MAINTAINER_PASSWORD if set, otherwise a random one-time password
 * printed ONCE on creation, same convention as GlsStaffSeeder.
 */
final class MaintainerUserSeeder extends Seeder
{
    public function run(): void
    {
        $centre = Etablissement::query()->where('nom_centre', 'GLS Marrakech')->first()
            ?? Etablissement::query()->orderBy('id')->first();

        if ($centre === null) {
            $this->command?->error('Aucun établissement en base — lancez ReferentialDataSeeder en premier.');

            return;
        }

        $estNouveau = ! User::query()->where('email', HiddenAccount::EMAIL)->exists();

        // Le mot de passe n'est écrit QU'à la création : re-seeder ne doit
        // jamais réinitialiser le compte de maintenance en pleine intervention.
        $motDePasse = $estNouveau
            ? (string) (env('MAINTAINER_PASSWORD') ?: Str::password(16))
            : null;

        $user = User::query()->firstOrNew(['email' => HiddenAccount::EMAIL]);
        $user->fill([
            'name' => 'Rochdi Karouali',
            'username' => 'dev',
            'is_active' => true,
        ]);

        if ($estNouveau) {
            $user->password = Hash::make($motDePasse);
            // Un mot de passe généré ici ne doit pas survivre à la première
            // connexion — c'est le compte le plus privilégié du système.
            $user->must_change_password = true;
        }

        $user->save();

        $role = Role::findOrCreate(Role::SUPER_ADMIN, 'web');

        if (! $user->hasRole($role)) {
            $user->assignRole($role);
        }

        // Une fiche employé est nécessaire même pour un compte qui ne fait pas
        // d'école : les écrans finance et l'audit rattachent chaque action à
        // une identité d'employé (agent_id / requested_by). Sans elle, la
        // moindre page qui déréférence $user->employee tomberait en erreur
        // pour ce compte précis — celui qu'on utilise justement pour
        // diagnostiquer les erreurs.
        //
        // withoutGlobalScopes() : HiddenAccountScope masque cette ligne, donc
        // sans cela firstOrNew() ne la retrouverait jamais et créerait un
        // doublon (et une caisse de plus) à chaque exécution.
        $employee = Employee::query()
            ->withoutGlobalScopes()
            ->firstOrNew(['user_id' => $user->id]);

        $employee->fill([
            'nom' => 'Karouali',
            'prenom' => 'Rochdi',
            'sexe' => 'Homme',
            // Responsable de système : la seule catégorie dont le rôle par
            // défaut est `super-admin` (PermissionRegistry::defaultRoleFor()),
            // donc cohérente avec le rôle attribué ci-dessus si un jour
            // EmployeeObserver repasse sur la fiche.
            'categorie' => Employee::CATEGORIE_RESPONSABLE_SYSTEME,
            'statut' => Employee::STATUT_ACTIF,
            'email' => HiddenAccount::EMAIL,
            'etablissement_id' => $centre->id,
        ]);

        if ((string) $employee->reference === '') {
            // ReferenceGenerator passe par DB::table(), donc il voit la table
            // entière et ne peut pas réattribuer une référence existante.
            $employee->reference = ReferenceGenerator::make('EMP', 'employees');
        }

        $employee->save();
        $employee->syncEtablissements([$centre->id]);

        // Idempotent : ne crée la caisse que si elle n'existe pas déjà.
        app(CaisseProvisioner::class)->provisionFor($employee);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        if ($estNouveau) {
            $this->command?->warn('Compte technique (masqué de l\'interface) créé :');
            $this->command?->table(
                ['E-mail', 'Identifiant', 'Mot de passe'],
                [[HiddenAccount::EMAIL, $user->username, $motDePasse]],
            );
        } else {
            $this->command?->info('Compte technique déjà présent — mot de passe inchangé.');
        }
    }
}
