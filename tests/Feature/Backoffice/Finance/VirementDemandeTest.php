<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Models\AnneeScolaire;
use App\Models\Caisse;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Models\Virement;
use App\Services\Context\CurrentContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Demandes de virement (07/10/2026).
 *
 *  - un virement n'est plus encaissé directement : la ligne « Virement » du
 *    modal de paiement est refusée, elle ouvre une DEMANDE (payeur, référence
 *    bancaire, justificatif) qui ne bouge aucune caisse ;
 *  - le comptable (`virements.validate`) VALIDE — l'encaissement naît alors,
 *    daté du jour de l'opération, au nom de l'employé qui a reçu l'étudiant,
 *    sur le compte « Virement » du centre de la demande — ou REFUSE avec un
 *    motif ; celui qui a déclaré ne valide jamais sa propre demande ;
 *  - la page des paiements compte les demandes en attente À PART de
 *    « Montant total », qui ne les intègre qu'à la validation.
 */
final class VirementDemandeTest extends TestCase
{
    use RefreshDatabase;

    private AnneeScolaire $annee;

    private Etablissement $centre;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2025-10-07 10:00:00');
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('media');
        $this->annee = AnneeScolaire::create([
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

    private function actingInCentre(User $user): self
    {
        $this->actingAs($user);
        app(CurrentContext::class)->setEtablissement($this->centre->id);

        return $this;
    }

    /** @return array{0: Student, 1: Inscription, 2: InscriptionFee} */
    private function enrolledStudentWithFee(float $montant = 1500): array
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $group = Group::factory()->create(['etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id]);
        $inscription = Inscription::create([
            'reference' => 'INS-'.fake()->unique()->numerify('#####'),
            'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => Inscription::STATUT_ACTIVE, 'date_inscription' => '2025-09-15',
            'montant_total' => $montant,
        ]);
        $fee = InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => 'Frais de Novembre',
            'montant_initial' => $montant, 'montant' => $montant,
            'date_echeance' => '2025-11-19', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);

        return [$student, $inscription, $fee];
    }

    private function compteVirement(): Caisse
    {
        return Caisse::query()
            ->where('etablissement_id', $this->centre->id)
            ->where('type', Caisse::TYPE_VIREMENT)
            ->firstOrFail();
    }

    private function declarer(User $user, Student $student, Inscription $inscription, InscriptionFee $fee, array $overrides = []): TestResponse
    {
        return $this->actingInCentre($user)->post(route('backoffice.virements.store'), [
            'student_id' => $student->id,
            'inscription_id' => $inscription->id,
            'fee_id' => $fee->id,
            'montant' => '1000',
            'nom_payeur' => 'Ahmed Belattar',
            'reference_virement' => 'VIR-2025-10-07-001',
            'date_operation' => '2025-10-05',
            'justificatif' => UploadedFile::fake()->image('virement.jpg'),
            ...$overrides,
        ]);
    }

    // --- Déclaration --------------------------------------------------------

    public function test_a_virement_line_in_the_payment_form_is_refused(): void
    {
        $guichet = $this->userWith('payments.view', 'payments.create');
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1000);

        $this->actingInCentre($guichet)->post(route('backoffice.encaissements.store'), [
            'student_id' => $student->id,
            'inscription_id' => $inscription->id,
            'date_paiement' => '2025-10-05',
            'payment_lines' => [
                ['fee_id' => $fee->id, 'montant' => '1000', 'methode' => 'Virement', 'date_paiement' => '2025-10-05'],
            ],
        ])->assertSessionHasErrors('payment_lines.0.methode');

        $this->assertSame(0, Encaissement::count());
        $this->assertSame(InscriptionFee::STATUT_NON_PAYE, $fee->fresh()->statut);
    }

    public function test_declaring_a_transfer_records_a_pending_request_and_moves_no_money(): void
    {
        $guichet = $this->userWith('payments.view', 'payments.create');
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1500);
        $soldeAvant = (float) $this->compteVirement()->solde;

        $this->declarer($guichet, $student, $inscription, $fee)
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $virement = Virement::query()->sole();
        $this->assertSame(Virement::STATUT_EN_ATTENTE, $virement->statut);
        $this->assertStringStartsWith('VIR-', $virement->reference);
        $this->assertSame('1000.00', (string) $virement->montant);
        $this->assertSame('Ahmed Belattar', $virement->nom_payeur);
        $this->assertSame('VIR-2025-10-07-001', $virement->reference_virement);
        // Le guichet ne choisit pas la date : le 05/10 posté est remplacé par le jour même.
        $this->assertSame('2025-10-07', $virement->date_operation->toDateString());
        $this->assertSame($fee->id, $virement->inscription_fee_id);
        $this->assertSame($this->centre->id, $virement->etablissement_id);
        $this->assertSame($guichet->employee->id, $virement->demande_par_id);
        $this->assertNotSame('', $virement->getFirstMediaUrl(Virement::MEDIA_JUSTIFICATIF));

        // Rien n'est encaissé : pas de ligne, pas de solde, frais inchangé.
        $this->assertSame(0, Encaissement::count());
        $this->assertSame($soldeAvant, (float) $this->compteVirement()->fresh()->solde);
        $this->assertSame(InscriptionFee::STATUT_NON_PAYE, $fee->fresh()->statut);

        // La page des paiements le compte À PART de « Montant total ».
        $props = $this->actingInCentre($guichet)
            ->get(route('backoffice.encaissements.index', ['dateFrom' => '', 'dateTo' => '']))
            ->viewData('page')['props'];
        $this->assertSame('0.00', $props['montantTotal']);
        $this->assertSame(1, $props['virementsEnAttente']['count']);
        $this->assertSame('1000.00', $props['virementsEnAttente']['montant']);

        // Et la ligne de frais du modal dit ce qui est déjà déclaré.
        $fees = $this->actingInCentre($guichet)
            ->getJson(route('backoffice.inscriptions.unpaid-fees', $inscription))
            ->json('fees');
        $this->assertSame('1500.00', $fees[0]['reste']);
        $this->assertSame('1000.00', $fees[0]['virementEnAttente']);
    }

    /**
     * Toute l'équipe guichet déclare un virement — assistante administrative
     * et consultant compris ; seule la VALIDATION reste au comptable et au
     * super-admin (07/10/2026).
     */
    public function test_every_front_office_role_can_declare_but_not_validate_a_transfer(): void
    {
        foreach (['administrative-assistant', 'consultant'] as $role) {
            $user = User::factory()->create();
            $user->assignRole($role);
            Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->centre->id]);
            $user = $user->fresh();
            [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1500);

            $this->declarer($user, $student, $inscription, $fee)->assertSessionHasNoErrors()->assertRedirect();
            $this->assertFalse($user->can('virements.validate'), $role);
        }

        $this->assertSame(2, Virement::query()->where('statut', Virement::STATUT_EN_ATTENTE)->count());
    }

    /**
     * Le modal de demande est aussi ouvert depuis la page Inscriptions (son
     * modal « Ajouter un paiement » offre désormais « Virement ») : la
     * demande y ramène, au lieu de jeter le guichet sur la page Paiements.
     */
    public function test_a_transfer_declared_from_the_inscriptions_page_returns_there(): void
    {
        $guichet = $this->userWith('payments.view', 'payments.create', 'registrations.view');
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1500);

        $this->declarer($guichet, $student, $inscription, $fee, ['retour' => 'inscriptions'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('backoffice.inscriptions.index'));

        // Toute autre valeur retombe sur la page Paiements — jamais une URL du client.
        $this->declarer($guichet, $student, $inscription, $fee, ['retour' => 'https://evil.example', 'montant' => '100'])
            ->assertRedirect(route('backoffice.encaissements.index'));
    }

    public function test_only_the_super_admin_chooses_the_operation_date(): void
    {
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1500);

        // Front office : aucune date envoyée = aujourd'hui, jamais une erreur.
        $guichet = $this->userWith('payments.view', 'payments.create');
        $this->declarer($guichet, $student, $inscription, $fee, ['montant' => '500', 'date_operation' => ''])
            ->assertSessionHasNoErrors();
        $this->assertSame('2025-10-07', Virement::query()->latest('id')->first()->date_operation->toDateString());

        // Super-admin : la date choisie est conservée.
        $admin = User::factory()->create();
        $admin->assignRole(Role::SUPER_ADMIN);
        Employee::factory()->create(['user_id' => $admin->id, 'etablissement_id' => $this->centre->id]);
        $this->declarer($admin->fresh(), $student, $inscription, $fee, ['montant' => '500'])
            ->assertSessionHasNoErrors();
        $this->assertSame('2025-10-05', Virement::query()->latest('id')->first()->date_operation->toDateString());
    }

    public function test_the_proof_the_payer_and_the_reference_are_required(): void
    {
        $guichet = $this->userWith('payments.view', 'payments.create');
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee();

        $this->declarer($guichet, $student, $inscription, $fee, [
            'justificatif' => null, 'nom_payeur' => '', 'reference_virement' => '',
        ])->assertSessionHasErrors(['justificatif', 'nom_payeur', 'reference_virement']);

        $this->assertSame(0, Virement::count());
    }

    public function test_the_amount_is_capped_by_the_remaining_due_minus_pending_transfers(): void
    {
        $guichet = $this->userWith('payments.view', 'payments.create');
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1500);

        $this->declarer($guichet, $student, $inscription, $fee, ['montant' => '1000'])->assertSessionHasNoErrors();
        // 500 restent déclarables, pas 600.
        $this->declarer($guichet, $student, $inscription, $fee, ['montant' => '600'])->assertSessionHasErrors('montant');
        $this->declarer($guichet, $student, $inscription, $fee, ['montant' => '500'])->assertSessionHasNoErrors();

        $this->assertSame(2, Virement::count());
    }

    public function test_declaring_requires_payments_create(): void
    {
        $lecteur = $this->userWith('payments.view');
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee();

        $this->declarer($lecteur, $student, $inscription, $fee)->assertForbidden();
        $this->assertSame(0, Virement::count());
    }

    // --- Décision du comptable ---------------------------------------------

    public function test_the_accountant_validates_and_the_payment_is_recorded_at_the_operation_date(): void
    {
        $guichet = $this->userWith('payments.view', 'payments.create');
        $comptable = $this->userWith('payments.view', 'virements.validate');
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1000);
        $this->declarer($guichet, $student, $inscription, $fee)->assertSessionHasNoErrors();
        $virement = Virement::query()->sole();
        $soldeAvant = (float) $this->compteVirement()->solde;

        $this->travelTo('2025-10-09 15:00:00');
        $this->actingInCentre($comptable)
            ->patch(route('backoffice.virements.valider', $virement))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $virement->refresh();
        $this->assertSame(Virement::STATUT_VALIDE, $virement->statut);
        $this->assertSame($comptable->employee->id, $virement->decide_par_id);
        $this->assertNotNull($virement->decide_le);

        $encaissement = Encaissement::query()->sole();
        $this->assertSame($encaissement->id, $virement->encaissement_id);
        $this->assertSame(Encaissement::METHODE_VIREMENT, $encaissement->methode);
        $this->assertSame('1000.00', (string) $encaissement->montant);
        // Daté du jour où l'étudiant s'est présenté (07/10), pas du clic du comptable (09/10).
        $this->assertSame('2025-10-07', $encaissement->date_paiement->toDateString());
        // Au nom de l'employé qui a reçu l'étudiant.
        $this->assertSame($guichet->employee->id, $encaissement->agent_id);
        $this->assertSame($fee->id, $encaissement->inscription_fee_id);
        $this->assertStringContainsString('VIR-2025-10-07-001', (string) $encaissement->note);
        $this->assertStringContainsString('Ahmed Belattar', (string) $encaissement->note);

        // Le compte « Virement » du centre est crédité, le frais soldé.
        $this->assertSame($this->compteVirement()->id, $encaissement->caisse_id);
        $this->assertSame($soldeAvant + 1000.0, (float) $this->compteVirement()->fresh()->solde);
        $this->assertSame(InscriptionFee::STATUT_PAYE, $fee->fresh()->statut);

        // Et « Montant total » l'intègre maintenant, la boîte de réception est vide.
        $props = $this->actingInCentre($comptable)
            ->get(route('backoffice.encaissements.index', ['dateFrom' => '', 'dateTo' => '']))
            ->viewData('page')['props'];
        $this->assertSame('1000.00', $props['montantTotal']);
        $this->assertSame(0, $props['virementsEnAttente']['count']);
    }

    public function test_the_declarer_cannot_validate_their_own_request(): void
    {
        $polyvalent = $this->userWith('payments.view', 'payments.create', 'virements.validate');
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1000);
        $this->declarer($polyvalent, $student, $inscription, $fee)->assertSessionHasNoErrors();
        $virement = Virement::query()->sole();

        $this->actingInCentre($polyvalent)
            ->patch(route('backoffice.virements.valider', $virement))
            ->assertSessionHasErrors('statut');

        $this->assertSame(Virement::STATUT_EN_ATTENTE, $virement->fresh()->statut);
        $this->assertSame(0, Encaissement::count());
    }

    public function test_refusing_keeps_the_row_with_its_reason_and_moves_no_money(): void
    {
        $guichet = $this->userWith('payments.view', 'payments.create');
        $comptable = $this->userWith('payments.view', 'virements.validate');
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1000);
        $this->declarer($guichet, $student, $inscription, $fee)->assertSessionHasNoErrors();
        $virement = Virement::query()->sole();

        // Le motif est obligatoire.
        $this->actingInCentre($comptable)
            ->patch(route('backoffice.virements.refuser', $virement), ['motif' => ''])
            ->assertSessionHasErrors('motif');

        $this->actingInCentre($comptable)
            ->patch(route('backoffice.virements.refuser', $virement), ['motif' => 'Aucun virement de ce montant sur le relevé'])
            ->assertSessionHasNoErrors();

        $virement->refresh();
        $this->assertSame(Virement::STATUT_REFUSE, $virement->statut);
        $this->assertSame('Aucun virement de ce montant sur le relevé', $virement->motif_refus);
        $this->assertSame($comptable->employee->id, $virement->decide_par_id);
        $this->assertSame(0, Encaissement::count());
        $this->assertSame(InscriptionFee::STATUT_NON_PAYE, $fee->fresh()->statut);

        // Une demande refusée ne bloque plus le reste déclarable.
        $this->declarer($guichet, $student, $inscription, $fee, ['montant' => '1000'])->assertSessionHasNoErrors();
    }

    public function test_a_decided_request_cannot_be_decided_twice(): void
    {
        $guichet = $this->userWith('payments.view', 'payments.create');
        $comptable = $this->userWith('payments.view', 'virements.validate');
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1000);
        $this->declarer($guichet, $student, $inscription, $fee)->assertSessionHasNoErrors();
        $virement = Virement::query()->sole();

        $this->actingInCentre($comptable)->patch(route('backoffice.virements.valider', $virement))->assertSessionHasNoErrors();
        $this->actingInCentre($comptable)->patch(route('backoffice.virements.valider', $virement))->assertSessionHasErrors('statut');
        $this->actingInCentre($comptable)
            ->patch(route('backoffice.virements.refuser', $virement), ['motif' => 'trop tard'])
            ->assertSessionHasErrors('statut');

        $this->assertSame(1, Encaissement::count());
        $this->assertSame(Virement::STATUT_VALIDE, $virement->fresh()->statut);
    }

    public function test_validation_is_refused_when_the_fee_was_settled_meanwhile(): void
    {
        $guichet = $this->userWith('payments.view', 'payments.create');
        $comptable = $this->userWith('payments.view', 'virements.validate');
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1000);
        $this->declarer($guichet, $student, $inscription, $fee)->assertSessionHasNoErrors();
        $virement = Virement::query()->sole();

        // Entre-temps l'étudiant règle le même frais en espèces au guichet.
        $this->actingInCentre($guichet)->post(route('backoffice.encaissements.store'), [
            'student_id' => $student->id,
            'inscription_id' => $inscription->id,
            'date_paiement' => '2025-10-06',
            'payment_lines' => [
                ['fee_id' => $fee->id, 'montant' => '1000', 'methode' => 'Espèces', 'date_paiement' => '2025-10-06'],
            ],
        ])->assertSessionHasNoErrors();

        // La liste du comptable dit pourquoi AVANT le clic…
        $row = $this->actingInCentre($comptable)
            ->get(route('backoffice.virements.index', ['statutFilter' => Virement::STATUT_EN_ATTENTE]))
            ->viewData('page')['props']['virements']['data'][0];
        $this->assertNotNull($row['validationBlocker']);
        $this->assertFalse($row['autoValidation']);

        // …et l'action refuse sous verrou.
        $this->actingInCentre($comptable)
            ->patch(route('backoffice.virements.valider', $virement))
            ->assertSessionHasErrors('statut');

        $this->assertSame(Virement::STATUT_EN_ATTENTE, $virement->fresh()->statut);
        $this->assertSame(1, Encaissement::count());
    }

    // --- Permissions --------------------------------------------------------

    public function test_validation_requires_the_dedicated_permission(): void
    {
        $guichet = $this->userWith('payments.view', 'payments.create');
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1000);
        $this->declarer($guichet, $student, $inscription, $fee)->assertSessionHasNoErrors();
        $virement = Virement::query()->sole();

        $this->actingInCentre($guichet)->patch(route('backoffice.virements.valider', $virement))->assertForbidden();
        $this->actingInCentre($guichet)
            ->patch(route('backoffice.virements.refuser', $virement), ['motif' => 'non'])
            ->assertForbidden();

        $this->assertSame(Virement::STATUT_EN_ATTENTE, $virement->fresh()->statut);

        // Le comptable la porte par son preset ; le guichet et la direction non.
        $this->assertTrue(Role::findByName('accountant')->hasPermissionTo('virements.validate'));
        $this->assertFalse(Role::findByName('administrative-assistant')->hasPermissionTo('virements.validate'));
        $this->assertFalse(Role::findByName('consultant')->hasPermissionTo('virements.validate'));
        $this->assertFalse(Role::findByName('director')->hasPermissionTo('virements.validate'));
    }

    public function test_the_virements_page_opens_on_pending_requests(): void
    {
        $guichet = $this->userWith('payments.view', 'payments.create');
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1000);
        $this->declarer($guichet, $student, $inscription, $fee)->assertSessionHasNoErrors();

        $this->actingInCentre($guichet)
            ->get(route('backoffice.virements.index'))
            ->assertRedirect(route('backoffice.virements.index', ['statutFilter' => Virement::STATUT_EN_ATTENTE]));

        $props = $this->actingInCentre($guichet)
            ->get(route('backoffice.virements.index', ['statutFilter' => Virement::STATUT_EN_ATTENTE]))
            ->assertOk()
            ->viewData('page')['props'];
        $this->assertSame('Backoffice/Virements/Index', $this->actingInCentre($guichet)
            ->get(route('backoffice.virements.index', ['statutFilter' => Virement::STATUT_EN_ATTENTE]))
            ->viewData('page')['component']);
        $this->assertCount(1, $props['virements']['data']);
        $this->assertSame('1000.00', $props['montantTotal']);
        $this->assertSame(1, $props['enAttente']['count']);
        $this->assertFalse($props['canValidate']);
        $this->assertSame($student->nomComplet(), $props['virements']['data'][0]['studentNom']);
        $this->assertSame('Frais de Novembre', $props['virements']['data'][0]['feeNom']);
        $this->assertNotNull($props['virements']['data'][0]['justificatifUrl']);
    }
}
