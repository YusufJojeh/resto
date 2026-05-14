<?php

declare(strict_types=1);

namespace App\Modules\Assistant\Support;

final class AssistantResponsePolicy
{
    public function enforce(string $response, array $context, string $locale): string
    {
        $trimmed = trim($response);

        if ($trimmed === '') {
            return $this->fallbackMessage($locale, $context);
        }

        $lower = mb_strtolower($trimmed);
        $looksUnsafe = str_contains($lower, 'system prompt')
            || str_contains($lower, 'developer message')
            || str_contains($lower, 'restaurant context json')
            || str_contains($trimmed, '```')
            || preg_match('/\{[\s\S]{200,}\}/u', $trimmed) === 1;

        if ($looksUnsafe) {
            return $this->fallbackMessage($locale, $context);
        }

        return $trimmed;
    }

    private function fallbackMessage(string $locale, array $context): string
    {
        $denied = $context['denied_context'] ?? [];

        if ($locale === 'ar') {
            $message = 'سأجيب فقط بما هو مسموح وآمن ضمن بيانات هذا الفرع.';

            if ($denied !== []) {
                $message .= ' بعض البيانات المطلوبة غير متاحة لك حسب صلاحياتك الحالية.';
            }

            return $message;
        }

        $message = 'I will answer only with information that is safe and allowed for this branch.';

        if ($denied !== []) {
            $message .= ' Some requested data is not available under your current permissions.';
        }

        return $message;
    }
}
