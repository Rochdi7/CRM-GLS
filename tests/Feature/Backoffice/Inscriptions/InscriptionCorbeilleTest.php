<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Inscriptions;

use App\Models\AnneeScolaire;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionCorbeille;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Services\Context\CurrentContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Corbeille des inscriptions (19/10/2026) : chaque suppression dépose une
 * ligne append-only (ce qu'était le dossier + QUI l'a supprimé), que
 * l'onglet « Corbeille » liste, compte et ventile par personne.
 */
final class InscriptionCorbeilleTest extends TestCase
{
    use RefreshDatabase;

    private AnneeScolaire $annee;

    private Etablissement $rabat;

    private Group $group;

    private Employee $guichet;

    private static int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->annee = AnneeScolaire::create([
            'nom' => '2026/2027', 'date_debut' => '2026-09-01', 'date_fin' => '2027-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->rabat = Etablissement::factory()->create(['nom_centre' => 'GLS Rabat']);
        $this->group = Group::factory()->create([
            'etablissement_id' => $this->rabat->id, 'annee_scolaire_id' => $this->annee->id, 'nom' => 'Rabat A1',
        ]);

        $this->guichet = Employee::factory()->create(['etablissement_id' => $this->rabat->id]);
        $this->guichet->user->forceFill(['must_change_password' => false, 'name' => 'Sara Guichet'])->save();
        $this->guichet->user->givePermissionTo(['registrations.view', 'registrations.delete']);
    }

    /** La corbeille est réservée au super-admin (registrations.trash ∈ superAdminOnly()). */
    private function superAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::SUPER_ADMIN);

        return $admin->fresh();
    }

    private function inscription(?Group $group = null, string $nomEtudiant = 'BENALI'): Inscription
    {
        $group ??= $this->group;
        $student = Student::factory()->create(['etablissement_id' => $group->etablissement_id, 'nom' => $nomEtudiant, 'prenom' => 'Sara']);

        return Inscription::create([
            'reference' => 'INS-C'.(++self::$sequence),
            'student_id' => $student->id,
            'group_id' => $group->id,
            'etablissement_id' => $group->etablissement_id,
            'annee_scolaire_id' => $group->annee_scolaire_id,
            'statut' => Inscription::STATUT_ACTIVE,
            'date_inscription' => '2026-09-10',
            'montant_total' => 1500,
        ]);
    }

    public function test_deleting_a_registration_writes_a_frozen_trash_row_naming_the_author(): void
    {
        $inscription = $this->inscription();
        $student = $inscription->student;

        $this->actingAs($this->guichet->user)
            ->delete(route('backoffice.inscriptions.destroy', $inscription))
            ->assertSessionHasNoErrors();

        $this->assertNull(Inscription::find($inscription->id));

        $row = InscriptionCorbeille::query()->where('inscription_id', $inscription->id)->firstOrFail();
        $this->assertSame($inscription->reference, $row->reference);
        $this->assertSame($student->id, $row->student_id);
        $this->assertSame('Sara BENALI', $row->student_nom);
        $this->assertSame($student->reference, $row->student_reference);
        $this->assertSame($this->rabat->id, $row->etablissement_id);
        $this->assertSame($this->annee->id, $row->annee_scolaire_id);
        $this->assertSame('2026/2027', $row->annee_nom);
        $this->assertSame('Rabat A1', $row->groupe_nom);
        $this->assertSame(Inscription::STATUT_ACTIVE, $row->statut);
        $this->assertSame('1500.00', (string) $row->montant_total);
        $this->assertSame($this->guichet->user->id, $row->supprime_par_user_id);
        $this->assertStringStartsWith('Sara Guichet', (string) $row->supprime_par_label);
        $this->assertNotNull($row->supprime_le);
    }

    public function test_a_trash_row_is_append_only(): void
    {
        $inscription = $this->inscription();
        $this->actingAs($this->guichet->user)->delete(route('backoffice.inscriptions.destroy', $inscription));

        $row = InscriptionCorbeille::query()->firstOrFail();

        $this->expectException(\RuntimeException::class);
        $row->update(['supprime_par_label' => 'Quelqu’un d’autre']);
    }

    public function test_the_trash_tab_lists_deleted_registrations_and_counts_them_per_author(): void
    {
        $autre = Employee::factory()->create(['etablissement_id' => $this->rabat->id]);
        $autre->user->forceFill(['must_change_password' => false, 'name' => 'Ali Autre'])->save();
        $autre->user->givePermissionTo(['registrations.view', 'registrations.delete']);

        $a = $this->inscription(null, 'UN');
        $b = $this->inscription(null, 'DEUX');
        $c = $this->inscription(null, 'TROIS');
        $this->inscription(null, 'RESTE'); // never deleted

        $this->actingAs($this->guichet->user)->delete(route('backoffice.inscriptions.destroy', $a))->assertSessionHasNoErrors();
        $this->actingAs($this->guichet->user)->delete(route('backoffice.inscriptions.destroy', $b))->assertSessionHasNoErrors();
        $this->actingAs($autre->user->fresh())->delete(route('backoffice.inscriptions.destroy', $c))->assertSessionHasNoErrors();

        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->get(route('backoffice.inscriptions.corbeille'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Inscriptions/Corbeille', false)
                ->where('total', 3)
                ->has('rows.data', 3)
                ->where('rows.data.0.reference', $c->reference)
                ->where('rows.data.0.student.nomComplet', 'Sara TROIS')
                ->where('rows.data.0.groupe', 'Rabat A1')
                ->has('parAuteur', 2)
                ->where('parAuteur.0.userId', $this->guichet->user->id)
                ->where('parAuteur.0.count', 2)
                ->where('parAuteur.1.userId', $autre->user->id)
                ->where('parAuteur.1.count', 1)
                ->has('auteurOptions', 2)
                ->where('tabCounts./backoffice/inscriptions/corbeille', 3));

        // Filtre « Supprimé par » : seules les lignes de cette personne, et le total suit.
        $this->actingAs($admin)
            ->get(route('backoffice.inscriptions.corbeille', ['auteurFilter' => (string) $autre->user->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('total', 1)
                ->has('rows.data', 1)
                ->where('rows.data.0.reference', $c->reference)
                ->has('parAuteur', 1));

        // Recherche sur le nom de l'étudiant.
        $this->actingAs($admin)
            ->get(route('backoffice.inscriptions.corbeille', ['search' => 'deux']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('total', 1)
                ->where('rows.data.0.reference', $b->reference));

        // La page Inscriptions porte le badge de l'onglet — pour le super-admin
        // seulement ; le guichet ne reçoit même pas le compte.
        $this->actingAs($admin)
            ->get(route('backoffice.inscriptions.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('tabCounts./backoffice/inscriptions/corbeille', 3));
        $this->actingAs($this->guichet->user)
            ->get(route('backoffice.inscriptions.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('tabCounts./backoffice/inscriptions/corbeille', 0));
    }

    public function test_the_trash_follows_the_active_centre_and_year(): void
    {
        $casa = Etablissement::factory()->create(['nom_centre' => 'GLS Casablanca']);
        $groupCasa = Group::factory()->create(['etablissement_id' => $casa->id, 'annee_scolaire_id' => $this->annee->id, 'nom' => 'Casa A1']);
        $autreAnnee = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => false, 'inscription_ouverte' => false,
        ]);
        $groupVieux = Group::factory()->create(['etablissement_id' => $this->rabat->id, 'annee_scolaire_id' => $autreAnnee->id, 'nom' => 'Rabat vieux']);

        $admin = User::factory()->create();
        $admin->assignRole(Role::SUPER_ADMIN);

        $rabat = $this->inscription();
        $casab = $this->inscription($groupCasa, 'CASA');
        $vieux = $this->inscription($groupVieux, 'VIEUX');

        foreach ([$rabat, $casab, $vieux] as $i) {
            $this->actingAs($admin)->delete(route('backoffice.inscriptions.destroy', $i))->assertSessionHasNoErrors();
        }

        $this->actingAs($admin);
        app(CurrentContext::class)->setEtablissement($this->rabat->id);
        app(CurrentContext::class)->setAnneeScolaire($this->annee->id);

        $this->get(route('backoffice.inscriptions.corbeille'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('total', 1)
                ->has('rows.data', 1)
                ->where('rows.data.0.reference', $rabat->reference));

        app(CurrentContext::class)->setAnneeScolaire($autreAnnee->id);

        $this->get(route('backoffice.inscriptions.corbeille'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('total', 1)
                ->where('rows.data.0.reference', $vieux->reference));
    }

    public function test_the_trash_is_super_admin_only(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('backoffice.inscriptions.corbeille'))
            ->assertForbidden();

        // registrations.view + registrations.delete ne suffisent pas : la
        // permission est dans superAdminOnly(), aucun preset ne la porte.
        $this->actingAs($this->guichet->user)
            ->get(route('backoffice.inscriptions.corbeille'))
            ->assertForbidden();

        $this->actingAs($this->superAdmin())
            ->get(route('backoffice.inscriptions.corbeille'))
            ->assertOk();
    }
}
