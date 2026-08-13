<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Inscriptions;

use App\Models\AnneeScolaire;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\Student;
use App\Models\StockArticle;
use App\Models\StockType;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Books ("Livre" stock) assigned to a registration — AssignerLivresInscription
 * + InscriptionController's livres()/groupLivres()/updateLivres() endpoints.
 * A book (StockArticle) with per-center stock is one row PER center (same
 * title, different etablissement_id/quantite) — see the stock_articles
 * migration's docblock.
 */
final class InscriptionLivresTest extends TestCase
{
    use RefreshDatabase;

    private AnneeScolaire $annee;

    private Etablissement $centre;

    private StockType $livreType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->annee = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->centre = Etablissement::factory()->create();
        $this->livreType = StockType::create(['nom' => StockType::SYSTEM_LIVRE, 'is_system' => true, 'statut' => StockType::STATUT_ACTIF]);
    }

    private function userWith(string ...$permissions): User
    {
        $user = User::factory()->create();
        foreach ([...$permissions, 'centers.access-all'] as $p) {
            $user->givePermissionTo($p);
        }

        return $user->fresh();
    }

    private function makeGroup(): Group
    {
        return Group::factory()->create([
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);
    }

    private function makeBook(string $nom = 'Daf Kompakt neu A2', int $quantite = 40, ?int $etablissementId = null): StockArticle
    {
        return StockArticle::create([
            'reference' => 'ART-'.str_pad((string) (StockArticle::count() + 1), 6, '0', STR_PAD_LEFT),
            'nom' => $nom,
            'stock_type_id' => $this->livreType->id,
            'quantite' => $quantite,
            'etablissement_id' => $etablissementId ?? $this->centre->id,
            'statut' => StockArticle::STATUT_ACTIF,
        ]);
    }

    private function enrolledStudent(Group $group): array
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $inscription = Inscription::create([
            'reference' => 'INS-'.str_pad((string) (Inscription::count() + 1), 6, '0', STR_PAD_LEFT),
            'student_id' => $student->id,
            'group_id' => $group->id,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'statut' => Inscription::STATUT_ACTIVE,
            'date_inscription' => '2025-09-15',
        ]);

        return [$student, $inscription];
    }

    // --- groupLivres() lookup ------------------------------------------------

    public function test_group_livres_lists_only_this_centers_book_stock(): void
    {
        $group = $this->makeGroup();
        $otherCentre = Etablissement::factory()->create();
        $this->makeBook('Daf Kompakt neu A2', 40, $this->centre->id);
        $this->makeBook('Daf Kompakt neu A2', 15, $otherCentre->id);

        $this->actingAs($this->userWith('registrations.view', 'registrations.create'))
            ->get(route('backoffice.groups.inscription-livres', $group))
            ->assertOk()
            ->assertJson(fn ($json) => $json->has('livres', 1)
                ->where('livres.0.nom', 'Daf Kompakt neu A2')
                ->where('livres.0.quantite', 40)
            );
    }

    // --- store() with livre_ids ----------------------------------------------

    public function test_creating_a_registration_with_books_decrements_stock_and_creates_pivot_rows(): void
    {
        $group = $this->makeGroup();
        $book = $this->makeBook('Daf Kompakt neu A2', 40);
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);

        $this->actingAs($this->userWith('registrations.view', 'registrations.create'));

        $this->post(route('backoffice.inscriptions.store'), [
            'inscription_mode' => 'existing',
            'student_id' => $student->id,
            'group_id' => $group->id,
            'date_inscription' => '2025-09-15',
            'livre_ids' => [$book->id],
        ])->assertRedirect(route('backoffice.inscriptions.index'));

        $this->assertSame(39, $book->fresh()->quantite);

        $inscription = Inscription::where('student_id', $student->id)->firstOrFail();
        $this->assertSame(1, $inscription->livres()->count());
        $this->assertDatabaseHas('stock_mouvements', [
            'stock_article_id' => $book->id,
            'type' => 'Sortie',
            'quantite' => 1,
        ]);
    }

    public function test_creating_a_registration_with_an_out_of_stock_book_is_refused(): void
    {
        $group = $this->makeGroup();
        $book = $this->makeBook('Daf Kompakt neu A2', 0);
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);

        $this->actingAs($this->userWith('registrations.view', 'registrations.create'))
            ->post(route('backoffice.inscriptions.store'), [
                'inscription_mode' => 'existing',
                'student_id' => $student->id,
                'group_id' => $group->id,
                'date_inscription' => '2025-09-15',
                'livre_ids' => [$book->id],
            ])->assertSessionHasErrors('livre_ids');

        $this->assertSame(0, Inscription::count());
        $this->assertSame(0, $book->fresh()->quantite);
    }

    // --- livres() / updateLivres() on an existing registration ---------------

    public function test_livres_endpoint_returns_assigned_ids_and_center_stock(): void
    {
        $group = $this->makeGroup();
        $book = $this->makeBook('Daf Kompakt neu A2', 39);
        [, $inscription] = $this->enrolledStudent($group);
        $inscription->livres()->create(['stock_article_id' => $book->id]);

        $this->actingAs($this->userWith('registrations.view'))
            ->get(route('backoffice.inscriptions.livres', $inscription))
            ->assertOk()
            ->assertJson(fn ($json) => $json
                ->where('assignedIds', [$book->id])
                ->has('livres', 1)
            );
    }

    public function test_adding_a_book_on_an_existing_registration_decrements_stock_once(): void
    {
        $group = $this->makeGroup();
        $book = $this->makeBook('Daf Kompakt neu A2', 40);
        [, $inscription] = $this->enrolledStudent($group);

        $this->actingAs($this->userWith('registrations.view', 'registrations.manage-fees'))
            ->put(route('backoffice.inscriptions.livres.update', $inscription), [
                'livre_ids' => [$book->id],
            ])->assertRedirect();

        $this->assertSame(39, $book->fresh()->quantite);
        $this->assertSame(1, $inscription->fresh()->livres()->count());
    }

    /**
     * Re-submitting the same already-assigned book must NOT decrement stock
     * a second time — this is the core "no double-decrement on re-edit"
     * guarantee, and the reason a later level change can add a further book
     * without re-charging for ones already given.
     */
    public function test_resubmitting_the_same_book_does_not_double_decrement(): void
    {
        $group = $this->makeGroup();
        $bookA = $this->makeBook('Daf Kompakt neu A2', 40);
        $bookB = $this->makeBook('Daf Kompakt neu B1', 40);
        [, $inscription] = $this->enrolledStudent($group);

        $this->actingAs($this->userWith('registrations.view', 'registrations.manage-fees'));

        $this->put(route('backoffice.inscriptions.livres.update', $inscription), [
            'livre_ids' => [$bookA->id],
        ])->assertRedirect();
        $this->assertSame(39, $bookA->fresh()->quantite);

        // Re-submit A alongside a newly added B — A must stay untouched.
        $this->put(route('backoffice.inscriptions.livres.update', $inscription), [
            'livre_ids' => [$bookA->id, $bookB->id],
        ])->assertRedirect();

        $this->assertSame(39, $bookA->fresh()->quantite);
        $this->assertSame(39, $bookB->fresh()->quantite);
        $this->assertSame(2, $inscription->fresh()->livres()->count());
    }

    public function test_removing_a_book_restores_stock(): void
    {
        $group = $this->makeGroup();
        $book = $this->makeBook('Daf Kompakt neu A2', 39);
        [, $inscription] = $this->enrolledStudent($group);
        $inscription->livres()->create(['stock_article_id' => $book->id]);

        $this->actingAs($this->userWith('registrations.view', 'registrations.manage-fees'))
            ->put(route('backoffice.inscriptions.livres.update', $inscription), [
                'livre_ids' => [],
            ])->assertRedirect();

        $this->assertSame(40, $book->fresh()->quantite);
        $this->assertSame(0, $inscription->fresh()->livres()->count());
        $this->assertDatabaseHas('stock_mouvements', [
            'stock_article_id' => $book->id,
            'type' => 'Entrée',
            'quantite' => 1,
        ]);
    }

    public function test_a_level_change_can_add_a_different_book_without_recharging_the_first(): void
    {
        $group = $this->makeGroup();
        $bookA1 = $this->makeBook('Daf Kompakt neu A1', 40);
        $bookA2 = $this->makeBook('Daf Kompakt neu A2', 40);
        [, $inscription] = $this->enrolledStudent($group);

        $this->actingAs($this->userWith('registrations.view', 'registrations.manage-fees'));

        $this->put(route('backoffice.inscriptions.livres.update', $inscription), [
            'livre_ids' => [$bookA1->id],
        ])->assertRedirect();

        // Level change: keep A1 (already given), add A2.
        $this->put(route('backoffice.inscriptions.livres.update', $inscription), [
            'livre_ids' => [$bookA1->id, $bookA2->id],
        ])->assertRedirect();

        $this->assertSame(39, $bookA1->fresh()->quantite);
        $this->assertSame(39, $bookA2->fresh()->quantite);
        $this->assertSame(2, $inscription->fresh()->livres()->count());
    }

    public function test_updating_livres_requires_manage_fees_permission(): void
    {
        $group = $this->makeGroup();
        $book = $this->makeBook('Daf Kompakt neu A2', 40);
        [, $inscription] = $this->enrolledStudent($group);

        $this->actingAs($this->userWith('registrations.view'))
            ->put(route('backoffice.inscriptions.livres.update', $inscription), [
                'livre_ids' => [$book->id],
            ])->assertForbidden();

        $this->assertSame(40, $book->fresh()->quantite);
    }
}
