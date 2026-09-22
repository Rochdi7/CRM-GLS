<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Payroll;

use App\Domain\Payroll\Support\MoisDeGroupe;
use App\Models\Group;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Les « mois » de paie d'un groupe commencent le jour où il a démarré —
 * jamais le 1er du mois civil (décision du 22/09/2026).
 *
 * Tests purs : aucune base.
 */
final class MoisDeGroupeTest extends TestCase
{
    use RefreshDatabase;

    private function groupe(?string $debut, ?string $fin = null): Group
    {
        return new Group([
            'nom' => 'Test',
            'date_debut_formation' => $debut,
            'date_fin_formation' => $fin,
        ]);
    }

    #[Test]
    public function a_group_started_on_the_7th_pays_from_the_7th_to_the_6th(): void
    {
        // L'exemple de référence : parti le 07/09, « septembre » = 07/09 → 06/10.
        $mois = MoisDeGroupe::pour($this->groupe('2026-09-07'), '2026-09');

        $this->assertSame('2026-09-07', $mois->debut->toDateString());
        $this->assertSame('2026-10-06', $mois->fin->toDateString());
        $this->assertTrue($mois->ancreSurLeGroupe);
        $this->assertSame('Septembre 2026', $mois->libelle);
    }

    #[Test]
    public function the_next_month_chains_without_a_gap_or_an_overlap(): void
    {
        $sept = MoisDeGroupe::pour($this->groupe('2026-09-07'), '2026-09');
        $oct = MoisDeGroupe::pour($this->groupe('2026-09-07'), '2026-10');

        // Le lendemain de la fin de septembre est le début d'octobre :
        // aucune séance ne tombe entre deux mois, aucune n'est comptée deux fois.
        $this->assertSame($sept->fin->copy()->addDay()->toDateString(), $oct->debut->toDateString());
        $this->assertSame('2026-10-07', $oct->debut->toDateString());
        $this->assertSame('2026-11-06', $oct->fin->toDateString());
    }

    #[Test]
    public function a_group_started_on_the_31st_anchors_on_the_28th(): void
    {
        // Sans plafond, « février » chercherait un 31 février. Le 28 est le
        // seul jour qui existe dans tous les mois.
        $fev = MoisDeGroupe::pour($this->groupe('2026-01-31'), '2026-02');

        $this->assertSame('2026-02-28', $fev->debut->toDateString());
        $this->assertSame('2026-03-27', $fev->fin->toDateString());
    }

    #[Test]
    public function a_group_without_a_start_date_falls_back_to_the_calendar_month_and_says_so(): void
    {
        $mois = MoisDeGroupe::pour($this->groupe(null), '2026-09');

        $this->assertSame('2026-09-01', $mois->debut->toDateString());
        $this->assertSame('2026-09-30', $mois->fin->toDateString());
        // L'écran affiche un avertissement sur ce drapeau : il doit être vrai
        // seulement quand le groupe a VRAIMENT une date d'ancrage.
        $this->assertFalse($mois->ancreSurLeGroupe);
    }

    #[Test]
    public function the_civil_month_key_is_the_one_used_for_the_win_win_table(): void
    {
        // Le taux win-win est stocké par mois CIVIL (le 1er). Un mois de
        // groupe « septembre » qui court jusqu'au 06/10 lit quand même le
        // taux de SEPTEMBRE, pas celui d'octobre.
        $mois = MoisDeGroupe::pour($this->groupe('2026-09-07'), '2026-09');

        $this->assertSame('2026-09-01', $mois->moisCivil->toDateString());
    }

    #[Test]
    public function the_options_include_months_taught_before_the_declared_start_date(): void
    {
        // ⚠ Régression du 22/09/2026 : OUASSIMA 13H avait des séances depuis
        // janvier mais une `date_debut_formation` saisie au 01/09 — le menu
        // ne proposait QUE septembre, rendant impayables huit mois pourtant
        // enseignés. Les bornes viennent des SÉANCES, élargies par les dates
        // déclarées, jamais l'inverse.
        Carbon::setTestNow('2026-09-22');

        try {
            $group = Group::factory()->create([
                'date_debut_formation' => '2026-09-01',
                'date_fin_formation' => null,
            ]);

            foreach (['2026-07-10', '2026-09-10'] as $date) {
                DB::table('seances')->insert([
                    'group_id' => $group->id,
                    'date_seance' => $date,
                    'statut' => 'Effectuée',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $options = MoisDeGroupe::options($group);

            $this->assertSame(['2026-09', '2026-08', '2026-07'], array_column($options, 'value'));
        } finally {
            Carbon::setTestNow();
        }
    }

    #[Test]
    public function a_future_end_of_training_never_opens_months_that_have_not_happened(): void
    {
        // Une fin de formation au 15/11 ne doit pas proposer octobre et
        // novembre : on ne paie pas un mois qui n'a pas eu lieu.
        Carbon::setTestNow('2026-09-22');

        try {
            $options = MoisDeGroupe::options($this->groupe('2026-09-01', '2026-11-15'));

            $this->assertSame('2026-09', $options[0]['value']);
        } finally {
            Carbon::setTestNow();
        }
    }

    #[Test]
    public function the_options_run_from_the_start_month_to_today_newest_first(): void
    {
        Carbon::setTestNow('2026-01-15');

        try {
            $options = MoisDeGroupe::options($this->groupe('2025-10-08'));

            $this->assertSame(
                ['2026-01', '2025-12', '2025-11', '2025-10'],
                array_column($options, 'value'),
            );
            $this->assertSame('Janvier 2026', $options[0]['label']);
        } finally {
            Carbon::setTestNow();
        }
    }

    #[Test]
    public function the_options_stop_at_the_end_of_training_when_it_is_past(): void
    {
        Carbon::setTestNow('2026-09-22');

        try {
            $options = MoisDeGroupe::options($this->groupe('2025-10-08', '2026-02-20'));

            // Pas de mois proposé au-delà de février : le groupe n'enseignait plus.
            $this->assertSame('2026-02', $options[0]['value']);
            $this->assertSame('2025-10', end($options)['value']);
        } finally {
            Carbon::setTestNow();
        }
    }
}
