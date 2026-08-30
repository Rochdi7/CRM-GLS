<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice\Concerns;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Sends the user back to the LIST THEY CAME FROM, filters intact.
 *
 * A mutation that answers with `redirect()->route('backoffice.x.index')`
 * throws away the whole query string, so the list reloads unfiltered: the
 * cashier who had narrowed the Avances tab to one student, applied an
 * avance, and was dropped back onto page 1 of every avance of the centre
 * had to re-enter every filter after each application (30/08/2026).
 *
 * The referer carries the filters the page was actually showing, so it is
 * the source of truth here — `$extra` only overrides the keys the action
 * itself decides (e.g. forcing `view=avance` after converting payments,
 * since the user may have started from the Encaissements tab), and always
 * resets `page`, because after a write the row may no longer sit on the
 * page it was on.
 *
 * Falls back to the named route when there is no usable referer (a direct
 * POST, a test hitting the endpoint cold) or when it points at another
 * host — never redirect to an off-site URL a client supplied.
 */
trait RedirectsPreservingFilters
{
    /**
     * @param  array<string, scalar|null>  $extra  keys the action itself
     *                                             decides; they override the
     *                                             referer's own values
     */
    private function backToListPreservingFilters(
        Request $request,
        string $routeName,
        array $extra = [],
    ): RedirectResponse {
        $filters = $this->refererQueryOf($request);

        if ($filters === null) {
            return redirect()->route($routeName, $extra);
        }

        // `page` is intentionally dropped: after a write the affected row can
        // move (a fully-used avance leaves the default « Disponibles » view),
        // and page 7 of a now-shorter list is an empty table.
        unset($filters['page']);

        return redirect()->route($routeName, [...$filters, ...$extra]);
    }

    /**
     * The referer's query parameters, or null when it is absent, unparsable
     * or points outside this application.
     *
     * @return array<string, mixed>|null
     */
    private function refererQueryOf(Request $request): ?array
    {
        $referer = $request->headers->get('referer');

        if ($referer === null || $referer === '') {
            return null;
        }

        $parts = parse_url($referer);

        if ($parts === false) {
            return null;
        }

        $host = $parts['host'] ?? null;

        if ($host !== null && $host !== $request->getHost()) {
            return null;
        }

        parse_str($parts['query'] ?? '', $query);

        return $query;
    }
}
