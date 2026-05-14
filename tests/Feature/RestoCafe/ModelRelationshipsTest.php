<?php

namespace Tests\Feature\RestoCafe;

use App\Enums\OrderStatus;
use App\Models\User;
use App\Modules\Billing\Actions\CreateInvoiceFromOrder;
use App\Modules\Branches\Models\Branch;
use App\Modules\Inventory\Actions\AdjustStock;
use App\Modules\Inventory\Models\InventoryItem;
use App\Modules\Menu\Models\MenuCategory;
use App\Modules\Menu\Models\MenuItem;
use App\Modules\Orders\Models\Order;
use App\Modules\Tables\Models\RestaurantTable;

class ModelRelationshipsTest extends RestoCafeTestCase
{
    public function test_branch_has_relations(): void
    {
        $branch = Branch::query()->find(1);
        $this->assertInstanceOf(User::class, $branch->users->first());
        $this->assertInstanceOf(RestaurantTable::class, $branch->tables->first());
        $this->assertInstanceOf(MenuCategory::class, $branch->categories->first());
        $this->assertInstanceOf(MenuItem::class, $branch->menuItems->first());
        $this->assertInstanceOf(InventoryItem::class, $branch->inventoryItems->first());
        $this->assertCount(0, $branch->orders);
        $this->assertCount(0, $branch->invoices);
    }

    public function test_user_has_relations(): void
    {
        $waiter = $this->waiter();
        $order = $this->makeOrder($waiter);
        $this->assertSame(1, $waiter->orders()->count());

        $this->assertInstanceOf(Branch::class, $waiter->branch);
        $this->assertSame(0, $waiter->createdInvoices()->count());
        $this->assertSame(0, $waiter->stockMovements()->count());

        app(AdjustStock::class)->handle($this->manager(), InventoryItem::query()->first(), 1, 'test');
        $this->assertSame(1, $this->manager()->stockMovements()->count());
    }

    public function test_order_relations_and_subtotal_accessor(): void
    {
        $waiter = $this->waiter();
        $order = $this->makeOrder($waiter);

        $this->assertInstanceOf(Branch::class, $order->branch);
        $this->assertInstanceOf(User::class, $order->user);
        $this->assertInstanceOf(RestaurantTable::class, $order->table);
        $this->assertSame(1, $order->items->count());

        // subtotal accessor formats float with 2 decimals
        $expected = number_format((float) $order->items->first()->subtotal, 2, '.', '');
        $this->assertSame($expected, $order->subtotal);
    }

    public function test_invoice_relations(): void
    {
        $order = $this->makeOrder($this->waiter());
        $order->update(['status' => OrderStatus::Ready]);
        $invoice = app(CreateInvoiceFromOrder::class)->handle($this->cashier(), $order);

        $this->assertInstanceOf(Branch::class, $invoice->branch);
        $this->assertInstanceOf(Order::class, $invoice->order);
        $this->assertInstanceOf(User::class, $invoice->creator);
    }

    public function test_menu_relations(): void
    {
        $item = MenuItem::query()->where('branch_id', 1)->first();
        $this->assertInstanceOf(Branch::class, $item->branch);
        $this->assertInstanceOf(MenuCategory::class, $item->category);
        // may be null if no orderItem yet
        $this->assertCount(0, $item->orderItems);
        $this->assertInstanceOf(InventoryItem::class, $item->inventoryItem);

        $cat = MenuCategory::query()->where('branch_id', 1)->first();
        $this->assertInstanceOf(Branch::class, $cat->branch);
        $this->assertGreaterThan(0, $cat->items->count());
    }

    public function test_restaurant_table_relations(): void
    {
        $t = $this->firstTable();
        $this->assertInstanceOf(Branch::class, $t->branch);
        $this->assertCount(0, $t->orders);

        $this->makeOrder($this->waiter(), $t);
        $this->assertSame(1, $t->fresh()->orders()->count());
    }

    public function test_inventory_item_relations(): void
    {
        $item = InventoryItem::query()->first();
        $this->assertInstanceOf(Branch::class, $item->branch);
        $this->assertInstanceOf(MenuItem::class, $item->menuItem);
        $this->assertSame(0, $item->stockMovements()->count());
    }

    public function test_stock_movement_relations(): void
    {
        $item = InventoryItem::query()->first();
        app(AdjustStock::class)->handle($this->manager(), $item, 2, 'x');
        $m = $item->fresh()->stockMovements()->latest('id')->first();
        $this->assertInstanceOf(InventoryItem::class, $m->inventoryItem);
        $this->assertInstanceOf(User::class, $m->user);
    }

    public function test_order_item_relations(): void
    {
        $order = $this->makeOrder($this->waiter());
        $oi = $order->items->first();
        $this->assertInstanceOf(Order::class, $oi->order);
        $this->assertInstanceOf(MenuItem::class, $oi->menuItem);
    }

    public function test_soft_deletes_on_branch_and_menu(): void
    {
        $cat = MenuCategory::query()->where('branch_id', 1)->first();
        $cat->delete();
        $this->assertSoftDeleted('menu_categories', ['id' => $cat->id]);

        $item = MenuItem::query()->where('branch_id', 1)->first();
        $item->delete();
        $this->assertSoftDeleted('menu_items', ['id' => $item->id]);
    }
}
