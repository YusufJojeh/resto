<?php

namespace App\Http\Middleware;

use App\Modules\Branches\Models\Branch;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBranchPlanFeature
{
    public function handle(Request $request, Closure $next, string $featureKey): Response
    {
        $user = $request->user();
        if ($user === null || $user->branch_id === null) {
            return redirect()->route('dashboard')->with('error', 'No branch assigned.');
        }

        $branch = Branch::query()->with('plan')->find($user->branch_id);

        if ($branch === null) {
            return redirect()->route('dashboard')->with('error', 'Branch not found.');
        }

        if (! $branch->canUseFeature($featureKey)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'This action is not available on your subscription plan.'], 403);
            }

            return redirect()->route('dashboard')->with('error', 'This feature is not included in your current plan.');
        }

        return $next($request);
    }
}
