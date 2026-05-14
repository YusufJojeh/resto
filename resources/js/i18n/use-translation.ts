import type { SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { useCallback } from 'react';
import { messages, type Locale } from './messages';

export type TFn = (key: string, replacements?: Record<string, string | number>) => string;

/**
 * Tiny translator. Reads `locale` from Inertia shared props (server-authoritative).
 * Falls back to the key itself if a translation is missing — surfaces gaps loudly in UI
 * rather than silently rendering English.
 */
export function useTranslation(): { t: TFn; locale: Locale; dir: 'ltr' | 'rtl' } {
    const page = usePage<SharedData>();
    const locale: Locale = (page.props.locale as Locale) ?? 'en';
    const dir: 'ltr' | 'rtl' = locale === 'ar' ? 'rtl' : 'ltr';
    const dict = messages[locale] ?? messages.en;

    const t = useCallback<TFn>(
        (key, replacements) => {
            const raw = dict[key] ?? messages.en[key] ?? key;
            if (!replacements) return raw;
            return Object.entries(replacements).reduce((acc, [k, v]) => acc.replaceAll(`{${k}}`, String(v)), raw);
        },
        [dict],
    );

    return { t, locale, dir };
}
