<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\Billing\Actions\ApplyProviderSubscriptionPatch;
use App\Support\Billing\Actions\ProcessBillingWebhook;
use App\Support\Billing\BillingProviderManager;
use App\Support\Billing\Contracts\BillingProviderContract;
use App\Support\Billing\Contracts\StripeSubscriptionGatewayContract;
use App\Support\Billing\Providers\StripeBillingProvider;
use App\Support\Billing\Stripe\StripeWebhookToPatchMapper;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(StripeBillingProvider::class, function (): StripeBillingProvider {
            return new StripeBillingProvider(
                filled(config('billing.stripe.secret_key'))
                    ? (string) config('billing.stripe.secret_key')
                    : null,

                filled(config('billing.stripe_api_version'))
                    ? (string) config('billing.stripe_api_version')
                    : null,
            );
        });

        $this->app->bind(
            BillingProviderContract::class,
            function (Application $app): BillingProviderContract {
                return match (config('billing.provider')) {
                    default => $app->make(StripeBillingProvider::class),
                };
            },
        );

        $this->app->singleton(
            BillingProviderManager::class,
            fn (Application $app): BillingProviderManager => new BillingProviderManager($app),
        );

        $this->app->singleton(
            StripeSubscriptionGatewayContract::class,
            fn (Application $app): StripeSubscriptionGatewayContract => $app->make(StripeBillingProvider::class),
        );

        $this->app->singleton(
            StripeWebhookToPatchMapper::class,
            StripeWebhookToPatchMapper::class,
        );

        $this->app->singleton(
            ApplyProviderSubscriptionPatch::class,
            ApplyProviderSubscriptionPatch::class,
        );

        $this->app->singleton(
            ProcessBillingWebhook::class,
            ProcessBillingWebhook::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('assistant', function (Request $request) {
            $key = $request->user()?->id
                ? 'assistant:' . $request->user()->id
                : 'assistant:' . $request->ip();

            return Limit::perMinute((int) config('assistant.rate_limit', 30))->by($key);
        });
    }
}
