<?php

namespace App\Enums;

enum StockMovementType: string
{
    case Opening = 'opening';
    case Purchase = 'purchase';
    case Sale = 'sale';
    case Consumption = 'consumption';
    case Adjustment = 'adjustment';
    case Wastage = 'wastage';
    case PurchaseReversal = 'purchase_reversal';
    case SaleReversal = 'sale_reversal';

    public function label(): string
    {
        return match ($this) {
            self::Opening => 'Opening stock',
            self::Purchase => 'Purchase',
            self::Sale => 'Sale',
            self::Consumption => 'Consumption',
            self::Adjustment => 'Adjustment',
            self::Wastage => 'Wastage',
            self::PurchaseReversal => 'Purchase reversal',
            self::SaleReversal => 'Sale reversal',
        };
    }

    /** Types an operator may record manually. */
    public static function manual(): array
    {
        return [self::Consumption, self::Adjustment, self::Wastage];
    }
}
