<?php

declare(strict_types=1);

namespace App\Modules\Assistant\Support;

final class AssistantAccessMap
{
    /**
     * @return array<string, mixed>
     */
    public function forRole(?string $role): array
    {
        return match ($role) {
            'admin' => [
                'role_name' => 'admin',
                'accessible_modules' => ['dashboard', 'tables', 'orders', 'kitchen', 'invoices', 'menu', 'inventory', 'reports', 'messages', 'notifications', 'assistant', 'users', 'branch_settings', 'plans'],
                'restricted_modules' => [],
                'sensitive_data' => ['cross_branch_data'],
                'assistant_use_cases' => [
                    'System overview and branch-level operations summary',
                    'Revenue and report snapshots for the current branch',
                    'Staff, menu, stock, and branch configuration guidance',
                ],
                'safe_context' => ['branch-scoped operational summaries', 'staff counts by role', 'inventory alerts', 'invoice and report summaries'],
            ],
            'manager' => [
                'role_name' => 'manager',
                'accessible_modules' => ['dashboard', 'tables', 'orders', 'kitchen', 'invoices', 'menu', 'inventory', 'reports', 'messages', 'notifications', 'assistant', 'branch_settings'],
                'restricted_modules' => ['users', 'plans'],
                'sensitive_data' => ['cross_branch_data', 'admin_only_plan_management'],
                'assistant_use_cases' => [
                    'Branch activity and service-flow summary',
                    'Revenue, kitchen queue, stock, and menu guidance',
                    'Operational next steps for the current shift',
                ],
                'safe_context' => ['branch-scoped operational summaries', 'inventory alerts', 'invoice and report summaries', 'branch settings summary'],
            ],
            'waiter' => [
                'role_name' => 'waiter',
                'accessible_modules' => ['dashboard', 'tables', 'orders', 'messages', 'notifications', 'assistant'],
                'restricted_modules' => ['kitchen', 'invoices', 'menu', 'inventory', 'reports', 'users', 'branch_settings', 'plans'],
                'sensitive_data' => ['cross_branch_data', 'other_staff_orders', 'financial_analytics', 'staff_management'],
                'assistant_use_cases' => [
                    'Own orders and table workflow guidance',
                    'Current service priorities for the shift',
                    'Help understanding what actions are available next',
                ],
                'safe_context' => ['own order summaries', 'table status counts', 'dashboard KPIs already visible in the product'],
            ],
            'cashier' => [
                'role_name' => 'cashier',
                'accessible_modules' => ['dashboard', 'tables', 'orders', 'invoices', 'messages', 'notifications', 'assistant'],
                'restricted_modules' => ['kitchen', 'menu', 'inventory', 'reports', 'users', 'branch_settings', 'plans'],
                'sensitive_data' => ['cross_branch_data', 'staff_management', 'reporting_analytics'],
                'assistant_use_cases' => [
                    'Ready-to-bill order and invoice workflow guidance',
                    'Payment status and cashier-facing operational summary',
                    'Help with invoice-related next steps',
                ],
                'safe_context' => ['cashier-visible order summaries', 'invoice status summaries', 'dashboard KPIs already visible in the product'],
            ],
            'kitchen' => [
                'role_name' => 'kitchen',
                'accessible_modules' => ['dashboard', 'kitchen', 'messages', 'notifications', 'assistant'],
                'restricted_modules' => ['tables', 'orders', 'invoices', 'menu', 'inventory', 'reports', 'users', 'branch_settings', 'plans'],
                'sensitive_data' => ['cross_branch_data', 'financial_data', 'staff_management'],
                'assistant_use_cases' => [
                    'Kitchen queue and readiness guidance',
                    'Shift priorities based on active queue',
                    'Help understanding what can be marked ready next',
                ],
                'safe_context' => ['kitchen queue summary', 'dashboard KPIs already visible in the product'],
            ],
            default => [
                'role_name' => $role ?? 'unknown',
                'accessible_modules' => ['assistant'],
                'restricted_modules' => ['dashboard', 'tables', 'orders', 'kitchen', 'invoices', 'menu', 'inventory', 'reports', 'users', 'branch_settings', 'plans'],
                'sensitive_data' => ['all_operational_data'],
                'assistant_use_cases' => ['General product guidance only'],
                'safe_context' => ['conversation-local help only'],
            ],
        };
    }

    public function canAccessModule(?string $role, string $module): bool
    {
        return in_array($module, $this->forRole($role)['accessible_modules'], true);
    }

    /**
     * @return array{key:string,label:string,path:?string}
     */
    public function moduleForPath(?string $path): array
    {
        $normalized = '/' . ltrim((string) $path, '/');

        return match (true) {
            str_starts_with($normalized, '/dashboard') => ['key' => 'dashboard', 'label' => 'Dashboard', 'path' => $normalized],
            str_starts_with($normalized, '/tables') => ['key' => 'tables', 'label' => 'Tables', 'path' => $normalized],
            str_starts_with($normalized, '/orders') => ['key' => 'orders', 'label' => 'Orders', 'path' => $normalized],
            str_starts_with($normalized, '/kitchen') => ['key' => 'kitchen', 'label' => 'Kitchen', 'path' => $normalized],
            str_starts_with($normalized, '/invoices') => ['key' => 'invoices', 'label' => 'Invoices', 'path' => $normalized],
            str_starts_with($normalized, '/inventory') => ['key' => 'inventory', 'label' => 'Inventory', 'path' => $normalized],
            str_starts_with($normalized, '/menu/categories'), str_starts_with($normalized, '/menu/items') => ['key' => 'menu', 'label' => 'Menu', 'path' => $normalized],
            str_starts_with($normalized, '/reports') => ['key' => 'reports', 'label' => 'Reports', 'path' => $normalized],
            str_starts_with($normalized, '/users') => ['key' => 'users', 'label' => 'Users', 'path' => $normalized],
            str_starts_with($normalized, '/messages') => ['key' => 'messages', 'label' => 'Messages', 'path' => $normalized],
            str_starts_with($normalized, '/notifications') => ['key' => 'notifications', 'label' => 'Notifications', 'path' => $normalized],
            str_starts_with($normalized, '/settings/branch') => ['key' => 'branch_settings', 'label' => 'Branch Settings', 'path' => $normalized],
            str_starts_with($normalized, '/settings/plans') => ['key' => 'plans', 'label' => 'Plans', 'path' => $normalized],
            str_starts_with($normalized, '/assistant') => ['key' => 'assistant', 'label' => 'Assistant', 'path' => $normalized],
            default => ['key' => 'dashboard', 'label' => 'Dashboard', 'path' => $normalized === '/' ? '/dashboard' : $normalized],
        };
    }

    /**
     * @return list<string>
     */
    public function starterPrompts(?string $role, string $locale, ?string $module = null): array
    {
        $isArabic = $locale === 'ar';

        $moduleSpecific = match ($module) {
            'orders' => $isArabic
                ? ['لخص حالة الطلبات المسموح لي برؤيتها', 'ما الخطوة التالية في سير الطلبات؟', 'اعرض الطلبات التي تحتاج متابعة الآن']
                : ['Summarize the orders I am allowed to see', 'What is the next step in the order flow?', 'Show orders that need attention now'],
            'kitchen' => $isArabic
                ? ['لخص طابور المطبخ الحالي', 'ما الطلبات الجاهزة قريباً؟', 'ما الذي يحتاج متابعة في المطبخ الآن؟']
                : ['Summarize the current kitchen queue', 'Which orders are close to ready?', 'What needs attention in the kitchen now?'],
            'invoices' => $isArabic
                ? ['لخص حالة الفواتير المتاحة لي', 'ما الطلبات الجاهزة للفوترة؟', 'اعرض المدفوع وغير المدفوع اليوم']
                : ['Summarize the invoices I can access', 'Which orders are ready for billing?', 'Show paid and unpaid invoices for today'],
            'inventory' => $isArabic
                ? ['اعرض تنبيهات المخزون المنخفض', 'ما الأصناف التي تحتاج إعادة تعبئة؟', 'لخص حالة المخزون لهذا الفرع']
                : ['Show low-stock alerts', 'Which items need restocking?', 'Summarize inventory status for this branch'],
            'reports' => $isArabic
                ? ['لخص أداء اليوم', 'ما الإيراد ومتوسط الطلبات اليوم؟', 'اعرض أفضل الأصناف اليوم']
                : ['Summarize today’s performance', 'What are today’s revenue and average order value?', 'Show top items today'],
            default => $this->starterPromptsByRole($role, $isArabic),
        };

        return array_slice($moduleSpecific, 0, 3);
    }

    /**
     * @return list<string>
     */
    private function starterPromptsByRole(?string $role, bool $isArabic): array
    {
        return match ($role) {
            'admin' => $isArabic
                ? ['اعرض ملخص النظام اليوم', 'لخص الأداء التشغيلي لهذا الفرع', 'ما التنبيهات التي تحتاج قراراً إدارياً؟']
                : ['Show today’s system summary', 'Summarize branch operations', 'What needs admin attention today?'],
            'manager' => $isArabic
                ? ['لخص نشاط الفرع اليوم', 'ما الأولويات التشغيلية الآن؟', 'اعرض المخزون والطلبات التي تحتاج متابعة']
                : ['Summarize branch activity today', 'What are the operational priorities now?', 'Show stock and orders that need follow-up'],
            'waiter' => $isArabic
                ? ['ما الذي يجب أن أتعامل معه الآن؟', 'لخص طلباتي الحالية', 'اعرض حالة الطاولات والطلبات الخاصة بي']
                : ['What should I handle next?', 'Summarize my current orders', 'Show my table and order status'],
            'cashier' => $isArabic
                ? ['ما الطلبات الجاهزة للدفع؟', 'لخص حالة الفواتير اليوم', 'ما الذي يحتاج متابعة من جهة الكاشير؟']
                : ['Which orders are ready for payment?', 'Summarize invoice status today', 'What needs cashier follow-up now?'],
            'kitchen' => $isArabic
                ? ['لخص طابور المطبخ الآن', 'ما الذي يجب تجهيزه أولاً؟', 'اعرض الطلبات التي تأخرت في المطبخ']
                : ['Summarize the kitchen queue now', 'What should be prepared first?', 'Show orders that are delayed in the kitchen'],
            default => $isArabic
                ? ['لخص ما يمكنني الوصول إليه', 'اشرح ما الذي يمكنك مساعدتي فيه', 'ما المعلومات المتاحة لي الآن؟']
                : ['Summarize what I can access', 'Explain what you can help me with', 'What information is available to me now?'],
        };
    }
}
