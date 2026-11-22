<?php

namespace App\Livewire\Configuration;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Audit log')]
class AuditLogs extends Component
{
    use WithPagination;

    #[Url]
    public string $action = '';

    #[Url]
    public string $user = '';

    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    public function mount(): void
    {
        Gate::authorize('settings');
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $logs = AuditLog::query()->with('user')
            ->when($this->action, fn ($q) => $q->where('action', 'like', $this->action.'%'))
            ->when($this->user, fn ($q) => $q->where('user_id', $this->user))
            ->when($this->from, fn ($q) => $q->where('created_at', '>=', $this->from.' 00:00:00'))
            ->when($this->to, fn ($q) => $q->where('created_at', '<=', $this->to.' 23:59:59'))
            ->latest('id')
            ->paginate(25);

        return view('livewire.configuration.audit-logs', [
            'logs' => $logs,
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'actions' => AuditLog::query()->select('action')->distinct()->orderBy('action')->pluck('action'),
        ]);
    }
}
