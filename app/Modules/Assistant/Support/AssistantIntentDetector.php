<?php

declare(strict_types=1);

namespace App\Modules\Assistant\Support;

class AssistantIntentDetector
{
    public function detect(string $prompt): string
    {
        $text = $this->normalize($prompt);

        return match (true) {
            $this->containsAny($text, [
                'revenue', 'sales', 'income', 'analytics', 'report', 'reports', 'average order', 'top items',
                'الايرادات', 'الإيرادات', 'المبيعات', 'التقارير', 'التحليلات', 'متوسط الطلب', 'افضل الاصناف', 'أفضل الأصناف',
            ]) => AssistantIntent::REVENUE,

            $this->containsAny($text, [
                'inventory', 'stock', 'low stock', 'restock',
                'المخزون', 'نقص المخزون', 'اعادة تعبئة', 'إعادة تعبئة',
            ]) => AssistantIntent::INVENTORY,

            $this->containsAny($text, [
                'order', 'orders', 'table service',
                'طلبات', 'الطلبات', 'حالة الطلبات',
            ]) => AssistantIntent::ORDERS,

            $this->containsAny($text, [
                'table', 'tables', 'طاولة', 'طاولات',
            ]) => AssistantIntent::TABLES,

            $this->containsAny($text, [
                'kitchen', 'queue', 'ready',
                'المطبخ', 'الطابور', 'جاهز',
            ]) => AssistantIntent::KITCHEN,

            $this->containsAny($text, [
                'invoice', 'payment', 'billing', 'receipt', 'invoice status',
                'فاتورة', 'فواتير', 'الدفع', 'السداد',
            ]) => AssistantIntent::INVOICES,

            $this->containsAny($text, [
                'menu', 'item', 'category', 'menu availability',
                'المنيو', 'القائمة', 'الاصناف', 'الأصناف',
            ]) => AssistantIntent::MENU,

            $this->containsAny($text, [
                'staff', 'user', 'users', 'employee', 'team',
                'الموظفين', 'المستخدمين', 'الفريق',
            ]) => AssistantIntent::USERS,

            $this->containsAny($text, [
                'branch', 'settings', 'subscription', 'plan',
                'الفرع', 'الاعدادات', 'الإعدادات', 'الاشتراك', 'الخطة',
            ]) => AssistantIntent::BRANCH_SETTINGS,

            $this->containsAny($text, [
                'message', 'messages', 'conversation',
                'رسالة', 'رسائل', 'محادثة',
            ]) => AssistantIntent::MESSAGES,

            $this->containsAny($text, [
                'notification', 'notifications',
                'اشعار', 'إشعار', 'اشعارات', 'إشعارات',
            ]) => AssistantIntent::NOTIFICATIONS,

            $this->containsAny($text, [
                'help', 'what can you do', 'how can you help',
                'مساعدة', 'ماذا يمكنك', 'كيف تساعدني',
            ]) => AssistantIntent::HELP,

            $this->containsAny($text, [
                'summary', 'overview', 'today', 'status',
                'ملخص', 'نظرة عامة', 'اليوم', 'الحالة',
            ]) => AssistantIntent::DASHBOARD_SUMMARY,

            default => AssistantIntent::GENERAL_RESTAURANT_QUESTION,
        };
    }

    /**
     * @param list<string> $needles
     */
    private function containsAny(string $text, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($text, $this->normalize($needle))) {
                return true;
            }
        }

        return false;
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = str_replace(['أ', 'إ', 'آ'], 'ا', $value);
        $value = str_replace('ى', 'ي', $value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return $value;
    }
}
