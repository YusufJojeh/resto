<?php

namespace App\Modules\Menu\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Branches\Models\Branch;
use App\Modules\Menu\Models\MenuCategory;
use App\Modules\Menu\Models\MenuItem;
use App\Modules\Menu\Requests\StoreMenuItemRequest;
use App\Support\Subscription\PlanLimitKey;
use App\Modules\Menu\Requests\UpdateMenuItemRequest;
use App\Modules\Shared\Http\Controllers\Concerns\EnsuresBranchAccess;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MenuItemController extends Controller
{
    use EnsuresBranchAccess;

    public function index(): Response
    {
        $branchId = request()->user()->branch_id;

        return Inertia::render('menu/items/index', [
            'items' => MenuItem::query()->where('branch_id', $branchId)->with('category')->orderBy('name')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('menu/items/form', [
            'item' => null,
            'categories' => MenuCategory::query()->where('branch_id', request()->user()->branch_id)->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreMenuItemRequest $request): RedirectResponse
    {
        $branch = Branch::query()->with('plan')->findOrFail((int) $request->user()->branch_id);
        $menuItemCount = MenuItem::query()->where('branch_id', $branch->id)->count();
        if ($branch->isAtOrOverPlanLimit(PlanLimitKey::MAX_MENU_ITEMS, $menuItemCount)) {
            return back()->with('error', 'Your plan\'s maximum number of menu items has been reached.')->withInput();
        }

        MenuItem::query()->create([
            'branch_id' => $request->user()->branch_id,
            'category_id' => $request->validated('category_id'),
            'name' => $request->validated('name'),
            'description' => $request->validated('description'),
            'price' => $request->validated('price'),
            'is_available' => $request->boolean('is_available', true),
            'sort_order' => 0,
        ]);

        return to_route('menu.items.index')->with('success', 'Menu item created.');
    }

    public function edit(MenuItem $item): Response
    {
        $this->ensureBranchAccess($item);

        return Inertia::render('menu/items/form', [
            'item' => $item,
            'categories' => MenuCategory::query()->where('branch_id', request()->user()->branch_id)->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateMenuItemRequest $request, MenuItem $item): RedirectResponse
    {
        $this->ensureBranchAccess($item);

        $item->update([
            'category_id' => $request->validated('category_id'),
            'name' => $request->validated('name'),
            'description' => $request->validated('description'),
            'price' => $request->validated('price'),
            'is_available' => $request->boolean('is_available', true),
        ]);

        return to_route('menu.items.index')->with('success', 'Menu item updated.');
    }

    public function availability(MenuItem $item): RedirectResponse
    {
        $this->ensureBranchAccess($item);

        $item->update(['is_available' => ! $item->is_available]);

        return back()->with('success', 'Menu availability updated.');
    }
}
