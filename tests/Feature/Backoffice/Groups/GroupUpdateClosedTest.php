<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Groups;

use App\Models\AnneeScolaire;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\Role;
use App\Models\User;
use App\Support\Access\HiddenAccount;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Modifier un groupe CLOS (« Fin de formation » / « Annulée ») — 07/09/2026.
 *
 * L'onglet Historique reste en lecture seule pour tout le monde ; le SEUL
 * compte de maintenance (HiddenAccount::EMAIL) peut corriger le nom, le
 * niveau ou les dates d'un dossier clos sans passer par « Réactiver », qui
 * remettrait le groupe dans les listes actives et le rendrait de nouveau
 * inscriptible.
 *
 * Les trois garanties testées ici :
 *  1. c'est une IDENTITÉ, pas une permission : un super-admin ordinaire (le
 *     CEO) est refusé, et le second compte du mainteneur — un compte de
 *     STAFF — l'est aussi ;
 *  2. le STATUT reste verrouillé même pour le mainteneur : un groupe clos ne
 *     ressuscite jamais par le modal, seulement par `reopen` ;
 *  3. un groupe actif reste modifiable normalement (la garde ne s'arme que
 *     sur un statut terminal).
 */
final class GroupUpdateClosedTest extends TestCase
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
            'statut' => Group::STATUT_EN_FORMATION,
            'nom' => 'ANASS 15H',
            'date_debut_formation' => '2025-09-15',
            'date_fin_formation' => '2026-06-30',
        ]);
    }

    private function userWithEmail(string $email): User
    {
        $user = User::factory()->create(['email' => $email]);
        $user->assignRole(Role::SUPER_ADMIN);
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->centre->id]);

        return $user->fresh();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'nom' => 'ANASS 15H CORRIGÉ',
            'niveau' => $this->group->niveau,
            'statut' => $this->group->fresh()->statut,
            'date_debut_formation' => '2025-09-15',
            'date_fin_formation' => '2026-06-30',
        ], $overrides);
    }

    public function test_the_maintainer_edits_a_finished_group(): void
    {
        $this->group->archiverCommeTermine();

        $this->actingAs($this->userWithEmail(HiddenAccount::EMAIL))
            ->put(route('backoffice.groups.update', $this->group), $this->payload())
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame('ANASS 15H CORRIGÉ', $this->group->fresh()->nom);
        // Le dossier reste clos : la correction ne le ressuscite pas.
        $this->assertSame(Group::STATUT_FIN_FORMATION, $this->group->fresh()->statut);
    }

    public function test_the_maintainer_edits_a_cancelled_group(): void
    {
        $this->group->annuler();

        $this->actingAs($this->userWithEmail(HiddenAccount::EMAIL))
            ->put(route('backoffice.groups.update', $this->group), $this->payload())
            ->assertSessionHasNoErrors();

        $this->assertSame('ANASS 15H CORRIGÉ', $this->group->fresh()->nom);
        $this->assertSame(Group::STATUT_ANNULEE, $this->group->fresh()->statut);
    }

    public function test_the_statut_stays_locked_even_for_the_maintainer(): void
    {
        $this->group->archiverCommeTermine();

        $this->actingAs($this->userWithEmail(HiddenAccount::EMAIL))
            ->put(route('backoffice.groups.update', $this->group), $this->payload([
                'statut' => Group::STATUT_EN_FORMATION,
            ]));

        // Sortir d'un statut terminal passe UNIQUEMENT par `reopen`.
        $this->assertSame(Group::STATUT_FIN_FORMATION, $this->group->fresh()->statut);
    }

    public function test_an_ordinary_super_admin_cannot_edit_a_closed_group(): void
    {
        $this->group->archiverCommeTermine();

        $this->actingAs($this->userWithEmail('rafik@glszentrum.com'))
            ->put(route('backoffice.groups.update', $this->group), $this->payload())
            ->assertForbidden();

        $this->assertSame('ANASS 15H', $this->group->fresh()->nom);
    }

    public function test_the_maintainers_staff_account_cannot_edit_a_closed_group(): void
    {
        // STAFF_EMAIL est un compte de personnel, pas l'identité de
        // maintenance : la règle vise EMAIL seul, jamais emails().
        $this->group->annuler();

        $this->actingAs($this->userWithEmail(HiddenAccount::STAFF_EMAIL))
            ->put(route('backoffice.groups.update', $this->group), $this->payload())
            ->assertForbidden();

        $this->assertSame('ANASS 15H', $this->group->fresh()->nom);
    }

    public function test_an_active_group_is_still_editable_by_an_ordinary_super_admin(): void
    {
        $this->actingAs($this->userWithEmail('rafik@glszentrum.com'))
            ->put(route('backoffice.groups.update', $this->group), $this->payload())
            ->assertSessionHasNoErrors();

        $this->assertSame('ANASS 15H CORRIGÉ', $this->group->fresh()->nom);
    }

    public function test_no_role_preset_grants_the_closed_group_edit(): void
    {
        $this->group->archiverCommeTermine();

        foreach (Role::query()->where('name', '!=', Role::SUPER_ADMIN)->get() as $role) {
            $user = User::factory()->create();
            $user->assignRole($role);
            Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->centre->id]);

            $this->assertFalse(
                $user->fresh()->can('updateClosed', $this->group),
                "Le role {$role->name} ne doit pas pouvoir modifier un groupe clos.",
            );
        }
    }
}
