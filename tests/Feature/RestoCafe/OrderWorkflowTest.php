<?php

namespace Tests\Feature\RestoCafe;

use App\Models\User;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Orders\Models\Order;
use App\Modules\Tables\Models\RestaurantTable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_waiter_kitchen_cashier_end_to_end_flow_works(): void
    {
        $waiter = User::query()->where('email', 'waiter@restocafe.test')->firstOrFail();
        $kitchen = User::query()->where('email', 'kitchen@restocafe.test')->firstOrFail();
        $cashier = User::query()->where('email', 'cashier@restocafe.test')->firstOrFail();
        $table = RestaurantTable::query()->where('branch_id', 1)->where('number', 1)->firstOrFail();
        $menuItems = \App\Modules\Menu\Models\MenuItem::query()->where('branch_id', 1)->take(2)->get();

        $this->actingAs($waiter)
            ->post(route('orders.store'), [
                'table_id' => $table->id,
                'notes' => 'Academic demo order',
                'items' => [
                    ['menu_item_id' => $menuItems[0]->id, 'quantity' => 2, 'notes' => 'No sugar'],
                    ['menu_item_id' => $menuItems[1]->id, 'quantity' => 1, 'notes' => null],
                ],
            ])
            ->assertRedirect();

        $order = Order::query()->latest()->firstOrFail();

        $this->assertSame('new', $order->status->value);
        $this->assertSame('occupied', $table->fresh()->status->value);

        $this->actingAs($waiter)
            ->patch(route('orders.submit', $order))
            ->assertRedirect(route('orders.show', $order));

        $this->assertSame('in_kitchen', $order->fresh()->status->value);

        $this->actingAs($kitchen)
            ->patch(route('kitchen.ready', $order))
            ->assertRedirect();

        $this->assertSame('ready', $order->fresh()->status->value);

        $this->actingAs($cashier)
            ->post(route('invoices.store', $order))
            ->assertRedirect();

        $invoice = Invoice::query()->where('order_id', $order->id)->firstOrFail();

        $this->assertSame('served', $order->fresh()->status->value);
        $this->assertNull($invoice->paid_at);

        $this->actingAs($cashier)
            ->patch(route('invoices.pay', $invoice), [
                'payment_method' => 'cash',
            ])
            ->assertRedirect(route('invoices.show', $invoice));

        $this->assertNotNull($invoice->fresh()->paid_at);
        $this->assertSame('available', $table->fresh()->status->value);
    }
}
