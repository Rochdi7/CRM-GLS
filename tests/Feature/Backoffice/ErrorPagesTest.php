<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\User;
use App\Support\Errors\ErrorReference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

/**
 * Reported 31/08/2026: a bare « 500 Erreur serveur » made users tell the office
 * that the SERVER WAS DOWN, when in fact one action had failed and the rest of
 * the CRM was running fine. These tests pin the two halves of the fix — the
 * error pages render at all (a broken error page is invisible until the day it
 * is needed), and their wording distinguishes "this action failed" from "the
 * application is unavailable".
 */
final class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Memoised per process, not per request (see ErrorReference).
        ErrorReference::flush();

        // These tests are about what the USER sees, which only exists when the
        // debug page is off. Without this they would assert against Ignition.
        config()->set('app.debug', false);

        Route::middleware('web')->get('/test-boom', function (): never {
            throw new RuntimeException('boom');
        });
    }

    public function test_a_server_error_says_the_action_failed_not_that_the_server_is_down(): void
    {
        $response = $this->get('/test-boom');

        $response->assertStatus(500);
        $response->assertSee('Cette action n’a pas pu être effectuée', false);
        $response->assertSee('L’application fonctionne toujours', false);

        // The regression itself: never tell the user the server is down when it
        // is not. "Erreur serveur" was the exact string that caused the reports.
        $response->assertDontSee('Erreur serveur', false);
        $response->assertDontSee('indisponible', false);
    }

    public function test_a_server_error_shows_a_support_reference_that_matches_the_log(): void
    {
        $response = $this->get('/test-boom');

        $response->assertStatus(500);
        $response->assertSee(ErrorReference::current(), false);
        $this->assertMatchesRegularExpression('/^GLS-[0-9A-F]{8}$/', ErrorReference::current());
    }

    public function test_a_forbidden_page_reads_as_a_permission_refusal_not_a_crash(): void
    {
        Route::middleware('web')->get('/test-forbidden', fn () => abort(403));

        $response = $this->get('/test-forbidden');

        $response->assertStatus(403);
        $response->assertSee('Vous n’avez pas accès à cette page', false);
        $response->assertSee('Il ne s’agit pas d’un dysfonctionnement', false);
    }

    public function test_a_missing_page_states_the_application_still_works(): void
    {
        $response = $this->get('/backoffice/definitely-not-a-route');

        $response->assertStatus(404);
        $response->assertSee('Cette page n’existe pas', false);
        $response->assertSee('L’application fonctionne normalement', false);
    }

    /**
     * 503 is the ONE page allowed to say the app is unavailable, because there
     * it genuinely is. Keeping it distinct from 500 is the whole point.
     */
    public function test_maintenance_is_the_only_page_that_says_unavailable(): void
    {
        Route::middleware('web')->get('/test-maintenance', fn () => abort(503));

        $response = $this->get('/test-maintenance');

        $response->assertStatus(503);
        $response->assertSee('Maintenance en cours', false);
        $response->assertSee('temporairement indisponible', false);
    }

    /**
     * The backoffice posts through Inertia, where an HTML error body cannot be
     * rendered and leaves the user staring at a dead screen. An authenticated
     * Inertia request must therefore get a real Inertia page back.
     */
    public function test_an_inertia_request_receives_a_renderable_inertia_error_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withHeader('X-Inertia', 'true')
            ->withHeader('X-Inertia-Version', (string) app(HandleInertiaRequests::class)->version(request()))
            ->get('/test-boom');

        $response->assertStatus(500);
        $response->assertHeader('x-inertia', 'true');

        $page = $response->json();
        $this->assertSame('Error', $page['component']);
        $this->assertSame(500, $page['props']['status']);
        $this->assertNotEmpty($page['props']['errorId']);
    }

    /**
     * The React error page renders inside BackofficeLayout, which needs the
     * authenticated shared props. A guest must keep the standalone Blade page
     * or the error page would itself throw.
     */
    public function test_a_guest_inertia_request_falls_back_to_the_standalone_page(): void
    {
        $response = $this->withHeader('X-Inertia', 'true')
            ->withHeader('X-Inertia-Version', (string) app(HandleInertiaRequests::class)->version(request()))
            ->get('/test-boom');

        $response->assertStatus(500);
        $this->assertNotSame('true', $response->headers->get('x-inertia'));
    }

    public function test_debug_mode_keeps_the_developer_exception_page(): void
    {
        config()->set('app.debug', true);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withHeader('X-Inertia', 'true')
            ->withHeader('X-Inertia-Version', (string) app(HandleInertiaRequests::class)->version(request()))
            ->get('/test-boom');

        $response->assertStatus(500);
        $this->assertNotSame('true', $response->headers->get('x-inertia'));
    }
}
