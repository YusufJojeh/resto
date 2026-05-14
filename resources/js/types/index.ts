import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User | null;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    /** i18n key from `resources/js/i18n/messages.ts` */
    titleKey: string;
    url: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
    roles?: string[];
}

export interface BrandingTokens {
    business_name: string;
    tagline: string | null;
    story: string | null;
    logo_path: string | null;
    cover_path: string | null;
    primary_color: string;
    secondary_color: string;
    accent_color: string;
    whatsapp: string | null;
    instagram_url: string | null;
    facebook_url: string | null;
    tiktok_url: string | null;
    google_maps_url: string | null;
    opening_hours: Record<string, string>[];
    is_public: boolean;
    public_slug: string | null;
    currency_code: string;
}

export interface SubscriptionEntitlements {
    id: number;
    name: string;
    slug: string;
    features: Record<string, boolean>;
    /** Quantitative caps enforced in controllers / middleware today */
    limits: Record<string, number>;
    /** Plan JSON keys reserved for roadmap / UX only (documented backend-side) */
    informational_limits?: Record<string, number>;
}

export interface SubscriptionMetadata {
    plan: string | null;
    plan_id: number | null;
    status: string | null;
    trial_ends_at: string | null;
    subscription_ends_at: string | null;
    current_period_ends_at?: string | null;
    grace_days?: number;
    has_access: boolean;
    /** Machine-safe code from Laravel (SubscriptionAccessReason) */
    reason_code?: string;
    /** Human-readable explanation for display only */
    reason: string;
    entitlement_summary: SubscriptionEntitlements | null;
    grandfathered_without_plan: boolean;
}

export interface BranchDisplayPlanRow {
    id: number;
    name: string;
    slug: string;
}

/** Branch's linked plan tier (inactive allowed). */
export interface BranchCurrentPlanRow {
    id: number;
    name: string;
    slug: string;
    is_active: boolean;
}

export interface BranchPlanAssignmentOption {
    id: number;
    name: string;
    slug: string;
    is_active: boolean;
    /** Whether a Stripe price id is stored (checkout still requires billing to be configured). */
    has_provider_price: boolean;
}

export interface BranchBillingCatalogPlan {
    id: number;
    name: string;
    slug: string;
    provider_price_id: string | null;
}

/** Admin-only Stripe operator context — no secrets. */
export interface BranchBillingProps {
    state: {
        explicitly_enabled: boolean;
        checkout_ready: boolean;
        stripe_secret_configured: boolean;
        webhook_configured: boolean;
        portal_ready: boolean;
        branch_has_customer: boolean;
    };
}


export interface SharedData {
    name: string;
    locale: 'en' | 'ar';
    dir: 'ltr' | 'rtl';
    available_locales: ReadonlyArray<'en' | 'ar'>;
    auth: Auth;
    flash?: {
        success?: string | null;
        error?: string | null;
    };
    branding: BrandingTokens;
    subscription: SubscriptionMetadata;
    /** Laravel / Inertia validation errors for the last form submission */
    errors?: Record<string, string>;
    features?: {
        messages: boolean;
        notifications: boolean;
        assistant: boolean;
        realtime: boolean;
    };
    assistantMeta?: {
        enabled: boolean;
        read_only: boolean;
        role: string | null;
        current_module: {
            key: string;
            label: string;
            path: string | null;
        };
        permission_notice: string;
        module_notice: string;
        starter_prompts: string[];
        loading_messages: string[];
        errors: {
            forbidden: string;
            throttled: string;
            unavailable: string;
            validation: string;
            network: string;
        };
    };
    [key: string]: unknown;
}

export interface User {
    id: number;
    branch_id?: number | null;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at?: string | null;
    created_at?: string;
    updated_at?: string;
    is_active?: boolean;
    roles?: string[];
    can_manage_subscription?: boolean;
    unread_notifications_count?: number;
    [key: string]: unknown;
}
