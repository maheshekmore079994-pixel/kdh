<?php

namespace App\Livewire\Purchases;

use App\Models\Supplier;
use App\Services\AuditService;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Suppliers')]
class Suppliers extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public ?int $editingId = null;

    public string $name = '';

    public string $phone = '';

    public string $email = '';

    public string $address = '';

    public string $gstin = '';

    public string $notes = '';

    public bool $isActive = true;

    public function mount(): void
    {
        Gate::authorize('purchases');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->resetForm();
        Flux::modal('supplier-form')->show();
    }

    public function edit(int $id): void
    {
        $s = Supplier::query()->findOrFail($id);
        $this->resetForm();
        $this->editingId = $s->id;
        $this->name = $s->name;
        $this->phone = (string) $s->phone;
        $this->email = (string) $s->email;
        $this->address = (string) $s->address;
        $this->gstin = (string) $s->gstin;
        $this->notes = (string) $s->notes;
        $this->isActive = $s->is_active;
        Flux::modal('supplier-form')->show();
    }

    public function save(AuditService $audit): void
    {
        Gate::authorize('purchases');
        $v = $this->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:500'],
            'gstin' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'isActive' => ['boolean'],
        ]);
        $data = ['name' => trim($v['name']), 'phone' => $v['phone'] ?: null, 'email' => $v['email'] ?: null, 'address' => $v['address'] ?: null, 'gstin' => $v['gstin'] ?: null, 'notes' => $v['notes'] ?: null, 'is_active' => $v['isActive']];

        if ($this->editingId) {
            $s = Supplier::query()->findOrFail($this->editingId);
            $s->fill($data);
            $audit->logChanges('supplier.updated', $s);
            $s->save();
        } else {
            $s = Supplier::query()->create($data);
            $audit->log('supplier.created', $s, [], ['name' => $s->name]);
        }

        Flux::modal('supplier-form')->close();
        $this->resetForm();
        Flux::toast(variant: 'success', text: __('Supplier saved.'));
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'phone', 'email', 'address', 'gstin', 'notes', 'isActive']);
        $this->resetValidation();
    }

    public function render(): View
    {
        return view('livewire.purchases.suppliers', [
            'suppliers' => Supplier::query()->search($this->search)
                ->withCount('purchases')
                ->withSum(['purchases as due' => fn ($q) => $q->where('status', 'posted')], 'balance_due')
                ->orderBy('name')->paginate(20),
        ]);
    }
}
