<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Domain\Payments\Actions\AppliquerAvance;
use App\Domain\Payments\Actions\ConvertirEncaissementsEnAvance;
use App\Domain\Payments\Support\ValiditeAvance;
use App\Models\Activity;
use App\Models\AnneeScolaire;
use App\Models\Caisse;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Un paiement de plus de 14 jours ne se réutilise plus au guichet
 * (29/09/2026, ValiditeAvance).
 *
 * Le problème : de l'argent ancien restait réutilisable indéfiniment. Un
 * employé pouvait convertir un vieux paiement en avance, ou appliquer une
 * avance vieille de plusieurs mois, « quand il voulait », sans que rien ne
 * le signale. Désormais, passé 14 jours :
 *  - une avance ne s'APPLIQUE plus ;
 *  - un paiement ne se CONVERTIT plus en avance ;
 *  - seul le SUPER-ADMIN garde ces deux gestes, et le journal le note.
 *
 * Ce que ces tests fixent aussi :
 *  - une ligne antérieure à la colonne (NULL) suit la même règle à la
 *    lecture, sans backfill — la production n'est pas réécrite ;
 *  - les flux SYSTÈME (retrait d'un frais payé, changement de groupe…) ne
 *    sont pas bornés, et l'avance qu'ils libèrent a 14 jours devant elle ;
 *  - l'écran porte la règle de l'action (`applicable`, `convertible`) ;
 *  - l'expiration ne touche ni au montant restant ni à la caisse.
 */
final class AvanceExpirationTest extends TestCase
{
    use RefreshDatabase;

    private AnneeScolaire $annee;

    private Etablissement $centre;

    private User $admin;

    private User $guichet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-10-20 10:00:00');
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->annee = AnneeScolaire::create([
            'nom' => '2026/2027', 'date_debut' => '2026-09-01', 'date_fin' => '2027-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->centre = Etablissement::factory()->create();

        $this->admin = $this->userWithRole('super-admin', Employee::CATEGORIE_DIRECTEUR);
        $this->guichet = $this->userWithRole('administrative-assistant', Employee::CATEGORIE_ASSISTANTE_ADMINISTRATIVE);
    }

    private function userWithRole(string $role, string $categorie): User
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'etablissement_id' => $this->centre->id,
            'categorie' => $categorie,
        ]);
        $employee->syncEtablissements([$this->centre->id]);
        $user->syncRoles([$role]);

        return $user->fresh();
    }

    /** @return array{0: Student, 1: Inscription, 2: InscriptionFee} */
    private function enrolled(float $montant = 1000): array
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $group = Group::factory()->create(['etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id]);
        $inscription = Inscription::create([
            'reference' => 'INS-'.fake()->unique()->numerify('#####'),
            'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => Inscription::STATUT_ACTIVE, 'date_inscription' => '2026-09-15',
            'montant_total' => $montant,
        ]);
        $fee = $this->feeSur($inscription, 'Frais de scolarite', $montant);

        return [$student, $inscription, $fee];
    }

    private function feeSur(Inscription $inscription, string $nom, float $montant): InscriptionFee
    {
        return InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => $nom,
            'montant_initial' => $montant, 'montant' => $montant,
            'date_echeance' => '2026-11-30', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);
    }

    private function till(): Caisse
    {
        return $this->admin->employee->till()->firstOrFail();
    }

    private function avance(Student $student, string $date, float $montant = 500): Encaissement
    {
        return Encaissement::create([
            'reference' => 'ENC-'.fake()->unique()->numerify('#####'),
            'student_id' => $student->id,
            'etablissement_id' => $this->centre->id,
            'inscription_fee_id' => null,
            'montant' => $montant,
            'methode' => Encaissement::METHODE_ESPECES,
            'date_paiement' => $date,
            'caisse_id' => $this->till()->id,
            'agent_id' => $this->admin->employee->id,
        ]);
    }

    private function paiementSurFrais(Student $student, InscriptionFee $fee, string $date, float $montant): Encaissement
    {
        $paiement = Encaissement::create([
            'reference' => 'ENC-'.fake()->unique()->numerify('#####'),
            'student_id' => $student->id,
            'etablissement_id' => $this->centre->id,
            'inscription_fee_id' => $fee->id,
            'montant' => $montant,
            'methode' => Encaissement::METHODE_ESPECES,
            'date_paiement' => $date,
            'caisse_id' => $this->till()->id,
            'agent_id' => $this->admin->employee->id,
        ]);

        $fee->rafraichirStatut();

        return $paiement;
    }

    /** @return array<string, mixed>|null */
    private function avanceRow(int $id, string $solde = 'tous'): ?array
    {
        $row = null;

        $this->get(route('backoffice.encaissements.index', ['view' => 'avance', 'soldeFilter' => $solde]))
            ->assertOk()
            ->assertInertia(function (Assert $page) use ($id, &$row): void {
                $row = collect($page->toArray()['props']['encaissements']['data'])->firstWhere('id', $id);
            });

        return $row;
    }

    /** @return array<string, mixed> */
    private function ligneDuModal(Inscription $inscription, int $id): array
    {
        $payments = $this->getJson(route('backoffice.inscriptions.payments', $inscription))
            ->assertOk()
            ->json('payments');

        return collect($payments)->firstWhere('id', $id);
    }

    // ------------------------------------------------------------------ //
    // Le tampon
    // ------------------------------------------------------------------ //

    public function test_une_avance_saisie_expire_14_jours_apres_sa_date_de_paiement(): void
    {
        [$student] = $this->enrolled();
        $this->actingAs($this->guichet);

        $this->post(route('backoffice.avances.store'), [
            'student_id' => $student->id,
            'montant' => '500',
            'methode' => 'Espèces',
            'date_paiement' => '2026-10-18',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $avance = Encaissement::query()->whereNull('inscription_fee_id')->firstOrFail();

        $this->assertSame('2026-11-01', $avance->avance_expire_le->toDateString());
        $this->assertFalse(ValiditeAvance::estExpiree($avance));
    }

    public function test_une_date_de_paiement_future_ne_fabrique_pas_une_avance_longue_duree(): void
    {
        [$student] = $this->enrolled();

        $avance = $this->avance($student, '2027-06-01');

        // Plafonnée à aujourd'hui (20/10) + 14, jamais 15/06/2027.
        $this->assertSame('2026-11-03', $avance->avance_expire_le->toDateString());
    }

    public function test_un_paiement_pose_sur_un_frais_ne_porte_aucune_date_d_expiration(): void
    {
        [$student, , $fee] = $this->enrolled();

        $paiement = $this->paiementSurFrais($student, $fee, '2026-10-01', 400);

        $this->assertNull($paiement->fresh()->avance_expire_le);
    }

    // ------------------------------------------------------------------ //
    // Appliquer une avance
    // ------------------------------------------------------------------ //

    public function test_le_guichet_applique_une_avance_dans_son_delai(): void
    {
        [$student, , $fee] = $this->enrolled();
        $avance = $this->avance($student, '2026-10-10');
        $this->actingAs($this->guichet);

        $this->post(route('backoffice.avances.apply', $avance), [
            'fee_id' => $fee->id,
            'montant' => '500',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(1, Encaissement::query()->where('applied_from_encaissement_id', $avance->id)->count());
    }

    public function test_le_guichet_ne_peut_plus_appliquer_une_avance_expiree(): void
    {
        [$student, , $fee] = $this->enrolled();
        $avance = $this->avance($student, '2026-09-01');
        $soldeAvant = (string) $this->till()->fresh()->solde;
        $this->actingAs($this->guichet);

        $this->post(route('backoffice.avances.apply', $avance), [
            'fee_id' => $fee->id,
            'montant' => '500',
        ])->assertSessionHasErrors('avance');

        $this->assertSame(0, Encaissement::query()->where('applied_from_encaissement_id', $avance->id)->count());
        $this->assertSame(InscriptionFee::STATUT_NON_PAYE, $fee->fresh()->statut);
        // L'expiration ne prend rien : l'argent reste reçu, la caisse intacte.
        $this->assertSame(500.0, $avance->fresh()->montantRestant());
        $this->assertSame($soldeAvant, (string) $this->till()->fresh()->solde);
    }

    public function test_un_directeur_non_plus(): void
    {
        [$student, , $fee] = $this->enrolled();
        $avance = $this->avance($student, '2026-09-01');
        $this->actingAs($this->userWithRole('director', Employee::CATEGORIE_DIRECTEUR));

        $this->post(route('backoffice.avances.apply', $avance), [
            'fee_id' => $fee->id,
            'montant' => '500',
        ])->assertSessionHasErrors('avance');

        $this->assertSame(InscriptionFee::STATUT_NON_PAYE, $fee->fresh()->statut);
    }

    public function test_le_super_admin_applique_encore_une_avance_expiree_et_le_journal_le_note(): void
    {
        [$student, , $fee] = $this->enrolled();
        $avance = $this->avance($student, '2026-09-01');
        $this->actingAs($this->admin);

        $this->post(route('backoffice.avances.apply', $avance), [
            'fee_id' => $fee->id,
            'montant' => '500',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(InscriptionFee::STATUT_PAYE_PARTIELLEMENT, $fee->fresh()->statut);

        $entree = Activity::query()->where('event', 'avance_applied')->latest('id')->firstOrFail();
        $this->assertTrue($entree->properties['delai_outrepasse']);
        $this->assertSame('2026-09-15', $entree->properties['avance_expiree_le']);
        $this->assertSame($this->admin->id, (int) $entree->causer_id);
    }

    public function test_une_application_dans_le_delai_n_est_pas_marquee_outrepassee(): void
    {
        [$student, , $fee] = $this->enrolled();
        $avance = $this->avance($student, '2026-10-15');
        $this->actingAs($this->admin);

        $this->post(route('backoffice.avances.apply', $avance), ['fee_id' => $fee->id, 'montant' => '500'])
            ->assertSessionHasNoErrors();

        $entree = Activity::query()->where('event', 'avance_applied')->latest('id')->firstOrFail();
        $this->assertFalse($entree->properties['delai_outrepasse']);
    }

    public function test_l_avance_expire_le_jour_meme_de_son_echeance(): void
    {
        [$student, , $fee] = $this->enrolled();
        // Payée le 06/10 : dernière journée utile le 19/10, expirée le 20/10.
        $avance = $this->avance($student, '2026-10-06');

        $this->travelTo('2026-10-19 23:00:00');
        $this->assertFalse(ValiditeAvance::estExpiree($avance->fresh()));

        $this->travelTo('2026-10-20 00:05:00');
        $this->assertTrue(ValiditeAvance::estExpiree($avance->fresh()));

        $this->expectException(ValidationException::class);
        app(AppliquerAvance::class)->handle($avance, $fee, 500.0);
    }

    // ------------------------------------------------------------------ //
    // Les données antérieures à la colonne
    // ------------------------------------------------------------------ //

    public function test_une_ligne_sans_date_stockee_suit_la_regle_a_la_lecture(): void
    {
        [$student, , $fee] = $this->enrolled();
        $vieille = $this->avance($student, '2026-03-01');
        $recente = $this->avance($student, '2026-10-15');

        // L'état de la production au déploiement : la colonne existe, vide.
        DB::table('encaissements')->update(['avance_expire_le' => null]);

        $this->assertSame('2026-03-15', ValiditeAvance::expireLe($vieille->fresh())->toDateString());
        $this->assertTrue(ValiditeAvance::estExpiree($vieille->fresh()));
        $this->assertFalse(ValiditeAvance::estExpiree($recente->fresh()));

        $this->actingAs($this->guichet);

        $this->post(route('backoffice.avances.apply', $vieille), ['fee_id' => $fee->id, 'montant' => '500'])
            ->assertSessionHasErrors('avance');
        $this->post(route('backoffice.avances.apply', $recente), ['fee_id' => $fee->id, 'montant' => '500'])
            ->assertSessionHasNoErrors();

        // Rien n'a été réécrit sur la vieille ligne : aucun backfill.
        $this->assertNull($vieille->fresh()->avance_expire_le);

        // La liste lit la même règle en SQL que l'action en PHP.
        $this->assertNotNull($this->avanceRow($vieille->id, 'expire'));
        $this->assertNull($this->avanceRow($recente->id, 'expire'));
    }

    // ------------------------------------------------------------------ //
    // Convertir un paiement en avance
    // ------------------------------------------------------------------ //

    public function test_le_guichet_convertit_un_paiement_recent(): void
    {
        [$student, $inscription, $fee] = $this->enrolled();
        $paiement = $this->paiementSurFrais($student, $fee, '2026-10-12', 600);
        $this->actingAs($this->guichet);

        $this->post(route('backoffice.avances.convert'), [
            'inscription_id' => $inscription->id,
            'encaissement_ids' => [$paiement->id],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertTrue($paiement->fresh()->isAvance());
        // 14 jours à compter de la conversion (20/10).
        $this->assertSame('2026-11-03', $paiement->fresh()->avance_expire_le->toDateString());
    }

    public function test_le_guichet_ne_peut_plus_convertir_un_paiement_de_plus_de_14_jours(): void
    {
        [$student, $inscription, $fee] = $this->enrolled();
        $paiement = $this->paiementSurFrais($student, $fee, '2026-08-20', 600);
        $this->actingAs($this->guichet);

        $this->post(route('backoffice.avances.convert'), [
            'inscription_id' => $inscription->id,
            'encaissement_ids' => [$paiement->id],
        ])->assertSessionHasErrors('encaissement_ids');

        $this->assertSame($fee->id, (int) $paiement->fresh()->inscription_fee_id);
        $this->assertSame(InscriptionFee::STATUT_PAYE_PARTIELLEMENT, $fee->fresh()->statut);
    }

    public function test_un_seul_paiement_trop_ancien_refuse_le_lot_entier(): void
    {
        [$student, $inscription, $fee] = $this->enrolled();
        $recent = $this->paiementSurFrais($student, $fee, '2026-10-12', 300);
        $ancien = $this->paiementSurFrais($student, $fee, '2026-08-20', 300);
        $this->actingAs($this->guichet);

        $this->post(route('backoffice.avances.convert'), [
            'inscription_id' => $inscription->id,
            'encaissement_ids' => [$recent->id, $ancien->id],
        ])->assertSessionHasErrors('encaissement_ids');

        // Le paiement récent n'a PAS été converti à moitié de lot.
        $this->assertFalse($recent->fresh()->isAvance());
        $this->assertFalse($ancien->fresh()->isAvance());
    }

    public function test_le_super_admin_convertit_encore_un_paiement_ancien_et_le_journal_le_note(): void
    {
        [$student, $inscription, $fee] = $this->enrolled(1000);
        $autreFee = $this->feeSur($inscription, 'Frais de Novembre', 600);
        $paiement = $this->paiementSurFrais($student, $fee, '2026-08-20', 600);
        $this->actingAs($this->admin);

        $this->post(route('backoffice.avances.convert'), [
            'inscription_id' => $inscription->id,
            'encaissement_ids' => [$paiement->id],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $avance = $paiement->fresh();
        $this->assertTrue($avance->isAvance());
        $this->assertSame('2026-11-03', $avance->avance_expire_le->toDateString());

        $entree = Activity::query()->where('event', 'avance_conversion_ancienne')->latest('id')->firstOrFail();
        $this->assertSame('2026-08-20', $entree->properties['date_paiement']);
        $this->assertSame($this->admin->id, (int) $entree->causer_id);

        // Rendue au guichet pour 14 jours : l'employé peut la poser.
        $this->actingAs($this->guichet);
        $this->post(route('backoffice.avances.apply', $avance), ['fee_id' => $autreFee->id, 'montant' => '600'])
            ->assertSessionHasNoErrors();

        $this->assertSame(InscriptionFee::STATUT_PAYE, $autreFee->fresh()->statut);
    }

    public function test_les_flux_systeme_ne_sont_pas_bornes_par_l_age_du_paiement(): void
    {
        [$student, $inscription, $fee] = $this->enrolled(1400);
        $paiement = $this->paiementSurFrais($student, $fee, '2026-08-20', 1400);

        // Appel sans troisième valeur = retrait d'un frais payé, clôture,
        // fusion… : 700 libérés, 700 ré-appliqués au même frais.
        app(ConvertirEncaissementsEnAvance::class)->handle($inscription, [$paiement->id], [$paiement->id => 700.0]);

        $this->assertSame(700.0, $paiement->fresh()->montantRestant());
        $this->assertSame(InscriptionFee::STATUT_PAYE_PARTIELLEMENT, $fee->fresh()->statut);
        $this->assertSame(0, Activity::query()->where('event', 'avance_conversion_ancienne')->count());
    }

    // ------------------------------------------------------------------ //
    // L'écran
    // ------------------------------------------------------------------ //

    public function test_la_liste_porte_la_regle_de_l_action_pour_chaque_utilisateur(): void
    {
        [$student] = $this->enrolled();
        $expiree = $this->avance($student, '2026-09-01');
        $valide = $this->avance($student, '2026-10-15');

        $this->actingAs($this->guichet);

        $row = $this->avanceRow($expiree->id);
        $this->assertTrue($row['avanceExpiree']);
        $this->assertFalse($row['applicable']);
        $this->assertSame('2026-09-15', $row['avanceExpireLe']);
        // Le reste est toujours affiché : l'argent n'a pas disparu.
        $this->assertSame('500.00', $row['montantRestant']);

        $row = $this->avanceRow($valide->id);
        $this->assertFalse($row['avanceExpiree']);
        $this->assertTrue($row['applicable']);
        $this->assertSame('2026-10-29', $row['avanceExpireLe']);

        // Le super-admin voit la même avance expirée, mais encore applicable.
        $this->actingAs($this->admin);

        $row = $this->avanceRow($expiree->id);
        $this->assertTrue($row['avanceExpiree']);
        $this->assertTrue($row['applicable']);
    }

    public function test_le_modal_de_conversion_montre_le_paiement_ancien_et_le_desactive(): void
    {
        [$student, $inscription, $fee] = $this->enrolled();
        $recent = $this->paiementSurFrais($student, $fee, '2026-10-12', 300);
        $ancien = $this->paiementSurFrais($student, $fee, '2026-08-20', 300);

        $this->actingAs($this->guichet);

        $ligne = $this->ligneDuModal($inscription, $ancien->id);
        $this->assertTrue($ligne['ancien']);
        $this->assertFalse($ligne['convertible']);
        $this->assertNotNull($ligne['convertBlocker']);

        $ligne = $this->ligneDuModal($inscription, $recent->id);
        $this->assertFalse($ligne['ancien']);
        $this->assertTrue($ligne['convertible']);

        $this->actingAs($this->admin);

        $ligne = $this->ligneDuModal($inscription, $ancien->id);
        $this->assertTrue($ligne['ancien']);
        $this->assertTrue($ligne['convertible']);
        $this->assertNull($ligne['convertBlocker']);
    }

    public function test_le_filtre_expire_ne_liste_que_l_argent_perime_qui_reste(): void
    {
        [$student, , $fee] = $this->enrolled();
        $expiree = $this->avance($student, '2026-09-01');
        $valide = $this->avance($student, '2026-10-15');
        // Expirée mais ÉPUISÉE : appliquée en entier pendant son délai.
        $epuisee = $this->avance($student, '2026-09-02', 300);
        $this->travelTo('2026-09-05 10:00:00');
        app(AppliquerAvance::class)->handle($epuisee, $fee, 300.0);
        $this->travelTo('2026-10-20 10:00:00');

        $this->actingAs($this->guichet);

        $this->assertNotNull($this->avanceRow($expiree->id, 'expire'));
        $this->assertNull($this->avanceRow($valide->id, 'expire'));
        $this->assertNull($this->avanceRow($epuisee->id, 'expire'));

        // Une avance épuisée n'a plus rien à périmer.
        $this->assertFalse($this->avanceRow($epuisee->id)['avanceExpiree']);
        // Le filtre par défaut continue de montrer l'argent périmé (badgé).
        $this->assertNotNull($this->avanceRow($expiree->id, 'restant'));
    }

    // ------------------------------------------------------------------ //
    // La prolongation
    // ------------------------------------------------------------------ //

    public function test_un_super_admin_rend_une_avance_expiree_au_guichet_avec_un_motif(): void
    {
        [$student, , $fee] = $this->enrolled();
        $avance = $this->avance($student, '2026-09-01');
        $this->actingAs($this->admin);

        $this->post(route('backoffice.avances.prolonger', $avance), [
            'motif' => 'Etudiant hospitalise, revient le 25/10',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('2026-11-03', $avance->fresh()->avance_expire_le->toDateString());

        $entree = Activity::query()->where('event', 'avance_prolongee')->latest('id')->firstOrFail();
        $this->assertSame('Etudiant hospitalise, revient le 25/10', $entree->properties['motif']);
        $this->assertSame('2026-09-15', $entree->properties['expire_le_avant']);
        $this->assertSame('2026-11-03', $entree->properties['expire_le_apres']);

        $this->actingAs($this->guichet);
        $this->post(route('backoffice.avances.apply', $avance), ['fee_id' => $fee->id, 'montant' => '500'])
            ->assertSessionHasNoErrors();
        $this->assertSame(InscriptionFee::STATUT_PAYE_PARTIELLEMENT, $fee->fresh()->statut);
    }

    public function test_la_prolongation_exige_un_motif(): void
    {
        [$student] = $this->enrolled();
        $avance = $this->avance($student, '2026-09-01');
        $this->actingAs($this->admin);

        $this->post(route('backoffice.avances.prolonger', $avance), ['motif' => ''])
            ->assertSessionHasErrors('motif');

        $this->assertSame('2026-09-15', $avance->fresh()->avance_expire_le->toDateString());
    }

    public function test_aucun_role_ne_peut_prolonger_lui_meme_une_avance(): void
    {
        [$student] = $this->enrolled();
        $avance = $this->avance($student, '2026-09-01');

        foreach ([$this->guichet, $this->userWithRole('director', Employee::CATEGORIE_DIRECTEUR)] as $user) {
            $this->actingAs($user);

            $this->post(route('backoffice.avances.prolonger', $avance), ['motif' => 'Je me prolonge moi-meme'])
                ->assertForbidden();
        }

        $this->assertSame('2026-09-15', $avance->fresh()->avance_expire_le->toDateString());
    }

    public function test_une_avance_epuisee_ne_se_prolonge_pas(): void
    {
        [$student, , $fee] = $this->enrolled();
        $avance = $this->avance($student, '2026-10-15', 300);
        app(AppliquerAvance::class)->handle($avance, $fee, 300.0);
        $this->actingAs($this->admin);

        $this->post(route('backoffice.avances.prolonger', $avance), ['motif' => 'Rien a rouvrir'])
            ->assertSessionHasErrors('avance');
    }

    // ------------------------------------------------------------------ //
    // Aucun rôle ne porte le passe-droit
    // ------------------------------------------------------------------ //

    public function test_aucun_preset_de_role_ne_porte_le_passe_droit(): void
    {
        $roles = \App\Models\Role::query()
            ->whereHas('permissions', fn ($q) => $q->where('name', ValiditeAvance::PERMISSION))
            ->pluck('name')
            ->all();

        $this->assertSame([], $roles);
        $this->assertTrue(ValiditeAvance::peutOutrepasser($this->admin));
        $this->assertFalse(ValiditeAvance::peutOutrepasser($this->guichet));
    }
}
