<?php

namespace App\Services;

use App\Models\NumberSequence;
use Illuminate\Support\Facades\DB;

/**
 * Generates unique, gap-tolerant document numbers like INV-2026-00001.
 * Must be called inside a database transaction so the row lock is held until commit.
 */
class NumberingService
{
    public function __construct(private readonly SettingsService $settings) {}

    public function next(string $key): string
    {
        $generate = function () use ($key): string {
            /** @var NumberSequence $sequence */
            $sequence = NumberSequence::query()->where('key', $key)->lockForUpdate()->first()
                ?? NumberSequence::query()->create([
                    'key' => $key,
                    'prefix' => $this->defaultPrefix($key),
                    'padding' => 5,
                    'next_number' => 1,
                    'reset_yearly' => true,
                    'year' => (int) now()->format('Y'),
                ]);

            $year = (int) now()->format('Y');
            if ($sequence->reset_yearly && $sequence->year !== $year) {
                $sequence->year = $year;
                $sequence->next_number = 1;
            }

            $number = $sequence->next_number;
            $sequence->next_number = $number + 1;
            $sequence->save();

            $prefix = $sequence->prefix !== '' ? $sequence->prefix.'-' : '';
            $yearPart = $sequence->reset_yearly ? $year.'-' : '';

            return $prefix.$yearPart.str_pad((string) $number, $sequence->padding, '0', STR_PAD_LEFT);
        };

        return DB::transactionLevel() > 0 ? $generate() : DB::transaction($generate);
    }

    private function defaultPrefix(string $key): string
    {
        return (string) match ($key) {
            'invoice' => $this->settings->get('invoice.prefix', 'INV'),
            'job' => $this->settings->get('invoice.job_prefix', 'JOB'),
            'payment' => $this->settings->get('invoice.payment_prefix', 'RCT'),
            'purchase' => $this->settings->get('invoice.purchase_prefix', 'PUR'),
            'expense' => $this->settings->get('invoice.expense_prefix', 'EXP'),
            default => strtoupper(substr($key, 0, 3)),
        };
    }
}
