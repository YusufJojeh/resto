import { useTranslation } from '@/i18n/use-translation';
import AppLogoIcon from './app-logo-icon';

export default function AppLogo() {
    const { t } = useTranslation();

    return (
        <>
            <div className="bg-sidebar-primary text-sidebar-primary-foreground shadow-sidebar-primary/30 ring-sidebar-primary/10 flex aspect-square size-8 items-center justify-center rounded-md shadow-sm ring-1">
                <AppLogoIcon className="text-sidebar-primary-foreground size-5 fill-current" />
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-none font-semibold">{t('nav.product_name')}</span>
            </div>
        </>
    );
}
