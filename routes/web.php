<?php

use App\Http\Controllers\MessagesController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\AssistantController;
use App\Http\Controllers\SubscriptionNoticeController;
use App\Modules\Billing\Http\Controllers\InvoiceController;
use App\Modules\Billing\Http\Controllers\StripeWebhookController;
use App\Modules\Branches\Http\Controllers\BranchBillingController;
use App\Modules\Branches\Http\Controllers\BranchSettingsController;
use App\Modules\Branches\Http\Controllers\PlanSettingsController;
use App\Support\Subscription\PlanFeatureKey;
use App\Modules\Inventory\Http\Controllers\InventoryController;
use App\Modules\Kitchen\Http\Controllers\KitchenBoardController;
use App\Modules\Menu\Http\Controllers\MenuCategoryController;
use App\Modules\Menu\Http\Controllers\MenuItemController;
use App\Modules\Orders\Http\Controllers\OrderController;
use App\Modules\Public\Http\Controllers\PublicLandingController;
use App\Modules\Public\Http\Controllers\PublicMenuController;
use App\Modules\Reports\Http\Controllers\ReportController;
use App\Modules\Shared\Http\Controllers\DashboardController;
use App\Modules\Tables\Http\Controllers\TableController;
use App\Modules\Users\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;


// Public (unauthenticated) routes
Route::get('/', PublicLandingController::class)->name('home');
Route::get('/menu', [PublicMenuController::class, 'index'])->name('public.menu');

// Locale switcher — public (works pre-auth on landing/login).
Route::post('/locale', [\App\Http\Controllers\LocaleController::class, 'update'])->name('locale.update');
Route::post('/billing/stripe/webhook', StripeWebhookController::class)->name('billing.stripe.webhook');

Route::middleware(['auth'])->group(function () {
    Route::get('/subscription', SubscriptionNoticeController::class)->name('subscription.notice');

    Route::middleware(['role:admin'])->group(function () {
        Route::post('/settings/branch/billing/checkout', [BranchBillingController::class, 'checkout'])->name('branch.billing.checkout');
        Route::post('/settings/branch/billing/cancel', [BranchBillingController::class, 'cancel'])->name('branch.billing.cancel');
        Route::post('/settings/branch/billing/portal', [BranchBillingController::class, 'portal'])->name('branch.billing.portal');
    });

    Route::middleware('branch.subscription')->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
  Route::get('/assistant', [AssistantController::class, 'index'])
        ->name('assistant.index');

    Route::post('/assistant/conversations', [AssistantController::class, 'storeConversation'])
        ->middleware('throttle:assistant')
        ->name('assistant.conversations.store');

    Route::post('/assistant/panel/messages', [AssistantController::class, 'storePanelMessage'])
        ->middleware('throttle:assistant')
        ->name('assistant.panel.messages.store');

    Route::get('/assistant/{conversation}', [AssistantController::class, 'show'])
        ->whereNumber('conversation')
        ->name('assistant.show');

    Route::post('/assistant/{conversation}/messages', [AssistantController::class, 'storeMessage'])
        ->whereNumber('conversation')
        ->middleware('throttle:assistant')
        ->name('assistant.messages.store');
        Route::get('/notifications', [NotificationsController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/read-all', [NotificationsController::class, 'markAllRead'])->name('notifications.read_all');
        Route::post('/notifications/{notification}/read', [NotificationsController::class, 'markRead'])->name('notifications.read');
        Route::delete('/notifications/{notification}', [NotificationsController::class, 'destroy'])->name('notifications.destroy');

        Route::get('/messages', [MessagesController::class, 'index'])->name('messages.index');
        Route::get('/messages/{conversation}', [MessagesController::class, 'show'])->name('messages.show');
        Route::post('/messages/conversations', [MessagesController::class, 'storeConversation'])->name('messages.conversations.store');
        Route::post('/messages/{conversation}/messages', [MessagesController::class, 'storeMessage'])->name('messages.messages.store');
        Route::post('/messages/{conversation}/read', [MessagesController::class, 'markRead'])->name('messages.read');

        Route::middleware('role:admin')->group(function () {
            Route::get('/users', [UserController::class, 'index'])->name('users.index');
            Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
            Route::post('/users', [UserController::class, 'store'])->name('users.store');
            Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
            Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
            Route::patch('/users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');

            // Subscription management - admin only
            Route::patch('/settings/branch/subscription', [BranchSettingsController::class, 'updateSubscription'])->name('branch.subscription.update');

            Route::get('/settings/plans', [PlanSettingsController::class, 'index'])->name('plans.index');
            Route::get('/settings/plans/create', [PlanSettingsController::class, 'create'])->name('plans.create');
            Route::post('/settings/plans', [PlanSettingsController::class, 'store'])->name('plans.store');
            Route::get('/settings/plans/{plan}/edit', [PlanSettingsController::class, 'edit'])->name('plans.edit');
            Route::put('/settings/plans/{plan}', [PlanSettingsController::class, 'update'])->name('plans.update');
            Route::delete('/settings/plans/{plan}', [PlanSettingsController::class, 'destroy'])->name('plans.destroy');
        });

        Route::middleware('role:admin|manager')->group(function () {
            Route::get('/settings/branch', [BranchSettingsController::class, 'edit'])->name('branch.edit');
            Route::put('/settings/branch', [BranchSettingsController::class, 'update'])->name('branch.update');

            Route::get('/menu/categories', [MenuCategoryController::class, 'index'])->name('menu.categories.index');
            Route::get('/menu/categories/create', [MenuCategoryController::class, 'create'])->name('menu.categories.create');
            Route::post('/menu/categories', [MenuCategoryController::class, 'store'])->name('menu.categories.store');
            Route::get('/menu/categories/{category}/edit', [MenuCategoryController::class, 'edit'])->name('menu.categories.edit');
            Route::put('/menu/categories/{category}', [MenuCategoryController::class, 'update'])->name('menu.categories.update');

            Route::get('/menu/items', [MenuItemController::class, 'index'])->name('menu.items.index');
            Route::get('/menu/items/create', [MenuItemController::class, 'create'])->name('menu.items.create');
            Route::post('/menu/items', [MenuItemController::class, 'store'])->name('menu.items.store');
            Route::get('/menu/items/{item}/edit', [MenuItemController::class, 'edit'])->name('menu.items.edit');
            Route::put('/menu/items/{item}', [MenuItemController::class, 'update'])->name('menu.items.update');
            Route::patch('/menu/items/{item}/availability', [MenuItemController::class, 'availability'])->name('menu.items.availability');

            Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
            Route::get('/inventory/create', [InventoryController::class, 'create'])->name('inventory.create');
            Route::post('/inventory', [InventoryController::class, 'store'])->name('inventory.store');
            Route::get('/inventory/{item}/edit', [InventoryController::class, 'edit'])->name('inventory.edit');
            Route::put('/inventory/{item}', [InventoryController::class, 'update'])->name('inventory.update');
            Route::post('/inventory/{item}/adjustments', [InventoryController::class, 'adjust'])->name('inventory.adjust');

            Route::get('/reports', [ReportController::class, 'index'])
                ->middleware('plan.feature:'.PlanFeatureKey::REPORTS)
                ->name('reports.index');
        });

        Route::middleware('role:admin|manager|waiter|cashier')->group(function () {
            Route::get('/tables', [TableController::class, 'index'])->name('tables.index');
        });

        Route::middleware('role:admin|manager')->group(function () {
            Route::get('/tables/create', [TableController::class, 'create'])->name('tables.create');
            Route::post('/tables', [TableController::class, 'store'])->name('tables.store');
            Route::get('/tables/{table}/edit', [TableController::class, 'edit'])->name('tables.edit');
            Route::put('/tables/{table}', [TableController::class, 'update'])->name('tables.update');
            Route::patch('/tables/{table}/status', [TableController::class, 'status'])->name('tables.status');
        });

        Route::middleware('role:admin|manager|waiter')->group(function () {
            Route::get('/orders/create', [OrderController::class, 'create'])->name('orders.create');
            Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
            Route::post('/orders/{order}/items', [OrderController::class, 'addItems'])->name('orders.items.store');
            Route::patch('/orders/{order}/submit', [OrderController::class, 'submit'])->name('orders.submit');
            Route::patch('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
        });

        Route::middleware('role:admin|manager|waiter|cashier')->group(function () {
            Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
            Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        });

        Route::middleware(['role:admin|manager|kitchen', 'plan.feature:'.PlanFeatureKey::KITCHEN])->group(function () {
            Route::get('/kitchen', [KitchenBoardController::class, 'index'])->name('kitchen.index');
            Route::get('/kitchen/queue', [KitchenBoardController::class, 'queue'])->name('kitchen.queue');
            Route::patch('/kitchen/orders/{order}/ready', [KitchenBoardController::class, 'ready'])->name('kitchen.ready');
        });

        Route::middleware('role:admin|manager|cashier')->group(function () {
            Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
            Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
            Route::post('/orders/{order}/invoice', [InvoiceController::class, 'store'])->name('invoices.store');
            Route::patch('/invoices/{invoice}/pay', [InvoiceController::class, 'pay'])->name('invoices.pay');
        });
    });
});

require __DIR__.'/auth.php';
require __DIR__.'/settings.php';
