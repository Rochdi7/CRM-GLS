<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Students;

use App\Domain\Finance\Support\CaisseLedger;
use App\Domain\Finance\Support\VentilationCentre;
use App\Domain\Payments\Queries\GetEncaissementsList;
use App\Domain\Reports\Actions\GetDashboardStats;
use App\Models\Activity;
use App\Models\AnneeScolaire;
use App\Models\Caisse;
use App\Models\Cheque;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Frais;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Remboursement;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentTransfer;
use App\Models\User;
use App\Services\Context\CurrentContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Recette « QA » du transfert d'étudiant entre centres (25/09/2026) : ce
 * que l'étudiant retrouve à l'arrivée (avances, chèques, remboursements) et
 * ce qui ne doit JAMAIS bouger (les caisses).
 *
 *  1. une avance suit l'étudiant, reste applicable au nouveau centre par
 *     l'écran ordinaire, avec son reste exact ; l'ancien centre ne la voit
 *     plus ;
 *  2. une avance PARTIELLEMENT appliquée suit avec sa ligne d'application ;
 *  3. un chèque de garantie suit : la copie paie avec, l'ancienne fiche non ;
 *  4. un remboursement partiel antérieur reste lié et le frais déplacé
 *     affiche le NET ; un remboursement APRÈS transfert débite la caisse
 *     ordinaire, jamais une caisse fantôme ;
 *  5. AUCUNE caisse ne bouge, le ledger n'a pas une ligne de plus, la
 *     ventilation par centre est identique et `caisse:verifier-coherence
 *     --strict` reste à 0 ;
 *  6. un second transfert (retour) enchaîne proprement.
 */
final class TransfertEtudiantQaTest extends TestCase
{
    use RefreshDatabase;

    private AnneeScolaire $annee;

    private Etablissement $rabat;

    private Etablissement $casa;

    private Employee $guichet;

    private Caisse $till;

    private Group $groupeRabat;

    private Group $groupeCasa;

    private User $admin;

    private static int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->annee = AnneeScolaire::create([
            'nom' => '2026/2027', 'date_debut' => '2026-09-01', 'date_fin' => '2027-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->rabat = Etablissement::factory()->create(['nom_centre' => 'GLS Rabat']);
        $this->casa = Etablissement::factory()->create(['nom_centre' => 'GLS Casablanca']);

        // Le guichet de Rabat est aussi le caissier dont le tiroir a reçu l'argent.
        $this->guichet = Employee::factory()->create(['etablissement_id' => $this->rabat->id]);
        $this->guichet->user->forceFill(['must_change_password' => false])->save();
        $this->guichet->user->givePermissionTo(['students.view', 'student-transfers.view', 'student-transfers.create', 'payments.view', 'payments.create']);
        $this->till = $this->guichet->till()->firstOrFail();
        // Solde d'ouverture PAR LE LEDGER, jamais un update brut (§11) — sinon
        // l'auditeur strict signale un écart solde / journal qui n'a rien à
        // voir avec le transfert.
        app(CaisseLedger::class)->credit($this->till, 5000, 'Solde d ouverture (test)', $this->till);
        $this->till->refresh();

        $adminEmployee = Employee::factory()->create(['etablissement_id' => $this->casa->id]);
        $this->admin = $adminEmployee->user;
        $this->admin->forceFill(['must_change_password' => false])->save();
        $this->admin->syncRoles([Role::SUPER_ADMIN]);
        $this->admin = $this->admin->fresh();
        app(CaisseLedger::class)->credit($adminEmployee->till()->firstOrFail(), 1000, 'Solde d ouverture (test)', $adminEmployee->till()->firstOrFail());

        $this->groupeRabat = Group::factory()->create(['etablissement_id' => $this->rabat->id, 'annee_scolaire_id' => $this->annee->id, 'nom' => 'Rabat A1']);
        $this->groupeCasa = Group::factory()->create(['etablissement_id' => $this->casa->id, 'annee_scolaire_id' => $this->annee->id, 'nom' => 'Casa A1']);
        $this->groupeCasa->frais()->attach($this->frais('Frais de Novembre')->id, ['montant' => 600, 'date_echeance' => '2026-11-01']);
    }

    private function frais(string $nom): Frais
    {
        return Frais::firstOrCreate(['nom' => $nom], ['statut' => 'Actif', 'montant_defaut' => 100]);
    }

    private function etudiant(): Student
    {
        return Student::factory()->create(['etablissement_id' => $this->rabat->id, 'nom' => 'ZAHIRI', 'prenom' => 'Nour']);
    }

    private function inscription(Student $student, Group $group): Inscription
    {
        return Inscription::create([
            'reference' => 'INS-Q'.(++self::$sequence),
            'student_id' => $student->id,
            'group_id' => $group->id,
            'etablissement_id' => $group->etablissement_id,
            'annee_scolaire_id' => $this->annee->id,
            'statut' => Inscription::STATUT_ACTIVE,
            'date_inscription' => '2026-09-10',
        ]);
    }

    private function fee(Inscription $inscription, string $nom, float $montant, string $statut = InscriptionFee::STATUT_NON_PAYE): InscriptionFee
    {
        return InscriptionFee::create([
            'inscription_id' => $inscription->id,
            'frais_id' => $this->frais($nom)->id,
            'nom' => $nom,
            'montant_initial' => $montant,
            'montant' => $montant,
            'date_echeance' => '2026-10-01',
            'statut' => $statut,
        ]);
    }

    private function paiement(Student $student, ?InscriptionFee $fee, float $montant, array $extra = []): Encaissement
    {
        return Encaissement::create([
            'reference' => 'ENC-Q'.(++self::$sequence),
            'student_id' => $student->id,
            'etablissement_id' => $this->rabat->id,
            'inscription_fee_id' => $fee?->id,
            'caisse_id' => $this->till->id,
            'agent_id' => $this->guichet->id,
            'montant' => $montant,
            'methode' => 'Espèces',
            'date_paiement' => '2026-09-12',
            ...$extra,
        ]);
    }

    private function transferer(Student $student): StudentTransfer
    {
        $this->actingAs($this->guichet->user)->post(route('backoffice.student-transfers.store'), [
            'student_id' => $student->id,
            'etablissement_cible_id' => $this->casa->id,
            'group_cible_id' => $this->groupeCasa->id,
            'motif' => 'Déménagement.',
        ])->assertSessionHasNoErrors();

        $transfert = StudentTransfer::query()->where('student_id', $student->id)->where('statut', StudentTransfer::STATUT_EN_ATTENTE)->sole();

        $this->tousLesCentres();
        $this->actingAs($this->admin)->put(route('backoffice.student-transfers.validate', $transfert))->assertSessionHasNoErrors();

        return $transfert->fresh();
    }

    /** Le super-admin agit depuis « Tous les centres » (la session de test est partagée entre les acteurs). */
    private function tousLesCentres(): void
    {
        $this->actingAs($this->admin);
        app(CurrentContext::class)->setEtablissement(null);
    }

    /** @return array<int, string> caisse id => solde */
    private function soldes(): array
    {
        return Caisse::query()->orderBy('id')->pluck('solde', 'id')->map(fn ($s): string => (string) $s)->all();
    }

    private function ledgerCount(): int
    {
        return Activity::query()->where('event', 'solde_movement')->count();
    }

    // ---------------------------------------------------------------
    // 1. Avances
    // ---------------------------------------------------------------

    public function test_an_avance_follows_the_student_and_is_applicable_at_the_new_centre_only(): void
    {
        $student = $this->etudiant();
        $this->inscription($student, $this->groupeRabat);
        $avance = $this->paiement($student, null, 800);

        $transfert = $this->transferer($student);
        $copie = Student::query()->findOrFail($transfert->nouveau_student_id);
        $nouvelle = Inscription::query()->findOrFail($transfert->nouvelle_inscription_id);

        $avance->refresh();
        $this->assertSame($copie->id, $avance->student_id);
        $this->assertTrue($avance->isAvance());
        $this->assertSame(800.0, $avance->montantRestant());

        // Onglet Avances : visible à Casablanca, plus à Rabat.
        $list = app(GetEncaissementsList::class);
        app(CurrentContext::class)->setEtablissement($this->casa->id);
        $this->assertContains($avance->id, collect($list($this->admin, view: 'avance')['data']->items())->pluck('id')->all());
        app(CurrentContext::class)->setEtablissement($this->rabat->id);
        $this->assertNotContains($avance->id, collect($list($this->admin, view: 'avance')['data']->items())->pluck('id')->all());
        $this->tousLesCentres();

        // L'écran ordinaire « Appliquer l'avance » solde le frais du nouveau dossier.
        $feeNov = $nouvelle->fees()->where('nom', 'Frais de Novembre')->sole();
        $this->actingAs($this->admin)
            ->post(route('backoffice.avances.apply', $avance), ['fee_id' => $feeNov->id, 'montant' => 600])
            ->assertSessionHasNoErrors();

        $this->assertSame(InscriptionFee::STATUT_PAYE, $feeNov->fresh()->statut);
        $this->assertSame(200.0, $avance->fresh()->montantRestant());

        $application = Encaissement::query()->where('applied_from_encaissement_id', $avance->id)->sole();
        $this->assertSame($copie->id, $application->student_id);
        // La ligne d'application hérite caisse / agent / date de l'avance — jamais du transfert.
        $this->assertSame($this->till->id, $application->caisse_id);
        $this->assertSame($this->guichet->id, $application->agent_id);
        $this->assertSame('2026-09-12', $application->date_paiement->toDateString());

        // Et l'ancienne fiche ne peut pas ré-appliquer : le frais n'est pas à elle.
        $this->assertSame(0, Encaissement::query()->where('student_id', $student->id)->count());
    }

    public function test_a_partially_applied_avance_moves_with_its_application_row(): void
    {
        $student = $this->etudiant();
        $inscription = $this->inscription($student, $this->groupeRabat);
        $fee = $this->fee($inscription, "Frais d'inscription", 300);
        $avance = $this->paiement($student, null, 1000);
        $application = $this->paiement($student, $fee, 300, ['applied_from_encaissement_id' => $avance->id]);
        $fee->rafraichirStatut();
        $this->assertSame(InscriptionFee::STATUT_PAYE, $fee->fresh()->statut);

        $transfert = $this->transferer($student);
        $copie = Student::query()->findOrFail($transfert->nouveau_student_id);

        $this->assertSame($copie->id, $avance->fresh()->student_id);
        $this->assertSame($copie->id, $application->fresh()->student_id);
        $this->assertSame($transfert->nouvelle_inscription_id, $fee->fresh()->inscription_id);
        $this->assertSame(700.0, $avance->fresh()->montantRestant());
        // Montant emporté = argent REÇU (1000), pas 1000 + 300.
        $this->assertSame('1000.00', (string) $transfert->montant_transfere);
    }

    // ---------------------------------------------------------------
    // 2. Chèques
    // ---------------------------------------------------------------

    public function test_a_guarantee_cheque_follows_the_student_and_only_the_copy_can_pay_with_it(): void
    {
        $student = $this->etudiant();
        $this->inscription($student, $this->groupeRabat);
        $cheque = Cheque::create([
            'reference' => 'CHQ-Q1', 'source' => Cheque::SOURCE_ETUDIANT, 'student_id' => $student->id,
            'numero_cheque' => '123456', 'montant' => 1500, 'banque' => 'CIH', 'date_reception' => '2026-09-10',
            'type' => Cheque::TYPE_GARANTIE, 'statut' => Cheque::STATUT_EN_POSSESSION,
            'etablissement_id' => $this->rabat->id, 'agent_id' => $this->guichet->id,
        ]);

        $transfert = $this->transferer($student);
        $copie = Student::query()->findOrFail($transfert->nouveau_student_id);
        $nouvelle = Inscription::query()->findOrFail($transfert->nouvelle_inscription_id);
        $feeNov = $nouvelle->fees()->where('nom', 'Frais de Novembre')->sole();

        $this->assertSame($copie->id, $cheque->fresh()->student_id);
        $this->assertSame(1500.0, $cheque->fresh()->montantRestant());

        $ligne = fn (int $studentId, int $inscriptionId, int $feeId) => [
            'student_id' => $studentId, 'inscription_id' => $inscriptionId, 'date_paiement' => '2026-10-01',
            'payment_lines' => [['fee_id' => $feeId, 'montant' => 600, 'methode' => 'Chèque', 'date_paiement' => '2026-10-01', 'cheque_id' => $cheque->id]],
        ];

        // La copie paie 600 DH avec le chèque au nouveau centre.
        $this->actingAs($this->admin)->post(route('backoffice.encaissements.store'), $ligne($copie->id, $nouvelle->id, $feeNov->id))
            ->assertSessionHasNoErrors();
        $this->assertSame(900.0, $cheque->fresh()->montantRestant());
        $this->assertSame(InscriptionFee::STATUT_PAYE, $feeNov->fresh()->statut);

        // L'ancienne fiche ne peut plus s'en servir (dossier Transférée, chèque parti).
        $ancienne = Inscription::query()->where('student_id', $student->id)->sole();
        $this->actingAs($this->admin)->post(route('backoffice.encaissements.store'), $ligne($student->id, $ancienne->id, $feeNov->id))
            ->assertSessionHasErrors();
        $this->assertSame(900.0, $cheque->fresh()->montantRestant());
    }

    // ---------------------------------------------------------------
    // 3. Remboursements
    // ---------------------------------------------------------------

    public function test_refunds_before_and_after_the_transfer_stay_coherent(): void
    {
        $student = $this->etudiant();
        $inscription = $this->inscription($student, $this->groupeRabat);
        $fee = $this->fee($inscription, "Frais d'inscription", 300);
        $paiement = $this->paiement($student, $fee, 300);
        $fee->rafraichirStatut();

        // Remboursement partiel AVANT le transfert (100 DH rendus).
        $this->tousLesCentres();
        $this->actingAs($this->admin)->post(route('backoffice.remboursements.store'), [
            'beneficiaire_id' => $student->id, 'encaissement_id' => $paiement->id,
            'montant' => 100, 'date_remboursement' => '2026-09-15', 'motif' => 'Geste commercial',
        ])->assertSessionHasNoErrors();
        $remboursement = Remboursement::query()->sole();
        $soldesAvant = $this->soldes();

        $transfert = $this->transferer($student);
        $copie = Student::query()->findOrFail($transfert->nouveau_student_id);

        // Le frais a suivi (payé net 200 > 0) et affiche le NET ; le remboursement reste lié.
        $this->assertSame($transfert->nouvelle_inscription_id, $fee->fresh()->inscription_id);
        $this->assertSame(200.0, $fee->fresh()->montantPaye());
        $this->assertSame($paiement->id, $remboursement->fresh()->encaissement_id);
        $this->assertSame($copie->id, $paiement->fresh()->student_id);

        // Le transfert n'a touché aucune caisse.
        $this->assertSame($soldesAvant, $this->soldes());

        // Remboursement APRÈS transfert, au nom de la copie : la caisse de
        // l'agent qui rend bouge, et seulement elle.
        $adminTill = $this->admin->employee->till()->firstOrFail();
        $this->assertSame('900.00', (string) $adminTill->solde, '1000 d ouverture - 100 rendus avant le transfert');
        $this->actingAs($this->admin)->post(route('backoffice.remboursements.store'), [
            'beneficiaire_id' => $copie->id, 'encaissement_id' => $paiement->id,
            'montant' => 50, 'date_remboursement' => '2026-10-02', 'motif' => 'Solde',
        ])->assertSessionHasNoErrors();
        $this->assertSame('850.00', (string) $adminTill->fresh()->solde);
        $this->assertSame(150.0, $fee->fresh()->montantPaye());

        // Au nom de l'ANCIENNE fiche, le même paiement n'est plus remboursable.
        $this->actingAs($this->admin)->post(route('backoffice.remboursements.store'), [
            'beneficiaire_id' => $student->id, 'encaissement_id' => $paiement->id,
            'montant' => 10, 'date_remboursement' => '2026-10-02',
        ])->assertSessionHasErrors('encaissement_id');
    }

    // ---------------------------------------------------------------
    // 4. LES CAISSES NE BOUGENT PAS
    // ---------------------------------------------------------------

    public function test_a_transfer_never_touches_any_till_ledger_or_centre_share(): void
    {
        $student = $this->etudiant();
        $inscription = $this->inscription($student, $this->groupeRabat);
        $fee = $this->fee($inscription, "Frais d'inscription", 300);
        $this->paiement($student, $fee, 300);
        $this->paiement($student, null, 450);
        $fee->rafraichirStatut();

        $ventilation = app(VentilationCentre::class);
        $soldesAvant = $this->soldes();
        $ledgerAvant = $this->ledgerCount();
        $partRabatAvant = $ventilation->soldeDuCentre($this->till, $this->rabat->id);
        $partCasaAvant = $ventilation->soldeDuCentre($this->till, $this->casa->id);

        $this->transferer($student);

        $this->assertSame($soldesAvant, $this->soldes(), 'aucune caisse ne doit bouger');
        $this->assertSame($ledgerAvant, $this->ledgerCount(), 'le ledger ne doit pas avoir une ligne de plus');
        $this->assertSame($partRabatAvant, $ventilation->soldeDuCentre($this->till->fresh(), $this->rabat->id), 'la part de Rabat dans le tiroir est inchangée');
        $this->assertSame($partCasaAvant, $ventilation->soldeDuCentre($this->till->fresh(), $this->casa->id), 'Casablanca ne gagne aucune part du tiroir de Rabat');
        // L'argent est toujours stampé Rabat : c'est là qu'il a été encaissé.
        $this->assertSame(0, Encaissement::query()->where('etablissement_id', $this->casa->id)->count());

        $this->assertSame(0, Artisan::call('caisse:verifier-coherence', ['--strict' => true]), Artisan::output());
    }

    // ---------------------------------------------------------------
    // 5. Listes et compteurs
    // ---------------------------------------------------------------

    public function test_the_old_centre_stops_counting_the_student_and_their_money(): void
    {
        $student = $this->etudiant();
        $inscription = $this->inscription($student, $this->groupeRabat);
        $fee = $this->fee($inscription, "Frais d'inscription", 300);
        $paiement = $this->paiement($student, $fee, 300);
        $fee->rafraichirStatut();

        $transfert = $this->transferer($student);

        $list = app(GetEncaissementsList::class);
        $context = app(CurrentContext::class);

        $this->actingAs($this->admin);
        $context->setEtablissement($this->rabat->id);
        $this->assertNotContains($paiement->id, collect($list($this->admin)['data']->items())->pluck('id')->all());
        $this->assertSame(0, app(GetDashboardStats::class)($context)->studentsTotal, 'une fiche Transféré ne compte plus comme étudiant de Rabat');

        $context->setEtablissement($this->casa->id);
        $this->assertContains($paiement->id, collect($list($this->admin)['data']->items())->pluck('id')->all());
        $this->assertSame(1, app(GetDashboardStats::class)($context)->studentsTotal);
        $context->setEtablissement(null);

        $this->assertNotNull($transfert->nouveau_student_id);
    }

    // ---------------------------------------------------------------
    // 6. Retour : un second transfert enchaîne
    // ---------------------------------------------------------------

    public function test_a_second_transfer_back_chains_cleanly(): void
    {
        $student = $this->etudiant();
        $this->inscription($student, $this->groupeRabat);
        $avance = $this->paiement($student, null, 500);

        $t1 = $this->transferer($student);
        $copie = Student::query()->findOrFail($t1->nouveau_student_id);

        // Retour vers Rabat, dans le groupe de Rabat — demandé depuis Casablanca.
        $this->actingAs($this->admin);
        app(CurrentContext::class)->setEtablissement($this->casa->id);
        $this->actingAs($this->admin)->post(route('backoffice.student-transfers.store'), [
            'student_id' => $copie->id, 'etablissement_cible_id' => $this->rabat->id,
            'group_cible_id' => $this->groupeRabat->id, 'motif' => 'Retour.',
        ])->assertSessionHasNoErrors();
        $t2 = StudentTransfer::query()->where('student_id', $copie->id)->sole();
        $this->tousLesCentres();
        $this->actingAs($this->admin)->put(route('backoffice.student-transfers.validate', $t2))->assertSessionHasNoErrors();

        $troisieme = Student::query()->findOrFail($t2->fresh()->nouveau_student_id);
        $this->assertSame(Student::STATUT_TRANSFERE, $copie->fresh()->statut);
        $this->assertSame(Student::STATUT_ACTIF, $troisieme->statut);
        $this->assertSame($this->rabat->id, $troisieme->etablissement_id);
        $this->assertSame($troisieme->id, $avance->fresh()->student_id);
        $this->assertSame(500.0, $avance->fresh()->montantRestant());
        $this->assertSame(3, Student::count());

        // Une seule fiche Active porte cette personne ; l'historique remonte les deux centres.
        $this->actingAs($this->admin)->get(route('backoffice.students.show', $troisieme))
            ->assertInertia(fn ($page) => $page
                ->has('student.historiqueTransfert', 2)
                ->where('student.historiqueTransfert.0.centre', 'GLS Casablanca')
                ->where('student.historiqueTransfert.1.centre', 'GLS Rabat'));
    }
}
