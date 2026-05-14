<?php

declare(strict_types=1);

namespace App\Support\Billing;

final class BillingConfiguration
{
    public static function isExplicitlyEnabled(): bool
    {
        return (bool) config('billing.enabled', false);
    }

    /**
     * Operator opt-in PLUS minimal Stripe credential presence.
     */
    public static function checkoutAvailable(): bool
    {
        if (! self::isExplicitlyEnabled()) {
            return false;
        }

        return filled(config('billing.stripe.secret_key'))
            && filled(config('billing.checkout.success_url'))
            && filled(config('billing.checkout.cancel_url'));
    }

    public static function webhookAvailable(): bool
    {
        if (! self::isExplicitlyEnabled()) {
            return false;
        }

        return filled(config('billing.stripe.webhook_secret'));
    }

    public static function stripeSecretConfigured(): bool
    {
        return filled(config('billing.stripe.secret_key'));
    }

    /**
     * Self-service Stripe Customer Portal (payment methods / invoice list / subscription UX in Stripe-hosted UI).
     * Requires billing opt-in plus a secret key; checkout return URLs are not required.
     */
    public static function billingPortalAvailable(): bool
    {
        if (! self::isExplicitlyEnabled()) {
            return false;
        }

        return self::stripeSecretConfigured();
    }
}
