import type { BrandingTokens, SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';

export function useBrandStyles(override?: Partial<BrandingTokens>) {
    const { branding } = usePage<SharedData>().props;

    const tokens = { ...branding, ...override };

    useEffect(() => {
        const root = document.documentElement;
        root.style.setProperty('--brand-primary', tokens.primary_color);
        root.style.setProperty('--brand-secondary', tokens.secondary_color);
        root.style.setProperty('--brand-accent', tokens.accent_color);

        return () => {
            root.style.removeProperty('--brand-primary');
            root.style.removeProperty('--brand-secondary');
            root.style.removeProperty('--brand-accent');
        };
    }, [tokens.primary_color, tokens.secondary_color, tokens.accent_color]);

    return tokens;
}
