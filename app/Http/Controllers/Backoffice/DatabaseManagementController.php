<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Maintenance\DeleteDatabaseRowRequest;
use App\Http\Requests\Backoffice\Maintenance\SaveDatabaseRowRequest;
use App\Http\Requests\Backoffice\Maintenance\TruncateDatabaseTableRequest;
use App\Support\Database\DatabaseBrowser;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * « Gestion de la base de données » — /backoffice/database-management.
 *
 * Un explorateur de tables piloté par des boutons : liste des tables,
 * parcours paginé/recherché/trié des lignes, ajout, modification,
 * suppression, vidage et export CSV — sans jamais saisir une requête SQL
 * (App\Support\Database\DatabaseBrowser).
 *
 * ⚠ RÉSERVÉ AU COMPTE DE MAINTENANCE — c'est une IDENTITÉ, pas une
 * permission, et elle n'est accordable à personne
 * (`AppServiceProvider::MAINTAINER_ONLY_ABILITIES`, décidée AU-DESSUS du
 * bypass super-admin, même mécanique que « Réconciliation des paiements
 * importés » et `GroupPolicy@updateClosed`). L'outil écrit DIRECTEMENT dans
 * les tables, en contournant toute action Domain, tout observer et tout
 * invariant monétaire (§11) : c'est un outil de réparation, pas un écran
 * d'exploitation, et le CEO n'y a pas accès.
 *
 * Volontairement ABSENT de la barre latérale : atteint par son lien direct.
 * L'absence d'entrée de menu n'est JAMAIS une protection — le gate décide,
 * le contrôleur revérifie, les Form Requests rejouent l'identité (§16).
 *
 * Chaque écriture est journalisée sous le log `database` avec la ligne
 * AVANT et APRÈS : c'est la seule trace de ce que l'outil a changé, puisque
 * `Auditable` (Eloquent) ne voit pas ces requêtes. `activity_log` lui-même
 * est en lecture seule ici (DatabaseBrowser::READ_ONLY_TABLES).
 */
final class DatabaseManagementController extends Controller
{
    /** Ability d'identité — voir AppServiceProvider::MAINTAINER_ONLY_ABILITIES. */
    public const ABILITY = 'database.manage';

    public function __construct(private readonly DatabaseBrowser $browser) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()->can(self::ABILITY), 403);

        return Inertia::render('Backoffice/DatabaseManagement/Index', [
            'database' => DB::connection()->getDatabaseName(),
            'tables' => $this->browser->tables(),
        ]);
    }

    public function show(Request $request, string $table): Response
    {
        abort_unless($request->user()->can(self::ABILITY), 403);
        abort_unless($this->browser->exists($table), 404);

        $filters = [
            'search' => (string) $request->query('search', ''),
            'sort' => (string) $request->query('sort', ''),
            'direction' => (string) $request->query('direction', 'asc'),
            'perPage' => (string) $request->query('perPage', '25'),
        ];

        return Inertia::render('Backoffice/DatabaseManagement/Table', [
            'table' => [
                'name' => $table,
                'readOnly' => $this->browser->isReadOnly($table),
                'primaryKey' => $this->browser->primaryKey($table),
                'total' => DB::table($table)->count(),
            ],
            'columns' => $this->browser->columns($table),
            'rows' => $this->browser->rows($table, $filters),
            'filters' => $filters,
        ]);
    }

    public function store(SaveDatabaseRowRequest $request, string $table): RedirectResponse
    {
        abort_unless($request->user()->can(self::ABILITY), 403);
        abort_unless($this->browser->exists($table), 404);

        return $this->attempt(function () use ($request, $table): string {
            $row = $this->browser->insert($table, $request->validated()['values']);

            $this->journal('row inserted', $table, null, $row);

            return __('Row added.');
        });
    }

    public function update(SaveDatabaseRowRequest $request, string $table): RedirectResponse
    {
        abort_unless($request->user()->can(self::ABILITY), 403);
        abort_unless($this->browser->exists($table), 404);

        return $this->attempt(function () use ($request, $table): string {
            $validated = $request->validated();
            $result = $this->browser->update($table, $validated['key'], $validated['values']);

            $this->journal('row updated', $table, $result['before'], $result['after']);

            return __('Row updated.');
        });
    }

    public function destroy(DeleteDatabaseRowRequest $request, string $table): RedirectResponse
    {
        abort_unless($request->user()->can(self::ABILITY), 403);
        abort_unless($this->browser->exists($table), 404);

        return $this->attempt(function () use ($request, $table): string {
            $before = $this->browser->delete($table, $request->validated()['key']);

            $this->journal('row deleted', $table, $before, null);

            return __('Row deleted.');
        });
    }

    public function truncate(TruncateDatabaseTableRequest $request, string $table): RedirectResponse
    {
        abort_unless($request->user()->can(self::ABILITY), 403);
        abort_unless($this->browser->exists($table), 404);

        return $this->attempt(function () use ($table): string {
            $count = $this->browser->truncate($table);

            $this->journal('table emptied', $table, ['rows' => $count], null);

            return __(':count rows deleted from :table.', ['count' => $count, 'table' => $table]);
        });
    }

    public function export(Request $request, string $table): StreamedResponse
    {
        abort_unless($request->user()->can(self::ABILITY), 403);
        abort_unless($this->browser->exists($table), 404);

        $this->journal('table exported', $table, null, null);

        $filename = $table.'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($table): void {
            $out = fopen('php://output', 'w');

            // BOM so Excel opens the UTF-8 file with accents intact.
            fwrite($out, "\xEF\xBB\xBF");

            foreach ($this->browser->exportRows($table) as $line) {
                fputcsv($out, $line, ';', '"', '\\');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Runs a write in its own transaction and turns a database refusal
     * (constraint, type, FK) into a form error the modal shows verbatim,
     * instead of a 500 page. The transaction also keeps the journal entry
     * and the row change together: neither exists without the other.
     *
     * @param  \Closure(): string  $write
     */
    private function attempt(\Closure $write): RedirectResponse
    {
        try {
            $message = DB::transaction($write);
        } catch (QueryException $e) {
            return back()->withErrors(['database' => $this->cleanMessage($e->getMessage())]);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['database' => $e->getMessage()]);
        }

        return back()->with('success', $message);
    }

    /** Strip the "SQLSTATE[…]: … (Connection: pgsql, SQL: …)" wrapper: the SQL text says nothing the user typed. */
    private function cleanMessage(string $message): string
    {
        $message = preg_replace('/\s*\(Connection: [^,]+, SQL: .*\)\s*$/s', '', $message) ?? $message;
        $message = preg_replace('/^SQLSTATE\[[^\]]+\]: [^:]+: \d+ /', '', $message) ?? $message;

        return trim($message);
    }

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    private function journal(string $event, string $table, ?array $before, ?array $after): void
    {
        activity('database')
            ->causedBy(auth()->user())
            ->withProperties(array_filter([
                'table' => $table,
                'old' => $before,
                'new' => $after,
            ], fn ($v) => $v !== null))
            ->log($event);
    }
}
