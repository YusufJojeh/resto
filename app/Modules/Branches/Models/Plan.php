<?php

declare(strict_types=1);

namespace App\Modules\Branches\Models;

use Database\Factories\PlanFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected static function newFactory(): PlanFactory
    {
        return PlanFactory::new();
    }

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_amount',
        'billing_interval',
        'provider_price_id',
        'is_active',
        'sort_order',
        'features',
        'limits',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'price_amount' => 'decimal:2',
            'features' => 'array',
            'limits' => 'array',
        ];
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class, 'plan_id');
    }

    /**
     * @param  Builder<Plan>  $query
     * @return Builder<Plan>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', '=', true);
    }

    /**
     * Active tiers with a Stripe price id set (Stripe checkout eligibility at the data layer —
     * still require {@see \App\Support\Billing\BillingConfiguration::checkoutAvailable()} at runtime).
     *
     * @param  Builder<Plan>  $query
     * @return Builder<Plan>
     */
    public function scopePurchasable(Builder $query): Builder
    {
        return $query->active()->where(function (Builder $q): void {
            $q->whereNotNull('provider_price_id')->where('provider_price_id', '!=', '');
        });
    }

    /**
     * @param  Builder<Plan>  $query
     * @return Builder<Plan>
     */
    public function scopeAssignableForBranch(Builder $query, ?int $branchPlanId): Builder
    {
        return $query->where(function (Builder $q) use ($branchPlanId): void {
            $q->where('is_active', '=', true);

            if ($branchPlanId !== null) {
                $q->orWhere('id', '=', $branchPlanId);
            }
        });
    }

    public function isPurchasableForStripeCheckout(): bool
    {
        return (bool) $this->is_active && filled((string) $this->provider_price_id);
    }

    /**
     * Whether this plan row may remain selected or become the branch tier via manual assignment.
     * Inactive tiers are only selectable when already attached to keep legacy rows visible.
     */
    public function allowsManualAssignment(?int $currentBranchPlanId): bool
    {
        if ($this->is_active) {
            return true;
        }

        return $currentBranchPlanId !== null && (int) $this->id === $currentBranchPlanId;
    }
}
