<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Expenses\Actions\EnregistrerDepense;
use App\Domain\Finance\Actions\DemanderTransfertCaisse;
use App\Domain\Finance\Actions\EnregistrerRemboursement;
use App\Domain\Finance\Actions\ValiderTransfertCaisse;
use App\Domain\Payments\Actions\EnregistrerEncaissement;
use App\Models\Caisse;
use App\Models\Depense;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Group;
use App\Models\InscriptionFee;
use App\Models\Student;
use App\Models\TypeDepense;
use App\Services\CaisseProvisioner;
use Illuminate\Database\Seeder;

/**
 * Demo/test FINANCE data — payments, expenses, refunds and till transfers —
 * so every money screen has something to click through.
 *
 * Every movement goes through its Domain action (invariants: caisses.solde
 * moves in the same transaction, references are system-generated, transfers
 * are validated by a DIFFERENT employee). Depends on DemoDataSeeder
 * (students + inscriptions with fee lines).
 *
 *   php artisan db:seed --class=DemoFinanceSeeder
 *
 * ⚠ Demo data only — do not run in production.
 */
final class DemoFinanceSeeder extends Seeder
{
    public function run(): void
    {
        if (Encaissement::query()->exists() || Depense::query()->exists()) {
            $this->command?->warn('Finance data already present — skipping.');

            return;
        }

        $fees = InscriptionFee::query()->with('inscription.student')->where('montant', '>', 0)->get();

        if ($fees->isEmpty()) {
            $this->command?->error('Run DemoDataSeeder first (inscriptions with fee lines).');

            return;
        }

        // Every employee owns a till — backfill in case observers were off
        // (DatabaseSeeder runs WithoutModelEvents).
        $provisioner = app(CaisseProvisioner::class);
        Employee::query()->each(fn (Employee $e) => $provisioner->provisionFor($e));

        $agents = Employee::query()->whereHas('caisses')->with('caisses')->get();
        $agentFor = fn (int $i): Employee => $agents[$i % $agents->count()];

        // --- Payments: settle some fee lines (full + partial) ---------------
        $paiement = app(EnregistrerEncaissement::class);
        $methodes = Encaissement::METHODES;

        foreach ($fees->take(6)->values() as $i => $fee) {
            $montant = $i % 2 === 0
                ? (float) $fee->montant                    // fully paid
                : round((float) $fee->montant / 2, 2);     // partially paid

            if ($montant <= 0) {
                continue;
            }

            $agent = $agentFor($i);
            $paiement->handle([
                'student_id' => $fee->inscription->student_id,
                'inscription_fee_id' => $fee->id,
                'montant' => $montant,
                'methode' => $methodes[$i % count($methodes)],
                'date_paiement' => now()->subDays(20 - $i)->toDateString(),
                'caisse_id' => $agent->caisses->first()->id,
                'note' => 'Paiement de démonstration',
            ], $agent);
        }

        // --- Expenses: across types, with the billing fields ----------------
        $depense = app(EnregistrerDepense::class);
        $types = TypeDepense::query()->where('is_system', false)->orderBy('nom')->get();
        $groupIds = Group::query()->pluck('id')->all();

        $lignes = [
            ['montant' => 1200.00, 'desc' => 'Loyer du local — juillet', 'methode' => 'Virement'],
            ['montant' => 640.50, 'desc' => 'Facture eau/électricité', 'methode' => 'Espèces'],
            ['montant' => 399.00, 'desc' => 'Abonnement fibre', 'methode' => 'Virement'],
            ['montant' => 250.00, 'desc' => 'Produits d\'entretien', 'methode' => 'Espèces'],
            ['montant' => 180.75, 'desc' => 'Fournitures pédagogiques', 'methode' => 'TPE'],
        ];

        foreach ($lignes as $i => $ligne) {
            $agent = $agentFor($i + 1);
            $depense->handle([
                'type_depense_id' => $types[$i % $types->count()]->id,
                'caisse_id' => $agent->caisses->first()->id,
                'group_id' => $i % 2 === 0 && $groupIds !== [] ? $groupIds[$i % count($groupIds)] : null,
                'montant' => $ligne['montant'],
                'methode_paiement' => $ligne['methode'],
                'date_depense' => now()->subDays(15 - $i)->toDateString(),
                'reference_facture' => sprintf('FA-DEMO-%04d', $i + 1),
                'description' => $ligne['desc'],
                'mots_cles' => 'demo,'.str($ligne['desc'])->slug(),
            ], $agent);
        }

        // --- Refunds --------------------------------------------------------
        $remboursement = app(EnregistrerRemboursement::class);
        $beneficiaire = Student::query()->first();
        $agent = $agentFor(2);
        $remboursement->handle([
            'beneficiaire_id' => $beneficiaire->id,
            'caisse_id' => $agent->caisses->first()->id,
            'montant' => 300.00,
            'date_remboursement' => now()->subDays(4)->toDateString(),
            'motif' => 'Annulation d\'inscription',
            'note' => 'Remboursement de démonstration',
        ], $agent);

        // --- Till transfers: one validated (by a DIFFERENT employee), one pending
        $demander = app(DemanderTransfertCaisse::class);
        $valider = app(ValiderTransfertCaisse::class);

        $source = Caisse::query()->where('solde', '>', 500)->first() ?? $agents->first()->caisses->first();
        $destination = Caisse::query()->whereKeyNot($source->id)->first();
        $requester = $source->responsable ?? $agents->first();
        $validator = $agents->first(fn (Employee $e) => $e->id !== $requester->id);

        $transfert = $demander->handle([
            'caisse_source_id' => $source->id,
            'caisse_destination_id' => $destination->id,
            'montant' => min(250.00, max(1.0, (float) $source->fresh()->solde)),
            'note' => 'Transfert de démonstration (validé)',
        ], $requester);
        $valider->handle($transfert, $validator);

        $demander->handle([
            'caisse_source_id' => $destination->id,
            'caisse_destination_id' => $source->id,
            'montant' => 100.00,
            'note' => 'Transfert de démonstration (en attente)',
        ], $validator);

        $this->command?->info('Finance demo data seeded: payments, expenses, refunds and transfers.');
    }
}
