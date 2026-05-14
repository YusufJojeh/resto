import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';
import { useHtmlDir } from '@/i18n/use-html-dir';
import { useTranslation } from '@/i18n/use-translation';
import { Head, Link } from '@inertiajs/react';

export default function NotFound() {
    useHtmlDir();
    const { t } = useTranslation();

    return (
        <div className="bg-background flex min-h-svh flex-col items-center justify-center gap-8 p-6 text-center">
            <Head title={t('errors.not_found_title')} />

            <Link href="/" className="flex items-center gap-2">
                <AppLogoIcon className="text-accent h-10 w-10 fill-current" />
            </Link>

            <div>
                <p className="text-accent text-8xl font-bold tracking-tight">404</p>
                <h1 className="mt-4 text-2xl font-semibold">{t('errors.not_found_title')}</h1>
                <p className="text-muted-foreground mt-2 max-w-sm text-sm">{t('errors.not_found_description')}</p>
            </div>

            <Button asChild>
                <Link href={route('dashboard')}>{t('errors.back_to_dashboard')}</Link>
            </Button>
        </div>
    );
}
