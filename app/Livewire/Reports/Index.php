<?php

namespace App\Livewire\Reports;

use App\Services\ReportBuilder;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Reports')]
class Index extends Component
{
    public function mount(): void
    {
        Gate::authorize('reports');
    }

    public function render(): View
    {
        return view('livewire.reports.index', ['reports' => ReportBuilder::REPORTS]);
    }
}
