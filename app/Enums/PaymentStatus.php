<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Unpaid = 'unpaid';
    case Partial = 'partial';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Unpaid',
            self::Partial => 'Partially paid',
            self::Paid => 'Paid',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Unpaid => 'red',
            self::Partial => 'amber',
            self::Paid => 'green',
        };
    }

    public static function fromAmounts(string $paid, string $total): self
    {
        if (bccomp($paid, '0', 2) <= 0) {
            return self::Unpaid;
        }

        return bccomp($paid, $total, 2) >= 0 ? self::Paid : self::Partial;
    }
}
