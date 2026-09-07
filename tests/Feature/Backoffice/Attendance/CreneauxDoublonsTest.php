<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Attendance;

use App\Domain\Attendance\Actions\GenererSeancesDepuisCreneau;
use App\Domain\Attendance\Support\DiagnostiquerEmploiDuTemps;
use App\Models\AnneeScolaire;
use App\Models\Creneau;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\Seance;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Emploi du temps saisi DEUX FOIS — signalé le 07/09/2026 sur « Ilyass sept
 * 19H » : cinq créneaux créés le 02/09, cinq identiques le 04/09, d'où deux
 * séances chaque soir et deux appels à faire pour la même classe.
 *
 * Trois protections doivent tenir ensemble, et chacune couvre un trou que les
 * autres ne voient pas :
 *  1. la SAISIE refuse une case déjà occupée (la cause) ;
 *  2. la GÉNÉRATION ne crée qu'une séance par groupe + jour + heure, même si
 *     deux créneaux jumeaux existent déjà en base (le stock déjà abîmé) ;
 *  3. la FICHE du groupe signale le doublon (sinon il reste invisible : rien
 *     ne manque à l'écran, seul l'appel se présente en double).
 */
final class CreneauxDoublonsTest extends TestCase
{
    use RefreshDatabase;

    private AnneeScolaire $annee;

    private Group $group;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->annee = AnneeScolaire::create([
            'nom' => 'Test', 'date_debut' => '2000-09-01', 'date_fin' => '2100-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->group = Group::factory()->create([
            'statut' => Group::STATUT_EN_FORMATION,
            'etablissement_id' => Etablissement::factory()->create()->id,
            'annee_scolaire_id' => $this->annee->id,
            'date_debut_formation' => Carbon::today()->subDay()->toDateString(),
        ]);
    }

    private function userWith(string ...$permissions): User
    {
        $user = User::factory()->create();
        foreach ([...$permissions, 'centers.access-all'] as $p) {
            $user->givePermissionTo($p);
        }

        return $user->fresh();
    }

    public function test_creating_a_second_slot_on_an_occupied_time_is_refused(): void
    {
        Creneau::create([
            'group_id' => $this->group->id,
            'jour_semaine' => 1,
            'heure_debut' => '19:00',
            'heure_fin' => '21:00',
        ]);

        $this->actingAs($this->userWith('attendance.create', 'attendance.view'))
            ->post(route('backoffice.creneaux.store'), [
                'group_id' => $this->group->id,
                'jours_semaine' => [1, 2],
                'heure_debut' => '19:00',
                'heure_fin' => '21:00',
            ])
            ->assertSessionHasErrors('jours_semaine');

        // Refus GLOBAL : le jour libre (mardi) ne passe pas non plus en
        // douce, sinon un envoi partiellement fautif laisserait la moitié
        // des créneaux créés et l'utilisateur ne saurait pas lesquels.
        $this->assertSame(1, Creneau::where('group_id', $this->group->id)->count());
    }

    /**
     * Un créneau CLÔTURÉ n'occupe plus la case : c'est l'emploi du temps d'un
     * enseignant parti, conservé pour la paie. Le nouvel enseignant doit
     * pouvoir saisir le même horaire.
     */
    public function test_a_closed_slot_does_not_block_the_same_time(): void
    {
        Creneau::create([
            'group_id' => $this->group->id,
            'jour_semaine' => 1,
            'heure_debut' => '19:00',
            'heure_fin' => '21:00',
            'date_fin' => Carbon::today()->subMonth()->toDateString(),
        ]);

        $this->actingAs($this->userWith('attendance.create', 'attendance.view'))
            ->post(route('backoffice.creneaux.store'), [
                'group_id' => $this->group->id,
                'jours_semaine' => [1],
                'heure_debut' => '19:00',
                'heure_fin' => '21:00',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Creneau::where('group_id', $this->group->id)->count());
    }

    /**
     * Le cœur du bug : le générateur est idempotent PAR CRÉNEAU, si bien que
     * deux jumeaux produisaient chacun leur séance sans jamais se répéter
     * eux-mêmes. La garde doit porter sur le GROUPE + la case horaire.
     */
    public function test_two_twin_slots_generate_only_one_seance(): void
    {
        $jour = (int) Carbon::today()->isoWeekday();

        $a = Creneau::create([
            'group_id' => $this->group->id, 'jour_semaine' => $jour,
            'heure_debut' => '19:00', 'heure_fin' => '21:00',
        ]);
        $b = Creneau::create([
            'group_id' => $this->group->id, 'jour_semaine' => $jour,
            'heure_debut' => '19:00', 'heure_fin' => '21:00',
        ]);

        $generer = app(GenererSeancesDepuisCreneau::class);
        $generer->generer($a);
        $generer->generer($b);

        $this->assertSame(1, Seance::query()
            ->where('group_id', $this->group->id)
            ->whereDate('date_seance', Carbon::today()->toDateString())
            ->count());
    }

    public function test_the_group_card_reports_duplicate_slots(): void
    {
        Creneau::create([
            'group_id' => $this->group->id, 'jour_semaine' => 1,
            'heure_debut' => '19:00', 'heure_fin' => '21:00',
        ]);
        Creneau::create([
            'group_id' => $this->group->id, 'jour_semaine' => 1,
            'heure_debut' => '19:00', 'heure_fin' => '21:00',
        ]);

        $probleme = app(DiagnostiquerEmploiDuTemps::class)($this->group, 2, 2);

        $this->assertNotNull($probleme);
        $this->assertSame(DiagnostiquerEmploiDuTemps::CRENEAUX_DOUBLES, $probleme['code']);
        $this->assertStringContainsString('Lundi 19:00', $probleme['message']);
    }

    public function test_a_healthy_schedule_reports_nothing(): void
    {
        Creneau::create([
            'group_id' => $this->group->id, 'jour_semaine' => 1,
            'heure_debut' => '19:00', 'heure_fin' => '21:00',
        ]);
        Creneau::create([
            'group_id' => $this->group->id, 'jour_semaine' => 2,
            'heure_debut' => '19:00', 'heure_fin' => '21:00',
        ]);

        $this->assertNull(app(DiagnostiquerEmploiDuTemps::class)($this->group, 2, 2));
    }

    /**
     * La commande de rattrapage : elle garde le plus ANCIEN créneau de chaque
     * paire et emporte les séances futures « Prévue » de la copie.
     */
    public function test_the_cleanup_command_keeps_the_oldest_slot(): void
    {
        $jour = (int) Carbon::today()->isoWeekday();

        $original = Creneau::create([
            'group_id' => $this->group->id, 'jour_semaine' => $jour,
            'heure_debut' => '19:00', 'heure_fin' => '21:00',
        ]);
        $copie = Creneau::create([
            'group_id' => $this->group->id, 'jour_semaine' => $jour,
            'heure_debut' => '19:00', 'heure_fin' => '21:00',
        ]);

        Seance::create([
            'group_id' => $this->group->id, 'creneau_id' => $copie->id,
            'date_seance' => Carbon::today()->toDateString(),
            'heure_debut' => '19:00', 'heure_fin' => '21:00',
            'etablissement_id' => $this->group->etablissement_id,
            'annee_scolaire_id' => $this->annee->id,
            'statut' => Seance::STATUT_PREVUE,
        ]);

        $this->artisan('groupes:supprimer-creneaux-doubles', ['--apply' => true])
            ->assertSuccessful();

        $this->assertNotNull($original->fresh());
        $this->assertNull($copie->fresh());
        $this->assertSame(0, Seance::where('group_id', $this->group->id)->count());
    }
}
