<?php

namespace Tests\Feature\RestoCafe\Controllers;

use App\Modules\Menu\Models\MenuCategory;
use App\Modules\Menu\Models\MenuItem;
use Tests\Feature\RestoCafe\RestoCafeTestCase;

class MenuControllersTest extends RestoCafeTestCase
{
    public function test_categories_index_ok_manager(): void
    {
        $this->actingAs($this->manager())->get(route('menu.categories.index'))->assertOk();
    }

    public function test_categories_index_forbidden_waiter(): void
    {
        $this->actingAs($this->waiter())->get(route('menu.categories.index'))->assertForbidden();
    }

    public function test_categories_create_ok(): void
    {
        $this->actingAs($this->admin())->get(route('menu.categories.create'))->assertOk();
    }

    public function test_categories_store_ok(): void
    {
        $this->actingAs($this->manager())->post(route('menu.categories.store'), [
            'name' => 'Beverages',
            'sort_order' => 1,
        ])->assertRedirect(route('menu.categories.index'));
        $this->assertDatabaseHas('menu_categories', ['name' => 'Beverages', 'branch_id' => 1]);
    }

    public function test_categories_store_sort_order_default_zero(): void
    {
        $this->actingAs($this->manager())->post(route('menu.categories.store'), [
            'name' => 'NoSort',
        ])->assertRedirect();
        $this->assertSame(0, MenuCategory::where('name', 'NoSort')->value('sort_order'));
    }

    public function test_categories_store_rejects_duplicate_name_same_branch(): void
    {
        $this->actingAs($this->manager())->post(route('menu.categories.store'), [
            'name' => 'Coffee', // exists in seeded data
        ])->assertSessionHasErrors(['name']);
    }

    public function test_categories_store_allows_same_name_different_branch(): void
    {
        $other = $this->makeSecondaryBranch();
        MenuCategory::query()->create(['branch_id' => $other['branch']->id, 'name' => 'SharedName', 'sort_order' => 0]);
        $this->actingAs($this->manager())->post(route('menu.categories.store'), [
            'name' => 'SharedName',
        ])->assertRedirect();
        $this->assertDatabaseHas('menu_categories', ['name' => 'SharedName', 'branch_id' => 1]);
    }

    public function test_categories_edit_ok(): void
    {
        $cat = MenuCategory::query()->where('branch_id', 1)->first();
        $this->actingAs($this->manager())->get(route('menu.categories.edit', $cat))->assertOk();
    }

    public function test_categories_edit_returns_404_for_foreign_branch_category(): void
    {
        $other = $this->makeSecondaryBranch();

        $this->actingAs($this->manager())
            ->get(route('menu.categories.edit', $other['category']))
            ->assertNotFound();
    }

    public function test_categories_update_rejects_foreign_branch_category(): void
    {
        $other = $this->makeSecondaryBranch();
        $original = $other['category']->name;

        $this->actingAs($this->manager())
            ->put(route('menu.categories.update', $other['category']), [
                'name' => 'Hijacked',
                'sort_order' => 0,
            ])
            ->assertNotFound();

        $this->assertSame($original, $other['category']->fresh()->name);
    }

    public function test_categories_update_ok_and_ignores_self_for_unique(): void
    {
        $cat = MenuCategory::query()->where('branch_id', 1)->first();
        $this->actingAs($this->manager())->put(route('menu.categories.update', $cat), [
            'name' => $cat->name,
            'sort_order' => 9,
        ])->assertRedirect(route('menu.categories.index'));
        $this->assertSame(9, $cat->fresh()->sort_order);
    }

    public function test_categories_update_rejects_taken_name(): void
    {
        $cat = MenuCategory::query()->where('branch_id', 1)->first();
        $another = MenuCategory::query()->create(['branch_id' => 1, 'name' => 'Taken', 'sort_order' => 0]);

        $this->actingAs($this->manager())->put(route('menu.categories.update', $cat), [
            'name' => 'Taken',
        ])->assertSessionHasErrors(['name']);
    }

    public function test_items_index_ok(): void
    {
        $this->actingAs($this->manager())->get(route('menu.items.index'))->assertOk();
    }

    public function test_items_create_ok(): void
    {
        $this->actingAs($this->manager())->get(route('menu.items.create'))->assertOk();
    }

    public function test_items_store_ok(): void
    {
        $cat = MenuCategory::query()->where('branch_id', 1)->first();
        $this->actingAs($this->manager())->post(route('menu.items.store'), [
            'category_id' => $cat->id,
            'name' => 'Latte',
            'description' => 'smooth',
            'price' => 4.0,
            'is_available' => true,
        ])->assertRedirect(route('menu.items.index'));
        $this->assertDatabaseHas('menu_items', ['name' => 'Latte', 'branch_id' => 1]);
    }

    public function test_items_store_validation_errors(): void
    {
        $this->actingAs($this->manager())->post(route('menu.items.store'), [])
            ->assertSessionHasErrors(['category_id', 'name', 'price']);
    }

    public function test_items_store_price_min_validation(): void
    {
        $cat = MenuCategory::query()->where('branch_id', 1)->first();
        $this->actingAs($this->manager())->post(route('menu.items.store'), [
            'category_id' => $cat->id,
            'name' => 'Free',
            'price' => 0,
        ])->assertSessionHasErrors(['price']);
    }

    public function test_items_store_rejects_missing_category_exists(): void
    {
        $this->actingAs($this->manager())->post(route('menu.items.store'), [
            'category_id' => 9999,
            'name' => 'x', 'price' => 1,
        ])->assertSessionHasErrors(['category_id']);
    }

    public function test_items_edit_and_update(): void
    {
        $item = MenuItem::query()->where('branch_id', 1)->first();
        $this->actingAs($this->manager())->get(route('menu.items.edit', $item))->assertOk();

        $this->actingAs($this->manager())->put(route('menu.items.update', $item), [
            'category_id' => $item->category_id,
            'name' => 'Renamed',
            'description' => 'ok',
            'price' => 9.99,
            'is_available' => false,
        ])->assertRedirect(route('menu.items.index'));
        $fresh = $item->fresh();
        $this->assertSame('Renamed', $fresh->name);
        $this->assertFalse($fresh->is_available);
    }

    public function test_items_availability_toggles(): void
    {
        $item = MenuItem::query()->where('branch_id', 1)->first();
        $before = (bool) $item->is_available;
        $this->actingAs($this->manager())
            ->patch(route('menu.items.availability', $item))
            ->assertRedirect();
        $this->assertSame(! $before, (bool) $item->fresh()->is_available);
    }

    public function test_items_forbidden_for_waiter(): void
    {
        $this->actingAs($this->waiter())->get(route('menu.items.index'))->assertForbidden();
    }

    public function test_items_availability_forbidden_for_kitchen(): void
    {
        $item = MenuItem::query()->where('branch_id', 1)->first();
        $this->actingAs($this->kitchen())
            ->patch(route('menu.items.availability', $item))
            ->assertForbidden();
    }

    public function test_items_edit_returns_404_for_foreign_branch_item(): void
    {
        $other = $this->makeSecondaryBranch();

        $this->actingAs($this->manager())
            ->get(route('menu.items.edit', $other['menuItem']))
            ->assertNotFound();
    }

    public function test_items_update_rejects_foreign_branch_item(): void
    {
        $other = $this->makeSecondaryBranch();
        $foreignItem = $other['menuItem'];
        $localCategory = MenuCategory::query()->where('branch_id', 1)->first();

        $this->actingAs($this->manager())
            ->put(route('menu.items.update', $foreignItem), [
                'category_id' => $localCategory->id,
                'name' => 'Pwned',
                'description' => '',
                'price' => 1,
                'is_available' => true,
            ])
            ->assertNotFound();

        $this->assertNotSame('Pwned', $foreignItem->fresh()->name);
    }

    public function test_items_availability_rejects_foreign_branch_item(): void
    {
        $other = $this->makeSecondaryBranch();
        $foreignItem = $other['menuItem'];
        $before = (bool) $foreignItem->is_available;

        $this->actingAs($this->manager())
            ->patch(route('menu.items.availability', $foreignItem))
            ->assertNotFound();

        $this->assertSame($before, (bool) $foreignItem->fresh()->is_available);
    }

    public function test_items_store_rejects_category_from_other_branch(): void
    {
        $other = $this->makeSecondaryBranch();

        $this->actingAs($this->manager())
            ->post(route('menu.items.store'), [
                'category_id' => $other['category']->id,
                'name' => 'Hijack',
                'description' => '',
                'price' => 5,
                'is_available' => true,
            ])
            ->assertSessionHasErrors(['category_id']);
    }
}
