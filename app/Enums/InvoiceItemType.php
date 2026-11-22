<?php

namespace App\Enums;

enum InvoiceItemType: string
{
    case Service = 'service';
    case Product = 'product';
}
