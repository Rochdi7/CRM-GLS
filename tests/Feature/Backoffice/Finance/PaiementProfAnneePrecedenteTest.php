<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Models\AnneeScolaire;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\TypeDepense;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ⚠ Un « Paiement prof » vise un groupe d'une année PRÉCÉDENTE — jamais un
 * autre centre (11/09/2026).
 *
 * Un enseignant est réglé APRÈS la prestation : le groupe terminé en juin
 * se paie en septembre, quand le sélecteur du haut est déjà passé à la
 * nouvelle année. Le garde de contexte refusait ce groupe (422), si bien
 * que le paiement se saisissait SANS groupe — hors de tout récapitulatif
 * par groupe — ou sur un homonyme de l'année en cours.
 *
 * C'est la même exception assumée que « Changement de groupe » (§11) : la
 * moitié ANNÉE du garde tombe, et rien d'autre. Ce fichier assère les
 * quatre bornes indissociables.
 */
final class PaiementProfAnneePrecedenteTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $centre;

    private TypeDepense $profType;

    private AnneeScolaire $anneeActive;

    private AnneeScolaire $anneePrecedente;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->centre = Etablissement::factory()->create();
        $this->profType = TypeDepense::create([
            'nom' => TypeDepense::SYSTEM_PAIEMENT_PROF, 'is_system' => true, 'statut' => TypeDepense::STATUT_ACTIF,
        ]);
        $this->anneeActive = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->anneePrecedente = AnneeScolaire::create([
            'nom' => '2024/2025', 'date_debut' => '2024-09-01', 'date_fin' => '2025-08-31',
            'par_defaut' => false, 'inscription_ouverte' => false,
        ]);
    }

    /**
     * Le sélecteur du haut pointe un centre PRÉCIS — c'est le cas réel, et
     * le seul où la moitié CENTRE du garde peut se prononcer : sur « Tous
     * les centres » le contexte est NULL et ne borne rien (§11).
     */
    private function actor(): User
    {
        $user = User::factory()->create();
        foreach (['expenses.view', 'expenses.create', 'centers.access-all'] as $p) {
            $user->givePermissionTo($p);
        }
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->centre->id]);

        session([
            'context.annee_scolaire_id' => $this->anneeActive->id,
            'context.etablissement_id' => $this->centre->id,
        ]);

        return $user->fresh();
    }

    private function group(AnneeScolaire $annee, ?Etablissement $centre = null): Group
    {
        return Group::factory()->create([
            'etablissement_id' => ($centre ?? $this->centre)->id,
            'annee_scolaire_id' => $annee->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(Group $group, array $overrides = []): array
    {
        return array_merge([
            'type_depense_id' => $this->profType->id,
            'group_id' => $group->id,
            'montant' => '800',
            'methode_paiement' => 'Espèces',
            'date_depense' => '2025-09-30',
            'periode_debut' => '2025-05-01',
            'periode_fin' => '2025-06-30',
            'description' => 'Heures de juin, réglées en septembre',
        ], $overrides);
    }

    /** Borne 1 — le cœur de la demande : l'année précédente est ACCEPTÉE. */
    public function test_a_paiement_prof_may_target_a_group_of_a_previous_year(): void
    {
        $group = $this->group($this->anneePrecedente);

        $this->actingAs($this->actor())
            ->post(route('backoffice.depenses.store'), $this->payload($group))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('depenses', ['group_id' => $group->id]);
    }

    /** Borne 2 — le CENTRE ne s'ouvre jamais, même sur une année passée. */
    public function test_a_group_of_another_centre_is_still_refused(): void
    {
        $autre = Etablissement::factory()->create();
        $group = $this->group($this->anneePrecedente, $autre);

        $this->actingAs($this->actor())
            ->post(route('backoffice.depenses.store'), $this->payload($group))
            ->assertSessionHasErrors('group_id');

        $this->assertDatabaseMissing('depenses', ['group_id' => $group->id]);
    }

    /** Borne 3 — une année CLÔTURÉE n'accepte aucune écriture, pour personne. */
    public function test_a_group_of_a_closed_year_is_refused(): void
    {
        $this->anneePrecedente->update(['cloturee' => true]);
        $group = $this->group($this->anneePrecedente);

        $this->actingAs($this->actor())
            ->post(route('backoffice.depenses.store'), $this->payload($group))
            ->assertSessionHasErrors('group_id');

        $this->assertDatabaseMissing('depenses', ['group_id' => $group->id]);
    }

    /**
     * Borne 4 — l'écran ne promet jamais ce que le serveur refuse (§5) : la
     * liste servie au modal exclut l'autre centre et les années clôturées,
     * et le libellé PORTE l'année (deux homonymes sont sinon
     * indiscernables — c'est l'erreur même que la case rend possible).
     */
    public function test_the_dropdown_lists_previous_years_scoped_to_the_centre_and_open_years(): void
    {
        $attendu = $this->group($this->anneePrecedente);
        $autreCentre = $this->group($this->anneePrecedente, Etablissement::factory()->create());

        $close = AnneeScolaire::create([
            'nom' => '2023/2024', 'date_debut' => '2023-09-01', 'date_fin' => '2024-08-31',
            'par_defaut' => false, 'inscription_ouverte' => false, 'cloturee' => true,
        ]);
        $groupeClos = $this->group($close);

        $actif = $this->group($this->anneeActive);

        $response = $this->actingAs($this->actor())->get(route('backoffice.depenses.index'));
        $response->assertOk();

        $options = collect($response->viewData('page')['props']['groupsAnneesPrecedentes']);
        $ids = $options->pluck('id')->all();

        $this->assertContains($attendu->id, $ids);
        $this->assertNotContains($autreCentre->id, $ids, 'un groupe d’un autre centre ne doit jamais être offert');
        $this->assertNotContains($groupeClos->id, $ids, 'une année clôturée refuse toute écriture : ne pas l’offrir');
        $this->assertNotContains($actif->id, $ids, 'l’année active est déjà dans `groups`, pas ici');

        $this->assertSame(
            $attendu->nom.' — 2024/2025',
            $options->firstWhere('id', $attendu->id)['nom'],
            'le libellé doit porter l’année, sinon deux homonymes sont indiscernables',
        );
    }
}
