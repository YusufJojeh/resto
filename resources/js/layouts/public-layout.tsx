import { LocaleSwitcher } from '@/components/locale-switcher';
import { useBrandStyles } from '@/hooks/use-brand-styles';
import { useHtmlDir } from '@/i18n/use-html-dir';
import type { BrandingTokens } from '@/types';
import { Link } from '@inertiajs/react';
import { Facebook, Instagram, MapPin, MenuSquare, Phone, Utensils } from 'lucide-react';
import type { ReactNode } from 'react';

interface Props {
    children: ReactNode;
    branding?: Partial<BrandingTokens>;
}

export default function PublicLayout({ children, branding: override }: Props) {
    useHtmlDir();
    const tokens = useBrandStyles(override);

    return (
        <div className="bg-background text-foreground min-h-screen">
            <header className="glass-strong border-border/60 sticky top-0 z-40 border-b">
                <div className="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:h-20 lg:px-8">
                    <Link href="/" className="flex min-w-0 items-center gap-3">
                        <div className="bg-primary text-primary-foreground shadow-primary/15 flex h-11 w-11 items-center justify-center rounded-2xl shadow-lg">
                            <Utensils className="h-5 w-5" />
                        </div>
                        <div className="min-w-0">
                            <div className="truncate text-base font-semibold sm:text-lg">{tokens.business_name}</div>
                            {tokens.tagline ? <div className="text-muted-foreground truncate text-xs">{tokens.tagline}</div> : null}
                        </div>
                    </Link>

                    <nav className="hidden items-center gap-6 md:flex">
                        <Link href="/" className="text-muted-foreground hover:text-foreground text-sm transition">
                            Home
                        </Link>
                        <Link href="/menu" className="text-muted-foreground hover:text-foreground text-sm transition">
                            Menu
                        </Link>
                        {tokens.google_maps_url ? (
                            <a
                                href={tokens.google_maps_url}
                                target="_blank"
                                rel="noreferrer"
                                className="text-muted-foreground hover:text-foreground text-sm transition"
                            >
                                Location
                            </a>
                        ) : null}
                    </nav>

                    <div className="flex items-center gap-2">
                        <div className="hidden sm:block">
                            <LocaleSwitcher variant="compact" className="border-border/70 bg-card/70" />
                        </div>
                        <Link
                            href="/login"
                            className="bg-primary text-primary-foreground shadow-primary/15 inline-flex h-10 items-center rounded-xl px-4 text-sm font-medium shadow-lg"
                        >
                            Sign In
                        </Link>
                    </div>
                </div>
            </header>

            <main>{children}</main>

            <footer className="border-border/60 bg-card/40 border-t">
                <div className="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 lg:grid-cols-[1.4fr_1fr_1fr] lg:px-8">
                    <div className="space-y-4">
                        <div className="flex items-center gap-3">
                            <div className="bg-primary text-primary-foreground flex h-10 w-10 items-center justify-center rounded-2xl">
                                <Utensils className="h-5 w-5" />
                            </div>
                            <div>
                                <div className="font-semibold">{tokens.business_name}</div>
                                {tokens.tagline ? <div className="text-muted-foreground text-sm">{tokens.tagline}</div> : null}
                            </div>
                        </div>
                        {tokens.story ? <p className="text-muted-foreground max-w-xl text-sm leading-6">{tokens.story}</p> : null}
                    </div>
                    <div className="space-y-3">
                        <div className="text-primary text-sm font-semibold tracking-[0.18em] uppercase">Visit</div>
                        {tokens.google_maps_url ? (
                            <a
                                href={tokens.google_maps_url}
                                target="_blank"
                                rel="noreferrer"
                                className="text-muted-foreground hover:text-foreground flex items-start gap-2 text-sm transition"
                            >
                                <MapPin className="mt-0.5 h-4 w-4" />
                                <span>Open in Maps</span>
                            </a>
                        ) : null}
                        {tokens.whatsapp ? (
                            <a
                                href={`https://wa.me/${tokens.whatsapp.replace(/\D/g, '')}`}
                                target="_blank"
                                rel="noreferrer"
                                className="text-muted-foreground hover:text-foreground flex items-start gap-2 text-sm transition"
                            >
                                <Phone className="mt-0.5 h-4 w-4" />
                                <span>{tokens.whatsapp}</span>
                            </a>
                        ) : null}
                    </div>
                    <div className="space-y-3">
                        <div className="text-primary text-sm font-semibold tracking-[0.18em] uppercase">Follow</div>
                        <div className="flex items-center gap-3">
                            {tokens.instagram_url ? (
                                <a
                                    href={tokens.instagram_url}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="border-border/70 bg-card/70 text-muted-foreground hover:text-foreground inline-flex h-10 w-10 items-center justify-center rounded-xl border transition"
                                >
                                    <Instagram className="h-4 w-4" />
                                </a>
                            ) : null}
                            {tokens.facebook_url ? (
                                <a
                                    href={tokens.facebook_url}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="border-border/70 bg-card/70 text-muted-foreground hover:text-foreground inline-flex h-10 w-10 items-center justify-center rounded-xl border transition"
                                >
                                    <Facebook className="h-4 w-4" />
                                </a>
                            ) : null}
                            <Link
                                href="/menu"
                                className="border-border/70 bg-card/70 text-muted-foreground hover:text-foreground inline-flex h-10 items-center gap-2 rounded-xl border px-3 text-sm transition"
                            >
                                <MenuSquare className="h-4 w-4" />
                                Menu
                            </Link>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    );
}
