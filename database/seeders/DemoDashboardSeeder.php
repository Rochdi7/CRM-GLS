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
 * Demo/test data for the Dashboard's "Résumé des frais annuels" chart
 * (GetAnnualFraisSummary) — 12 months of fee lines (with due dates spread
 * across the current calendar year), payments settling most of them
 * (leaving a deliberate "reste à payer" balance on some), and monthly
 * expenses, shaped into a wavy curve (rises, a mid-year dip, a late spike)
 * so the chart has something visually meaningful to show rather than a flat
 * line — same intent as the reference screenshot.
 *
 * Depends on DemoDataSeeder (groups with assigned fees, teachers, a
 * director) having run.
 *
 *   php artisan db:seed --class=DemoDashboardSeeder
 *
 * ⚠ Demo data only — do not run in production.
 */
final class DemoDashboardSeeder extends Seeder
{
    public function run(): void
    {
        if (Student::query()->where('reference', 'ilike', 'ETU-DASH%')->exists()) {
            $this->command?->warn('Dashboard demo data already present — skipping.');

            return;
        }

        $annee = AnneeScolaire::query()->where('par_defaut', true)->first()
            ?? AnneeScolaire::query()->orderByDesc('date_debut')->first();

        $groups = Group::query()->with('frais')->whereHas('frais')->get();
        $agent = Employee::query()->whereHas('caisses')->with('caisses')->first();
        $directeur = Employee::query()->where('categorie', Employee::CATEGORIE_DIRECTEUR)->first() ?? $agent;
        $typesDepense = TypeDepense::query()->where('is_system', false)->orderBy('nom')->get();

        if ($annee === null || $groups->isEmpty() || $agent === null || $typesDepense->isEmpty()) {
            $this->command?->error('Run DemoDataSeeder + TypeDepenseSeeder first.');

            return;
        }

        // Relative shape (0..1) per month — mirrors the reference screenshot's
        // curve: builds up, dips mid-year (low season), recovers late.
        $shape = [
            1 => 0.85, 2 => 0.90, 3 => 1.00, 4 => 0.88, 5 => 0.95,
            6 => 0.60, 7 => 0.30, 8 => 0.20, 9 => 0.55, 10 => 0.75,
            11 => 0.80, 12 => 0.82,
        ];

        $year = (int) now()->year;
        $paiement = app(EnregistrerEncaissement::class);
        $depenseAction = app(EnregistrerDepense::class);

        DB::transaction(function () use ($shape, $year, $groups, $annee, $agent, $directeur, $typesDepense, $paiement, $depenseAction): void {
            $studentIndex = 0;

            foreach ($shape as $month => $factor) {
                $centreId = $groups->first()->etablissement_id;
                $groupIds = $groups->pluck('id')->all();

                // --- Expenses for the month (independent of fees) ----------
                $depenseAction->handle([
                    'type_depense_id' => $typesDepense[($month - 1) % $typesDepense->count()]->id,
                    'caisse_id' => $agent->caisses->first()->id,
                    'group_id' => $groupIds[($month - 1) % count($groupIds)],
                    'montant' => round(9000 * $factor + random_int(-500, 500), 2),
                    'methode_paiement' => 'Virement',
                    'date_depense' => sprintf('%d-%02d-%02d', $year, $month, random_int(5, 25)),
                    'reference_facture' => sprintf('FA-DASH-%d%02d', $year, $month),
                    'description' => 'Dépense de démonstration (tableau de bord)',
                    'mots_cles' => 'demo,dashboard',
                ], $directeur);

                // --- 3 students billed this month, one fee each ------------
                $feesThisMonth = 3;

                for ($i = 0; $i < $feesThisMonth; $i++) {
                    $studentIndex++;
                    $group = $groups[$studentIndex % $groups->count()];

                    $student = Student::query()->create([
                        'reference' => 'ETU-DASH'.str_pad((string) $studentIndex, 3, '0', STR_PAD_LEFT),
                        'nom' => 'Démo'.$studentIndex,
                        'prenom' => 'Étudiant'.$studentIndex,
                        'sexe' => $studentIndex % 2 === 0 ? 'Homme' : 'Femme',
                        'date_naissance' => now()->subYears(random_int(17, 30))->toDateString(),
                        'telephone' => '06'.random_int(10000000, 99999999),
                        'whatsapp' => '06'.random_int(10000000, 99999999),
                        'email' => 'dash'.$studentIndex.'@example.com',
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
                        'date_inscription' => sprintf('%d-%02d-%02d', $year, $month, 1),
                        'date_debut' => sprintf('%d-%02d-%02d', $year, $month, 1),
                        'created_by' => $directeur->id,
                    ]);

                    $frais = $group->frais->first();
                    $montant = round(((float) ($frais->pivot->montant ?: 1300)) * (0.8 + $factor * 0.4), 2);

                    $fee = InscriptionFee::query()->create([
                        'inscription_id' => $inscription->id,
                        'frais_id' => $frais->id,
                        'nom' => $frais->nom,
                        'montant_initial' => $montant,
                        'montant' => $montant,
                        'date_echeance' => sprintf('%d-%02d-%02d', $year, $month, random_int(5, 25)),
                        'statut' => InscriptionFee::STATUT_NON_PAYE,
                    ]);

                    $inscription->update(['montant_total' => $montant]);

                    // Collection rate follows the same seasonal shape — leaves
                    // a visible "reste à payer" band, larger in low months.
                    $collectionRate = min(1.0, 0.55 + $factor * 0.45);
                    $paidAmount = round($montant * $collectionRate, 2);

                    if ($paidAmount > 0) {
                        $paiement->handle([
                            'student_id' => $student->id,
                            'inscription_fee_id' => $fee->id,
                            'montant' => $paidAmount,
                            'methode' => 'Espèces',
                            'date_paiement' => sprintf('%d-%02d-%02d', $year, $month, random_int(1, 28)),
                            'caisse_id' => $agent->caisses->first()->id,
                            'note' => 'Paiement de démonstration (tableau de bord)',
                        ], $agent);
                    }
                }

                // --- One "catch-up" payment received THIS month but settling
                //     an OLDER fee (or a fresh avance) — makes "Encaissements"
                //     (total cash received) diverge from "Collecté" (payments
                //     on fees due this month), as in the reference screenshot.
                $catchUpFee = InscriptionFee::query()
                    ->whereHas('inscription', fn ($q) => $q->where('etablissement_id', $centreId))
                    ->inRandomOrder()
                    ->first();

                if ($catchUpFee !== null) {
                    $reste = round((float) $catchUpFee->montant - $catchUpFee->montantPaye(), 2);
                    $extra = $reste > 0 ? min($reste, round(4000 * $factor, 2)) : round(2000 * $factor, 2);

                    if ($extra > 0) {
                        $paiement->handle([
                            'student_id' => $catchUpFee->inscription->student_id,
                            'inscription_fee_id' => $reste > 0 ? $catchUpFee->id : null,
                            'montant' => $extra,
                            'methode' => 'Virement',
                            'date_paiement' => sprintf('%d-%02d-%02d', $year, $month, random_int(1, 28)),
                            'caisse_id' => $agent->caisses->first()->id,
                            'note' => 'Rattrapage de paiement (démonstration tableau de bord)',
                        ], $agent);
                    }
                }
            }
        });

        $this->command?->info("Dashboard demo data seeded: 12 months of fees, payments and expenses for {$year}.");
    }
}
