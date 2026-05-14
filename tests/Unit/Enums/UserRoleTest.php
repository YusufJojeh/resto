<?php

namespace Tests\Unit\Enums;

use App\Enums\UserRole;
use PHPUnit\Framework\TestCase;

class UserRoleTest extends TestCase
{
    public function test_values_returns_all_role_strings(): void
    {
        $values = UserRole::values();
        sort($values);
        $this->assertSame(['admin', 'cashier', 'kitchen', 'manager', 'waiter'], $values);
    }

    public function test_values_returns_plain_strings(): void
    {
        foreach (UserRole::values() as $v) {
            $this->assertIsString($v);
        }
    }

    public function test_each_case_maps_to_its_value(): void
    {
        $this->assertSame('admin', UserRole::Admin->value);
        $this->assertSame('manager', UserRole::Manager->value);
        $this->assertSame('waiter', UserRole::Waiter->value);
        $this->assertSame('cashier', UserRole::Cashier->value);
        $this->assertSame('kitchen', UserRole::Kitchen->value);
    }

    public function test_from_string_returns_case(): void
    {
        $this->assertSame(UserRole::Admin, UserRole::from('admin'));
    }
}
