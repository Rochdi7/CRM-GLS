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
            // Lazy: only resolved when an Inertia page/partial-reload actually
            // asks for it, and never at all for guests — CurrentContext's
            // year/center/available-list queries are real DB round-trips,
            // not free (migration plan §"Shared Inertia props").
            'context' => $user === null ? null : fn () => (function (): array {
                $context = app(CurrentContext::class);

                return [
                    'anneeScolaireId' => $context->anneeScolaireId(),
                    'etablissementId' => $context->etablissementId(),
                    'isAllCenters' => $context->isAllCenters(),
                    'canSwitchCenter' => $context->canSwitchCenter(),
                    'currentCenter' => $context->etablissement() === null ? null : [
                        'id' => $context->etablissement()->id,
                        'name' => $context->etablissement()->nom_centre,
                    ],
                    'currentAcademicYear' => $context->anneeScolaire() === null ? null : [
                        'id' => $context->anneeScolaire()->id,
                        'name' => $context->anneeScolaire()->nom,
                    ],
                    'availableCenters' => $context->availableCentres()
                        ->map(fn ($centre) => ['id' => $centre->id, 'name' => $centre->nom_centre])
                        ->values(),
                    'availableAcademicYears' => $context->availableAnnees()
                        ->map(fn ($annee) => ['id' => $annee->id, 'name' => $annee->nom])
                        ->values(),
                ];
            })(),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
                // Laravel's password-broker convention ($status = Password::sendResetLink(...))
                // flashes a translated string under this key — ForgotPasswordController and
                // ResetPasswordController already do `->with('status', ...)` unchanged.
                'status' => fn () => $request->session()->get('status'),
            ],
            'locale' => app()->getLocale(),
        ];
    }
}
