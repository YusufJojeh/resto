<?php

declare(strict_types=1);

return [
    /**
     * Active billing connector: currently only Stripe is wired.
     */
    'provider' => env('BILLING_PROVIDER', 'stripe'),

    /**
     * Master switch — keep false until keys and URLs are configured in each environment.
     */
    'enabled' => env('BILLING_ENABLED') !== null ? filter_var(env('BILLING_ENABLED'), FILTER_VALIDATE_BOOL) : false,

    'currency' => env('BILLING_CURRENCY', 'usd'),

    'stripe' => [
        'secret_key' => env('STRIPE_SECRET_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'checkout' => [
        'success_url' => env('STRIPE_CHECKOUT_SUCCESS_URL'),
        'cancel_url' => env('STRIPE_CHECKOUT_CANCEL_URL'),
    ],

    'stripe_api_version' => env('STRIPE_API_VERSION', '2024-11-20.acacia'),
];
