<?php

declare(strict_types=1);

namespace App\Support\Settings;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Cache;

/**
 * Single source of truth for application-wide switches (Paramètres → Système).
 *
 * A switch that is not yet stored falls back to its DEFAULTS value, so a fresh
 * install behaves exactly like one where the admin never touched Paramètres —
 * no seeder required for the feature to work.
 *
 * ⚠ Feature code must ask THIS class (e.g. AppSettings::expenseApprovalEnabled()),
 * never query app_settings directly: the values are cached and invalidated here.
 */
final class AppSettings
{
    /** Dépenses go through a super-admin approval instead of debiting instantly. */
    public const EXPENSE_APPROVAL = 'expenses.approval_required';

    private const CACHE_KEY = 'app_settings.all';

    private const OPTIONS_CACHE_KEY = 'app_settings.options';

    /** @var array<string, bool> */
    public const DEFAULTS = [
        // ON by default: money must not leave a till without a decision.
        self::EXPENSE_APPROVAL => true,
    ];

    /**
     * Fallback structured config, same contract as DEFAULTS above: a key with
     * no stored `options` row reads its defaults here, so a fresh install
     * behaves like one the admin never touched. Empty until a setting needs it.
     *
     * @var array<string, array<string, mixed>>
     */
    public const OPTION_DEFAULTS = [];

    /** @return array<string, string> */
    private static function all(): array
    {
        return Cache::rememberForever(
            self::CACHE_KEY,
            fn (): array => AppSetting::query()->pluck('valeur', 'cle')->all(),
        );
    }

    public static function bool(string $key): bool
    {
        $stored = self::all()[$key] ?? null;

        if ($stored === null) {
            return self::DEFAULTS[$key] ?? false;
        }

        return filter_var($stored, FILTER_VALIDATE_BOOL);
    }

    public static function setBool(string $key, bool $value): void
    {
        AppSetting::query()->updateOrCreate(
            ['cle' => $key],
            ['valeur' => $value ? '1' : '0'],
        );

        self::flush();
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::OPTIONS_CACHE_KEY);
    }

    // ---------------------------------------------------------------
    // Structured settings (app_settings.options, jsonb)
    //
    // `valeur` above stays the scalar form every existing switch reads.
    // Use these when a setting needs more than one scalar — a list, a
    // per-center override map, a threshold set. Same cache discipline:
    // feature code asks THIS class, never app_settings directly.
    // ---------------------------------------------------------------

    /** @return array<string, array<string, mixed>> */
    private static function allOptions(): array
    {
        return Cache::rememberForever(
            self::OPTIONS_CACHE_KEY,
            fn (): array => AppSetting::query()
                ->whereNotNull('options')
                ->pluck('options', 'cle')
                ->map(fn ($o): array => is_array($o) ? $o : (array) json_decode((string) $o, true))
                ->all(),
        );
    }

    /**
     * The whole structured bag for one key, or OPTION_DEFAULTS/[] when unset.
     *
     * @return array<string, mixed>
     */
    public static function options(string $key): array
    {
        return self::allOptions()[$key] ?? self::OPTION_DEFAULTS[$key] ?? [];
    }

    /**
     * One value inside the bag, dot-notated ("seuils.alerte").
     */
    public static function option(string $key, string $path, mixed $default = null): mixed
    {
        return data_get(self::options($key), $path, $default
            ?? data_get(self::OPTION_DEFAULTS[$key] ?? [], $path));
    }

    /**
     * Replace the whole bag for a key. Pass [] to clear it; `valeur` is left
     * untouched, so a key may carry both a scalar switch and structured config.
     *
     * @param  array<string, mixed>  $options
     */
    public static function setOptions(string $key, array $options): void
    {
        AppSetting::query()->updateOrCreate(
            ['cle' => $key],
            ['options' => $options === [] ? null : $options],
        );

        self::flush();
    }

    /**
     * Merge into the existing bag (shallow) rather than replacing it.
     *
     * @param  array<string, mixed>  $options
     */
    public static function mergeOptions(string $key, array $options): void
    {
        self::setOptions($key, [...self::options($key), ...$options]);
    }

    /** Are new dépenses required to be approved before the till is debited? */
    public static function expenseApprovalEnabled(): bool
    {
        return self::bool(self::EXPENSE_APPROVAL);
    }
}
