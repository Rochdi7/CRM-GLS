<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Payroll;

use App\Models\AnneeScolaire;
use App\Models\Depense;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\GroupEnseignant;
use App\Models\Presence;
use App\Models\Seance;
use App\Models\Student;
use App\Models\TypeDepense;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Écran « Calcul paiement prof » — il LIT les appels et propose un montant
 * pour UN enseignant, selon le mode configuré sur SA fiche.
 *
 * Ce que ces tests protègent avant tout : l'écran n'écrit RIEN. Un calcul de
 * paie qui créerait une dépense au passage contournerait tous les invariants
 * monétaires (§11).
 */
final class PaiementProfEcranTest extends TestCase
{
    use RefreshDatabase;

    private AnneeScolaire $annee;

    private Etablissement $centre;

    private Group $group;

    private Employee $prof;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->annee = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->centre = Etablissement::factory()->create();

        // Enseignant en mode GLS à 500 DH / étudiant.
        $this->prof = Employee::factory()->create([
            'categorie' => Employee::CATEGORIE_ENSEIGNANT,
            'etablissement_id' => $this->centre->id,
            'mode_paiement_prof' => Employee::MODE_PAIEMENT_GLS,
            'montant_par_etudiant_prof' => 500,
        ]);

        // Groupe démarré le 1er : ses mois coïncident avec le mois civil.
        $this->group = Group::factory()->create([
            'statut' => Group::STATUT_EN_FORMATION,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'enseignant_id' => $this->prof->id,
            'date_debut_formation' => '2025-09-01',
        ]);
        GroupEnseignant::create([
            'group_id' => $this->group->id,
            'enseignant_id' => $this->prof->id,
            'date_debut' => '2025-09-01',
            'statut' => 'Actif',
        ]);
    }

    private function user(string ...$permissions): User
    {
        $user = User::factory()->create();

        foreach ([...$permissions, 'centers.access-all'] as $p) {
            $user->givePermissionTo($p);
        }

        return $user->fresh();
    }

    private function seance(string $date, array $appels, ?int $enseignantId = null): Seance
    {
        $seance = Seance::create([
            'group_id' => $this->group->id,
            'date_seance' => $date,
            'heure_debut' => '18:00',
            'heure_fin' => '20:00',
            'enseignant_id' => $enseignantId ?? $this->prof->id,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'statut' => Seance::STATUT_EFFECTUEE,
        ]);

        foreach ($appels as $studentId => $statut) {
            Presence::create(['seance_id' => $seance->id, 'student_id' => $studentId, 'statut' => $statut]);
        }

        return $seance;
    }

    private function student(string $prenom): Student
    {
        return Student::factory()->create(['etablissement_id' => $this->centre->id, 'prenom' => $prenom]);
    }

    /** 20 séances en septembre, tout le monde présent. */
    private function moisPlein(Student $student): void
    {
        foreach (['2025-09-01', '2025-09-08', '2025-09-15', '2025-09-22'] as $lundi) {
            for ($i = 0; $i < 5; $i++) {
                $this->seance(date('Y-m-d', strtotime($lundi.' +'.$i.' day')), [$student->id => Presence::STATUT_PRESENT]);
            }
        }
    }

    private function calculer(array $extra = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->user('prof-payments.calculate'))
            ->get('/backoffice/paiement-prof?'.http_build_query([
                'groupFilter' => $this->group->id,
                'enseignantFilter' => $this->prof->id,
                'mois' => '2025-09',
                ...$extra,
            ]));
    }

    /*
    |--------------------------------------------------------------------
    | Accès
    |--------------------------------------------------------------------
    */

    #[Test]
    public function it_refuses_a_user_without_the_permission(): void
    {
        $this->actingAs($this->user('expenses.view'))->get('/backoffice/paiement-prof')->assertForbidden();
    }

    #[Test]
    public function it_opens_empty_for_a_user_holding_the_permission(): void
    {
        $this->actingAs($this->user('prof-payments.calculate'))
            ->get('/backoffice/paiement-prof')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/PaiementProf/Index')
                ->where('calcul', null));
    }

    #[Test]
    public function it_lists_the_teacher_payments_already_recorded(): void
    {
        // Signalé le 24/09/2026 : trois « Paiement prof » approuvés
        // (27 773,80 MAD) et l'écran s'ouvrait sur « Aucun calcul ».
        $type = TypeDepense::create([
            'nom' => TypeDepense::SYSTEM_PAIEMENT_PROF, 'is_system' => true, 'statut' => TypeDepense::STATUT_ACTIF,
        ]);
        $agent = Employee::factory()->create(['etablissement_id' => $this->centre->id]);

        Depense::query()->create([
            'reference' => 'DEP-001',
            'type_depense_id' => $type->id,
            'caisse_id' => $agent->till()->first()->id,
            'group_id' => $this->group->id,
            'enseignant_id' => $this->prof->id,
            'montant' => '9000.00',
            'statut' => Depense::STATUT_APPROUVEE,
            'date_depense' => '2025-09-30',
            'periode_debut' => '2025-09-01',
            'periode_fin' => '2025-09-30',
            'description' => 'Paiement prof',
            'agent_id' => $agent->id,
        ]);

        $this->actingAs($this->user('prof-payments.calculate', 'expenses.view'))
            ->get('/backoffice/paiement-prof')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('calcul', null)
                ->where('paiementsProf.montantTotal', '9000.00')
                ->has('paiementsProf.data.data', 1)
                ->where('paiementsProf.data.data.0.reference', 'DEP-001')
                ->where('paiementsProf.data.data.0.enseignant', $this->prof->nomComplet()));
    }

    #[Test]
    public function the_recorded_payments_need_the_expenses_view_permission(): void
    {
        $this->actingAs($this->user('prof-payments.calculate'))
            ->get('/backoffice/paiement-prof')
            ->assertInertia(fn (Assert $page) => $page->where('paiementsProf', null));
    }

    /*
    |--------------------------------------------------------------------
    | Le calcul, mode GLS
    |--------------------------------------------------------------------
    */

    #[Test]
    public function it_computes_from_the_teachers_own_rate_and_the_recorded_roll_call(): void
    {
        $this->moisPlein($this->student('Amine'));

        $this->calculer()
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('calcul.enseignant.mode', Employee::MODE_PAIEMENT_GLS)
                ->where('calcul.enseignant.taux', fn ($v) => (float) $v === 500.0)
                // Présent partout ⇒ vaut exactement le taux.
                ->where('calcul.total', fn ($v) => (float) $v === 500.0)
                ->where('calcul.nombreSeances', 20)
                ->has('calcul.lignes', 1));
    }

    #[Test]
    public function the_month_window_follows_the_groups_start_day(): void
    {
        // Groupe démarré le 07/09 : « septembre » = 07/09 → 06/10.
        $this->group->update(['date_debut_formation' => '2025-09-07']);

        $this->calculer()
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('calcul.periode.debut', '2025-09-07')
                ->where('calcul.periode.fin', '2025-10-06')
                ->where('calcul.periode.ancreSurLeGroupe', true));
    }

    #[Test]
    public function only_this_teachers_sessions_are_counted(): void
    {
        // Un remplaçant a donné 5 des 20 séances : elles ne sont pas à
        // notre prof, et ne diluent pas sa paie.
        $remplacant = Employee::factory()->create([
            'categorie' => Employee::CATEGORIE_ENSEIGNANT,
            'etablissement_id' => $this->centre->id,
        ]);
        $student = $this->student('Sara');

        foreach (['2025-09-01', '2025-09-08', '2025-09-15'] as $lundi) {
            for ($i = 0; $i < 5; $i++) {
                $this->seance(date('Y-m-d', strtotime($lundi.' +'.$i.' day')), [$student->id => Presence::STATUT_PRESENT]);
            }
        }
        for ($i = 0; $i < 5; $i++) {
            $this->seance(date('Y-m-d', strtotime('2025-09-22 +'.$i.' day')), [$student->id => Presence::STATUT_PRESENT], $remplacant->id);
        }

        $this->calculer()
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('calcul.nombreSeances', 15)
                // 15 présences sur 15 séances DU PROF ⇒ le taux entier.
                ->where('calcul.total', fn ($v) => (float) $v === 500.0));
    }

    #[Test]
    public function sessions_without_a_teacher_pay_nobody_and_are_flagged(): void
    {
        $student = $this->student('Youssef');
        $this->moisPlein($student);
        // Trois séances orphelines de plus, DANS la fenêtre de septembre
        // (26, 29, 30 — pas le 1er octobre, qui appartient au mois suivant).
        foreach (['2025-09-26', '2025-09-29', '2025-09-30'] as $date) {
            Seance::create([
                'group_id' => $this->group->id,
                'date_seance' => $date,
                'enseignant_id' => null,
                'etablissement_id' => $this->centre->id,
                'annee_scolaire_id' => $this->annee->id,
                'statut' => Seance::STATUT_EFFECTUEE,
            ]);
        }

        $this->calculer()
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('calcul.seancesSansEnseignant', 3)
                // Elles n'entrent pas dans le diviseur du prof.
                ->where('calcul.nombreSeances', 20));
    }

    /*
    |--------------------------------------------------------------------
    | Modes horaire et win-win
    |--------------------------------------------------------------------
    */

    #[Test]
    public function the_hourly_mode_multiplies_the_teachers_rate_by_the_entered_hours(): void
    {
        $this->prof->update([
            'mode_paiement_prof' => Employee::MODE_PAIEMENT_HORAIRE,
            'taux_horaire_prof' => 80,
            'montant_par_etudiant_prof' => null,
        ]);
        $this->moisPlein($this->student('Nadia'));

        $this->calculer(['heures' => '12.5'])
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('calcul.enseignant.mode', Employee::MODE_PAIEMENT_HORAIRE)
                ->where('calcul.heuresSaisies', fn ($v) => (float) $v === 12.5)
                ->where('calcul.total', fn ($v) => (float) $v === 1000.0)
                // Pas de lignes par étudiant en mode horaire.
                ->has('calcul.lignes', 0));
    }

    #[Test]
    public function the_win_win_mode_reads_the_amount_of_the_requested_month(): void
    {
        $this->prof->update(['mode_paiement_prof' => Employee::MODE_PAIEMENT_WIN_WIN, 'montant_par_etudiant_prof' => null]);
        $this->prof->tauxMensuels()->create(['mois' => '2025-09-01', 'montant_par_etudiant' => 400]);
        $this->prof->tauxMensuels()->create(['mois' => '2025-10-01', 'montant_par_etudiant' => 450]);
        $this->moisPlein($this->student('Karim'));

        $this->calculer()
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('calcul.enseignant.mode', Employee::MODE_PAIEMENT_WIN_WIN)
                // Septembre = 400, pas 450 ni le taux GLS.
                ->where('calcul.enseignant.taux', fn ($v) => (float) $v === 400.0)
                ->where('calcul.total', fn ($v) => (float) $v === 400.0));
    }

    #[Test]
    public function the_win_win_mode_refuses_a_month_that_was_never_entered(): void
    {
        $this->prof->update(['mode_paiement_prof' => Employee::MODE_PAIEMENT_WIN_WIN, 'montant_par_etudiant_prof' => null]);
        $this->prof->tauxMensuels()->create(['mois' => '2025-10-01', 'montant_par_etudiant' => 450]);
        $this->moisPlein($this->student('Lina'));

        // Septembre n'est pas saisi : on ne prend PAS octobre à la place.
        $this->calculer()
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('calcul.enseignant.taux', fn ($v) => (float) $v === 0.0)
                ->where('calcul.enseignant.probleme', fn ($v) => is_string($v) && $v !== '')
                ->where('calcul.total', fn ($v) => (float) $v === 0.0));
    }

    #[Test]
    public function a_teacher_without_a_pay_mode_is_refused_with_a_named_problem(): void
    {
        $this->prof->update(['mode_paiement_prof' => null, 'montant_par_etudiant_prof' => null]);
        $this->moisPlein($this->student('Omar'));

        $this->calculer()
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('calcul.enseignant.probleme', fn ($v) => is_string($v) && str_contains($v, 'Paiement prof'))
                ->where('calcul.total', fn ($v) => (float) $v === 0.0));
    }

    /*
    |--------------------------------------------------------------------
    | L'endpoint d'options du modal
    |--------------------------------------------------------------------
    */

    #[Test]
    public function the_group_options_endpoint_lists_teachers_months_and_orphan_sessions(): void
    {
        $this->group->update(['date_debut_formation' => '2025-09-07']);
        $this->moisPlein($this->student('Yasmine'));

        $this->actingAs($this->user('prof-payments.calculate'))
            ->getJson("/backoffice/paiement-prof/groupes/{$this->group->id}/options?mois=2025-09")
            ->assertOk()
            ->assertJsonPath('fenetre.debut', '2025-09-07')
            ->assertJsonPath('fenetre.fin', '2025-10-06')
            ->assertJsonPath('enseignantParDefaut', $this->prof->id)
            ->assertJsonPath('enseignants.0.mode', Employee::MODE_PAIEMENT_GLS)
            ->assertJsonPath('seancesSansEnseignant', 0);
    }

    /*
    |--------------------------------------------------------------------
    | L'écran n'écrit rien
    |--------------------------------------------------------------------
    */

    #[Test]
    public function computing_a_payment_never_creates_an_expense(): void
    {
        $this->moisPlein($this->student('Karim'));

        $this->calculer()->assertOk();

        $this->assertSame(0, Depense::count());
    }

    #[Test]
    public function the_screen_exposes_no_write_route(): void
    {
        $this->actingAs($this->user('prof-payments.calculate', 'expenses.create'))
            ->post('/backoffice/paiement-prof')
            ->assertStatus(405);

        $this->assertSame(0, Depense::count());
    }

    /*
    |--------------------------------------------------------------------
    | Portée
    |--------------------------------------------------------------------
    */

    #[Test]
    public function a_group_of_another_centre_is_refused(): void
    {
        $autreCentre = Etablissement::factory()->create();
        $autreGroupe = Group::factory()->create([
            'statut' => Group::STATUT_EN_FORMATION,
            'etablissement_id' => $autreCentre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);

        $this->actingAs($this->user('prof-payments.calculate'))
            ->withSession(['context.etablissement_id' => $this->centre->id])
            ->get('/backoffice/paiement-prof?'.http_build_query([
                'groupFilter' => $autreGroupe->id,
                'enseignantFilter' => $this->prof->id,
                'mois' => '2025-09',
            ]))
            ->assertSessionHasErrors('groupFilter');
    }
}
