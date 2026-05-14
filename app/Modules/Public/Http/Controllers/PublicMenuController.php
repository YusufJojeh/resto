<?php

namespace App\Modules\Public\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Branches\Models\Branch;
use App\Modules\Menu\Models\MenuCategory;
use App\Modules\Public\Support\BrandTokens;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicMenuController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var Branch $branch */
        $branch = Branch::query()
            ->where('is_active', true)
            ->where('is_public', true)
            ->when($request->query('slug'), fn ($q, $slug) => $q->where('public_slug', $slug))
            ->firstOrFail();

        return $this->renderMenu($branch);
    }

    private function renderMenu(Branch $branch): Response
    {
        $categories = MenuCategory::query()
            ->where('branch_id', $branch->id)
            ->with([
                'items' => fn ($q) => $q
                    ->where('is_available', true)
                    ->orderBy('sort_order')
                    ->orderBy('name'),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->filter(fn ($cat) => $cat->items->isNotEmpty())
            ->values();

        $branding = BrandTokens::fromBranch($branch);

        return Inertia::render('public/menu', [
            'categories' => $categories->map(fn ($cat) => [
                'id'    => $cat->id,
                'name'  => $cat->name,
                'items' => $cat->items->map(fn ($item) => [
                    'id'          => $item->id,
                    'name'        => $item->name,
                    'description' => $item->description,
                    'price'       => $item->price,
                    'image_path'  => $item->image_path ? asset('storage/'.$item->image_path) : null,
                ]),
            ]),
            'branding'       => $branding,
            'currency_code'  => $branch->currency_code,
        ]);
    }
}
