<?php

namespace App\Modules\Orders\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Modules\Branches\Models\Branch;
use App\Modules\Menu\Models\MenuCategory;
use App\Modules\Orders\Actions\AddOrderItems;
use App\Modules\Orders\Actions\CancelOrder;
use App\Modules\Orders\Actions\CreateOrder;
use App\Modules\Orders\Actions\SubmitOrderToKitchen;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Requests\AddOrderItemsRequest;
use App\Support\Subscription\PlanLimitKey;
use App\Modules\Orders\Requests\CancelOrderRequest;
use App\Modules\Orders\Requests\StoreOrderRequest;
use App\Modules\Shared\Http\Controllers\Concerns\EnsuresBranchAccess;
use App\Modules\Tables\Models\RestaurantTable;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class OrderController extends Controller
{
    use EnsuresBranchAccess;

    public function __construct(
        private readonly CreateOrder $createOrder,
        private readonly AddOrderItems $addOrderItems,
        private readonly SubmitOrderToKitchen $submitOrderToKitchen,
        private readonly CancelOrder $cancelOrder,
    ) {
    }

    public function index(): Response
    {
        $user = request()->user();
        $query = Order::query()
            ->where('branch_id', $user->branch_id)
            ->with(['table', 'user', 'items', 'invoice'])
            ->latest();

        if ($user->hasRole(UserRole::Waiter->value)) {
            $query->where('user_id', $user->id);
        }

        if ($user->hasRole(UserRole::Cashier->value)) {
            $query->whereIn('status', [OrderStatus::Ready->value, OrderStatus::Served->value]);
        }

        $paginator = $query->paginate(20)->withQueryString();

        return Inertia::render('orders/index', [
            'orders' => $paginator->items(),
            'ordersPagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $branchId = request()->user()->branch_id;
        $selectedTableId = $request->integer('table_id') ?: null;

        if ($selectedTableId !== null) {
            $selectedTable = RestaurantTable::query()
                ->whereKey($selectedTableId)
                ->where('branch_id', $branchId)
                ->where('status', 'available')
                ->firstOrFail();
        }

        return Inertia::render('orders/create', [
            'tables' => RestaurantTable::query()
                ->where('branch_id', $branchId)
                ->where('status', 'available')
                ->orderBy('number')
                ->get(),
            'selectedTableId' => $selectedTableId,
            'categories' => MenuCategory::query()
                ->where('branch_id', $branchId)
                ->with(['items' => fn ($query) => $query->where('is_available', true)->orderBy('name')])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $user = $request->user();

        $branch = Branch::query()->with('plan')->findOrFail((int) $user->branch_id);

        $ordersTodayCount = Order::query()
            ->where('branch_id', $branch->id)
            ->whereDate('created_at', today())
            ->count();

        if ($branch->isAtOrOverPlanLimit(PlanLimitKey::MAX_DAILY_ORDERS, $ordersTodayCount)) {
            return back()->with('error', 'Your plan\'s maximum daily orders has been reached for today.')->withInput();
        }

        $order = $this->createOrder->handle($user, $request->validated());

        return to_route('orders.show', $order)->with('success', 'Order created.');
    }

    public function show(Order $order): Response
    {
        $this->ensureBranchAccess($order);
        $this->authorizeView($order);

        return Inertia::render('orders/show', [
            'order' => $order->load(['table', 'user', 'items', 'invoice']),
            'categories' => MenuCategory::query()
                ->where('branch_id', request()->user()->branch_id)
                ->with(['items' => fn ($query) => $query->where('is_available', true)->orderBy('name')])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function addItems(AddOrderItemsRequest $request, Order $order): RedirectResponse
    {
        $this->ensureBranchAccess($order);
        $this->authorizeOwnerMutation($order);

        try {
            $this->addOrderItems->handle($request->user(), $order, $request->validated('items'));
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return to_route('orders.show', $order)->with('success', 'Items added to order.');
    }

    public function submit(Order $order): RedirectResponse
    {
        $this->ensureBranchAccess($order);
        $this->authorizeOwnerMutation($order);

        try {
            $this->submitOrderToKitchen->handle($order);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return to_route('orders.show', $order)->with('success', 'Order sent to kitchen.');
    }

    public function cancel(CancelOrderRequest $request, Order $order): RedirectResponse
    {
        $this->ensureBranchAccess($order);
        $this->authorizeCancel($order);

        try {
            $this->cancelOrder->handle($order, $request->validated('reason'));
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return to_route('orders.index')->with('success', 'Order cancelled.');
    }

    private function authorizeView(Order $order): void
    {
        $user = request()->user();
        abort_unless(
            $user->hasAnyRole([UserRole::Admin->value, UserRole::Manager->value, UserRole::Cashier->value])
            || $order->user_id === $user->id,
            403,
        );
    }

    private function authorizeOwnerMutation(Order $order): void
    {
        $user = request()->user();
        abort_unless(
            $user->hasAnyRole([UserRole::Admin->value, UserRole::Manager->value])
            || $order->user_id === $user->id,
            403,
        );
    }

    private function authorizeCancel(Order $order): void
    {
        $user = request()->user();

        $allowed = $user->hasAnyRole([UserRole::Admin->value, UserRole::Manager->value])
            || ($user->hasRole(UserRole::Waiter->value) && $order->user_id === $user->id && $order->status === OrderStatus::New);

        abort_unless($allowed, 403);
    }
}
