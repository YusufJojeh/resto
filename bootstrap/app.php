<?php

use App\Http\Middleware\EnsureBranchPlanFeature;
use App\Http\Middleware\EnsureBranchSubscriptionAccess;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            SetLocale::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'branch.subscription' => EnsureBranchSubscriptionAccess::class,
            'plan.feature' => EnsureBranchPlanFeature::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);

        $middleware->validateCsrfTokens(except: ['billing/stripe/webhook']);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->respond(function (\Symfony\Component\HttpFoundation\Response $response, \Throwable $exception, Request $request) {
            if ($response->getStatusCode() === 404 && ! $request->expectsJson()) {
                $inertiaResponse = \Inertia\Inertia::render('errors/not-found');

                if ($request->header('X-Inertia')) {
                    $inertiaRequest = $request->duplicate();
                    $inertiaRequest->headers->set('X-Inertia', 'true');

                    return $inertiaResponse->toResponse($inertiaRequest)->setStatusCode(404);
                }

                return $inertiaResponse->toResponse($request)->setStatusCode(404);
            }

            return $response;
        });
    })->create();
