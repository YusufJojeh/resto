<?php

namespace App\Modules\Branches\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Branches\Models\Plan;
use App\Modules\Branches\Requests\StorePlanRequest;
use App\Modules\Branches\Requests\UpdatePlanRequest;
use App\Support\Subscription\PlanFeatureKey;
use App\Support\Subscription\PlanLimitKey;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PlanSettingsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('plans/index', [
            'plans' => Plan::query()->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('plans/form', [
            'plan' => null,
            'featureKeys' => PlanFeatureKey::all(),
            'limitKeys' => PlanLimitKey::all(),
        ]);
    }

    public function store(StorePlanRequest $request): RedirectResponse
    {
        Plan::query()->create($request->toPlanAttributes());

        return to_route('plans.index')->with('success', 'Plan created.');
    }

    public function edit(Plan $plan): Response
    {
        return Inertia::render('plans/form', [
            'plan' => $plan,
            'featureKeys' => PlanFeatureKey::all(),
            'limitKeys' => PlanLimitKey::all(),
        ]);
    }

    public function update(UpdatePlanRequest $request, Plan $plan): RedirectResponse
    {
        $plan->update($request->toPlanAttributes());

        return to_route('plans.index')->with('success', 'Plan updated.');
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        if ($plan->branches()->exists()) {
            return back()->with('error', 'Cannot delete a plan that is assigned to branches.');
        }

        $plan->delete();

        return to_route('plans.index')->with('success', 'Plan deleted.');
    }
}
