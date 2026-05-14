<?php

namespace App\Modules\Branches\Models;

use App\Enums\SubscriptionStatus;
use App\Models\User;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Inventory\Models\InventoryItem;
use App\Modules\Menu\Models\MenuCategory;
use App\Modules\Menu\Models\MenuItem;
use App\Modules\Orders\Models\Order;
use App\Modules\Tables\Models\RestaurantTable;
use App\Support\Subscription\PlanFeatureKey;
use App\Support\Subscription\PlanLimitKey;
use App\Support\Subscription\SubscriptionAccessEvaluator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'tax_rate',
        'currency_code',
        'is_active',
        'public_slug',
        'is_public',
        'business_name',
        'tagline',
        'story',
        'logo_path',
        'cover_path',
        'primary_color',
        'secondary_color',
        'accent_color',
        'whatsapp',
        'instagram_url',
        'facebook_url',
        'tiktok_url',
        'google_maps_url',
        'opening_hours',
        'plan_id',
        'subscription_plan',
        'subscription_status',
        'trial_ends_at',
        'subscription_ends_at',
        'current_period_ends_at',
        'provider_name',
        'provider_customer_id',
        'provider_subscription_id',
    ];

    protected function casts(): array
    {
        return [
            'tax_rate' => 'decimal:2',
            'is_active' => 'boolean',
            'is_public' => 'boolean',
            'opening_hours' => 'array',
            'subscription_status' => SubscriptionStatus::class,
            'trial_ends_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
            'current_period_ends_at' => 'datetime',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * When no plan is assigned the branch behaves as grandfathered/full access until a tier is enforced.
     */
    public function canUseFeature(string $featureKey): bool
    {
        if ($this->plan_id === null) {
            return true;
        }

        $plan = $this->relationLoaded('plan') ? $this->plan : $this->plan()->first();

        // Referential integrity gap: enforced plan row missing — deny entitlement rather than widening access.
        if ($this->plan_id !== null && ! $plan instanceof Plan) {
            return false;
        }

        /** @var array<string, mixed> $features */
        $features = is_array($plan->features) ? $plan->features : [];
        if (! array_key_exists($featureKey, $features)) {
            return false;
        }

        return (bool) $features[$featureKey];
    }

    public function getPlanLimit(string $limitKey): ?int
    {
        if ($this->plan_id === null) {
            return null;
        }

        $plan = $this->relationLoaded('plan') ? $this->plan : $this->plan()->first();

        if ($this->plan_id !== null && ! $plan instanceof Plan) {
            return null;
        }

        /** @var array<string, mixed> $limits */
        $limits = is_array($plan->limits) ? $plan->limits : [];
        if (! array_key_exists($limitKey, $limits)) {
            return null;
        }

        if (! is_numeric($limits[$limitKey])) {
            return null;
        }

        $value = (int) $limits[$limitKey];

        return $value >= 0 ? $value : null;
    }

    public function hasPlanLimit(string $limitKey): bool
    {
        return $this->getPlanLimit($limitKey) !== null;
    }

    public function isAtOrOverPlanLimit(string $limitKey, int $usageCount): bool
    {
        $max = $this->getPlanLimit($limitKey);

        return $max !== null && $usageCount >= $max;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function entitlementSummary(): ?array
    {
        if (! $this->plan instanceof Plan && $this->plan_id !== null) {
            $this->loadMissing('plan');
        }

        $plan = $this->plan;
        if ($plan === null) {
            return null;
        }

        $featuresOut = [];
        foreach (PlanFeatureKey::all() as $key) {
            $featuresOut[$key] = $this->canUseFeature($key);
        }

        $limitsOut = [];
        foreach (PlanLimitKey::all() as $limitKey) {
            $limitsOut[$limitKey] = $this->getPlanLimit($limitKey);
        }

        $enforcedLimits = [];
        $informationalLimits = [];
        foreach ($limitsOut as $key => $value) {
            if ($value === null) {
                continue;
            }
            if (PlanLimitKey::isEnforced($key)) {
                $enforcedLimits[$key] = $value;
            } else {
                $informationalLimits[$key] = $value;
            }
        }

        return [
            'id' => $plan->id,
            'name' => $plan->name,
            'slug' => $plan->slug,
            'features' => $featuresOut,
            'limits' => $enforcedLimits,
            'informational_limits' => $informationalLimits,
        ];
    }

    /**
     * Authoritative lifecycle evaluation for dashboard / API gating for this branch snapshot.
     */
    public function subscriptionAccessAssessment(): \App\Support\Subscription\SubscriptionAccessResult
    {
        return SubscriptionAccessEvaluator::evaluate($this);
    }

    public function hasSubscriptionAccess(): bool
    {
        return $this->subscriptionAccessAssessment()->allowed;
    }

    /**
     * Machine-safe reason code matching {@see \App\Support\Subscription\SubscriptionAccessReason} constants.
     */
    public function subscriptionAccessReason(): string
    {
        return $this->subscriptionAccessAssessment()->reasonCode;
    }

    /**
     * Human-facing sentence for banners and subscription notice pages.
     */
    public function subscriptionAccessExplanation(): string
    {
        return $this->subscriptionAccessAssessment()->explanation;
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function tables(): HasMany
    {
        return $this->hasMany(RestaurantTable::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(MenuCategory::class);
    }

    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }
}
