<?php

namespace App\Enums;

enum OrderStatus: string
{
    case New = 'new';
    case InKitchen = 'in_kitchen';
    case Ready = 'ready';
    case Served = 'served';
    case Cancelled = 'cancelled';

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::New => in_array($next, [self::InKitchen, self::Cancelled], true),
            self::InKitchen => in_array($next, [self::Ready, self::Cancelled], true),
            self::Ready => $next === self::Served,
            self::Served, self::Cancelled => false,
        };
    }
}
