<?php

namespace App\Livewire\Jobs;

use App\Enums\JobStatus;
use App\Exceptions\DomainException;
use App\Models\ServiceJob;
use App\Services\JobService;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Jobs')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = 'open';

    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    public function mount(): void
    {
        Gate::authorize('jobs');
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    public function setStatus(int $id, string $status, JobService $jobs): void
    {
        Gate::authorize('jobs');
        $job = ServiceJob::query()->findOrFail($id);
        try {
            $jobs->changeStatus($job, JobStatus::from($status));
            Flux::toast(variant: 'success', text: __(':n marked :s.', ['n' => $job->job_number, 's' => JobStatus::from($status)->label()]));
        } catch (DomainException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());
        }
    }

    public function render(): View
    {
        $jobs = ServiceJob::query()->with(['customer', 'invoice', 'assignee'])
            ->search($this->search)
            ->when($this->status === 'open', fn ($q) => $q->whereIn('status', ['pending', 'processing']))
            ->when($this->status === 'overdue', fn ($q) => $q->whereIn('status', ['pending', 'processing'])->whereNotNull('expected_at')->where('expected_at', '<', now()))
            ->when($this->status && ! in_array($this->status, ['open', 'overdue', 'all'], true), fn ($q) => $q->where('status', $this->status))
            ->when($this->from, fn ($q) => $q->where('received_at', '>=', $this->from.' 00:00:00'))
            ->when($this->to, fn ($q) => $q->where('received_at', '<=', $this->to.' 23:59:59'))
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'processing' THEN 1 WHEN 'completed' THEN 2 ELSE 3 END")
            ->latest('received_at')
            ->paginate(20);

        return view('livewire.jobs.index', ['jobs' => $jobs, 'statuses' => JobStatus::cases()]);
    }
}
