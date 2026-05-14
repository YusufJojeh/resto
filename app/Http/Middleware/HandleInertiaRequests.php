<?php

namespace App\Http\Middleware;

use App\Http\Middleware\SetLocale;
use App\Modules\Assistant\Support\AssistantUiSupport;
use App\Modules\Branches\Models\Branch;
use App\Support\Subscription\SubscriptionAccessEvaluator;
use App\Modules\Public\Support\BrandTokens;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $locale = app()->getLocale();
        $branch = $user?->branch_id ? Branch::query()->with('plan')->find($user->branch_id) : null;

        return array_merge(parent::share($request), [
            'name' => config('app.name'),
            'locale' => $locale,
            'dir' => SetLocale::dirFor($locale),
            'available_locales' => SetLocale::SUPPORTED,
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'branch_id' => $user->branch_id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_active' => $user->is_active,
                    'roles' => $user->getRoleNames()->values(),
                    'can_manage_subscription' => $user->hasRole('admin'),
                    'unread_notifications_count' => $user->unreadNotifications()->count(),
                ] : null,
            ],
            'features' => [
                'messages' => (bool) config('features.messages.enabled', true),
                'notifications' => (bool) config('features.notifications.enabled', true),
                'assistant' => (bool) config('assistant.enabled', true),
                'realtime' => (bool) config('features.realtime.enabled', true),
            ],
            'assistantMeta' => app(AssistantUiSupport::class)->build(
                $user,
                $request->path(),
                $locale,
            ),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'branding' => fn () => BrandTokens::fromBranch($branch),
            'subscription' => function () use ($branch) {
                if ($branch === null) {
                    $noBranch = SubscriptionAccessEvaluator::noBranchAttached();

                    return [
                        'plan' => null,
                        'plan_id' => null,
                        'status' => null,
                        'trial_ends_at' => null,
                        'subscription_ends_at' => null,
                        'current_period_ends_at' => null,
                        'grace_days' => SubscriptionAccessEvaluator::graceDays(),
                        'has_access' => false,
                        'reason_code' => $noBranch->reasonCode,
                        'reason' => $noBranch->explanation,
                        'entitlement_summary' => null,
                        'grandfathered_without_plan' => false,
                    ];
                }

                $assessment = $branch->subscriptionAccessAssessment();

                return [
                    'plan' => $branch->subscription_plan,
                    'plan_id' => $branch->plan_id,
                    'status' => $branch->subscription_status?->value,
                    'trial_ends_at' => $branch->trial_ends_at?->toIso8601String(),
                    'subscription_ends_at' => $branch->subscription_ends_at?->toIso8601String(),
                    'current_period_ends_at' => $branch->current_period_ends_at?->toIso8601String(),
                    'grace_days' => SubscriptionAccessEvaluator::graceDays(),
                    'has_access' => $assessment->allowed,
                    'reason_code' => $assessment->reasonCode,
                    'reason' => $assessment->explanation,
                    'entitlement_summary' => $branch->entitlementSummary(),
                    'grandfathered_without_plan' => $branch->plan_id === null,
                ];
            },
        ]);
    }
}
