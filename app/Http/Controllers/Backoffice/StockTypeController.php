<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Stock\Queries\GetStockTypesList;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\StockTypes\StoreStockTypeRequest;
use App\Http\Requests\Backoffice\StockTypes\UpdateStockTypeRequest;
use App\Models\StockType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Types de stock CRUD — replaces StockArticle's old hardcoded CATEGORIES
 * array, mirroring TypeDepenseController one-for-one. is_system rows (the
 * original 6 categories) are LOCKED — guarded unconditionally before the
 * policy call so it also stops super-admin.
 */
final class StockTypeController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(StockType::class, 'stock_type');
    }

    public function index(Request $request, GetStockTypesList $getStockTypesList): Response
    {
        $search = (string) $request->query('search', '');
        $perPage = (int) $request->query('per_page', 10);

        return Inertia::render('Backoffice/StockTypes/Index', [
            'types' => $getStockTypesList($search, in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10),
            'filters' => ['search' => $search],
            'permissions' => [
                'create' => $request->user()->can('create', StockType::class),
                'update' => $request->user()->can('stock-types.update'),
                'delete' => $request->user()->can('stock-types.delete'),
            ],
        ]);
    }

    public function store(StoreStockTypeRequest $request): RedirectResponse
    {
        // The admin form only ever creates custom types.
        StockType::create([
            ...$request->validated(),
            'is_system' => false,
        ]);

        return redirect()->route('backoffice.stock-types.index')
            ->with('status', __('Type de stock créé.'));
    }

    public function update(UpdateStockTypeRequest $request, StockType $stock_type): RedirectResponse
    {
        // System types are protected — checked unconditionally, BEFORE
        // authorize(), so even a super-admin cannot edit one.
        abort_if($stock_type->is_system, 403, __('Les types système ne sont pas modifiables.'));
        $this->authorize('update', $stock_type);

        $stock_type->update($request->validated());

        return redirect()->route('backoffice.stock-types.index')
            ->with('status', __('Type de stock mis à jour.'));
    }

    public function destroy(StockType $stock_type): RedirectResponse
    {
        abort_if($stock_type->is_system, 403, __('Les types système ne sont pas supprimables.'));
        $this->authorize('delete', $stock_type);

        $stock_type->loadCount('articles');

        if ($stock_type->articles_count) {
            return back()->withErrors(['delete' => __('This stock type is used by articles and cannot be deleted.')]);
        }

        $stock_type->delete();

        return redirect()->route('backoffice.stock-types.index')
            ->with('status', __('Type de stock supprimé.'));
    }
}
