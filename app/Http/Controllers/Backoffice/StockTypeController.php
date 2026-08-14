<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\StockTypes\StoreStockTypeRequest;
use App\Http\Requests\Backoffice\StockTypes\UpdateStockTypeRequest;
use App\Models\StockType;
use Illuminate\Http\RedirectResponse;

/**
 * Types de stock CRUD — replaces StockArticle's old hardcoded CATEGORIES
 * array, mirroring TypeDepenseController one-for-one. is_system rows (the
 * original 6 categories) are LOCKED — guarded unconditionally before the
 * policy call so it also stops super-admin. No index() here — the list
 * lives in StockController@index as the "types" tab of the merged Gestion
 * du stock page; these are only the mutation endpoints its forms submit to.
 */
final class StockTypeController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(StockType::class, 'stock_type', ['except' => ['index']]);
    }

    public function store(StoreStockTypeRequest $request): RedirectResponse
    {
        // The admin form only ever creates custom types.
        StockType::create([
            ...$request->validated(),
            'is_system' => false,
        ]);

        return redirect()->route('backoffice.stock.index', ['tab' => 'types'])
            ->with('success', __('Type de stock créé.'));
    }

    public function update(UpdateStockTypeRequest $request, StockType $stock_type): RedirectResponse
    {
        // System types are protected — checked unconditionally, BEFORE
        // authorize(), so even a super-admin cannot edit one.
        abort_if($stock_type->is_system, 403, __('Les types système ne sont pas modifiables.'));
        $this->authorize('update', $stock_type);

        $stock_type->update($request->validated());

        return redirect()->route('backoffice.stock.index', ['tab' => 'types'])
            ->with('success', __('Type de stock mis à jour.'));
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

        return redirect()->route('backoffice.stock.index', ['tab' => 'types'])
            ->with('success', __('Type de stock supprimé.'));
    }
}
