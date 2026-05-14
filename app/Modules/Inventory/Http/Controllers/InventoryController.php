<?php

namespace App\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Actions\AdjustStock;
use App\Modules\Inventory\Models\InventoryItem;
use App\Modules\Inventory\Requests\AdjustStockRequest;
use App\Modules\Inventory\Requests\StoreInventoryItemRequest;
use App\Modules\Inventory\Requests\UpdateInventoryItemRequest;
use App\Modules\Menu\Models\MenuItem;
use App\Modules\Shared\Http\Controllers\Concerns\EnsuresBranchAccess;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class InventoryController extends Controller
{
    use EnsuresBranchAccess;

    public function __construct(
        private readonly AdjustStock $adjustStock,
    ) {
    }

    public function index(): Response
    {
        $paginator = InventoryItem::query()
            ->where('branch_id', request()->user()->branch_id)
            ->with('menuItem')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('inventory/index', [
            'items' => $paginator->items(),
            'itemsPagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('inventory/form', [
            'item' => null,
            'menuItems' => MenuItem::query()->where('branch_id', request()->user()->branch_id)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreInventoryItemRequest $request): RedirectResponse
    {
        InventoryItem::query()->create([
            'branch_id' => $request->user()->branch_id,
            ...$request->validated(),
        ]);

        return to_route('inventory.index')->with('success', 'Inventory item created.');
    }

    public function edit(InventoryItem $item): Response
    {
        $this->ensureBranchAccess($item);

        return Inertia::render('inventory/form', [
            'item' => $item,
            'menuItems' => MenuItem::query()->where('branch_id', request()->user()->branch_id)->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateInventoryItemRequest $request, InventoryItem $item): RedirectResponse
    {
        $this->ensureBranchAccess($item);
        $item->update($request->validated());

        return to_route('inventory.index')->with('success', 'Inventory item updated.');
    }

    public function adjust(AdjustStockRequest $request, InventoryItem $item): RedirectResponse
    {
        $this->ensureBranchAccess($item);
        try {
            $this->adjustStock->handle(
                $request->user(),
                $item,
                (float) $request->validated('adjustment'),
                $request->validated('reason'),
            );
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return to_route('inventory.index')->with('success', 'Stock adjusted.');
    }
}
