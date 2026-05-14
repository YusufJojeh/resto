<?php

namespace Tests\Feature\RestoCafe\Controllers;

use App\Enums\InvoicePaymentMethod;
use App\Enums\OrderStatus;
use App\Modules\Billing\Actions\CreateInvoiceFromOrder;
use App\Modules\Billing\Actions\MarkInvoicePaid;
use App\Modules\Inventory\Models\InventoryItem;
use Illuminate\Support\Carbon;
use Tests\Feature\RestoCafe\RestoCafeTestCase;

class ReportAndDashboardTest extends RestoCafeTestCase
{
    public function test_dashboard_ok(): void
    {
        $this->actingAs($this->admin())->get(route('dashboard'))->assertOk();
    }

    public function test_dashboard_accessible_to_all_roles(): void
    {
        foreach ([$this->admin(), $this->manager(), $this->waiter(), $this->cashier(), $this->kitchen()] as $u) {
            $this->actingAs($u)->get(route('dashboard'))->assertOk();
        }
    }

    public function test_dashboard_stats_low_stock_branch_unaffected_by_cross_branch(): void
    {
        $other = $this->makeSecondaryBranch();
        InventoryItem::query()->where('branch_id', $other['branch']->id)->update(['quantity' => 0, 'low_threshold' => 5]);

        $this->actingAs($this->admin())->get(route('dashboard'))->assertOk();
    }

    public function test_reports_index_ok_without_date(): void
    {
        $this->actingAs($this->manager())->get(route('reports.index'))->assertOk();
    }

    public function test_reports_index_with_specific_date(): void
    {
        Carbon::setTestNow('2026-04-18 12:00:00');

        $order = $this->makeOrder($this->waiter());
        $order->update(['status' => OrderStatus::Ready]);
        $invoice = app(CreateInvoiceFromOrder::class)->handle($this->cashier(), $order);
        app(MarkInvoicePaid::class)->handle($invoice, InvoicePaymentMethod::Cash);

        $resp = $this->actingAs($this->manager())
            ->get(route('reports.index', ['date' => '2026-04-18']))
            ->assertOk();

        $this->assertStringContainsString('&quot;paidInvoices&quot;:1', $resp->content());

        Carbon::setTestNow();
    }

    public function test_reports_forbidden_for_waiter(): void
    {
        $this->actingAs($this->waiter())->get(route('reports.index'))->assertForbidden();
    }

    public function test_reports_forbidden_for_kitchen(): void
    {
        $this->actingAs($this->kitchen())->get(route('reports.index'))->assertForbidden();
    }

    public function test_reports_forbidden_for_cashier(): void
    {
        $this->actingAs($this->cashier())->get(route('reports.index'))->assertForbidden();
    }

    public function test_reports_index_invalid_date_is_rejected(): void
    {
        $this->actingAs($this->manager())
            ->get(route('reports.index', ['date' => 'not-a-date']))
            ->assertRedirect()
            ->assertSessionHasErrors('date');
    }

    public function test_dashboard_stats_match_seeded_state(): void
    {
        $this->actingAs($this->admin())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('dashboard')
                ->where('stats.tables', 8)
                ->where('stats.activeOrders', 0)
                ->where('stats.readyOrders', 0)
                ->where('stats.todayRevenue', 0)
                ->where('stats.lowStockCount', 0)
            );
    }

    public function test_dashboard_stats_active_orders_counts_active_statuses(): void
    {
        $tables = $this->availableTables(3);
        $this->makeOrder($this->waiter(), $tables[0], OrderStatus::New);
        $this->makeOrder($this->waiter(), $tables[1], OrderStatus::InKitchen);
        $this->makeOrder($this->waiter(), $tables[2], OrderStatus::Ready);

        $this->actingAs($this->admin())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('stats.activeOrders', 3)
                ->where('stats.readyOrders', 1)
            );
    }
}
