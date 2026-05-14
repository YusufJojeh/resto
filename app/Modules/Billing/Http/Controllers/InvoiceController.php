<?php

namespace App\Modules\Billing\Http\Controllers;

use App\Enums\InvoicePaymentMethod;
use App\Http\Controllers\Controller;
use App\Modules\Billing\Actions\CreateInvoiceFromOrder;
use App\Modules\Billing\Actions\MarkInvoicePaid;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Billing\Requests\PayInvoiceRequest;
use App\Modules\Orders\Models\Order;
use App\Modules\Shared\Http\Controllers\Concerns\EnsuresBranchAccess;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class InvoiceController extends Controller
{
    use EnsuresBranchAccess;

    public function __construct(
        private readonly CreateInvoiceFromOrder $createInvoiceFromOrder,
        private readonly MarkInvoicePaid $markInvoicePaid,
    ) {
    }

    public function index(): Response
    {
        $paginator = Invoice::query()
            ->where('branch_id', request()->user()->branch_id)
            ->with(['order.table', 'creator'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('invoices/index', [
            'invoices' => $paginator->items(),
            'invoicesPagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function show(Invoice $invoice): Response
    {
        $this->ensureBranchAccess($invoice);

        return Inertia::render('invoices/show', [
            'invoice' => $invoice->load(['order.items', 'order.table', 'creator', 'branch']),
        ]);
    }

    public function store(Order $order): RedirectResponse
    {
        $this->ensureBranchAccess($order);

        try {
            $invoice = $this->createInvoiceFromOrder->handle(request()->user(), $order);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return to_route('invoices.show', $invoice)->with('success', 'Invoice created.');
    }

    public function pay(PayInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->ensureBranchAccess($invoice);

        try {
            $this->markInvoicePaid->handle($invoice, InvoicePaymentMethod::from($request->validated('payment_method')));
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return to_route('invoices.show', $invoice)->with('success', 'Payment recorded.');
    }
}
