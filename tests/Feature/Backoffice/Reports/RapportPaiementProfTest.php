<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Reports;

use App\Models\AnneeScolaire;
use App\Models\Depense;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\GroupEnseignant;
use App\Models\Inscription;
use App\Models\Presence;
use App\Models\Seance;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Gestion des rapports — « Calcul paiement prof » (onglet Employés) : le
 * calcul de l'écran « Calcul paiement prof », imprimable en PDF et Excel.
 *
 * Ce que ces tests protègent :
 *  - le document imprime le MÊME calcul que l'écran (paliers de présences,
 *    taux de la fiche employé) — jamais une règle recopiée ;
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
            'dateFrom' => '2025-09-01',
            'dateTo' => '2025-09-30',
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
            ->assertHeader('Content-Disposition', 'attachment; filename=liste-inscriptions_2025-09-01_2025-09-30.xlsx');
    }

    // ─────────────────────── Le calcul imprimé ───────────────────────

    public function test_it_prints_the_same_calculation_as_the_screen(): void
    {
        $this->septembre($this->inscrit('Amal'), $this->inscrit('Badr'));

        $this->actingAs($this->user('reports.view', 'prof-payments.calculate'))
            ->get(route('backoffice.rapports.index', $this->window()))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.rapport', self::CLE)
                ->where('filtresVisibles', ['groupFilter', 'enseignantFilter'])
                // Aucun enseignant choisi : le principal du groupe.
                ->where('filters.enseignantFilter', (string) $this->prof->id)
                ->where('enseignantOptions.0.value', (string) $this->prof->id)
                ->where('nombreLignes', 2)
                // 400 (mois complet) + 200 (7 présences = 2 semaines).
                ->where('montantTotal', '600,00 DH')
                ->where('avertissementRapport', '')
                ->where('resumeRapport.Enseignant', $this->prof->nomComplet())
                ->where('resumeRapport.Séances effectuées', '14'));
    }

    public function test_without_a_group_it_says_why_nothing_is_computed(): void
    {
        $this->actingAs($this->user('reports.view', 'prof-payments.calculate'))
            ->get(route('backoffice.rapports.index', $this->window(['groupFilter' => ''])))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('nombreLignes', 0)
                ->where('montantTotal', '')
                ->where('avertissementRapport', 'Choisissez un groupe pour calculer le paiement de l’enseignant.'));
    }

    public function test_a_period_longer_than_the_pay_window_computes_nothing(): void
    {
        $this->septembre($this->inscrit('Amal'), $this->inscrit('Badr'));

        $this->actingAs($this->user('reports.view', 'prof-payments.calculate'))
            ->get(route('backoffice.rapports.index', $this->window(['dateTo' => '2025-12-31'])))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('nombreLignes', 0)
                ->where('montantTotal', '')
                ->where('avertissementRapport', 'Choisissez une date de début et une date de fin, 62 jours au plus.'));
    }

    // ─────────────────────── Les téléchargements ───────────────────────

    public function test_it_downloads_a_pdf_and_an_excel_workbook_and_writes_nothing(): void
    {
        $this->septembre($this->inscrit('Amal'), $this->inscrit('Badr'));
        $user = $this->user('reports.view', 'prof-payments.calculate');

        $pdf = $this->actingAs($user)->get(route('backoffice.rapports.pdf', $this->window()));
        $pdf->assertOk();
        $this->assertSame('application/pdf', $pdf->headers->get('Content-Type'));
        $this->assertStringContainsString('calcul-paiement-prof', (string) $pdf->headers->get('Content-Disposition'));

        $excel = $this->actingAs($user)->get(route('backoffice.rapports.excel', $this->window()));
        $excel->assertOk();
        $this->assertStringContainsString('calcul-paiement-prof', (string) $excel->headers->get('Content-Disposition'));

        // Un calcul n'est pas un paiement.
        $this->assertSame(0, Depense::query()->count());
    }

    public function test_an_empty_calculation_still_produces_a_document(): void
    {
        $this->actingAs($this->user('reports.view', 'prof-payments.calculate'))
            ->get(route('backoffice.rapports.pdf', $this->window(['groupFilter' => ''])))
            ->assertOk();
    }
}
