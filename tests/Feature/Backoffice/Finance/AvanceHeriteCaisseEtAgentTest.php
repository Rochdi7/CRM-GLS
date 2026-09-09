<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Domain\Payments\Actions\AppliquerAvance;
use App\Domain\Payments\Actions\ConvertirEncaissementsEnAvance;
use App\Models\AnneeScolaire;
use App\Models\Caisse;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Appliquer une avance n'encaisse RIEN : l'argent est entré une seule fois,
 * dans UNE caisse, à UNE date, sous la responsabilité d'UN agent. Rattacher
 * cet argent à un frais ne fait que le ré-allouer.
 *
 * La ligne d'application hérite donc de `caisse_id`, `agent_id` et
 * `date_paiement` de l'AVANCE — jamais de l'employé connecté ni de la date
 * du jour.
 *
 * Signalé le 09/09/2026 : la caisse de Mouna Zakri affichait 200 DH pour un
 * paiement de mai 2026 encaissé par quelqu'un d'autre. Le diagnostic a
 * montré que ce n'était PAS une application d'avance (c'était une saisie
 * neuve), mais rien en test ne garantissait la règle — d'où ce fichier.
 *
 * Conséquence pratique pour les données importées : l'ancien CRM a été
 * chargé sur la caisse de Mohamed Rafik, donc toute avance importée qu'on
 * applique — ou ré-applique — reste sur SA caisse, quel que soit l'agent qui
 * fait le geste aujourd'hui.
 */
final class AvanceHeriteCaisseEtAgentTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $centre;

    private Employee $encaisseur;

    private Employee $autreAgent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);

        $this->centre = Etablissement::factory()->create();

        // Celui qui a REÇU l'argent à l'origine.
        $this->encaisseur = Employee::factory()->create([
            'etablissement_id' => $this->centre->id,
            'categorie' => Employee::CATEGORIE_DIRECTEUR,
        ]);

        // Celui qui applique l'avance plus tard — sa caisse ne doit pas bouger.
        $user = User::factory()->create()->assignRole('super-admin');
        $this->autreAgent = Employee::factory()->create([
            'user_id' => $user->id,
            'etablissement_id' => $this->centre->id,
            'categorie' => Employee::CATEGORIE_ASSISTANTE_ADMINISTRATIVE,
        ]);
    }

    private function feeFor(Student $student, string $nom, float $montant): InscriptionFee
    {
        $group = Group::factory()->create([
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => AnneeScolaire::query()->value('id'),
        ]);

        $inscription = Inscription::create([
            'reference' => 'INS-'.fake()->unique()->numerify('#####'),
            'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => AnneeScolaire::query()->value('id'),
            'statut' => Inscription::STATUT_ACTIVE, 'date_inscription' => '2025-09-15',
            'montant_total' => $montant,
        ]);

        return InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => $nom,
            'montant_initial' => $montant, 'montant' => $montant,
            'date_echeance' => '2025-10-31', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);
    }

    /** Une avance encaissée par $encaisseur, dans SA caisse, à une date passée. */
    private function avancePour(Student $student, float $montant, ?string $legacySource = null): Encaissement
    {
        /** @var Caisse $caisse */
        $caisse = $this->encaisseur->till()->firstOrFail();

        return Encaissement::create([
            'reference' => 'ENC-'.fake()->unique()->numerify('#####'),
            'etablissement_id' => $this->centre->id,
            'student_id' => $student->id,
            'inscription_fee_id' => null,
            'caisse_id' => $caisse->id,
            'montant' => $montant,
            'methode' => 'Espèces',
            'date_paiement' => '2026-05-05',
            'agent_id' => $this->encaisseur->id,
            'legacy_source' => $legacySource,
        ]);
    }

    // ------------------------------------------------------------------ //

    public function test_l_application_herite_de_la_caisse_de_l_agent_et_de_la_date_de_l_avance(): void
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $fee = $this->feeFor($student, 'Frais de Mai', 200);
        $avance = $this->avancePour($student, 200);

        $caisseOrigine = (int) $avance->caisse_id;
        $caisseAutre = (int) $this->autreAgent->till()->firstOrFail()->id;
        $soldeAutreAvant = (float) Caisse::findOrFail($caisseAutre)->solde;

        // C'est l'AUTRE agent qui fait le geste.
        $this->actingAs($this->autreAgent->user);
        $application = app(AppliquerAvance::class)->handle($avance, $fee, 200.0);

        $this->assertSame($caisseOrigine, (int) $application->caisse_id, 'la caisse a changé');
        $this->assertSame($this->encaisseur->id, (int) $application->agent_id, "l'agent a changé");
        $this->assertSame('2026-05-05', $application->date_paiement->format('Y-m-d'), 'la date a changé');

        // La caisse de celui qui applique ne bouge pas d'un dirham.
        $this->assertSame($soldeAutreAvant, (float) Caisse::findOrFail($caisseAutre)->fresh()->solde);
    }

    /** Appliquer ne crédite JAMAIS une caisse : l'argent est déjà arrivé. */
    public function test_appliquer_une_avance_ne_credite_aucune_caisse(): void
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $fee = $this->feeFor($student, 'Frais de Mai', 200);
        $avance = $this->avancePour($student, 200);

        $caisse = Caisse::findOrFail($avance->caisse_id);
        $soldeAvant = (float) $caisse->solde;

        app(AppliquerAvance::class)->handle($avance, $fee, 200.0);

        $this->assertSame($soldeAvant, (float) $caisse->fresh()->solde, 'le solde a bougé');
    }

    /**
     * Le cas qui manquait : une ligne d'application DÉTACHÉE est elle-même
     * une avance, et sa ré-application doit garder la caisse d'origine.
     * Sans cela, la deuxième main sur le dossier réécrirait qui a encaissé.
     */
    public function test_une_reapplication_garde_encore_la_caisse_d_origine(): void
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $feeA = $this->feeFor($student, 'Frais A', 200);
        $feeB = $this->feeFor($student, 'Frais B', 200);
        $avance = $this->avancePour($student, 200);

        $caisseOrigine = (int) $avance->caisse_id;

        $application = app(AppliquerAvance::class)->handle($avance, $feeA, 200.0);
        app(ConvertirEncaissementsEnAvance::class)->handle($feeA->inscription, [$application->id]);

        // Ré-appliquée par quelqu'un d'autre, sur un autre frais.
        $this->actingAs($this->autreAgent->user);
        $reapplication = app(AppliquerAvance::class)->handle($application->fresh(), $feeB, 200.0);

        $this->assertSame($caisseOrigine, (int) $reapplication->caisse_id, 'la caisse a changé à la ré-application');
        $this->assertSame($this->encaisseur->id, (int) $reapplication->agent_id, "l'agent a changé à la ré-application");
        $this->assertSame('2026-05-05', $reapplication->date_paiement->format('Y-m-d'), 'la date a changé à la ré-application');
    }

    /**
     * Données importées : l'ancien CRM a été chargé sur la caisse de Mohamed
     * Rafik, donc une avance importée y reste — appliquée ou ré-appliquée —
     * quel que soit l'agent qui fait le geste.
     */
    public function test_une_avance_importee_reste_sur_la_caisse_de_l_import(): void
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $fee = $this->feeFor($student, 'Frais de Mai', 200);
        $avance = $this->avancePour($student, 200, 'ancien-crm');

        $caisseImport = (int) $avance->caisse_id;

        $this->actingAs($this->autreAgent->user);
        $application = app(AppliquerAvance::class)->handle($avance, $fee, 200.0);

        $this->assertSame($caisseImport, (int) $application->caisse_id);
        $this->assertSame($this->encaisseur->id, (int) $application->agent_id);
        $this->assertSame('2026-05-05', $application->date_paiement->format('Y-m-d'));
    }
}
