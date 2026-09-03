<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Groups;

use App\Domain\Attendance\Support\DiagnostiquerEmploiDuTemps;
use App\Models\AnneeScolaire;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\GroupEnseignant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le diagnostic « pourquoi ce groupe ne génère plus de séances » doit nommer
 * la BONNE cause : le personnel voyait un emploi du temps vide sans jamais
 * savoir ce qui bloquait, et ressaisissait chaque séance à la main
 * (signalé 03/09/2026, 9 groupes sur 4 centres).
 *
 * ⚠ Ce read-model REFLÈTE les refus de GenererSeancesDepuisCreneau — il ne
 * les redérive pas. Si un refus change là-bas, ces tests doivent bouger aussi.
 */
final class EmploiDuTempsDiagnosticTest extends TestCase
{
    use RefreshDatabase;

    private AnneeScolaire $annee;

    private Etablissement $centre;

    protected function setUp(): void
    {
        parent::setUp();
        $this->annee = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->centre = Etablissement::factory()->create();
    }

    private function group(array $attributes = []): Group
    {
        return Group::factory()->create(array_merge([
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'date_debut_formation' => '2025-09-01',
            'date_fin_formation' => now()->addMonths(3)->toDateString(),
            'statut' => Group::STATUT_EN_FORMATION,
        ], $attributes));
    }

    private function diagnostiquer(Group $group, int $total, int $ouverts): ?array
    {
        return app(DiagnostiquerEmploiDuTemps::class)($group, $total, $ouverts);
    }

    public function test_a_healthy_group_reports_no_problem(): void
    {
        $this->assertNull($this->diagnostiquer($this->group(), 5, 5));
    }

    public function test_a_group_without_a_start_date_is_reported(): void
    {
        $probleme = $this->diagnostiquer($this->group(['date_debut_formation' => null]), 5, 5);

        $this->assertSame(DiagnostiquerEmploiDuTemps::DATE_DEBUT_MANQUANTE, $probleme['code']);
    }

    public function test_a_group_past_its_end_date_is_reported(): void
    {
        $group = $this->group(['date_fin_formation' => now()->subDay()->toDateString()]);

        $probleme = $this->diagnostiquer($group, 5, 5);

        $this->assertSame(DiagnostiquerEmploiDuTemps::FORMATION_TERMINEE, $probleme['code']);
        // La date fautive est affichée : c'est elle qu'on demande de corriger.
        $this->assertStringContainsString(now()->subDay()->format('d/m/Y'), $probleme['message']);
    }

    public function test_a_group_with_no_creneau_at_all_is_reported(): void
    {
        $probleme = $this->diagnostiquer($this->group(), 0, 0);

        $this->assertSame(DiagnostiquerEmploiDuTemps::AUCUN_CRENEAU, $probleme['code']);
    }

    public function test_a_group_whose_creneaux_are_all_closed_is_reported(): void
    {
        $probleme = $this->diagnostiquer($this->group(), 5, 0);

        $this->assertSame(DiagnostiquerEmploiDuTemps::CRENEAUX_FERMES, $probleme['code']);
    }

    /**
     * Le message ne doit JAMAIS annoncer un changement d'enseignant qui n'a
     * pas eu lieu : sur ces groupes l'historique n'affiche qu'une seule
     * période, et parler de changement enverrait l'utilisateur chercher
     * quelque chose d'inexistant.
     */
    public function test_closed_creneaux_without_a_real_change_do_not_mention_one(): void
    {
        $group = $this->group();
        GroupEnseignant::create([
            'group_id' => $group->id,
            'enseignant_id' => Employee::factory()->create([
                'categorie' => Employee::CATEGORIE_ENSEIGNANT,
                'etablissement_id' => $this->centre->id,
            ])->id,
            'date_debut' => '2025-09-01',
            'statut' => GroupEnseignant::STATUT_ACTIF,
        ]);

        $probleme = $this->diagnostiquer($group->fresh(), 5, 0);

        $this->assertSame(DiagnostiquerEmploiDuTemps::CRENEAUX_FERMES, $probleme['code']);
        $this->assertStringContainsString("aucun changement d'enseignant n'a", $probleme['message']);
    }

    /** Le pendant : deux périodes = un vrai changement, le message le dit. */
    public function test_closed_creneaux_after_a_real_change_mention_it(): void
    {
        $group = $this->group();
        foreach ([['2025-09-01', GroupEnseignant::STATUT_ARCHIVE], ['2025-10-01', GroupEnseignant::STATUT_ACTIF]] as [$debut, $statut]) {
            GroupEnseignant::create([
                'group_id' => $group->id,
                'enseignant_id' => Employee::factory()->create([
                    'categorie' => Employee::CATEGORIE_ENSEIGNANT,
                    'etablissement_id' => $this->centre->id,
                ])->id,
                'date_debut' => $debut,
                'statut' => $statut,
            ]);
        }

        $probleme = $this->diagnostiquer($group->fresh(), 5, 0);

        $this->assertStringContainsString("lors d'un changement d'enseignant", $probleme['message']);
    }

    /**
     * ⚠ Clôture PARTIELLE — le piège le plus discret : il reste des créneaux
     * ouverts, donc le groupe génère bien des séances, mais seulement
     * certains jours. OUASSIMA 13H et HERR ABDESSAMAD 10H (Marrakech,
     * 03/09/2026) avaient le lundi ouvert et le mardi au vendredi fermés :
     * rien ne le signalait, et le premier balayage (« aucun créneau ouvert »)
     * les laissait passer.
     */
    public function test_a_partially_closed_timetable_is_reported(): void
    {
        $probleme = $this->diagnostiquer($this->group(), 5, 1);

        $this->assertSame(DiagnostiquerEmploiDuTemps::CRENEAUX_PARTIELS, $probleme['code']);
        // Le message chiffre l'amputation : 4 créneaux sur 5.
        $this->assertStringContainsString('4 de ses 5', $probleme['message']);
    }

    /**
     * Un groupe terminé ou annulé N'EST PAS censé générer des séances : le
     * signaler noierait les vrais problèmes sous des fausses alertes.
     */
    public function test_a_finished_group_is_never_flagged(): void
    {
        foreach (Group::STATUTS_HISTORIQUE as $statut) {
            $group = $this->group(['statut' => $statut, 'date_debut_formation' => null]);

            $this->assertNull($this->diagnostiquer($group, 0, 0), $statut);
        }
    }
}
