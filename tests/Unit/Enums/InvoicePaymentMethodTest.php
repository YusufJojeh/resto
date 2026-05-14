<?php

namespace Tests\Unit\Enums;

use App\Enums\InvoicePaymentMethod;
use PHPUnit\Framework\TestCase;

class InvoicePaymentMethodTest extends TestCase
{
    public function test_has_cash_and_card(): void
    {
        $this->assertSame('cash', InvoicePaymentMethod::Cash->value);
        $this->assertSame('card', InvoicePaymentMethod::Card->value);
        $this->assertCount(2, InvoicePaymentMethod::cases());
    }

    public function test_from_string_cash(): void
    {
        $this->assertSame(InvoicePaymentMethod::Cash, InvoicePaymentMethod::from('cash'));
    }

    public function test_from_string_card(): void
    {
        $this->assertSame(InvoicePaymentMethod::Card, InvoicePaymentMethod::from('card'));
    }

    public function test_from_string_invalid_throws(): void
    {
        $this->expectException(\ValueError::class);
        InvoicePaymentMethod::from('crypto');
    }
}
