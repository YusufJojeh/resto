<?php

namespace App\Enums;

enum InvoicePaymentMethod: string
{
    case Cash = 'cash';
    case Card = 'card';
}
