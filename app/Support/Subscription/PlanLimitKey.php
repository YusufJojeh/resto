<?php

declare(strict_types=1);

namespace App\Support\Subscription;

final class PlanLimitKey
{
    public const MAX_USERS = 'max_users';

    public const MAX_TABLES = 'max_tables';

    public const MAX_MENU_ITEMS = 'max_menu_items';

    public const MAX_DAILY_ORDERS = 'max_daily_orders';

    /** Reserved for future multi-location accounts; not enforced in the current single-branch-per-tenant UX. */
    public const MAX_BRANCHES = 'max_branches';


    /**
     * Optional caps stored under `plans.limits` as string keys mapped to non-negative integers.
     *
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::MAX_USERS,
            self::MAX_TABLES,
            self::MAX_MENU_ITEMS,
            self::MAX_DAILY_ORDERS,
            self::MAX_BRANCHES,
        ];
    }

    /**
     * Limits enforced in application controllers / actions. Other keys remain informational in plan JSON.
     */
    public static function isEnforced(string $key): bool
    {
        return in_array($key, [
            self::MAX_USERS,
            self::MAX_TABLES,
            self::MAX_MENU_ITEMS,
            self::MAX_DAILY_ORDERS,
        ], true);
    }
}