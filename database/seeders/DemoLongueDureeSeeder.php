<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Expenses\Actions\EnregistrerDepense;
use App\Domain\Payments\Actions\EnregistrerEncaissement;
use App\Domain\Shared\Support\ReferenceGenerator;
use App\Models\AnneeScolaire;
use App\Models\Employee;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Student;
use App\Models\TypeDepense;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Long-range demo data for the "Résumé des frais annuels" chart — HIGH
 * volume from January of the current year up to the CURRENT month, at the
 * same order of magnitude as real manual entries (~20–35k MAD of fees per
 * month), so every series draws a full, visually rich curve instead of a
 * flat line with one late spike.
 *
 * Complements DemoDashboardSeeder (which seeds only ~3 small fee lines per
 * month): per month this creates 8–12 students, each with an inscription +
 * one fee line due that month, payments settling 55–95% of them (seasonal
 * collection rate → a visible "reste à payer" band), catch-up payments on
 * older fees (so "Encaissements" diverges from "Collecté") and 2–4 expenses.
 *
 * Money movements go through the Domain actions (caisses.solde invariants);
 * students/inscriptions/fee lines are plain creates like the other demo
 * seeders. Depends on DemoDataSeeder (groups with assigned fees, employees
 * with tills) + TypeDepenseSeeder.
 *
 *   php artisan db:seed --class=DemoLongueDureeSeeder
 *
 * ⚠ Demo data only — do not run in production.
 */
final class DemoLongueDureeSeeder extends Seeder
{
    public function run(): void
    {
        if (Student::query()->where('reference', 'ilike', 'ETU-LONG%')->exists()) {
            $this->command?->warn('Longue-durée demo data already present — skipping.');

            return;
        }

        $annee = AnneeScolaire::query()->where('par_defaut', true)->first()
            ?? AnneeScolaire::query()->orderByDesc('date_debut')->first();

        $groups = Group::query()->with('frais')->whereHas('frais')->get();
        $agents = Employee::query()->whereHas('caisses')->with('caisses')->get();
        $directeur = Employee::query()->where('categorie', Employee::CATEGORIE_DIRECTEUR)->first() ?? $agents->first();
        $typesDepense = TypeDepense::query()->where('is_system', false)->orderBy('nom')->get();

        if ($annee === null || $groups->isEmpty() || $agents->isEmpty() || $typesDepense->isEmpty()) {
            $this->command?->error('Run DemoDataSeeder + TypeDepenseSeeder first.');

            return;
        }

        // Seasonal shape (0..1) — busy winter/spring term, June slowdown,
        // summer trough, like the reference curve.
        $shape = [
            1 => 0.90, 2 => 0.95, 3 => 0.85, 4 => 0.88, 5 => 0.75,
            6 => 0.82, 7 => 0.45, 8 => 0.55, 9 => 0.70, 10 => 0.85,
            11 => 0.90, 12 => 0.80,
        ];

        $year = (int) now()->year;
        $currentMonth = (int) now()->month;
        $today = (int) now()->day;

        $paiement = app(EnregistrerEncaissement::class);
        $depenseAction = app(EnregistrerDepense::class);

        DB::transaction(function () use ($shape, $year, $currentMonth, $today, $groups, $annee, $agents, $directeur, $typesDepense, $paiement, $depenseAction): void {
            $studentIndex = 0;

            for ($month = 1; $month <= $currentMonth; $month++) {
                $factor = $shape[$month];

                // Days never in the future for the running month ("to now").
                $maxDay = $month === $currentMonth ? max(1, min(28, $today)) : 28;
                $day = fn (): int => random_int(1, $maxDay);

                // --- Students billed this month (8–12, seasonal) -----------
                $count = (int) round(8 + $factor * 4);

                for ($i = 0; $i < $count; $i++) {
                    $studentIndex++;
                    $group = $groups[$studentIndex % $groups->count()];
                    $agent = $agents[$studentIndex % $agents->count()];

                    $student = Student::query()->create([
                        'reference' => 'ETU-LONG'.str_pad((string) $studentIndex, 3, '0', STR_PAD_LEFT),
                        'nom' => 'Longue'.$studentIndex,
                        'prenom' => 'Durée'.$studentIndex,
                        'sexe' => $studentIndex % 2 === 0 ? 'Homme' : 'Femme',
                        'date_naissance' => now()->subYears(random_int(17, 32))->toDateString(),
                        'telephone' => '06'.random_int(10000000, 99999999),
                        'whatsapp' => '06'.random_int(10000000, 99999999),
                        'email' => 'longue'.$studentIndex.'@example.com',
                        'niveau' => $group->niveau,
                        'etablissement_id' => $group->etablissement_id,
                        'parent_nom' => 'Parent'.$studentIndex,
                        'parent_telephone' => '06'.random_int(10000000, 99999999),
                    ]);

                    $inscription = Inscription::query()->create([
                        'reference' => ReferenceGenerator::make('INS', 'inscriptions'),
                        'student_id' => $student->id,
                        'group_id' => $group->id,
                        'etablissement_id' => $group->etablissement_id,
                        'annee_scolaire_id' => $annee->id,
                        'statut' => Inscription::STATUT_ACTIVE,
                        'date_inscription' => sprintf('%d-%02d-01', $year, $month),
                        'date_debut' => sprintf('%d-%02d-01', $year, $month),
                        'created_by' => $directeur->id,
                    ]);

                    $frais = $group->frais->first();
                    // 1 800 – 3 800 MAD per fee → 20–35k of CA per month.
                    $montant = round(1800 + random_int(0, 2000) * $factor, 2);

                    $fee = InscriptionFee::query()->create([
                        'inscription_id' => $inscription->id,
                        'frais_id' => $frais->id,
                        'nom' => $frais->nom,
                        'montant_initial' => $montant,
                        'montant' => $montant,
                        'date_echeance' => sprintf('%d-%02d-%02d', $year, $month, $day()),
                        'statut' => InscriptionFee::STATUT_NON_PAYE,
                    ]);

                    $inscription->update(['montant_total' => $montant]);

                    // Collection follows the season: 55–95% of the fee paid,
                    // a few fees left fully unpaid for a real "reste" band.
                    if ($studentIndex % 5 === 0) {
                        continue;
                    }

                    $collectionRate = min(0.95, 0.55 + $factor * 0.40);
                    $paidAmount = round($montant * $collectionRate, 2);

                    $paiement->handle([
                        'student_id' => $student->id,
                        'inscription_fee_id' => $fee->id,
                        'montant' => $paidAmount,
                        'methode' => $studentIndex % 3 === 0 ? 'Virement' : 'Espèces',
                        'date_paiement' => sprintf('%d-%02d-%02d', $year, $month, $day()),
                        'caisse_id' => $agent->caisses->first()->id,
                        'note' => 'Paiement de démonstration (longue durée)',
                    ], $agent);
                }

                // --- Catch-up payments: cash received THIS month settling
                //     OLDER fees → "Encaissements" ≠ "Collecté" ------------
                $openFees = InscriptionFee::query()
                    ->where('date_echeance', '<', sprintf('%d-%02d-01', $year, $month))
                    ->whereHas('inscription', fn ($q) => $q->where('statut', Inscription::STATUT_ACTIVE))
                    ->inRandomOrder()
                    ->limit(3)
                    ->get();

                foreach ($openFees as $j => $openFee) {
                    $reste = round((float) $openFee->montant - $openFee->montantPaye(), 2);

                    if ($reste <= 0) {
                        continue;
                    }

                    $agent = $agents[$j % $agents->count()];
                    $paiement->handle([
                        'student_id' => $openFee->inscription->student_id,
                        'inscription_fee_id' => $openFee->id,
                        'montant' => min($reste, round(1500 * $factor + 300, 2)),
                        'methode' => 'Espèces',
                        'date_paiement' => sprintf('%d-%02d-%02d', $year, $month, $day()),
                        'caisse_id' => $agent->caisses->first()->id,
                        'note' => 'Rattrapage de paiement (démonstration longue durée)',
                    ], $agent);
                }

                // --- Expenses: 2–4 lines, ~4–10k per month -----------------
                $depensesCount = random_int(2, 4);

                for ($k = 0; $k < $depensesCount; $k++) {
                    $depenseAction->handle([
                        'type_depense_id' => $typesDepense[($month + $k) % $typesDepense->count()]->id,
                        'caisse_id' => $agents[$k % $agents->count()]->caisses->first()->id,
                        'group_id' => $k % 2 === 0 ? $groups[($month + $k) % $groups->count()]->id : null,
                        'montant' => round((2500 + random_int(0, 1500)) * (0.6 + $factor * 0.5), 2),
                        'methode_paiement' => $k % 2 === 0 ? 'Virement' : 'Espèces',
                        'date_depense' => sprintf('%d-%02d-%02d', $year, $month, $day()),
                        'reference_facture' => sprintf('FA-LONG-%d%02d-%d', $year, $month, $k + 1),
                        'description' => 'Dépense de démonstration (longue durée)',
                        'mots_cles' => 'demo,longue-duree',
                    ], $directeur);
                }
            }
        });

        $this->command?->info("Longue-durée demo data seeded: 01/{$year} → ".now()->format('m/Y').' (fees, payments, catch-ups, expenses).');
    }
}
