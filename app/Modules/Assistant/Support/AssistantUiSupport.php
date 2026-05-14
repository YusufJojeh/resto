<?php

declare(strict_types=1);

namespace App\Modules\Assistant\Support;

use App\Models\User;

final class AssistantUiSupport
{
    public function __construct(
        private readonly AssistantAccessMap $accessMap,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(?User $user, ?string $currentPath, string $locale): array
    {
        $role = $user?->getRoleNames()->first();
        $module = $this->accessMap->moduleForPath($currentPath);

        return [
            'enabled' => (bool) config('assistant.enabled', true) && (bool) config('features.assistant.enabled', true),
            'read_only' => true,
            'role' => $role,
            'current_module' => $module,
            'permission_notice' => $locale === 'ar'
                ? 'هذا المساعد للقراءة فقط، وتختلف الإجابات حسب صلاحياتك وبيانات الفرع المسموح بها.'
                : 'This assistant is read-only, and answers depend on your permissions and allowed branch data.',
            'module_notice' => $locale === 'ar'
                ? 'يمكنه المساعدة ضمن الصفحة الحالية عندما تكون البيانات متاحة ومسموحاً بها.'
                : 'It can help with the current module when data is available and permitted.',
            'starter_prompts' => $this->accessMap->starterPrompts($role, $locale, $module['key']),
            'loading_messages' => $locale === 'ar'
                ? ['جارٍ تحليل البيانات المسموح بها…', 'جارٍ التحقق من صلاحياتك…', 'جارٍ تجهيز الإجابة…']
                : ['Analyzing allowed data…', 'Checking your permissions…', 'Preparing answer…'],
            'errors' => [
                'forbidden' => $locale === 'ar'
                    ? 'ليست لديك صلاحية للوصول إلى هذه البيانات.'
                    : 'You do not have permission to access this data.',
                'throttled' => $locale === 'ar'
                    ? 'تم تجاوز الحد المؤقت للرسائل. حاول مرة أخرى بعد قليل.'
                    : 'You have reached the temporary message limit. Please try again shortly.',
                'unavailable' => $locale === 'ar'
                    ? 'محرك الذكاء غير متاح مؤقتاً.'
                    : 'The AI engine is temporarily unavailable.',
                'validation' => $locale === 'ar'
                    ? 'يرجى مراجعة الرسالة وإعادة المحاولة.'
                    : 'Please review your message and try again.',
                'network' => $locale === 'ar'
                    ? 'تعذر الاتصال بالخادم. تحقق من الشبكة ثم أعد المحاولة.'
                    : 'The server could not be reached. Check your network and try again.',
            ],
        ];
    }
}
