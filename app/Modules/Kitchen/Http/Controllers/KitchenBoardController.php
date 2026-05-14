<?php

namespace App\Modules\Kitchen\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Kitchen\Actions\MarkKitchenOrderReady;
use App\Modules\Orders\Models\Order;
use App\Modules\Shared\Http\Controllers\Concerns\EnsuresBranchAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class KitchenBoardController extends Controller
{
    use EnsuresBranchAccess;

    public function __construct(
        private readonly MarkKitchenOrderReady $markKitchenOrderReady,
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('kitchen/index', [
            'orders' => $this->queueData(),
        ]);
    }

    public function queue(): JsonResponse
    {
        return response()->json([
            'orders' => $this->queueData(),
        ]);
    }

    public function ready(Order $order): RedirectResponse
    {
        $this->ensureBranchAccess($order);

        try {
            $this->markKitchenOrderReady->handle($order);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Order marked ready.');
    }

    private function queueData()
    {
        return Order::query()
            ->where('branch_id', request()->user()->branch_id)
            ->where('status', 'in_kitchen')
            ->with(['table', 'items'])
            ->oldest()
            ->get();
    }
}
