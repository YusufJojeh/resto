import { Alert, AlertDescription } from '@/components/ui/alert';
import { SharedData } from '@/types';
import { usePage } from '@inertiajs/react';

export function FlashAlert() {
    const { flash } = usePage<SharedData>().props;

    if (!flash?.success && !flash?.error) {
        return null;
    }

    return (
        <Alert
            className={flash.error ? 'border-rose-500/20 bg-rose-500/10 text-rose-200' : 'border-emerald-500/20 bg-emerald-500/10 text-emerald-200'}
        >
            <AlertDescription>{flash.error ?? flash.success}</AlertDescription>
        </Alert>
    );
}
