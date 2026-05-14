<?php

namespace Database\Seeders;

use App\Enums\TableStatus;
use App\Modules\Inventory\Models\InventoryItem;
use App\Modules\Menu\Models\MenuCategory;
use App\Modules\Menu\Models\MenuItem;
use App\Modules\Tables\Models\RestaurantTable;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $categories = collect([
            ['name' => 'Coffee', 'sort_order' => 1],
            ['name' => 'Desserts', 'sort_order' => 2],
            ['name' => 'Main Dishes', 'sort_order' => 3],
        ])->map(fn (array $category) => MenuCategory::query()->updateOrCreate(
            ['branch_id' => 1, 'name' => $category['name']],
            ['sort_order' => $category['sort_order']],
        ));

        $items = [
            ['category' => 'Coffee', 'name' => 'Espresso', 'price' => 3.50],
            ['category' => 'Coffee', 'name' => 'Cappuccino', 'price' => 4.25],
            ['category' => 'Desserts', 'name' => 'Cheesecake', 'price' => 6.50],
            ['category' => 'Main Dishes', 'name' => 'Club Sandwich', 'price' => 8.75],
            ['category' => 'Main Dishes', 'name' => 'Pasta Alfredo', 'price' => 11.50],
        ];

        foreach ($items as $item) {
            $category = $categories->firstWhere('name', $item['category']);

            $menuItem = MenuItem::query()->updateOrCreate(
                ['branch_id' => 1, 'name' => $item['name']],
                [
                    'category_id' => $category->id,
                    'description' => $item['name'].' demo item',
                    'price' => $item['price'],
                    'is_available' => true,
                    'sort_order' => 0,
                ],
            );

            InventoryItem::query()->updateOrCreate(
                ['branch_id' => 1, 'name' => $item['name'].' Stock'],
                [
                    'menu_item_id' => $menuItem->id,
                    'unit' => 'pcs',
                    'quantity' => 30,
                    'low_threshold' => 5,
                ],
            );
        }

        foreach (range(1, 8) as $number) {
            RestaurantTable::query()->updateOrCreate(
                ['branch_id' => 1, 'number' => $number],
                [
                    'name' => 'Table '.$number,
                    'capacity' => $number <= 4 ? 4 : 6,
                    'status' => TableStatus::Available,
                ],
            );
        }
    }
}
