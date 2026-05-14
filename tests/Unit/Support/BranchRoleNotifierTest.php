<?php

namespace Tests\Unit\Support;

use App\Enums\UserRole;
use App\Models\User;
use App\Modules\Branches\Models\Branch;
use App\Notifications\OperationalNotification;
use App\Support\Notifications\BranchRoleNotifier;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BranchRoleNotifierTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_only_users_with_matching_roles_are_notified(): void
    {
        Notification::fake();

        $branch = Branch::query()->create([
            'name' => 'Test',
            'address' => 'A',
            'phone' => null,
            'tax_rate' => 0,
            'currency_code' => 'USD',
            'is_active' => true,
            'subscription_status' => 'active',
        ]);

        $admin = User::query()->create([
            'branch_id' => $branch->id,
            'name' => 'Admin',
            'email' => 'notifier-admin@test.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $admin->syncRoles([UserRole::Admin->value]);

        $waiter = User::query()->create([
            'branch_id' => $branch->id,
            'name' => 'Waiter',
            'email' => 'notifier-waiter@test.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $waiter->syncRoles([UserRole::Waiter->value]);

        $kitchen = User::query()->create([
            'branch_id' => $branch->id,
            'name' => 'Kitchen',
            'email' => 'notifier-kitchen@test.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $kitchen->syncRoles([UserRole::Kitchen->value]);

        $notifier = new BranchRoleNotifier();
        $notification = new OperationalNotification('test', 'Test', 'Test body', $branch->id);

        $notifier->notifyByRoles($branch->id, [UserRole::Admin, UserRole::Kitchen], $notification);

        Notification::assertSentTo($admin, OperationalNotification::class);
        Notification::assertSentTo($kitchen, OperationalNotification::class);
        Notification::assertNotSentTo($waiter, OperationalNotification::class);
    }

    public function test_inactive_users_are_not_notified(): void
    {
        Notification::fake();

        $branch = Branch::query()->create([
            'name' => 'Test',
            'address' => 'A',
            'phone' => null,
            'tax_rate' => 0,
            'currency_code' => 'USD',
            'is_active' => true,
            'subscription_status' => 'active',
        ]);

        $activeAdmin = User::query()->create([
            'branch_id' => $branch->id,
            'name' => 'Active Admin',
            'email' => 'active-admin@test.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $activeAdmin->syncRoles([UserRole::Admin->value]);

        $inactiveAdmin = User::query()->create([
            'branch_id' => $branch->id,
            'name' => 'Inactive Admin',
            'email' => 'inactive-admin@test.com',
            'password' => Hash::make('password'),
            'is_active' => false,
        ]);
        $inactiveAdmin->syncRoles([UserRole::Admin->value]);

        $notifier = new BranchRoleNotifier();
        $notification = new OperationalNotification('test', 'Test', 'Test body', $branch->id);

        $notifier->notifyByRoles($branch->id, [UserRole::Admin], $notification);

        Notification::assertSentTo($activeAdmin, OperationalNotification::class);
        Notification::assertNotSentTo($inactiveAdmin, OperationalNotification::class);
    }
}
