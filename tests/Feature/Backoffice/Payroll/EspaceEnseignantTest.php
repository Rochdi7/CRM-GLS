<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Payroll;

use App\Domain\Expenses\Actions\EnregistrerDepense;
use App\Domain\Finance\Support\CaisseResolver;
use App\Models\AnneeScolaire;
use App\Models\Depense;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\Presence;
use App\Models\Seance;
use App\Models\Student;
use App\Models\TypeDepense;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TypeDepenseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Espace enseignant sur le tableau de bord (23/09/2026).
 *
 * Ce que ces tests protègent avant tout : un prof ne lit QUE le sien.
 * L'identité vient de `$user->employee`, jamais du client — c'est la seule
 * fenêtre finance du rôle `teacher`, et elle ne doit s'ouvrir que sur sa
 * propre paie.
 */
final class EspaceEnseignantTest extends TestCase
{
    use RefreshDatabase;

    private AnneeScolaire $annee;

    private Etablissement $centre;

    private Employee $cashier;

    private int $typeProf;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(TypeDepenseSeeder::class);

        $this->annee = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->centre = Etablissement::factory()->create();
        $this->cashier = Employee::factory()->create([
            'categorie' => Employee::CATEGORIE_COMPTABLE,
            'etablissement_id' => $this->centre->id,
        ]);
        $this->typeProf = (int) TypeDepense::query()->where('nom', TypeDepense::SYSTEM_PAIEMENT_PROF)->value('id');
    }

    /** Un enseignant AVEC son login au rôle teacher. */
    private function prof(string $prenom): Employee
    {
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $user->assignRole('teacher');

        return Employee::factory()->create([
            'prenom' => $prenom,
            'categorie' => Employee::CATEGORIE_ENSEIGNANT,
            'etablissement_id' => $this->centre->id,
            'user_id' => $user->id,
        ]);
    }

    private function groupe(Employee $prof, string $nom): Group
    {
        return Group::factory()->create([
            'nom' => $nom,
            'statut' => Group::STATUT_EN_FORMATION,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'enseignant_id' => $prof->id,
            'date_debut_formation' => '2025-09-01',
        ]);
    }

    private function paiementProf(Group $groupe, ?Employee $enseignant, float $montant): Depense
    {
        $caisse = app(CaisseResolver::class)->tillOf($this->cashier);

        return app(EnregistrerDepense::class)->handle([
            'type_depense_id' => $this->typeProf,
            'caisse_id' => $caisse->id,
            'group_id' => $groupe->id,
            'enseignant_id' => $enseignant?->id,
            'montant' => $montant,
            'methode_paiement' => 'Espèces',
            'date_depense' => '2026-03-15',
            'periode_debut' => '2026-02-01',
            'periode_fin' => '2026-02-28',
            'description' => 'Paiement prof test',
        ], $this->cashier);
    }

    /*
    |--------------------------------------------------------------------
    | Le paiement porte SON enseignant
    |--------------------------------------------------------------------
    */

    #[Test]
    public function a_teacher_payment_records_which_teacher_was_paid(): void
    {
        $prof = $this->prof('Ouassima');
        $groupe = $this->groupe($prof, 'OUASSIMA 13H');

        $d = $this->paiementProf($groupe, $prof, 7700);

        $this->assertSame($prof->id, $d->enseignant_id);
    }

    #[Test]
    public function a_hand_keyed_payment_falls_back_to_the_groups_current_teacher(): void
    {
        // Saisie à la main sans enseignant_id : le serveur prend le prof du
        // groupe — et le FIGE, un changement de prof ultérieur ne réattribue
        // jamais un paiement déjà versé.
        $prof = $this->prof('Driss');
        $groupe = $this->groupe($prof, 'Herr Driss 19H');

        $d = $this->paiementProf($groupe, null, 500);
        $this->assertSame($prof->id, $d->enseignant_id);

        $remplacant = $this->prof('Nizar');
        $groupe->update(['enseignant_id' => $remplacant->id]);

        $this->assertSame($prof->id, $d->fresh()->enseignant_id);
    }

    #[Test]
    public function a_non_teacher_cannot_be_paid_as_a_teacher(): void
    {
        $prof = $this->prof('Driss');
        $groupe = $this->groupe($prof, 'Herr Driss 19H');
        $admin = User::factory()->create();
        foreach (['expenses.view', 'expenses.create', 'centers.access-all'] as $p) {
            $admin->givePermissionTo($p);
        }
        $admin->employee()->save(Employee::factory()->make([
            'categorie' => Employee::CATEGORIE_COMPTABLE,
            'etablissement_id' => $this->centre->id,
        ]));

        $this->actingAs($admin->fresh())
            ->post('/backoffice/depenses', [
                'type_depense_id' => $this->typeProf,
                'group_id' => $groupe->id,
                'enseignant_id' => $this->cashier->id, // une comptable, pas un prof
                'montant' => 500,
                'methode_paiement' => 'Espèces',
                'date_depense' => '2026-03-15',
                'periode_debut' => '2026-02-01',
                'periode_fin' => '2026-02-28',
                'description' => 'x',
            ])
            ->assertSessionHasErrors('enseignant_id');
    }

    /*
    |--------------------------------------------------------------------
    | Le prof ne lit QUE le sien
    |--------------------------------------------------------------------
    */

    #[Test]
    public function a_teacher_sees_only_their_own_payments_on_the_dashboard(): void
    {
        $oua = $this->prof('Ouassima');
        $driss = $this->prof('Driss');
        $this->paiementProf($this->groupe($oua, 'OUASSIMA 13H'), $oua, 7700);
        $this->paiementProf($this->groupe($driss, 'Herr Driss 19H'), $driss, 5000);

        $this->actingAs($oua->user)
            ->get('/backoffice/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('espaceEnseignant.enseignant.id', $oua->id)
                ->has('espaceEnseignant.paiements', 1)
                ->where('espaceEnseignant.paiements.0.montant', fn ($v) => (float) $v === 7700.0)
                ->where('espaceEnseignant.paiements.0.enseignantDeduit', false));
    }

    #[Test]
    public function a_teacher_cannot_read_another_teachers_space_by_forging_the_request(): void
    {
        // L'identité vient de la session, jamais de la query string : aucun
        // paramètre ne permet de désigner un autre prof.
        $oua = $this->prof('Ouassima');
        $driss = $this->prof('Driss');
        $this->paiementProf($this->groupe($driss, 'Herr Driss 19H'), $driss, 5000);

        $this->actingAs($oua->user)
            ->get('/backoffice/dashboard?enseignant='.$driss->id.'&enseignantFilter='.$driss->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('espaceEnseignant.enseignant.id', $oua->id)
                ->has('espaceEnseignant.paiements', 0));
    }

    #[Test]
    public function a_non_teacher_account_receives_no_teacher_space_at_all(): void
    {
        // Pas un composant caché : le prop est NULL, la donnée ne quitte pas
        // le serveur.
        $admin = User::factory()->create();
        $admin->givePermissionTo('dashboard.view');
        $admin->givePermissionTo('dashboard.espace-enseignant');
        $admin->employee()->save(Employee::factory()->make([
            'categorie' => Employee::CATEGORIE_COMPTABLE,
            'etablissement_id' => $this->centre->id,
        ]));

        $this->actingAs($admin->fresh())
            ->get('/backoffice/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('espaceEnseignant', null));
    }

    /*
    |--------------------------------------------------------------------
    | Statuts et cumul
    |--------------------------------------------------------------------
    */

    #[Test]
    public function the_yearly_total_counts_only_approved_money_and_shows_pending_apart(): void
    {
        $prof = $this->prof('Ouassima');
        $groupe = $this->groupe($prof, 'OUASSIMA 13H');

        // EnregistrerDepense crée « En attente » (validation ON par défaut).
        $enAttente = $this->paiementProf($groupe, $prof, 7700);
        $approuve = $this->paiementProf($groupe, $prof, 5000);
        $approuve->update(['statut' => Depense::STATUT_APPROUVEE]);

        $this->actingAs($prof->user)
            ->get('/backoffice/dashboard')
            ->assertInertia(fn (Assert $page) => $page
                ->where('espaceEnseignant.cumul.approuve', fn ($v) => (float) $v === 5000.0)
                ->where('espaceEnseignant.cumul.enAttente', fn ($v) => (float) $v === 7700.0)
                ->has('espaceEnseignant.paiements', 2));

        $this->assertSame(Depense::STATUT_EN_ATTENTE, $enAttente->fresh()->statut);
    }

    #[Test]
    public function a_legacy_payment_without_a_teacher_is_shown_via_the_group_and_flagged(): void
    {
        $prof = $this->prof('Driss');
        $groupe = $this->groupe($prof, 'Herr Driss 19H');

        // Ligne ANTÉRIEURE à la colonne : enseignant_id NULL.
        $this->paiementProf($groupe, $prof, 500)->update(['enseignant_id' => null]);

        $this->actingAs($prof->user)
            ->get('/backoffice/dashboard')
            ->assertInertia(fn (Assert $page) => $page
                ->has('espaceEnseignant.paiements', 1)
                ->where('espaceEnseignant.paiements.0.enseignantDeduit', true));
    }

    #[Test]
    public function the_groups_block_counts_only_this_teachers_completed_sessions(): void
    {
        $prof = $this->prof('Ouassima');
        $autre = $this->prof('Nizar');
        $groupe = $this->groupe($prof, 'OUASSIMA 13H');
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);

        $seance = function (string $date, Employee $qui, string $statut) use ($groupe, $student): void {
            $s = Seance::create([
                'group_id' => $groupe->id, 'date_seance' => $date, 'enseignant_id' => $qui->id,
                'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id, 'statut' => $statut,
            ]);
            Presence::create(['seance_id' => $s->id, 'student_id' => $student->id, 'statut' => Presence::STATUT_PRESENT]);
        };

        $seance('2026-03-02', $prof, Seance::STATUT_EFFECTUEE);
        $seance('2026-03-03', $prof, Seance::STATUT_EFFECTUEE);
        $seance('2026-03-04', $prof, Seance::STATUT_ANNULEE);   // annulée : pas comptée
        $seance('2026-03-05', $autre, Seance::STATUT_EFFECTUEE); // un autre prof : pas la sienne

        $this->actingAs($prof->user)
            ->get('/backoffice/dashboard?espaceMois=2026-03')
            ->assertInertia(fn (Assert $page) => $page
                ->where('espaceEnseignant.mois', '2026-03')
                ->where('espaceEnseignant.groupes.0.seancesCeMois', 2)
                ->where('espaceEnseignant.groupes.0.appels', 2)
                ->where('espaceEnseignant.groupes.0.tauxPresence', 100));
    }
}
