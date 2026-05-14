<?php

declare(strict_types=1);

namespace App\Modules\Assistant\Support;

final class AssistantFallbackResponder
{
    public function respond(array $context, string $intent, string $locale): string
    {
        $currentModule = $context['current_module']['label'] ?? 'current module';
        $primary = $context['allowed_context']['primary'] ?? [];
        $denied = $context['denied_context'] ?? [];
        $summary = $this->summarizePrimary($primary, $locale, $currentModule);

        if ($locale === 'ar') {
            $lines = ['محرك الذكاء غير متاح مؤقتاً، لكن يمكنني تلخيص ما هو متاح بشكل آمن.'];
            $lines[] = $summary;

            if ($denied !== []) {
                $lines[] = 'بعض البيانات غير متاحة لك حسب صلاحياتك الحالية.';
            }

            $lines[] = 'يمكنني أيضاً مساعدتك بسؤال أكثر تحديداً داخل هذا النطاق.';

            return implode("\n\n", $lines);
        }

        $lines = ['The AI engine is temporarily unavailable, but I can still summarize what is safely available.'];
        $lines[] = $summary;

        if ($denied !== []) {
            $lines[] = 'Some requested data is not available under your current permissions.';
        }

        $lines[] = 'You can also ask a more specific question within this scope.';

        return implode("\n\n", $lines);
    }

    private function summarizePrimary(array $primary, string $locale, string $currentModule): string
    {
        if (isset($primary['stats'])) {
            $stats = $primary['stats'];

            return $locale === 'ar'
                ? sprintf(
                    'ملخص اللوحة: %d طاولات، %d طلبات نشطة، %d طلبات جاهزة، %.2f %s إيراد اليوم، و%d عناصر منخفضة المخزون.',
                    (int) ($stats['tables'] ?? 0),
                    (int) ($stats['active_orders'] ?? 0),
                    (int) ($stats['ready_orders'] ?? 0),
                    (float) ($stats['today_revenue'] ?? 0),
                    (string) ($stats['currency'] ?? ''),
                    (int) ($stats['low_stock_count'] ?? 0),
                )
                : (string) ($primary['summary'] ?? "Current context: {$currentModule}.");
        }

        if (isset($primary['status_counts'])) {
            return $locale === 'ar'
                ? 'تم تجهيز ملخص للحالات المسموح لك برؤيتها في هذا القسم.'
                : (string) ($primary['summary'] ?? "Current context: {$currentModule}.");
        }

        if (isset($primary['queue_count'])) {
            return $locale === 'ar'
                ? 'تم تجهيز ملخص لطابور المطبخ الحالي.'
                : (string) ($primary['summary'] ?? "Current context: {$currentModule}.");
        }

        if (isset($primary['metrics'])) {
            return $locale === 'ar'
                ? 'تم تجهيز ملخص مالي آمن حسب صلاحياتك الحالية.'
                : (string) ($primary['summary'] ?? "Current context: {$currentModule}.");
        }

        if (isset($primary['low_stock_count'])) {
            return $locale === 'ar'
                ? 'تم تجهيز ملخص لعناصر المخزون المنخفضة في هذا الفرع.'
                : (string) ($primary['summary'] ?? "Current context: {$currentModule}.");
        }

        if (isset($primary['total_users'])) {
            return $locale === 'ar'
                ? 'تم تجهيز ملخص لفريق العمل في هذا الفرع.'
                : (string) ($primary['summary'] ?? "Current context: {$currentModule}.");
        }

        return $locale === 'ar'
            ? 'السياق الحالي: ' . $currentModule . '.'
            : 'Current context: ' . $currentModule . '.';
    }
}
