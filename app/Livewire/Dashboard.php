<?php

namespace App\Livewire;

use App\Services\ReportService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Dashboard')]
class Dashboard extends Component
{
    #[Url]
    public string $period = 'today';

    #[Url]
    public ?string $from = null;

    #[Url]
    public ?string $to = null;

    public function render(ReportService $reports): View
    {
        [$start, $end] = ReportService::resolveRange($this->period, $this->from, $this->to);
        $user = auth()->user();

        $canReports = $user->can('reports');
        $canBilling = $user->can('billing');

        return view('livewire.dashboard', [
            'start' => $start,
            'end' => $end,
            'sales' => $canReports || $canBilling ? $reports->salesSummary($start, $end) : null,
            'receipts' => $canReports || $canBilling ? $reports->receiptsByMethod($start, $end) : null,
            'expenses' => $canReports || $user->can('expenses') ? $reports->expensesTotal($start, $end) : null,
            'profit' => $canReports ? $reports->profitEstimate($start, $end) : null,
            'jobs' => $reports->jobStats($start, $end),
            'dues' => $canReports || $canBilling ? $reports->customerDuesTotal() : null,
            'lowStock' => $user->can('inventory') ? $reports->lowStockProducts((int) setting('app.low_stock_dashboard_limit', 5)) : collect(),
            'lowStockCount' => $user->can('inventory') ? $reports->lowStockCount() : 0,
            'activity' => $canBilling ? $reports->recentActivity(8) : collect(),
        ]);
    }
}
