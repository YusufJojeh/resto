interface PageHeaderProps {
    title: string;
    description?: string;
    actions?: React.ReactNode;
}

export function PageHeader({ title, description, actions }: PageHeaderProps) {
    return (
        <div className="border-border/60 flex flex-col gap-4 border-b pb-5 sm:flex-row sm:items-end sm:justify-between">
            <div className="space-y-2">
                <span className="border-primary/15 bg-primary/10 text-primary inline-flex w-fit items-center rounded-full border px-3 py-1 text-[11px] font-semibold tracking-[0.22em] uppercase">
                    Operations
                </span>
                <div className="space-y-1">
                    <h1 className="text-3xl font-semibold tracking-tight">{title}</h1>
                    {description ? <p className="text-muted-foreground max-w-3xl text-sm">{description}</p> : null}
                </div>
            </div>
            {actions ? <div className="flex flex-wrap items-center gap-2 sm:justify-end">{actions}</div> : null}
        </div>
    );
}
