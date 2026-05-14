<?php

namespace App\Modules\Shared\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Branches\Models\Branch;
use App\Modules\Inventory\Models\InventoryItem;
use App\Modules\Orders\Models\Order;
use App\Modules\Tables\Models\RestaurantTable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $branchId = $request->user()->branch_id;
        $currencyCode = Branch::query()->find($branchId)?->currency_code ?? 'USD';

        return Inertia::render('dashboard', [
            'stats' => [
                'tables' => RestaurantTable::query()->where('branch_id', $branchId)->count(),
                'activeOrders' => Order::query()->where('branch_id', $branchId)->whereIn('status', ['new', 'in_kitchen', 'ready'])->count(),
                'readyOrders' => Order::query()->where('branch_id', $branchId)->where('status', 'ready')->count(),
                'todayRevenue' => (float) Invoice::query()->where('branch_id', $branchId)->whereDate('paid_at', today())->sum('total'),
                'lowStockCount' => InventoryItem::query()->where('branch_id', $branchId)->whereColumn('quantity', '<=', 'low_threshold')->count(),
                'currency_code' => $currencyCode,
            ],
        ]);
    }
}
