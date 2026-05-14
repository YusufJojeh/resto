import { useTranslation } from '@/i18n/use-translation';
import { useMotionVariants } from '@/motion/use-motion';
import { fadeIn } from '@/motion/variants';
import { motion } from 'framer-motion';

export type ConnectionState = 'live' | 'reconnecting' | 'offline';

interface Props {
    state: ConnectionState;
    /** ISO timestamp of last successful update — optional, shown as subtitle */
    lastUpdate?: string;
}

const STYLES: Record<ConnectionState, string> = {
    live: 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 ring-emerald-500/30',
    reconnecting: 'bg-amber-500/15 text-amber-700 dark:text-amber-300 ring-amber-500/30',
    offline: 'bg-rose-500/15 text-rose-700 dark:text-rose-300 ring-rose-500/30',
};

export function ConnectionPill({ state, lastUpdate }: Props) {
    const { t } = useTranslation();
    const variants = useMotionVariants(fadeIn);

    const label =
        state === 'live'
            ? t('kitchen.connection.auto_refresh')
            : state === 'reconnecting'
              ? t('kitchen.connection.retrying')
              : t('kitchen.connection.offline');

    return (
        <motion.div
            initial="hidden"
            animate="visible"
            variants={variants}
            role="status"
            aria-live="polite"
            className={['inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-medium ring-1', STYLES[state]].join(' ')}
        >
            <span
                className={[
                    'inline-block size-2 rounded-full',
                    state === 'live' ? 'bg-emerald-500' : state === 'reconnecting' ? 'animate-pulse bg-amber-500' : 'bg-rose-500',
                ].join(' ')}
                aria-hidden="true"
            />
            <span>{label}</span>
            {lastUpdate && (
                <time dateTime={lastUpdate} className="opacity-60">
                    {new Date(lastUpdate).toLocaleTimeString()}
                </time>
            )}
        </motion.div>
    );
}
