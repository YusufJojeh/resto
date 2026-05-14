import PublicLayout from '@/layouts/public-layout';
import { formatCurrency } from '@/lib/format-currency';
import type { BrandingTokens, SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { Clock3, Search, Star, UtensilsCrossed } from 'lucide-react';
import { useMemo, useState } from 'react';

interface MenuItem {
    id: number;
    name: string;
    description: string | null;
    price: string;
    image_path: string | null;
}

interface Category {
    id: number;
    name: string;
    items: MenuItem[];
}

interface Props {
    categories: Category[];
    branding: BrandingTokens;
    currency_code: string;
}

export default function PublicMenu({ categories, branding, currency_code }: Props) {
    const { locale } = usePage<SharedData>().props;
    const [query, setQuery] = useState('');
    const [selectedCategory, setSelectedCategory] = useState<number | 'all'>('all');
    const loc = (locale as 'en' | 'ar') ?? 'en';

    const filteredCategories = useMemo(() => {
        return categories
            .map((category) => ({
                ...category,
                items: category.items.filter((item) => {
                    const matchQuery =
                        !query ||
                        item.name.toLowerCase().includes(query.toLowerCase()) ||
                        item.description?.toLowerCase().includes(query.toLowerCase());
                    const matchCategory = selectedCategory === 'all' || category.id === selectedCategory;
                    return matchQuery && matchCategory;
                }),
            }))
            .filter((category) => category.items.length > 0);
    }, [categories, query, selectedCategory]);

    return (
        <PublicLayout branding={branding}>
            <Head title={`Menu - ${branding.business_name}`} />

            <section className="border-border/60 relative overflow-hidden border-b py-18">
                {branding.cover_path ? (
                    <img src={branding.cover_path} alt="" className="absolute inset-0 h-full w-full object-cover opacity-20" />
                ) : null}
                <div className="from-background/30 via-background/75 to-background absolute inset-0 bg-gradient-to-b" />
                <div className="relative mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
                    <div className="max-w-2xl">
                        <span className="border-primary/20 bg-primary/10 text-primary inline-flex items-center gap-2 rounded-full border px-4 py-2 text-sm font-medium">
                            <UtensilsCrossed className="h-4 w-4" />
                            Full Menu
                        </span>
                        <h1 className="mt-5 text-4xl font-semibold tracking-tight sm:text-5xl">Explore our menu.</h1>
                        <p className="text-muted-foreground mt-4 text-base sm:text-lg">
                            Browse dishes, filter by category, and keep the public menu fast and readable on any screen.
                        </p>
                    </div>
                </div>
            </section>

            <section className="border-border/60 bg-background/88 sticky top-16 z-30 border-b backdrop-blur lg:top-20">
                <div className="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-4 sm:px-6 lg:px-8">
                    <div className="relative max-w-xl">
                        <Search className="text-muted-foreground absolute inset-y-0 left-3 my-auto h-4 w-4" />
                        <input
                            value={query}
                            onChange={(event) => setQuery(event.target.value)}
                            placeholder="Search dishes..."
                            className="border-input bg-card/80 ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex h-11 w-full rounded-xl border pr-4 pl-10 text-sm outline-none focus-visible:ring-2"
                        />
                    </div>
                    <div className="flex gap-2 overflow-x-auto pb-1">
                        <button
                            type="button"
                            onClick={() => setSelectedCategory('all')}
                            className={`rounded-xl border px-4 py-2 text-sm ${selectedCategory === 'all' ? 'border-primary bg-primary/10 text-primary' : 'border-border/70 bg-card/70 text-muted-foreground'}`}
                        >
                            All
                        </button>
                        {categories.map((category) => (
                            <button
                                key={category.id}
                                type="button"
                                onClick={() => setSelectedCategory(category.id)}
                                className={`rounded-xl border px-4 py-2 text-sm ${selectedCategory === category.id ? 'border-primary bg-primary/10 text-primary' : 'border-border/70 bg-card/70 text-muted-foreground'}`}
                            >
                                {category.name}
                            </button>
                        ))}
                    </div>
                </div>
            </section>

            <section className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                {filteredCategories.length === 0 ? (
                    <div className="surface-panel flex min-h-64 flex-col items-center justify-center rounded-3xl text-center">
                        <Star className="text-muted-foreground/50 mb-4 h-8 w-8" />
                        <div className="text-lg font-medium">No dishes match the current filter.</div>
                    </div>
                ) : null}

                <div className="space-y-12">
                    {filteredCategories.map((category) => (
                        <section key={category.id} id={`category-${category.id}`} className="space-y-5">
                            <div className="border-border/60 flex items-end justify-between gap-4 border-b pb-4">
                                <div>
                                    <div className="text-primary text-sm font-medium tracking-[0.2em] uppercase">Category</div>
                                    <h2 className="mt-1 text-2xl font-semibold">{category.name}</h2>
                                </div>
                                <div className="text-muted-foreground text-sm">{category.items.length} items</div>
                            </div>
                            <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                                {category.items.map((item) => (
                                    <article key={item.id} className="surface-panel overflow-hidden rounded-3xl">
                                        <div className="relative aspect-[16/10] overflow-hidden">
                                            {item.image_path ? (
                                                <img
                                                    src={item.image_path}
                                                    alt={item.name}
                                                    className="h-full w-full object-cover transition duration-500 hover:scale-105"
                                                />
                                            ) : (
                                                <div className="bg-secondary flex h-full w-full items-center justify-center">
                                                    <UtensilsCrossed className="text-muted-foreground h-8 w-8" />
                                                </div>
                                            )}
                                        </div>
                                        <div className="space-y-3 p-5">
                                            <div className="flex items-start justify-between gap-3">
                                                <div>
                                                    <h3 className="text-lg font-semibold">{item.name}</h3>
                                                    {item.description ? (
                                                        <p className="text-muted-foreground mt-1 line-clamp-2 text-sm">{item.description}</p>
                                                    ) : null}
                                                </div>
                                                <div className="text-primary shrink-0 text-lg font-semibold">
                                                    {formatCurrency(Number(item.price), currency_code, loc)}
                                                </div>
                                            </div>
                                            <div className="flex items-center justify-between">
                                                <div className="text-muted-foreground inline-flex items-center gap-2 text-xs tracking-[0.18em] uppercase">
                                                    <Clock3 className="h-3 w-3" />
                                                    Freshly prepared
                                                </div>
                                                <Link href="/login" className="text-primary text-sm font-medium">
                                                    Order
                                                </Link>
                                            </div>
                                        </div>
                                    </article>
                                ))}
                            </div>
                        </section>
                    ))}
                </div>
            </section>
        </PublicLayout>
    );
}
