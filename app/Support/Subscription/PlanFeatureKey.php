<?php

declare(strict_types=1);

namespace App\Support\Subscription;

final class PlanFeatureKey
{
    public const ORDERS = 'orders';

    public const TABLES = 'tables';

    public const KITCHEN = 'kitchen';

    public const REPORTS = 'reports';

    public const STAFF_MANAGEMENT = 'staff_management';

    public const BRANCH_SETTINGS = 'branch_settings';

    public const MENU_MANAGEMENT = 'menu_management';

    public const INVENTORY = 'inventory';

    public const BILLING = 'billing';

    public const ADVANCED_ANALYTICS = 'advanced_analytics';

    /**
     * Feature flags stored under `plans.features` as string keys mapped to booleans.
     *
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::ORDERS,
            self::TABLES,
            self::KITCHEN,
            self::REPORTS,
            self::STAFF_MANAGEMENT,
            self::BRANCH_SETTINGS,
            self::MENU_MANAGEMENT,
            self::INVENTORY,
            self::BILLING,
            self::ADVANCED_ANALYTICS,
        ];
    }
}
