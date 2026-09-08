<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Models\AnneeScolaire;
use App\Models\Caisse;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Frais;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\PendingCommand;
use Tests\TestCase;

/**
 * `paiements:reconcilier` — l'export de l'ancien CRM fait foi.
 *
 * Rejoue les trois réparations manuelles des 07–08/09/2026 (ISMAIL AMARIR,
 * EL ABLAOUI, HAMZA LACHKAR) sur de vrais fichiers .xlsx générés à la volée,
 * et vérifie surtout ce que la commande ne doit JAMAIS faire : toucher un
 * montant, une date, une caisse, un solde, ou écrire dans une année
 * clôturée.
 */
final class ReconcilierPaiementsLegacyTest extends TestCase
{
    use RefreshDatabase;

    private AnneeScolaire $annee;

    private Etablissement $centre;

    private User $user;

    private string $dossier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->annee = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);

        // Le sous-dossier « marrakech » doit correspondre au nom du centre.
        $this->centre = Etablissement::factory()->create(['nom_centre' => 'GLS Marrakech']);

        $this->user = User::factory()->create()->assignRole('super-admin');
        Employee::factory()->create(['user_id' => $this->user->id, 'etablissement_id' => $this->centre->id]);
        $this->user = $this->user->fresh();

        $this->dossier = storage_path('framework/testing/gls-reconcilier-'.uniqid());
        mkdir($this->dossier.'/old data/marrakech', 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dossier.'/old data/marrakech/*') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($this->dossier.'/old data/marrakech');
        @rmdir($this->dossier.'/old data');
        @rmdir($this->dossier);

        parent::tearDown();
    }

    /**
     * Installe l'export de test dans le dossier attendu.
     *
     * La fixture `tests/Fixtures/legacy/liste-paiements-test.xlsx` est un
     * VRAI .xlsx au format exact de l'ancien CRM (deux lignes de titre, puis
     * « N° | Réf. | Élève / Payeur | … »), lu par le même `SheetReader` que
     * l'import. Elle porte les cinq lignes des trois cas réels :
     *
     *   P3266  HAMZA LACHKAR ......  300 DH  Frais d'inscription A1/A2/B1
     *   P3267  HAMZA LACHKAR ......  500 DH  «-» (aucun frais ⇒ avance)
     *   P2868  RAJA EL ABLAOUI ....  1300 DH Frais d'Octobre
     *   P2871  CHAIMA EL ABLAOUI ..  1300 DH Frais d'Octobre
     *   P519   ISMAIL AMARIR ......  300 DH  Frais d'inscription A1/A2/B1
     *
     * Un fichier écrit à la volée serait plus souple, mais le writer XLSX
     * d'OpenSpout échoue sur cette machine (renommage ZIP dans %TEMP%) — et
     * une fixture figée teste de toute façon le format RÉEL plutôt qu'une
     * idée qu'on s'en fait.
     */
    private function installerExport(): string
    {
        $chemin = $this->dossier.'/old data/marrakech/liste-paiements_19_20260824.xlsx';
        copy(base_path('tests/Fixtures/legacy/liste-paiements-test.xlsx'), $chemin);

        return $chemin;
    }

    private function inscriptionAvecFrais(Student $student, array $frais, ?AnneeScolaire $annee = null): Inscription
    {
        $annee ??= $this->annee;

        $group = Group::factory()->create([
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $annee->id,
        ]);

        $inscription = Inscription::create([
            'reference' => 'INS-'.fake()->unique()->numerify('#####'),
            'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $annee->id,
            'statut' => Inscription::STATUT_ACTIVE, 'date_inscription' => '2025-09-15',
            'montant_total' => array_sum($frais),
        ]);

        foreach ($frais as $nom => $montant) {
            InscriptionFee::create([
                'inscription_id' => $inscription->id, 'nom' => $nom,
                'montant_initial' => $montant, 'montant' => $montant,
                'date_echeance' => '2025-10-31', 'statut' => InscriptionFee::STATUT_NON_PAYE,
            ]);
        }

        return $inscription->fresh();
    }

    private function paiement(Student $student, string $legacyRef, float $montant, ?InscriptionFee $fee = null): Encaissement
    {
        /** @var Caisse $caisse */
        $caisse = $this->user->employee->till()->firstOrFail();

        return Encaissement::create([
            'reference' => 'ENC-'.fake()->unique()->numerify('#####'),
            'etablissement_id' => $this->centre->id,
            'student_id' => $student->id,
            'inscription_fee_id' => $fee?->id,
            'caisse_id' => $caisse->id,
            'montant' => $montant,
            'methode' => 'Espèces',
            'date_paiement' => '2026-04-01',
            'agent_id' => $this->user->employee->id,
            'legacy_ref' => $legacyRef,
        ]);
    }

    private function reconcilier(array $options = []): PendingCommand
    {
        return $this->artisan('paiements:reconcilier', array_merge([
            '--dossier' => $this->dossier,
            '--centre' => 'Marrakech',
        ], $options));
    }

    // ---------------------------------------------------------------- //

    public function test_une_base_conforme_ne_produit_aucun_ecart(): void
    {
        $student = Student::factory()->create([
            'etablissement_id' => $this->centre->id, 'prenom' => 'HAMZA', 'nom' => 'LACHKAR',
            'legacy_ref' => 'E812',
        ]);
        $inscription = $this->inscriptionAvecFrais($student, ["Frais d'inscription A1/A2/B1" => 300]);
        $fee = $inscription->fees()->first();
        $this->paiement($student, 'P3266', 300, $fee);

        // P3267 (500 DH, « - ») est l'autre ligne HAMZA de la fixture : déjà
        // une avance en base, donc conforme elle aussi.
        $this->paiement($student, 'P3267', 500);

        $this->installerExport();

        // Ciblé sur HAMZA : les autres lignes de la fixture concernent
        // d'autres étudiants, absents de cette base de test.
        $this->reconcilier(['--etudiant' => 'E812'])
            ->expectsOutputToContain('Aucun écart')
            ->assertSuccessful();
    }

    /** Cas EL ABLAOUI : le fichier nomme un frais, la base en montre un autre. */
    public function test_un_paiement_mal_rattache_est_repointe_sur_le_frais_du_fichier(): void
    {
        Frais::create(['nom' => "Frais d'Octobre", 'montant_defaut' => 1300, 'statut' => 'Actif']);

        $student = Student::factory()->create([
            'etablissement_id' => $this->centre->id, 'prenom' => 'RAJA', 'nom' => 'EL ABLAOUI',
        ]);
        $inscription = $this->inscriptionAvecFrais($student, [
            "Frais d'inscription B2" => 200,
            "Frais d'Octobre" => 1300,
        ]);
        $b2 = $inscription->fees()->where('nom', "Frais d'inscription B2")->first();
        $octobre = $inscription->fees()->where('nom', "Frais d'Octobre")->first();

        $paiement = $this->paiement($student, 'P2868', 1300, $b2);

        $this->installerExport();

        $this->reconcilier(['--apply' => true])->assertSuccessful();

        // L'argent est sur Octobre, plus sur B2.
        $this->assertSame(1300.0, round($octobre->fresh()->montantPaye(), 2));
        $this->assertSame(0.0, round($b2->fresh()->montantPaye(), 2));

        // Le paiement d'origine n'a pas été supprimé.
        $this->assertDatabaseHas('encaissements', ['id' => $paiement->id, 'legacy_ref' => 'P2868']);
    }

    /** Cas HAMZA : le fichier ne nomme AUCUN frais (« - ») ⇒ l'argent redevient une avance. */
    public function test_un_paiement_sans_frais_au_fichier_redevient_une_avance(): void
    {
        $student = Student::factory()->create([
            'etablissement_id' => $this->centre->id, 'prenom' => 'HAMZA', 'nom' => 'LACHKAR',
        ]);
        $inscription = $this->inscriptionAvecFrais($student, ["Frais d'inscription B2" => 200]);
        $fee = $inscription->fees()->first();
        $paiement = $this->paiement($student, 'P3267', 200, $fee);

        $this->installerExport();

        $this->reconcilier(['--apply' => true])->assertSuccessful();

        $this->assertNull($paiement->fresh()->inscription_fee_id);
        $this->assertTrue($paiement->fresh()->isAvance());
        $this->assertSame(0.0, round($fee->fresh()->montantPaye(), 2));
    }

    /** Cas ISMAIL : la ligne du fichier n'a jamais été importée — signalée, jamais inventée. */
    public function test_un_paiement_absent_de_la_base_est_signale_sans_etre_cree(): void
    {
        Student::factory()->create([
            'etablissement_id' => $this->centre->id, 'prenom' => 'ISMAIL', 'nom' => 'AMARIR',
        ]);

        $this->installerExport();

        $this->reconcilier(['--apply' => true])
            ->expectsOutputToContain('ABSENT DE LA BASE')
            ->assertSuccessful();

        // Rien n'a été créé : la commande RÉCONCILIE, elle n'importe pas.
        $this->assertDatabaseMissing('encaissements', ['legacy_ref' => 'P519']);
    }

    /** L'invariant central : une année clôturée est refusée, jamais rouverte. */
    public function test_une_annee_cloturee_est_refusee_et_rien_nest_ecrit(): void
    {
        Frais::create(['nom' => "Frais d'Octobre", 'montant_defaut' => 1300, 'statut' => 'Actif']);

        $student = Student::factory()->create([
            'etablissement_id' => $this->centre->id, 'prenom' => 'HAMZA', 'nom' => 'LACHKAR',
        ]);
        $inscription = $this->inscriptionAvecFrais($student, [
            "Frais d'inscription B2" => 200,
            "Frais d'Octobre" => 1300,
        ]);
        $b2 = $inscription->fees()->where('nom', "Frais d'inscription B2")->first();
        $this->paiement($student, 'P2868', 1300, $b2);

        $this->annee->update(['cloturee' => true]);

        $this->installerExport();

        $this->reconcilier(['--apply' => true])
            ->expectsOutputToContain('CLÔTURÉE')
            ->assertSuccessful();

        // L'argent n'a pas bougé, et l'année est TOUJOURS clôturée.
        $this->assertSame(1300.0, round($b2->fresh()->montantPaye(), 2));
        $this->assertTrue($this->annee->fresh()->cloturee);
    }

    /** Le mode par défaut ne doit rien écrire, même sur un écart réel. */
    public function test_la_simulation_ne_modifie_rien(): void
    {
        Frais::create(['nom' => "Frais d'Octobre", 'montant_defaut' => 1300, 'statut' => 'Actif']);

        $student = Student::factory()->create([
            'etablissement_id' => $this->centre->id, 'prenom' => 'RAJA', 'nom' => 'EL ABLAOUI',
        ]);
        $inscription = $this->inscriptionAvecFrais($student, [
            "Frais d'inscription B2" => 200,
            "Frais d'Octobre" => 1300,
        ]);
        $b2 = $inscription->fees()->where('nom', "Frais d'inscription B2")->first();
        $this->paiement($student, 'P2868', 1300, $b2);

        $this->installerExport();

        $this->reconcilier()->expectsOutputToContain('SIMULATION')->assertSuccessful();

        $this->assertSame(1300.0, round($b2->fresh()->montantPaye(), 2));
    }

    /**
     * La garantie qui compte le plus : la commande NE TOUCHE PAS l'argent
     * lui-même — ni le solde de caisse, ni la date, ni le montant, ni la
     * caisse. Elle ne change QUE le frais rattaché.
     */
    public function test_ni_le_solde_ni_la_date_ni_la_caisse_ne_bougent(): void
    {
        Frais::create(['nom' => "Frais d'Octobre", 'montant_defaut' => 1300, 'statut' => 'Actif']);

        $student = Student::factory()->create([
            'etablissement_id' => $this->centre->id, 'prenom' => 'RAJA', 'nom' => 'EL ABLAOUI',
        ]);
        $inscription = $this->inscriptionAvecFrais($student, [
            "Frais d'inscription B2" => 200,
            "Frais d'Octobre" => 1300,
        ]);
        $b2 = $inscription->fees()->where('nom', "Frais d'inscription B2")->first();
        $paiement = $this->paiement($student, 'P2868', 1300, $b2);

        $caisse = Caisse::findOrFail($paiement->caisse_id);
        $soldeAvant = (float) $caisse->solde;
        $dateAvant = $paiement->date_paiement->format('Y-m-d');

        $this->installerExport();

        $this->reconcilier(['--apply' => true])->assertSuccessful();

        $apres = $paiement->fresh();

        $this->assertSame($soldeAvant, (float) $caisse->fresh()->solde, 'le solde de caisse a bougé');
        $this->assertSame($dateAvant, $apres->date_paiement->format('Y-m-d'), 'la date de paiement a changé');
        $this->assertSame(1300.0, (float) $apres->montant, 'le montant a changé');
        $this->assertSame($caisse->id, $apres->caisse_id, 'la caisse a changé');

        // L'argent réellement reçu est inchangé : 1 300 DH, une seule fois.
        $recu = Encaissement::where('student_id', $student->id)
            ->whereNull('applied_from_encaissement_id')->sum('montant');
        $this->assertSame(1300.0, (float) $recu);
    }

    /** Le filtre --etudiant ne doit toucher personne d'autre. */
    public function test_le_filtre_etudiant_isole_bien_une_seule_personne(): void
    {
        Frais::create(['nom' => "Frais d'Octobre", 'montant_defaut' => 1300, 'statut' => 'Actif']);

        $cible = Student::factory()->create([
            'etablissement_id' => $this->centre->id, 'prenom' => 'RAJA', 'nom' => 'EL ABLAOUI', 'legacy_ref' => 'E622',
        ]);
        $autre = Student::factory()->create([
            'etablissement_id' => $this->centre->id, 'prenom' => 'CHAIMA', 'nom' => 'EL ABLAOUI', 'legacy_ref' => 'E623',
        ]);

        foreach ([$cible, $autre] as $student) {
            $inscription = $this->inscriptionAvecFrais($student, [
                "Frais d'inscription B2" => 200,
                "Frais d'Octobre" => 1300,
            ]);
            $b2 = $inscription->fees()->where('nom', "Frais d'inscription B2")->first();
            $this->paiement($student, $student->id === $cible->id ? 'P2868' : 'P2871', 1300, $b2);
        }

        $this->installerExport();

        $this->reconcilier(['--apply' => true, '--etudiant' => 'E622'])->assertSuccessful();

        $b2Cible = InscriptionFee::whereHas('inscription', fn ($q) => $q->where('student_id', $cible->id))
            ->where('nom', "Frais d'inscription B2")->firstOrFail();
        $b2Autre = InscriptionFee::whereHas('inscription', fn ($q) => $q->where('student_id', $autre->id))
            ->where('nom', "Frais d'inscription B2")->firstOrFail();

        $this->assertSame(0.0, round($b2Cible->montantPaye(), 2), 'la cible aurait dû être corrigée');
        $this->assertSame(1300.0, round($b2Autre->montantPaye(), 2), 'un autre étudiant a été modifié');
    }
}
