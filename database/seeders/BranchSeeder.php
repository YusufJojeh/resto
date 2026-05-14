<?php

namespace Database\Seeders;

use App\Modules\Branches\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        Branch::query()->updateOrCreate(
            ['id' => 1],
            [
                'name' => 'Main Branch',
                'address' => 'Demo Street, Istanbul',
                'phone' => '+90 555 000 0000',
                'tax_rate' => 10.00,
                'currency_code' => 'USD',
                'is_active' => true,
                'subscription_status' => 'active',
            ],
        );
    }
}
