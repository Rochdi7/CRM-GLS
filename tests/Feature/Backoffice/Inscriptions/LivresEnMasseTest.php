<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Inscriptions;

use App\Models\AnneeScolaire;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionLivre;
use App\Models\StockArticle;
use App\Models\StockMouvement;
use App\Models\StockType;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Onglet « Livres » des Étudiants (12/10/2026) — remise d'un livre en masse
 * à un groupe. Chaque remise = une ligne inscription_livres + une Sortie −1 ;
 * un lot douteux est refusé EN ENTIER, jamais filtré en silence.
 */
final class LivresEnMasseTest extends TestCase
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

    private function user(string ...$permissions): User
    {
        $user = User::factory()->create();
        foreach ([...$permissions, 'centers.access-all'] as $p) {
            $user->givePermissionTo($p);
        }

        return $user->fresh();
    }

    private function book(string $nom, int $quantite, ?int $centreId = null): StockArticle
    {
        return StockArticle::create([
            'reference' => 'ART-'.str_pad((string) (StockArticle::count() + 1), 6, '0', STR_PAD_LEFT),
            'nom' => $nom,
            'stock_type_id' => $this->livreType->id,
            'quantite' => $quantite,
            'etablissement_id' => $centreId ?? $this->centre->id,
            'statut' => StockArticle::STATUT_ACTIF,
        ]);
    }

    private function group(): Group
    {
        return Group::factory()->create([
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);
    }

    private function inscription(Group $group, string $statut = Inscription::STATUT_ACTIVE): Inscription
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);

        return Inscription::create([
            'reference' => 'INS-'.str_pad((string) (Inscription::count() + 1), 6, '0', STR_PAD_LEFT),
            'student_id' => $student->id,
            'group_id' => $group->id,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'statut' => $statut,
            'date_inscription' => '2025-09-15',
        ]);
    }

    public function test_the_page_lists_every_registration_with_the_books_it_already_has(): void
    {
        $group = $this->group();
        $a1 = $this->book('Daf Kompakt neu A1', 5);
        $spektrum = $this->book('Spektrum Deutsch A1+', 5);
        $avecA1 = $this->inscription($group);
        $sans = $this->inscription($group, Inscription::STATUT_ANNULEE);
        $avecA1->livres()->create(['stock_article_id' => $a1->id]);

        $this->actingAs($this->user('registrations.manage-fees'))
            ->get(route('backoffice.students.livres.index', ['group_id' => $group->id, 'livre_id' => $spektrum->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Students/Livres', false)
                ->has('livres', 2)
                ->has('lignes', 2)
                ->where('lignes', function ($rows) use ($avecA1, $sans) {
                    $rows = collect($rows)->keyBy('id');

                    return $rows[$avecA1->id]['livres'][0]['nom'] === 'Daf Kompakt neu A1'
                        && $rows[$avecA1->id]['aDejaCeLivre'] === false
                        && $rows[$sans->id]['livres'] === []
                        && $rows[$sans->id]['statut'] === Inscription::STATUT_ANNULEE;
                })
            );
    }

    public function test_ticked_registrations_receive_the_book_and_stock_goes_down(): void
    {
        $group = $this->group();
        $a1 = $this->book('Daf Kompakt neu A1', 5);
        $spektrum = $this->book('Spektrum Deutsch A1+', 3);
        $one = $this->inscription($group);
        $two = $this->inscription($group, Inscription::STATUT_ANNULEE);
        $notTicked = $this->inscription($group);
        $one->livres()->create(['stock_article_id' => $a1->id]);

        $this->actingAs($this->user('registrations.manage-fees'))
            ->post(route('backoffice.students.livres.store'), [
                'group_id' => $group->id,
                'stock_article_id' => $spektrum->id,
                'inscription_ids' => [$one->id, $two->id],
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertSame(1, (int) $spektrum->fresh()->quantite);
        $this->assertSame(2, StockMouvement::where('stock_article_id', $spektrum->id)->where('type', StockMouvement::TYPE_SORTIE)->count());
        // The other book already given is never taken back.
        $this->assertTrue($one->livres()->where('stock_article_id', $a1->id)->exists());
        $this->assertSame(5, (int) $a1->fresh()->quantite);
        $this->assertFalse($notTicked->livres()->exists());
    }

    public function test_a_registration_that_already_has_the_book_is_skipped_not_charged_twice(): void
    {
        $group = $this->group();
        $spektrum = $this->book('Spektrum Deutsch A1+', 3);
        $deja = $this->inscription($group);
        $nouveau = $this->inscription($group);
        $deja->livres()->create(['stock_article_id' => $spektrum->id]);

        $this->actingAs($this->user('registrations.manage-fees'))
            ->post(route('backoffice.students.livres.store'), [
                'group_id' => $group->id,
                'stock_article_id' => $spektrum->id,
                'inscription_ids' => [$deja->id, $nouveau->id],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, (int) $spektrum->fresh()->quantite);
        $this->assertSame(1, InscriptionLivre::where('inscription_id', $deja->id)->count());
    }

    public function test_not_enough_stock_refuses_the_whole_batch(): void
    {
        $group = $this->group();
        $spektrum = $this->book('Spektrum Deutsch A1+', 1);
        $one = $this->inscription($group);
        $two = $this->inscription($group);

        $this->actingAs($this->user('registrations.manage-fees'))
            ->post(route('backoffice.students.livres.store'), [
                'group_id' => $group->id,
                'stock_article_id' => $spektrum->id,
                'inscription_ids' => [$one->id, $two->id],
            ])
            ->assertSessionHasErrors('inscription_ids');

        $this->assertSame(1, (int) $spektrum->fresh()->quantite);
        $this->assertSame(0, InscriptionLivre::count());
    }

    public function test_a_registration_of_another_group_refuses_the_whole_batch(): void
    {
        $group = $this->group();
        $other = $this->group();
        $spektrum = $this->book('Spektrum Deutsch A1+', 5);
        $mine = $this->inscription($group);
        $forged = $this->inscription($other);

        $this->actingAs($this->user('registrations.manage-fees'))
            ->post(route('backoffice.students.livres.store'), [
                'group_id' => $group->id,
                'stock_article_id' => $spektrum->id,
                'inscription_ids' => [$mine->id, $forged->id],
            ])
            ->assertSessionHasErrors('inscription_ids');

        $this->assertSame(5, (int) $spektrum->fresh()->quantite);
        $this->assertSame(0, InscriptionLivre::count());
    }

    public function test_a_book_of_another_centre_is_refused(): void
    {
        $group = $this->group();
        $ailleurs = $this->book('Spektrum Deutsch A1+', 5, Etablissement::factory()->create()->id);
        $one = $this->inscription($group);

        $this->actingAs($this->user('registrations.manage-fees'))
            ->post(route('backoffice.students.livres.store'), [
                'group_id' => $group->id,
                'stock_article_id' => $ailleurs->id,
                'inscription_ids' => [$one->id],
            ])
            ->assertSessionHasErrors('stock_article_id');

        $this->assertSame(5, (int) $ailleurs->fresh()->quantite);
    }

    public function test_without_the_permission_the_tab_and_the_write_are_forbidden(): void
    {
        $group = $this->group();
        $spektrum = $this->book('Spektrum Deutsch A1+', 5);
        $one = $this->inscription($group);
        $user = $this->user('students.view');

        $this->actingAs($user)->get(route('backoffice.students.livres.index'))->assertForbidden();
        $this->actingAs($user)
            ->post(route('backoffice.students.livres.store'), [
                'group_id' => $group->id,
                'stock_article_id' => $spektrum->id,
                'inscription_ids' => [$one->id],
            ])
            ->assertForbidden();

        $this->assertSame(5, (int) $spektrum->fresh()->quantite);
    }
}
