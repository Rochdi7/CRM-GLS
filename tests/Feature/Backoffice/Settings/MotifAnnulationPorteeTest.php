<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Settings;

use App\Domain\Settings\Queries\GetMotifsAnnulationList;
use App\Http\Requests\Backoffice\Attendance\AnnulerSeanceRequest;
use App\Models\MotifAnnulation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A séance and an inscription are cancelled for different reasons, and the
 * catalogue is shared. Before `portee` existed, every reason added for one
 * form also appeared on the other: « Malade » / « jour férié » were offered
 * to cancel an ENROLLMENT, and « Non-paiement » to cancel a CLASS SESSION.
 *
 * These tests pin the separation at the single funnel both forms read
 * (GetMotifsAnnulationList::activeNames), so a new reason cannot leak across
 * by being added to the catalogue.
 */
final class MotifAnnulationPorteeTest extends TestCase
{
    use RefreshDatabase;

    private function catalogue(): void
    {
        MotifAnnulation::query()->create(['nom' => 'Malade', 'portee' => MotifAnnulation::PORTEE_SEANCE]);
        MotifAnnulation::query()->create(['nom' => 'Non-paiement', 'portee' => MotifAnnulation::PORTEE_INSCRIPTION]);
        MotifAnnulation::query()->create(['nom' => 'Autre', 'portee' => MotifAnnulation::PORTEE_TOUS]);
    }

    public function test_a_seance_reason_is_never_offered_to_cancel_an_inscription(): void
    {
        $this->catalogue();

        $names = app(GetMotifsAnnulationList::class)->activeNames(MotifAnnulation::PORTEE_INSCRIPTION);

        $this->assertNotContains('Malade', $names);
        $this->assertContains('Non-paiement', $names);
    }

    public function test_an_inscription_reason_is_never_offered_to_cancel_a_seance(): void
    {
        $this->catalogue();

        $names = app(GetMotifsAnnulationList::class)->activeNames(MotifAnnulation::PORTEE_SEANCE);

        $this->assertNotContains('Non-paiement', $names);
        $this->assertContains('Malade', $names);
    }

    public function test_a_reason_scoped_to_all_stays_on_both_forms(): void
    {
        $this->catalogue();

        $query = app(GetMotifsAnnulationList::class);

        $this->assertContains('Autre', $query->activeNames(MotifAnnulation::PORTEE_SEANCE));
        $this->assertContains('Autre', $query->activeNames(MotifAnnulation::PORTEE_INSCRIPTION));
    }

    /**
     * The dropdown and the validation rule read the same method, so a reason
     * the séance form does not offer is also refused when posted.
     */
    public function test_the_seance_form_rule_refuses_an_inscription_only_reason(): void
    {
        $this->catalogue();

        $this->assertNotContains('Non-paiement', AnnulerSeanceRequest::motifs());
    }

    /**
     * « Changement de groupe » is the system reason the group-change flow
     * writes when it archives an enrollment — it says nothing about why a
     * class did not take place, and must stay off the séance form.
     */
    public function test_the_system_group_change_reason_stays_off_the_seance_form(): void
    {
        MotifAnnulation::query()->create([
            'nom' => MotifAnnulation::MOTIF_CHANGEMENT_GROUPE,
            'is_system' => true,
            'portee' => MotifAnnulation::PORTEE_INSCRIPTION,
        ]);

        $this->assertNotContains(
            MotifAnnulation::MOTIF_CHANGEMENT_GROUPE,
            AnnulerSeanceRequest::motifs(),
        );
    }

    /** An unclassified reason defaults to 'tous' — nothing vanishes on upgrade. */
    public function test_a_reason_created_without_a_scope_defaults_to_all(): void
    {
        $motif = MotifAnnulation::query()->create(['nom' => 'Raison libre']);

        $this->assertSame(MotifAnnulation::PORTEE_TOUS, $motif->portee);
        $this->assertContains('Raison libre', app(GetMotifsAnnulationList::class)->activeNames(MotifAnnulation::PORTEE_SEANCE));
    }
}
