import InputError from '@/components/input-error';
import { LocaleSwitcher } from '@/components/locale-switcher';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useHtmlDir } from '@/i18n/use-html-dir';
import { useTranslation } from '@/i18n/use-translation';
import type { SharedData } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { motion, useReducedMotion } from 'framer-motion';
import {
    ArrowRight,
    ChefHat,
    ClipboardList,
    Eye,
    EyeOff,
    LayoutDashboard,
    Loader2,
    LockKeyhole,
    Receipt,
    ShieldCheck,
    Users,
    Utensils,
} from 'lucide-react';
import { FormEventHandler, useState } from 'react';

interface LoginProps {
    status?: string;
    canResetPassword: boolean;
}

const referenceImage = '/assets/reference/photos/login-hero.jpg';

const staggerContainer = {
    hidden: {},
    visible: {
        transition: {
            staggerChildren: 0.05,
            delayChildren: 0.1,
        },
    },
};

const staggerItem = {
    hidden: { opacity: 0, y: 10 },
    visible: { opacity: 1, y: 0 },
};

export default function Login({ status, canResetPassword }: LoginProps) {
    useHtmlDir();

    const { branding } = usePage<SharedData>().props;
    const { t, dir } = useTranslation();
    const reduceMotion = useReducedMotion();
    const [showPassword, setShowPassword] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false as boolean,
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    const heroImage = branding.cover_path || referenceImage;
    const brandName = branding.business_name || 'RestoCafe';
    const productName = brandName.toLowerCase().includes('os') ? brandName : `${brandName} OS`;
    const topError = errors.email || errors.password;
    const workspaceHighlights = [
        { icon: ShieldCheck, label: t('login.highlight.secure') },
        { icon: Users, label: t('login.highlight.roles') },
        { icon: LayoutDashboard, label: t('login.highlight.dashboard') },
    ];

    return (
        <>
            <Head title={t('auth.head_title')} />

            <div className="reference-public bg-background text-foreground relative flex min-h-screen" dir={dir}>
                <LocaleSwitcher variant="compact" className="bg-secondary/50 absolute end-4 top-4 z-20" />

                <div className="flex flex-1 flex-col justify-center px-4 py-12 sm:px-6 lg:w-[480px] lg:flex-none lg:px-12 xl:w-[560px] xl:px-20">
                    <motion.div
                        variants={staggerContainer}
                        initial={reduceMotion ? false : 'hidden'}
                        animate="visible"
                        className="mx-auto w-full max-w-sm"
                    >
                        <motion.div variants={staggerItem} className="mb-8">
                            <Link href="/" className="inline-flex items-center gap-2">
                                <div className="bg-primary text-primary-foreground flex h-11 w-11 items-center justify-center rounded-xl">
                                    <Utensils className="h-6 w-6" />
                                </div>
                                <div>
                                    <span className="text-xl font-semibold">{productName}</span>
                                    <span className="text-muted-foreground block text-xs font-medium">{t('login.workspace_label')}</span>
                                </div>
                            </Link>
                        </motion.div>

                        <motion.div variants={staggerItem}>
                            <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">{t('login.welcome_back')}</h1>
                            <p className="text-muted-foreground mt-2">{t('login.subtitle')}</p>
                        </motion.div>

                        <motion.div variants={staggerItem} className="mt-5 grid gap-2">
                            {workspaceHighlights.map((item) => (
                                <div
                                    key={item.label}
                                    className="border-border/80 bg-secondary/25 flex items-center gap-3 rounded-xl border px-3 py-2 text-sm"
                                >
                                    <item.icon className="text-primary h-4 w-4 shrink-0" />
                                    <span className="text-muted-foreground">{item.label}</span>
                                </div>
                            ))}
                        </motion.div>

                        <motion.form variants={staggerItem} onSubmit={submit} className="mt-8 space-y-5">
                            {status ? (
                                <motion.div
                                    initial={reduceMotion ? false : { opacity: 0, y: -10 }}
                                    animate={{ opacity: 1, y: 0 }}
                                    className="rounded-lg border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200"
                                >
                                    {status}
                                </motion.div>
                            ) : null}

                            {topError ? (
                                <motion.div
                                    initial={reduceMotion ? false : { opacity: 0, y: -10 }}
                                    animate={{ opacity: 1, y: 0 }}
                                    className="border-destructive/20 bg-destructive/10 text-destructive rounded-lg border px-4 py-3 text-sm"
                                >
                                    {topError}
                                </motion.div>
                            ) : null}

                            <div className="space-y-2">
                                <Label htmlFor="email">{t('login.email_label')}</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    placeholder={t('login.email_placeholder')}
                                    value={data.email}
                                    onChange={(event) => setData('email', event.target.value)}
                                    autoComplete="email"
                                    required
                                    autoFocus
                                    aria-invalid={Boolean(errors.email)}
                                    className="bg-secondary/50 h-11"
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="space-y-2">
                                <div className="flex items-center justify-between gap-4">
                                    <Label htmlFor="password">{t('auth.password')}</Label>
                                    {canResetPassword ? (
                                        <Link
                                            href={route('password.request')}
                                            className="text-muted-foreground hover:text-primary text-sm transition-colors"
                                        >
                                            {t('auth.forgot')}
                                        </Link>
                                    ) : null}
                                </div>
                                <div className="relative">
                                    <Input
                                        id="password"
                                        type={showPassword ? 'text' : 'password'}
                                        placeholder={t('login.password_placeholder')}
                                        value={data.password}
                                        onChange={(event) => setData('password', event.target.value)}
                                        autoComplete="current-password"
                                        required
                                        aria-invalid={Boolean(errors.password)}
                                        className="bg-secondary/50 h-11 pe-10"
                                    />
                                    <button
                                        type="button"
                                        onClick={() => setShowPassword((value) => !value)}
                                        className="text-muted-foreground hover:text-foreground absolute end-3 top-1/2 -translate-y-1/2 transition-colors"
                                        aria-label={showPassword ? t('login.hide_password') : t('login.show_password')}
                                    >
                                        {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                                    </button>
                                </div>
                                <InputError message={errors.password} />
                            </div>

                            <label className="text-muted-foreground flex items-center gap-3 text-sm">
                                <Checkbox checked={data.remember} onCheckedChange={(checked) => setData('remember', Boolean(checked))} />
                                <span>{t('auth.remember')}</span>
                            </label>

                            <Button type="submit" className="h-11 w-full gap-2" disabled={processing}>
                                {processing ? (
                                    <>
                                        <Loader2 className="h-4 w-4 animate-spin" />
                                        {t('auth.logging_in')}
                                    </>
                                ) : (
                                    <>
                                        {t('auth.submit')}
                                        <ArrowRight className="h-4 w-4" />
                                    </>
                                )}
                            </Button>
                        </motion.form>

                        <motion.div variants={staggerItem} className="border-border bg-secondary/30 mt-8 rounded-lg border p-4">
                            <p className="text-muted-foreground mb-2 text-xs font-medium">{t('login.demo_credentials')}</p>
                            <div className="space-y-1 text-sm">
                                <p>
                                    <span className="text-muted-foreground">{t('auth.email')}:</span> admin@restocafe.test
                                </p>
                                <p>
                                    <span className="text-muted-foreground">{t('auth.password')}:</span> password
                                </p>
                            </div>
                        </motion.div>

                        <motion.p variants={staggerItem} className="text-muted-foreground mt-8 text-center text-sm">
                            <Link href="/" className="hover:text-foreground transition-colors">
                                {t('login.back_to_website')}
                            </Link>
                        </motion.p>
                    </motion.div>
                </div>

                <div className="relative hidden flex-1 overflow-hidden lg:block">
                    <img src={heroImage} alt="" className="h-full w-full object-cover" />
                    <div className="from-background via-background/50 absolute inset-0 bg-gradient-to-r to-transparent" />
                    <div className="from-background/80 absolute inset-0 bg-gradient-to-t via-transparent to-transparent" />

                    <div className="absolute inset-x-12 bottom-12 max-w-xl">
                        <motion.div
                            initial={reduceMotion ? false : { opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ delay: 0.5 }}
                            className="glass rounded-2xl p-6"
                        >
                            <div className="flex items-start justify-between gap-4">
                                <div>
                                    <p className="text-primary text-xs font-semibold tracking-[0.22em] uppercase">{t('login.panel_eyebrow')}</p>
                                    <h2 className="mt-2 text-2xl font-semibold">{t('login.panel_title')}</h2>
                                    <p className="text-muted-foreground mt-2 text-sm leading-6">{t('login.panel_description')}</p>
                                </div>
                                <div className="bg-primary/10 text-primary flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl">
                                    <LockKeyhole className="h-6 w-6" />
                                </div>
                            </div>

                            <div className="mt-6 grid gap-3 sm:grid-cols-2">
                                {[
                                    { icon: ClipboardList, title: t('login.panel.orders'), description: t('login.panel.orders_desc') },
                                    { icon: ChefHat, title: t('login.panel.kitchen'), description: t('login.panel.kitchen_desc') },
                                    { icon: Receipt, title: t('login.panel.billing'), description: t('login.panel.billing_desc') },
                                    { icon: LayoutDashboard, title: t('login.panel.reports'), description: t('login.panel.reports_desc') },
                                ].map((item) => (
                                    <div key={item.title} className="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                                        <item.icon className="text-primary h-5 w-5" />
                                        <p className="mt-3 text-sm font-medium">{item.title}</p>
                                        <p className="text-muted-foreground mt-1 text-xs leading-5">{item.description}</p>
                                    </div>
                                ))}
                            </div>
                        </motion.div>
                    </div>
                </div>
            </div>
        </>
    );
}
