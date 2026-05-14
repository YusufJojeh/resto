import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { TrendingDown, TrendingUp } from 'lucide-react';

interface StatCardProps {
    label: string;
    value: string | number;
    hint?: string;
    icon?: React.ElementType;
    trend?: { value: number; label: string };
    accent?: string;
}

export function StatCard({ label, value, hint, icon: Icon, trend, accent }: StatCardProps) {
    const isPositiveTrend = trend && trend.value >= 0;

    return (
        <Card className="border-border/60 relative overflow-hidden">
            <div className="via-primary/45 absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent to-transparent" />
            <CardHeader className="flex flex-row items-start justify-between space-y-0 pb-2">
                <CardTitle className="text-muted-foreground text-xs font-medium tracking-[0.18em] uppercase">{label}</CardTitle>
                {Icon && (
                    <div className={cn('flex h-11 w-11 items-center justify-center rounded-2xl border border-white/5', accent ?? 'bg-accent/10')}>
                        <Icon className={cn('h-5 w-5', accent ? 'text-white' : 'text-primary')} aria-hidden />
                    </div>
                )}
            </CardHeader>
            <CardContent>
                <div className="text-3xl font-semibold tracking-tight">{value}</div>
                {trend && (
                    <div className={cn('mt-2 flex items-center gap-1 text-xs', isPositiveTrend ? 'text-emerald-400' : 'text-destructive')}>
                        {isPositiveTrend ? <TrendingUp className="h-3 w-3" aria-hidden /> : <TrendingDown className="h-3 w-3" aria-hidden />}
                        <span>
                            {isPositiveTrend ? '+' : ''}
                            {trend.value}% {trend.label}
                        </span>
                    </div>
                )}
                {hint && !trend ? <p className="text-muted-foreground mt-2 text-xs">{hint}</p> : null}
            </CardContent>
        </Card>
    );
}
