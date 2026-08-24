<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Import;

use App\Models\AnneeScolaire;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ReaffecterAnneeImporteeTest extends TestCase
{
    use RefreshDatabase;

    private AnneeScolaire $de;

    private AnneeScolaire $vers;

    private Etablissement $centre;

    private Etablissement $autreCentre;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vers = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->de = AnneeScolaire::create([
            'nom' => '2026/2027', 'date_debut' => '2026-09-01', 'date_fin' => '2027-08-31',
            'par_defaut' => false, 'inscription_ouverte' => true,
        ]);
        $this->centre = Etablissement::factory()->create();
        $this->autreCentre = Etablissement::factory()->create();
    }

    private function makeInscription(Etablissement $centre, AnneeScolaire $annee): Inscription
    {
        $group = Group::factory()->create([
            'etablissement_id' => $centre->id,
            'annee_scolaire_id' => $annee->id,
        ]);
        $student = Student::factory()->create(['etablissement_id' => $centre->id]);

        return Inscription::create([
            'reference' => 'INS-TEST-'.$group->id,
            'student_id' => $student->id,
            'group_id' => $group->id,
            'etablissement_id' => $centre->id,
            'annee_scolaire_id' => $annee->id,
            'statut' => Inscription::STATUT_ACTIVE,
            'date_inscription' => '2026-08-01',
        ]);
    }

    public function test_it_moves_only_the_targeted_centre_and_year(): void
    {
        $wrong = $this->makeInscription($this->centre, $this->de);
        $otherCentre = $this->makeInscription($this->autreCentre, $this->de);
        $alreadyRight = $this->makeInscription($this->centre, $this->vers);

        $this->artisan('annee:reaffecter', [
            '--centre' => (string) $this->centre->id,
            '--de' => '2026/2027',
            '--vers' => '2025/2026',
        ])->expectsConfirmation('Confirmer la réaffectation ?', 'yes')
            ->assertSuccessful();

        // The targeted centre's records moved — group and inscription both.
        $this->assertSame($this->vers->id, $wrong->fresh()->annee_scolaire_id);
        $this->assertSame($this->vers->id, $wrong->group->fresh()->annee_scolaire_id);

        // Another centre's records under the same year are untouched.
        $this->assertSame($this->de->id, $otherCentre->fresh()->annee_scolaire_id);

        // Records already in the target year are untouched.
        $this->assertSame($this->vers->id, $alreadyRight->fresh()->annee_scolaire_id);
    }

    public function test_dry_run_changes_nothing(): void
    {
        $wrong = $this->makeInscription($this->centre, $this->de);

        $this->artisan('annee:reaffecter', [
            '--centre' => (string) $this->centre->id,
            '--de' => '2026/2027',
            '--vers' => '2025/2026',
            '--dry-run' => true,
        ])->assertSuccessful();

        $this->assertSame($this->de->id, $wrong->fresh()->annee_scolaire_id);
    }

    public function test_it_refuses_an_unknown_year_or_ambiguous_centre(): void
    {
        $this->artisan('annee:reaffecter', [
            '--centre' => (string) $this->centre->id,
            '--de' => '2019/2020',
            '--vers' => '2025/2026',
        ])->assertFailed();

        $this->artisan('annee:reaffecter', [
            '--centre' => 'GLS',
            '--de' => '2026/2027',
            '--vers' => '2025/2026',
        ])->assertFailed();
    }
}
