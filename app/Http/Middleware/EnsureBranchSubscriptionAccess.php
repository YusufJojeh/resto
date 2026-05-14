<?php

namespace App\Http\Middleware;

use App\Modules\Branches\Models\Branch;
use App\Support\Subscription\SubscriptionAccessEvaluator;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBranchSubscriptionAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Allow routes that users should always access even with no/expired subscription.
        if ($request->routeIs('subscription.notice', 'profile.*', 'password.*', 'appearance', 'logout')) {
            return $next($request);
        }

        // Branch/contact settings remain reachable for managers/admins during commercial blocks.
        if ($request->routeIs(['branch.edit', 'branch.update']) && $user->hasAnyRole(['manager', 'admin'])) {
            return $next($request);
        }

        // Admins retain manual subscription tooling, plan catalog edits, and checkout/cancel/portal initiation.
        if (
            $request->routeIs([
                'branch.subscription.update',
                'branch.billing.checkout',
                'branch.billing.cancel',
                'branch.billing.portal',
                'plans.index',
                'plans.create',
                'plans.store',
                'plans.edit',
                'plans.update',
                'plans.destroy',
            ])
            && $user->hasRole('admin')) {
            return $next($request);
        }

        $branch = $user->branch_id ? Branch::query()->find($user->branch_id) : null;

        $assessment = $branch !== null
            ? $branch->subscriptionAccessAssessment()
            : SubscriptionAccessEvaluator::noBranchAttached();

        if (! $assessment->allowed) {
            // JSON/API requests get a structured error response, not an HTML redirect.
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Subscription access required.',
                    'reason_code' => $assessment->reasonCode,
                    'reason' => $assessment->explanation,
                ], 403);
            }

            // Prevent redirect loops: if already on subscription notice, continue.
            if ($request->routeIs('subscription.notice')) {
                return $next($request);
            }

            return redirect()->route('subscription.notice');
        }

        return $next($request);
    }
}
