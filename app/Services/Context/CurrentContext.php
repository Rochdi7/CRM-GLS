<?php

declare(strict_types=1);

namespace App\Services\Context;

use App\Models\AnneeScolaire;
use App\Models\Etablissement;
use App\Services\Authorization\CenterAccessService;
use Illuminate\Support\Collection;

/**
 * The "active working context" every backoffice screen is scoped to:
 * a selected academic year and a selected center (or "all centers").
 *
 * Persisted in the session (per user), so switching from the top bar sticks
 * across pages. Center choices are permission-aware:
 *  - `centers.access-all` ⇒ every center, plus "Tous les centres";
 *  - otherwise the centers the user's employee is assigned to
 *    (employee_etablissement pivot). A multi-center employee may switch
 *    freely between THEIR centers and pick "Tous les centres", which then
 *    means "all of mine" — every query still runs through
 *    CenterAccessService::scopeAccessibleCenters(), so it can never widen
 *    beyond the assignment;
 *  - a single-center employee stays locked to it, exactly as before.
 *
 * Registered as a singleton and shared to every view as $context
 * (see AppServiceProvider).
 */
final class CurrentContext
{
    private const SESSION_YEAR = 'context.annee_scolaire_id';

    private const SESSION_CENTER = 'context.etablissement_id';

    // Request-local memoization only (this class is a per-request singleton —
    // see AppServiceProvider). Never persisted beyond the request, so it
    // cannot leak state between users. Must be invalidated by every setter.
    private bool $yearResolved = false;

    private ?AnneeScolaire $resolvedYear = null;

    private bool $centerResolved = false;

    private ?Etablissement $resolvedCenter = null;

    private ?Collection $resolvedAnnees = null;

    private ?Collection $resolvedCentres = null;

    public function __construct(private readonly CenterAccessService $centerAccess) {}

    // --- Academic year -----------------------------------------------------

    public function anneeScolaire(): ?AnneeScolaire
    {
        if ($this->yearResolved) {
            return $this->resolvedYear;
        }

        $id = session(self::SESSION_YEAR);

        $year = $id ? AnneeScolaire::find($id) : null;

        // Fall back to the default year, then persist it.
        if ($year === null) {
            $year = AnneeScolaire::parDefaut()->first() ?? AnneeScolaire::orderByDesc('date_debut')->first();
            if ($year !== null) {
                session([self::SESSION_YEAR => $year->id]);
            }
        }

        $this->resolvedYear = $year;
        $this->yearResolved = true;

        return $year;
    }

    public function anneeScolaireId(): ?int
    {
        return $this->anneeScolaire()?->id;
    }

    public function setAnneeScolaire(int $id): void
    {
        // Only accept a real year id.
        if (AnneeScolaire::whereKey($id)->exists()) {
            session([self::SESSION_YEAR => $id]);
            $this->yearResolved = false;
            $this->resolvedYear = null;
        }
    }

    /** @return Collection<int, AnneeScolaire> */
    public function availableAnnees(): Collection
    {
        return $this->resolvedAnnees ??= AnneeScolaire::query()->orderByDesc('date_debut')->get();
    }

    // --- Center ------------------------------------------------------------

    /**
     * null = "all centers" — every center for a global user, or all the
     * centers they are assigned to for a multi-center employee. A
     * single-center employee is always locked to its own center.
     */
    public function etablissement(): ?Etablissement
    {
        if ($this->centerResolved) {
            return $this->resolvedCenter;
        }

        $user = auth()->user();

        if ($user !== null && ! $this->centerAccess->hasGlobalAccess($user)) {
            $ids = $this->centerAccess->accessibleCenterIds($user);

            if ($ids === []) {
                $center = null;
            } elseif (count($ids) === 1) {
                // Single-center employee: always locked to it.
                $center = Etablissement::find($ids[0]);
            } else {
                // Multi-center employee: honor the session choice as long as
                // it is one of THEIR centers; null = "all of mine".
                $id = session(self::SESSION_CENTER);

                $center = ($id !== null && in_array((int) $id, $ids, true))
                    ? Etablissement::find($id)
                    : null;
            }
        } else {
            $id = session(self::SESSION_CENTER);

            $center = $id ? Etablissement::find($id) : null;
        }

        $this->resolvedCenter = $center;
        $this->centerResolved = true;

        return $center;
    }

    public function etablissementId(): ?int
    {
        return $this->etablissement()?->id;
    }

    /**
     * True when the context spans every center (global user, "all" selected).
     */
    /**
     * True when no single center is selected. For a global user that means
     * literally every center; for a multi-center employee it means "all the
     * centers I am assigned to" — the actual narrowing is always done by
     * CenterAccessService::scopeAccessibleCenters(), which every list query
     * applies regardless of this flag.
     */
    public function isAllCenters(): bool
    {
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        if ($this->centerAccess->hasGlobalAccess($user)) {
            return session(self::SESSION_CENTER) === null;
        }

        // Only meaningful for employees holding more than one center.
        return count($this->centerAccess->accessibleCenterIds($user)) > 1
            && $this->etablissement() === null;
    }

    public function setEtablissement(?int $id): void
    {
        $user = auth()->user();

        if ($user === null || ! $this->canSwitchCenter()) {
            return;
        }

        // A non-global user may only ever select one of their own centers.
        if ($id !== null
            && ! $this->centerAccess->hasGlobalAccess($user)
            && ! in_array((int) $id, $this->centerAccess->accessibleCenterIds($user), true)) {
            return;
        }

        if ($id === null) {
            session()->forget(self::SESSION_CENTER);
            $this->centerResolved = false;
            $this->resolvedCenter = null;

            return;
        }

        if (Etablissement::whereKey($id)->exists()) {
            session([self::SESSION_CENTER => $id]);
            $this->centerResolved = false;
            $this->resolvedCenter = null;
        }
    }

    /**
     * Centers the user may pick from in the switcher.
     *
     * @return Collection<int, Etablissement>
     */
    public function availableCentres(): Collection
    {
        if ($this->resolvedCentres !== null) {
            return $this->resolvedCentres;
        }

        $user = auth()->user();

        $query = Etablissement::query()->orderByDesc('siege_social')->orderBy('nom_centre');

        if ($user !== null && ! $this->centerAccess->hasGlobalAccess($user)) {
            $query->whereIn('id', $this->centerAccess->accessibleCenterIds($user) ?: [0]);
        }

        return $this->resolvedCentres = $query->get();
    }

    /**
     * Global users always may; an employee may switch only when assigned to
     * more than one center (a single-center employee has nothing to pick).
     */
    public function canSwitchCenter(): bool
    {
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        return $this->centerAccess->hasGlobalAccess($user)
            || count($this->centerAccess->accessibleCenterIds($user)) > 1;
    }
}
