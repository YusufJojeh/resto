import { Badge } from '@/components/ui/badge';
import { useTranslation } from '@/i18n/use-translation';

const styles: Record<string, string> = {
    available: 'border-emerald-500/20 bg-emerald-500/12 text-emerald-300',
    occupied: 'border-rose-500/20 bg-rose-500/12 text-rose-300',
    reserved: 'border-amber-500/20 bg-amber-500/12 text-amber-300',
    in_stock: 'border-emerald-500/20 bg-emerald-500/12 text-emerald-300',
    low_stock: 'border-amber-500/20 bg-amber-500/12 text-amber-300',
    out_of_stock: 'border-rose-500/20 bg-rose-500/12 text-rose-300',
    new: 'border-slate-400/20 bg-slate-400/12 text-slate-200',
    in_kitchen: 'border-sky-500/20 bg-sky-500/12 text-sky-300',
    ready: 'border-amber-500/20 bg-amber-500/12 text-amber-300',
    served: 'border-emerald-500/20 bg-emerald-500/12 text-emerald-300',
    cancelled: 'border-rose-500/20 bg-rose-500/12 text-rose-300',
    paid: 'border-emerald-500/20 bg-emerald-500/12 text-emerald-300',
    unpaid: 'border-amber-500/20 bg-amber-500/12 text-amber-300',
    active: 'border-emerald-500/20 bg-emerald-500/12 text-emerald-300',
    inactive: 'border-slate-400/20 bg-slate-400/12 text-slate-200',
    item_available: 'border-emerald-500/20 bg-emerald-500/12 text-emerald-300',
    item_unavailable: 'border-slate-400/20 bg-slate-400/12 text-slate-200',
};

const ORDER_KEYS: Record<string, string> = {
    new: 'order.status.new',
    in_kitchen: 'order.status.in_kitchen',
    ready: 'order.status.ready',
    served: 'order.status.served',
    cancelled: 'order.status.cancelled',
};

const TABLE_KEYS: Record<string, string> = {
    available: 'table.status.available',
    occupied: 'table.status.occupied',
    reserved: 'table.status.reserved',
};

const INVOICE_KEYS: Record<string, string> = {
    paid: 'invoice.status.paid',
    unpaid: 'invoice.status.unpaid',
};

const MENU_ITEM_KEYS: Record<string, string> = {
    item_available: 'menu.item.status.available',
    item_unavailable: 'menu.item.status.unavailable',
};

function labelize(value: string) {
    return value.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase());
}

export function StatusBadge({ value }: { value: string | null | undefined }) {
    const { t } = useTranslation();
    if (!value) {
        return <Badge variant="outline">{t('common.na')}</Badge>;
    }

    const orderLabel = ORDER_KEYS[value];
    const tableLabel = TABLE_KEYS[value];
    const invoiceLabel = INVOICE_KEYS[value];
    const menuItemLabel = MENU_ITEM_KEYS[value];
    const text = orderLabel
        ? t(orderLabel)
        : tableLabel
          ? t(tableLabel)
          : invoiceLabel
            ? t(invoiceLabel)
            : menuItemLabel
              ? t(menuItemLabel)
              : labelize(value);

    return <Badge className={styles[value] ?? 'border-border bg-muted/60 text-foreground'}>{text}</Badge>;
}
