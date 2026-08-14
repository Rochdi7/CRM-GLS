<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Payments\Actions\EnregistrerEncaissement;
use App\Domain\Shared\Support\ReferenceGenerator;
use App\Models\AnneeScolaire;
use App\Models\Employee;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Student;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Demo/test data for "Gestion des recouvrements" (RecouvrementController +
 * GetRetardsList) — DemoDataSeeder's own fee lines are all due in the FUTURE
 * (date_echeance = now()->addDays(5..40)), so nothing in that seeder is ever
 * overdue. This seeder creates its own students/inscriptions/fees with PAST
 * due dates spread across every duration bucket (1/7/15/30/+30 jours) and
 * every payment state (non payé, payé partiellement, payé — the last one to
 * prove fully-paid fees are correctly excluded from the report), so the
 * Recouvrement page has something real to filter/paginate through.
 *
 * Depends on DemoDataSeeder (groups with assigned fees, teachers) having run.
 *
 *   php artisan db:seed --class=DemoRecouvrementSeeder
 *
 * ⚠ Demo data only — do not run in production.
 */
final class DemoRecouvrementSeeder extends Seeder
{
    public function run(): void
    {
        if (Student::query()->where('reference', 'ilike', 'ETU-RETARD%')->exists()) {
            $this->command?->warn('Recouvrement demo data already present — skipping.');

            return;
        }

        $annee = AnneeScolaire::query()->where('par_defaut', true)->first()
            ?? AnneeScolaire::query()->orderByDesc('date_debut')->first();

        $groups = Group::query()->with('frais')->whereHas('frais')->get();
        $agent = Employee::query()->whereHas('caisses')->with('caisses')->first();

        if ($annee === null || $groups->isEmpty() || $agent === null) {
            $this->command?->error('Run DemoDataSeeder first (groups with assigned fees, an employee with a till).');

            return;
        }

        // [nom, prenom, sexe, niveau, days late, payment state]
        // "payment state": 'unpaid' | 'partial' | 'paid' — 'paid' fees must
        // NOT show up in the Recouvrement report (reste à payer = 0).
        $profiles = [
            ['Amrani', 'Hicham', 'Homme', 'A1.1', 1, 'unpaid'],
            ['Bakkali', 'Fatima', 'Femme', 'A1.2', 3, 'partial'],
            ['Chaoui', 'Reda', 'Homme', 'A2.1', 6, 'unpaid'],
            ['Daoudi', 'Meryem', 'Femme', 'A2.2', 7, 'unpaid'],
            ['Ezzahi', 'Anas', 'Homme', 'B1.1', 10, 'partial'],
            ['Fahmi', 'Khadija', 'Femme', 'B1.2', 14, 'unpaid'],
            ['Guerraoui', 'Yassine', 'Homme', 'B1.3', 15, 'unpaid'],
            ['Haddaoui', 'Siham', 'Femme', 'B2.1', 22, 'partial'],
            ['Idani', 'Othmane', 'Homme', 'B2.2', 29, 'unpaid'],
            ['Jabri', 'Wafae', 'Femme', 'B2.3', 30, 'unpaid'],
            ['Kadiri', 'Zakaria', 'Homme', 'A1.1', 45, 'partial'],
            ['Lamrani', 'Houda', 'Femme', 'A2.1', 60, 'unpaid'],
            ['Moukrim', 'Bilal', 'Homme', 'B1.1', 90, 'unpaid'],
            // Fully paid, still overdue on the calendar — must be excluded.
            ['Naciri', 'Asmae', 'Femme', 'A1.2', 12, 'paid'],
        ];

        DB::transaction(function () use ($profiles, $groups, $annee, $agent): void {
            $paiement = app(EnregistrerEncaissement::class);
            $directeur = Employee::query()->where('categorie', Employee::CATEGORIE_DIRECTEUR)->first() ?? $agent;

            foreach ($profiles as $i => [$nom, $prenom, $sexe, $niveau, $daysLate, $state]) {
                $group = $groups[$i % $groups->count()];

                $student = Student::query()->create([
                    'reference' => 'ETU-RETARD'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'sexe' => $sexe,
                    'date_naissance' => now()->subYears(random_int(17, 34))->toDateString(),
                    'telephone' => '06'.random_int(10000000, 99999999),
                    'whatsapp' => '06'.random_int(10000000, 99999999),
                    'email' => strtolower($prenom.'.'.$nom).'.retard@example.com',
                    'niveau' => $niveau,
                    'etablissement_id' => $group->etablissement_id,
                    'parent_nom' => $nom,
                    'parent_telephone' => '06'.random_int(10000000, 99999999),
                ]);

                $inscription = Inscription::query()->create([
                    'reference' => ReferenceGenerator::make('INS', 'inscriptions'),
                    'student_id' => $student->id,
                    'group_id' => $group->id,
                    'etablissement_id' => $group->etablissement_id,
                    'annee_scolaire_id' => $annee->id,
                    'statut' => Inscription::STATUT_ACTIVE,
                    'date_inscription' => now()->subDays($daysLate + 30)->toDateString(),
                    'date_debut' => now()->subDays($daysLate + 30)->toDateString(),
                    'created_by' => $directeur->id,
                ]);

                // One overdue fee per student, taken from the group's own
                // assigned catalog (real montant, not invented).
                $frais = $group->frais->first();
                $montant = (float) ($frais->pivot->montant ?: 500);

                $fee = InscriptionFee::query()->create([
                    'inscription_id' => $inscription->id,
                    'frais_id' => $frais->id,
                    'nom' => $frais->nom,
                    'montant_initial' => $montant,
                    'montant' => $montant,
                    'date_echeance' => now()->subDays($daysLate)->toDateString(),
                    'statut' => InscriptionFee::STATUT_NON_PAYE,
                ]);

                $inscription->update(['montant_total' => $montant]);

                $paidAmount = match ($state) {
                    'partial' => round($montant / 2, 2),
                    'paid' => $montant,
                    default => 0.0,
                };

                if ($paidAmount > 0) {
                    $paiement->handle([
                        'student_id' => $student->id,
                        'inscription_fee_id' => $fee->id,
                        'montant' => $paidAmount,
                        'methode' => 'Espèces',
                        'date_paiement' => now()->subDays(max(0, $daysLate - 1))->toDateString(),
                        'caisse_id' => $agent->caisses->first()->id,
                        'note' => 'Paiement de démonstration (recouvrement)',
                    ], $agent);
                }
            }
        });

        $this->command?->info('Recouvrement demo data seeded: 14 overdue fees across every duration bucket and payment state.');
    }
}
