<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Spatie Role + French display label (docs/authorization-architecture.md).
 * Registered in config/permission.php ('models.role').
 */
class Role extends SpatieRole
{
    use Auditable;

    public const SUPER_ADMIN = 'super-admin';

    /** Roles whose machine name / existence must never change from the UI. */
    public const PROTECTED = [self::SUPER_ADMIN];

    protected $fillable = ['name', 'guard_name', 'label'];

    public function isProtected(): bool
    {
        return in_array($this->name, self::PROTECTED, true);
    }

    public function displayLabel(): string
    {
        return $this->label ?? $this->name;
    }
}
