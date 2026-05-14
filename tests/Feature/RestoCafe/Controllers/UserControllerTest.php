<?php

namespace Tests\Feature\RestoCafe\Controllers;

use App\Models\User;
use Tests\Feature\RestoCafe\RestoCafeTestCase;

class UserControllerTest extends RestoCafeTestCase
{
    public function test_index_admin_ok(): void
    {
        $this->actingAs($this->admin())->get(route('users.index'))->assertOk();
    }

    public function test_index_manager_forbidden(): void
    {
        $this->actingAs($this->manager())->get(route('users.index'))->assertForbidden();
    }

    public function test_create_ok(): void
    {
        $this->actingAs($this->admin())->get(route('users.create'))->assertOk();
    }

    public function test_store_creates_user_with_role(): void
    {
        $this->actingAs($this->admin())->post(route('users.store'), [
            'name' => 'New Guy',
            'email' => 'new@rc.test',
            'password' => 'password1',
            'password_confirmation' => 'password1',
            'role' => 'waiter',
        ])->assertRedirect(route('users.index'));

        $u = User::query()->where('email', 'new@rc.test')->firstOrFail();
        $this->assertTrue($u->hasRole('waiter'));
        $this->assertSame(1, $u->branch_id);
    }

    public function test_store_validation_errors(): void
    {
        $this->actingAs($this->admin())->post(route('users.store'), [])
            ->assertSessionHasErrors(['name', 'email', 'password', 'role']);
    }

    public function test_store_rejects_duplicate_email(): void
    {
        $this->actingAs($this->admin())->post(route('users.store'), [
            'name' => 'Dupe',
            'email' => 'waiter@restocafe.test',
            'password' => 'password1',
            'password_confirmation' => 'password1',
            'role' => 'waiter',
        ])->assertSessionHasErrors(['email']);
    }

    public function test_store_rejects_mismatched_confirmation(): void
    {
        $this->actingAs($this->admin())->post(route('users.store'), [
            'name' => 'x', 'email' => 'x@y.z',
            'password' => 'password1', 'password_confirmation' => 'nope',
            'role' => 'waiter',
        ])->assertSessionHasErrors(['password']);
    }

    public function test_store_rejects_invalid_role(): void
    {
        $this->actingAs($this->admin())->post(route('users.store'), [
            'name' => 'x', 'email' => 'y@y.z',
            'password' => 'password1', 'password_confirmation' => 'password1',
            'role' => 'owner',
        ])->assertSessionHasErrors(['role']);
    }

    public function test_edit_ok(): void
    {
        $this->actingAs($this->admin())->get(route('users.edit', $this->waiter()))->assertOk();
    }

    public function test_update_without_password(): void
    {
        $u = $this->waiter();
        $originalHash = $u->password;
        $this->actingAs($this->admin())->put(route('users.update', $u), [
            'name' => 'New Name',
            'email' => $u->email,
            'role' => 'waiter',
            'is_active' => true,
        ])->assertRedirect(route('users.index'));
        $this->assertSame('New Name', $u->fresh()->name);
        $this->assertSame($originalHash, $u->fresh()->password);
    }

    public function test_update_with_password(): void
    {
        $u = $this->waiter();
        $this->actingAs($this->admin())->put(route('users.update', $u), [
            'name' => $u->name,
            'email' => $u->email,
            'password' => 'newpassword1',
            'password_confirmation' => 'newpassword1',
            'role' => 'waiter',
            'is_active' => true,
        ])->assertRedirect();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('newpassword1', $u->fresh()->password));
    }

    public function test_update_role_change_applies(): void
    {
        $u = $this->waiter();
        $this->actingAs($this->admin())->put(route('users.update', $u), [
            'name' => $u->name,
            'email' => $u->email,
            'role' => 'manager',
            'is_active' => true,
        ])->assertRedirect();
        $this->assertTrue($u->fresh()->hasRole('manager'));
        $this->assertFalse($u->fresh()->hasRole('waiter'));
    }

    public function test_update_ignores_own_email_for_unique(): void
    {
        $u = $this->waiter();
        $this->actingAs($this->admin())->put(route('users.update', $u), [
            'name' => 'x',
            'email' => $u->email,
            'role' => 'waiter',
        ])->assertRedirect();
    }

    public function test_deactivate_user(): void
    {
        $u = $this->waiter();
        $this->actingAs($this->admin())
            ->patch(route('users.deactivate', $u))
            ->assertRedirect();
        $this->assertFalse((bool) $u->fresh()->is_active);
    }

    public function test_deactivate_self_blocked(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)
            ->patch(route('users.deactivate', $admin))
            ->assertStatus(422);
        $this->assertTrue((bool) $admin->fresh()->is_active);
    }

    public function test_non_admin_cannot_access_any_user_route(): void
    {
        $this->actingAs($this->manager())->get(route('users.create'))->assertForbidden();
        $this->actingAs($this->manager())->post(route('users.store'), [])->assertForbidden();
        $this->actingAs($this->manager())->patch(route('users.deactivate', $this->waiter()))->assertForbidden();
    }

    public function test_index_only_returns_users_from_admins_branch(): void
    {
        $other = $this->makeSecondaryBranch();

        $response = $this->actingAs($this->admin())->get(route('users.index'))->assertOk();

        $payload = $response->viewData('page')['props']['users'];
        $emails = collect($payload)->pluck('email')->all();

        $this->assertNotContains($other['users']['admin']->email, $emails);
        $this->assertContains('admin@restocafe.test', $emails);
    }

    public function test_admin_cannot_edit_user_from_another_branch(): void
    {
        $other = $this->makeSecondaryBranch();
        $foreignUser = $other['users']['waiter'];

        $this->actingAs($this->admin())
            ->get(route('users.edit', $foreignUser))
            ->assertNotFound();
    }

    public function test_admin_cannot_update_user_from_another_branch(): void
    {
        $other = $this->makeSecondaryBranch();
        $foreignUser = $other['users']['waiter'];
        $originalName = $foreignUser->name;

        $this->actingAs($this->admin())
            ->put(route('users.update', $foreignUser), [
                'name' => 'Pwned',
                'email' => $foreignUser->email,
                'role' => 'admin',
                'is_active' => true,
            ])
            ->assertNotFound();

        $this->assertSame($originalName, $foreignUser->fresh()->name);
        $this->assertFalse($foreignUser->fresh()->hasRole('admin'));
    }

    public function test_admin_cannot_deactivate_user_from_another_branch(): void
    {
        $other = $this->makeSecondaryBranch();
        $foreignUser = $other['users']['waiter'];

        $this->actingAs($this->admin())
            ->patch(route('users.deactivate', $foreignUser))
            ->assertNotFound();

        $this->assertTrue((bool) $foreignUser->fresh()->is_active);
    }
}
