<?php

namespace App\Modules\Reports\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Orders\Models\OrderItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $branchId = $request->user()->branch_id;
        $date = $request->string('date')->toString() ?: today()->toDateString();

        $invoiceQuery = Invoice::query()
            ->where('branch_id', $branchId)
            ->whereDate('paid_at', $date);

        $topItems = OrderItem::query()
            ->selectRaw('menu_item_name, SUM(quantity) as total_quantity, SUM(subtotal) as total_revenue')
            ->whereHas('order.invoice', fn ($query) => $query->where('branch_id', $branchId)->whereDate('paid_at', $date))
            ->groupBy('menu_item_name')
            ->orderByDesc('total_quantity')
            ->limit(10)
            ->get();

        return Inertia::render('reports/index', [
            'selectedDate' => $date,
            'summary' => [
                'paidInvoices' => $invoiceQuery->count(),
                'revenue' => (float) $invoiceQuery->sum('total'),
                'averageOrderValue' => round((float) $invoiceQuery->avg('total'), 2),
                'cashRevenue' => (float) (clone $invoiceQuery)->where('payment_method', 'cash')->sum('total'),
                'cardRevenue' => (float) (clone $invoiceQuery)->where('payment_method', 'card')->sum('total'),
            ],
            'topItems' => $topItems,
        ]);
    }
}
