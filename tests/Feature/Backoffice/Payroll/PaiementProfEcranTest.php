<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Payroll;

use App\Models\AnneeScolaire;
use App\Models\Depense;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\Presence;
use App\Models\Seance;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Écran « Calcul paiement prof » — il LIT les appels et propose un montant.
 *
 * Ce que ces tests protègent avant tout : l'écran n'écrit RIEN. Un calcul de
 * paie qui créerait une dépense au passage contournerait tous les invariants
 * monétaires (§11) — validation, garde de solde, `CaisseLedger`, journal.
 */
final class PaiementProfEcranTest extends TestCase
{
    use RefreshDatabase;

    private AnneeScolaire $annee;

    private Etablissement $centre;

    private Group $group;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->annee = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->centre = Etablissement::factory()->create();
        $this->group = Group::factory()->create([
            'statut' => Group::STATUT_EN_FORMATION,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'montant_par_etudiant_prof' => 500,
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

    /** Une séance EFFECTUÉE avec l'appel de chaque étudiant. */
    private function seance(string $date, array $appels): Seance
    {
        $seance = Seance::create([
            'group_id' => $this->group->id,
            'date_seance' => $date,
            'heure_debut' => '18:00',
            'heure_fin' => '20:00',
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'statut' => Seance::STATUT_EFFECTUEE,
        ]);

        foreach ($appels as $studentId => $statut) {
            Presence::create([
                'seance_id' => $seance->id,
                'student_id' => $studentId,
                'statut' => $statut,
            ]);
        }

        return $seance;
    }

    private function student(string $prenom): Student
    {
        return Student::factory()->create([
            'etablissement_id' => $this->centre->id,
            'prenom' => $prenom,
        ]);
    }

    /** Quatre semaines pleines (lundi→vendredi), tout le monde présent. */
    private function moisPlein(Student $student): void
    {
        foreach (['2025-09-01', '2025-09-08', '2025-09-15', '2025-09-22'] as $lundi) {
            for ($i = 0; $i < 5; $i++) {
                $this->seance(
                    date('Y-m-d', strtotime($lundi.' +'.$i.' day')),
                    [$student->id => Presence::STATUT_PRESENT],
                );
            }
        }
    }

    /*
    |--------------------------------------------------------------------
    | Accès
    |--------------------------------------------------------------------
    */

    #[Test]
    public function it_refuses_a_user_without_the_permission(): void
    {
        $this->actingAs($this->user('expenses.view'))
            ->get('/backoffice/paiement-prof')
            ->assertForbidden();
    }

    #[Test]
    public function it_opens_for_a_user_holding_the_permission(): void
    {
        $this->actingAs($this->user('prof-payments.calculate'))
            ->get('/backoffice/paiement-prof')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/PaiementProf/Index')
                // Sans groupe ni période, rien n'est calculé : l'écran
                // n'invente pas une période par défaut.
                ->where('calcul', null));
    }

    /*
    |--------------------------------------------------------------------
    | Le calcul
    |--------------------------------------------------------------------
    */

    #[Test]
    public function it_computes_the_payment_from_the_recorded_roll_call(): void
    {
        $student = $this->student('Amine');
        $this->moisPlein($student);

        $this->actingAs($this->user('prof-payments.calculate'))
            ->get('/backoffice/paiement-prof?'.http_build_query([
                'groupFilter' => $this->group->id,
                'dateDebut' => '2025-09-01',
                'dateFin' => '2025-09-26',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                // Présent partout ⇒ l'étudiant vaut exactement le montant.
                ->where('calcul.total', fn ($v) => (float) $v === 500.0)
                ->where('calcul.etudiantsRemunerateurs', 1)
                ->has('calcul.lignes', 1));
    }

    #[Test]
    public function only_completed_sessions_are_paid(): void
    {
        $student = $this->student('Sara');

        // Une séance PRÉVUE et une ANNULÉE n'ont rien enseigné.
        foreach (['2025-09-01' => Seance::STATUT_PREVUE, '2025-09-02' => Seance::STATUT_ANNULEE] as $date => $statut) {
            $seance = Seance::create([
                'group_id' => $this->group->id,
                'date_seance' => $date,
                'etablissement_id' => $this->centre->id,
                'annee_scolaire_id' => $this->annee->id,
                'statut' => $statut,
            ]);
            Presence::create([
                'seance_id' => $seance->id,
                'student_id' => $student->id,
                'statut' => Presence::STATUT_PRESENT,
            ]);
        }

        $this->actingAs($this->user('prof-payments.calculate'))
            ->get('/backoffice/paiement-prof?'.http_build_query([
                'groupFilter' => $this->group->id,
                'dateDebut' => '2025-09-01',
                'dateFin' => '2025-09-26',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('calcul.nombreSeances', 0)
                ->where('calcul.total', fn ($v) => (float) $v === 0.0));
    }

    #[Test]
    public function retard_and_justifie_neither_earn_nor_cost(): void
    {
        // Règle propre au CRM (21/09/2026) : seul « Présent » rémunère ;
        // « Retard » et « Justifié » sont ÉCARTÉS du calcul. Une semaine de
        // 3 Présent + 2 Retard qualifie donc comme 3 Présent seuls.
        $student = $this->student('Youssef');

        foreach (['2025-09-01', '2025-09-08', '2025-09-15', '2025-09-22'] as $lundi) {
            foreach ([0, 1, 2] as $i) {
                $this->seance(
                    date('Y-m-d', strtotime($lundi.' +'.$i.' day')),
                    [$student->id => Presence::STATUT_PRESENT],
                );
            }
            foreach ([3, 4] as $i) {
                $this->seance(
                    date('Y-m-d', strtotime($lundi.' +'.$i.' day')),
                    [$student->id => $i === 3 ? Presence::STATUT_RETARD : Presence::STATUT_JUSTIFIE],
                );
            }
        }

        $this->actingAs($this->user('prof-payments.calculate'))
            ->get('/backoffice/paiement-prof?'.http_build_query([
                'groupFilter' => $this->group->id,
                'dateDebut' => '2025-09-01',
                'dateFin' => '2025-09-26',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                // 3 « Présent » par semaine = le seuil : les 4 semaines
                // qualifient malgré les Retard/Justifié.
                ->where('calcul.total', fn ($v) => (float) $v === 500.0)
                ->where('calcul.lignes.0.joursIgnores', 8));
    }

    #[Test]
    public function the_amount_per_student_can_be_overridden_on_the_screen(): void
    {
        $student = $this->student('Nadia');
        $this->moisPlein($student);

        $this->actingAs($this->user('prof-payments.calculate'))
            ->get('/backoffice/paiement-prof?'.http_build_query([
                'groupFilter' => $this->group->id,
                'dateDebut' => '2025-09-01',
                'dateFin' => '2025-09-26',
                'montantParEtudiant' => '800',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('calcul.total', fn ($v) => (float) $v === 800.0));
    }

    /*
    |--------------------------------------------------------------------
    | L'écran n'écrit rien
    |--------------------------------------------------------------------
    */

    #[Test]
    public function computing_a_payment_never_creates_an_expense(): void
    {
        // ⚠ L'invariant central : un calcul n'est pas un paiement. La dépense
        // reste une soumission relue par l'opérateur, qui passe par
        // EnregistrerDepense avec toutes ses gardes (§11).
        $student = $this->student('Karim');
        $this->moisPlein($student);

        $this->actingAs($this->user('prof-payments.calculate'))
            ->get('/backoffice/paiement-prof?'.http_build_query([
                'groupFilter' => $this->group->id,
                'dateDebut' => '2025-09-01',
                'dateFin' => '2025-09-26',
            ]))
            ->assertOk();

        $this->assertSame(0, Depense::count());
    }

    #[Test]
    public function the_screen_is_read_only_and_exposes_no_write_route(): void
    {
        $user = $this->user('prof-payments.calculate', 'expenses.create');

        // Aucune route d'écriture : l'écran est un GET, et rien d'autre.
        $this->actingAs($user)->post('/backoffice/paiement-prof')->assertStatus(405);
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

        $user = $this->user('prof-payments.calculate');
        // Contexte actif = le centre du groupe de ce test.
        $this->actingAs($user)->withSession(['context.etablissement_id' => $this->centre->id])
            ->get('/backoffice/paiement-prof?'.http_build_query([
                'groupFilter' => $autreGroupe->id,
                'dateDebut' => '2025-09-01',
                'dateFin' => '2025-09-26',
            ]))
            // Un id venu de la query string est revérifié côté serveur : il
            // ne doit jamais ouvrir un groupe hors du contexte actif.
            ->assertSessionHasErrors('groupFilter');
    }
}
