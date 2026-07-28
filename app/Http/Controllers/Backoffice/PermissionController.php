<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Support\Authorization\PermissionRegistry;
use Illuminate\Contracts\View\View;
use Spatie\Permission\Models\Permission;

/**
 * Read-only permissions catalogue — permissions are defined by the
 * application (PermissionRegistry) and seeded, never created from the UI.
 * Plain Blade on purpose: no server-side dynamics needed (CLAUDE.md §5).
 */
final class PermissionController extends Controller
{
    public function __invoke(): View
    {
        $this->authorize('permissions.view');

        return view('backoffice.permissions.index', [
            'groups' => PermissionRegistry::grouped(),
            'seededCount' => Permission::query()->count(),
        ]);
    }
}
