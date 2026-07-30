<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Support\Authorization\PermissionRegistry;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;

/**
 * Read-only permissions catalogue — permissions are defined by the
 * application (PermissionRegistry) and seeded, never created from the UI.
 *
 * Inertia/React pilot page (docs/inertia-react-migration-plan.md §6):
 * chosen first because it is read-only, so there is no form, validation,
 * Domain action, or center-scoping edge case to get wrong on the first
 * end-to-end attempt. Authorization is unchanged from the Blade version.
 */
final class PermissionController extends Controller
{
    public function __invoke(): Response
    {
        $this->authorize('permissions.view');

        return Inertia::render('Backoffice/Permissions/Index', [
            'groups' => PermissionRegistry::grouped(),
            'seededCount' => Permission::query()->count(),
        ]);
    }
}
