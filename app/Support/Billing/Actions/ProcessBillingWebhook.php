<?php

declare(strict_types=1);

namespace App\Support\Billing\Actions;

use App\Modules\Billing\Models\BillingWebhookEvent;
use App\Support\Billing\Stripe\StripeWebhookToPatchMapper;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Throwable;

final class ProcessBillingWebhook
{
    public function __construct(
        private readonly StripeWebhookToPatchMapper $mapper,
        private readonly ApplyProviderSubscriptionPatch $applyProviderSubscriptionPatch,
    ) {}

    public function handleRawStripePayload(string $payload, string $signatureHeader): void
    {
        $secret = config('billing.stripe.webhook_secret');
        if (! is_string($secret) || $secret === '') {
            throw new \RuntimeException('Stripe webhook secret not configured.');
        }

        try {
            $event = Webhook::constructEvent($payload, $signatureHeader, $secret);
        } catch (SignatureVerificationException $e) {
            throw $e;
        }

        $this->handleVerifiedStripeEvent($event, $payload);
    }

    private function handleVerifiedStripeEvent(Event $event, string $rawPayload): void
    {
        /** @phpstan-ignore-next-line */
        $eventId = (string) $event->id;
        $eventType = (string) $event->type;

        $payloadPreview = $this->safePayloadPreview($rawPayload);

        try {
            DB::transaction(function () use ($event, $eventId, $eventType, $payloadPreview): void {
                try {
                    BillingWebhookEvent::query()->create([
                        'provider' => 'stripe',
                        'provider_event_id' => $eventId,
                        'event_type' => $eventType,
                        'payload' => $payloadPreview,
                        'related_branch_id' => null,
                        'processed_at' => null,
                        'failed_at' => null,
                        'failure_reason' => null,
                    ]);
                } catch (UniqueConstraintViolationException) {
                    return;
                } catch (QueryException $e) {
                    if ($this->isDuplicateStripeEventViolation($e)) {
                        return;
                    }

                    throw $e;
                }

                /** @phpstan-ignore-next-line */
                $row = BillingWebhookEvent::query()
                    ->where('provider', 'stripe')
                    ->where('provider_event_id', $eventId)
                    ->first();

                /** @phpstan-ignore-next-line */
                if ($row === null) {
                    return;
                }

                try {
                    $patches = $this->mapper->patchesFromStripeEvent($event);
                    if ($patches === []) {
                        $row->forceFill([
                            'processed_at' => now(),
                        ])->saveQuietly();

                        return;
                    }

                    $relatedBranchId = null;

                    foreach ($patches as $patch) {
                        $branch = $this->applyProviderSubscriptionPatch->execute($patch);
                        if ($branch !== null) {
                            $relatedBranchId = $branch->id;
                        }
                    }

                    $row->forceFill([
                        'related_branch_id' => $relatedBranchId,
                        'processed_at' => now(),
                    ])->saveQuietly();
                } catch (Throwable $e) {
                    Log::error('billing.webhook.processing_failed', [
                        'event_id' => $eventId,
                        'event_type' => $eventType,
                        'message' => $e->getMessage(),
                    ]);

                    $row->forceFill([
                        'failed_at' => now(),
                        'failure_reason' => $e->getMessage(),
                        'processed_at' => now(),
                    ])->saveQuietly();
                }
            });
        } catch (Throwable $e) {
            Log::error('billing.webhook.transaction_failed', [
                'event_id' => $eventId,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function isDuplicateStripeEventViolation(QueryException $e): bool
    {
        if ($e instanceof UniqueConstraintViolationException) {
            return true;
        }

        $msg = strtoupper($e->getMessage());

        return str_contains($msg, 'UNIQUE')
            || str_contains($msg, 'DUPLICATE');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function safePayloadPreview(string $rawPayload): ?array
    {
        try {
            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($rawPayload, true, 512, JSON_THROW_ON_ERROR);

            return [
                'id' => $decoded['id'] ?? null,
                'type' => $decoded['type'] ?? null,
                'livemode' => $decoded['livemode'] ?? null,
                'api_version' => $decoded['api_version'] ?? null,
            ];
        } catch (Throwable) {
            return null;
        }
    }
}
