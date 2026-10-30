<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Domain\Payments\Actions\AppliquerAvance;
use App\Domain\Payments\Actions\ConvertirEncaissementsEnAvance;
use App\Domain\Shared\Support\ReferenceGenerator;
use App\Models\AnneeScolaire;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Une PART d'avance partie sur une AUTRE fiche (30/10/2026, IMANE HAMDANI :
 * avance de 1 300 DH à Marrakech, 1 000 DH appliqués sur place, 300 DH
 * transférés sur sa fiche GLS Online). La liste affichait « Montant utilisé
 * 1 300 » à Marrakech : les 300 DH transférés comptaient comme utilisés sur
 * place. Ils sortent toujours de l'avance (le reste ne change pas), mais
 * sont désormais comptés à part dans `montantTransfere` et nommés
 * « Transférée → centre (étudiant) ».
 */
final class AvancePartTransfereeAutreFicheTest extends TestCase
{
    use RefreshDatabase;

    private AnneeScolaire $annee;

    private Etablissement $centre;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->annee = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->centre = Etablissement::factory()->create();
        $this->user = User::factory()->create();
        foreach (['payments.view', 'payments.create', 'centers.access-all'] as $p) {
            $this->user->givePermissionTo($p);
        }
        Employee::factory()->create(['user_id' => $this->user->id, 'etablissement_id' => $this->centre->id]);
        $this->user = $this->user->fresh();
    }

    /** @return array{0: Inscription, 1: InscriptionFee} */
    private function inscriptionWithFee(Student $student, string $nom, float $montant): array
    {
        $group = Group::factory()->create(['etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id]);
        $inscription = Inscription::create([
            'reference' => 'INS-'.fake()->unique()->numerify('#####'),
            'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => Inscription::STATUT_ACTIVE, 'date_inscription' => '2025-09-15',
            'montant_total' => $montant,
        ]);
        $fee = InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => $nom,
            'montant_initial' => $montant, 'montant' => $montant,
            'date_echeance' => '2025-10-31', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);

        return [$inscription, $fee];
    }

    /** @return array<int, array<string, mixed>> keyed by encaissement id */
    private function avances(string $solde): array
    {
        $props = $this->actingAs($this->user)
            ->get(route('backoffice.encaissements.index', ['view' => 'avance', 'soldeFilter' => $solde, 'dateFrom' => '', 'dateTo' => '']))
            ->viewData('page')['props'];

        return collect($props['encaissements']['data'])->keyBy('id')->all();
    }

    public function test_the_part_sent_to_another_record_is_not_counted_as_used_here(): void
    {
        $imane = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $copieOnline = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        [$insA, $feePaye] = $this->inscriptionWithFee($imane, 'Frais de Septembre', 1300);
        [, $octobre] = $this->inscriptionWithFee($imane, "Frais d'Octobre", 1300);
        [$insOnline, $octobreOnline] = $this->inscriptionWithFee($copieOnline, "Frais d'Octobre", 1000);

        // 1 300 DH payés puis libérés en avance ; 1 000 DH appliqués sur place.
        $this->actingAs($this->user)->post(route('backoffice.encaissements.store'), [
            'student_id' => $imane->id, 'inscription_id' => $insA->id, 'date_paiement' => '2025-09-20',
            'payment_lines' => [['fee_id' => $feePaye->id, 'montant' => '1300.00', 'methode' => 'Espèces', 'date_paiement' => '2025-09-20']],
        ])->assertSessionHasNoErrors();
        $avance = Encaissement::query()->where('student_id', $imane->id)->latest('id')->firstOrFail();
        app(ConvertirEncaissementsEnAvance::class)->handle($insA, [$avance->id]);
        app(AppliquerAvance::class)->handle($avance->fresh(), $octobre, 1000.0);

        // 300 DH partis sur l'autre fiche — une ligne d'application, comme
        // AppliquerAvance la crée, mais au nom de la fiche d'arrivée.
        $part = Encaissement::create([
            'reference' => ReferenceGenerator::make('ENC', 'encaissements'),
            'student_id' => $copieOnline->id,
            'etablissement_id' => $avance->etablissement_id,
            'inscription_fee_id' => $octobreOnline->id,
            'applied_from_encaissement_id' => $avance->id,
            'montant' => 300,
            'methode' => $avance->methode,
            'date_paiement' => $avance->date_paiement,
            'caisse_id' => $avance->caisse_id,
            'agent_id' => $avance->agent_id,
        ]);

        $row = $this->avances('epuise')[$avance->id];
        $this->assertSame('1000.00', $row['montantUtilise'], 'Only 1 000 DH were used on this record.');
        $this->assertSame('300.00', $row['montantTransfere']);
        $this->assertSame('0.00', $row['montantRestant'], 'The advance is still fully spent.');
        $this->assertTrue(
            collect($row['fraisAppliques'])->contains(fn ($l) => str_contains($l['frais'], 'Transférée') && $l['montant'] === '300.00'),
            'The transferred part is named as such.',
        );

        // Left un-applied on the other record (the user applies it by hand):
        // the root still reports 300 transferred, and the other record's own
        // avance shows 300 available, none used.
        app(ConvertirEncaissementsEnAvance::class)->handle($insOnline, [$part->id]);
        $epuisees = $this->avances('epuise');
        $this->assertSame('1000.00', $epuisees[$avance->id]['montantUtilise']);
        $this->assertSame('300.00', $epuisees[$avance->id]['montantTransfere']);
        $disponibles = $this->avances('restant');
        $this->assertSame('0.00', $disponibles[$part->id]['montantUtilise']);
        $this->assertSame('300.00', $disponibles[$part->id]['montantRestant']);
    }

    public function test_an_avance_used_on_its_own_record_reports_no_transfer(): void
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        [$ins, $fee] = $this->inscriptionWithFee($student, 'Frais de Septembre', 500);
        [, $autre] = $this->inscriptionWithFee($student, "Frais d'Octobre", 500);
        $this->actingAs($this->user)->post(route('backoffice.encaissements.store'), [
            'student_id' => $student->id, 'inscription_id' => $ins->id, 'date_paiement' => '2025-09-20',
            'payment_lines' => [['fee_id' => $fee->id, 'montant' => '500.00', 'methode' => 'Espèces', 'date_paiement' => '2025-09-20']],
        ])->assertSessionHasNoErrors();
        $avance = Encaissement::query()->where('student_id', $student->id)->latest('id')->firstOrFail();
        app(ConvertirEncaissementsEnAvance::class)->handle($ins, [$avance->id]);
        app(AppliquerAvance::class)->handle($avance->fresh(), $autre, 200.0);

        $row = $this->avances('restant')[$avance->id];
        $this->assertSame('200.00', $row['montantUtilise']);
        $this->assertSame('0.00', $row['montantTransfere']);
        $this->assertSame('300.00', $row['montantRestant']);
    }
}
