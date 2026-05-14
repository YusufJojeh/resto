<?php

declare(strict_types=1);

namespace App\Support\Billing\Stripe;

use App\Support\Billing\Contracts\StripeSubscriptionGatewayContract;
use App\Support\Billing\Data\ProviderSubscriptionPatch;
use App\Enums\SubscriptionStatus;
use Carbon\CarbonImmutable;
use Stripe\Event;

final class StripeWebhookToPatchMapper
{
    public function __construct(
        private readonly StripeSubscriptionGatewayContract $gateway,
    ) {}

    /**
     * @return list<ProviderSubscriptionPatch>
     */
    public function patchesFromStripeEvent(Event $event): array
    {
        return match ($event->type) {
            'checkout.session.completed' => $this->fromCheckoutSessionCompleted($event),
            'customer.subscription.created',
            'customer.subscription.updated' => $this->fromSubscriptionUpdated($event),
            'customer.subscription.deleted' => $this->fromSubscriptionDeleted($event),
            'invoice.payment_failed' => $this->fromInvoicePayment($event, failure: true),
            'invoice.payment_succeeded' => $this->fromInvoicePayment($event, failure: false),
            default => [],
        };
    }

    /** @return list<ProviderSubscriptionPatch> */
    private function fromCheckoutSessionCompleted(Event $event): array
    {
        $sessionObj = $event->data->object;
        /** @phpstan-ignore-next-line */
        $subscriptionId = is_string($sessionObj->subscription)
            /** @phpstan-ignore-next-line */
            ? $sessionObj->subscription
            /** @phpstan-ignore-next-line */
            : (($sessionObj->subscription->id ?? null) ?: null);

        /** @phpstan-ignore-next-line */
        $customerIdStrip = isset($sessionObj->customer)
            /** @phpstan-ignore-next-line */
            ? (is_string($sessionObj->customer)
                /** @phpstan-ignore-next-line */
                ? $sessionObj->customer
                /** @phpstan-ignore-next-line */
                : ($sessionObj->customer->id ?? null))
            : null;

        if ($subscriptionId === null || ! is_string($subscriptionId) || $subscriptionId === '') {
            return [];
        }

        $metaRaw = isset($sessionObj->metadata) ? json_decode(json_encode($sessionObj->metadata), true) : [];
        $branchHint = isset($metaRaw['branch_id'])
            /** @phpstan-ignore-next-line */
            ? filter_var($metaRaw['branch_id'], FILTER_VALIDATE_INT)
            : false;
        /** @phpstan-ignore-next-line */
        $planHintRaw = isset($metaRaw['plan_id']) ? filter_var($metaRaw['plan_id'], FILTER_VALIDATE_INT) : false;

        $subscriptionArray = $this->gateway->retrieveSubscription((string) $subscriptionId);
        /** @phpstan-ignore-next-line */
        $lifecycle = SubscriptionStatusLookup::fromStripeSubscription($subscriptionArray)->status;

        $priceId = SubscriptionStatusLookup::firstPriceId($subscriptionArray);
        $planResolved = StripePlanResolver::planIdFromStripePrice($priceId);
        /** @phpstan-ignore-next-line */
        $planMerged = $planResolved ?? (($planHintRaw !== false && $planHintRaw > 0) ? $planHintRaw : null);

        /** @phpstan-ignore-next-line */
        $cust = is_string($customerIdStrip) && $customerIdStrip !== ''
            ? $customerIdStrip
            : (isset($subscriptionArray['customer']) ? (string) $subscriptionArray['customer'] : null);

        /** @phpstan-ignore-next-line */
        $hintId = ($branchHint !== false && $branchHint > 0) ? $branchHint : null;

        return [
            /** @phpstan-ignore-next-line */
            new ProviderSubscriptionPatch(
                provider: 'stripe',
                providerEventId: (string) $event->id,
                eventType: 'checkout.session.completed',
                subscriptionStatus: $lifecycle,
                planId: $planMerged,
                trialEndsAt: SubscriptionStatusLookup::trialEnd($subscriptionArray),
                currentPeriodEndsAt: SubscriptionStatusLookup::currentPeriodEnd($subscriptionArray),
                subscriptionEndsAt: SubscriptionStatusLookup::computedSubscriptionEnd($subscriptionArray),
                providerName: 'stripe',
                providerCustomerId: $cust,
                providerSubscriptionId: (string) ($subscriptionArray['id'] ?? $subscriptionId),
                resolveBranchIdHint: $hintId,
                trustedBranchResolutionFromHintOnly: $hintId !== null,
                clearTrialEnds: false,
            ),
        ];
    }

    /** @return list<ProviderSubscriptionPatch> */
    private function fromSubscriptionUpdated(Event $event): array
    {
        /** @phpstan-ignore-next-line */
        $subscription = (array) json_decode(json_encode($event->data->object), true);
        $subscriptionId = (string) ($subscription['id'] ?? '');
        /** @phpstan-ignore-next-line */
        if ($subscriptionId === '') {
            return [];
        }

        /** @phpstan-ignore-next-line */
        $lifecycle = SubscriptionStatusLookup::fromStripeSubscription($subscription)->status;
        /** @phpstan-ignore-next-line */
        $planId = StripePlanResolver::planIdFromStripePrice(SubscriptionStatusLookup::firstPriceId($subscription));

        return [
            /** @phpstan-ignore-next-line */
            new ProviderSubscriptionPatch(
                provider: 'stripe',
                providerEventId: (string) $event->id,
                eventType: $event->type,
                subscriptionStatus: $lifecycle,
                planId: $planId,
                trialEndsAt: SubscriptionStatusLookup::trialEnd($subscription),
                currentPeriodEndsAt: SubscriptionStatusLookup::currentPeriodEnd($subscription),
                subscriptionEndsAt: SubscriptionStatusLookup::computedSubscriptionEnd($subscription),
                providerName: 'stripe',
                providerCustomerId: isset($subscription['customer']) ? (string) $subscription['customer'] : null,
                providerSubscriptionId: $subscriptionId,
                trustedBranchResolutionFromHintOnly: false,
                clearTrialEnds: false,
            ),
        ];
    }

    /** @return list<ProviderSubscriptionPatch> */
    private function fromSubscriptionDeleted(Event $event): array
    {
        /** @phpstan-ignore-next-line */
        $subscription = (array) json_decode(json_encode($event->data->object), true);
        /** @phpstan-ignore-next-line */
        $subsId = (string) ($subscription['id'] ?? '');
        /** @phpstan-ignore-next-line */
        $cust = isset($subscription['customer']) ? (string) $subscription['customer'] : null;
        /** @phpstan-ignore-next-line */
        $periodEndTs = isset($subscription['current_period_end']) ? (int) $subscription['current_period_end'] : null;
        $periodEnd = $periodEndTs > 0 ? CarbonImmutable::createFromTimestamp($periodEndTs) : null;

        $status = ($periodEnd instanceof CarbonImmutable && $periodEnd->isFuture())
            /** @phpstan-ignore-next-line */
            ? SubscriptionStatus::Canceled
            : SubscriptionStatus::Expired;

        return [
            /** @phpstan-ignore-next-line */
            new ProviderSubscriptionPatch(
                provider: 'stripe',
                providerEventId: (string) $event->id,
                eventType: 'customer.subscription.deleted',
                subscriptionStatus: $status,
                planId: null,
                trialEndsAt: null,
                currentPeriodEndsAt: $periodEnd,
                subscriptionEndsAt: $periodEnd,
                providerName: 'stripe',
                providerCustomerId: $cust,
                providerSubscriptionId: $subsId !== '' ? $subsId : null,
                trustedBranchResolutionFromHintOnly: false,
                clearTrialEnds: false,
            ),
        ];
    }

    /** @return list<ProviderSubscriptionPatch> */
    private function fromInvoicePayment(Event $event, bool $failure): array
    {
        /** @phpstan-ignore-next-line */
        $invoice = (array) json_decode(json_encode($event->data->object), true);
        /** @phpstan-ignore-next-line */
        $subscriptionId = $this->invoiceSubscriptionToId($invoice['subscription'] ?? null);
        if ($subscriptionId === null) {
            return [];
        }

        $subscriptionArray = $this->gateway->retrieveSubscription($subscriptionId);
        /** @phpstan-ignore-next-line */
        $priceId = SubscriptionStatusLookup::firstPriceId($subscriptionArray);

        /** @phpstan-ignore-next-line */
        $periodEnd = isset($invoice['period_end']) ? CarbonImmutable::createFromTimestamp((int) $invoice['period_end']) : null;

        if ($failure) {
            return [
                /** @phpstan-ignore-next-line */
                new ProviderSubscriptionPatch(
                    provider: 'stripe',
                    providerEventId: (string) $event->id,
                    eventType: 'invoice.payment_failed',
                    subscriptionStatus: SubscriptionStatus::PastDue,
                    /** @phpstan-ignore-next-line */
                    planId: StripePlanResolver::planIdFromStripePrice($priceId),
                    trialEndsAt: SubscriptionStatusLookup::trialEnd($subscriptionArray),
                    currentPeriodEndsAt: SubscriptionStatusLookup::currentPeriodEnd($subscriptionArray) ?? $periodEnd,
                    subscriptionEndsAt: $periodEnd ?? SubscriptionStatusLookup::computedSubscriptionEnd($subscriptionArray),
                    providerName: 'stripe',
                    providerCustomerId: isset($subscriptionArray['customer']) ? (string) $subscriptionArray['customer'] : null,
                    providerSubscriptionId: (string) ($subscriptionArray['id'] ?? $subscriptionId),
                    trustedBranchResolutionFromHintOnly: false,
                    clearTrialEnds: false,
                ),
            ];
        }

        return [
            new ProviderSubscriptionPatch(
                provider: 'stripe',
                providerEventId: (string) $event->id,
                eventType: 'invoice.payment_succeeded',
                subscriptionStatus: SubscriptionStatus::Active,
                planId: StripePlanResolver::planIdFromStripePrice($priceId),
                trialEndsAt: SubscriptionStatusLookup::trialEnd($subscriptionArray),
                currentPeriodEndsAt: SubscriptionStatusLookup::currentPeriodEnd($subscriptionArray),
                subscriptionEndsAt: SubscriptionStatusLookup::computedSubscriptionEnd($subscriptionArray),
                providerName: 'stripe',
                providerCustomerId: isset($subscriptionArray['customer']) ? (string) $subscriptionArray['customer'] : null,
                providerSubscriptionId: (string) ($subscriptionArray['id'] ?? $subscriptionId),
                resolveBranchIdHint: null,
                trustedBranchResolutionFromHintOnly: false,
                clearTrialEnds: true,
            ),
        ];
    }

    private function invoiceSubscriptionToId(mixed $subscription): ?string
    {
        if (is_string($subscription) && $subscription !== '') {
            return $subscription;
        }
        /** @phpstan-ignore-next-line */
        if (is_array($subscription) && isset($subscription['id'])) {
            return (string) $subscription['id'];
        }

        return null;
    }
}
