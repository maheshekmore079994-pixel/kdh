<?php

namespace App\Enums;

enum JobStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Processing => 'blue',
            self::Completed => 'green',
            self::Delivered => 'emerald',
            self::Cancelled => 'zinc',
        };
    }

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Processing, self::Completed, self::Cancelled],
            self::Processing => [self::Completed, self::Pending, self::Cancelled],
            self::Completed => [self::Delivered, self::Processing, self::Cancelled],
            self::Delivered => [],
            self::Cancelled => [self::Pending],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Pending, self::Processing], true);
    }
}
