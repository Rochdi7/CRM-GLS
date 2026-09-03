<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Domain\Finance\Queries\GetRemboursementsList;
use App\Models\Caisse;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Remboursement;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rejoue le scénario de production du 03/09/2026 (ELWARDI) et vérifie que la
 * commande de réparation produit exactement UN remboursement de 300 DH,
 * débité de la caisse Rafik, rattaché au centre du paiement (Kénitra), avec
 * la caisse Karouali ramenée de -300 à 0.
 */
final class ReparerRemboursementElwardiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reverses_the_duplicate_and_moves_the_refund_to_the_right_till_and_centre(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $marrakech = Etablissement::factory()->create(['nom_centre' => 'GLS Marrakech']);
        $kenitra = Etablissement::factory()->create(['nom_centre' => 'GLS Kénitra']);

        // Les deux caissiers sont rattachés à Marrakech, l'étudiant à Kénitra —
        // c'est toute l'origine du bug.
        $rafik = Employee::factory()->create(['etablissement_id' => $marrakech->id]);
        $karouali = Employee::factory()->create(['etablissement_id' => $marrakech->id]);

        $caisseRafik = $rafik->till()->firstOrFail();
        $caisseRafik->update(['solde' => 17443991]);
        $caisseKarouali = $karouali->till()->firstOrFail();
        $caisseKarouali->update(['solde' => 0]);

        $student = Student::factory()->create(['etablissement_id' => $kenitra->id]);

        // L'avance réellement encaissée à Kénitra, que RMB-002 rembourse.
        $encaissement = Encaissement::create([
            'reference' => 'ENC-14782',
            'student_id' => $student->id,
            'etablissement_id' => $kenitra->id,
            'inscription_fee_id' => null,
            'caisse_id' => $caisseRafik->id,
            'agent_id' => $rafik->id,
            'montant' => 300,
            'methode' => 'Espèces',
            'date_paiement' => '2026-06-18',
        ]);

        $rmb1 = Remboursement::create([
            'reference' => 'RMB-001',
            'beneficiaire_id' => $student->id,
            'caisse_id' => $caisseRafik->id,
            'montant' => 300,
            'date_remboursement' => '2026-09-03',
            'agent_id' => $rafik->id,
        ]);
        $caisseRafik->update(['solde' => 17443691]); // le débit d'origine

        $rmb2 = Remboursement::create([
            'reference' => 'RMB-002',
            'beneficiaire_id' => $student->id,
            'caisse_id' => $caisseKarouali->id,
            'encaissement_id' => $encaissement->id,
            'montant' => 300,
            'date_remboursement' => '2026-09-03',
            'agent_id' => $karouali->id,
        ]);
        $caisseKarouali->update(['solde' => -300]); // la caisse partie en négatif

        $this->artisan('remboursements:reparer-elwardi --apply')->assertSuccessful();

        // La caisse Karouali revient à zéro : son mouvement a été annulé.
        $this->assertSame('0.00', (string) $caisseKarouali->fresh()->solde);

        // Rafik : recréditée de RMB-001 (+300) puis débitée de RMB-002 (-300)
        // ⇒ elle reste au solde qu'elle avait après le premier débit, et porte
        // désormais l'unique remboursement réel.
        $this->assertSame('17443691.00', (string) $caisseRafik->fresh()->solde);

        // Le remboursement conservé pointe la bonne caisse ET le bon centre.
        $rmb2->refresh();
        $this->assertSame($caisseRafik->id, $rmb2->caisse_id);
        $this->assertSame($kenitra->id, $rmb2->etablissement_id);
        $this->assertSame($encaissement->id, $rmb2->encaissement_id);

        // Le doublon est conservé (append-only) mais annoté, et rattaché lui
        // aussi à Kénitra pour ne pas rester invisible.
        $rmb1->refresh();
        $this->assertStringContainsString('[ANNULÉ]', (string) $rmb1->note);
        $this->assertSame($kenitra->id, $rmb1->etablissement_id);

        // La liste ne doit PAS lire comme 600 DH : le doublon annulé est
        // marqué comme tel, et le journal ne le compte plus du tout.
        $lecteur = User::factory()->create();
        $lecteur->givePermissionTo('refunds.view');
        $lecteur->givePermissionTo('centers.access-all');

        $rows = app(GetRemboursementsList::class)($lecteur->fresh());
        $parReference = collect($rows->items())->keyBy('reference');

        $this->assertTrue($parReference['RMB-001']['annule'], 'RMB-001 doit être marqué annulé.');
        $this->assertFalse($parReference['RMB-002']['annule'], 'RMB-002 est le remboursement réel.');

        // Le total affiché ne compte QUE l'argent réellement sorti : 300 DH,
        // pas 600. C'est ce que l'écran annonçait avant le correctif.
        $totaux = app(GetRemboursementsList::class)->totaux($lecteur->fresh());
        $this->assertSame('300.00', $totaux['montant']);
        $this->assertSame(1, $totaux['count']);
        $this->assertSame(1, $totaux['annules']);

        // Idempotence : relancer ne redéplace rien.
        $this->artisan('remboursements:reparer-elwardi --apply')->assertSuccessful();
        $this->assertSame('17443691.00', (string) $caisseRafik->fresh()->solde);
        $this->assertSame('0.00', (string) $caisseKarouali->fresh()->solde);
    }
}
