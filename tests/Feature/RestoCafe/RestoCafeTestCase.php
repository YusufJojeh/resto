<?php

namespace Tests\Feature\RestoCafe;

use App\Enums\OrderStatus;
use App\Enums\TableStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Modules\Branches\Models\Branch;
use App\Modules\Inventory\Models\InventoryItem;
use App\Modules\Menu\Models\MenuCategory;
use App\Modules\Menu\Models\MenuItem;
use App\Modules\Orders\Models\Order;
use App\Modules\Tables\Models\RestaurantTable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

abstract class RestoCafeTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    protected function admin(): User
    {
        return User::query()->where('email', 'admin@restocafe.test')->firstOrFail();
    }

    protected function manager(): User
    {
        return User::query()->where('email', 'manager@restocafe.test')->firstOrFail();
    }

    protected function waiter(): User
    {
        return User::query()->where('email', 'waiter@restocafe.test')->firstOrFail();
    }

    protected function cashier(): User
    {
        return User::query()->where('email', 'cashier@restocafe.test')->firstOrFail();
    }

    protected function kitchen(): User
    {
        return User::query()->where('email', 'kitchen@restocafe.test')->firstOrFail();
    }

    protected function firstTable(): RestaurantTable
    {
        return RestaurantTable::query()->where('branch_id', 1)->where('number', 1)->firstOrFail();
    }

    protected function availableTables(int $count = 2): array
    {
        return RestaurantTable::query()
            ->where('branch_id', 1)
            ->where('status', TableStatus::Available)
            ->orderBy('number')
            ->take($count)
            ->get()
            ->all();
    }

    protected function menuItems(int $count = 2): array
    {
        return MenuItem::query()->where('branch_id', 1)->orderBy('id')->take($count)->get()->all();
    }

    /** Create a secondary branch with its own table, menu item, inventory item, and users per role. */
    protected function makeSecondaryBranch(): array
    {
        $branch = Branch::query()->create([
            'name' => 'Second Branch',
            'address' => 'Elsewhere',
            'phone' => '+90 555 111 2222',
            'tax_rate' => 8.00,
            'currency_code' => 'USD',
            'is_active' => true,
            'subscription_status' => 'active',
        ]);

        $category = MenuCategory::query()->create([
            'branch_id' => $branch->id,
            'name' => 'Branch2 Coffee',
            'sort_order' => 1,
        ]);

        $menuItem = MenuItem::query()->create([
            'branch_id' => $branch->id,
            'category_id' => $category->id,
            'name' => 'Branch2 Espresso',
            'description' => 'b2',
            'price' => 5.00,
            'is_available' => true,
            'sort_order' => 0,
        ]);

        $table = RestaurantTable::query()->create([
            'branch_id' => $branch->id,
            'number' => 1,
            'name' => 'B2 Table 1',
            'capacity' => 4,
            'status' => TableStatus::Available,
        ]);

        $inventory = InventoryItem::query()->create([
            'branch_id' => $branch->id,
            'menu_item_id' => $menuItem->id,
            'name' => 'Branch2 Stock',
            'unit' => 'pcs',
            'quantity' => 10,
            'low_threshold' => 2,
        ]);

        $users = [];
        foreach (UserRole::cases() as $role) {
            $u = User::query()->create([
                'branch_id' => $branch->id,
                'name' => 'B2 '.$role->value,
                'email' => 'b2-'.$role->value.'@restocafe.test',
                'password' => Hash::make('password'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
            $u->syncRoles([$role->value]);
            $users[$role->value] = $u;
        }

        return [
            'branch' => $branch,
            'category' => $category,
            'menuItem' => $menuItem,
            'table' => $table,
            'inventory' => $inventory,
            'users' => $users,
        ];
    }

    /** Place a new order in the given status using action-bypassing direct persistence. */
    protected function makeOrder(User $user, ?RestaurantTable $table = null, OrderStatus $status = OrderStatus::New): Order
    {
        $table ??= $this->firstTable();
        $menuItem = $this->menuItems(1)[0];

        $order = Order::query()->create([
            'branch_id' => $user->branch_id,
            'table_id' => $table->id,
            'user_id' => $user->id,
            'status' => $status,
            'notes' => null,
        ]);

        $order->items()->create([
            'menu_item_id' => $menuItem->id,
            'menu_item_name' => $menuItem->name,
            'unit_price' => $menuItem->price,
            'quantity' => 2,
            'subtotal' => bcmul((string) $menuItem->price, '2', 2),
            'notes' => null,
        ]);

        if ($status !== OrderStatus::Cancelled) {
            $table->update(['status' => TableStatus::Occupied]);
        }

        return $order->fresh(['items', 'table']);
    }

    protected function assertRoleExists(string $role): void
    {
        $this->assertNotNull(Role::query()->where('name', $role)->first());
    }
}
