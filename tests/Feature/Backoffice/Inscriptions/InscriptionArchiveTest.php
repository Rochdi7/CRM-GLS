<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Inscriptions;

use App\Models\AnneeScolaire;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Services\Context\CurrentContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Inscription « Archivée » = en PAUSE (19/10/2026) : motif + date de retour
 * obligatoires, le dossier sort de la liste courante vers l'onglet
 * « Inscriptions archivées », compté à part sur le tableau de bord, et se
 * réactive d'un clic. Rien d'autre ne bouge — ni frais, ni argent.
 */
final class InscriptionArchiveTest extends TestCase
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
        $this->guichet->user->forceFill(['must_change_password' => false])->save();
        $this->guichet->user->givePermissionTo(['dashboard.view', 'registrations.view', 'registrations.archive']);
    }

    private function inscription(?Student $student = null, ?Group $group = null, string $statut = Inscription::STATUT_ACTIVE): Inscription
    {
        $group ??= $this->group;
        $student ??= Student::factory()->create(['etablissement_id' => $group->etablissement_id, 'nom' => 'BENALI', 'prenom' => 'Sara']);

        $inscription = Inscription::create([
            'reference' => 'INS-A'.(++self::$sequence),
            'student_id' => $student->id,
            'group_id' => $group->id,
            'etablissement_id' => $group->etablissement_id,
            'annee_scolaire_id' => $group->annee_scolaire_id,
            'statut' => $statut,
            'date_inscription' => '2026-09-10',
            'montant_total' => 900,
        ]);
        InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => 'Frais de Septembre',
            'montant_initial' => 900, 'montant' => 900, 'date_echeance' => '2026-09-05',
            'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);

        return $inscription;
    }

    private function archiver(Inscription $inscription, array $payload = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->guichet->user)->post(route('backoffice.inscriptions.archive', $inscription), $payload + [
            'motif' => 'Voyage à l’étranger',
            'date_retour' => now()->addMonth()->toDateString(),
        ]);
    }

    public function test_archiving_requires_a_reason_and_a_future_return_date(): void
    {
        $inscription = $this->inscription();

        $this->actingAs($this->guichet->user)
            ->post(route('backoffice.inscriptions.archive', $inscription), ['motif' => '', 'date_retour' => ''])
            ->assertSessionHasErrors(['motif', 'date_retour']);

        $this->archiver($inscription, ['date_retour' => now()->toDateString()])
            ->assertSessionHasErrors(['date_retour']);

        $this->assertSame(Inscription::STATUT_ACTIVE, $inscription->fresh()->statut);
    }

    public function test_archiving_stamps_the_pause_moves_the_row_to_the_archives_tab_and_touches_nothing_else(): void
    {
        $inscription = $this->inscription();
        $autre = $this->inscription();
        $feesAvant = $inscription->fees()->whereNull('masque_le')->count();

        $this->archiver($inscription)->assertSessionHasNoErrors();

        $inscription->refresh();
        $this->assertSame(Inscription::STATUT_ARCHIVEE, $inscription->statut);
        $this->assertSame('Voyage à l’étranger', $inscription->archive_motif);
        $this->assertSame(now()->addMonth()->toDateString(), $inscription->archive_date_retour->toDateString());
        $this->assertNotNull($inscription->archive_le);
        $this->assertSame($this->guichet->user->id, $inscription->archive_par_user_id);
        // Rien d'autre ne bouge : le frais reste visible, aucun motif d'annulation.
        $this->assertSame($feesAvant, $inscription->fees()->whereNull('masque_le')->count());
        $this->assertNull($inscription->motif_annulation);
        $this->assertDatabaseHas('activity_log', ['event' => 'inscription_archived', 'subject_id' => $inscription->id]);

        // Liste courante : le dossier en pause a disparu, l'autre reste ; badge de l'onglet.
        $this->actingAs($this->guichet->user)
            ->get(route('backoffice.inscriptions.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('archivesView', false)
                ->where('canArchive', true)
                ->has('inscriptions.data', 1)
                ->where('inscriptions.data.0.id', $autre->id)
                ->where('tabCounts./backoffice/inscriptions/archivees', 1));

        // Onglet « Inscriptions archivées » : lui seul, avec motif / retour / auteur.
        $this->actingAs($this->guichet->user)
            ->get(route('backoffice.inscriptions.archivees'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Inscriptions/Index', false)
                ->where('archivesView', true)
                ->has('inscriptions.data', 1)
                ->where('inscriptions.data.0.id', $inscription->id)
                ->where('inscriptions.data.0.statut', 'Archivée')
                ->where('inscriptions.data.0.archiveMotif', 'Voyage à l’étranger')
                ->where('inscriptions.data.0.archiveDateRetour', now()->addMonth()->format('d/m/Y'))
                ->where('inscriptions.data.0.archiveRetourDepasse', false)
                ->where('inscriptions.data.0.archivePar', $this->guichet->user->name));
    }

    public function test_reactivating_puts_the_registration_back_and_clears_the_pause(): void
    {
        $inscription = $this->inscription();
        $this->archiver($inscription)->assertSessionHasNoErrors();

        $this->actingAs($this->guichet->user)
            ->post(route('backoffice.inscriptions.reactivate', $inscription))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('backoffice.inscriptions.archivees'));

        $inscription->refresh();
        $this->assertSame(Inscription::STATUT_ACTIVE, $inscription->statut);
        $this->assertNull($inscription->archive_motif);
        $this->assertNull($inscription->archive_date_retour);
        $this->assertNull($inscription->archive_le);
        $this->assertNull($inscription->archive_par_user_id);
        $this->assertDatabaseHas('activity_log', ['event' => 'inscription_reactivated', 'subject_id' => $inscription->id]);

        $this->actingAs($this->guichet->user)
            ->get(route('backoffice.inscriptions.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('inscriptions.data', 1)
                ->where('inscriptions.data.0.id', $inscription->id)
                ->where('tabCounts./backoffice/inscriptions/archivees', 0));

        // Réactiver un dossier qui n'est pas en pause est refusé, jamais silencieux.
        $this->actingAs($this->guichet->user)
            ->post(route('backoffice.inscriptions.reactivate', $inscription))
            ->assertSessionHasErrors(['archive']);
    }

    public function test_reactivation_never_creates_two_active_registrations_for_one_student_in_a_year(): void
    {
        $student = Student::factory()->create(['etablissement_id' => $this->rabat->id]);
        $pause = $this->inscription($student);
        $this->archiver($pause)->assertSessionHasNoErrors();

        // Un second dossier ouvert pendant la pause (autre groupe, même année).
        $autreGroupe = Group::factory()->create(['etablissement_id' => $this->rabat->id, 'annee_scolaire_id' => $this->annee->id, 'nom' => 'Rabat A2']);
        $this->inscription($student, $autreGroupe);

        $this->actingAs($this->guichet->user)
            ->post(route('backoffice.inscriptions.reactivate', $pause))
            ->assertSessionHasErrors(['archive']);

        $this->assertSame(Inscription::STATUT_ARCHIVEE, $pause->fresh()->statut);
    }

    public function test_only_an_active_registration_can_be_archived(): void
    {
        $annulee = $this->inscription(null, null, Inscription::STATUT_ANNULEE);
        $this->archiver($annulee)->assertSessionHasErrors(['archive']);
        $this->assertSame(Inscription::STATUT_ANNULEE, $annulee->fresh()->statut);

        $deja = $this->inscription();
        $this->archiver($deja)->assertSessionHasNoErrors();
        $this->archiver($deja, ['motif' => 'Autre motif'])->assertSessionHasErrors(['archive']);
        $this->assertSame('Voyage à l’étranger', $deja->fresh()->archive_motif, 'Le premier motif n’est jamais réécrit.');
    }

    public function test_a_past_return_date_is_flagged_as_overdue(): void
    {
        $inscription = $this->inscription();
        $this->archiver($inscription)->assertSessionHasNoErrors();

        Inscription::query()->whereKey($inscription->id)->update(['archive_date_retour' => now()->subDays(3)->toDateString()]);

        $this->actingAs($this->guichet->user)
            ->get(route('backoffice.inscriptions.archivees'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('inscriptions.data.0.archiveRetourDepasse', true));
    }

    public function test_the_dashboard_counts_archived_registrations_apart(): void
    {
        $this->inscription();
        $this->inscription();
        $pause = $this->inscription();
        $this->archiver($pause)->assertSessionHasNoErrors();

        $this->actingAs($this->guichet->user)
            ->get(route('backoffice.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.inscriptionsTotal', 3)
                ->where('stats.inscriptionsActives', 2)
                ->where('stats.inscriptionsArchivees', 1));
    }

    public function test_archiving_needs_the_dedicated_permission_and_stays_within_centre_reach(): void
    {
        $inscription = $this->inscription();
        $payload = ['motif' => 'x', 'date_retour' => now()->addMonth()->toDateString()];

        $sansDroit = User::factory()->create();
        $sansDroit->givePermissionTo(['registrations.view', 'registrations.update']);

        $this->actingAs($sansDroit->fresh())
            ->post(route('backoffice.inscriptions.archive', $inscription), $payload)
            ->assertForbidden();

        $casa = Etablissement::factory()->create(['nom_centre' => 'GLS Casablanca']);
        $ailleurs = Employee::factory()->create(['etablissement_id' => $casa->id]);
        $ailleurs->user->forceFill(['must_change_password' => false])->save();
        $ailleurs->user->givePermissionTo(['registrations.view', 'registrations.archive']);

        $this->actingAs($ailleurs->user->fresh())
            ->post(route('backoffice.inscriptions.archive', $inscription), $payload)
            ->assertForbidden();

        $this->assertSame(Inscription::STATUT_ACTIVE, $inscription->fresh()->statut);
    }

    public function test_the_archives_tab_follows_the_active_centre_and_year(): void
    {
        $casa = Etablissement::factory()->create(['nom_centre' => 'GLS Casablanca']);
        $groupCasa = Group::factory()->create(['etablissement_id' => $casa->id, 'annee_scolaire_id' => $this->annee->id, 'nom' => 'Casa A1']);
        $admin = User::factory()->create();
        $admin->assignRole(Role::SUPER_ADMIN);

        $rabat = $this->inscription();
        $casab = $this->inscription(null, $groupCasa);
        foreach ([$rabat, $casab] as $i) {
            Inscription::query()->whereKey($i->id)->update([
                'statut' => Inscription::STATUT_ARCHIVEE, 'archive_motif' => 'm', 'archive_date_retour' => now()->addWeek()->toDateString(),
            ]);
        }

        $this->actingAs($admin);
        app(CurrentContext::class)->setEtablissement($casa->id);
        app(CurrentContext::class)->setAnneeScolaire($this->annee->id);

        $this->get(route('backoffice.inscriptions.archivees'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('inscriptions.data', 1)
                ->where('inscriptions.data.0.id', $casab->id)
                ->where('tabCounts./backoffice/inscriptions/archivees', 1));
    }
}
