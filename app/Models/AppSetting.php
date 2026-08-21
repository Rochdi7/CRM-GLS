<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

/**
 * One application-wide switch (key/value). Always read and written through
 * App\Support\Settings\AppSettings — never queried directly by feature code,
 * so the cache stays coherent and every change lands in the audit journal.
 */
class AppSetting extends Model
{
    use Auditable;

    protected $table = 'app_settings';

    protected $fillable = ['cle', 'valeur', 'options'];

    /**
     * `valeur` holds the scalar a switch reads as; `options` is the jsonb bag
     * for settings that need structure (lists, per-center maps, thresholds).
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'options' => 'array',
        ];
    }
}
