<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Attendance;

use App\Models\AnneeScolaire;
use App\Models\Caisse;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Seance;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Fiche de présence — signalement du RETARD DE PAIEMENT sur la ligne d'appel.
 *
 * La règle n'appartient pas à cet écran : c'est celle de « Gestion des
 * recouvrements », portée par `RetardPaiementEtudiant` (frais non masqué,
 * échéance passée, reste dû > 0, inscription Active). Ces tests vérifient
 * que la fiche la PORTE fidèlement plutôt que de la redériver.
 */
final class RetardPaiementFichePresenceTest extends TestCase
{
    use RefreshDatabase;

    private AnneeScolaire $annee;

    private Etablissement $centre;

    private Group $group;

    private Seance $seance;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->annee = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->centre = Etablissement::factory()->create();
        $this->group = Group::factory()->create([
            'statut' => Group::STATUT_EN_FORMATION,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);
        $this->seance = Seance::create([
            'group_id' => $this->group->id,
            'date_seance' => now()->toDateString(),
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'statut' => Seance::STATUT_PREVUE,
        ]);
    }

    private function user(): User
    {
        $user = User::factory()->create();
        foreach (['attendance.view', 'attendance.mark', 'centers.access-all'] as $p) {
            $user->givePermissionTo($p);
        }

        return $user->fresh();
    }

    /** @return array{0: Student, 1: Inscription} */
    private function enroll(string $prenom, string $statut = Inscription::STATUT_ACTIVE): array
    {
        $student = Student::factory()->create([
            'etablissement_id' => $this->centre->id,
            'prenom' => $prenom,
        ]);

        $inscription = Inscription::create([
            'reference' => 'INS-RET-'.$student->id,
            'student_id' => $student->id,
            'group_id' => $this->group->id,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'statut' => $statut,
            'date_inscription' => '2026-03-01',
        ]);

        return [$student, $inscription];
    }

    private function fee(Inscription $inscription, string $echeance, float $montant = 300, ?string $masqueLe = null): InscriptionFee
    {
        return InscriptionFee::create([
            'inscription_id' => $inscription->id,
            'nom' => 'Frais de scolarité',
            'montant_initial' => $montant,
            'montant' => $montant,
            'date_echeance' => $echeance,
            'masque_le' => $masqueLe,
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    private function studentsOfFiche(User $user): array
    {
        $students = [];

        $this->actingAs($user)
            ->get(route('backoffice.seances.show', $this->seance))
            ->assertOk()
            ->assertInertia(function (Assert $page) use (&$students): void {
                $students = $page->toArray()['props']['seance']['students'];
            });

        return $students;
    }

    private function retardOf(array $students, string $prenom): ?array
    {
        foreach ($students as $line) {
            if ($line['prenom'] === $prenom) {
                return $line['retardPaiement'];
            }
        }

        $this->fail("Étudiant {$prenom} absent de la fiche de présence.");
    }

    public function test_it_flags_an_overdue_fee_and_grades_it_by_days_late(): void
    {
        [, $tardif] = $this->enroll('Tardif');
        $this->fee($tardif, now()->subDays(15)->toDateString());

        [, $recent] = $this->enroll('Recent');
        $this->fee($recent, now()->subDays(2)->toDateString());

        $students = $this->studentsOfFiche($this->user());

        $grave = $this->retardOf($students, 'Tardif');
        $this->assertNotNull($grave);
        $this->assertSame(15, $grave['jours']);
        $this->assertTrue($grave['grave'], 'Plus de 5 jours donne un signalement rouge.');

        $leger = $this->retardOf($students, 'Recent');
        $this->assertNotNull($leger);
        $this->assertSame(2, $leger['jours']);
        $this->assertFalse($leger['grave'], 'Moins de 5 jours donne un signalement orange.');
    }

    public function test_a_student_up_to_date_carries_no_flag(): void
    {
        // Échéance à venir.
        [, $futur] = $this->enroll('Futur');
        $this->fee($futur, now()->addDays(10)->toDateString());

        // Échéance passée mais frais SOLDÉ.
        [$paye, $inscriptionPayee] = $this->enroll('Paye');
        $fee = $this->fee($inscriptionPayee, now()->subDays(20)->toDateString());
        $caisse = Caisse::factory()->create(['etablissement_id' => $this->centre->id]);
        Encaissement::create([
            'reference' => 'ENC-RET-1',
            'student_id' => $paye->id,
            'inscription_fee_id' => $fee->id,
            'etablissement_id' => $this->centre->id,
            'caisse_id' => $caisse->id,
            'agent_id' => Employee::factory()->create(['etablissement_id' => $this->centre->id])->id,
            'montant' => 300,
            'methode' => Encaissement::METHODE_ESPECES,
            'date_paiement' => now()->subDays(20)->toDateString(),
        ]);

        $students = $this->studentsOfFiche($this->user());

        $this->assertNull($this->retardOf($students, 'Futur'));
        $this->assertNull($this->retardOf($students, 'Paye'));
    }

    public function test_a_hidden_fee_is_never_chased(): void
    {
        // Un frais retiré de l'inscription n'est plus dû : son argent a été
        // libéré en avance (CLAUDE.md §11). Le signaler enverrait réclamer
        // de l'argent que personne ne doit.
        [, $inscription] = $this->enroll('Masque');
        $this->fee($inscription, now()->subDays(30)->toDateString(), 300, now()->toDateTimeString());

        $this->assertNull($this->retardOf($this->studentsOfFiche($this->user()), 'Masque'));
    }

    public function test_it_shows_the_current_month_due_date_and_sums_every_overdue_line(): void
    {
        // Deux dettes : une vieille (mois précédent) et celle du mois EN
        // COURS. C'est la seconde que la personne qui fait l'appel doit
        // lire — mais le montant reste le total dû.
        [, $inscription] = $this->enroll('Cumul');
        $ceMois = now()->startOfMonth()->toDateString();
        $vieille = now()->subMonths(8)->toDateString();

        $this->fee($inscription, $vieille, 300);
        $this->fee($inscription, $ceMois, 150);

        $retard = $this->retardOf($this->studentsOfFiche($this->user()), 'Cumul');

        $this->assertTrue($retard['moisCourant']);
        $this->assertSame(
            now()->startOfMonth()->format('d/m/Y'),
            $retard['dateEcheance'],
            "L'échéance du mois en cours prime sur la plus ancienne."
        );
        $this->assertSame('450.00', $retard['montant'], 'Le montant cumule toutes les lignes échues.');
    }

    public function test_it_falls_back_to_the_oldest_debt_when_the_current_month_is_settled(): void
    {
        // Rien ne tombe ce mois-ci : la vieille dette reste due, donc elle
        // reste affichée. La masquer ferait disparaître de l'écran de
        // l'argent que l'étudiant doit réellement.
        [, $inscription] = $this->enroll('Ancien');
        $this->fee($inscription, now()->subMonths(8)->toDateString(), 300);

        $retard = $this->retardOf($this->studentsOfFiche($this->user()), 'Ancien');

        $this->assertFalse($retard['moisCourant']);
        $this->assertSame(now()->subMonths(8)->format('d/m/Y'), $retard['dateEcheance']);
    }
}
