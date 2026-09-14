<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Attendance;

use App\Models\AnneeScolaire;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\Seance;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * « Saisir l'absence » : changer Employé (ou Date) doit changer la LISTE
 * D'ÉTUDIANTS affichée — signalé le 14/09/2026, capture à l'appui : le
 * sélecteur « Séances » retombait sur « Choisir une séance… » pendant que
 * l'appel d'un AUTRE enseignant restait affiché en dessous.
 *
 * Cause : sur /backoffice/seances/{id} (show), la séance vient du paramètre
 * de route et n'est JAMAIS ré-résolue à partir des filtres, alors que le
 * sélecteur, lui, est reconstruit par `seancesFor($date, $enseignant)`. La
 * séance chargée disparaissait donc de ses propres options : picker vide,
 * roster périmé. `presences()` ré-résout correctement ; `show()` non.
 */
final class FicheDePresenceFiltresTest extends TestCase
{
    use RefreshDatabase;

    private AnneeScolaire $annee;

    private Etablissement $centre;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->annee = AnneeScolaire::create([
            'nom' => '2026/2027', 'date_debut' => '2026-09-01', 'date_fin' => '2027-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->centre = Etablissement::factory()->create();
    }

    private function viewer(): User
    {
        $user = User::factory()->create();
        foreach (['attendance.view', 'attendance.mark', 'centers.access-all'] as $p) {
            $user->givePermissionTo($p);
        }
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->centre->id]);

        return $user->fresh();
    }

    private function seanceFor(Employee $enseignant, string $heure): Seance
    {
        $group = Group::factory()->create([
            'statut' => Group::STATUT_EN_FORMATION,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'enseignant_id' => $enseignant->id,
        ]);

        return Seance::create([
            'group_id' => $group->id,
            'enseignant_id' => $enseignant->id,
            'date_seance' => '2026-09-14',
            'heure_debut' => $heure,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'statut' => Seance::STATUT_PREVUE,
        ]);
    }

    /**
     * LE BUG : on ouvre la séance de A, puis on choisit l'enseignant B dans
     * « Employé ». La page doit montrer la séance de B — jamais garder celle
     * de A, que le sélecteur ne peut même plus nommer.
     */
    public function test_changing_the_teacher_reloads_the_roll_call_of_that_teacher(): void
    {
        $profA = Employee::factory()->create(['etablissement_id' => $this->centre->id]);
        $profB = Employee::factory()->create(['etablissement_id' => $this->centre->id]);

        $seanceA = $this->seanceFor($profA, '09:00');
        $seanceB = $this->seanceFor($profB, '11:00');

        $this->actingAs($this->viewer())
            ->get(route('backoffice.seances.show', $seanceA).'?date=2026-09-14&enseignant='.$profB->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Seances/Show', false)
                // La séance affichée doit être celle de l'enseignant demandé.
                ->where('seance.id', $seanceB->id)
            );
    }

    /**
     * Et le sélecteur « Séances » doit toujours CONTENIR la séance affichée,
     * sinon il retombe sur son placeholder au-dessus d'un appel bien réel.
     */
    public function test_the_open_seance_is_always_among_its_own_options(): void
    {
        $profA = Employee::factory()->create(['etablissement_id' => $this->centre->id]);
        $profB = Employee::factory()->create(['etablissement_id' => $this->centre->id]);

        $seanceA = $this->seanceFor($profA, '09:00');
        $this->seanceFor($profB, '11:00');

        $props = [];

        $this->actingAs($this->viewer())
            ->get(route('backoffice.seances.show', $seanceA).'?date=2026-09-14&enseignant='.$profB->id)
            ->assertOk()
            ->assertInertia(function ($page) use (&$props): void {
                $props = $page->toArray()['props'];
            });

        if ($props['seance'] === null) {
            $this->assertSame([], $props['seanceOptions'], 'Aucune séance affichée mais des options proposées.');

            return;
        }

        $ids = array_column($props['seanceOptions'], 'value');

        $this->assertContains(
            $props['seance']['id'],
            $ids,
            'La séance affichée est absente de ses propres options : le '
            .'sélecteur affiche « Choisir une séance… » au-dessus d\'un appel '
            .'qui appartient à quelqu\'un d\'autre.',
        );
    }
}
