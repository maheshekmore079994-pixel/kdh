<?php

namespace App\Livewire\Reports;

use App\Services\ReportBuilder;
use App\Services\ReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;

class Show extends Component
{
    public string $report;

    #[Url]
    public string $period = 'today';

    #[Url]
    public ?string $from = null;

    #[Url]
    public ?string $to = null;

    public function mount(string $report): void
    {
        Gate::authorize('reports');
        abort_unless(ReportBuilder::exists($report), 404);
        $this->report = $report;
        if (in_array($report, ['sales', 'payments', 'expenses', 'purchases', 'profit-loss'], true) && $this->period === 'today' && ! request()->has('period')) {
            $this->period = 'month';
        }
    }

    public function render(ReportBuilder $builder): View
    {
        [$start, $end] = ReportService::resolveRange($this->period, $this->from, $this->to);
        $data = $builder->build($this->report, $start, $end);

        return view('livewire.reports.show', ['data' => $data, 'start' => $start, 'end' => $end])->title(__($data['title']));
    }
}
