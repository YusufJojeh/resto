<?php

declare(strict_types=1);

namespace App\Modules\Assistant\Support;

use App\Models\User;
use App\Modules\Assistant\Models\AssistantConversation;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Branches\Models\Branch;
use App\Modules\Inventory\Models\InventoryItem;
use App\Modules\Messaging\Models\Conversation;
use App\Modules\Orders\Models\Order;
use App\Modules\Tables\Models\RestaurantTable;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class AssistantContextBuilder
{
    public function __construct(
        private readonly AssistantAccessMap $accessMap,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(
        User $user,
        AssistantConversation $conversation,
        string $intent,
        string $prompt,
        ?string $currentPath = null,
    ): array {
        $role = $user->getRoleNames()->first();
        $branch = Branch::query()->with('plan')->find($conversation->branch_id);
        $module = $this->accessMap->moduleForPath($currentPath);
        $capabilities = $this->accessMap->forRole($role);
        $generatedAt = CarbonImmutable::now();

        $context = [
            'user_profile' => [
                'id' => (int) $user->id,
                'name' => $user->name,
                'role' => $role,
                'branch_id' => $user->branch_id,
                'branch_name' => $branch?->name,
                'language' => $this->detectLocale($prompt),
            ],
            'role_capability_summary' => [
                'accessible_modules' => $capabilities['accessible_modules'],
                'restricted_modules' => $capabilities['restricted_modules'],
                'assistant_use_cases' => $capabilities['assistant_use_cases'],
                'safe_context' => $capabilities['safe_context'],
            ],
            'current_module' => $module,
            'data_freshness' => [
                'generated_at' => $generatedAt->toIso8601String(),
                'date_range' => [
                    'today' => $generatedAt->toDateString(),
                    'month_start' => $generatedAt->startOfMonth()->toDateString(),
                    'month_end' => $generatedAt->endOfMonth()->toDateString(),
                ],
            ],
            'denied_context' => [],
            'allowed_context' => [],
        ];

        $requestedModule = $this->moduleForIntent($intent, $module['key']);

        if (! $this->accessMap->canAccessModule($role, $requestedModule)) {
            $context['denied_context'][] = $this->deniedReason($requestedModule);
        } else {
            $context['allowed_context']['primary'] = $this->buildModuleContext($requestedModule, $user, $branch);
        }

        if ($requestedModule !== $module['key'] && $this->accessMap->canAccessModule($role, $module['key'])) {
            $moduleContext = $this->buildModuleContext($module['key'], $user, $branch);
            $context['allowed_context']['current_module'] = $moduleContext;
            $context['allowed_context']['primary'] ??= $moduleContext;
        }

        if ($context['allowed_context'] === [] && $this->accessMap->canAccessModule($role, 'dashboard')) {
            $context['allowed_context']['primary'] = $this->buildModuleContext('dashboard', $user, $branch);
        }

        return $context;
    }

    private function moduleForIntent(string $intent, string $currentModule): string
    {
        return match ($intent) {
            AssistantIntent::ORDERS => 'orders',
            AssistantIntent::TABLES => 'tables',
            AssistantIntent::KITCHEN => 'kitchen',
            AssistantIntent::INVOICES => 'invoices',
            AssistantIntent::REVENUE => 'reports',
            AssistantIntent::INVENTORY => 'inventory',
            AssistantIntent::MENU => 'menu',
            AssistantIntent::USERS => 'users',
            AssistantIntent::BRANCH_SETTINGS => 'branch_settings',
            AssistantIntent::MESSAGES => 'messages',
            AssistantIntent::NOTIFICATIONS => 'notifications',
            AssistantIntent::DASHBOARD_SUMMARY, AssistantIntent::HELP, AssistantIntent::GENERAL_RESTAURANT_QUESTION => $currentModule,
            default => $currentModule,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function buildModuleContext(string $module, User $user, ?Branch $branch): array
    {
        return match ($module) {
            'dashboard' => $this->dashboardContext($user, $branch),
            'tables' => $this->tablesContext($user),
            'orders' => $this->ordersContext($user),
            'kitchen' => $this->kitchenContext($user),
            'invoices', 'reports' => $this->invoiceReportContext($user, $module === 'reports'),
            'inventory' => $this->inventoryContext($user),
            'menu' => $this->menuContext($user),
            'users' => $this->usersContext($user),
            'branch_settings' => $this->branchSettingsContext($branch, $user),
            'messages' => $this->messagesContext($user),
            'notifications' => $this->notificationsContext($user),
            default => [
                'summary' => 'No additional module context is available.',
            ],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function dashboardContext(User $user, ?Branch $branch): array
    {
        $branchId = (int) $user->branch_id;

        $stats = [
            'tables' => RestaurantTable::query()->where('branch_id', $branchId)->count(),
            'active_orders' => Order::query()->where('branch_id', $branchId)->whereIn('status', ['new', 'in_kitchen', 'ready'])->count(),
            'ready_orders' => Order::query()->where('branch_id', $branchId)->where('status', 'ready')->count(),
            'today_revenue' => round((float) Invoice::query()->where('branch_id', $branchId)->whereDate('paid_at', today())->sum('total'), 2),
            'low_stock_count' => InventoryItem::query()->where('branch_id', $branchId)->whereColumn('quantity', '<=', 'low_threshold')->count(),
            'currency' => $branch?->currency_code ?? 'USD',
        ];

        return [
            'summary' => sprintf(
                'Dashboard summary: %d tables, %d active orders, %d ready orders, %s %.2f revenue today, %d low-stock items.',
                $stats['tables'],
                $stats['active_orders'],
                $stats['ready_orders'],
                $stats['currency'],
                $stats['today_revenue'],
                $stats['low_stock_count'],
            ),
            'stats' => $stats,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function tablesContext(User $user): array
    {
        $rows = RestaurantTable::query()
            ->where('branch_id', $user->branch_id)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->all();

        $available = (int) ($rows['available'] ?? 0);
        $occupied = (int) ($rows['occupied'] ?? 0);
        $reserved = (int) ($rows['reserved'] ?? 0);

        return [
            'summary' => "Table summary: {$available} available, {$occupied} occupied, {$reserved} reserved.",
            'status_counts' => $rows,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function ordersContext(User $user): array
    {
        $query = Order::query()
            ->where('branch_id', $user->branch_id)
            ->with(['table:id,number', 'user:id,name']);

        if ($user->hasRole('waiter')) {
            $query->where('user_id', $user->id);
        }

        if ($user->hasRole('cashier')) {
            $query->whereIn('status', ['ready', 'served']);
        }

        $statusCounts = (clone $query)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->all();

        $recent = $query->latest('id')->limit(5)->get()->map(fn (Order $order): array => [
            'id' => $order->id,
            'status' => $order->status->value,
            'table_number' => $order->table?->number,
            'owner_name' => $user->hasAnyRole(['admin', 'manager']) ? $order->user?->name : null,
            'created_at' => $order->created_at?->toIso8601String(),
        ])->values()->all();

        return [
            'summary' => 'Order summary prepared for your visible scope.',
            'status_counts' => $statusCounts,
            'recent_orders' => $recent,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function kitchenContext(User $user): array
    {
        $orders = Order::query()
            ->where('branch_id', $user->branch_id)
            ->where('status', 'in_kitchen')
            ->with('table:id,number')
            ->oldest()
            ->limit(5)
            ->get();

        return [
            'summary' => 'Kitchen summary prepared for the current queue.',
            'queue_count' => $orders->count(),
            'queue' => $orders->map(fn (Order $order): array => [
                'id' => $order->id,
                'table_number' => $order->table?->number,
                'created_at' => $order->created_at?->toIso8601String(),
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function invoiceReportContext(User $user, bool $includeReports): array
    {
        $branchId = (int) $user->branch_id;
        $today = today()->toDateString();

        $base = Invoice::query()->where('branch_id', $branchId);
        $todayInvoices = (clone $base)->whereDate('paid_at', $today);

        $context = [
            'paid_invoices_today' => $todayInvoices->count(),
            'revenue_today' => round((float) $todayInvoices->sum('total'), 2),
            'unpaid_invoices' => (clone $base)->whereNull('paid_at')->count(),
            'currency' => Branch::query()->find($branchId)?->currency_code ?? 'USD',
        ];

        if ($includeReports) {
            $context['cash_revenue_today'] = round((float) (clone $todayInvoices)->where('payment_method', 'cash')->sum('total'), 2);
            $context['card_revenue_today'] = round((float) (clone $todayInvoices)->where('payment_method', 'card')->sum('total'), 2);
            $context['average_order_value_today'] = round((float) (clone $todayInvoices)->avg('total'), 2);
        }

        return [
            'summary' => $includeReports
                ? 'Revenue and reports summary prepared for the current branch.'
                : 'Invoice summary prepared for the current branch.',
            'metrics' => $context,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function inventoryContext(User $user): array
    {
        $items = InventoryItem::query()
            ->where('branch_id', $user->branch_id)
            ->whereColumn('quantity', '<=', 'low_threshold')
            ->orderBy('quantity')
            ->limit(5)
            ->get(['id', 'name', 'quantity', 'low_threshold']);

        return [
            'summary' => 'Inventory summary prepared for low-stock monitoring.',
            'low_stock_count' => $items->count(),
            'low_stock_items' => $items->map(fn (InventoryItem $item): array => [
                'id' => $item->id,
                'name' => $item->name,
                'quantity' => (float) $item->quantity,
                'low_threshold' => (float) $item->low_threshold,
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function menuContext(User $user): array
    {
        $query = DB::table('menu_items')->where('branch_id', $user->branch_id);

        return [
            'summary' => 'Menu summary prepared for this branch.',
            'item_count' => (clone $query)->count(),
            'available_count' => (clone $query)->where('is_available', true)->count(),
            'unavailable_count' => (clone $query)->where('is_available', false)->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function usersContext(User $user): array
    {
        $users = User::query()
            ->where('branch_id', $user->branch_id)
            ->get(['id', 'is_active']);

        $roleCounts = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->join('users', 'users.id', '=', 'model_has_roles.model_id')
            ->where('model_has_roles.model_type', User::class)
            ->where('users.branch_id', $user->branch_id)
            ->select('roles.name', DB::raw('COUNT(*) as count'))
            ->groupBy('roles.name')
            ->pluck('count', 'roles.name')
            ->all();

        return [
            'summary' => 'Staff summary prepared for this branch.',
            'total_users' => $users->count(),
            'active_users' => $users->where('is_active', true)->count(),
            'inactive_users' => $users->where('is_active', false)->count(),
            'role_counts' => $roleCounts,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function branchSettingsContext(?Branch $branch, User $user): array
    {
        if (! $branch instanceof Branch) {
            return ['summary' => 'Branch settings are unavailable because no branch is attached.'];
        }

        return [
            'summary' => 'Branch settings summary prepared for the current branch.',
            'branch' => [
                'name' => $branch->name,
                'currency_code' => $branch->currency_code,
                'is_active' => $branch->is_active,
                'public_slug' => $branch->public_slug,
                'subscription_status' => $branch->subscription_status?->value,
                'plan_name' => $branch->plan?->name,
                'can_manage_subscription' => $user->hasRole('admin'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function messagesContext(User $user): array
    {
        $conversationCount = Conversation::query()
            ->where('branch_id', $user->branch_id)
            ->whereHas('conversationParticipants', fn ($query) => $query->where('user_id', $user->id))
            ->count();

        return [
            'summary' => 'Messaging summary prepared for your account.',
            'conversation_count' => $conversationCount,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function notificationsContext(User $user): array
    {
        return [
            'summary' => 'Notification summary prepared for your account.',
            'unread_count' => $user->unreadNotifications()->count(),
        ];
    }

    private function deniedReason(string $module): string
    {
        return match ($module) {
            'users' => 'You do not have permission to view staff management data.',
            'reports' => 'You do not have permission to view report-level financial analytics.',
            'inventory' => 'You do not have permission to view inventory details.',
            'branch_settings', 'plans' => 'You do not have permission to view branch configuration details.',
            default => 'You do not have permission to view this module.',
        };
    }

    private function detectLocale(string $prompt): string
    {
        return preg_match('/\p{Arabic}/u', $prompt) === 1 ? 'ar' : 'en';
    }
}
