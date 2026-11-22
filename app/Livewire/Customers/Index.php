<?php

namespace App\Livewire\Customers;

use App\Models\Customer;
use App\Services\AuditService;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Customers')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public ?int $editingId = null;

    public string $name = '';

    public string $mobile = '';

    public string $email = '';

    public string $address = '';

    public string $notes = '';

    public ?Customer $duplicate = null;

    public function mount(): void
    {
        Gate::authorize('customers');
        if (request()->boolean('new')) {
            $this->create();
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedMobile(): void
    {
        $this->duplicate = preg_match('/^\d{10}$/', $this->mobile)
            ? Customer::query()->where('mobile', $this->mobile)->when($this->editingId, fn ($q) => $q->where('id', '!=', $this->editingId))->first()
            : null;
    }

    public function create(): void
    {
        Gate::authorize('customers');
        $this->resetForm();
        Flux::modal('customer-form')->show();
    }

    public function edit(int $id): void
    {
        Gate::authorize('customers');
        $c = Customer::query()->findOrFail($id);
        $this->resetForm();
        $this->editingId = $c->id;
        $this->name = $c->name;
        $this->mobile = $c->mobile;
        $this->email = (string) $c->email;
        $this->address = (string) $c->address;
        $this->notes = (string) $c->notes;
        Flux::modal('customer-form')->show();
    }

    public function save(AuditService $audit): void
    {
        Gate::authorize('customers');

        $v = $this->validate([
            'name' => ['required', 'string', 'max:150'],
            'mobile' => ['required', 'regex:/^[6-9]\d{9}$/'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], ['mobile.regex' => __('Enter a valid 10-digit Indian mobile number.')]);

        $dupe = Customer::query()->where('mobile', $v['mobile'])->when($this->editingId, fn ($q) => $q->where('id', '!=', $this->editingId))->first();
        if ($dupe) {
            $this->addError('mobile', __('This mobile number already belongs to :name.', ['name' => $dupe->name]));

            return;
        }

        $data = [
            'name' => trim($v['name']),
            'mobile' => $v['mobile'],
            'email' => $v['email'] ?: null,
            'address' => $v['address'] ?: null,
            'notes' => $v['notes'] ?: null,
        ];

        if ($this->editingId) {
            $c = Customer::query()->findOrFail($this->editingId);
            $c->fill($data);
            $audit->logChanges('customer.updated', $c);
            $c->save();
            Flux::toast(variant: 'success', text: __('Customer updated.'));
        } else {
            $c = Customer::query()->create($data + ['created_by' => Auth::id()]);
            $audit->log('customer.created', $c, [], ['name' => $c->name, 'mobile' => $c->mobile]);
            Flux::toast(variant: 'success', text: __('Customer added.'));
        }

        Flux::modal('customer-form')->close();
        $this->resetForm();
    }

    public function delete(int $id, AuditService $audit): void
    {
        Gate::authorize('customers');
        $c = Customer::query()->withCount(['invoices', 'jobs'])->findOrFail($id);
        if ($c->invoices_count > 0 || $c->jobs_count > 0) {
            Flux::toast(variant: 'danger', text: __('This customer has bills or jobs and cannot be deleted.'));

            return;
        }
        $audit->log('customer.deleted', $c, ['name' => $c->name, 'mobile' => $c->mobile], []);
        $c->delete();
        Flux::toast(variant: 'success', text: __('Customer removed.'));
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'mobile', 'email', 'address', 'notes', 'duplicate']);
        $this->resetValidation();
    }

    public function render(): View
    {
        $customers = Customer::query()
            ->search($this->search)
            ->withCount('invoices')
            ->withSum(['invoices as due' => fn ($q) => $q->where('status', '!=', 'cancelled')], 'balance_due')
            ->orderBy('name')
            ->paginate(20);

        return view('livewire.customers.index', ['customers' => $customers]);
    }
}
