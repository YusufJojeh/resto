<?php

namespace App\Modules\Branches\Requests\Concerns;

use App\Support\Subscription\PlanFeatureKey;
use App\Support\Subscription\PlanLimitKey;

trait NormalizesPlanPayload
{
    /**
     * Build boolean feature map keyed by known feature identifiers.
     *
     * @param  array<string, mixed>|null  $features
     * @return array<string, bool>
     */
    protected function coerceFeatures(?array $features): array
    {
        /** @var array<string, mixed> $input */
        $input = $features ?? [];

        /** @var array<string, bool> $out */
        $out = [];
        foreach (PlanFeatureKey::all() as $key) {
            if (! array_key_exists($key, $input)) {
                $out[$key] = false;

                continue;
            }

            $value = $input[$key];

            $out[$key] = match (true) {
                is_bool($value) => $value,
                $value === 1, $value === '1', $value === 'true', $value === 'on', $value === 'yes' => true,
                default => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            };
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>|null  $limits
     * @return array<string, int>
     */
    protected function coerceLimits(?array $limits): array
    {
        /** @var array<string, mixed> $input */
        $input = $limits ?? [];

        /** @var array<string, int> $out */
        $out = [];
        foreach ($input as $key => $value) {
            if (! in_array($key, PlanLimitKey::all(), true)) {
                continue;
            }
            if ($value === null || $value === '') {
                continue;
            }
            if (! is_numeric($value)) {
                continue;
            }
            $int = (int) $value;
            if ($int < 0) {
                continue;
            }
            $out[$key] = $int;
        }

        return $out;
    }
}
