import { useEffect } from 'react';
import { useTranslation } from './use-translation';

/**
 * Mounts on root layouts. Keeps `<html lang>` and `<html dir>` in sync with
 * the server-shared locale, since Inertia client-side navigations don't
 * re-render the blade root template.
 */
export function useHtmlDir(): void {
    const { locale, dir } = useTranslation();

    useEffect(() => {
        const html = document.documentElement;
        html.setAttribute('lang', locale);
        html.setAttribute('dir', dir);
    }, [locale, dir]);
}
