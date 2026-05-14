import type { Locale } from '@/i18n/messages';
import { useTranslation } from '@/i18n/use-translation';
import { router } from '@inertiajs/react';
import { Languages } from 'lucide-react';

const LABELS: Record<Locale, string> = {
    en: 'English',
    ar: 'العربية',
};

interface Props {
    variant?: 'compact' | 'full';
    className?: string;
}

export function LocaleSwitcher({ variant = 'full', className = '' }: Props) {
    const { locale, t } = useTranslation();

    const switchTo = (next: Locale) => {
        if (next === locale) return;
        router.post(
            route('locale.update'),
            { locale: next },
            {
                preserveScroll: true,
                preserveState: false,
                onSuccess: () => {
                    // Dir/lang are reapplied on the next page render via useHtmlDir.
                },
            },
        );
    };

    return (
        <div
            role="group"
            aria-label={t('common.language')}
            className={`border-border bg-background inline-flex items-center gap-1 rounded-md border p-0.5 text-xs ${className}`}
        >
            {variant === 'full' && <Languages aria-hidden="true" className="ms-1 size-3.5 opacity-60" />}
            {(['en', 'ar'] as const).map((code) => {
                const active = code === locale;
                return (
                    <button
                        key={code}
                        type="button"
                        onClick={() => switchTo(code)}
                        aria-pressed={active}
                        className={[
                            'rounded px-2 py-1 font-medium transition-colors',
                            active ? 'bg-foreground text-background' : 'text-muted-foreground hover:text-foreground',
                        ].join(' ')}
                    >
                        {variant === 'compact' ? code.toUpperCase() : LABELS[code]}
                    </button>
                );
            })}
        </div>
    );
}
