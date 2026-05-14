<?php

declare(strict_types=1);

namespace App\Support\Subscription;

readonly final class SubscriptionAccessResult
{
    /**
     * @param string $reasonCode One of SubscriptionAccessReason::* constants
     */
    public function __construct(
        public bool $allowed,
        public string $reasonCode,
        public string $explanation,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'allowed' => $this->allowed,
            'reason_code' => $this->reasonCode,
            'explanation' => $this->explanation,
        ];
    }
}
