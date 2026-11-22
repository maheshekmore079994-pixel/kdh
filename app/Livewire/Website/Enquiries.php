<?php

namespace App\Livewire\Website;

use App\Enums\EnquiryStatus;
use App\Models\Enquiry;
use App\Services\AuditService;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Enquiries')]
class Enquiries extends Component
{
    use WithPagination;

    #[Url]
    public string $status = 'new';

    #[Url]
    public string $search = '';

    public ?int $viewingId = null;

    public string $note = '';

    public function mount(): void
    {
        Gate::authorize('website');
    }

    public function updated($name): void
    {
        if (in_array($name, ['status', 'search'], true)) {
            $this->resetPage();
        }
    }

    public function view(int $id): void
    {
        $e = Enquiry::query()->findOrFail($id);
        $this->viewingId = $e->id;
        $this->note = (string) $e->notes;
        Flux::modal('enquiry-view')->show();
    }

    public function setStatus(string $status, AuditService $audit): void
    {
        Gate::authorize('website');
        $e = Enquiry::query()->findOrFail($this->viewingId);
        $e->status = EnquiryStatus::from($status);
        $e->notes = $this->note ?: null;
        $e->handled_by = auth()->id();
        $e->handled_at = now();
        $audit->logChanges('enquiry.updated', $e);
        $e->save();
        Flux::modal('enquiry-view')->close();
        Flux::toast(variant: 'success', text: __('Enquiry marked :s.', ['s' => $e->status->label()]));
    }

    public function render(): View
    {
        return view('livewire.website.enquiries', [
            'enquiries' => Enquiry::query()->with('handler')
                ->when($this->status !== 'all', fn ($q) => $q->where('status', $this->status))
                ->when($this->search, fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%{$this->search}%")->orWhere('phone', 'like', "%{$this->search}%")->orWhere('message', 'like', "%{$this->search}%")))
                ->latest()->paginate(20),
            'viewing' => $this->viewingId ? Enquiry::query()->find($this->viewingId) : null,
            'newCount' => Enquiry::query()->where('status', 'new')->count(),
        ]);
    }
}
