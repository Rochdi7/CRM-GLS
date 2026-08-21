<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Settings;

use App\Models\AppSetting;
use App\Support\Settings\AppSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The jsonb `options` bag on app_settings — the structured companion to the
 * scalar `valeur` used by switches like EXPENSE_APPROVAL. Nothing reads it in
 * production yet; these tests pin the contract so a future setting can rely on
 * it without another migration (CLAUDE.md §11 / §17).
 */
final class AppSettingsOptionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        AppSettings::flush();
    }

    public function test_options_default_to_an_empty_array_when_never_stored(): void
    {
        $this->assertSame([], AppSettings::options('demo.unset'));
        $this->assertNull(AppSettings::option('demo.unset', 'anything'));
    }

    public function test_it_stores_and_reads_back_a_structured_bag(): void
    {
        AppSettings::setOptions('demo.key', [
            'liste' => ['a', 'b'],
            'seuils' => ['alerte' => 500],
        ]);

        $this->assertSame(['a', 'b'], AppSettings::options('demo.key')['liste']);
        $this->assertSame(500, AppSettings::option('demo.key', 'seuils.alerte'));
    }

    public function test_option_returns_the_given_default_for_a_missing_path(): void
    {
        AppSettings::setOptions('demo.key', ['seuils' => ['alerte' => 500]]);

        $this->assertSame(42, AppSettings::option('demo.key', 'seuils.absent', 42));
    }

    public function test_merge_options_keeps_untouched_keys(): void
    {
        AppSettings::setOptions('demo.key', ['a' => 1, 'b' => 2]);
        AppSettings::mergeOptions('demo.key', ['b' => 3]);

        $this->assertSame(['a' => 1, 'b' => 3], AppSettings::options('demo.key'));
    }

    public function test_setting_an_empty_bag_clears_the_column(): void
    {
        AppSettings::setOptions('demo.key', ['a' => 1]);
        AppSettings::setOptions('demo.key', []);

        $this->assertSame([], AppSettings::options('demo.key'));
        $this->assertNull(AppSetting::query()->where('cle', 'demo.key')->value('options'));
    }

    public function test_scalar_valeur_and_structured_options_are_independent(): void
    {
        // A key may carry both: the switch stays readable as a bool while the
        // bag holds its structured configuration.
        AppSettings::setBool(AppSettings::EXPENSE_APPROVAL, false);
        AppSettings::setOptions(AppSettings::EXPENSE_APPROVAL, ['seuil' => 1000]);

        $this->assertFalse(AppSettings::expenseApprovalEnabled());
        $this->assertSame(1000, AppSettings::option(AppSettings::EXPENSE_APPROVAL, 'seuil'));
    }

    public function test_writes_invalidate_the_cache(): void
    {
        AppSettings::setOptions('demo.key', ['a' => 1]);
        $this->assertSame(['a' => 1], AppSettings::options('demo.key'));

        AppSettings::setOptions('demo.key', ['a' => 2]);
        $this->assertSame(['a' => 2], AppSettings::options('demo.key'));
    }
}
