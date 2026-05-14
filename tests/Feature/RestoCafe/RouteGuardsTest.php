<?php

namespace Tests\Feature\RestoCafe;

class RouteGuardsTest extends RestoCafeTestCase
{
    /**
     * Every restricted route redirects unauthenticated users to /login.
     */
    public static function guestRoutes(): array
    {
        return [
            ['dashboard'], ['tables.index'], ['orders.index'],
            ['kitchen.index'], ['invoices.index'], ['inventory.index'],
            ['menu.categories.index'], ['menu.items.index'],
            ['users.index'], ['branch.edit'], ['reports.index'],
            ['messages.index'], ['notifications.index'],
        ];
    }

    /** @dataProvider guestRoutes */
    public function test_unauthenticated_is_redirected_to_login(string $name): void
    {
        $this->get(route($name))->assertRedirect(route('login'));
    }

    public function test_home_returns_public_landing_page(): void
    {
        $this->get('/')->assertOk();
    }

    public static function adminOnly(): array
    {
        return [
            ['users.index'], ['users.create'],
        ];
    }

    /** @dataProvider adminOnly */
    public function test_non_admin_forbidden_on_admin_only(string $name): void
    {
        foreach (['manager', 'waiter', 'cashier', 'kitchen'] as $role) {
            $user = \App\Models\User::query()->where('email', $role.'@restocafe.test')->firstOrFail();
            $this->actingAs($user)->get(route($name))->assertForbidden();
        }
    }

    public function test_menu_routes_forbidden_for_kitchen(): void
    {
        foreach (['menu.categories.index', 'menu.items.index', 'inventory.index', 'reports.index', 'branch.edit'] as $r) {
            $this->actingAs($this->kitchen())->get(route($r))->assertForbidden();
        }
    }

    public function test_orders_create_forbidden_for_cashier(): void
    {
        $this->actingAs($this->cashier())->get(route('orders.create'))->assertForbidden();
    }

    public function test_kitchen_routes_forbidden_for_cashier_and_waiter(): void
    {
        foreach ([$this->cashier(), $this->waiter()] as $u) {
            $this->actingAs($u)->get(route('kitchen.index'))->assertForbidden();
        }
    }

    public function test_invoice_routes_forbidden_for_waiter_and_kitchen(): void
    {
        foreach ([$this->waiter(), $this->kitchen()] as $u) {
            $this->actingAs($u)->get(route('invoices.index'))->assertForbidden();
        }
    }

    public function test_messages_and_notifications_are_accessible_to_all_authenticated_roles(): void
    {
        foreach ([$this->admin(), $this->manager(), $this->waiter(), $this->cashier(), $this->kitchen()] as $user) {
            $this->actingAs($user)->get(route('messages.index'))->assertOk();
            $this->actingAs($user)->get(route('notifications.index'))->assertOk();
        }
    }
}
