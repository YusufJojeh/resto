<?php

namespace Database\Seeders;

use App\Enums\InvoicePaymentMethod;
use App\Enums\UserRole;
use App\Modules\Billing\Actions\CreateInvoiceFromOrder;
use App\Modules\Billing\Actions\MarkInvoicePaid;
use App\Modules\Branches\Models\Branch;
use App\Modules\Inventory\Actions\AdjustStock;
use App\Modules\Inventory\Models\InventoryItem;
use App\Modules\Kitchen\Actions\MarkKitchenOrderReady;
use App\Modules\Menu\Models\MenuItem;
use App\Modules\Orders\Actions\CancelOrder;
use App\Modules\Orders\Actions\CreateOrder;
use App\Modules\Orders\Actions\SubmitOrderToKitchen;
use App\Modules\Tables\Models\RestaurantTable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class VisualRegressionSeeder extends Seeder
{
    public function run(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-18 09:00:00', 'UTC'));

        $this->seedEmptyBranchUsers();
        $this->seedMainBranchVisualState();

        Carbon::setTestNow();
    }

    private function seedEmptyBranchUsers(): void
    {
        $branch = Branch::query()->create([
            'name' => 'Empty Demo Branch',
            'address' => 'Silent Street 2',
            'phone' => '+90 555 222 0000',
            'tax_rate' => 10.00,
            'currency_code' => 'USD',
            'is_active' => true,
            'subscription_status' => 'active',
        ]);

        foreach (UserRole::cases() as $role) {
            $user = User::query()->create([
                'branch_id' => $branch->id,
                'name' => 'Empty '.ucfirst($role->value),
                'email' => 'empty-'.$role->value.'@restocafe.test',
                'password' => Hash::make('password'),
                'email_verified_at' => Carbon::now(),
                'is_active' => true,
            ]);

            $user->syncRoles([$role->value]);
        }
    }

    private function seedMainBranchVisualState(): void
    {
        // Apply branding to main branch so public pages have real content
        Branch::query()->find(1)?->update([
            'business_name'  => 'RestoCafe',
            'tagline'        => 'Freshness you can taste.',
            'story'          => 'Founded in 2020, RestoCafe started as a small corner café with a big dream: to serve honest, fresh food made with locally sourced ingredients.',
            'public_slug'    => 'restocafe',
            'is_public'      => true,
            'primary_color'  => '#1a1a2e',
            'secondary_color'=> '#16213e',
            'accent_color'   => '#e94560',
            'whatsapp'       => '+1234567890',
            'opening_hours'  => [
                ['day' => 'Mon – Fri', 'hours' => '07:00 – 22:00'],
                ['day' => 'Sat – Sun', 'hours' => '09:00 – 23:00'],
            ],
        ]);

        $waiter = User::query()->where('email', 'waiter@restocafe.test')->firstOrFail();
        $manager = User::query()->where('email', 'manager@restocafe.test')->firstOrFail();
        $cashier = User::query()->where('email', 'cashier@restocafe.test')->firstOrFail();

        User::query()->create([
            'branch_id' => 1,
            'name' => 'Inactive Waiter',
            'email' => 'inactive.waiter@restocafe.test',
            'password' => Hash::make('password'),
            'email_verified_at' => Carbon::now(),
            'is_active' => false,
        ])->syncRoles([UserRole::Waiter->value]);

        RestaurantTable::query()->where('branch_id', 1)->where('number', 2)->update(['status' => 'reserved']);
        MenuItem::query()->where('branch_id', 1)->where('name', 'Pasta Alfredo')->update(['is_available' => false]);

        $espresso = MenuItem::query()->where('branch_id', 1)->where('name', 'Espresso')->firstOrFail();
        $cappuccino = MenuItem::query()->where('branch_id', 1)->where('name', 'Cappuccino')->firstOrFail();
        $cheesecake = MenuItem::query()->where('branch_id', 1)->where('name', 'Cheesecake')->firstOrFail();
        $clubSandwich = MenuItem::query()->where('branch_id', 1)->where('name', 'Club Sandwich')->firstOrFail();

        $tables = RestaurantTable::query()
            ->where('branch_id', 1)
            ->orderBy('number')
            ->get()
            ->keyBy('number');

        $this->seedInventoryStates($manager);

        Carbon::setTestNow(Carbon::parse('2026-04-18 09:05:00', 'UTC'));
        $cancelledOrder = app(CreateOrder::class)->handle($waiter, [
            'table_id' => $tables[1]->id,
            'notes' => 'Guest left before ordering',
            'items' => [
                ['menu_item_id' => $espresso->id, 'quantity' => 1, 'notes' => 'No sugar'],
            ],
        ]);
        app(CancelOrder::class)->handle($cancelledOrder, 'Guest left');

        Carbon::setTestNow(Carbon::parse('2026-04-18 09:10:00', 'UTC'));
        app(CreateOrder::class)->handle($waiter, [
            'table_id' => $tables[3]->id,
            'notes' => 'Window seat guest',
            'items' => [
                ['menu_item_id' => $espresso->id, 'quantity' => 2, 'notes' => 'Extra hot'],
                ['menu_item_id' => $cheesecake->id, 'quantity' => 1, 'notes' => 'Two forks'],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2026-04-18 09:20:00', 'UTC'));
        $inKitchenOrder = app(CreateOrder::class)->handle($waiter, [
            'table_id' => $tables[4]->id,
            'notes' => 'Lunch rush',
            'items' => [
                ['menu_item_id' => $clubSandwich->id, 'quantity' => 1, 'notes' => 'Cut in half'],
                ['menu_item_id' => $cappuccino->id, 'quantity' => 1, 'notes' => null],
            ],
        ]);
        app(SubmitOrderToKitchen::class)->handle($inKitchenOrder);

        Carbon::setTestNow(Carbon::parse('2026-04-18 09:30:00', 'UTC'));
        $readyOrder = app(CreateOrder::class)->handle($waiter, [
            'table_id' => $tables[5]->id,
            'notes' => 'Ready for cashier handoff',
            'items' => [
                ['menu_item_id' => $clubSandwich->id, 'quantity' => 2, 'notes' => 'One spicy'],
            ],
        ]);
        app(SubmitOrderToKitchen::class)->handle($readyOrder);
        app(MarkKitchenOrderReady::class)->handle($readyOrder);

        Carbon::setTestNow(Carbon::parse('2026-04-18 09:40:00', 'UTC'));
        $servedUnpaidOrder = app(CreateOrder::class)->handle($waiter, [
            'table_id' => $tables[6]->id,
            'notes' => 'Awaiting payment at the counter',
            'items' => [
                ['menu_item_id' => $espresso->id, 'quantity' => 2, 'notes' => null],
                ['menu_item_id' => $cheesecake->id, 'quantity' => 1, 'notes' => 'Birthday plate'],
            ],
        ]);
        app(SubmitOrderToKitchen::class)->handle($servedUnpaidOrder);
        $servedUnpaidReady = app(MarkKitchenOrderReady::class)->handle($servedUnpaidOrder);
        app(CreateInvoiceFromOrder::class)->handle($cashier, $servedUnpaidReady);

        Carbon::setTestNow(Carbon::parse('2026-04-18 10:00:00', 'UTC'));
        $paidCashOrder = app(CreateOrder::class)->handle($waiter, [
            'table_id' => $tables[7]->id,
            'notes' => 'Cash customer',
            'items' => [
                ['menu_item_id' => $cappuccino->id, 'quantity' => 2, 'notes' => 'Extra foam'],
            ],
        ]);
        app(SubmitOrderToKitchen::class)->handle($paidCashOrder);
        $paidCashReady = app(MarkKitchenOrderReady::class)->handle($paidCashOrder);
        $cashInvoice = app(CreateInvoiceFromOrder::class)->handle($cashier, $paidCashReady);
        app(MarkInvoicePaid::class)->handle($cashInvoice, InvoicePaymentMethod::Cash);

        Carbon::setTestNow(Carbon::parse('2026-04-18 10:15:00', 'UTC'));
        $paidCardOrder = app(CreateOrder::class)->handle($waiter, [
            'table_id' => $tables[8]->id,
            'notes' => 'Card customer',
            'items' => [
                ['menu_item_id' => $clubSandwich->id, 'quantity' => 1, 'notes' => null],
                ['menu_item_id' => $cappuccino->id, 'quantity' => 1, 'notes' => 'Oat milk'],
            ],
        ]);
        app(SubmitOrderToKitchen::class)->handle($paidCardOrder);
        $paidCardReady = app(MarkKitchenOrderReady::class)->handle($paidCardOrder);
        $cardInvoice = app(CreateInvoiceFromOrder::class)->handle($cashier, $paidCardReady);
        app(MarkInvoicePaid::class)->handle($cardInvoice, InvoicePaymentMethod::Card);
    }

    private function seedInventoryStates(User $manager): void
    {
        $espressoStock = InventoryItem::query()->where('branch_id', 1)->where('name', 'Espresso Stock')->firstOrFail();
        $cappuccinoStock = InventoryItem::query()->where('branch_id', 1)->where('name', 'Cappuccino Stock')->firstOrFail();
        $cheesecakeStock = InventoryItem::query()->where('branch_id', 1)->where('name', 'Cheesecake Stock')->firstOrFail();

        Carbon::setTestNow(Carbon::parse('2026-04-18 08:00:00', 'UTC'));
        app(AdjustStock::class)->handle($manager, $espressoStock, -28, 'Morning rush');

        Carbon::setTestNow(Carbon::parse('2026-04-18 08:15:00', 'UTC'));
        app(AdjustStock::class)->handle($manager, $cappuccinoStock, -30, 'Sold out before restock');

        Carbon::setTestNow(Carbon::parse('2026-04-18 08:30:00', 'UTC'));
        app(AdjustStock::class)->handle($manager, $cheesecakeStock, 12, 'Bakery delivery');
    }
}
