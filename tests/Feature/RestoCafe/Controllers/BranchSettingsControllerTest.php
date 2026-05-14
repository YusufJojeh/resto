<?php

namespace Tests\Feature\RestoCafe\Controllers;

use Tests\Feature\RestoCafe\RestoCafeTestCase;

class BranchSettingsControllerTest extends RestoCafeTestCase
{
    public function test_edit_ok_admin(): void
    {
        $this->actingAs($this->admin())->get(route('branch.edit'))->assertOk();
    }

    public function test_edit_forbidden_waiter(): void
    {
        $this->actingAs($this->waiter())->get(route('branch.edit'))->assertForbidden();
    }

    public function test_update_ok(): void
    {
        $this->actingAs($this->manager())->put(route('branch.update'), [
            'name' => 'Renamed',
            'address' => 'New addr',
            'phone' => '+1 555',
            'tax_rate' => 5.5,
            'currency_code' => 'EUR',
        ])->assertRedirect(route('branch.edit'));

        $this->assertDatabaseHas('branches', ['id' => 1, 'name' => 'Renamed', 'currency_code' => 'EUR']);
    }

    public function test_update_validation_errors(): void
    {
        $this->actingAs($this->manager())->put(route('branch.update'), [])
            ->assertSessionHasErrors(['name', 'tax_rate', 'currency_code']);
    }

    public function test_update_rejects_tax_above_100(): void
    {
        $this->actingAs($this->manager())->put(route('branch.update'), [
            'name' => 'x', 'tax_rate' => 200, 'currency_code' => 'USD',
        ])->assertSessionHasErrors(['tax_rate']);
    }

    public function test_update_rejects_negative_tax(): void
    {
        $this->actingAs($this->manager())->put(route('branch.update'), [
            'name' => 'x', 'tax_rate' => -1, 'currency_code' => 'USD',
        ])->assertSessionHasErrors(['tax_rate']);
    }

    public function test_update_forbidden_waiter(): void
    {
        $this->actingAs($this->waiter())->put(route('branch.update'), [
            'name' => 'x', 'tax_rate' => 1, 'currency_code' => 'USD',
        ])->assertForbidden();
    }
}
