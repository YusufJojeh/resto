<?php

declare(strict_types=1);

namespace App\Modules\Billing\Models;

use App\Modules\Branches\Models\Branch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingWebhookEvent extends Model
{
    protected $table = 'billing_webhook_events';

    protected $fillable = [
        'provider',
        'provider_event_id',
        'event_type',
        'payload',
        'related_branch_id',
        'processed_at',
        'failed_at',
        'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'related_branch_id');
    }
}
