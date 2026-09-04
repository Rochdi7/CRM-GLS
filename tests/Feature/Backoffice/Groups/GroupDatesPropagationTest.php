<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Groups;

use App\Domain\Groups\Actions\SynchroniserDatesInscriptions;
use App\Models\AnneeScolaire;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * « Modifier le groupe » → les dates de formation redescendent sur les
 * inscriptions du groupe (Domain\Groups\Actions\SynchroniserDatesInscriptions).
 *
 * La règle métier (confirmée le 04/09/2026) : `inscriptions.date_debut` /
 * `date_fin` sont les dates DU GROUPE, pas des données propres à l'étudiant.
 * La seule date qui appartient à l'étudiant est `date_inscription`, et elle
 * ne bouge jamais ici.
 */
final class GroupDatesPropagationTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $centre;

    private AnneeScolaire $annee;

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
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'date_debut_formation' => '2025-09-01',
            'date_fin_formation' => '2026-06-30',
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

    private function inscription(array $attributes = []): Inscription
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);

        return Inscription::create([
            'reference' => 'INS-DTS-'.$student->id,
            'student_id' => $student->id,
            'group_id' => $this->group->id,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            ...$attributes,
        ]);
    }

    private function editGroup(string $debut, string $fin): TestResponse
    {
        return $this->put(route('backoffice.groups.update', $this->group), [
            'nom' => $this->group->nom,
            'niveau' => $this->group->niveau,
            'statut' => $this->group->statut,
            'date_debut_formation' => $debut,
            'date_fin_formation' => $fin,
        ]);
    }

    public function test_editing_the_group_dates_rewrites_them_on_its_active_inscriptions(): void
    {
        $this->actingAs($this->userWith('groups.view', 'groups.update'));

        $inscription = $this->inscription([
            'statut' => Inscription::STATUT_ACTIVE,
            'date_inscription' => '2025-08-28',
            'date_debut' => '2025-08-28',
            'date_fin' => '2026-03-31',
        ]);

        $this->editGroup('2025-10-12', '2026-08-13')->assertSessionDoesntHaveErrors();

        $fresh = $inscription->fresh();
        $this->assertSame('2025-10-12', $fresh->date_debut->toDateString());
        $this->assertSame('2026-08-13', $fresh->date_fin->toDateString());
    }

    /**
     * `date_inscription` est le jour où l'étudiant s'est inscrit — une donnée
     * qui lui appartient. Elle ne suit jamais le groupe.
     */
    public function test_the_registration_own_date_is_never_touched(): void
    {
        $this->actingAs($this->userWith('groups.view', 'groups.update'));

        $inscription = $this->inscription([
            'statut' => Inscription::STATUT_ACTIVE,
            'date_inscription' => '2025-08-28',
            'date_debut' => '2025-09-01',
            'date_fin' => '2026-06-30',
        ]);

        $this->editGroup('2025-10-12', '2026-08-13')->assertSessionDoesntHaveErrors();

        $this->assertSame('2025-08-28', $inscription->fresh()->date_inscription->toDateString());
    }

    /**
     * Une inscription qui n'est plus Active est de l'histoire figée : elle
     * garde les dates du groupe telles qu'elles étaient de son temps, comme
     * ses lignes de frais (cf. RetirerFraisGroupe).
     */
    public function test_a_cancelled_inscription_keeps_its_frozen_dates(): void
    {
        $this->actingAs($this->userWith('groups.view', 'groups.update'));

        $annulee = $this->inscription([
            'statut' => Inscription::STATUT_ANNULEE,
            'date_inscription' => '2025-08-28',
            'date_debut' => '2025-09-01',
            'date_fin' => '2026-06-30',
        ]);

        $this->editGroup('2025-10-12', '2026-08-13')->assertSessionDoesntHaveErrors();

        $fresh = $annulee->fresh();
        $this->assertSame('2025-09-01', $fresh->date_debut->toDateString());
        $this->assertSame('2026-06-30', $fresh->date_fin->toDateString());
    }

    /**
     * Une inscription déjà alignée n'est pas réécrite du tout : pas d'UPDATE,
     * donc pas d'entrée « avant/après » identique dans le journal d'audit.
     */
    public function test_an_already_aligned_inscription_is_not_rewritten(): void
    {
        $this->actingAs($this->userWith('groups.view', 'groups.update'));

        $inscription = $this->inscription([
            'statut' => Inscription::STATUT_ACTIVE,
            'date_inscription' => '2025-08-28',
            'date_debut' => '2025-09-01',
            'date_fin' => '2026-06-30',
        ]);
        $avant = $inscription->fresh()->updated_at;

        // Mêmes dates que celles déjà portées par le groupe.
        $this->editGroup('2025-09-01', '2026-06-30')->assertSessionDoesntHaveErrors();

        $this->assertEquals($avant, $inscription->fresh()->updated_at);
    }

    /**
     * Un groupe sans dates n'efface pas celles de ses inscriptions : il n'a
     * rien à propager. Vider serait une perte, pas une synchronisation.
     */
    public function test_a_group_without_dates_erases_nothing(): void
    {
        $this->group->update(['date_debut_formation' => null, 'date_fin_formation' => null]);

        $inscription = $this->inscription([
            'statut' => Inscription::STATUT_ACTIVE,
            'date_inscription' => '2025-08-28',
            'date_debut' => '2025-09-01',
            'date_fin' => '2026-06-30',
        ]);

        $this->assertSame(0, app(SynchroniserDatesInscriptions::class)->handle($this->group->fresh()));

        $fresh = $inscription->fresh();
        $this->assertSame('2025-09-01', $fresh->date_debut->toDateString());
        $this->assertSame('2026-06-30', $fresh->date_fin->toDateString());
    }

    public function test_the_action_reports_only_the_rows_it_actually_changed(): void
    {
        $this->inscription([
            'statut' => Inscription::STATUT_ACTIVE,
            'date_inscription' => '2025-08-28',
            'date_debut' => '2025-08-28',
            'date_fin' => '2026-03-31',
        ]);
        // Déjà alignée — ne doit pas être comptée.
        $this->inscription([
            'statut' => Inscription::STATUT_ACTIVE,
            'date_inscription' => '2025-08-28',
            'date_debut' => '2025-09-01',
            'date_fin' => '2026-06-30',
        ]);

        $this->assertSame(1, app(SynchroniserDatesInscriptions::class)->handle($this->group));
    }
}
