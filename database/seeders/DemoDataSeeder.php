<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Shared\Support\ReferenceGenerator;
use App\Models\AnneeScolaire;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Frais;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Salle;
use App\Models\Student;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Demo/test data: teachers, students, groups (with assigned fees) and
 * inscriptions (with fee lines) — so the CRM has something to click through.
 *
 * Idempotent-ish: it skips creating if demo students already exist, so
 * re-running won't pile up duplicates. Depends on ReferentialDataSeeder +
 * FraisSeeder having run (centers, years, rooms, fee catalog).
 *
 *   php artisan db:seed --class=DemoDataSeeder
 *
 * ⚠ Demo data only — do not run in production.
 */
final class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (Student::query()->where('reference', 'ilike', 'ETU-DEMO%')->exists()) {
            $this->command?->warn('Demo data already present — skipping.');

            return;
        }

        $annee = AnneeScolaire::query()->where('par_defaut', true)->first()
            ?? AnneeScolaire::query()->orderByDesc('date_debut')->first();

        if ($annee === null || Etablissement::query()->doesntExist() || Frais::query()->doesntExist()) {
            $this->command?->error('Run ReferentialDataSeeder + FraisSeeder first.');

            return;
        }

        DB::transaction(function () use ($annee): void {
            $teachers = $this->createTeachers();
            $students = $this->createStudents();
            $groups = $this->createGroups($annee, $teachers);
            $this->createInscriptions($students, $groups, $annee);
        });

        $this->command?->info('Demo data seeded: teachers, students, groups (with fees) and inscriptions.');
    }

    /** @return array<int, Employee> */
    private function createTeachers(): array
    {
        $names = [
            ['Driss', 'El Amrani'],
            ['Sanaa', 'Bennani'],
            ['Karim', 'Fassi'],
            ['Nadia', 'Cherkaoui'],
        ];

        $centres = Etablissement::query()->pluck('id')->all();
        $teachers = [];

        foreach ($names as $i => [$prenom, $nom]) {
            // user_id left null: EmployeeObserver auto-creates a login whose
            // name/username/email are derived from the employee's own name
            // (EmployeeCredentialService) — never a randomized Faker identity.
            $employee = Employee::query()->create([
                'reference' => ReferenceGenerator::make('EMP', 'employees'),
                'nom' => $nom,
                'prenom' => $prenom,
                'sexe' => $i % 2 === 0 ? 'Homme' : 'Femme',
                'categorie' => Employee::CATEGORIE_ENSEIGNANT,
                'statut' => Employee::STATUT_ACTIF,
                'telephone' => '06'.random_int(10000000, 99999999),
                'etablissement_id' => $centres[$i % count($centres)],
            ]);

            // Grant the 'teacher' role so this demo login's sidebar isn't
            // empty — the observer creates the User but assigns no role.
            $employee->user?->syncRoles(['teacher']);

            $teachers[] = $employee;
        }

        return $teachers;
    }

    /** @return array<int, Student> */
    private function createStudents(): array
    {
        $data = [
            ['Alaoui', 'Sara', 'Femme', 'A1.1'],
            ['Idrissi', 'Youssef', 'Homme', 'A1.2'],
            ['Benjelloun', 'Imane', 'Femme', 'A2.1'],
            ['Tazi', 'Omar', 'Homme', 'A2.2'],
            ['Berrada', 'Salma', 'Femme', 'B1.1'],
            ['El Fassi', 'Mehdi', 'Homme', 'B1.2'],
            ['Chraibi', 'Lina', 'Femme', 'B2.1'],
            ['Ouazzani', 'Adam', 'Homme', 'B2.2'],
            ['Sqalli', 'Nour', 'Femme', 'A2.3'],
            ['Kabbaj', 'Rayan', 'Homme', 'B1.3'],
            ['Lahlou', 'Yasmine', 'Femme', 'A1.1'],
            ['Bennis', 'Ilias', 'Homme', 'B2.3'],
        ];

        $centres = Etablissement::query()->pluck('id')->all();
        $students = [];

        foreach ($data as $i => [$nom, $prenom, $sexe, $niveau]) {
            $students[] = Student::query()->create([
                'reference' => 'ETU-DEMO'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'nom' => $nom,
                'prenom' => $prenom,
                'sexe' => $sexe,
                'date_naissance' => now()->subYears(random_int(16, 35))->toDateString(),
                'telephone' => '06'.random_int(10000000, 99999999),
                'whatsapp' => '06'.random_int(10000000, 99999999),
                'email' => strtolower($prenom.'.'.$nom).'@example.com',
                'niveau' => $niveau,
                'etablissement_id' => $centres[$i % count($centres)],
                'parent_nom' => $nom,
                'parent_telephone' => '06'.random_int(10000000, 99999999),
            ]);
        }

        return $students;
    }

    /**
     * @param  array<int, Employee>  $teachers
     * @return array<int, Group>
     */
    private function createGroups(AnneeScolaire $annee, array $teachers): array
    {
        $inscription = Frais::query()->where('nom', 'ilike', 'Frais d\'inscription%')->first();
        $mois = Frais::query()->where('nom', 'ilike', 'Frais de %')->orderBy('id')->take(3)->get();

        $defs = [
            ['Herr Driss 13h - Intensifs', 'B1.1', Group::STATUT_EN_FORMATION],
            ['Frau Sanaa 18h - Débutants', 'A1.1', Group::STATUT_EN_FORMATION],
            ['Herr Karim 10h - Prépa ÖSD', 'B2.1', Group::STATUT_EN_INSCRIPTION],
            ['Frau Nadia 16h - Intermédiaire', 'A2.2', Group::STATUT_EN_FORMATION],
        ];

        $centres = Etablissement::query()->pluck('id')->all();
        $groups = [];

        foreach ($defs as $i => [$nom, $niveau, $statut]) {
            $centreId = $centres[$i % count($centres)];
            $salle = Salle::query()->where('etablissement_id', $centreId)->first();

            $group = Group::query()->create([
                'nom' => $nom,
                'niveau' => $niveau,
                'enseignant_id' => $teachers[$i % count($teachers)]->id,
                'salle_id' => $salle?->id,
                'etablissement_id' => $centreId,
                'annee_scolaire_id' => $annee->id,
                'capacite_max' => random_int(12, 20),
                'statut' => $statut,
                'date_debut_formation' => $statut === Group::STATUT_EN_FORMATION ? now()->subMonth()->toDateString() : null,
            ]);

            // Assign fees to the group: inscription (once) + 3 monthly fees.
            // Amounts are always per-group (group_frais.montant).
            $assign = [];
            if ($inscription) {
                $assign[$inscription->id] = ['montant' => 300];
            }
            foreach ($mois as $m) {
                $assign[$m->id] = ['montant' => 1300];
            }
            $group->frais()->sync($assign);

            $groups[] = $group;
        }

        return $groups;
    }

    /**
     * @param  array<int, Student>  $students
     * @param  array<int, Group>  $groups
     */
    private function createInscriptions(array $students, array $groups, AnneeScolaire $annee): void
    {
        foreach ($students as $i => $student) {
            // Enroll into a group in the same center when possible, else round-robin.
            $group = collect($groups)->firstWhere('etablissement_id', $student->etablissement_id)
                ?? $groups[$i % count($groups)];

            $inscription = Inscription::query()->create([
                'reference' => ReferenceGenerator::make('INS', 'inscriptions'),
                'student_id' => $student->id,
                'group_id' => $group->id,
                'etablissement_id' => $group->etablissement_id,
                'annee_scolaire_id' => $annee->id,
                'statut' => Inscription::STATUT_ACTIVE,
                'date_inscription' => now()->subDays(random_int(1, 60))->toDateString(),
                'date_debut' => now()->subDays(random_int(1, 30))->toDateString(),
                'created_by' => Employee::query()->where('categorie', Employee::CATEGORIE_DIRECTEUR)->value('id'),
            ]);

            // Bill the group's assigned fees, with a discount on one line for variety.
            $total = 0;
            foreach ($group->frais as $index => $frais) {
                $initial = (float) $frais->pivot->montant;
                $remisePct = ($i % 4 === 0 && $index === 1) ? 50.0 : null; // 50% off one line for some students
                $final = InscriptionFee::computeMontant($initial, $remisePct, null);
                $total += $final;

                InscriptionFee::query()->create([
                    'inscription_id' => $inscription->id,
                    'frais_id' => $frais->id,
                    'nom' => $frais->nom,
                    'montant_initial' => $initial,
                    'remise_pct' => $remisePct,
                    'montant' => $final,
                    'date_echeance' => now()->addDays(random_int(5, 40))->toDateString(),
                    'note' => $remisePct ? 'Remise fidélité' : null,
                    'statut' => InscriptionFee::STATUT_NON_PAYE,
                ]);
            }

            $inscription->update(['montant_total' => $total]);
        }
    }
}
