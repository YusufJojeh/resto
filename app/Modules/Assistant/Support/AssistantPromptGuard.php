<?php

declare(strict_types=1);

namespace App\Modules\Assistant\Support;

final class AssistantPromptGuard
{
    private const AR_BLOCK_MESSAGE = "\xD9\x84\xD8\xA7 \xD8\xA3\xD8\xB3\xD8\xAA\xD8\xB7\xD9\x8A\xD8\xB9 \xD9\x83\xD8\xB4\xD9\x81 \xD8\xA7\xD9\x84\xD8\xAA\xD8\xB9\xD9\x84\xD9\x8A\xD9\x85\xD8\xA7\xD8\xAA \xD8\xA7\xD9\x84\xD8\xAF\xD8\xA7\xD8\xAE\xD9\x84\xD9\x8A\xD8\xA9 \xD8\xA3\xD9\x88 \xD8\xAA\xD8\xAC\xD8\xA7\xD9\x88\xD8\xB2 \xD8\xA7\xD9\x84\xD8\xB5\xD9\x84\xD8\xA7\xD8\xAD\xD9\x8A\xD8\xA7\xD8\xAA \xD8\xA3\xD9\x88 \xD8\xB9\xD8\xB1\xD8\xB6 \xD8\xA8\xD9\x8A\xD8\xA7\xD9\x86\xD8\xA7\xD8\xAA \xD9\x85\xD8\xAE\xD9\x81\xD9\x8A\xD8\xA9. \xD9\x8A\xD9\x85\xD9\x83\xD9\x86\xD9\x86\xD9\x8A \xD9\x85\xD8\xB3\xD8\xA7\xD8\xB9\xD8\xAF\xD8\xAA\xD9\x83 \xD9\x81\xD9\x82\xD8\xB7 \xD8\xA8\xD8\xA7\xD9\x84\xD9\x85\xD8\xB9\xD9\x84\xD9\x88\xD9\x85\xD8\xA7\xD8\xAA \xD8\xA7\xD9\x84\xD9\x85\xD8\xB3\xD9\x85\xD9\x88\xD8\xAD \xD8\xA8\xD9\x87\xD8\xA7 \xD8\xAF\xD8\xA7\xD8\xAE\xD9\x84 \xD8\xAF\xD9\x88\xD8\xB1\xD9\x83 \xD8\xA7\xD9\x84\xD8\xAD\xD8\xA7\xD9\x84\xD9\x8A.";

    private const EN_BLOCK_MESSAGE = "I can't reveal internal instructions, bypass permissions, or expose hidden data. I can only help with information allowed for your current role.";

    /**
     * @return array{blocked:bool,reasons:list<string>,message:string|null}
     */
    public function inspect(string $prompt, string $locale): array
    {
        if (! (bool) config('assistant.guard.enabled', true)) {
            return ['blocked' => false, 'reasons' => [], 'message' => null];
        }

        $normalized = self::normalize($prompt);

        $checks = [
            'reveal_system_prompt' => [
                'system prompt',
                'hidden prompt',
                'developer message',
                'show your instructions',
            ],
            'dump_hidden_context' => [
                'dump the context',
                'raw context',
                'print json',
                'database dump',
                'show all data',
            ],
            'bypass_permissions' => [
                'ignore permissions',
                'ignore role',
                'act as admin',
                'pretend i am admin',
                'bypass security',
            ],
            'exfiltrate_sensitive_data' => [
                'list all users',
                'all customer data',
                'all emails',
                'all phone numbers',
                'private notes',
            ],
        ];

        $arabicChecks = [
            'reveal_system_prompt' => [
                "\xD8\xA7\xD8\xB8\xD9\x87\xD8\xB1 \xD8\xA7\xD9\x84\xD8\xAA\xD8\xB9\xD9\x84\xD9\x8A\xD9\x85\xD8\xA7\xD8\xAA",
                "\xD8\xA7\xD8\xB8\xD9\x87\xD8\xB1 \xD8\xA7\xD9\x84\xD8\xA8\xD8\xB1\xD9\x88\xD9\x85\xD8\xA8\xD8\xAA",
                "\xD8\xA7\xD8\xB8\xD9\x87\xD8\xB1 \xD8\xA7\xD9\x84\xD9\x86\xD8\xB8\xD8\xA7\xD9\x85",
                "\xD8\xA7\xD8\xB9\xD8\xB1\xD8\xB6 \xD8\xA7\xD9\x84\xD8\xA8\xD8\xB1\xD9\x88\xD9\x85\xD8\xA8\xD8\xAA",
            ],
            'dump_hidden_context' => [
                "\xD8\xA7\xD8\xB9\xD8\xB1\xD8\xB6 \xD9\x83\xD9\x84 \xD8\xA7\xD9\x84\xD8\xA8\xD9\x8A\xD8\xA7\xD9\x86\xD8\xA7\xD8\xAA",
                "\xD8\xA7\xD8\xB7\xD8\xA8\xD8\xB9 json",
                "\xD8\xA7\xD8\xB9\xD8\xB1\xD8\xB6 \xD8\xA7\xD9\x84\xD8\xB3\xD9\x8A\xD8\xA7\xD9\x82",
                "\xD8\xAA\xD9\x81\xD8\xB1\xD9\x8A\xD8\xBA \xD9\x82\xD8\xA7\xD8\xB9\xD8\xAF\xD8\xA9 \xD8\xA7\xD9\x84\xD8\xA8\xD9\x8A\xD8\xA7\xD9\x86\xD8\xA7\xD8\xAA",
            ],
            'bypass_permissions' => [
                "\xD8\xAA\xD8\xAC\xD8\xA7\xD9\x87\xD9\x84 \xD8\xA7\xD9\x84\xD8\xB5\xD9\x84\xD8\xA7\xD8\xAD\xD9\x8A\xD8\xA7\xD8\xAA",
                "\xD8\xAA\xD8\xAC\xD8\xA7\xD9\x87\xD9\x84 \xD8\xA7\xD9\x84\xD8\xAF\xD9\x88\xD8\xB1",
                "\xD8\xAA\xD8\xB5\xD8\xB1\xD9\x81 \xD9\x83\xD9\x85\xD8\xAF\xD9\x8A\xD8\xB1",
                "\xD8\xA7\xD8\xB9\xD8\xAA\xD8\xA8\xD8\xB1\xD9\x86\xD9\x8A \xD8\xA7\xD8\xAF\xD9\x85\xD9\x86",
                "\xD8\xAA\xD8\xAC\xD8\xA7\xD9\x88\xD8\xB2 \xD8\xA7\xD9\x84\xD8\xAD\xD9\x85\xD8\xA7\xD9\x8A\xD8\xA9",
            ],
            'exfiltrate_sensitive_data' => [
                "\xD8\xA7\xD8\xB9\xD8\xB1\xD8\xB6 \xD8\xAC\xD9\x85\xD9\x8A\xD8\xB9 \xD8\xA7\xD9\x84\xD9\x85\xD8\xB3\xD8\xAA\xD8\xAE\xD8\xAF\xD9\x85\xD9\x8A\xD9\x86",
                "\xD9\x83\xD9\x84 \xD8\xA7\xD9\x84\xD8\xA7\xD9\x8A\xD9\x85\xD9\x8A\xD9\x84\xD8\xA7\xD8\xAA",
                "\xD9\x83\xD9\x84 \xD8\xA7\xD9\x84\xD8\xA7\xD8\xB1\xD9\x82\xD8\xA7\xD9\x85",
                "\xD8\xA7\xD9\x84\xD9\x85\xD9\x84\xD8\xA7\xD8\xAD\xD8\xB8\xD8\xA7\xD8\xAA \xD8\xA7\xD9\x84\xD8\xAE\xD8\xA7\xD8\xB5\xD8\xA9",
            ],
        ];

        $reasons = [];

        foreach ($checks as $reason => $needles) {
            foreach ($needles as $needle) {
                if ($needle !== '' && str_contains($normalized, mb_strtolower($needle))) {
                    $reasons[] = $reason;
                    break;
                }
            }
        }

        foreach ($arabicChecks as $reason => $needles) {
            if (in_array($reason, $reasons, true)) {
                continue;
            }
            foreach ($needles as $needle) {
                if ($needle !== '' && str_contains($normalized, mb_strtolower($needle))) {
                    $reasons[] = $reason;
                    break;
                }
            }
        }

        if ($reasons === []) {
            return ['blocked' => false, 'reasons' => [], 'message' => null];
        }

        return [
            'blocked' => true,
            'reasons' => $reasons,
            'message' => $locale === 'ar' ? self::AR_BLOCK_MESSAGE : self::EN_BLOCK_MESSAGE,
        ];
    }

    // Defense-in-depth: not a hard security boundary, but raises the bar against casual bypass.
    private static function normalize(string $input): string
    {
        $text = mb_strtolower(trim($input));

        if (function_exists('normalizer_normalize')) {
            $text = \Normalizer::normalize($text, \Normalizer::FORM_KC) ?: $text;
        }

        $text = preg_replace("/[\\x{200B}-\\x{200D}\\x{FEFF}\\x{00AD}\\x{2060}]/u", '', $text) ?? $text;

        $text = preg_replace('/\\s+/u', ' ', $text) ?? $text;

        return $text;
    }
}
