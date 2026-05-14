<?php

namespace Tests\Feature\RestoCafe;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationBoundaryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_kitchen_staff_cannot_access_user_management(): void
    {
        $kitchen = User::query()->where('email', 'kitchen@restocafe.test')->firstOrFail();

        $this->actingAs($kitchen)
            ->get(route('users.index'))
            ->assertForbidden();
    }
}
