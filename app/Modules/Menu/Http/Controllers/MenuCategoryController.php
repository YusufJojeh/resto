<?php

namespace App\Modules\Menu\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Menu\Models\MenuCategory;
use App\Modules\Menu\Requests\StoreMenuCategoryRequest;
use App\Modules\Menu\Requests\UpdateMenuCategoryRequest;
use App\Modules\Shared\Http\Controllers\Concerns\EnsuresBranchAccess;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MenuCategoryController extends Controller
{
    use EnsuresBranchAccess;

    public function index(): Response
    {
        return Inertia::render('menu/categories/index', [
            'categories' => MenuCategory::query()
                ->where('branch_id', request()->user()->branch_id)
                ->withCount('items')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('menu/categories/form', ['category' => null]);
    }

    public function store(StoreMenuCategoryRequest $request): RedirectResponse
    {
        MenuCategory::query()->create([
            'branch_id' => $request->user()->branch_id,
            'name' => $request->validated('name'),
            'sort_order' => $request->validated('sort_order', 0),
        ]);

        return to_route('menu.categories.index')->with('success', 'Category created.');
    }

    public function edit(MenuCategory $category): Response
    {
        $this->ensureBranchAccess($category);

        return Inertia::render('menu/categories/form', ['category' => $category]);
    }

    public function update(UpdateMenuCategoryRequest $request, MenuCategory $category): RedirectResponse
    {
        $this->ensureBranchAccess($category);
        $category->update([
            'name' => $request->validated('name'),
            'sort_order' => $request->validated('sort_order', 0),
        ]);

        return to_route('menu.categories.index')->with('success', 'Category updated.');
    }
}
