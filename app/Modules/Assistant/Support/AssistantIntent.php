<?php

declare(strict_types=1);

namespace App\Modules\Assistant\Support;

final class AssistantIntent
{
    public const DASHBOARD_SUMMARY = 'dashboard.summary';
    public const ORDERS = 'orders.summary';
    public const TABLES = 'tables.summary';
    public const KITCHEN = 'kitchen.summary';
    public const INVOICES = 'invoices.summary';
    public const REVENUE = 'reports.revenue';
    public const INVENTORY = 'inventory.summary';
    public const MENU = 'menu.summary';
    public const USERS = 'users.summary';
    public const BRANCH_SETTINGS = 'branch.settings';
    public const MESSAGES = 'messages.summary';
    public const NOTIFICATIONS = 'notifications.summary';
    public const HELP = 'help';
    public const GENERAL_RESTAURANT_QUESTION = 'general.restaurant_question';
    public const UNKNOWN = 'unknown';
}
