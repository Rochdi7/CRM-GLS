<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Reports;

use App\Models\AnneeScolaire;
use App\Models\Caisse;
use App\Models\Depense;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\GroupEnseignant;
use App\Models\Inscription;
use App\Models\Presence;
use App\Models\Seance;
use App\Models\Student;
use App\Models\TypeDepense;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Gestion des rapports — « Calcul paiement prof » (onglet Employés) : les
 * paiements prof ENREGISTRÉS, imprimables en PDF et Excel (refonte du
 * 23/10/2026 : filtres Groupe / Enseignant / Paiement, sans fenêtre de dates).
 *
 * Ce que ces tests protègent :
 *  - sans paiement choisi le document LISTE les paiements qui passent les
 *    filtres ; un paiement choisi, il le DÉTAILLE avec le MÊME calcul que
 *    l'écran (paliers de présences, taux de la fiche employé, période et
 *    ajustements STOCKÉS) — jamais une règle recopiée ;
 *  - `reports.view` (tous les rôles) ne suffit pas : il faut
 *    `prof-payments.calculate`, sinon le rapport serait une porte dérobée
 *    vers la paie des enseignants ;
 *  - rien n'est écrit : un calcul n'est pas un paiement.
 */
final class RapportPaiementProfTest extends TestCase
{
    use RefreshDatabase;

    private const CLE = 'calcul-paiement-prof';

    private AnneeScolaire $annee;

    private Etablissement $centre;

    private Group $group;

    private Employee $prof;

    private TypeDepense $type;

    private Employee $agent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->annee = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->centre = Etablissement::factory()->create();

        // Système GLS à 400 DH / étudiant : une semaine = 100 DH.
        $this->prof = Employee::factory()->create([
            'categorie' => Employee::CATEGORIE_ENSEIGNANT,
            'etablissement_id' => $this->centre->id,
            'mode_paiement_prof' => Employee::MODE_PAIEMENT_GLS,
            'montant_par_etudiant_prof' => 400,
        ]);

        $this->group = Group::factory()->create([
            'statut' => Group::STATUT_EN_FORMATION,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'enseignant_id' => $this->prof->id,
            'date_debut_formation' => '2025-09-01',
        ]);
        GroupEnseignant::create([
            'group_id' => $this->group->id,
            'enseignant_id' => $this->prof->id,
            'date_debut' => '2025-09-01',
            'statut' => 'Actif',
        ]);

        $this->type = TypeDepense::create([
            'nom' => TypeDepense::SYSTEM_PAIEMENT_PROF, 'is_system' => true, 'statut' => TypeDepense::STATUT_ACTIF,
        ]);
        $this->agent = Employee::factory()->create(['etablissement_id' => $this->centre->id]);
    }

    /** Un paiement prof ENREGISTRÉ pour septembre, sur ce groupe et ce prof. */
    private function paiement(array $extra = []): Depense
    {
        return Depense::query()->create([
            'reference' => 'DEP-'.str_pad((string) (Depense::query()->count() + 1), 3, '0', STR_PAD_LEFT),
            'type_depense_id' => $this->type->id,
            'caisse_id' => $this->agent->till()->first()->id,
            'group_id' => $this->group->id,
            'enseignant_id' => $this->prof->id,
            'montant' => '600.00',
            'statut' => Depense::STATUT_APPROUVEE,
            'date_depense' => '2025-09-30',
            'periode_debut' => '2025-09-01',
            'periode_fin' => '2025-09-30',
            'description' => 'Paiement prof',
            'agent_id' => $this->agent->id,
            ...$extra,
        ]);
    }

    private function user(string ...$permissions): User
    {
        $user = User::factory()->create();

        foreach ([...$permissions, 'centers.access-all'] as $p) {
            $user->givePermissionTo($p);
        }

        return $user->fresh();
    }

    private function inscrit(string $prenom): Student
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id, 'prenom' => $prenom]);

        Inscription::create([
            'reference' => 'INS-RPP-'.$student->id,
            'student_id' => $student->id,
            'group_id' => $this->group->id,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'statut' => Inscription::STATUT_ACTIVE,
            'date_inscription' => '2025-08-20',
        ]);

        return $student;
    }

    /**
     * Quatorze séances en septembre : $complet présent à toutes (mois
     * complet = 400), $partiel à 7 seulement (2 semaines = 200).
     */
    private function septembre(Student $complet, Student $partiel): void
    {
        for ($jour = 1; $jour <= 14; $jour++) {
            $seance = Seance::create([
                'group_id' => $this->group->id,
                'date_seance' => sprintf('2025-09-%02d', $jour),
                'heure_debut' => '18:00',
                'heure_fin' => '20:00',
                'enseignant_id' => $this->prof->id,
                'etablissement_id' => $this->centre->id,
                'annee_scolaire_id' => $this->annee->id,
                'statut' => Seance::STATUT_EFFECTUEE,
            ]);

            Presence::create(['seance_id' => $seance->id, 'student_id' => $complet->id, 'statut' => Presence::STATUT_PRESENT]);
            Presence::create([
                'seance_id' => $seance->id,
                'student_id' => $partiel->id,
                'statut' => $jour <= 7 ? Presence::STATUT_PRESENT : Presence::STATUT_ABSENT,
            ]);
        }
    }

    /** @return array<string, mixed> */
    private function window(array $extra = []): array
    {
        return [
            'rapport' => self::CLE,
            'groupFilter' => (string) $this->group->id,
            // Le marqueur « vidé » : sans lui l'URL nue recevrait la fenêtre
            // par défaut — que ce rapport ne dessine pas.
            'dateFrom' => '-',
            'dateTo' => '-',
            ...$extra,
        ];
    }

    // ─────────────────────────── Accès ───────────────────────────

    public function test_the_report_is_not_offered_without_the_calculation_permission(): void
    {
        $this->actingAs($this->user('reports.view'))
            ->get(route('backoffice.rapports.index', $this->window()))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                // Le rapport retombe sur le défaut, et l'onglet Employés
                // n'en propose aucun.
                ->where('filters.rapport', 'liste-inscriptions')
                ->where('onglets.6.key', 'employes')
                ->where('onglets.6.rapports', []));
    }

    public function test_the_downloads_never_print_the_calculation_without_the_permission(): void
    {
        $this->septembre($this->inscrit('Amal'), $this->inscrit('Badr'));
        $user = $this->user('reports.view');

        $this->actingAs($user)->get(route('backoffice.rapports.excel', $this->window()))
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename=liste-inscriptions.xlsx');
    }

    // ─────────────────────── La liste des paiements ───────────────────────

    public function test_it_lists_the_recorded_payments_without_a_date_window(): void
    {
        $approuve = $this->paiement();
        $this->paiement(['statut' => Depense::STATUT_EN_ATTENTE, 'montant' => '250.00', 'periode_debut' => '2025-10-01', 'periode_fin' => '2025-10-31']);
        // Refusée : jamais listée, l'argent n'est jamais sorti.
        $this->paiement(['statut' => Depense::STATUT_REFUSEE, 'montant' => '999.00']);

        $this->actingAs($this->user('reports.view', 'prof-payments.calculate'))
            ->get(route('backoffice.rapports.index', $this->window(['groupFilter' => ''])))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.rapport', self::CLE)
                ->where('filtresVisibles', ['groupFilter', 'enseignantFilter', 'paiementFilter'])
                ->where('fenetreDatesVisible', false)
                ->where('paiementProfDetail', false)
                ->where('nombreLignes', 2)
                // Seul l'approuvé entre dans le total.
                ->where('montantTotal', '600,00 DH')
                ->where('avertissementRapport', '')
                ->where('enseignantOptions.0.value', (string) $this->prof->id)
                ->has('paiementOptions', 2)
                ->where('paiementOptions.1.value', (string) $approuve->id));
    }

    public function test_a_teacher_alone_lists_all_his_groups_and_a_group_narrows_to_it(): void
    {
        $this->paiement();
        $autre = Group::factory()->create([
            'statut' => Group::STATUT_EN_FORMATION,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'enseignant_id' => $this->prof->id,
            'date_debut_formation' => '2025-09-01',
        ]);
        $this->paiement(['group_id' => $autre->id, 'montant' => '300.00']);
        // Un autre prof : hors du filtre Enseignant.
        $collegue = Employee::factory()->create(['categorie' => Employee::CATEGORIE_ENSEIGNANT, 'etablissement_id' => $this->centre->id]);
        $this->paiement(['enseignant_id' => $collegue->id, 'montant' => '100.00']);

        $user = $this->user('reports.view', 'prof-payments.calculate');

        $this->actingAs($user)
            ->get(route('backoffice.rapports.index', $this->window(['groupFilter' => '', 'enseignantFilter' => (string) $this->prof->id])))
            ->assertInertia(fn (Assert $page) => $page
                ->where('nombreLignes', 2)
                ->where('montantTotal', '900,00 DH'));

        $this->actingAs($user)
            ->get(route('backoffice.rapports.index', $this->window(['enseignantFilter' => (string) $this->prof->id])))
            ->assertInertia(fn (Assert $page) => $page
                ->where('nombreLignes', 1)
                ->where('montantTotal', '600,00 DH'));

        $this->actingAs($user)
            ->get(route('backoffice.rapports.index', $this->window()))
            ->assertInertia(fn (Assert $page) => $page
                ->where('nombreLignes', 2)
                ->has('enseignantOptions', 2));
    }

    public function test_without_any_payment_it_says_so(): void
    {
        $this->actingAs($this->user('reports.view', 'prof-payments.calculate'))
            ->get(route('backoffice.rapports.index', $this->window()))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('nombreLignes', 0)
                ->where('avertissementRapport', 'Aucun paiement prof ne correspond à ces filtres.'));
    }

    // ─────────────────────── Le détail d'un paiement ───────────────────────

    public function test_a_chosen_payment_prints_the_same_calculation_as_the_screen(): void
    {
        $this->septembre($this->inscrit('Amal'), $this->inscrit('Badr'));
        // Net 500 après 100 de CNSS : le pied imprime le NET stocké, pas le
        // total recalculé (600).
        $paiement = $this->paiement(['montant' => '500.00', 'cnss_montant' => '100.00']);

        $this->actingAs($this->user('reports.view', 'prof-payments.calculate'))
            ->get(route('backoffice.rapports.index', $this->window(['paiementFilter' => (string) $paiement->id])))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('paiementProfDetail', true)
                ->where('nombreLignes', 2)
                ->where('montantTotal', '500,00 DH')
                ->where('avertissementRapport', '')
                ->where('resumeRapport.Paiement', $paiement->reference)
                ->where('resumeRapport.Enseignant', $this->prof->nomComplet())
                ->where('resumeRapport.Cotisation CNSS retenue', '100,00 DH')
                // 400 (mois complet) + 200 (7 présences = 2 semaines).
                ->where('resumeRapport.Total calculé', '600,00 DH')
                ->where('resumeRapport.Séances effectuées', '14'));
    }

    public function test_a_payment_outside_the_scope_is_never_detailed(): void
    {
        $ailleurs = Etablissement::factory()->create();
        $groupe = Group::factory()->create([
            'statut' => Group::STATUT_EN_FORMATION,
            'etablissement_id' => $ailleurs->id,
            'annee_scolaire_id' => $this->annee->id,
            'enseignant_id' => $this->prof->id,
        ]);
        $paiement = $this->paiement(['group_id' => $groupe->id]);

        // Un utilisateur borné à SON centre : l'id forgé ne détaille rien.
        $user = User::factory()->create();
        foreach (['reports.view', 'prof-payments.calculate'] as $p) {
            $user->givePermissionTo($p);
        }
        $employe = Employee::factory()->create(['etablissement_id' => $this->centre->id, 'user_id' => $user->id]);
        $employe->syncEtablissements([$this->centre->id], $this->centre->id);

        $this->actingAs($user->fresh())
            ->get(route('backoffice.rapports.index', $this->window(['groupFilter' => '', 'paiementFilter' => (string) $paiement->id])))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('paiementProfDetail', false)
                ->where('nombreLignes', 0));
    }

    // ─────────────────────── Les téléchargements ───────────────────────

    public function test_it_downloads_a_pdf_and_an_excel_workbook_and_writes_nothing(): void
    {
        $this->septembre($this->inscrit('Amal'), $this->inscrit('Badr'));
        $paiement = $this->paiement();
        $user = $this->user('reports.view', 'prof-payments.calculate');

        // La liste…
        $this->actingAs($user)->get(route('backoffice.rapports.pdf', $this->window()))->assertOk();
        $this->actingAs($user)->get(route('backoffice.rapports.excel', $this->window()))->assertOk();

        // …et le détail d'un paiement.
        $pdf = $this->actingAs($user)->get(route('backoffice.rapports.pdf', $this->window(['paiementFilter' => (string) $paiement->id])));
        $pdf->assertOk();
        $this->assertSame('application/pdf', $pdf->headers->get('Content-Type'));
        $this->assertStringContainsString('calcul-paiement-prof', (string) $pdf->headers->get('Content-Disposition'));

        $excel = $this->actingAs($user)->get(route('backoffice.rapports.excel', $this->window(['paiementFilter' => (string) $paiement->id])));
        $excel->assertOk();
        $this->assertStringContainsString('calcul-paiement-prof', (string) $excel->headers->get('Content-Disposition'));

        // Imprimer n'écrit rien.
        $this->assertSame(1, Depense::query()->count());
        $this->assertSame('600.00', $paiement->fresh()->montant);
    }

    public function test_an_empty_list_still_produces_a_document(): void
    {
        $this->actingAs($this->user('reports.view', 'prof-payments.calculate'))
            ->get(route('backoffice.rapports.pdf', $this->window(['groupFilter' => ''])))
            ->assertOk();
    }
}
