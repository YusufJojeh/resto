<?php

declare(strict_types=1);

namespace App\Modules\Billing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Billing\Actions\ProcessBillingWebhook;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Stripe\Exception\SignatureVerificationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, ProcessBillingWebhook $processor): Response
    {
        $secret = config('billing.stripe.webhook_secret');
        /** @phpstan-ignore-next-line */
        if ($secret === null || $secret === '') {
            throw new HttpException(503, 'Webhook unavailable.');
        }

        /** @phpstan-ignore-next-line */
        $payload = $request->getContent();
        /** @phpstan-ignore-next-line */
        $signature = $request->header('Stripe-Signature');
        /** @phpstan-ignore-next-line */
        if ($signature === null || $signature === '' || (! is_string($payload)) || $payload === '') {
            abort(400, 'Malformed webhook.');
        }

        try {
            $processor->handleRawStripePayload($payload, $signature);
        } catch (SignatureVerificationException) {
            abort(400);
        }

        /** @phpstan-ignore-next-line */
        return response('ok');
    }
}
