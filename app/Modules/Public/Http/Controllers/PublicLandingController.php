<?php

namespace App\Modules\Public\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Branches\Models\Branch;
use App\Modules\Menu\Models\MenuCategory;
use App\Modules\Menu\Models\MenuItem;
use App\Modules\Public\Support\BrandTokens;
use Inertia\Inertia;
use Inertia\Response;

class PublicLandingController extends Controller
{
    public function __invoke(): Response
    {
        $branch = Branch::query()
            ->where('is_active', true)
            ->where('is_public', true)
            ->first();

        $branding = BrandTokens::fromBranch($branch);

        $featured = $branch
            ? MenuItem::query()
                ->where('branch_id', $branch->id)
                ->where('is_available', true)
                ->inRandomOrder()
                ->limit(6)
                ->get()
                ->map(fn ($item) => [
                    'id'          => $item->id,
                    'name'        => $item->name,
                    'description' => $item->description,
                    'price'       => $item->price,
                    'image_path'  => $item->image_path ? asset('storage/'.$item->image_path) : null,
                ])
            : collect();

        $categories = $branch
            ? MenuCategory::query()
                ->where('branch_id', $branch->id)
                ->withCount(['items as item_count' => fn ($query) => $query->where('is_available', true)])
                ->orderBy('sort_order')
                ->limit(6)
                ->get()
                ->map(fn ($category) => [
                    'id'         => $category->id,
                    'name'       => $category->name,
                    'item_count' => $category->item_count,
                ])
            : collect();

        return Inertia::render('public/landing', [
            'branding'      => $branding,
            'featured'      => $featured,
            'categories'     => $categories,
            'currency_code' => $branch?->currency_code ?? 'USD',
        ]);
    }
}
