<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Branches\Models\Plan;
use App\Support\Subscription\PlanFeatureKey;
use App\Support\Subscription\PlanLimitKey;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Test Plan '.$this->faker->unique()->numerify('####'),
            'slug' => 'test-plan-'.$this->faker->unique()->numerify('####'),
            'description' => 'Factory plan',
            'price_amount' => null,
            'billing_interval' => null,
            'is_active' => true,
            'sort_order' => 0,
            'features' => collect(PlanFeatureKey::all())->mapWithKeys(fn (string $k) => [$k => true])->all(),
            'limits' => [],
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    /**
     * @param array<string, bool|null> $features
     */
    public function withFeatures(array $features): static
    {
        return $this->state(fn () => ['features' => $features]);
    }

    /**
     * @param array<string, int> $limits
     */
    public function withLimits(array $limits): static
    {
        return $this->state(fn () => ['limits' => $limits]);
    }
}
