<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'System Admin', 'email' => 'admin@restocafe.test', 'role' => UserRole::Admin],
            ['name' => 'Branch Manager', 'email' => 'manager@restocafe.test', 'role' => UserRole::Manager],
            ['name' => 'Floor Waiter', 'email' => 'waiter@restocafe.test', 'role' => UserRole::Waiter],
            ['name' => 'Cashier Desk', 'email' => 'cashier@restocafe.test', 'role' => UserRole::Cashier],
            ['name' => 'Kitchen Staff', 'email' => 'kitchen@restocafe.test', 'role' => UserRole::Kitchen],
        ];

        foreach ($users as $seed) {
            $user = User::query()->updateOrCreate(
                ['email' => $seed['email']],
                [
                    'branch_id' => 1,
                    'name' => $seed['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'is_active' => true,
                ],
            );

            $user->syncRoles([$seed['role']->value]);
        }
    }
}
