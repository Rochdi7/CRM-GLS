<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Models\AnneeScolaire;
use App\Models\Caisse;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * L'agent d'un encaissement — qui a le droit d'y figurer, et comment le
 * réparer quand l'import s'est trompé.
 *
 * Audit 09/09/2026 : la table « Opérateur → Employé » de l'import proposait
 * TOUS les employés du centre, donc un enseignant pouvait être enregistré
 * comme agent encaisseur. 3 166 paiements importés (3 100 540 DH) l'étaient,
 * dont 68 sur une enseignante SANS login qui ne s'est jamais connectée.
 */
final class AgentEncaisseurTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $centre;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);

        $this->centre = Etablissement::factory()->create();
        $this->user = User::factory()->create()->assignRole('super-admin');
        Employee::factory()->create([
            'user_id' => $this->user->id,
            'etablissement_id' => $this->centre->id,
            'categorie' => Employee::CATEGORIE_DIRECTEUR,
        ]);
        $this->user = $this->user->fresh();
    }

    private function employe(string $categorie): Employee
    {
        return Employee::factory()->create([
            'etablissement_id' => $this->centre->id,
            'categorie' => $categorie,
        ]);
    }

    private function paiement(Employee $agent, float $montant, ?string $legacyRef = null): Encaissement
    {
        /** @var Caisse $caisse */
        $caisse = $this->user->employee->till()->firstOrFail();

        return Encaissement::create([
            'reference' => 'ENC-'.fake()->unique()->numerify('#####'),
            'etablissement_id' => $this->centre->id,
            'student_id' => Student::factory()->create(['etablissement_id' => $this->centre->id])->id,
            'inscription_fee_id' => null,
            'caisse_id' => $caisse->id,
            'montant' => $montant,
            'methode' => 'Espèces',
            'date_paiement' => '2026-04-01',
            'agent_id' => $agent->id,
            'legacy_ref' => $legacyRef,
        ]);
    }

    // ------------------------------------------------------------------ //
    // Prévention : qui peut être proposé comme agent
    // ------------------------------------------------------------------ //

    public function test_un_enseignant_nest_pas_un_agent_encaisseur(): void
    {
        $enseignant = $this->employe(Employee::CATEGORIE_ENSEIGNANT);
        $autre = $this->employe(Employee::CATEGORIE_AUTRE);
        $comptable = $this->employe(Employee::CATEGORIE_COMPTABLE);

        $ids = Employee::query()->canCollectPayments()->pluck('id');

        $this->assertNotContains($enseignant->id, $ids, 'un enseignant ne doit pas être proposé');
        $this->assertNotContains($autre->id, $ids, '« Autre » ne doit pas être proposé');
        $this->assertContains($comptable->id, $ids, 'un comptable doit rester proposable');
    }

    /** Le dropdown n'est qu'un confort : le serveur doit refuser aussi. */
    public function test_le_mapping_dimport_refuse_un_enseignant(): void
    {
        $enseignant = $this->employe(Employee::CATEGORIE_ENSEIGNANT);

        // Un vrai .xlsx : la requête exige un fichier AVANT de valider le
        // mapping, donc un POST nu ne prouverait rien du garde-fou testé ici.
        $fichier = new UploadedFile(
            base_path('tests/Fixtures/legacy/liste-paiements-test.xlsx'),
            'liste-paiements.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );

        // `post`, pas `postJson` : un upload passe en multipart.
        $this->actingAs($this->user)
            ->post('/backoffice/import/encaissements/analyze', [
                'file' => $fichier,
                'etablissement_id' => $this->centre->id,
                'operateur_mapping' => [
                    ['label' => 'prof', 'employee_id' => $enseignant->id],
                ],
            ])
            ->assertSessionHasErrors('operateur_mapping.0.employee_id');
    }

    // ------------------------------------------------------------------ //
    // Réparation : la commande
    // ------------------------------------------------------------------ //

    public function test_la_commande_reattribue_les_encaissements(): void
    {
        $enseignant = $this->employe(Employee::CATEGORIE_ENSEIGNANT);
        $caissiere = $this->employe(Employee::CATEGORIE_COMPTABLE);

        $importe = $this->paiement($enseignant, 300, 'P4420');
        $saisi = $this->paiement($enseignant, 200);

        $this->artisan('encaissements:reattribuer-agent', [
            '--de' => $enseignant->id,
            '--vers' => $caissiere->id,
        ])->assertSuccessful();

        $this->assertSame($caissiere->id, $importe->fresh()->agent_id);
        $this->assertSame($caissiere->id, $saisi->fresh()->agent_id);
    }

    public function test_le_dry_run_ne_modifie_rien(): void
    {
        $enseignant = $this->employe(Employee::CATEGORIE_ENSEIGNANT);
        $caissiere = $this->employe(Employee::CATEGORIE_COMPTABLE);
        $paiement = $this->paiement($enseignant, 300, 'P4420');

        $this->artisan('encaissements:reattribuer-agent', [
            '--de' => $enseignant->id,
            '--vers' => $caissiere->id,
            '--dry-run' => true,
        ])->assertSuccessful();

        $this->assertSame($enseignant->id, $paiement->fresh()->agent_id);
    }

    public function test_importes_seulement_laisse_les_saisies(): void
    {
        $enseignant = $this->employe(Employee::CATEGORIE_ENSEIGNANT);
        $caissiere = $this->employe(Employee::CATEGORIE_COMPTABLE);

        $importe = $this->paiement($enseignant, 300, 'P4420');
        $saisi = $this->paiement($enseignant, 200);

        $this->artisan('encaissements:reattribuer-agent', [
            '--de' => $enseignant->id,
            '--vers' => $caissiere->id,
            '--importes-seulement' => true,
        ])->assertSuccessful();

        $this->assertSame($caissiere->id, $importe->fresh()->agent_id);
        $this->assertSame($enseignant->id, $saisi->fresh()->agent_id, 'la saisie ne devait pas bouger');
    }

    /** Réparer vers un autre non-encaisseur ne ferait que déplacer le problème. */
    public function test_la_commande_refuse_un_destinataire_non_encaisseur(): void
    {
        $enseignant = $this->employe(Employee::CATEGORIE_ENSEIGNANT);
        $autre = $this->employe(Employee::CATEGORIE_AUTRE);
        $paiement = $this->paiement($enseignant, 300, 'P4420');

        $this->artisan('encaissements:reattribuer-agent', [
            '--de' => $enseignant->id,
            '--vers' => $autre->id,
        ])->assertFailed();

        $this->assertSame($enseignant->id, $paiement->fresh()->agent_id);
    }

    /** L'audit est en LECTURE SEULE : il nomme les cas, il n'en corrige aucun. */
    public function test_l_audit_liste_sans_rien_modifier(): void
    {
        $enseignant = $this->employe(Employee::CATEGORIE_ENSEIGNANT);
        $comptable = $this->employe(Employee::CATEGORIE_COMPTABLE);

        $suspect = $this->paiement($enseignant, 300, 'P4420');
        $sain = $this->paiement($comptable, 500, 'P4421');

        $this->artisan('encaissements:reattribuer-agent', ['--auditer' => true])
            ->expectsOutputToContain($enseignant->nom)
            ->assertSuccessful();

        // Rien n'a bougé — ni le cas signalé, ni le cas sain.
        $this->assertSame($enseignant->id, $suspect->fresh()->agent_id);
        $this->assertSame($comptable->id, $sain->fresh()->agent_id);
    }

    /**
     * LA garantie : réattribuer un agent ne déplace pas un dirham.
     */
    public function test_reattribuer_ne_touche_ni_largent_ni_la_caisse(): void
    {
        $enseignant = $this->employe(Employee::CATEGORIE_ENSEIGNANT);
        $caissiere = $this->employe(Employee::CATEGORIE_COMPTABLE);
        $paiement = $this->paiement($enseignant, 1300, 'P4420');

        $caisse = Caisse::findOrFail($paiement->caisse_id);
        $soldeAvant = (float) $caisse->solde;
        $dateAvant = $paiement->date_paiement->format('Y-m-d');

        $this->artisan('encaissements:reattribuer-agent', [
            '--de' => $enseignant->id,
            '--vers' => $caissiere->id,
        ])->assertSuccessful();

        $apres = $paiement->fresh();

        $this->assertSame($soldeAvant, (float) $caisse->fresh()->solde, 'le solde de caisse a bougé');
        $this->assertSame(1300.0, (float) $apres->montant, 'le montant a changé');
        $this->assertSame($dateAvant, $apres->date_paiement->format('Y-m-d'), 'la date a changé');
        $this->assertSame($caisse->id, $apres->caisse_id, 'la caisse a changé');
        $this->assertSame('P4420', $apres->legacy_ref, 'la référence legacy a changé');
    }

    /** Le changement d'agent doit rester traçable. */
    public function test_la_reattribution_est_journalisee(): void
    {
        $enseignant = $this->employe(Employee::CATEGORIE_ENSEIGNANT);
        $caissiere = $this->employe(Employee::CATEGORIE_COMPTABLE);
        $paiement = $this->paiement($enseignant, 300, 'P4420');

        $this->artisan('encaissements:reattribuer-agent', [
            '--de' => $enseignant->id,
            '--vers' => $caissiere->id,
        ])->assertSuccessful();

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Encaissement::class,
            'subject_id' => $paiement->id,
            'event' => 'updated',
        ]);
    }
}
