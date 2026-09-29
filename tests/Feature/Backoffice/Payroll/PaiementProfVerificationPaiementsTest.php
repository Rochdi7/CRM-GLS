<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Payroll;

use App\Models\AnneeScolaire;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Frais;
use App\Models\Group;
use App\Models\GroupEnseignant;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Presence;
use App\Models\Seance;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Contrôle « par les Détails paiement » du calcul Paiement prof — Système
 * GLS seulement : taux × min(1, payé net ÷ dû) sur le frais du MOIS choisi.
 * Le calcul par séances reste la base (`calcul.total` inchangé).
 */
final class PaiementProfVerificationPaiementsTest extends TestCase
{
    use RefreshDatabase;

    private AnneeScolaire $annee;

    private Etablissement $centre;

    private Group $group;

    private Employee $prof;

    private Employee $agent;

    private Frais $fraisMars;

    private Frais $fraisAvril;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->annee = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->centre = Etablissement::factory()->create();

        $this->prof = Employee::factory()->create([
            'categorie' => Employee::CATEGORIE_ENSEIGNANT,
            'etablissement_id' => $this->centre->id,
            'mode_paiement_prof' => Employee::MODE_PAIEMENT_GLS,
            'montant_par_etudiant_prof' => 500,
        ]);
        $this->agent = Employee::factory()->create(['etablissement_id' => $this->centre->id]);

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

        $this->fraisMars = Frais::create(['nom' => 'Frais de Mars', 'montant_defaut' => 1000]);
        $this->fraisAvril = Frais::create(['nom' => 'Frais d\'Avril', 'montant_defaut' => 1000]);
        $this->group->frais()->sync([
            $this->fraisMars->id => ['montant' => 1000, 'date_echeance' => '2026-03-01'],
            $this->fraisAvril->id => ['montant' => 1000, 'date_echeance' => '2026-04-01'],
        ]);
    }

    private function inscrire(string $prenom, float $payeMars, float $payeAvril = 0): Student
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id, 'prenom' => $prenom, 'nom' => 'TEST']);

        $inscription = Inscription::create([
            'reference' => 'INS-PPV-'.$student->id,
            'student_id' => $student->id,
            'group_id' => $this->group->id,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'statut' => Inscription::STATUT_ACTIVE,
            'date_inscription' => '2025-09-01',
        ]);

        foreach ([[$this->fraisMars, $payeMars], [$this->fraisAvril, $payeAvril]] as [$frais, $paye]) {
            $fee = InscriptionFee::create([
                'inscription_id' => $inscription->id,
                'frais_id' => $frais->id,
                'nom' => $frais->nom,
                'montant_initial' => 1000,
                'montant' => 1000,
                'date_echeance' => '2026-03-01',
                'statut' => InscriptionFee::STATUT_NON_PAYE,
            ]);

            if ($paye > 0) {
                Encaissement::create([
                    'reference' => 'ENC-PPV-'.$fee->id,
                    'student_id' => $student->id,
                    'inscription_fee_id' => $fee->id,
                    'montant' => $paye,
                    'methode' => Encaissement::METHODE_ESPECES,
                    'date_paiement' => '2026-03-02',
                    'caisse_id' => $this->agent->till()->firstOrFail()->id,
                    'agent_id' => $this->agent->id,
                ]);
            }
        }

        return $student;
    }

    private function calculer(): TestResponse
    {
        $user = User::factory()->create();
        foreach (['prof-payments.calculate', 'centers.access-all'] as $p) {
            $user->givePermissionTo($p);
        }

        return $this->actingAs($user->fresh())
            ->get('/backoffice/paiement-prof?'.http_build_query([
                'groupFilter' => $this->group->id,
                'enseignantFilter' => $this->prof->id,
                'mois' => '2026-03',
            ]));
    }

    #[Test]
    public function it_pays_the_rate_prorated_on_what_was_paid_on_the_months_fee(): void
    {
        $this->markTestSkipped("Vérification par paiements masquée (29/09/2026) en attendant la formule exacte.");

        $plein = $this->inscrire('Plein', 1000);
        $moitie = $this->inscrire('Moitie', 500, 1000);   // Avril payé : ne compte pas pour Mars
        $rien = $this->inscrire('Rien', 0, 1000);

        // Une séance de mars : tout le monde présent ⇒ base séances = 3 × 500.
        $seance = Seance::create([
            'group_id' => $this->group->id,
            'date_seance' => '2026-03-02',
            'heure_debut' => '18:00',
            'heure_fin' => '20:00',
            'enseignant_id' => $this->prof->id,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'statut' => Seance::STATUT_EFFECTUEE,
        ]);
        foreach ([$plein, $moitie, $rien] as $s) {
            Presence::create(['seance_id' => $seance->id, 'student_id' => $s->id, 'statut' => Presence::STATUT_PRESENT]);
        }

        $this->calculer()
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                // La base par défaut reste celle des séances.
                ->where('calcul.total', fn ($v) => (float) $v === 1500.0)
                ->where('calcul.verificationPaiements.frais', ['Frais de Mars'])
                ->where('calcul.verificationPaiements.total', fn ($v) => (float) $v === 750.0)
                ->where('calcul.verificationPaiements.etudiantsPayants', 2)
                ->has('calcul.verificationPaiements.lignes', 3));
    }

    #[Test]
    public function the_verification_is_offered_for_the_gls_system_only(): void
    {
        $this->inscrire('Plein', 1000);
        $this->prof->update(['mode_paiement_prof' => Employee::MODE_PAIEMENT_HORAIRE, 'taux_horaire_prof' => 100]);

        $this->calculer()
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('calcul.verificationPaiements', null));
    }

    #[Test]
    public function a_month_without_its_fee_on_the_group_says_so(): void
    {
        $this->markTestSkipped("Vérification par paiements masquée (29/09/2026) en attendant la formule exacte.");

        $this->inscrire('Plein', 1000);
        $this->group->frais()->detach($this->fraisMars->id);

        $this->calculer()
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('calcul.verificationPaiements.frais', [])
                ->where('calcul.verificationPaiements.total', fn ($v) => (float) $v === 0.0));
    }
}
