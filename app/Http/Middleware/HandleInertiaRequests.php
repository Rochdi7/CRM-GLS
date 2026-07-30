<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Context\CurrentContext;
use Illuminate\Http\Request;
use Inertia\Middleware;

/**
 * Shares only safe, minimal data with every Inertia page (migration plan
 * docs/inertia-react-migration-plan.md §4). No full Eloquent models and no
 * sensitive fields are shared globally — pages that need more request it
 * explicitly through their own controller props.
 */
final class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user === null ? null : [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'permissions' => $user === null ? [] : $user->getAllPermissions()->pluck('name')->values(),
            ],
            'context' => $user === null ? null : (function (): array {
                $context = app(CurrentContext::class);

                return [
                    'anneeScolaireId' => $context->anneeScolaireId(),
                    'etablissementId' => $context->etablissementId(),
                    'isAllCenters' => $context->isAllCenters(),
                    'canSwitchCenter' => $context->canSwitchCenter(),
                ];
            })(),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'locale' => app()->getLocale(),
        ];
    }
}
