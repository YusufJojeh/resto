<?php

namespace Tests\Feature\RestoCafe\Branding;

use App\Modules\Branches\Models\Branch;
use App\Modules\Menu\Models\MenuCategory;
use App\Modules\Menu\Models\MenuItem;
use App\Modules\Public\Support\BrandTokens;
use Tests\Feature\RestoCafe\RestoCafeTestCase;

class PublicMenuTest extends RestoCafeTestCase
{
    private function makePublicBranch(): Branch
    {
        $branch = Branch::query()->find(1);
        $branch->update(['is_public' => true, 'public_slug' => 'main']);
        return $branch;
    }

    public function test_public_menu_accessible_unauthenticated_when_public(): void
    {
        $this->makePublicBranch();
        $this->get(route('public.menu'))->assertOk();
    }

    public function test_public_menu_returns_404_when_no_public_branch(): void
    {
        // Default seeded branch has is_public=false
        $this->get(route('public.menu'))->assertNotFound();
    }

    public function test_public_menu_by_slug_query_param_resolves(): void
    {
        $this->makePublicBranch();
        $this->get(route('public.menu', ['slug' => 'main']))->assertOk();
    }

    public function test_public_menu_slug_query_param_404_on_wrong_slug(): void
    {
        $this->makePublicBranch();
        $this->get(route('public.menu', ['slug' => 'nonexistent']))->assertNotFound();
    }

    public function test_public_menu_only_shows_available_items(): void
    {
        $this->makePublicBranch();
        $items = $this->menuItems(2);
        $items[0]->update(['is_available' => false]);
        $items[1]->update(['is_available' => true]);

        $resp = $this->get(route('public.menu'))->assertOk();

        // Parse the Inertia data-page JSON to get the actual item IDs
        preg_match('/data-page="([^"]+)"/', $resp->content(), $m);
        $data = json_decode(html_entity_decode($m[1], ENT_QUOTES), true);

        $renderedItemIds = collect($data['props']['categories'])
            ->flatMap(fn ($cat) => $cat['items'])
            ->pluck('id')
            ->all();

        $this->assertContains($items[1]->id, $renderedItemIds);
        $this->assertNotContains($items[0]->id, $renderedItemIds);
    }

    public function test_public_menu_hides_inactive_branch(): void
    {
        Branch::query()->find(1)?->update(['is_public' => true, 'is_active' => false]);
        $this->get(route('public.menu'))->assertNotFound();
    }

    public function test_landing_page_ok_without_public_branch(): void
    {
        $this->get(route('home'))->assertOk();
    }

    public function test_landing_page_ok_with_public_branch(): void
    {
        $this->makePublicBranch();
        $this->get(route('home'))->assertOk();
    }

    public function test_landing_page_includes_branding_prop(): void
    {
        $this->makePublicBranch();
        Branch::query()->find(1)?->update([
            'business_name' => 'Test Cafe',
            'tagline' => 'Great food',
        ]);

        $resp = $this->get(route('home'))->assertOk();

        preg_match('/data-page="([^"]+)"/', $resp->content(), $m);
        $data = json_decode(html_entity_decode($m[1], ENT_QUOTES), true);

        $this->assertSame('Test Cafe', $data['props']['branding']['business_name']);
    }

    public function test_landing_page_unauthenticated_ok(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_public_menu_hides_category_where_all_items_are_unavailable(): void
    {
        $this->makePublicBranch();

        $category = MenuCategory::query()->where('branch_id', 1)->orderBy('id')->first();
        MenuItem::query()->where('category_id', $category->id)->update(['is_available' => false]);

        $resp = $this->get(route('public.menu'))->assertOk();

        preg_match('/data-page="([^"]+)"/', $resp->content(), $m);
        $data = json_decode(html_entity_decode($m[1], ENT_QUOTES), true);

        $renderedCategoryIds = collect($data['props']['categories'])->pluck('id')->all();
        $this->assertNotContains($category->id, $renderedCategoryIds);
    }

    public function test_brand_tokens_from_null_branch_returns_defaults(): void
    {
        $defaults = BrandTokens::fromBranch(null);

        $this->assertSame(config('app.name'), $defaults['business_name']);
        $this->assertNull($defaults['tagline']);
        $this->assertFalse($defaults['is_public']);
        $this->assertNull($defaults['public_slug']);
        $this->assertSame('USD', $defaults['currency_code']);
        $this->assertSame('#1a1a2e', $defaults['primary_color']);
        $this->assertSame([], $defaults['opening_hours']);
    }
}
