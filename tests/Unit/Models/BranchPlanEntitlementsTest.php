<?php

namespace Tests\Unit\Models;

use App\Modules\Branches\Models\Branch;
use App\Modules\Branches\Models\Plan;
use App\Support\Subscription\PlanFeatureKey;
use App\Support\Subscription\PlanLimitKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchPlanEntitlementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_without_plan_allows_every_feature_gate(): void
    {
        $branch = Branch::query()->create([
            'name' => 'Standalone',
            'address' => 'A',
            'phone' => null,
            'tax_rate' => 0,
            'currency_code' => 'USD',
            'is_active' => true,
        ]);

        $this->assertTrue($branch->canUseFeature(PlanFeatureKey::KITCHEN));
        $this->assertNull($branch->getPlanLimit(PlanLimitKey::MAX_USERS));
        $this->assertFalse($branch->hasPlanLimit(PlanLimitKey::MAX_USERS));
    }

    public function test_plan_feature_must_be_explicitly_true(): void
    {
        $features = [];
        foreach (PlanFeatureKey::all() as $key) {
            $features[$key] = false;
        }
        $features[PlanFeatureKey::TABLES] = true;

        $plan = Plan::factory()->create(['features' => $features]);

        $branch = Branch::query()->create([
            'name' => 'T2',
            'address' => 'B',
            'phone' => null,
            'tax_rate' => 0,
            'currency_code' => 'USD',
            'is_active' => true,
            'plan_id' => $plan->id,
        ]);

        $branch->load('plan');

        $this->assertTrue($branch->canUseFeature(PlanFeatureKey::TABLES));
        $this->assertFalse($branch->canUseFeature(PlanFeatureKey::KITCHEN));
    }

    public function test_plan_limit_gate(): void
    {
        $plan = Plan::factory()->withLimits([PlanLimitKey::MAX_TABLES => 3])->create();

        $branch = Branch::query()->create([
            'name' => 'T3',
            'address' => 'C',
            'phone' => null,
            'tax_rate' => 0,
            'currency_code' => 'USD',
            'is_active' => true,
            'plan_id' => $plan->id,
        ]);

        $branch->load('plan');

        $this->assertTrue($branch->hasPlanLimit(PlanLimitKey::MAX_TABLES));
        $this->assertSame(3, $branch->getPlanLimit(PlanLimitKey::MAX_TABLES));
        $this->assertTrue($branch->isAtOrOverPlanLimit(PlanLimitKey::MAX_TABLES, 4));
        $this->assertFalse($branch->isAtOrOverPlanLimit(PlanLimitKey::MAX_TABLES, 2));
    }
}
