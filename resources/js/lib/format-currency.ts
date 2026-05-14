/**
 * Locale-aware currency formatting using branch ISO currency code.
 */
export function formatCurrency(amount: number, currencyCode: string | undefined | null, locale: 'en' | 'ar'): string {
    const code = currencyCode && /^[A-Z]{3}$/i.test(currencyCode) ? currencyCode.toUpperCase() : 'USD';
    const intlLocale = locale === 'ar' ? 'ar-SA' : 'en-US';
    try {
        return new Intl.NumberFormat(intlLocale, {
            style: 'currency',
            currency: code,
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(amount);
    } catch {
        return `${amount.toFixed(2)} ${code}`;
    }
}
