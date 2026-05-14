<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Past-due grace period (days)
    |--------------------------------------------------------------------------
    |
    | After the billing anchor date (`subscription_ends_at` or
    | `current_period_ends_at` when `subscription_ends_at` is absent), Past Due
    | tenants keep operational access until this many calendar days elapse past
    | that anchor. Set to 0 for no grace extension beyond the anchor instant.
    |
    */

    'grace_days' => (int) env('SUBSCRIPTION_GRACE_DAYS', 3),
];
