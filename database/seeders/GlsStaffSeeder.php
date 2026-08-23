<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Role;
use App\Models\User;
use App\Services\CaisseProvisioner;
use App\Support\Authorization\PermissionRegistry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

/**
 * REAL GLS staff — the @glszentrum.com mailboxes, matched against the
 * « GLS_Employes_Tous_Centres » roster (one sheet per center).
 *
 * This is NOT demo data: these are real people with real e-mail addresses, so
 * it is safe (and intended) to run on production — it ships as part of
 * DatabaseSeeder. Fully idempotent: keyed on the e-mail address, so re-running
 * updates the existing rows instead of duplicating them.
 *
 *     C:\php84\php.exe artisan db:seed --class=GlsStaffSeeder
 *
 * What it does per person:
 *  - creates/updates the Employee (nom, prénom, catégorie, téléphone, e-mail)
 *  - attaches EVERY center they appear in via `employee_etablissement`
 *    (CLAUDE.md §16 — an employee may work in several centers and must have
 *    at least one). The PRIMARY center (`employees.etablissement_id`, where
 *    the Caisse lives) is the first one listed below: for someone holding a
 *    Directeur post in one center and an assistant post in another, the
 *    Directeur center is listed first on purpose.
 *  - creates/updates the User login (username = the e-mail local part),
 *    assigns the role derived from the catégorie, and forces a password
 *    change on first sign-in.
 *  - provisions the till (CaisseProvisioner), same as the Employees screen.
 *
 * Two accounts are seeded as `super-admin` (see SUPER_ADMINS): the CEO
 * Mohammed Rafik (rafik@glszentrum.com) and rochdi.karouali@glszentrum.com.
 * ⚠ rafik@ (Mohammed) and amine.rafik@ (Amine) are TWO BROTHERS, not one
 * person with two mailboxes — two employee records, two tills, and only
 * rafik@ is the super-admin CEO.
 *
 * ⚠ Passwords: a NEW user gets a random one-time password, printed ONCE to
 * the console when the seeder runs. An EXISTING user's password is never
 * touched, so re-seeding cannot lock anyone out. Copy the printed list, hand
 * the credentials over, and do not keep it.
 *
 * Four mailboxes have no counterpart on the roster spreadsheet
 * (achraf.elyounani, oumnya.salim, yassine.ait-lachguer, zineb.hmimas).
 * They are seeded anyway with the name derived from the address, catégorie
 * « Autre », the head office (GLS Marrakech) as their center and NO role —
 * so they cannot do anything until someone sets their real post and grants
 * a role on the Autorisations screen. `note` stays a free-text field for
 * humans: the seeder never writes to it.
 */
final class GlsStaffSeeder extends Seeder
{
    // catégorie → default role now lives in ONE place shared with
    // EmployeeObserver: PermissionRegistry::defaultRoleFor(). « Autre »
    // maps to null (no role ⇒ no access until granted by hand) and a role
    // already on the account is never overwritten — see run().

    /**
     * Comptes qui reçoivent le rôle `super-admin` — accès total, contourne
     * toutes les permissions via Gate::before (CLAUDE.md §16).
     *
     * ⚠ N'ajoutez une adresse ici qu'en connaissance de cause : un
     * super-admin voit et modifie TOUT, tous centres confondus. Le seul
     * garde-fou qui subsiste pour lui est la validation des transferts de
     * caisse, explicitement exclue du bypass (CLAUDE.md §11).
     *
     * @var list<string>
     */
    private const SUPER_ADMINS = [
        // Mohammed Rafik — CEO / fondateur (boîte courte historique). C'est
        // aussi le compte créé par AdminUserSeeder (même adresse).
        'rafik@glszentrum.com',
        // Rochdi Karouali — Responsable de système. Compte VISIBLE dans
        // l'interface : le compte technique du développeur est un compte
        // SÉPARÉ (App\Support\Access\HiddenAccount), provisionné par
        // MaintainerUserSeeder et masqué de toutes les listes.
        'rochdi.karouali@glszentrum.com',
    ];

    /**
     * The roster, as read from the spreadsheet.
     *
     * email => [prénom, nom, sexe, catégorie, téléphone|null, [centres…]]
     *
     * `sexe` is not on the spreadsheet — it is derived from the first name
     * and matters visually: Employee::photoUrl() falls back to
     * defaultgirl.webp / defaultman.webp based on it, so leaving it null
     * shows every woman with a man's avatar. Correct any mistake on the
     * Employees screen; a re-seed will not overwrite it (see run()).
     *
     * The centre list is ORDERED: the first entry becomes the primary center.
     *
     * @return array<string, array{0:string,1:string,2:string,3:string,4:?string,5:list<string>}>
     */
    private function roster(): array
    {
        $DIR = Employee::CATEGORIE_DIRECTEUR;
        $RAD = Employee::CATEGORIE_RESPONSABLE_ADMINISTRATIVE;
        $AAD = Employee::CATEGORIE_ASSISTANTE_ADMINISTRATIVE;
        $SYS = Employee::CATEGORIE_RESPONSABLE_SYSTEME;
        $AUT = Employee::CATEGORIE_AUTRE;

        $H = 'Homme';
        $F = 'Femme';

        return [
            // ---- GLS Marrakech (siège) ----------------------------------
            // CEO — super-admin (voir SUPER_ADMINS). « rafik@ » et
            // « amine.rafik@ » sont DEUX FRÈRES, donc deux comptes et deux
            // fiches employé distinctes : ne pas les fusionner.
            // Rattaché au siège : le super-admin voit de toute façon tous les
            // centres via Gate::before, mais son centre principal (et donc sa
            // caisse) doit rester exact.
            'rafik@glszentrum.com' => ['Mohammed', 'Rafik', $H, $DIR, '+212 661 95 93 41', ['GLS Marrakech']],
            // Le frère, Amine — même nom de famille, compte et caisse séparés.
            'amine.rafik@glszentrum.com' => ['Amine', 'Rafik', $H, $DIR, '+212 661 95 93 41', ['GLS Marrakech']],
            'latifa.abouelfath@glszentrum.com' => ['Latifa', 'Abou Elfath', $F, $DIR, '+212 669 72 87 05', ['GLS Marrakech']],
            // Responsable de système — la SEULE catégorie dont le rôle par
            // défaut est `super-admin` (PermissionRegistry::defaultRoleFor()),
            // donc cohérente avec sa présence dans SUPER_ADMINS ci-dessus.
            // Compte VISIBLE : à ne pas confondre avec le compte technique du
            // développeur (App\Support\Access\HiddenAccount), qui porte une
            // autre adresse et n'apparaît nulle part dans l'interface.
            'rochdi.karouali@glszentrum.com' => ['Rochdi', 'Karouali', $H, $SYS, '+212 689 98 10 22', ['GLS Marrakech']],
            'ichrak.fakroune@glszentrum.com' => ['Ichrak', 'Fakroune', $F, $AAD, '+212 655 61 53 65', ['GLS Marrakech']],
            'mustapha.benlmekki@glszentrum.com' => ['Mustapha', 'Ben Lmekki', $H, $AAD, '+212 707 04 65 81', ['GLS Marrakech']],

            // Directeur à Marrakech, Responsable administrative à Salé —
            // Marrakech en premier ⇒ centre principal (sa caisse y est).
            'abderrahimelmoulabbi@glszentrum.com' => ['Abderrahim', 'Elmoulabbi', $H, $DIR, '+212 603 86 52 51', ['GLS Marrakech', 'GLS Salé']],

            // ---- GLS Rabat ----------------------------------------------
            'elmehdi.bakhach@glszentrum.com' => ['El Mehdi', 'Bakhach', $H, $DIR, '+212 677 65 77 02', ['GLS Rabat']],
            // « Directeur pédagogique » sur la feuille Rabat : la seule
            // constante pédagogique du modèle est au féminin (Directrice
            // pédagogique), d'où Directeur ici — à revoir si une constante
            // masculine est ajoutée à Employee::CATEGORIES.
            'yassine.elbadaoui@glszentrum.com' => ['Yassine', 'El Badaoui', $H, $DIR, '+212 673 64 07 89', ['GLS Rabat']],
            'hafsa.elkhatabi@glszentrum.com' => ['Hafssa', 'Elkhattabi', $F, $RAD, '+212 669 82 92 01', ['GLS Rabat']],
            'khadija.manssouri@glszentrum.com' => ['Khadija', 'Manssouri', $F, $AAD, '+212 772 12 95 99', ['GLS Rabat']],

            // ---- GLS Casablanca -----------------------------------------
            'maria.jelloul@glszentrum.com' => ['Maria Nezha', 'Jalloul', $F, $DIR, null, ['GLS Casablanca']],
            'ikram.boussila@glszentrum.com' => ['Ikram', 'Boussila', $F, $AAD, null, ['GLS Casablanca']],

            // ---- GLS Kénitra --------------------------------------------
            // Directeur sur la feuille Kénitra ET sur celle de Casablanca —
            // Kénitra en premier ⇒ centre principal.
            'yassine.ouledlaghzal@glszentrum.com' => ['Yassine', 'Ouled Laghzal', $H, $DIR, null, ['GLS Kénitra', 'GLS Casablanca']],
            'khaoula.elghanoui@glszentrum.com' => ['Khaoula', 'El Ghanoui', $F, $AAD, null, ['GLS Kénitra']],

            // ---- GLS Agadir ---------------------------------------------
            'mouna.zakri@glszentrum.com' => ['Mouna', 'Zakri', $F, $DIR, '+212 777 61 78 58', ['GLS Agadir']],
            'saad.soutafi@glszentrum.com' => ['Saad', 'Soutafi', $H, $AAD, '+212 661 73 92 61', ['GLS Agadir']],

            // ---- GLS Salé -----------------------------------------------
            // Sur les feuilles Salé ET Online — Salé en premier (site physique).
            'soufiane.elmatmour@glszentrum.com' => ['Soufiane', 'El Matmour', $H, $AAD, '+212 767 87 15 92', ['GLS Salé', 'GLS Online']],

            // ---- GLS Online ---------------------------------------------
            'ahmed.khadimerrahman@glszentrum.com' => ['Ahmed', 'Khadimerrahman', $H, $AAD, null, ['GLS Online']],
            'hiba.messaoudi@glszentrum.com' => ['Hiba', 'Messaoudi', $F, $AAD, null, ['GLS Online']],
            'kaoutar.achibane@glszentrum.com' => ['Kaoutar', 'Achibane', $F, $AAD, null, ['GLS Online']],
            'oumayma.sayedi@glszentrum.com' => ['Oumayma', 'Sayedi', $F, $AAD, null, ['GLS Online']],
            // Sur les feuilles Online ET Rabat.
            'fatine.barnicha@glszentrum.com' => ['Fatine', 'Barnicha', $F, $AAD, null, ['GLS Online', 'GLS Rabat']],

            // ---- Boîtes sans correspondance sur le fichier --------------
            // Nom déduit de l'adresse, catégorie « Autre », siège par défaut.
            'achraf.elyounani@glszentrum.com' => ['Achraf', 'Elyounani', $H, $AUT, null, ['GLS Marrakech']],
            'oumnya.salim@glszentrum.com' => ['Oumnya', 'Salim', $F, $AUT, null, ['GLS Marrakech']],
            'yassine.ait-lachguer@glszentrum.com' => ['Yassine', 'Ait-Lachguer', $H, $AUT, null, ['GLS Marrakech']],
            'zineb.hmimas@glszentrum.com' => ['Zineb', 'Hmimas', $F, $AUT, null, ['GLS Marrakech']],
        ];
    }

    public function run(): void
    {
        /** @var array<string, int> $centres */
        $centres = Etablissement::query()->pluck('id', 'nom_centre')->all();

        if ($centres === []) {
            $this->command?->error('Aucun établissement en base — lancez ReferentialDataSeeder en premier.');

            return;
        }

        $provisioner = app(CaisseProvisioner::class);
        $nouveaux = [];
        $existants = 0;

        foreach ($this->roster() as $email => [$prenom, $nom, $sexe, $categorie, $telephone, $nomsCentres]) {
            $ids = [];

            foreach ($nomsCentres as $nomCentre) {
                if (! isset($centres[$nomCentre])) {
                    $this->command?->warn("Centre inconnu « {$nomCentre} » pour {$email} — ignoré.");

                    continue;
                }

                $ids[] = $centres[$nomCentre];
            }

            if ($ids === []) {
                $this->command?->warn("Aucun centre valide pour {$email} — employé ignoré.");

                continue;
            }

            $estNouveau = ! User::query()->where('email', $email)->exists();

            // Le mot de passe n'est écrit QU'à la création : re-seeder ne doit
            // jamais réinitialiser le mot de passe de quelqu'un.
            $motDePasse = $estNouveau ? Str::password(14) : null;

            $user = User::query()->firstOrNew(['email' => $email]);
            $user->fill([
                'name' => "{$prenom} {$nom}",
                'username' => $this->usernameLibre(Str::before($email, '@'), $user->id),
                'is_active' => true,
            ]);

            if ($estNouveau) {
                $user->password = Hash::make($motDePasse);
                $user->must_change_password = true;
            }

            $user->save();

            if (in_array($email, self::SUPER_ADMINS, true)) {
                // Le super-admin est décidé ici, pas déduit de la catégorie :
                // on l'attribue même si le compte porte déjà un autre rôle.
                // assignRole() est idempotent et n'enlève rien d'autre.
                $role = Role::findOrCreate(Role::SUPER_ADMIN, 'web');

                if (! $user->hasRole($role)) {
                    $user->assignRole($role);
                }
            } else {
                $role = PermissionRegistry::defaultRoleFor($categorie);

                // Ne jamais retirer un rôle attribué à la main (une promotion
                // décidée sur l'écran Autorisations doit survivre au re-seed) :
                // on n'assigne le rôle par défaut que si le compte n'en a aucun.
                if ($role !== null && $user->roles()->doesntExist()) {
                    $user->assignRole($role);
                }
            }

            // user_id explicite ⇒ EmployeeObserver ne génère PAS un second
            // login pour cet employé (CLAUDE.md §11).
            $employee = Employee::query()->firstOrNew(['user_id' => $user->id]);
            $employee->fill([
                'nom' => $nom,
                'prenom' => $prenom,
                // Déduit du prénom (absent du fichier) : ne l'écrase que s'il
                // est encore vide, pour qu'une correction faite à la main sur
                // l'écran Employés survive au re-seed.
                'sexe' => $employee->sexe ?: $sexe,
                'categorie' => $categorie,
                'statut' => Employee::STATUT_ACTIF,
                'telephone' => $telephone,
                'email' => $email,
                'etablissement_id' => $ids[0],
                // `note` est laissée au champ libre de l'écran Employés : le
                // seeder n'y écrit jamais rien.
            ]);

            if ((string) $employee->reference === '') {
                $employee->reference = $this->prochaineReference();
            }

            $employee->save();

            $employee->syncEtablissements($ids);

            // Idempotent : ne crée la caisse que si elle n'existe pas encore.
            $provisioner->provisionFor($employee);

            if ($estNouveau) {
                $nouveaux[] = [$email, $user->username, $motDePasse];
            } else {
                $existants++;
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->effacerAncienneNote();

        $this->rapporter($nouveaux, $existants);
    }

    /**
     * Une version précédente de ce seeder écrivait « Absent du fichier
     * GLS_Employes_Tous_Centres — à confirmer. » dans la note des employés
     * hors roster. La note est désormais un champ purement humain, donc on
     * efface cet ancien texte — et uniquement lui : une note saisie à la main
     * n'est jamais touchée.
     */
    private function effacerAncienneNote(): void
    {
        Employee::query()
            ->where('note', 'like', 'Absent du fichier GLS_Employes_Tous_Centres%')
            ->update(['note' => null]);
    }

    /**
     * A username collision can only happen against a pre-existing account
     * (the local parts are unique among themselves) — suffix rather than
     * fail the whole seed.
     */
    private function usernameLibre(string $souhaite, ?int $userId): string
    {
        $username = $souhaite;
        $i = 1;

        while (
            User::query()
                ->where('username', $username)
                ->when($userId !== null, fn ($q) => $q->whereKeyNot($userId))
                ->exists()
        ) {
            $username = $souhaite.$i++;
        }

        return $username;
    }

    /**
     * Même format que ReferenceGenerator::make('EMP', 'employees') — EMP-001,
     * EMP-002… — pour que les fiches seedées soient indistinguables de celles
     * créées depuis l'écran Employés.
     *
     * On n'appelle pas ReferenceGenerator directement : son compteur part de
     * max(id), qui ne bouge pas entre deux créations dans la même boucle et
     * produirait des collisions. On avance donc à partir du plus grand numéro
     * EMP- déjà stocké.
     */
    private function prochaineReference(): string
    {
        static $prochain = null;

        if ($prochain === null) {
            // withoutGlobalScopes() : la référence doit être unique sur TOUTE
            // la table. Le compte technique masqué (HiddenAccountScope) est
            // invisible dans l'interface mais occupe bien une ligne — l'ignorer
            // ici rendrait sa référence réattribuable à un vrai employé.
            $max = Employee::query()
                ->withoutGlobalScopes()
                ->where('reference', 'like', 'EMP-%')
                ->pluck('reference')
                ->map(fn ($reference) => (int) preg_replace('/\D/', '', (string) $reference))
                ->max();

            $prochain = ((int) $max) + 1;
        }

        do {
            $reference = sprintf('EMP-%03d', $prochain++);
        } while (
            Employee::query()
                ->withoutGlobalScopes()
                ->where('reference', $reference)
                ->exists()
        );

        return $reference;
    }

    /**
     * @param  list<array{0:string,1:string,2:string}>  $nouveaux
     */
    private function rapporter(array $nouveaux, int $existants): void
    {
        $this->command?->info(sprintf(
            '%d employé(s) créé(s), %d mis à jour.',
            count($nouveaux),
            $existants,
        ));

        if ($nouveaux === []) {
            return;
        }

        $this->command?->warn(
            'Mots de passe à usage unique — affichés UNE SEULE FOIS, '
            .'changement obligatoire à la première connexion :'
        );

        $this->command?->table(['E-mail', 'Identifiant', 'Mot de passe'], $nouveaux);
    }
}
