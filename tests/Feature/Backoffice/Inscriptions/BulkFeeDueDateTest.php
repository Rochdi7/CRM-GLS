<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Inscriptions;

use App\Models\AnneeScolaire;
use App\Models\Etablissement;
use App\Models\Frais;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Student;
use App\Models\User;
use App\Support\Authorization\PermissionRegistry;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * « Échéances en masse » — appliquer UNE date d'échéance à plusieurs lignes
 * de frais d'un groupe d'un seul coup.
 *
 * Ce que ces tests verrouillent :
 *  - l'outil est ouvert à TOUS les rôles (`defaultForEveryRole`), parce
 *    qu'il ne déplace aucun argent ;
 *  - il n'écrit QUE `date_echeance` : montant, statut et encaissements sont
 *    intacts après un lot ;
 *  - la portée (centres affectés + contexte actif) est revérifiée À
 *    L'ÉCRITURE, ligne par ligne, et un id hors portée fait échouer le LOT
 *    ENTIER au lieu d'être filtré en silence — sinon l'opérateur croit avoir
 *    modifié 30 lignes quand 28 seulement ont bougé ;
 *  - une ligne MASQUÉE n'est jamais redatée (elle n'est plus due, son argent
 *    a été libéré en avance).
 */
final class BulkFeeDueDateTest extends TestCase
{
    use RefreshDatabase;

    private AnneeScolaire $annee;

    private Etablissement $centre;

    private Group $group;

    private Frais $frais;

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
        ]);
        $this->frais = Frais::create([
            'nom' => 'Frais de Juillet', 'montant_defaut' => 1300, 'statut' => Frais::STATUT_ACTIF,
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

    private static int $counter = 0;

    private function feeFor(
        ?Group $group = null,
        string $echeance = '2025-10-01',
        string $statut = Inscription::STATUT_ACTIVE,
    ): InscriptionFee {
        $group ??= $this->group;
        $student = Student::factory()->create(['etablissement_id' => $group->etablissement_id]);
        $inscription = Inscription::create([
            'reference' => 'INS-BULK'.(++self::$counter),
            'student_id' => $student->id,
            'group_id' => $group->id,
            'etablissement_id' => $group->etablissement_id,
            'annee_scolaire_id' => $group->annee_scolaire_id,
            'statut' => $statut,
            'date_inscription' => '2025-09-15',
            'montant_total' => 1300,
        ]);

        return InscriptionFee::create([
            'inscription_id' => $inscription->id,
            'frais_id' => $this->frais->id,
            'nom' => 'Frais de Juillet',
            'montant_initial' => 1300,
            'montant' => 1300,
            'date_echeance' => $echeance,
            'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);
    }

    public function test_the_permission_is_granted_to_every_role(): void
    {
        // Demande métier : n'importe quel employé recale les échéances d'un
        // groupe. Le droit vit dans defaultForEveryRole() plutôt que recopié
        // dans les treize presets, donc un rôle ajouté demain l'a d'office.
        $this->assertContains('fee-due-dates.bulk-update', PermissionRegistry::defaultForEveryRole());

        foreach (array_keys(PermissionRegistry::roles()) as $role) {
            if ($role === 'super-admin') {
                continue;
            }

            $this->assertTrue(
                \App\Models\Role::findByName($role)->hasPermissionTo('fee-due-dates.bulk-update'),
                "[$role] must be able to bulk-update due dates",
            );
        }
    }

    public function test_the_page_is_refused_without_the_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('backoffice.bulk-echeance.index'))
            ->assertForbidden();
    }

    public function test_it_lists_the_group_students_for_the_chosen_fee(): void
    {
        $fee = $this->feeFor();
        $this->feeFor();
        // Une inscription d'un AUTRE groupe ne doit pas apparaître.
        $autre = Group::factory()->create([
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);
        $this->feeFor($autre);

        $this->actingAs($this->userWith('fee-due-dates.bulk-update'))
            ->get(route('backoffice.bulk-echeance.index', [
                'groupFilter' => $this->group->id,
                'fraisFilter' => $this->frais->id,
            ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Backoffice/EcheancesEnMasse/Index')
                ->has('lignes', 2)
                ->where('lignes.0.dateEcheance', $fee->date_echeance->toDateString()));
    }

    public function test_it_applies_one_date_to_every_selected_line(): void
    {
        $a = $this->feeFor();
        $b = $this->feeFor();
        $c = $this->feeFor();

        $this->actingAs($this->userWith('fee-due-dates.bulk-update'))
            ->post(route('backoffice.bulk-echeance.update'), [
                'fee_ids' => [$a->id, $b->id],
                'date_echeance' => '2026-01-15',
            ])
            ->assertRedirect();

        $this->assertSame('2026-01-15', $a->fresh()->date_echeance->toDateString());
        $this->assertSame('2026-01-15', $b->fresh()->date_echeance->toDateString());
        // La ligne non cochée ne bouge pas.
        $this->assertSame('2025-10-01', $c->fresh()->date_echeance->toDateString());
    }

    public function test_it_writes_only_the_due_date_and_never_the_money(): void
    {
        $fee = $this->feeFor();

        $this->actingAs($this->userWith('fee-due-dates.bulk-update'))
            ->post(route('backoffice.bulk-echeance.update'), [
                'fee_ids' => [$fee->id],
                'date_echeance' => '2026-02-20',
            ])
            ->assertRedirect();

        $fresh = $fee->fresh();
        $this->assertSame('2026-02-20', $fresh->date_echeance->toDateString());
        $this->assertSame('1300.00', (string) $fresh->montant);
        $this->assertSame('1300.00', (string) $fresh->montant_initial);
        $this->assertSame(InscriptionFee::STATUT_NON_PAYE, $fresh->statut);
    }

    public function test_a_forged_id_outside_the_scope_refuses_the_whole_batch(): void
    {
        $mine = $this->feeFor();

        // Une ligne d'un centre que l'utilisateur n'atteint pas.
        $autreCentre = Etablissement::factory()->create();
        $autreGroupe = Group::factory()->create([
            'etablissement_id' => $autreCentre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);
        $etrangere = $this->feeFor($autreGroupe);

        $user = User::factory()->create();
        $user->givePermissionTo('fee-due-dates.bulk-update');
        \App\Models\Employee::factory()->create([
            'user_id' => $user->id,
            'etablissement_id' => $this->centre->id,
        ]);

        $this->actingAs($user->fresh())
            ->post(route('backoffice.bulk-echeance.update'), [
                'fee_ids' => [$mine->id, $etrangere->id],
                'date_echeance' => '2026-03-10',
            ])
            ->assertSessionHasErrors('fee_ids');

        // Refus GLOBAL : la ligne légitime du lot n'a pas bougé non plus.
        $this->assertSame('2025-10-01', $mine->fresh()->date_echeance->toDateString());
        $this->assertSame('2025-10-01', $etrangere->fresh()->date_echeance->toDateString());
    }

    public function test_a_hidden_fee_line_is_never_redated(): void
    {
        $fee = $this->feeFor();
        $fee->update(['masque_le' => now(), 'masque_origine' => InscriptionFee::MASQUE_ORIGINE_MANUEL]);

        $this->actingAs($this->userWith('fee-due-dates.bulk-update'))
            ->post(route('backoffice.bulk-echeance.update'), [
                'fee_ids' => [$fee->id],
                'date_echeance' => '2026-04-01',
            ])
            ->assertSessionHasErrors('fee_ids');

        $this->assertSame('2025-10-01', $fee->fresh()->date_echeance->toDateString());
    }

    public function test_it_requires_at_least_one_line_and_a_date(): void
    {
        $user = $this->userWith('fee-due-dates.bulk-update');

        $this->actingAs($user)
            ->post(route('backoffice.bulk-echeance.update'), [
                'fee_ids' => [],
                'date_echeance' => '2026-05-01',
            ])
            ->assertSessionHasErrors('fee_ids');

        $fee = $this->feeFor();

        $this->actingAs($user)
            ->post(route('backoffice.bulk-echeance.update'), [
                'fee_ids' => [$fee->id],
                'date_echeance' => '',
            ])
            ->assertSessionHasErrors('date_echeance');
    }

    public function test_the_fee_dropdown_only_offers_fees_the_group_actually_carries(): void
    {
        $this->feeFor();
        // Un frais du catalogue qu'AUCUNE inscription du groupe ne porte : il
        // ouvrirait un tableau vide sans rien expliquer à l'écran.
        Frais::create(['nom' => 'Frais jamais utilisé', 'montant_defaut' => 500, 'statut' => Frais::STATUT_ACTIF]);

        $this->actingAs($this->userWith('fee-due-dates.bulk-update'))
            ->get(route('backoffice.bulk-echeance.index', ['groupFilter' => $this->group->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('fraisOptions', 1));
    }
}
