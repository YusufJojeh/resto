<?php

namespace App\Enums;

enum InventoryMovementType: string
{
    case Restock = 'restock';
    case Deduction = 'deduction';
    case Waste = 'waste';
    case Correction = 'correction';
}
