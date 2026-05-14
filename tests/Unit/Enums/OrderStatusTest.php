<?php

namespace Tests\Unit\Enums;

use App\Enums\OrderStatus;
use PHPUnit\Framework\TestCase;

class OrderStatusTest extends TestCase
{
    public static function transitionMatrixProvider(): array
    {
        $cases = [
            [OrderStatus::New, OrderStatus::InKitchen, true],
            [OrderStatus::New, OrderStatus::Cancelled, true],
            [OrderStatus::New, OrderStatus::Ready, false],
            [OrderStatus::New, OrderStatus::Served, false],
            [OrderStatus::New, OrderStatus::New, false],

            [OrderStatus::InKitchen, OrderStatus::Ready, true],
            [OrderStatus::InKitchen, OrderStatus::Cancelled, true],
            [OrderStatus::InKitchen, OrderStatus::New, false],
            [OrderStatus::InKitchen, OrderStatus::Served, false],
            [OrderStatus::InKitchen, OrderStatus::InKitchen, false],

            [OrderStatus::Ready, OrderStatus::Served, true],
            [OrderStatus::Ready, OrderStatus::New, false],
            [OrderStatus::Ready, OrderStatus::InKitchen, false],
            [OrderStatus::Ready, OrderStatus::Cancelled, false],
            [OrderStatus::Ready, OrderStatus::Ready, false],

            [OrderStatus::Served, OrderStatus::New, false],
            [OrderStatus::Served, OrderStatus::InKitchen, false],
            [OrderStatus::Served, OrderStatus::Ready, false],
            [OrderStatus::Served, OrderStatus::Cancelled, false],
            [OrderStatus::Served, OrderStatus::Served, false],

            [OrderStatus::Cancelled, OrderStatus::New, false],
            [OrderStatus::Cancelled, OrderStatus::InKitchen, false],
            [OrderStatus::Cancelled, OrderStatus::Ready, false],
            [OrderStatus::Cancelled, OrderStatus::Served, false],
            [OrderStatus::Cancelled, OrderStatus::Cancelled, false],
        ];

        $data = [];
        foreach ($cases as $row) {
            $data[$row[0]->value.'->'.$row[1]->value] = $row;
        }
        return $data;
    }

    /** @dataProvider transitionMatrixProvider */
    public function test_can_transition_to(OrderStatus $from, OrderStatus $to, bool $expected): void
    {
        $this->assertSame($expected, $from->canTransitionTo($to));
    }

    public function test_enum_has_all_expected_cases(): void
    {
        $values = array_map(fn ($c) => $c->value, OrderStatus::cases());
        sort($values);
        $this->assertSame(['cancelled', 'in_kitchen', 'new', 'ready', 'served'], $values);
    }
}
