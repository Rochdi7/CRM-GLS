<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Domain\Payments\Actions\RestituerChequeGarantie;
use App\Models\Activity;
use App\Models\AnneeScolaire;
use App\Models\Caisse;
use App\Models\Cheque;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Restitution d'un chèque de GARANTIE parce que l'étudiant a réglé
 * autrement (espèces / TPE / virement) — le cas réel : il laisse un chèque
 * en garantie, annonce la date à laquelle il apportera l'argent, revient ce
 * jour-là, paie en liquide et repart avec son papier.
 *
 * Deux moitiés indissociables, et c'est la seconde qui protège la caisse :
 *   1. le chèque PEUT sortir de l'inventaire (avant, il restait
 *      « En possession » pour toujours) ;
 *   2. une fois sorti, il ne peut PLUS rien payer — ni dans le menu
 *      déroulant, ni par une requête forgée.
 */
final class RestitutionChequeGarantieTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $centre;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->centre = Etablissement::factory()->create();
    }

    private function userWith(string ...$permissions): User
    {
        $user = User::factory()->create();
        foreach ([...$permissions, 'centers.access-all'] as $p) {
            $user->givePermissionTo($p);
        }
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->centre->id]);

        return $user->fresh();
    }

    private function cheque(array $attributes = []): Cheque
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $agent = Employee::factory()->create(['etablissement_id' => $this->centre->id]);

        return Cheque::create([
            'reference' => 'CHQ-'.str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT),
            'source' => Cheque::SOURCE_ETUDIANT,
            'student_id' => $student->id,
            'numero_cheque' => 'A'.random_int(10000, 99999),
            'montant' => '5000.00',
            'banque' => 'Attijariwafa Bank',
            'date_reception' => '2026-08-11',
            'type' => Cheque::TYPE_GARANTIE,
            'date_echeance' => '2026-06-19',
            'statut' => Cheque::STATUT_EN_POSSESSION,
            'etablissement_id' => $this->centre->id,
            'agent_id' => $agent->id,
            ...$attributes,
        ]);
    }

    public function test_a_guarantee_cheque_in_hand_is_returned_to_its_owner(): void
    {
        $user = $this->userWith('cheques.view', 'cheques.deposit');
        $cheque = $this->cheque();

        $this->actingAs($user)
            ->patch(route('backoffice.cheques.restituer-garantie', $cheque), [
                'motif' => 'Réglé en espèces le 19/09/2026',
            ])
            ->assertRedirect();

        $cheque->refresh();
        $this->assertTrue($cheque->estRetourne());
        $this->assertSame($user->employee->id, $cheque->retourne_par_id);
        // Le motif survit dans la note — c'est le seul endroit, avec le
        // journal, où l'on pourra plus tard expliquer la sortie.
        $this->assertStringContainsString('Réglé en espèces', (string) $cheque->note);
        $this->assertStringContainsString('[RESTITUÉ]', (string) $cheque->note);
        // ⚠ Le statut ne bouge PAS : « En possession » décrit le parcours
        // BANCAIRE du chèque, la restitution est un fait distinct porté par
        // retourne_le. Aucune colonne n'a été ajoutée à la table.
        $this->assertSame(Cheque::STATUT_EN_POSSESSION, $cheque->statut);
    }

    public function test_the_return_moves_no_money_at_all(): void
    {
        $user = $this->userWith('cheques.view', 'cheques.deposit');
        $cheque = $this->cheque();

        $soldesAvant = Caisse::query()->pluck('solde', 'id')->all();
        $mouvementsAvant = Activity::query()
            ->where('log_name', 'caisse')->where('event', 'solde_movement')->count();

        $this->actingAs($user)
            ->patch(route('backoffice.cheques.restituer-garantie', $cheque), ['motif' => 'Réglé en espèces'])
            ->assertRedirect();

        // Un chèque est un inventaire OFF-LEDGER : la caisse n'a pas bougé
        // quand il est entré, elle ne bouge pas quand il sort.
        $this->assertSame($soldesAvant, Caisse::query()->pluck('solde', 'id')->all());
        $this->assertSame($mouvementsAvant, Activity::query()
            ->where('log_name', 'caisse')->where('event', 'solde_movement')->count());
    }

    public function test_the_return_is_journaled_with_the_amount_and_the_reason(): void
    {
        $user = $this->userWith('cheques.view', 'cheques.deposit');
        $cheque = $this->cheque();

        $this->actingAs($user)
            ->patch(route('backoffice.cheques.restituer-garantie', $cheque), [
                'motif' => 'Réglé par virement, chèque rendu au guichet',
            ])
            ->assertRedirect();

        $entry = Activity::query()
            ->where('log_name', 'cheque')
            ->where('event', 'cheque_restitue')
            ->latest('id')
            ->firstOrFail();

        // « retourne_le : vide → … » ne dit pas ce que cela SIGNIFIE : une
        // garantie de 5 000 DH a quitté l'école. Le montant et le motif
        // appartiennent à la ligne que lira un contrôleur.
        $this->assertSame($cheque->reference, $entry->properties['reference']);
        $this->assertSame('5000.00', $entry->properties['montant']);
        $this->assertStringContainsString('virement', $entry->properties['motif']);
    }

    public function test_a_reason_is_required(): void
    {
        $cheque = $this->cheque();

        $this->actingAs($this->userWith('cheques.view', 'cheques.deposit'))
            ->patch(route('backoffice.cheques.restituer-garantie', $cheque), ['motif' => '   '])
            ->assertSessionHasErrors('motif');

        $this->assertFalse($cheque->fresh()->estRetourne());
    }

    public function test_a_cheque_to_deposit_is_refused(): void
    {
        // Un chèque « À déposer » se remet à la banque, il ne se rend pas :
        // son parcours est déjà couvert par « Remise à la banque ».
        $cheque = $this->cheque(['type' => Cheque::TYPE_A_DEPOSER]);

        $this->actingAs($this->userWith('cheques.view', 'cheques.deposit'))
            ->patch(route('backoffice.cheques.restituer-garantie', $cheque), ['motif' => 'Réglé en espèces'])
            ->assertSessionHasErrors('motif');

        $this->assertFalse($cheque->fresh()->estRetourne());
    }

    public function test_a_deposited_cheque_is_refused(): void
    {
        // « Déposé » veut dire que le papier est à la banque : on ne peut
        // pas rendre ce qu'on n'a plus en main.
        $cheque = $this->cheque(['statut' => Cheque::STATUT_DEPOSE]);

        $this->actingAs($this->userWith('cheques.view', 'cheques.deposit'))
            ->patch(route('backoffice.cheques.restituer-garantie', $cheque), ['motif' => 'Réglé en espèces'])
            ->assertSessionHasErrors('motif');

        $this->assertFalse($cheque->fresh()->estRetourne());
    }

    public function test_a_cheque_that_already_funded_a_payment_is_refused(): void
    {
        $user = $this->userWith('cheques.view', 'cheques.deposit');
        $cheque = $this->cheque();

        $caisse = Caisse::factory()->create(['etablissement_id' => $this->centre->id]);

        Encaissement::create([
            'reference' => 'ENC-'.random_int(10000, 99999),
            'student_id' => $cheque->student_id,
            'etablissement_id' => $this->centre->id,
            'inscription_fee_id' => null,
            'cheque_id' => $cheque->id,
            'montant' => '1000.00',
            'methode' => Encaissement::METHODE_CHEQUE,
            'date_paiement' => '2026-08-20',
            'caisse_id' => $caisse->id,
            'agent_id' => $user->employee->id,
        ]);

        $this->actingAs($user)
            ->patch(route('backoffice.cheques.restituer-garantie', $cheque), ['motif' => 'Réglé en espèces'])
            ->assertSessionHasErrors('motif');

        // Le papier a payé : il n'est plus à rendre, et les lignes qui
        // pointent dessus sont append-only.
        $this->assertFalse($cheque->fresh()->estRetourne());
    }

    public function test_a_cheque_is_never_returned_twice(): void
    {
        $user = $this->userWith('cheques.view', 'cheques.deposit');
        $cheque = $this->cheque();

        $this->actingAs($user)
            ->patch(route('backoffice.cheques.restituer-garantie', $cheque), ['motif' => 'Premier'])
            ->assertRedirect();

        $premiereDate = $cheque->fresh()->retourne_le;

        $this->actingAs($user)
            ->patch(route('backoffice.cheques.restituer-garantie', $cheque), ['motif' => 'Second'])
            ->assertSessionHasErrors('motif');

        $this->assertEquals($premiereDate, $cheque->fresh()->retourne_le);
    }

    public function test_returning_requires_the_deposit_permission(): void
    {
        $cheque = $this->cheque();

        // Rendre le papier au guichet n'est PAS réécrire le document :
        // `cheques.update` seul ne suffit pas.
        $this->actingAs($this->userWith('cheques.view', 'cheques.update'))
            ->patch(route('backoffice.cheques.restituer-garantie', $cheque), ['motif' => 'Réglé en espèces'])
            ->assertForbidden();

        $this->assertFalse($cheque->fresh()->estRetourne());
    }

    // --- La seconde moitié : un chèque rendu ne paie plus rien ---

    public function test_a_returned_cheque_disappears_from_the_payment_dropdown(): void
    {
        $user = $this->userWith('cheques.view', 'cheques.deposit');
        $cheque = $this->cheque();
        $student = $cheque->student;

        $this->actingAs($user)
            ->getJson(route('backoffice.students.cheques', $student))
            ->assertOk()
            ->assertJsonCount(1, 'cheques')
            // Il est aussi RAPPELÉ comme garantie en main, pour que la
            // caissière n'oublie pas de rendre le papier.
            ->assertJsonCount(1, 'garanties');

        $this->actingAs($user)
            ->patch(route('backoffice.cheques.restituer-garantie', $cheque), ['motif' => 'Réglé en espèces'])
            ->assertRedirect();

        // Le papier n'est plus chez nous : l'offrir encore laisserait
        // dépenser dans le CRM une feuille qui n'existe plus physiquement.
        $this->actingAs($user)
            ->getJson(route('backoffice.students.cheques', $student))
            ->assertOk()
            ->assertJsonCount(0, 'cheques')
            ->assertJsonCount(0, 'garanties');
    }

    public function test_a_returned_cheque_reads_as_restitue_in_the_statut_column(): void
    {
        $user = $this->userWith('cheques.view', 'cheques.deposit');
        $cheque = $this->cheque();

        $this->assertSame('En possession', $this->rows($user)[$cheque->id]['statutAffiche']);

        $this->actingAs($user)
            ->patch(route('backoffice.cheques.restituer-garantie', $cheque), ['motif' => 'Réglé en espèces'])
            ->assertRedirect();

        // Ce que l'utilisateur LIT doit dire où est le chèque MAINTENANT.
        // Le laisser « En possession » après l'avoir rendu fait mentir
        // l'écran (signalé le 19/09/2026).
        $this->assertSame('Restitué', $this->rows($user)[$cheque->id]['statutAffiche']);

        // ⚠ La colonne stockée n'a PAS changé : elle décrit le parcours
        // bancaire et ne connaît pas « Restitué ». Aucun statut, aucune
        // colonne, aucune migration n'a été ajoutée.
        $this->assertSame(Cheque::STATUT_EN_POSSESSION, $cheque->fresh()->statut);
        $this->assertNotContains('Restitué', Cheque::STATUTS);
    }

    public function test_the_statut_filter_understands_restitue_in_both_directions(): void
    {
        $user = $this->userWith('cheques.view', 'cheques.deposit');
        $rendu = $this->cheque();
        $enMain = $this->cheque();

        $this->actingAs($user)
            ->patch(route('backoffice.cheques.restituer-garantie', $rendu), ['motif' => 'Réglé en espèces'])
            ->assertRedirect();

        // Un badge que l'on affiche doit être filtrable, sinon l'écran
        // propose un état qu'aucun filtre ne retrouve.
        $restitues = $this->rows($user, ['statutFilter' => 'Restitué']);
        $this->assertArrayHasKey($rendu->id, $restitues);
        $this->assertArrayNotHasKey($enMain->id, $restitues);

        // Et dans l'autre sens : « En possession » ne doit plus ramener un
        // chèque rendu, puisqu'il ne s'affiche plus ainsi.
        $enPossession = $this->rows($user, ['statutFilter' => 'En possession']);
        $this->assertArrayHasKey($enMain->id, $enPossession);
        $this->assertArrayNotHasKey($rendu->id, $enPossession);
    }

    /**
     * Les lignes de la liste, indexées par id — la page AFFICHE le verdict
     * du serveur, elle ne le redérive jamais (§5).
     *
     * @param  array<string, string>  $query
     * @return array<int, array<string, mixed>>
     */
    private function rows(User $user, array $query = []): array
    {
        $rows = [];

        $this->actingAs($user)
            ->get(route('backoffice.cheques.index', $query))
            ->assertOk()
            ->assertInertia(function (Assert $page) use (&$rows): void {
                $rows = collect($page->toArray()['props']['cheques']['data'])->keyBy('id')->all();
            });

        return $rows;
    }

    public function test_a_returned_cheque_drops_out_of_the_header_total(): void
    {
        $user = $this->userWith('cheques.view', 'cheques.deposit');
        $rendu = $this->cheque(['montant' => '1400.00']);
        $this->cheque(['montant' => '600.00']);

        $this->assertSame('2000.00', $this->montantTotal($user));

        $this->actingAs($user)
            ->patch(route('backoffice.cheques.restituer-garantie', $rendu), ['motif' => 'Réglé en espèces'])
            ->assertRedirect();

        // « Montant total » chapeaute les papiers que l'école DÉTIENT. Un
        // chèque rendu n'est plus là : le compter gonflerait la garantie que
        // l'école croit avoir, et la page se contredirait — une ligne badgée
        // « Restitué » qui pèse quand même dans le total au-dessus d'elle.
        $this->assertSame('600.00', $this->montantTotal($user));

        // EXCEPTION : quand on DEMANDE les restitués, le total porte sur
        // eux — sinon la page montrerait des lignes et un total à 0,00.
        $this->assertSame('1400.00', $this->montantTotal($user, ['statutFilter' => 'Restitué']));
    }

    /** @param  array<string, string>  $query */
    private function montantTotal(User $user, array $query = []): string
    {
        $total = '';

        $this->actingAs($user)
            ->get(route('backoffice.cheques.index', $query))
            ->assertOk()
            ->assertInertia(function (Assert $page) use (&$total): void {
                $total = $page->toArray()['props']['montantTotal'];
            });

        return $total;
    }

    public function test_the_stored_type_keeps_its_full_value(): void
    {
        $user = $this->userWith('cheques.view', 'cheques.deposit');
        $cheque = $this->cheque();

        // L'écran raccourcit « Garantie (À encaisser) » en « Garantie »,
        // mais c'est un libellé d'AFFICHAGE (Cheques/Index.tsx::typeLabel).
        // La valeur STOCKÉE ne bouge pas : elle est en base sur chaque
        // ligne, dans le journal d'audit et dans les exports, et la
        // raccourcir à la source demanderait un UPDATE en production pour
        // un gain purement cosmétique (§11).
        $this->assertSame('Garantie (À encaisser)', $cheque->fresh()->type);
        $this->assertSame('Garantie (À encaisser)', $this->rows($user)[$cheque->id]['type']);
        $this->assertContains('Garantie (À encaisser)', Cheque::TYPES);
    }

    public function test_the_read_model_reports_whether_a_row_can_be_returned(): void
    {
        $user = $this->userWith('cheques.view', 'cheques.deposit');
        $garantie = $this->cheque();
        $aDeposer = $this->cheque(['type' => Cheque::TYPE_A_DEPOSER]);

        // La page AFFICHE le verdict du serveur, elle ne le redérive pas (§5).
        $rows = null;

        $this->actingAs($user)
            ->get(route('backoffice.cheques.index'))
            ->assertOk()
            ->assertInertia(function (Assert $page) use (&$rows): void {
                $rows = collect($page->toArray()['props']['cheques']['data'])->keyBy('id');
            });

        $this->assertTrue($rows[$garantie->id]['restituable']);
        $this->assertFalse($rows[$aDeposer->id]['restituable']);
    }
}
