<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrintCostProfile extends Model
{
    protected $fillable = [
        'name', 'paper_cost_per_sheet', 'ink_cost_per_page', 'maintenance_per_page', 'other_cost_per_page',
        'notes', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'paper_cost_per_sheet' => 'decimal:4',
            'ink_cost_per_page' => 'decimal:4',
            'maintenance_per_page' => 'decimal:4',
            'other_cost_per_page' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    /** Estimated cost per page (sheet cost divided by sides + per-page costs). */
    public function costPerPage(int $sidesPerSheet = 1): string
    {
        $sides = max(1, $sidesPerSheet);
        $paper = bcdiv((string) ($this->paper_cost_per_sheet ?? '0'), (string) $sides, 4);
        $total = bcadd($paper, (string) ($this->ink_cost_per_page ?? '0'), 4);
        $total = bcadd($total, (string) ($this->maintenance_per_page ?? '0'), 4);

        return bcadd($total, (string) ($this->other_cost_per_page ?? '0'), 4);
    }
}
