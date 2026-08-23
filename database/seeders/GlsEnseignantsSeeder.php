<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Etablissement;
use App\Services\CaisseProvisioner;
use Illuminate\Database\Seeder;

/**
 * REAL GLS teaching staff — the 66 « Enseignant » rows of
 * « GLS_Employes_Tous_Centres », one sheet per center.
 *
 * Companion to GlsStaffSeeder, kept separate for one reason: the teachers
 * have NO e-mail address on the roster (column E holds a phone number or
 * nothing at all). GlsStaffSeeder is keyed on the e-mail — that is what makes
 * it idempotent and what becomes the login — so teachers cannot go through
 * it. Here the identity key is (nom, prénom) instead.
 *
 *     C:\php84\php.exe artisan db:seed --class=GlsEnseignantsSeeder
 *
 * ⚠ **No login is created.** Teachers are seeded as Employee records only:
 * they show up in the Employees list, can be assigned to groups and take
 * attendance registers as a `group_enseignants` row, but they cannot sign
 * in. That is deliberate — inventing `@gls-crm.local` placeholder addresses
 * would produce 63 unusable accounts with real passwords. When a teacher
 * genuinely needs access, give them a real address on the Employees screen
 * and the normal credential flow takes over.
 *
 * Because of that, this seeder writes with `saveQuietly()` and provisions the
 * till by hand: EmployeeObserver::created() auto-creates a User whenever
 * `user_id` is null, which is exactly what must NOT happen here.
 *
 * Idempotent: keyed on (nom, prénom), so re-running updates instead of
 * duplicating, and it never overwrites a `sexe`, a phone or a centre
 * assignment corrected by hand afterwards.
 *
 * ── Roster corrections ────────────────────────────────────────────────────
 * The spreadsheet has 66 teacher rows but only 63 distinct people. Three
 * merges, each documented at its entry below:
 *
 *  1. **Ibrahim Dahri** — on the Kénitra sheet (PR20, no phone) AND the Salé
 *     sheet (PR10, +212 631 81 64 41). Treated as ONE teacher working in
 *     both centers, same as the multi-center admin staff.
 *  2. **Adil Abdelkrim / Abdelkrim Adil** — Marrakech PR18 and PR19 carry the
 *     IDENTICAL phone (+212 708 08 01 11) with prénom and nom swapped: one
 *     person entered twice, not two colleagues who happen to share a line.
 *  3. **Zineb Ayche** — three rows on GLS Online (PR20, PR22, PR25), all
 *     without a phone. Kept once. (Ghita Ayche, PR25, is a separate person
 *     and is kept.)
 *
 * Two rows also look like nom/prénom entered in the wrong columns —
 * « Seffar | Mehdi » and « Regragui | Tarik ». They are transcribed as
 * written rather than guessed at; fix them on the Employees screen if wrong
 * (a re-seed will then re-create them under the spreadsheet spelling, so
 * correct the spreadsheet too).
 *
 * `sexe` is not on the roster either — derived from the first name, since
 * Employee::photoUrl() picks defaultgirl/defaultman from it. A value already
 * stored is never overwritten.
 */
final class GlsEnseignantsSeeder extends Seeder
{
    /**
     * email => … does NOT apply here (no addresses). Shape is:
     *
     *   [prénom, nom, sexe, téléphone|null, [centres…]]
     *
     * The centre list is ORDERED: the first entry becomes the primary center
     * (`employees.etablissement_id`, where the Caisse lives).
     *
     * @return list<array{0:string,1:string,2:string,3:?string,4:list<string>}>
     */
    private function roster(): array
    {
        $H = 'Homme';
        $F = 'Femme';

        return [
            // ---- GLS Marrakech ------------------------------------------
            ['Abdessamad', 'Maali', $H, '+212 656 76 43 05', ['GLS Marrakech']],
            // Fusion PR18 + PR19 : même téléphone, prénom/nom inversés.
            ['Adil', 'Abdelkrim', $H, '+212 708 08 01 11', ['GLS Marrakech']],
            ['Ouassima', 'Zmara', $F, '+212 659 81 90 57', ['GLS Marrakech']],
            ['Khalid', 'Samih', $H, '+212 623 92 50 10', ['GLS Marrakech']],
            ['Mohamed', 'Jail', $H, '+212 618 33 35 48', ['GLS Marrakech']],
            ['Hanafi', 'Elahyane', $H, '+212 648 34 47 45', ['GLS Marrakech']],
            ['Ismail', 'Abdellhadi', $H, '+212 619 98 26 74', ['GLS Marrakech']],
            ['Nizar', 'Dhibi', $H, '+212 610 38 06 97', ['GLS Marrakech']],
            ['Moulay Abdellah', 'Tber', $H, '+212 622 99 38 66', ['GLS Marrakech']],
            ['Moulay Abdelouahed', 'Alaoui Taleb', $H, '+212 666 57 27 77', ['GLS Marrakech']],
            ['Moulay Driss', 'Kadiri', $H, '+212 610 05 14 18', ['GLS Marrakech']],

            // ---- GLS Rabat ----------------------------------------------
            ['Yassine', 'Lahlali', $H, '+212 786 12 03 56', ['GLS Rabat']],
            ['Moussa', 'El Fatoumi', $H, '+212 639 99 77 37', ['GLS Rabat']],
            ['Ilyas', 'Zbite', $H, null, ['GLS Rabat']],
            ['Ouissal', 'Rahhaoui', $F, '+212 785 49 77 22', ['GLS Rabat']],
            ['Anass', 'Rehioui', $H, '+212 632 68 38 45', ['GLS Rabat']],
            ['Yassmina', 'Boulhia', $F, '+212 617 46 20 16', ['GLS Rabat']],
            ['Abdellatif', 'Naji', $H, '+212 668 76 97 46', ['GLS Rabat']],
            ['Samir', 'Sadoq', $H, '+212 674 30 74 29', ['GLS Rabat']],
            ['Amine', 'Es-saadi', $H, '+212 671 13 36 49', ['GLS Rabat']],
            ['Farouk', 'Alfrani', $H, '+212 688 93 65 57', ['GLS Rabat']],
            ['Amine', 'Jalt', $H, '+212 664 60 49 01', ['GLS Rabat']],

            // ---- GLS Casablanca -----------------------------------------
            ['Samir', 'Rachad', $H, null, ['GLS Casablanca']],
            ['Nabil', 'Hatimi', $H, null, ['GLS Casablanca']],
            ['Mahmoud', 'El Kasri', $H, null, ['GLS Casablanca']],
            ['Khadija', 'Gharsa', $F, null, ['GLS Casablanca']],
            ['Oussama', 'Belhaud', $H, null, ['GLS Casablanca']],
            ['Mohamed Saad', 'Grine', $H, null, ['GLS Casablanca']],
            ['Mohammed Amine', 'Rida', $H, null, ['GLS Casablanca']],

            // ---- GLS Kénitra --------------------------------------------
            // Ibrahim Dahri : feuilles Kénitra ET Salé — un seul enseignant,
            // rattaché aux deux centres (Kénitra en premier ⇒ principal).
            ['Ibrahim', 'Dahri', $H, '+212 631 81 64 41', ['GLS Kénitra', 'GLS Salé']],
            ['Ilyass', 'El Qotbi', $H, null, ['GLS Kénitra']],
            ['Omar', 'Mansouri', $H, null, ['GLS Kénitra']],
            ['Ahmed', 'El Falahi', $H, null, ['GLS Kénitra']],
            ['Saad', 'Brada', $H, null, ['GLS Kénitra']],
            ['Mohammed', 'Laaziz', $H, null, ['GLS Kénitra']],
            ['Jamal', 'Ouahim', $H, null, ['GLS Kénitra']],

            // ---- GLS Agadir ---------------------------------------------
            ['Badre', 'Benali', $H, '+212 644 33 15 17', ['GLS Agadir']],
            ['Mohamed', 'Aoutil', $H, '+212 637 19 14 37', ['GLS Agadir']],
            ['Yassin', 'Ougaf', $H, '+212 680 80 79 80', ['GLS Agadir']],
            ['Youssef', 'Oubla', $H, '+212 643 76 24 85', ['GLS Agadir']],
            ['Rachid', 'Achiran', $H, '+212 613 11 78 58', ['GLS Agadir']],
            ['Hala', 'El Mzouek', $F, '+212 709 42 42 72', ['GLS Agadir']],
            ['Youssra', 'Said', $F, null, ['GLS Agadir']],

            // ---- GLS Salé -----------------------------------------------
            ['Aya', 'Figar', $F, '+212 637 03 84 94', ['GLS Salé']],
            ['Abdelbadie', 'Bel Fadil', $H, '+212 654 10 61 35', ['GLS Salé']],
            ['Ilyas', 'El Fizazi', $H, '+212 611 70 54 28', ['GLS Salé']],
            ['Ali', 'Bougni', $H, '+212 605 96 39 99', ['GLS Salé']],
            ['Samir', 'Boussif', $H, '+212 607 90 29 50', ['GLS Salé']],
            ['Rhassane', 'Rharss', $H, '+212 667 03 52 01', ['GLS Salé']],
            // Prénom/nom probablement inversés sur la feuille — transcrit tel quel.
            ['Seffar', 'Mehdi', $H, '+212 688 91 45 43', ['GLS Salé']],
            ['Regragui', 'Tarik', $H, '+212 633 20 29 71', ['GLS Salé']],
            ['Hajib', 'En-nhaili', $H, '+212 663 18 47 87', ['GLS Salé']],
            ['Yassine', 'Hajjaji', $H, '+212 611 09 00 75', ['GLS Salé']],
            ['El Mehdi', 'Kouay', $H, '+212 630 95 40 38', ['GLS Salé']],
            ['Youssef', 'Bourazza', $H, '+212 705 81 66 49', ['GLS Salé']],

            // ---- GLS Online ---------------------------------------------
            ['Ghita', 'Ayche', $F, null, ['GLS Online']],
            ['Ahmed', 'Shaita', $H, null, ['GLS Online']],
            ['Oussama', 'Ziati', $H, null, ['GLS Online']],
            // Zineb Ayche : 3 lignes identiques (PR20/PR22/PR25) ⇒ une seule.
            ['Zineb', 'Ayche', $F, null, ['GLS Online']],
            ['Hajar', 'Kajdouf', $F, null, ['GLS Online']],
            ['Mohamed Amine', 'Mejdoubi', $H, null, ['GLS Online']],
            // « Asma | Asma » sur la feuille : nom de famille manquant.
            ['Asma', 'Asma', $F, null, ['GLS Online']],
            ['Zakaria', 'El Guerda', $H, null, ['GLS Online']],
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
        $crees = 0;
        $majs = 0;

        foreach ($this->roster() as [$prenom, $nom, $sexe, $telephone, $nomsCentres]) {
            $ids = [];

            foreach ($nomsCentres as $nomCentre) {
                if (! isset($centres[$nomCentre])) {
                    $this->command?->warn("Centre inconnu « {$nomCentre} » pour {$prenom} {$nom} — ignoré.");

                    continue;
                }

                $ids[] = $centres[$nomCentre];
            }

            if ($ids === []) {
                $this->command?->warn("Aucun centre valide pour {$prenom} {$nom} — enseignant ignoré.");

                continue;
            }

            // Identité = (nom, prénom) faute d'e-mail. Insensible à la casse
            // pour qu'une correction de casse faite à la main ne crée pas un
            // doublon au re-seed.
            $employee = Employee::query()
                ->where('categorie', Employee::CATEGORIE_ENSEIGNANT)
                ->whereRaw('lower(nom) = ?', [mb_strtolower($nom)])
                ->whereRaw('lower(prenom) = ?', [mb_strtolower($prenom)])
                ->first();

            $estNouveau = $employee === null;

            if ($estNouveau) {
                $employee = new Employee([
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'categorie' => Employee::CATEGORIE_ENSEIGNANT,
                    'statut' => Employee::STATUT_ACTIF,
                ]);
                $employee->reference = $this->prochaineReference();
            }

            // Ne jamais écraser une correction faite sur l'écran Employés :
            // le fichier ne porte ni sexe ni e-mail, et son téléphone peut
            // être périmé.
            $employee->sexe = $employee->sexe ?: $sexe;
            $employee->telephone = $employee->telephone ?: $telephone;
            $employee->etablissement_id = $employee->etablissement_id ?: $ids[0];

            // saveQuietly() : EmployeeObserver::created() créerait un login
            // (user_id est null) — précisément ce qu'on ne veut pas ici.
            $employee->saveQuietly();

            // Conséquence du saveQuietly : la caisse et les centres sont
            // posés à la main.
            if ($employee->etablissements()->doesntExist()) {
                $employee->syncEtablissements($ids);
            }

            $provisioner->provisionFor($employee);

            $estNouveau ? $crees++ : $majs++;
        }

        $this->command?->info(sprintf(
            '%d enseignant(s) créé(s), %d mis à jour — sans compte de connexion.',
            $crees,
            $majs,
        ));
    }

    /**
     * Même format que ReferenceGenerator::make('EMP', 'employees') — EMP-001,
     * EMP-002… — pour que les fiches seedées soient indistinguables de celles
     * créées depuis l'écran Employés. On n'appelle pas le générateur
     * directement : son compteur part de max(id), qui ne bouge pas entre deux
     * créations dans la même boucle et produirait des collisions.
     */
    private function prochaineReference(): string
    {
        static $prochain = null;

        if ($prochain === null) {
            // withoutGlobalScopes() : la référence est unique sur TOUTE la
            // table, y compris la ligne masquée du compte technique
            // (HiddenAccountScope) — sinon sa référence serait réattribuable.
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
}
